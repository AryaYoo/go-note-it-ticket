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
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>Master Data — Perangkat</h1>
        <div class="page-header-sub">{{ $devices->count() }} perangkat terdaftar</div>
    </div>
    <div class="flex gap-8">
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

<div style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:6px; padding:10px 14px; margin-bottom:16px; font-size:12px; color:#1D4ED8;">
    <strong>Format CSV Perangkat:</strong> kolom urutan — <code>store_name, type, alias, model, serial_number, channel_count, camera_count, modem_notes</code>
</div>

<div class="master-table-card">
    <div style="overflow-x:auto;">
        <table class="master-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Toko</th>
                    <th>Tipe</th>
                    <th>Alias / Nama</th>
                    <th>Model</th>
                    <th>Serial</th>
                    <th>Kanal / Kamera</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                <tr>
                    <td style="color:var(--muted);">{{ $loop->iteration }}</td>
                    <td>
                        <div style="font-weight:600; font-size:12.5px;">{{ $device->store->name ?? '—' }}</div>
                        @if($device->store?->branch)
                        <div style="font-size:11px; color:var(--muted);">{{ $device->store->branch }}</div>
                        @endif
                    </td>
                    <td><span class="badge badge-device" style="font-size:11.5px;">{{ $device->type }}</span></td>
                    <td style="font-weight:500;">{{ $device->alias ?: '—' }}</td>
                    <td style="color:var(--muted);">{{ $device->model ?: '—' }}</td>
                    <td style="color:var(--muted); font-size:12px;">{{ $device->serial_number ?: '—' }}</td>
                    <td style="text-align:center;">
                        @if($device->channel_count || $device->camera_count)
                            <span style="font-size:12px;">{{ $device->channel_count ?? '—' }} / {{ $device->camera_count ?? '—' }}</span>
                        @else
                            <span style="color:var(--muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($device->is_active)
                            <span class="badge-active">Aktif</span>
                        @else
                            <span class="badge-inactive">Non-aktif</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <div class="flex gap-8" style="justify-content:flex-end;">
                            <button class="btn btn-secondary btn-sm" onclick="openEditModal({{ json_encode($device) }}, {{ json_encode($device->store) }})">Edit</button>
                            <form method="POST" action="{{ route('master.devices.destroy', $device) }}" onsubmit="return confirmDelete(event, '{{ $device->alias ?: $device->type }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; color:var(--muted); padding:32px;">
                        Belum ada perangkat. Klik <strong>Tambah Perangkat</strong> atau <strong>Impor CSV</strong>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Tambah --}}
<div class="modal-backdrop hidden" id="modal-add">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Tambah Perangkat</div>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-add').classList.add('hidden')">✕</button>
        </div>
        <form method="POST" action="{{ route('master.devices.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Toko <span class="required">*</span></label>
                    <select name="store_id" class="form-select" required>
                        <option value="">— Pilih Toko —</option>
                        @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Tipe <span class="required">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="">— Pilih Tipe —</option>
                            @foreach(['CCTV','POS','Laptop','Printer','Scanner','Router','Lainnya'] as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alias / Nama</label>
                        <input type="text" name="alias" class="form-input" placeholder="Contoh: DVR Utama">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-input" placeholder="Contoh: Hikvision DS-7208">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" class="form-input">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Jumlah Kanal</label>
                        <input type="number" name="channel_count" class="form-input" min="0" max="999" placeholder="8">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kamera Terpasang</label>
                        <input type="number" name="camera_count" class="form-input" min="0" max="999" placeholder="6">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan Modem / Kuota</label>
                    <textarea name="modem_notes" class="form-textarea" rows="2" placeholder="Catatan provider, kuota, nomor SIM, dll."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-add').classList.add('hidden')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
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
                            @foreach(['CCTV','POS','Laptop','Printer','Scanner','Router','Lainnya'] as $t)
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
@endsection

@push('scripts')
<script>
function openAddModal() { document.getElementById('modal-add').classList.remove('hidden'); }

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
    document.getElementById('form-edit').action    = `/master/devices/${dev.id}`;
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
