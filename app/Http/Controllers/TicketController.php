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
            ->latest();

        $tickets = $query->paginate(15)->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        $user = Auth::user();
        $today   = now();
        $prefix  = 'IT-' . $today->format('dmY') . '-';
        $lastNum = Ticket::where('nomor_tiket', 'like', $prefix . '%')->count() + 1;
        $suggestNomor = $prefix . str_pad($lastNum, 3, '0', STR_PAD_LEFT);

        return view('tickets.create', compact('user', 'suggestNomor'));
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

        // Gunakan Database Transaction & Locking untuk auto-correction nomor tiket
        $ticket = DB::transaction(function () use ($data, $request) {
            $tgl = \Carbon\Carbon::parse($data['tanggal_kejadian']);
            $prefix  = 'IT-' . $tgl->format('dmY') . '-';
            
            // Ambil tiket terakhir di hari yang sama, lock barisnya agar tidak ada user lain yang baca (prevent race condition)
            $lastTicket = Ticket::where('nomor_tiket', 'like', $prefix . '%')
                                ->lockForUpdate()
                                ->orderBy('nomor_tiket', 'desc')
                                ->first();
                                
            $lastNum = 0;
            if ($lastTicket) {
                $parts = explode('-', $lastTicket->nomor_tiket);
                $lastNum = (int) end($parts);
            }
            
            // Auto-correct nomor tiket (Abaikan nomor dari form frontend, buat yang final)
            $nextNum = $lastNum + 1;
            $data['nomor_tiket'] = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

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
