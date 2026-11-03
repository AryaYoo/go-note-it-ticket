<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\GeminiService;
use App\Exports\TicketsExport;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class TicketController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

    public function index(Request $request)
    {
        $query = Ticket::with('user')
            ->filterStatus($request->status)
            ->filterKategori($request->kategori)
            ->filterPrioritas($request->prioritas)
            ->filterTanggal($request->date_from, $request->date_to)
            ->filterSearch($request->search)
            ->orderByDesc('tanggal_kejadian')
            ->orderByDesc('id');

        $tickets = $query->paginate(15)->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $suggestNomor = Ticket::generateNextNomorTiket(now(), (int) $user->company_id);

        // Pre-fill dari pengecekan harian
        $prefill = [];
        if ($request->boolean('from_check')) {
            $prefill = [
                'from_check'          => true,
                'item_ids'            => $request->input('item_ids', ''),
                'tanggal_kejadian'    => $request->input('tanggal_kejadian', date('Y-m-d')),
                'divisi_toko'         => $request->input('divisi_toko', ''),
                'store_id'            => $request->input('store_id', ''),
                'deskripsi_kendala'   => $request->input('deskripsi_kendala', ''),
                'kategori'            => $request->input('kategori', ''),
                'prioritas'           => $request->input('prioritas', 'Medium'),
                'tindakan_dilakukan'  => $request->input('tindakan_dilakukan', ''),
                'dampak_operasional'  => $request->input('dampak_operasional', ''),
            ];
        }

        return view('tickets.create', compact('user', 'suggestNomor', 'prefill'));
    }

    /**
     * Analisis hingga 3 gambar WhatsApp sekaligus via Gemini API.
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'images'   => 'required|array|min:1|max:3',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ], [
            'images.required' => 'Harap unggah minimal 1 gambar.',
            'images.max'      => 'Maksimal 3 gambar per analisis.',
            'images.*.image'  => 'Setiap file harus berupa gambar.',
            'images.*.max'    => 'Ukuran setiap gambar maksimal 10 MB.',
        ]);

        try {
            $tmpPaths = [];
            foreach ($request->file('images') as $file) {
                $tmpPaths[] = $file->getRealPath();
            }

            $result = $this->gemini->analyzeWhatsAppScreenshot($tmpPaths);

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function store(StoreTicketRequest $request)
    {
        $data            = $request->validated();
        $data['user_id']     = Auth::id();
        $data['company_id']  = Auth::user()->company_id; // ikat tiket ke perusahaan user
        $data['pic']         = $data['pic'] ?? Auth::user()->name;

        // Jika tiket dibuat dari pengecekan harian, pastikan perangkat belum memiliki tiket aktif yang belum Closed
        if ($request->filled('_item_ids')) {
            $itemIds = array_filter(array_map('intval', explode(',', $request->input('_item_ids'))));
            if (!empty($itemIds)) {
                $checkItems = \App\Models\DailyCheckItem::with('device.tickets')->whereIn('id', $itemIds)->get();
                foreach ($checkItems as $ci) {
                    $existingOpenTicket = $ci->device?->tickets()->where('status', '!=', 'Closed')->latest('tickets.id')->first();
                    if ($existingOpenTicket) {
                        return response()->json([
                            'success' => false,
                            'message' => "Perangkat '{$ci->device->display_name}' masih memiliki tiket aktif (#{$existingOpenTicket->nomor_tiket}) yang belum Closed. Tidak bisa membuat tiket baru sampai tiket tersebut Closed.",
                        ], 422);
                    }
                }
            }
        }

        // Gunakan Database Transaction & Locking untuk auto-correction nomor tiket
        $ticket = DB::transaction(function () use ($data, $request) {
            // Auto-correct nomor tiket dengan memperhitungkan soft-deleted
            $companyId = $data['company_id'] ?? Auth::user()->company_id;
            $data['nomor_tiket'] = Ticket::generateNextNomorTiket($data['tanggal_kejadian'], (int) $companyId);

            // Tandai sumber preventive jika dari pengecekan harian
            if ($request->filled('_item_ids')) {
                $data['source']  = 'preventive';
            }

            // Simpan hingga 3 gambar menggunakan nomor_tiket final
            if ($request->hasFile('lampiran_gambar')) {
                $companyId = $data['company_id'] ?? Auth::user()->company_id ?? 'shared';
                $paths = [];
                foreach ($request->file('lampiran_gambar') as $index => $file) {
                    $paths[] = $file->store(
                        'tickets/company_' . $companyId . '/' . $data['nomor_tiket'],
                        'private'
                    );
                }
                $data['lampiran_gambar'] = $paths; // disimpan sebagai array → JSON via cast
            } else {
                $data['lampiran_gambar'] = null;
            }

            $createdTicket = Ticket::create($data);

            // Audit Log: Tiket baru dibuat
            \App\Models\TicketHistory::create([
                'company_id'  => $createdTicket->company_id,
                'ticket_id'   => $createdTicket->id,
                'user_id'     => Auth::id(),
                'action'      => 'created',
                'description' => 'Tiket dibuat dengan status ' . $createdTicket->status . ' dan prioritas ' . ($createdTicket->prioritas ?? '-'),
            ]);

            // Tandai item pengecekan sebagai ticket_created jika dari daily check
            if ($request->filled('_item_ids')) {
                $itemIds = array_filter(array_map('intval', explode(',', $request->input('_item_ids'))));
                if (!empty($itemIds)) {
                    \App\Http\Controllers\DailyCheckController::markItemsAsTicketed($itemIds, $createdTicket->id);
                }
            }

            return $createdTicket;
        });

        return response()->json([
            'success'     => true,
            'message'     => 'Tiket berhasil disimpan.',
            'redirect'    => route('tickets.show', $ticket),
            'nomor_tiket' => $ticket->nomor_tiket,
        ]);
    }

    public function show(Ticket $ticket)
    {
        Gate::authorize('view', $ticket);
        $ticket->load(['histories.user', 'user']);
        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket)
    {
        Gate::authorize('update', $ticket);
        $user = Auth::user();
        return view('tickets.edit', compact('ticket', 'user'));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        Gate::authorize('update', $ticket);
        $data = $request->validated();

        if ($request->hasFile('lampiran_gambar')) {
            // Hapus semua file lama
            if ($ticket->lampiran_gambar) {
                foreach ((array) $ticket->lampiran_gambar as $oldPath) {
                    Storage::disk('private')->delete($oldPath);
                }
            }
            // Simpan file-file baru terisolasi per perusahaan
            $companyId = $ticket->company_id ?? Auth::user()->company_id ?? 'shared';
            $paths = [];
            foreach ($request->file('lampiran_gambar') as $file) {
                $paths[] = $file->store('tickets/company_' . $companyId . '/' . $ticket->nomor_tiket, 'private');
            }
            $data['lampiran_gambar'] = $paths;
        } else {
            // Jangan ubah lampiran jika tidak ada file baru
            unset($data['lampiran_gambar']);
        }

        // Lacak perubahan untuk audit log
        $tracked = ['status', 'prioritas', 'kategori', 'divisi_toko', 'pic', 'solusi_diberikan'];
        $changes = [];
        $descriptions = [];
        foreach ($tracked as $field) {
            if (isset($data[$field]) && $data[$field] != $ticket->{$field}) {
                $changes[$field] = [
                    'old' => $ticket->{$field},
                    'new' => $data[$field],
                ];
                $descriptions[] = ucfirst(str_replace('_', ' ', $field)) . " diubah dari '{$ticket->{$field}}' menjadi '{$data[$field]}'";
            }
        }

        $ticket->update($data);

        // Audit Log: Perubahan tiket
        if (!empty($descriptions)) {
            \App\Models\TicketHistory::create([
                'company_id'  => $ticket->company_id,
                'ticket_id'   => $ticket->id,
                'user_id'     => Auth::id(),
                'action'      => isset($changes['status']) ? 'status_changed' : 'updated',
                'description' => implode('; ', $descriptions),
                'changes'     => $changes,
            ]);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Tiket berhasil diperbarui.',
            'redirect' => route('tickets.show', $ticket),
        ]);
    }

    /**
     * Analisis gambar bukti penyelesaian tiket via Gemini AI.
     */
    public function analyzeResolution(Request $request, Ticket $ticket)
    {
        Gate::authorize('update', $ticket);

        $request->validate([
            'images'   => 'required|array|min:1|max:3',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ], [
            'images.required' => 'Harap unggah minimal 1 gambar bukti.',
            'images.max'      => 'Maksimal 3 gambar per analisis.',
            'images.*.image'  => 'Setiap file harus berupa gambar.',
            'images.*.max'    => 'Ukuran setiap gambar maksimal 10 MB.',
        ]);

        try {
            $tmpPaths = [];
            foreach ($request->file('images') as $file) {
                $tmpPaths[] = $file->getRealPath();
            }

            $result = $this->gemini->analyzeResolutionScreenshot($tmpPaths, $ticket);

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function close(Request $request, Ticket $ticket)
    {
        Gate::authorize('update', $ticket);

        if ($ticket->status === 'Closed') {
            return response()->json(['success' => false, 'message' => 'Tiket sudah berstatus Closed.'], 422);
        }

        $request->validate([
            'tanggal_penyelesaian' => 'nullable|date',
            'tindakan_dilakukan'   => 'nullable|string',
            'solusi_diberikan'     => 'nullable|string',
            'root_cause'           => 'nullable|string',
            'lampiran_gambar'      => 'nullable|array|max:3',
            'lampiran_gambar.*'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $closedDate = $request->input('tanggal_penyelesaian') ?: now()->toDateString();
        $oldStatus = $ticket->status;
        $oldClosedDate = $ticket->tanggal_penyelesaian ? $ticket->tanggal_penyelesaian->format('Y-m-d') : null;

        $updateData = [
            'status'               => 'Closed',
            'tanggal_penyelesaian' => $closedDate,
        ];

        if ($request->has('tindakan_dilakukan')) {
            $updateData['tindakan_dilakukan'] = $request->input('tindakan_dilakukan');
        }
        if ($request->has('solusi_diberikan')) {
            $updateData['solusi_diberikan'] = $request->input('solusi_diberikan');
        }
        if ($request->has('root_cause')) {
            $updateData['root_cause'] = $request->input('root_cause');
        }

        $newPhotosCount = 0;
        if ($request->hasFile('lampiran_gambar')) {
            $existing = is_array($ticket->lampiran_gambar)
                ? $ticket->lampiran_gambar
                : ($ticket->lampiran_gambar ? [$ticket->lampiran_gambar] : []);

            $companyId = $ticket->company_id ?? Auth::user()->company_id ?? 'shared';
            foreach ($request->file('lampiran_gambar') as $file) {
                $path = $file->store('tickets/company_' . $companyId . '/' . $ticket->nomor_tiket, 'private');
                $existing[] = $path;
                $newPhotosCount++;
            }
            $updateData['lampiran_gambar'] = $existing;
        }

        $ticket->update($updateData);

        $desc = "Status diubah dari '{$oldStatus}' menjadi 'Closed' (Tgl Selesai: " . date('d/m/Y', strtotime($closedDate)) . ")";
        if ($newPhotosCount > 0) {
            $desc .= "; Ditambahkan {$newPhotosCount} foto bukti penyelesaian";
        }
        if (!empty($updateData['solusi_diberikan'])) {
            $desc .= "; Solusi diperbarui";
        }

        \App\Models\TicketHistory::create([
            'company_id'  => $ticket->company_id,
            'ticket_id'   => $ticket->id,
            'user_id'     => Auth::id(),
            'action'      => 'status_changed',
            'description' => $desc,
            'changes'     => [
                'status'               => ['old' => $oldStatus, 'new' => 'Closed'],
                'tanggal_penyelesaian' => ['old' => $oldClosedDate, 'new' => $closedDate],
            ],
        ]);

        return response()->json(['success' => true, 'message' => 'Tiket berhasil ditutup.']);
    }

    /**
     * Kembalikan tiket pada tanggal tertentu atau semua tiket Open dalam format JSON untuk fitur Share Text.
     */
    public function shareText(Request $request)
    {
        if ($request->boolean('all_open') || $request->input('scope') === 'all_open') {
            $tickets = Ticket::where('status', '!=', 'Closed')
                ->orderBy('prioritas', 'asc')
                ->orderBy('nomor_tiket', 'asc')
                ->get(['nomor_tiket', 'tanggal_kejadian', 'nama_pelapor', 'divisi_toko',
                       'kategori', 'prioritas', 'deskripsi_kendala', 'status', 'pic',
                       'tindakan_dilakukan', 'solusi_diberikan']);

            return response()->json(['tickets' => $tickets, 'mode' => 'all_open']);
        }

        $request->validate(['date' => 'required|date_format:Y-m-d']);

        $tickets = Ticket::whereDate('tanggal_kejadian', $request->date)
            ->orderBy('prioritas', 'asc')
            ->orderBy('nomor_tiket', 'asc')
            ->get(['nomor_tiket', 'tanggal_kejadian', 'nama_pelapor', 'divisi_toko',
                   'kategori', 'prioritas', 'deskripsi_kendala', 'status', 'pic',
                   'tindakan_dilakukan', 'solusi_diberikan']);

        return response()->json(['tickets' => $tickets, 'mode' => 'date']);
    }

    public function destroy(Ticket $ticket)
    {
        Gate::authorize('delete', $ticket);

        // Audit Log: Soft delete tiket
        \App\Models\TicketHistory::create([
            'company_id'  => $ticket->company_id,
            'ticket_id'   => $ticket->id,
            'user_id'     => Auth::id(),
            'action'      => 'deleted',
            'description' => 'Tiket dihapus (soft delete) oleh ' . Auth::user()->name,
        ]);

        $ticket->delete();

        return response()->json(['success' => true, 'message' => 'Tiket berhasil dihapus.']);
    }

    public function export(Request $request)
    {
        $filename = 'Form Ticket IT - ' . now()->format('m-Y') . '.xlsx';
        return Excel::download(new TicketsExport($request->all()), $filename);
    }

    /**
     * Sajikan gambar lampiran privat. index = 0, 1, atau 2.
     */
    public function image(Ticket $ticket, int $index = 0)
    {
        Gate::authorize('view', $ticket);

        $paths = (array) ($ticket->lampiran_gambar ?? []);

        if (empty($paths) || !isset($paths[$index])) {
            abort(404);
        }

        $path = $paths[$index];
        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        $mime = Storage::disk('private')->mimeType($path);
        return response(Storage::disk('private')->get($path), 200)
            ->header('Content-Type', $mime)
            ->header('Cache-Control', 'private, max-age=3600');
    }
}
