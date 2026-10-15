<?php

namespace App\Http\Controllers;

use App\Models\DailyCheck;
use App\Models\DailyCheckItem;
use App\Models\Device;
use App\Models\Store;
use App\Models\Ticket;
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

        // Ambil semua device aktif, pastikan masing-masing punya item di sesi ini
        $devices = Device::with('store')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('store_id')
            ->orderBy('alias')
            ->get();

        // Sinkronkan: buat item yang belum ada
        foreach ($devices as $device) {
            DailyCheckItem::firstOrCreate([
                'daily_check_id' => $check->id,
                'device_id'      => $device->id,
            ]);
        }

        // Reload items dengan relasi
        $check->load(['items.device.store', 'items.tickets']);

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
            $item = DailyCheckItem::findOrFail($itemData['id']);
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
        $items   = DailyCheckItem::with(['device.store'])->findMany($itemIds);

        // Validasi: semua item harus dari toko yang sama
        $storeIds = $items->map(fn($i) => $i->device->store_id)->unique();
        if ($storeIds->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Semua device harus berasal dari toko yang sama untuk digabung dalam satu tiket.',
            ], 422);
        }

        $store   = $items->first()->device->store;
        $storeName = $store ? ($store->name ?? $store->display_name) : 'Toko';
        $devicesLabel = $items->map(fn($i) => ($i->device->alias ?: ($i->device->type . ($i->device->model ? ' ' . $i->device->model : ''))))->implode(', ');

        $offlineParts = [];
        foreach ($items as $item) {
            $label = $item->device->alias ?: $item->device->type;
            $line = "- Perangkat: {$label}";
            if ($item->offline_since) {
                $line .= " (Offline sejak: " . date('d/m/Y H:i', strtotime($item->offline_since)) . ")";
            }
            if ($item->affected_channels) {
                $line .= "\n  Kanal terdampak: " . $item->affected_channels;
            }
            if ($item->notes) {
                $line .= "\n  Catatan: " . $item->notes;
            }
            $offlineParts[] = $line;
        }

        // Susun judul dan deskripsi otomatis: judul 'Perangkat offline – [nama toko]', deskripsi memuat detail offline dan kanal terdampak
        $deskripsi = "Perangkat offline – {$storeName}\n\n";
        $deskripsi .= "Detail Pengecekan:\n";
        $deskripsi .= implode("\n", $offlineParts);

        $hasCctv = $items->contains(fn($i) => stripos($i->device->type ?? '', 'cctv') !== false || stripos($i->device->type ?? '', 'dvr') !== false || stripos($i->device->type ?? '', 'nvr') !== false || stripos($i->device->type ?? '', 'kamera') !== false);
        $hasLaptop = $items->contains(fn($i) => stripos($i->device->type ?? '', 'laptop') !== false);
        $kategori = $hasCctv ? 'CCTV' : ($hasLaptop ? 'Laptop' : 'Device');

        return response()->json([
            'success'          => true,
            'redirect_url'     => route('tickets.create', [
                'from_check'          => 1,
                'item_ids'            => implode(',', $itemIds),
                'tanggal_kejadian'    => $request->tanggal_kejadian,
                'divisi_toko'         => $storeName,
                'store_id'            => $store?->id,
                'deskripsi_kendala'   => $deskripsi,
                'kategori'            => $kategori,
            ]),
        ]);
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
