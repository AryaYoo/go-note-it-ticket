<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterDataController extends Controller
{
    // ──────────────────────────── STORES ────────────────────────────

    public function storesIndex()
    {
        $stores = Store::withCount('devices')->orderBy('name')->get();
        return view('master.stores', compact('stores'));
    }

    public function storeStore(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:150',
            'branch'  => 'nullable|string|max:150',
            'contact' => 'nullable|string|max:150',
        ], [
            'name.required' => 'Nama toko wajib diisi.',
        ]);

        $data['company_id'] = Auth::user()->company_id;
        Store::create($data);

        return redirect()->route('master.stores')->with('success', 'Toko berhasil ditambahkan.');
    }

    public function storeUpdate(Request $request, Store $store)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:150',
            'branch'    => 'nullable|string|max:150',
            'contact'   => 'nullable|string|max:150',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $store->update($data);

        return redirect()->route('master.stores')->with('success', 'Data toko berhasil diperbarui.');
    }

    public function storeDestroy(Store $store)
    {
        if ($store->devices()->count() > 0) {
            return redirect()->route('master.stores')
                ->with('error', 'Toko tidak dapat dihapus karena masih memiliki device terdaftar.');
        }
        $store->delete();
        return redirect()->route('master.stores')->with('success', 'Toko berhasil dihapus.');
    }

    public function storeImportCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file      = $request->file('csv_file');
        $path      = $file->getRealPath();

        // Deteksi delimiter (koma atau titik-koma)
        $sampleLine = fgets(fopen($path, 'r'));
        $delimiter  = (substr_count($sampleLine, ';') > substr_count($sampleLine, ',')) ? ';' : ',';

        $handle    = fopen($path, 'r');
        $header    = fgetcsv($handle, 0, $delimiter); // skip header row
        $count     = 0;
        $companyId = Auth::user()->company_id;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty($row[0])) continue;

            $branch  = isset($row[1]) ? trim($row[1]) : '';
            $contact = isset($row[2]) ? trim($row[2]) : '';

            $store = Store::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'name'       => trim($row[0]),
                    'branch'     => $branch,
                ],
                [
                    'contact'    => $contact,
                ]
            );

            if (!$store->wasRecentlyCreated && empty($store->contact) && !empty($contact)) {
                $store->update(['contact' => $contact]);
            }

            $count++;
        }
        fclose($handle);

        return redirect()->route('master.stores')
            ->with('success', "{$count} toko berhasil diproses dari CSV.");
    }

    public function storesExportCsv(Request $request)
    {
        $stores = Store::withCount('devices')->orderBy('name')->get();
        $filename = 'Master_Toko_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($stores) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['name', 'branch', 'contact', 'devices_count', 'is_active']);

            foreach ($stores as $store) {
                fputcsv($handle, [
                    $store->name,
                    $store->branch ?? '',
                    $store->contact ?? '',
                    $store->devices_count ?? 0,
                    $store->is_active ? '1' : '0',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ──────────────────────────── DEVICES ────────────────────────────

    public function devicesIndex()
    {
        $devices = Device::with('store')->orderBy('store_id')->orderBy('alias')->get();
        $stores  = Store::where('is_active', true)->orderBy('name')->get();
        return view('master.devices', compact('devices', 'stores'));
    }

    public function deviceStore(Request $request)
    {
        $data = $request->validate([
            'store_id'      => 'required|exists:stores,id',
            'type'          => 'required|string|max:50',
            'alias'         => 'nullable|string|max:100',
            'model'         => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'channel_count' => 'nullable|integer|min:0|max:999',
            'camera_count'  => 'nullable|integer|min:0|max:999',
            'modem_notes'   => 'nullable|string',
            '_search'       => 'nullable|string',
        ]);

        $search = $request->input('_search', $request->input('search'));
        unset($data['_search']);

        $data['company_id'] = Auth::user()->company_id;
        Device::create($data);

        $redirect = redirect()->route('master.devices');
        if (!empty($search)) {
            $redirect = redirect()->route('master.devices', ['search' => $search]);
        }
        return $redirect->with('success', 'Device berhasil ditambahkan.');
    }

    public function deviceUpdate(Request $request, Device $device)
    {
        $data = $request->validate([
            'store_id'      => 'required|exists:stores,id',
            'type'          => 'required|string|max:50',
            'alias'         => 'nullable|string|max:100',
            'model'         => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'channel_count' => 'nullable|integer|min:0|max:999',
            'camera_count'  => 'nullable|integer|min:0|max:999',
            'modem_notes'   => 'nullable|string',
            'is_active'     => 'nullable|boolean',
            '_search'       => 'nullable|string',
        ]);

        $search = $request->input('_search', $request->input('search'));
        unset($data['_search']);

        $data['is_active'] = $request->boolean('is_active');
        $device->update($data);

        $redirect = redirect()->route('master.devices');
        if (!empty($search)) {
            $redirect = redirect()->route('master.devices', ['search' => $search]);
        }
        return $redirect->with('success', 'Device berhasil diperbarui.');
    }

    public function deviceDestroy(Request $request, Device $device)
    {
        $search = $request->input('_search', $request->input('search'));
        $device->delete();

        $redirect = redirect()->route('master.devices');
        if (!empty($search)) {
            $redirect = redirect()->route('master.devices', ['search' => $search]);
        }
        return $redirect->with('success', 'Device berhasil dihapus.');
    }

    public function deviceImportCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file      = $request->file('csv_file');
        $path      = $file->getRealPath();

        // Deteksi delimiter (koma atau titik-koma)
        $sampleLine = fgets(fopen($path, 'r'));
        $delimiter  = (substr_count($sampleLine, ';') > substr_count($sampleLine, ',')) ? ';' : ',';

        $handle    = fopen($path, 'r');
        $header    = fgetcsv($handle, 0, $delimiter); // skip header
        $count     = 0;
        $companyId = Auth::user()->company_id;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty($row[1])) continue; // kolom type wajib ada

            $storeQuery = trim($row[0] ?? '');
            // Cocokkan toko berdasarkan nama cabang, nama toko, atau format "Nama (Cabang)"
            $store = Store::where('company_id', $companyId)
                          ->where(function ($q) use ($storeQuery) {
                              $q->where('branch', $storeQuery)
                                ->orWhere('name', $storeQuery)
                                ->orWhereRaw("CONCAT(name, ' (', branch, ')') = ?", [$storeQuery])
                                ->orWhereRaw("CONCAT(name, ' ', branch) = ?", [$storeQuery]);
                          })
                          ->first();
            if (!$store) continue;

            Device::create([
                'company_id'    => $companyId,
                'store_id'      => $store->id,
                'type'          => trim($row[1]),
                'alias'         => trim($row[2] ?? ''),
                'model'         => trim($row[3] ?? ''),
                'serial_number' => trim($row[4] ?? ''),
                'channel_count' => is_numeric($row[5] ?? '') ? (int)$row[5] : null,
                'camera_count'  => is_numeric($row[6] ?? '') ? (int)$row[6] : null,
                'modem_notes'   => trim($row[7] ?? ''),
            ]);
            $count++;
        }
        fclose($handle);

        return redirect()->route('master.devices')
            ->with('success', "{$count} device berhasil diimpor dari CSV.");
    }

    public function devicesExportCsv(Request $request)
    {
        $devices = Device::with('store')->orderBy('store_id')->orderBy('alias')->get();
        $filename = 'Master_Perangkat_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($devices) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'store_name',
                'type',
                'alias',
                'model',
                'serial_number',
                'channel_count',
                'camera_count',
                'modem_notes',
                'is_active',
            ]);

            foreach ($devices as $dev) {
                $storeName = $dev->store ? ($dev->store->branch ?: $dev->store->name) : '';
                fputcsv($handle, [
                    $storeName,
                    $dev->type ?? '',
                    $dev->alias ?? '',
                    $dev->model ?? '',
                    $dev->serial_number ?? '',
                    $dev->channel_count ?? '',
                    $dev->camera_count ?? '',
                    $dev->modem_notes ?? '',
                    $dev->is_active ? '1' : '0',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
