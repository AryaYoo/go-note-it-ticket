@extends('layouts.app')
@section('title', 'Super Admin — Manajemen Perusahaan')

@push('head')
<style>
    :root {
        --sa-purple:   #7C3AED;
        --sa-purple-l: #EDE9FE;
        --sa-indigo:   #4F46E5;
        --sa-green:    #059669;
        --sa-green-l:  #D1FAE5;
        --sa-red:      #DC2626;
    }

    .sa-page-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
    }
    .sa-page-title { font-size: 20px; font-weight: 700; color: var(--text); }
    .sa-page-sub   { font-size: 13px; color: var(--muted); margin-top: 2px; }

    /* ── Btn primary ──────────────────────────────────────── */
    .btn-sa-primary {
        padding: 9px 18px; border-radius: 8px; border: none;
        background: var(--sa-purple); color: #fff;
        font-size: 13px; font-weight: 600; cursor: pointer;
        display: flex; align-items: center; gap: 7px;
        transition: background .15s;
        text-decoration: none;
    }
    .btn-sa-primary:hover { background: var(--sa-indigo); }

    /* ── Company cards ──────────────────────────────────────*/
    .company-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }
    .company-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        transition: box-shadow .2s, transform .2s;
    }
    .company-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); transform: translateY(-2px); }
    .company-card.inactive { opacity: .65; }

    .cc-top {
        display: flex; align-items: center; gap: 14px; margin-bottom: 16px;
    }
    .cc-avatar {
        width: 48px; height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--sa-indigo), var(--sa-purple));
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; font-weight: 700; color: #fff;
        flex-shrink: 0;
    }
    .cc-name   { font-size: 15px; font-weight: 700; color: var(--text); }
    .cc-email  { font-size: 12.5px; color: var(--muted); margin-top: 2px; }
    .cc-status {
        margin-left: auto; flex-shrink: 0;
        font-size: 11px; font-weight: 600;
        padding: 4px 10px; border-radius: 20px;
    }
    .cc-status.active   { background: var(--sa-green-l); color: var(--sa-green); }
    .cc-status.inactive { background: #F3F4F6; color: var(--muted); }

    .cc-stats {
        display: flex; gap: 16px;
        padding: 12px 14px;
        background: #F9FAFB;
        border-radius: 8px;
        margin-bottom: 14px;
    }
    .cc-stat-item { text-align: center; flex: 1; }
    .cc-stat-num  { font-size: 18px; font-weight: 700; color: var(--text); }
    .cc-stat-lbl  { font-size: 10.5px; color: var(--muted); font-weight: 600; text-transform: uppercase; margin-top: 1px; }

    .cc-actions { display: flex; gap: 8px; }
    .cc-btn {
        flex: 1; padding: 8px 12px;
        border-radius: 7px; border: 1.5px solid var(--border);
        background: #fff; cursor: pointer; font-size: 12.5px; font-weight: 600;
        color: var(--text); display: flex; align-items: center; justify-content: center; gap: 6px;
        transition: border-color .15s, background .15s, color .15s;
    }
    .cc-btn:hover  { border-color: var(--sa-purple); background: var(--sa-purple-l); color: var(--sa-purple); }
    .cc-btn.danger { border-color: #FECACA; }
    .cc-btn.danger:hover { border-color: #EF4444; background: #FEF2F2; color: var(--sa-red); }
    .cc-btn:disabled { opacity: .4; cursor: not-allowed; }

    /* ── Modal ───────────────────────────────────────────── */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.45); z-index: 1000;
        align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: #fff; border-radius: 14px;
        padding: 28px 30px; width: 100%; max-width: 500px;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        animation: slideUp .25s ease;
    }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .modal-header { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
    .modal-header-icon {
        width: 38px; height: 38px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        background: var(--sa-purple-l); flex-shrink: 0;
    }
    .modal-title  { font-size: 17px; font-weight: 700; color: var(--text); }
    .modal-sub    { font-size: 13px; color: var(--muted); margin-bottom: 22px; margin-left: 50px; }
    .modal-divider { border: none; border-top: 1px solid var(--border); margin: 16px 0; }
    .form-group   { margin-bottom: 14px; }
    .form-label   { display: block; font-size: 12.5px; font-weight: 600; color: var(--text); margin-bottom: 5px; }
    .form-input   {
        width: 100%; padding: 9px 12px; border-radius: 7px;
        border: 1.5px solid var(--border); font-size: 13.5px;
        color: var(--text); background: #fff; outline: none;
        transition: border-color .15s; box-sizing: border-box;
    }
    .form-input:focus { border-color: var(--sa-purple); box-shadow: 0 0 0 3px rgba(124,58,237,0.08); }
    .form-section {
        font-size: 11.5px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .5px; color: var(--sa-purple);
        margin-bottom: 12px;
        display: flex; align-items: center; gap: 6px;
    }
    .form-section::after { content: ''; flex: 1; height: 1px; background: var(--sa-purple-l); }
    .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 22px; }
    .btn-cancel { padding: 9px 20px; border-radius: 7px; border: 1.5px solid var(--border);
        background: #fff; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--muted); }
    .btn-primary-sa { padding: 9px 22px; border-radius: 7px; border: none;
        background: var(--sa-purple); color: #fff; cursor: pointer; font-size: 13px; font-weight: 600;
        display: flex; align-items: center; gap: 7px;
        transition: background .15s; }
    .btn-primary-sa:hover { background: var(--sa-indigo); }

    .pagination-wrap { display: flex; justify-content: center; margin-top: 20px; }
</style>
@endpush

@section('content')

<div class="sa-page-header">
    <div>
        <h1 class="sa-page-title">Manajemen Perusahaan</h1>
        <div class="sa-page-sub">Daftarkan perusahaan baru dan pantau aktivitasnya.</div>
    </div>
    <button onclick="document.getElementById('addCompanyModal').classList.add('open')" class="btn-sa-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Tambah Perusahaan
    </button>
</div>

{{-- Company cards --}}
<div class="company-cards">
    @forelse($companies as $company)
    <div class="company-card {{ $company->is_active ? '' : 'inactive' }}">
        <div class="cc-top">
            <div class="cc-avatar">{{ strtoupper(substr($company->name, 0, 1)) }}</div>
            <div>
                <div class="cc-name">{{ $company->name }}</div>
                @if($company->email)
                <div class="cc-email">{{ $company->email }}</div>
                @endif
            </div>
            <span class="cc-status {{ $company->is_active ? 'active' : 'inactive' }}">
                {{ $company->is_active ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>

        <div class="cc-stats">
            <div class="cc-stat-item">
                <div class="cc-stat-num">{{ $company->users_count }}</div>
                <div class="cc-stat-lbl">User</div>
            </div>
            <div class="cc-stat-item">
                <div class="cc-stat-num">{{ $company->tickets_count }}</div>
                <div class="cc-stat-lbl">Tiket</div>
            </div>
            <div class="cc-stat-item">
                <div class="cc-stat-num">{{ $company->created_at->format('Y') }}</div>
                <div class="cc-stat-lbl">Sejak</div>
            </div>
        </div>

        <div class="cc-actions">
            <button onclick="openEditCompany({{ $company->id }}, '{{ addslashes($company->name) }}', '{{ $company->email }}', {{ $company->is_active ? 'true' : 'false' }})"
                class="cc-btn">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit
            </button>
            @if($company->id !== 1)
            <form method="POST" action="{{ route('superadmin.companies.destroy', $company) }}"
                onsubmit="return confirm('Hapus perusahaan {{ addslashes($company->name) }}? Semua data terkait akan ikut terhapus.')" style="flex:1">
                @csrf @method('DELETE')
                <button type="submit" class="cc-btn danger" style="width:100%">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/>
                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                    </svg>
                    Hapus
                </button>
            </form>
            @else
            <button class="cc-btn" disabled style="flex:1;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                Protected
            </button>
            @endif
        </div>
    </div>
    @empty
    <div style="grid-column: 1/-1; text-align: center; padding: 48px; color: var(--muted); background: #fff; border: 1px solid var(--border); border-radius: 12px;">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="1.5" style="margin-bottom:12px;"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        <div style="font-size:14px; font-weight:600; margin-bottom:4px;">Belum ada perusahaan</div>
        <div style="font-size:13px;">Tambahkan perusahaan pertama untuk memulai.</div>
    </div>
    @endforelse
</div>

<div class="pagination-wrap">
    {{ $companies->links() }}
</div>

{{-- ── Modal Tambah Perusahaan ──────────────────────────── --}}
<div id="addCompanyModal" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-header-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                </svg>
            </div>
            <div class="modal-title">Tambah Perusahaan Baru</div>
        </div>
        <div class="modal-sub">Isi data perusahaan dan akun manager pertama yang akan mengelolanya.</div>

        <form method="POST" action="{{ route('superadmin.companies.store') }}">
            @csrf

            <div class="form-section">Data Perusahaan</div>

            <div class="form-group">
                <label class="form-label">Nama Perusahaan <span style="color:#EF4444">*</span></label>
                <input type="text" name="name" class="form-input" placeholder="PT Sukses Makmur" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Perusahaan</label>
                <input type="email" name="email" class="form-input" placeholder="info@perusahaan.com">
            </div>

            <hr class="modal-divider">
            <div class="form-section">Akun Manager Pertama</div>

            <div class="form-group">
                <label class="form-label">Nama Manager <span style="color:#EF4444">*</span></label>
                <input type="text" name="manager_name" class="form-input" placeholder="Ahmad Fauzi" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email / Username Manager <span style="color:#EF4444">*</span></label>
                <input type="text" name="manager_email" class="form-input" placeholder="manager@perusahaan.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password Manager <span style="color:#EF4444">*</span></label>
                <input type="password" name="manager_password" class="form-input" placeholder="Min. 6 karakter" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('addCompanyModal').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary-sa">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Simpan & Buat
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal Edit Perusahaan ──────────────────────────────── --}}
<div id="editCompanyModal" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-header-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </div>
            <div class="modal-title">Edit Perusahaan</div>
        </div>
        <div class="modal-sub">Perbarui data perusahaan.</div>
        <form id="editCompanyForm" method="POST">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Nama Perusahaan <span style="color:#EF4444">*</span></label>
                <input type="text" id="editCompanyName" name="name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Perusahaan</label>
                <input type="email" id="editCompanyEmail" name="email" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" id="editCompanyStatus" class="form-input">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('editCompanyModal').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary-sa">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openEditCompany(id, name, email, isActive) {
    document.getElementById('editCompanyForm').action = `/superadmin/companies/${id}`;
    document.getElementById('editCompanyName').value  = name;
    document.getElementById('editCompanyEmail').value = email || '';
    document.getElementById('editCompanyStatus').value = isActive ? '1' : '0';
    document.getElementById('editCompanyModal').classList.add('open');
}
</script>
@endpush
