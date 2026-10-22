@extends('layouts.app')
@section('title', 'Master Data — Perangkat')

@push('head')
<style>
    .master-table-card {
        background: #fff; border: 1px solid var(--border);
        border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow: hidden;
    }
    .master-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .master-table th {
        background: #F9FAFB; padding: 11px 16px; font-weight: 600; font-size: 11.5px;
        text-transform: uppercase; letter-spacing: 0.4px; color: var(--muted);
        border-bottom: 1px solid var(--border); text-align: left;
    }
    .master-table td { padding: 12px 16px; border-bottom: 1px solid #F3F4F6; color: var(--text); vertical-align: middle; }
    .master-table tr:last-child td { border-bottom: none; }
    .master-table tr:hover td { background: #FDFDFD; }

    .modal-backdrop { position: fixed; inset: 0; z-index: 1000; background: rgba(17,24,39,0.4); display: flex; align-items: center; justify-content: center; padding: 16px; }
    .modal-backdrop.hidden { display: none !important; }
    .modal-box { background: #fff; border-radius: 8px; width: 100%; max-width: 540px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden; max-height: 90vh; overflow-y: auto; }
    .modal-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; background: #fff; z-index: 10; }
    .modal-title { font-size: 14px; font-weight: 600; color: var(--text); }
    .modal-body  { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
    .modal-footer { padding: 14px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 8px; position: sticky; bottom: 0; background: #fff; }

    .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .badge-active   { background: #F0FDF4; color: var(--success); padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; }
    .badge-inactive { background: #F9FAFB; color: var(--muted);   padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; }

    /* Icon-only action buttons */
    .icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--muted);
        cursor: pointer;
        transition: all 0.15s ease;
        position: relative;
    }
    .icon-btn:hover { background: #F3F4F6; color: var(--text); border-color: #D1D5DB; }
    .icon-btn.icon-btn-danger { color: #EF4444; border-color: #FECACA; background: #FFF5F5; }
    .icon-btn.icon-btn-danger:hover { background: #FEE2E2; border-color: #EF4444; }
    .icon-btn[title]:hover::after {
        content: attr(title);
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%);
        background: #111827;
        color: #fff;
        font-size: 11px;
        padding: 3px 7px;
        border-radius: 4px;
        white-space: nowrap;
        pointer-events: none;
        z-index: 100;
    }

    /* Status dot indicators (bulat hijau/merah simpel & jelas) */
    .status-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        position: relative;
        vertical-align: middle;
        transition: all 0.15s ease;
    }
    .status-dot.status-ok {
        background: #16A34A;
        box-shadow: 0 0 0 3.5px rgba(22, 163, 74, 0.22);
    }
    .status-dot.status-offline {
        background: #DC2626;
        box-shadow: 0 0 0 3.5px rgba(220, 38, 38, 0.22);
    }
    .status-dot[title]:hover::after {
        content: attr(title);
        position: absolute;
        bottom: calc(100% + 7px);
        left: 50%;
        transform: translateX(-50%);
        background: #111827;
        color: #fff;
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 4px;
        white-space: nowrap;
        pointer-events: none;
        z-index: 100;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    /* Clickable Row & Detail Modal */
    .clickable-row {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .clickable-row:hover td {
        background: #F8FAFC !important;
    }
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    @media (max-width: 600px) {
        .detail-grid { grid-template-columns: 1fr; }
    }
    .detail-item {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        padding: 10px 14px;
    }
    .detail-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--muted);
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }
    .detail-value {
        font-size: 13.5px;
        font-weight: 500;
        color: var(--text);
        word-break: break-word;
    }
    .detail-full {
        grid-column: 1 / -1;
    }
</style>
@endpush

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
    <div>
        <h1 style="margin-bottom:4px;">Master Data — Perangkat</h1>
        <div class="page-header-sub" id="device-count-sub">{{ $devices->count() }} perangkat terdaftar · Klik baris untuk detail lengkap</div>
    </div>
    <div class="flex gap-8" style="align-items:center; flex-wrap:wrap;">
        {{-- Search Input --}}
        <div class="search-input-wrap" style="min-width:240px; max-width:320px;">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="device-search-input" class="search-input" placeholder="Cari toko, tipe, nama, SN..." value="{{ request('search') }}" autocomplete="off" oninput="filterDevices(this.value)">
            <button type="button" id="device-search-clear" class="search-clear-btn" style="{{ request('search') ? '' : 'display:none;' }} background:none; border:none; cursor:pointer;" onclick="clearDeviceSearch()" title="Hapus pencarian">✕</button>
        </div>

        {{-- Tombol Reset Search --}}
        <button type="button" id="btn-reset-search" class="btn btn-ghost btn-sm" style="{{ request('search') ? 'display:inline-flex;' : 'display:none;' }} align-items:center; gap:5px; border:1px solid var(--border); color:var(--muted);" onclick="clearDeviceSearch()" title="Reset pencarian ke semua perangkat">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.73"/></svg>
            Reset
        </button>

        <a href="{{ route('master.devices.export') }}" class="btn btn-secondary btn-sm" title="Ekspor data perangkat ke file CSV">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Ekspor CSV
        </a>
        <button class="btn btn-secondary btn-sm" onclick="document.getElementById('modal-import').classList.remove('hidden')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
            Impor CSV
        </button>
        @if($stores->isEmpty())
            <button class="btn btn-primary btn-sm" disabled title="Tambahkan toko terlebih dahulu">Tambah Perangkat</button>
        @else
            <button class="btn btn-primary btn-sm" onclick="openAddModal()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Perangkat
            </button>
        @endif
    </div>
</div>

@if($stores->isEmpty())
<div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#92400E;">
    ⚠️ Belum ada toko terdaftar. <a href="{{ route('master.stores') }}" style="color:#D97706; font-weight:600;">Daftarkan toko terlebih dahulu</a> sebelum menambah perangkat.
</div>
@endif

<div style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:6px; padding:10px 14px; margin-bottom:16px; font-size:12px; color:#1D4ED8; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
    <div><strong>Format CSV Perangkat:</strong> kolom urutan — <code>store_name, type, alias, model, serial_number, channel_count, camera_count, modem_notes</code></div>
    <div style="font-size:11.5px; color:#2563EB; display:flex; align-items:center; gap:4px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        Klik pada baris mana saja untuk melihat detail perangkat
    </div>
</div>

<div class="master-table-card">
    <div style="overflow-x:auto;">
        <table class="master-table">
            <thead>
                <tr>
                    <th style="width:48px;">#</th>
                    <th>Toko</th>
                    <th>Tipe</th>
                    <th>Nama</th>
                    <th>Status</th>
                    <th style="text-align:right; width:130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                <tr class="clickable-row device-row-item" 
                    data-search="{{ strtolower(($device->store->branch ?? '') . ' ' . ($device->store->name ?? '') . ' ' . $device->type . ' ' . ($device->alias ?? '') . ' ' . ($device->model ?? '') . ' ' . ($device->serial_number ?? '')) }}"
                    onclick="openDetailModal({{ json_encode($device) }}, {{ json_encode($device->store) }})" 
                    title="Klik untuk melihat detail lengkap perangkat">
                    <td class="row-number" style="color:var(--muted);">{{ $loop->iteration }}</td>
                    <td>
                        @if($device->store?->branch)
                            <div style="font-weight:600; font-size:13px; color:var(--text);">{{ $device->store->branch }}</div>
                            <div style="font-size:11px; color:var(--muted);">{{ $device->store->name }}</div>
                        @else
                            <div style="font-weight:600; font-size:13px; color:var(--text);">{{ $device->store->name ?? '—' }}</div>
                        @endif
                    </td>
                    <td><span class="badge {{ $device->type_badge_class }}" style="font-size:11.5px;">{{ $device->type }}</span></td>
                    <td style="font-weight:500;">{{ $device->alias ?: '—' }}</td>
                    <td>
                        @if($device->is_active)
                            <span class="status-dot status-ok" title="Aktif"></span>
                        @else
                            <span class="status-dot status-offline" title="Non-aktif"></span>
                        @endif
                    </td>
                    <td style="text-align:right;" onclick="event.stopPropagation()">
                        <div class="flex gap-8" style="justify-content:flex-end;">
                            <button type="button" class="icon-btn" title="Duplikat Perangkat" onclick="event.stopPropagation(); openDuplicateModal({{ json_encode($device) }}, {{ json_encode($device->store) }})">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                            <button type="button" class="icon-btn" title="Edit Perangkat" onclick="event.stopPropagation(); openEditModal({{ json_encode($device) }}, {{ json_encode($device->store) }})">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('master.devices.destroy', $device) }}" onsubmit="return confirmDelete(event, '{{ $device->alias ?: $device->type }}')" style="display:inline;" onclick="event.stopPropagation()">
                                @csrf @method('DELETE')
                                <input type="hidden" name="_search" class="current-search-param" value="{{ request('search') }}">
                                <button type="submit" class="icon-btn icon-btn-danger" title="Hapus Perangkat">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:var(--muted); padding:32px;">
                        Belum ada perangkat. Klik <strong>Tambah Perangkat</strong> atau <strong>Impor CSV</strong>.
                    </td>
                </tr>
                @endforelse
                <tr id="no-search-results" style="display:none;">
                    <td colspan="6" style="text-align:center; color:var(--muted); padding:36px;">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 8px; display:block; opacity:0.4;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Tidak ada perangkat yang cocok dengan pencarian "<strong id="searched-term"></strong>".
                        <div style="margin-top:10px;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="clearDeviceSearch()">Reset Pencarian</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Tambah --}}
<div class="modal-backdrop hidden" id="modal-add">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title" id="modal-add-title">Tambah Perangkat</div>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-add').classList.add('hidden')">✕</button>
        </div>
        <form method="POST" id="form-add" action="{{ route('master.devices.store') }}">
            @csrf
            <input type="hidden" name="_search" class="current-search-param" value="{{ request('search') }}">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Toko <span class="required">*</span></label>
                    <select name="store_id" id="add-store_id" class="form-select" required>
                        <option value="">— Pilih Toko —</option>
                        @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Tipe <span class="required">*</span></label>
                        <select name="type" id="add-type" class="form-select" required>
                            <option value="">— Pilih Tipe —</option>
                            @foreach(['CCTV','NVR','DVR','CAMERA','POS','Laptop','Printer','Scanner','Router','Modem','Lainnya'] as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alias / Nama</label>
                        <input type="text" name="alias" id="add-alias" class="form-input" placeholder="Contoh: DVR Utama">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" id="add-model" class="form-input" placeholder="Contoh: Hikvision DS-7208">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" id="add-serial_number" class="form-input">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Jumlah Kanal</label>
                        <input type="number" name="channel_count" id="add-channel_count" class="form-input" min="0" max="999" placeholder="8">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kamera Terpasang</label>
                        <input type="number" name="camera_count" id="add-camera_count" class="form-input" min="0" max="999" placeholder="6">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan Modem / Kuota</label>
                    <textarea name="modem_notes" id="add-modem_notes" class="form-textarea" rows="2" placeholder="Catatan provider, kuota, nomor SIM, dll."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-add').classList.add('hidden')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-save-add">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit --}}
<div class="modal-backdrop hidden" id="modal-edit">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Edit Perangkat</div>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-edit').classList.add('hidden')">✕</button>
        </div>
        <form method="POST" id="form-edit" action="">
            @csrf @method('PUT')
            <input type="hidden" name="_search" class="current-search-param" value="{{ request('search') }}">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Toko <span class="required">*</span></label>
                    <select name="store_id" id="edit-store_id" class="form-select" required>
                        @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Tipe <span class="required">*</span></label>
                        <select name="type" id="edit-type" class="form-select" required>
                            @foreach(['CCTV','NVR','DVR','CAMERA','POS','Laptop','Printer','Scanner','Router','Modem','Lainnya'] as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alias / Nama</label>
                        <input type="text" name="alias" id="edit-alias" class="form-input">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" id="edit-model" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" id="edit-serial" class="form-input">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Jumlah Kanal</label>
                        <input type="number" name="channel_count" id="edit-channel" class="form-input" min="0" max="999">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kamera Terpasang</label>
                        <input type="number" name="camera_count" id="edit-camera" class="form-input" min="0" max="999">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan Modem / Kuota</label>
                    <textarea name="modem_notes" id="edit-modem" class="form-textarea" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="is_active" id="edit-active" class="form-select">
                        <option value="1">Aktif</option>
                        <option value="0">Non-aktif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-edit').classList.add('hidden')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Import CSV --}}
<div class="modal-backdrop hidden" id="modal-import">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Impor Perangkat dari CSV</div>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-import').classList.add('hidden')">✕</button>
        </div>
        <form method="POST" action="{{ route('master.devices.import-csv') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:6px; padding:10px 14px; font-size:12px; color:#92400E;">
                    Format: <strong>store_name, type, alias, model, serial_number, channel_count, camera_count, modem_notes</strong><br>
                    Nama toko di CSV harus sama persis dengan data master toko.
                </div>
                <div class="form-group">
                    <label class="form-label">File CSV <span class="required">*</span></label>
                    <input type="file" name="csv_file" class="form-input" accept=".csv,.txt" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-import').classList.add('hidden')">Batal</button>
                <button type="submit" class="btn btn-primary">Impor</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Detail Perangkat --}}
<div class="modal-backdrop hidden" id="modal-detail">
    <div class="modal-box" style="max-width: 540px;">
        <div class="modal-header">
            <div style="display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/>
                    <line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
                <div class="modal-title">Detail Perangkat</div>
            </div>
            <button class="btn btn-ghost btn-sm" onclick="closeDetailModal()">✕</button>
        </div>
        <div class="modal-body" style="gap: 16px;">
            {{-- Top summary card --}}
            <div style="display: flex; align-items: center; justify-content: space-between; background: #F8FAFC; border: 1px solid #E2E8F0; padding: 12px 16px; border-radius: 8px;">
                <div>
                    <span id="detail-badge-type" class="badge badge-device" style="font-size: 11px; margin-bottom: 4px; display: inline-block;">-</span>
                    <h3 id="detail-alias" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text);">-</h3>
                    <div id="detail-store-name" style="font-size: 12px; color: var(--muted); margin-top: 2px;">-</div>
                </div>
                <div id="detail-badge-status">
                    <!-- status badge -->
                </div>
            </div>

            {{-- Grid details --}}
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Toko / Lokasi</div>
                    <div class="detail-value" id="detail-store">-</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Tipe Perangkat</div>
                    <div class="detail-value" id="detail-type">-</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Model Perangkat</div>
                    <div class="detail-value" id="detail-model">-</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Nomor Serial (SN)</div>
                    <div class="detail-value" id="detail-serial" style="font-family: monospace; font-size: 13px;">-</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Jumlah Kanal</div>
                    <div class="detail-value" id="detail-channel">-</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Jumlah Kamera</div>
                    <div class="detail-value" id="detail-camera">-</div>
                </div>
                <div class="detail-item detail-full">
                    <div class="detail-label">Catatan Modem / Kuota</div>
                    <div class="detail-value" id="detail-modem" style="white-space: pre-wrap; font-size: 12.5px; line-height: 1.5;">-</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
            <button type="button" class="btn btn-secondary" id="btn-duplicate-from-detail">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Duplikat
            </button>
            <button type="button" class="btn btn-primary" id="btn-edit-from-detail">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit Perangkat
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Real-time Search ──────────────────────────────
const totalDeviceCount = {{ $devices->count() }};

function filterDevices(term) {
    const raw = term;
    const q = raw.trim().toLowerCase();
    const clearBtn = document.getElementById('device-search-clear');
    const resetBtn = document.getElementById('btn-reset-search');
    const noResultsRow = document.getElementById('no-search-results');
    const countSub = document.getElementById('device-count-sub');
    const rows = document.querySelectorAll('.device-row-item');

    if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';
    if (resetBtn) resetBtn.style.display = q ? 'inline-flex' : 'none';

    // Sinkronkan nilai pencarian ke input hidden di form tambah, edit, & hapus
    document.querySelectorAll('.current-search-param').forEach(input => {
        input.value = raw.trim();
    });

    // Simpan ke sessionStorage & update URL tanpa reload
    if (q) {
        sessionStorage.setItem('master_device_search', raw.trim());
        const newUrl = window.location.pathname + '?search=' + encodeURIComponent(raw.trim());
        history.replaceState({ search: raw.trim() }, '', newUrl);
    } else {
        sessionStorage.removeItem('master_device_search');
        history.replaceState({}, '', window.location.pathname);
    }

    let visibleCount = 0;
    rows.forEach(row => {
        const data = row.getAttribute('data-search') || '';
        if (!q || data.includes(q)) {
            row.style.display = '';
            visibleCount++;
            const numCell = row.querySelector('.row-number');
            if (numCell) numCell.textContent = visibleCount;
        } else {
            row.style.display = 'none';
        }
    });

    if (visibleCount === 0 && rows.length > 0) {
        if (noResultsRow) {
            noResultsRow.style.display = '';
            const termEl = document.getElementById('searched-term');
            if (termEl) termEl.textContent = raw.trim();
        }
    } else {
        if (noResultsRow) noResultsRow.style.display = 'none';
    }

    if (countSub) {
        if (q) {
            countSub.textContent = `${visibleCount} dari ${totalDeviceCount} perangkat ditemukan · Klik baris untuk detail lengkap`;
        } else {
            countSub.textContent = `${totalDeviceCount} perangkat terdaftar · Klik baris untuk detail lengkap`;
        }
    }
}

function clearDeviceSearch() {
    sessionStorage.removeItem('master_device_search');
    history.replaceState({}, '', window.location.pathname);
    const input = document.getElementById('device-search-input');
    if (input) {
        input.value = '';
    }
    const rows = document.querySelectorAll('.device-row-item');
    if (rows.length < totalDeviceCount) {
        window.location.href = "{{ route('master.devices') }}";
        return;
    }
    filterDevices('');
    if (input) input.focus();
}

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('device-search-input');
    // Prioritaskan parameter URL, fallback ke sessionStorage
    const urlParams = new URLSearchParams(window.location.search);
    const urlSearch = urlParams.get('search');
    const storedSearch = sessionStorage.getItem('master_device_search');

    const activeSearch = urlSearch !== null ? urlSearch : (storedSearch || '');
    if (input && activeSearch) {
        input.value = activeSearch;
        filterDevices(activeSearch);
    }
});

function getTypeBadgeClass(type) {
    const t = (type || '').toLowerCase().trim();
    const map = {
        nvr: 'badge-type-nvr',
        cctv: 'badge-type-cctv',
        dvr: 'badge-type-dvr',
        camera: 'badge-type-camera',
        router: 'badge-type-router',
        modem: 'badge-type-modem',
        pos: 'badge-type-pos',
        laptop: 'badge-type-laptop',
        printer: 'badge-type-printer',
        scanner: 'badge-type-scanner',
    };
    return map[t] || 'badge-type-default';
}

let currentDetailDev = null;
let currentDetailStore = null;

function openDetailModal(dev, store) {
    currentDetailDev = dev;
    currentDetailStore = store;

    const storeDisplay = store ? (store.branch ? `${store.branch} (${store.name})` : store.name) : '—';
    
    document.getElementById('detail-alias').textContent = dev.alias || dev.type || '—';
    document.getElementById('detail-store-name').textContent = storeDisplay;
    
    const badgeTypeEl = document.getElementById('detail-badge-type');
    badgeTypeEl.textContent = dev.type || '—';
    badgeTypeEl.className = `badge ${getTypeBadgeClass(dev.type)}`;
    badgeTypeEl.style.fontSize = '11px';
    badgeTypeEl.style.marginBottom = '4px';
    badgeTypeEl.style.display = 'inline-block';
    
    const statusContainer = document.getElementById('detail-badge-status');
    if (dev.is_active) {
        statusContainer.innerHTML = '<span class="badge-active">Aktif</span>';
    } else {
        statusContainer.innerHTML = '<span class="badge-inactive">Non-aktif</span>';
    }

    document.getElementById('detail-store').textContent = storeDisplay;
    document.getElementById('detail-type').textContent = dev.type || '—';
    document.getElementById('detail-model').textContent = dev.model || '—';
    document.getElementById('detail-serial').textContent = dev.serial_number || '—';
    document.getElementById('detail-channel').textContent = (dev.channel_count !== null && dev.channel_count !== undefined && dev.channel_count !== '') ? `${dev.channel_count} Kanal` : '—';
    document.getElementById('detail-camera').textContent = (dev.camera_count !== null && dev.camera_count !== undefined && dev.camera_count !== '') ? `${dev.camera_count} Kamera` : '—';
    document.getElementById('detail-modem').textContent = dev.modem_notes || 'Tidak ada catatan khusus.';

    document.getElementById('btn-edit-from-detail').onclick = function() {
        closeDetailModal();
        openEditModal(currentDetailDev, currentDetailStore);
    };

    document.getElementById('btn-duplicate-from-detail').onclick = function() {
        closeDetailModal();
        openDuplicateModal(currentDetailDev, currentDetailStore);
    };

    document.getElementById('modal-detail').classList.remove('hidden');
}

function closeDetailModal() {
    document.getElementById('modal-detail').classList.add('hidden');
}

document.getElementById('modal-detail')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailModal();
    }
});

function openAddModal() {
    document.getElementById('modal-add-title').textContent = 'Tambah Perangkat';
    document.getElementById('btn-save-add').textContent = 'Simpan';
    document.getElementById('form-add').reset();
    document.getElementById('modal-add').classList.remove('hidden');
}

function openDuplicateModal(dev, store) {
    document.getElementById('modal-add-title').textContent = 'Duplikat Perangkat';
    document.getElementById('btn-save-add').textContent = 'Simpan Duplikat';

    document.getElementById('add-store_id').value = dev.store_id;

    // Pastikan type tersedia di select, jika tidak tambahkan secara dinamis
    const typeSelect = document.getElementById('add-type');
    let hasType = false;
    for (let i = 0; i < typeSelect.options.length; i++) {
        if (typeSelect.options[i].value.toLowerCase() === (dev.type || '').toLowerCase()) {
            typeSelect.selectedIndex = i;
            hasType = true;
            break;
        }
    }
    if (!hasType && dev.type) {
        const opt = document.createElement('option');
        opt.value = dev.type;
        opt.textContent = dev.type;
        opt.selected = true;
        typeSelect.appendChild(opt);
    }

    // Smart increment untuk nomor channel/kamera jika berakhiran angka
    let newAlias = dev.alias || '';
    if (newAlias) {
        const numMatch = newAlias.match(/^(.*?)(\d+)$/);
        if (numMatch) {
            const nextNum = parseInt(numMatch[2], 10) + 1;
            newAlias = numMatch[1] + nextNum;
        } else {
            newAlias = `${newAlias} (Salinan)`;
        }
    }

    document.getElementById('add-alias').value = newAlias;
    document.getElementById('add-model').value = dev.model || '';
    document.getElementById('add-serial_number').value = ''; // Kosongkan SN karena unik per perangkat
    document.getElementById('add-channel_count').value = (dev.channel_count !== null && dev.channel_count !== undefined) ? dev.channel_count : '';
    document.getElementById('add-camera_count').value = (dev.camera_count !== null && dev.camera_count !== undefined) ? dev.camera_count : '';
    document.getElementById('add-modem_notes').value = dev.modem_notes || '';

    document.getElementById('modal-add').classList.remove('hidden');

    setTimeout(() => {
        const aliasInput = document.getElementById('add-alias');
        if (aliasInput) {
            aliasInput.focus();
            aliasInput.select();
        }
    }, 100);
}

function openEditModal(dev, store) {
    document.getElementById('edit-store_id').value = dev.store_id;
    document.getElementById('edit-type').value     = dev.type;
    document.getElementById('edit-alias').value    = dev.alias || '';
    document.getElementById('edit-model').value    = dev.model || '';
    document.getElementById('edit-serial').value   = dev.serial_number || '';
    document.getElementById('edit-channel').value  = dev.channel_count || '';
    document.getElementById('edit-camera').value   = dev.camera_count || '';
    document.getElementById('edit-modem').value    = dev.modem_notes || '';
    document.getElementById('edit-active').value   = dev.is_active ? '1' : '0';
    document.getElementById('form-edit').action    = `{{ url('/master/devices') }}/${dev.id}`;
    document.getElementById('modal-edit').classList.remove('hidden');
}
async function confirmDelete(e, name) {
    e.preventDefault();
    const form = e.target;
    const result = await Swal.fire({
        title: 'Hapus Perangkat?', text: `Perangkat "${name}" akan dihapus.`,
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#DC2626',
    });
    if (result.isConfirmed) form.submit();
    return false;
}
</script>
@endpush
