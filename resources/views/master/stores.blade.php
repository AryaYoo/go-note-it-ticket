@extends('layouts.app')
@section('title', 'Master Data — Toko')

@push('head')
<style>
    .master-table-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    .master-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .master-table th {
        background: #F9FAFB;
        padding: 11px 16px;
        font-weight: 600;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: var(--muted);
        border-bottom: 1px solid var(--border);
        text-align: left;
    }
    .master-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #F3F4F6;
        color: var(--text);
        vertical-align: middle;
    }
    .master-table tr:last-child td { border-bottom: none; }
    .master-table tr:hover td { background: #FDFDFD; }

    /* Modal */
    .modal-backdrop {
        position: fixed; inset: 0; z-index: 1000;
        background: rgba(17,24,39,0.4);
        display: flex; align-items: center; justify-content: center; padding: 16px;
    }
    .modal-backdrop.hidden { display: none !important; }
    .modal-box {
        background: #fff; border-radius: 8px; width: 100%; max-width: 460px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden;
    }
    .modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between;
    }
    .modal-title { font-size: 14px; font-weight: 600; color: var(--text); }
    .modal-body  { padding: 20px; }
    .modal-footer {
        padding: 14px 20px;
        border-top: 1px solid var(--border);
        display: flex; justify-content: flex-end; gap: 8px;
    }
    .badge-active   { background: #F0FDF4; color: var(--success); padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; }
    .badge-inactive { background: #F9FAFB; color: var(--muted);   padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>Master Data — Toko</h1>
        <div class="page-header-sub">{{ $stores->count() }} toko terdaftar</div>
    </div>
    <div class="flex gap-8">
        <button class="btn btn-secondary btn-sm" onclick="document.getElementById('modal-import').classList.remove('hidden')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
            Impor CSV
        </button>
        <button class="btn btn-primary btn-sm" onclick="openAddModal()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Toko
        </button>
    </div>
</div>

{{-- CSV Format Info --}}
<div style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:6px; padding:10px 14px; margin-bottom:16px; font-size:12px; color:#1D4ED8;">
    <strong>Format CSV Toko:</strong> kolom urutan — <code>name, branch, contact</code> (baris pertama adalah header, dilewati otomatis)
</div>

<div class="master-table-card">
    <div style="overflow-x:auto;">
        <table class="master-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Toko</th>
                    <th>Cabang / Divisi</th>
                    <th>Kontak</th>
                    <th>Jumlah Device</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stores as $store)
                <tr>
                    <td style="color:var(--muted);">{{ $loop->iteration }}</td>
                    <td style="font-weight:600;">{{ $store->name }}</td>
                    <td>{{ $store->branch ?: '—' }}</td>
                    <td>{{ $store->contact ?: '—' }}</td>
                    <td>
                        <span class="badge" style="background:#EFF6FF; color:var(--primary);">{{ $store->devices_count }}</span>
                    </td>
                    <td>
                        @if($store->is_active)
                            <span class="badge-active">Aktif</span>
                        @else
                            <span class="badge-inactive">Non-aktif</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <div class="flex gap-8" style="justify-content:flex-end;">
                            <button class="btn btn-secondary btn-sm" onclick="openEditModal({{ json_encode($store) }})">Edit</button>
                            <form method="POST" action="{{ route('master.stores.destroy', $store) }}" onsubmit="return confirmDelete(event, '{{ $store->name }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:var(--muted); padding:32px;">
                        Belum ada toko terdaftar. Klik <strong>Tambah Toko</strong> atau <strong>Impor CSV</strong> untuk memulai.
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
            <div class="modal-title">Tambah Toko</div>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-add').classList.add('hidden')">✕</button>
        </div>
        <form method="POST" action="{{ route('master.stores.store') }}">
            @csrf
            <div class="modal-body" style="display:flex; flex-direction:column; gap:14px;">
                <div class="form-group">
                    <label class="form-label">Nama Toko <span class="required">*</span></label>
                    <input type="text" name="name" class="form-input" placeholder="Contoh: Toko Sudirman" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Cabang / Divisi</label>
                    <input type="text" name="branch" class="form-input" placeholder="Contoh: Cabang Jakarta Pusat">
                </div>
                <div class="form-group">
                    <label class="form-label">Kontak PIC Toko</label>
                    <input type="text" name="contact" class="form-input" placeholder="Contoh: Budi – 0812-xxxx">
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
            <div class="modal-title">Edit Toko</div>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-edit').classList.add('hidden')">✕</button>
        </div>
        <form method="POST" id="form-edit" action="">
            @csrf @method('PUT')
            <div class="modal-body" style="display:flex; flex-direction:column; gap:14px;">
                <div class="form-group">
                    <label class="form-label">Nama Toko <span class="required">*</span></label>
                    <input type="text" name="name" id="edit-name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Cabang / Divisi</label>
                    <input type="text" name="branch" id="edit-branch" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Kontak PIC Toko</label>
                    <input type="text" name="contact" id="edit-contact" class="form-input">
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
            <div class="modal-title">Impor Toko dari CSV</div>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-import').classList.add('hidden')">✕</button>
        </div>
        <form method="POST" action="{{ route('master.stores.import-csv') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:6px; padding:10px 14px; margin-bottom:14px; font-size:12px; color:#92400E;">
                    Format kolom CSV: <strong>name, branch, contact</strong><br>Baris pertama (header) dilewati otomatis.
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
function openAddModal() {
    document.getElementById('modal-add').classList.remove('hidden');
}
function openEditModal(store) {
    document.getElementById('edit-name').value    = store.name;
    document.getElementById('edit-branch').value  = store.branch || '';
    document.getElementById('edit-contact').value = store.contact || '';
    document.getElementById('edit-active').value  = store.is_active ? '1' : '0';
    document.getElementById('form-edit').action   = `/master/stores/${store.id}`;
    document.getElementById('modal-edit').classList.remove('hidden');
}
async function confirmDelete(e, name) {
    e.preventDefault();
    const form = e.target;
    const result = await Swal.fire({
        title: 'Hapus Toko?',
        text: `Toko "${name}" akan dihapus. Pastikan tidak ada device yang terdaftar.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#DC2626',
    });
    if (result.isConfirmed) form.submit();
    return false;
}
</script>
@endpush
