<?php

namespace App\Http\Controllers;

use App\Models\DailyCheck;
use App\Models\DailyCheckItem;
use App\Models\Device;
use App\Models\Store;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailyCheckController extends Controller
{
    /**
     * Tampilkan atau buat sesi pengecekan harian untuk hari ini.
     */
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $today     = now()->toDateString();

        // Ambil atau buat sesi hari ini
        $check = DailyCheck::firstOrCreate(
            ['company_id' => $companyId, 'check_date' => $today],
            ['user_id' => Auth::id()]
        );

        // Ambil semua device aktif dengan tiket yang masih Open / belum Closed
        $devices = Device::with(['store', 'tickets' => function ($q) {
            $q->where('status', '!=', 'Closed')->latest('tickets.id');
        }])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('store_id')
            ->orderBy('alias')
            ->get();

        // Sinkronkan: buat item yang belum ada dan sesuaikan status dengan tiket aktif
        foreach ($devices as $device) {
            $openTicket = $device->tickets->first();

            $item = DailyCheckItem::firstOrCreate([
                'daily_check_id' => $check->id,
                'device_id'      => $device->id,
            ]);

            if ($openTicket) {
                // Perangkat memiliki tiket aktif yang belum Closed:
                // Pada pengecekan hari berikutnya/saat ini, perangkat tetap offline dan berstatus bertiket
                $updates = [];
                if (!$item->is_offline) {
                    $updates['is_offline'] = true;
                }
                if ($item->result !== DailyCheckItem::RESULT_TICKET_CREATED) {
                    $updates['result'] = DailyCheckItem::RESULT_TICKET_CREATED;
                }
                if (!$item->offline_since) {
                    $updates['offline_since'] = $openTicket->created_at ?? now();
                }
                if (!empty($updates)) {
                    $item->update($updates);
                }
            } else {
                // Tiket sudah Closed atau belum pernah ada tiket.
                // Jika item sesi ini sebelumnya ditandai ticket_created karena tiket kemarin yang sekarang sudah Closed,
                // kembalikan ke status normal (online / OK).
                if ($item->result === DailyCheckItem::RESULT_TICKET_CREATED) {
                    $item->update([
                        'is_offline'    => false,
                        'result'        => null,
                        'offline_since' => null,
                    ]);
                }
            }
        }

        // Reload items dengan relasi lengkap
        $check->load([
            'items.device.store',
            'items.device.tickets' => function ($q) {
                $q->latest('tickets.id');
            },
            'items.tickets',
        ]);

        // Kelompokkan items per toko
        $itemsByStore = $check->items->groupBy(fn($item) => $item->device->store_id ?? 0);
        $stores       = Store::where('company_id', $companyId)->orderBy('name')->get()->keyBy('id');

        return view('daily-check.index', compact('check', 'itemsByStore', 'stores', 'today'));
    }

    /**
     * Simpan hasil pengecekan (checklist: offline/online + isian).
     * Dipanggil via AJAX (JSON response).
     */
    public function saveItems(Request $request)
    {
        $request->validate([
            'items'                    => 'required|array',
            'items.*.id'               => 'required|exists:daily_check_items,id',
            'items.*.is_offline'       => 'required|boolean',
            'items.*.offline_since'    => 'nullable|date',
            'items.*.affected_channels'=> 'nullable|string|max:500',
            'items.*.notes'            => 'nullable|string|max:500',
        ]);

        foreach ($request->input('items') as $itemData) {
            $item = DailyCheckItem::with('device.tickets')->findOrFail($itemData['id']);

            // Cek apakah device memiliki tiket yang masih Open / belum Closed
            $hasOpenTicket = $item->device?->tickets()->where('status', '!=', 'Closed')->exists();

            if ($hasOpenTicket) {
                // Jika tiketnya masih Open, perangkat harus tetap offline dan result ticket_created
                $item->update([
                    'is_offline'        => true,
                    'result'            => DailyCheckItem::RESULT_TICKET_CREATED,
                    'affected_channels' => $itemData['affected_channels'] ?? $item->affected_channels,
                    'notes'             => $itemData['notes'] ?? $item->notes,
                ]);
                continue;
            }

            $item->update([
                'is_offline'        => $itemData['is_offline'],
                'offline_since'     => $itemData['is_offline'] ? ($itemData['offline_since'] ?? null) : null,
                'affected_channels' => $itemData['is_offline'] ? ($itemData['affected_channels'] ?? null) : null,
                'notes'             => $itemData['notes'] ?? null,
                // Reset result jika di-ubah ke online lagi
                'result'            => $itemData['is_offline'] ? $item->result : null,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Data pengecekan berhasil disimpan.']);
    }

    /**
     * Set hasil penyelesaian satu item: fixed / no_fault.
     */
    public function resolveItem(Request $request, DailyCheckItem $item)
    {
        $request->validate([
            'result' => 'required|in:fixed,no_fault',
            'notes'  => 'nullable|string|max:500',
        ]);

        // Cek jika ada tiket aktif yang belum Closed
        $openTicket = $item->device?->tickets()->where('status', '!=', 'Closed')->latest('tickets.id')->first();
        if ($openTicket) {
            return response()->json([
                'success' => false,
                'message' => "Perangkat ini memiliki tiket aktif (#{$openTicket->nomor_tiket}) yang belum Closed. Selesaikan tiket terlebih dahulu.",
            ], 422);
        }

        $item->update([
            'result' => $request->result,
            'notes'  => $request->notes,
        ]);

        return response()->json(['success' => true, 'message' => 'Status item diperbarui.']);
    }

    /**
     * Redirect ke halaman buat tiket dengan data pre-filled dari item pengecekan.
     * Bisa menerima satu atau banyak item dari toko yang sama.
     */
    public function createTicketFromItems(Request $request)
    {
        $request->validate([
            'item_ids'         => 'required|array|min:1',
            'item_ids.*'       => 'required|exists:daily_check_items,id',
            'tanggal_kejadian' => 'required|date',
        ]);

        $itemIds = $request->input('item_ids');
        $items   = DailyCheckItem::with(['device.store', 'device.tickets'])->findMany($itemIds);

        // Validasi: pastikan tidak ada device yang masih memiliki tiket aktif (belum Closed)
        foreach ($items as $item) {
            $openTicket = $item->device?->tickets()->where('status', '!=', 'Closed')->latest('tickets.id')->first();
            if ($openTicket) {
                $deviceName = $item->device->alias ?: ($item->device->type . ' ' . ($item->device->model ?? ''));
                return response()->json([
                    'success' => false,
                    'message' => "Perangkat '{$deviceName}' masih memiliki tiket aktif (#{$openTicket->nomor_tiket}) yang belum Closed. Tidak bisa membuat tiket baru sampai tiket tersebut Closed.",
                ], 422);
            }
        }

        // Validasi: semua item harus dari toko yang sama
        $storeIds = $items->map(fn($i) => $i->device->store_id)->unique();
        if ($storeIds->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Semua device harus berasal dari toko yang sama untuk digabung dalam satu tiket.',
            ], 422);
        }

        $built = $this->buildTicketDataForItems($items, $request->tanggal_kejadian);

        return response()->json([
            'success'      => true,
            'redirect_url' => route('tickets.create', [
                'from_check'          => 1,
                'item_ids'            => implode(',', $itemIds),
                'tanggal_kejadian'    => $request->tanggal_kejadian,
                'divisi_toko'         => $built['store_name'],
                'store_id'            => $built['store']?->id,
                'deskripsi_kendala'   => $built['deskripsi'],
                'kategori'            => $built['kategori'],
                'prioritas'           => $built['prioritas'],
                'tindakan_dilakukan'  => $built['tindakan'],
                'dampak_operasional'  => $built['dampak'],
            ]),
        ]);
    }

    /**
     * Otomatis membuat tiket untuk seluruh device yang dicentang offline,
     * dikelompokkan per toko (1 tiket per toko).
     */
    public function batchCreateTickets(Request $request)
    {
        $request->validate([
            'item_ids'         => 'required|array|min:1',
            'item_ids.*'       => 'required|exists:daily_check_items,id',
            'tanggal_kejadian' => 'required|date',
        ]);

        $itemIds = $request->input('item_ids');
        $items   = DailyCheckItem::with(['device.store', 'device.tickets'])->findMany($itemIds);

        // Hanya proses yang belum memiliki tiket dan devicenya tidak memiliki tiket aktif yang belum Closed
        $unprocessedItems = $items->filter(function ($item) {
            if ($item->result === DailyCheckItem::RESULT_TICKET_CREATED) {
                return false;
            }
            $hasOpenTicket = $item->device?->tickets()->where('status', '!=', 'Closed')->exists();
            return !$hasOpenTicket;
        });

        if ($unprocessedItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Semua perangkat yang dipilih sudah pernah dibuatkan tiket atau sedang dalam penanganan (tiket belum Closed).',
            ], 422);
        }

        // Kelompokkan per toko
        $grouped = $unprocessedItems->groupBy(fn($i) => $i->device->store_id ?? 0);
        $user = Auth::user();
        $createdTickets = [];

        DB::beginTransaction();
        try {
            foreach ($grouped as $storeId => $storeItems) {
                $built = $this->buildTicketDataForItems($storeItems, $request->tanggal_kejadian);
                $store = $built['store'];

                // Generate safe sequential nomor_tiket (memperhitungkan soft-deleted)
                $nomorTiket = Ticket::generateNextNomorTiket($request->tanggal_kejadian, (int) $user->company_id);

                $ticket = Ticket::create([
                    'company_id'         => $user->company_id,
                    'user_id'            => $user->id,
                    'nomor_tiket'        => $nomorTiket,
                    'tanggal_kejadian'   => $request->tanggal_kejadian,
                    'nama_pelapor'       => $user->name,
                    'divisi_toko'        => $built['store_name'],
                    'kategori'           => $built['kategori'],
                    'prioritas'          => $built['prioritas'],
                    'status'             => 'Open',
                    'source'             => 'preventive',
                    'store_id'           => $store?->id,
                    'pic'                => $user->name,
                    'deskripsi_kendala'  => $built['deskripsi'],
                    'tindakan_dilakukan' => $built['tindakan'],
                    'dampak_operasional' => $built['dampak'],
                ]);

                // Audit history
                \App\Models\TicketHistory::create([
                    'company_id'  => $ticket->company_id,
                    'ticket_id'   => $ticket->id,
                    'user_id'     => $user->id,
                    'action'      => 'created',
                    'description' => 'Tiket dibuat otomatis dari Pengecekan Harian (Preventive)',
                ]);

                // Update status item daily check dan pivot ticket_devices
                $subItemIds = $storeItems->pluck('id')->all();
                self::markItemsAsTicketed($subItemIds, $ticket->id);

                $createdTickets[] = [
                    'id'          => $ticket->id,
                    'nomor_tiket' => $ticket->nomor_tiket,
                    'store_name'  => $built['store_name'],
                    'count'       => $storeItems->count(),
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil membuat ' . count($createdTickets) . ' tiket untuk perangkat offline.',
                'tickets' => $createdTickets,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat tiket otomatis: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Susun rincian narasi deskripsi, kategori, prioritas, tindakan dan dampak
     * dari koleksi DailyCheckItem pada 1 toko yang sama.
     */
    private function buildTicketDataForItems($items, string $tanggalKejadian): array
    {
        $store     = $items->first()->device->store;
        $storeName = $store ? ($store->name . ($store->branch ? ' — ' . $store->branch : '')) : 'Toko';

        $tanggalFormatted = Carbon::parse($tanggalKejadian)->translatedFormat('d F Y');
        $jumlahDevice     = $items->count();
        $perangkatLabel   = $jumlahDevice === 1 ? 'perangkat' : 'beberapa perangkat';

        // Paragraf pembuka — naratif
        $deskripsi  = "Saat pengecekan harian pada {$tanggalFormatted}, ditemukan {$perangkatLabel} dalam kondisi offline di {$storeName}.";
        $deskripsi .= "\n\nBerikut rincian perangkat yang terdeteksi bermasalah:\n";

        foreach ($items as $item) {
            $label = $item->device->alias ?: ($item->device->type . ($item->device->model ? ' ' . $item->device->model : ''));

            $deskripsi .= "\n• {$label}";

            if ($item->offline_since) {
                $deskripsi .= " — offline sejak " . Carbon::parse($item->offline_since)->translatedFormat('d F Y, H:i') . " WIB";
            }

            if ($item->affected_channels) {
                $deskripsi .= "\n  Kanal/channel yang terdampak: " . $item->affected_channels . ".";
            }

            if ($item->notes) {
                $deskripsi .= "\n  Catatan: " . $item->notes . ".";
            }
        }

        $deskripsi .= "\n\nMohon segera ditindaklanjuti. Terima kasih.";

        // Deteksi kategori, prioritas, dan tindakan berdasarkan device
        $hasCctv   = $items->contains(fn($i) => stripos($i->device->type ?? '', 'cctv') !== false || stripos($i->device->type ?? '', 'dvr') !== false || stripos($i->device->type ?? '', 'nvr') !== false || stripos($i->device->type ?? '', 'kamera') !== false);
        $hasLaptop = $items->contains(fn($i) => stripos($i->device->type ?? '', 'laptop') !== false);
        $kategori  = $hasCctv ? 'CCTV' : ($hasLaptop ? 'Laptop' : 'Device');

        // Kumpulkan saran prioritas, tindakan & dampak dari setiap item, ambil prioritas tertinggi
        $priorityOrder = ['Low' => 1, 'Medium' => 2, 'High' => 3];
        $bestPrioritas = 'Low';
        $tindakanParts = [];
        $dampakParts   = [];

        foreach ($items as $item) {
            $suggestion = $this->detectDeviceSuggestion($item);
            $label      = $item->device->alias ?: ($item->device->type . ($item->device->model ? ' ' . $item->device->model : ''));

            if (($priorityOrder[$suggestion['prioritas']] ?? 1) > ($priorityOrder[$bestPrioritas] ?? 1)) {
                $bestPrioritas = $suggestion['prioritas'];
            }

            $tindakanParts[] = "• {$label}: " . $suggestion['tindakan'];
            $dampakParts[]   = "• {$label}: " . $suggestion['dampak'];
        }

        $tindakanDefault = implode("\n", $tindakanParts);
        $dampakDefault   = implode("\n", $dampakParts);

        return [
            'store'      => $store,
            'store_name' => $storeName,
            'deskripsi'  => $deskripsi,
            'kategori'   => $kategori,
            'prioritas'  => $bestPrioritas,
            'tindakan'   => $tindakanDefault,
            'dampak'     => $dampakDefault,
        ];
    }

    /**
     * Deteksi prioritas, tindakan default, dan dampak operasional
     * berdasarkan tipe & alias device serta data offline dari daily check item.
     *
     * Aturan:
     *  [High]   NVR / DVR              → seluruh channel mati → reset modem / cabut-pasang kabel
     *  [High]   CCTV alias "moka"      → kamera kasir mati → cabut-pasang kabel power
     *  [Low]    CCTV alias "games"     → kamera games mati → cabut-pasang / cek kabel
     *  [Medium] CCTV / Camera lainnya  → salah satu kamera mati → reset modem atau cek kabel
     *  [Medium] Laptop / Device lain   → periksa koneksi & fisik perangkat
     */
    private function detectDeviceSuggestion(DailyCheckItem $item): array
    {
        $type     = strtolower(trim($item->device->type  ?? ''));
        $alias    = strtolower(trim($item->device->alias ?? ''));
        $channels = $item->affected_channels ? "kanal {$item->affected_channels}" : null;
        $chCount  = $item->device->channel_count ?? null;
        $camCount = $item->device->camera_count  ?? null;

        $isNvr   = in_array($type, ['nvr', 'dvr']);
        $isCctv  = in_array($type, ['cctv', 'camera', 'kamera']);
        $isMoka  = str_contains($alias, 'moka');
        $isGames = str_contains($alias, 'games') || str_contains($alias, 'game');

        // ── NVR / DVR ──────────────────────────────────────────────────────────
        if ($isNvr) {
            $totalInfo = $chCount ? " ({$chCount} kanal)" : '';
            $channelInfo = $channels ? ", terdampak pada {$channels}" : ' — seluruh channel kemungkinan mati';
            return [
                'prioritas' => 'High',
                'tindakan'  => 'Reset modem / cabut pasang kabel NVR (indikasi seluruh channel kamera mati serentak).',
                'dampak'    => "Seluruh monitoring CCTV{$totalInfo} pada toko tidak dapat merekam{$channelInfo}. Keamanan toko terganggu.",
            ];
        }

        // ── CCTV Moka (kasir) ──────────────────────────────────────────────────
        if ($isCctv && $isMoka) {
            return [
                'prioritas' => 'High',
                'tindakan'  => 'Cabut pasang kabel power CCTV / cek apakah sudah terpasang / kemungkinan unit rusak.',
                'dampak'    => 'Kamera kasir (Moka) tidak aktif. Monitoring transaksi dan area kasir tidak dapat dipantau.',
            ];
        }

        // ── CCTV Games ─────────────────────────────────────────────────────────
        if ($isCctv && $isGames) {
            return [
                'prioritas' => 'Low',
                'tindakan'  => 'Cabut pasang kabel / cek apakah sudah terpasang / kemungkinan unit rusak.',
                'dampak'    => 'Kamera area games tidak aktif. Monitoring area hiburan terganggu namun tidak berdampak langsung ke operasional utama.',
            ];
        }

        // ── CCTV / Kamera lainnya ──────────────────────────────────────────────
        if ($isCctv) {
            $channelInfo = $channels ? " pada {$channels}" : '';
            return [
                'prioritas' => 'Medium',
                'tindakan'  => 'Jika kamera standalone: coba reset modem. Jika terhubung ke NVR/DVR: cabut pasang kabel kamera / kemungkinan kamera rusak atau belum terpasang.',
                'dampak'    => "Salah satu titik kamera CCTV{$channelInfo} tidak aktif. Terdapat blind spot pada area yang tidak terpantau.",
            ];
        }

        // ── Laptop ─────────────────────────────────────────────────────────────
        if (str_contains($type, 'laptop') || str_contains($type, 'pc') || str_contains($type, 'komputer')) {
            return [
                'prioritas' => 'Medium',
                'tindakan'  => 'Periksa kondisi fisik, koneksi daya, dan coba restart perangkat.',
                'dampak'    => 'Perangkat komputer/laptop tidak dapat digunakan, aktivitas operasional yang bergantung pada perangkat ini terhenti sementara.',
            ];
        }

        // ── Modem / Router ─────────────────────────────────────────────────────
        if (str_contains($type, 'modem') || str_contains($type, 'router')) {
            return [
                'prioritas' => 'High',
                'tindakan'  => 'Restart modem/router. Jika tidak berhasil, cek kabel WAN dan hubungi ISP.',
                'dampak'    => 'Koneksi internet toko mati. Seluruh sistem yang bergantung pada jaringan (CCTV cloud, POS online, dll) tidak dapat beroperasi.',
            ];
        }

        // ── Fallback ───────────────────────────────────────────────────────────
        return [
            'prioritas' => 'Medium',
            'tindakan'  => 'Periksa koneksi dan kondisi fisik perangkat.',
            'dampak'    => 'Perangkat tidak dapat beroperasi, memerlukan pengecekan lebih lanjut.',
        ];
    }

    /**
     * Tandai item sebagai ticket_created setelah tiket berhasil dibuat.
     * Dipanggil oleh TicketController::store jika ada item_ids.
     */
    public static function markItemsAsTicketed(array $itemIds, int $ticketId): void
    {
        DailyCheckItem::whereIn('id', $itemIds)->update([
            'result' => DailyCheckItem::RESULT_TICKET_CREATED,
        ]);

        // Simpan relasi tiket ↔ device ke pivot
        $items = DailyCheckItem::whereIn('id', $itemIds)->get();
        foreach ($items as $item) {
            DB::table('ticket_devices')->insertOrIgnore([
                'ticket_id' => $ticketId,
                'device_id' => $item->device_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Ringkasan: device offline hari ini (untuk widget manager dashboard).
     */
    public static function todayOfflineCount(int $companyId): int
    {
        $today = now()->toDateString();
        $check = DailyCheck::where('company_id', $companyId)
                           ->where('check_date', $today)
                           ->first();

        if (!$check) return 0;

        return DailyCheckItem::where('daily_check_id', $check->id)
                             ->where('is_offline', true)
                             ->count();
    }
}
