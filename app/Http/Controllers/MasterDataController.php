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

        $file    = $request->file('csv_file');
        $handle  = fopen($file->getRealPath(), 'r');
        $header  = fgetcsv($handle); // skip header row
        $count   = 0;
        $companyId = Auth::user()->company_id;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue;
            Store::firstOrCreate(
                ['company_id' => $companyId, 'name' => trim($row[0])],
                [
                    'branch'  => trim($row[1] ?? ''),
                    'contact' => trim($row[2] ?? ''),
                ]
            );
            $count++;
        }
        fclose($handle);

        return redirect()->route('master.stores')
            ->with('success', "{$count} toko berhasil diimpor dari CSV.");
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
        ]);

        $data['company_id'] = Auth::user()->company_id;
        Device::create($data);

        return redirect()->route('master.devices')->with('success', 'Device berhasil ditambahkan.');
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
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $device->update($data);

        return redirect()->route('master.devices')->with('success', 'Device berhasil diperbarui.');
    }

    public function deviceDestroy(Device $device)
    {
        $device->delete();
        return redirect()->route('master.devices')->with('success', 'Device berhasil dihapus.');
    }

    public function deviceImportCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file      = $request->file('csv_file');
        $handle    = fopen($file->getRealPath(), 'r');
        $header    = fgetcsv($handle); // skip header
        $count     = 0;
        $companyId = Auth::user()->company_id;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[1])) continue; // kolom type wajib ada

            // Kolom CSV: store_name, type, alias, model, serial_number, channel_count, camera_count, modem_notes
            $store = Store::where('company_id', $companyId)
                          ->where('name', trim($row[0] ?? ''))
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
}
