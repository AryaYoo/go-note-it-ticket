@extends('layouts.app')
@section('title', 'Super Admin — Manajemen Manager')

@push('head')
<style>
    :root {
        --sa-purple:   #7C3AED;
        --sa-purple-l: #EDE9FE;
        --sa-indigo:   #4F46E5;
        --sa-blue:     #2563EB;
        --sa-blue-l:   #DBEAFE;
        --sa-red:      #DC2626;
    }

    .sa-page-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
    }
    .sa-page-title { font-size: 20px; font-weight: 700; color: var(--text); }
    .sa-page-sub   { font-size: 13px; color: var(--muted); margin-top: 2px; }

    .btn-sa-primary {
        padding: 9px 18px; border-radius: 8px; border: none;
        background: var(--sa-purple); color: #fff;
        font-size: 13px; font-weight: 600; cursor: pointer;
        display: flex; align-items: center; gap: 7px;
        transition: background .15s; text-decoration: none;
    }
    .btn-sa-primary:hover { background: var(--sa-indigo); }

    /* ── Table ──────────────────────────────────────────── */
    .sa-table-wrap {
        background: #fff; border: 1px solid var(--border);
        border-radius: 12px; overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }
    .sa-table {
        width: 100%; border-collapse: collapse; font-size: 13.5px;
    }
    .sa-table thead th {
        background: #FAFAFA; padding: 12px 18px;
        font-size: 11.5px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .5px; color: var(--muted);
        border-bottom: 1px solid var(--border); text-align: left;
    }
    .sa-table tbody td {
        padding: 14px 18px;
        border-bottom: 1px solid #F3F4F6;
        color: var(--text); vertical-align: middle;
    }
    .sa-table tbody tr:last-child td { border-bottom: none; }
    .sa-table tbody tr:hover td { background: #F9FAFB; }

    .user-chip { display: flex; align-items: center; gap: 10px; }
    .user-chip-avatar {
        width: 36px; height: 36px; border-radius: 8px;
        background: linear-gradient(135deg, var(--sa-blue), var(--sa-purple));
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 700; color: #fff;
        flex-shrink: 0;
    }
    .user-chip-name  { font-weight: 600; font-size: 13.5px; color: var(--text); }
    .user-chip-email { font-size: 12px; color: var(--muted); }

    .company-pill {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 20px;
        background: var(--sa-purple-l); color: var(--sa-purple);
        font-size: 12px; font-weight: 600;
    }

    .btn-danger-sm {
        padding: 6px 13px; border-radius: 6px;
        border: 1.5px solid #FECACA;
        background: #fff; cursor: pointer;
        font-size: 12px; font-weight: 600; color: var(--sa-red);
        display: inline-flex; align-items: center; gap: 5px;
        transition: background .15s, border-color .15s;
    }
    .btn-danger-sm:hover { background: #FEF2F2; border-color: #EF4444; }

    /* ── Empty state ────────────────────────────────────── */
    .sa-empty {
        text-align: center; padding: 48px; color: var(--muted);
    }
    .sa-empty svg { margin-bottom: 12px; }
    .sa-empty-title { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 4px; }
    .sa-empty-sub   { font-size: 13px; }

    /* ── Modal ──────────────────────────────────────────── */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.45); z-index: 1000;
        align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: #fff; border-radius: 14px;
        padding: 28px 30px; width: 100%; max-width: 460px;
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
    .modal-title { font-size: 17px; font-weight: 700; color: var(--text); }
    .modal-sub   { font-size: 13px; color: var(--muted); margin-bottom: 22px; margin-left: 50px; }
    .form-group  { margin-bottom: 14px; }
    .form-label  { display: block; font-size: 12.5px; font-weight: 600; color: var(--text); margin-bottom: 5px; }
    .form-input  {
        width: 100%; padding: 9px 12px; border-radius: 7px;
        border: 1.5px solid var(--border); font-size: 13.5px;
        color: var(--text); background: #fff; outline: none;
        transition: border-color .15s; box-sizing: border-box;
    }
    .form-input:focus { border-color: var(--sa-purple); box-shadow: 0 0 0 3px rgba(124,58,237,0.08); }
    .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 22px; }
    .btn-cancel { padding: 9px 20px; border-radius: 7px; border: 1.5px solid var(--border);
        background: #fff; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--muted); }
    .btn-primary-sa { padding: 9px 22px; border-radius: 7px; border: none;
        background: var(--sa-purple); color: #fff; cursor: pointer; font-size: 13px; font-weight: 600;
        display: flex; align-items: center; gap: 7px; }
    .btn-primary-sa:hover { background: var(--sa-indigo); }

    .pagination-wrap { display: flex; justify-content: center; margin-top: 20px; }
</style>
@endpush

@section('content')

<div class="sa-page-header">
    <div>
        <h1 class="sa-page-title">Manajemen Manager</h1>
        <div class="sa-page-sub">Tambah dan kelola akun manager untuk tiap perusahaan.</div>
    </div>
    <button onclick="document.getElementById('addManagerModal').classList.add('open')" class="btn-sa-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Tambah Manager
    </button>
</div>

<div class="sa-table-wrap">
    <table class="sa-table">
        <thead>
            <tr>
                <th>Manager</th>
                <th>Perusahaan</th>
                <th>Terdaftar Sejak</th>
                <th style="text-align:right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($managers as $manager)
            <tr>
                <td>
                    <div class="user-chip">
                        <div class="user-chip-avatar">{{ strtoupper(substr($manager->name, 0, 1)) }}</div>
                        <div>
                            <div class="user-chip-name">{{ $manager->name }}</div>
                            <div class="user-chip-email">{{ $manager->email }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    @if($manager->company)
                    <span class="company-pill">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                        </svg>
                        {{ $manager->company->name }}
                    </span>
                    @else
                    <span style="color: var(--muted); font-size: 12px;">&mdash;</span>
                    @endif
                </td>
                <td style="color: var(--muted); font-size: 12.5px;">{{ $manager->created_at->format('d M Y') }}</td>
                <td style="text-align:right">
                    <form method="POST" action="{{ route('superadmin.managers.destroy', $manager) }}"
                        onsubmit="return confirm('Hapus manager {{ addslashes($manager->name) }}?')" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger-sm">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                <path d="M10 11v6"/><path d="M14 11v6"/>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                            </svg>
                            Hapus
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4">
                    <div class="sa-empty">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="1.5">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <div class="sa-empty-title">Belum ada manager</div>
                        <div class="sa-empty-sub">Tambahkan manager untuk mulai mengelola perusahaan.</div>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $managers->links() }}
</div>

{{-- ── Modal Tambah Manager ─────────────────────────────── --}}
<div id="addManagerModal" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-header-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/>
                    <line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
            </div>
            <div class="modal-title">Tambah Manager Baru</div>
        </div>
        <div class="modal-sub">Manager baru akan dapat login dan mengelola staf di perusahaannya.</div>
        <form method="POST" action="{{ route('superadmin.managers.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Perusahaan <span style="color:#EF4444">*</span></label>
                <select name="company_id" class="form-input" required>
                    <option value="">— Pilih Perusahaan —</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Manager <span style="color:#EF4444">*</span></label>
                <input type="text" name="name" class="form-input" placeholder="Ahmad Fauzi" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email / Username <span style="color:#EF4444">*</span></label>
                <input type="text" name="email" class="form-input" placeholder="manager@perusahaan.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password <span style="color:#EF4444">*</span></label>
                <input type="password" name="password" class="form-input" placeholder="Min. 6 karakter" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('addManagerModal').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary-sa">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Simpan Manager
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
