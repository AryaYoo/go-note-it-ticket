@extends('layouts.app')
@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')

@push('head')
<style>
    .user-table-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    .user-table-wrap {
        overflow-x: auto;
    }
    .user-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        text-align: left;
    }
    .user-table th {
        background: #F9FAFB;
        padding: 12px 16px;
        font-weight: 600;
        font-size: 11.5px;
        text-transform: uppercase;
        color: var(--muted);
        border-bottom: 1px solid var(--border);
    }
    .user-table td {
        padding: 13px 16px;
        border-bottom: 1px solid #F3F4F6;
        color: var(--text);
        vertical-align: middle;
    }
    .user-table tr:last-child td {
        border-bottom: none;
    }
    .user-table tr:hover {
        background: #FDFDFD;
    }
    .role-badge {
        display: inline-block;
        padding: 3px 9px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 4px;
        text-transform: capitalize;
    }
    .role-manager {
        background: #F3E8FF;
        color: #7E22CE;
    }
    .role-staff {
        background: #EFF6FF;
        color: #1D4ED8;
    }

    /* Modal styles */
    .custom-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1000;
        background: rgba(17, 24, 39, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .custom-modal-backdrop.hidden {
        display: none !important;
    }
    .custom-modal {
        background: #fff;
        border-radius: 8px;
        width: 100%;
        max-width: 460px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        overflow: hidden;
        animation: modalFadeIn 0.15s ease-out;
    }
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.97); }
        to { opacity: 1; transform: scale(1); }
    }
    .custom-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
    }
    .custom-modal-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--text);
    }
    .custom-modal-close {
        background: none;
        border: none;
        font-size: 18px;
        color: var(--muted);
        cursor: pointer;
        padding: 4px;
        line-height: 1;
    }
    .custom-modal-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .custom-modal-footer {
        padding: 12px 20px;
        background: #F9FAFB;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
    .form-group-compact {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .form-group-compact label {
        font-size: 12px;
        font-weight: 500;
        color: var(--text);
    }
    .form-group-compact input,
    .form-group-compact select {
        padding: 8px 10px;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 13px;
        font-family: inherit;
        outline: none;
        transition: border-color 0.15s;
    }
    .form-group-compact input:focus,
    .form-group-compact select:focus {
        border-color: var(--primary);
    }
</style>
@endpush

@section('content')
<div class="page-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--text);">Manajemen Pengguna</h1>
        <div class="page-header-sub">Kelola akun staf IT dan manajer operasional</div>
    </div>
    <div>
        <button type="button" onclick="openModal('create-user-modal')" class="btn btn-primary" style="padding: 8px 16px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah User
        </button>
    </div>
</div>

<div class="user-table-card">
    <div class="user-table-wrap">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email / Username</th>
                    <th>Divisi</th>
                    <th>Role</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td style="font-weight: 600;">{{ $user->name }}</td>
                    <td style="color: var(--muted);">{{ $user->email }}</td>
                    <td>{{ $user->divisi ?: '—' }}</td>
                    <td>
                        <span class="role-badge {{ $user->role === 'manager' ? 'role-manager' : 'role-staff' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <button type="button" onclick="editUser({{ $user->id }}, @js($user->name), @js($user->email), @js($user->divisi ?? ''), @js($user->role))" class="btn btn-secondary btn-sm" style="margin-right: 4px;">Edit</button>
                        @if($user->id !== auth()->id())
                        <form action="{{ route('manager.users.destroy', $user) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div style="padding: 14px 20px; border-top: 1px solid var(--border);">
        {{ $users->links() }}
    </div>
    @endif
</div>

<!-- Modal Tambah User -->
<div id="create-user-modal" class="custom-modal-backdrop hidden">
    <div class="custom-modal" onclick="event.stopPropagation()">
        <div class="custom-modal-header">
            <h3 class="custom-modal-title">Tambah Pengguna Baru</h3>
            <button type="button" onclick="closeModal('create-user-modal')" class="custom-modal-close">&times;</button>
        </div>
        <form action="{{ route('manager.users.store') }}" method="POST">
            @csrf
            <div class="custom-modal-body">
                <div class="form-group-compact">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" required placeholder="Contoh: Budi Santoso">
                </div>
                <div class="form-group-compact">
                    <label>Email / Username</label>
                    <input type="text" name="email" required placeholder="budi@gonote.id">
                </div>
                <div class="form-group-compact">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Minimal 4 karakter">
                </div>
                <div class="form-group-compact">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="staff">Staff (IT Support)</option>
                        <option value="manager">Manager (Admin)</option>
                    </select>
                </div>
                <div class="form-group-compact">
                    <label>Divisi (Opsional)</label>
                    <input type="text" name="divisi" placeholder="Contoh: IT Support">
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" onclick="closeModal('create-user-modal')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit User -->
<div id="edit-user-modal" class="custom-modal-backdrop hidden">
    <div class="custom-modal" onclick="event.stopPropagation()">
        <div class="custom-modal-header">
            <h3 class="custom-modal-title">Edit Pengguna</h3>
            <button type="button" onclick="closeModal('edit-user-modal')" class="custom-modal-close">&times;</button>
        </div>
        <form id="edit-user-form" method="POST">
            @csrf
            @method('PUT')
            <div class="custom-modal-body">
                <div class="form-group-compact">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" id="edit-name" required>
                </div>
                <div class="form-group-compact">
                    <label>Email / Username</label>
                    <input type="text" name="email" id="edit-email" required>
                </div>
                <div class="form-group-compact">
                    <label>Password (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" placeholder="Biarkan kosong jika tidak diubah">
                </div>
                <div class="form-group-compact">
                    <label>Role</label>
                    <select name="role" id="edit-role" required>
                        <option value="staff">Staff (IT Support)</option>
                        <option value="manager">Manager (Admin)</option>
                    </select>
                </div>
                <div class="form-group-compact">
                    <label>Divisi (Opsional)</label>
                    <input type="text" name="divisi" id="edit-divisi">
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" onclick="closeModal('edit-user-modal')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function editUser(id, name, email, divisi, role) {
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-email').value = email;
        document.getElementById('edit-divisi').value = divisi || '';
        document.getElementById('edit-role').value = (role === 'user' ? 'staff' : role);
        
        document.getElementById('edit-user-form').action = `/manager/users/${id}`;
        openModal('edit-user-modal');
    }

    // Cegah modal ter-close saat pengguna menyeleksi / mem-blok teks dan mouse terlepas di area backdrop
    let mouseDownTarget = null;

    document.addEventListener('mousedown', function(e) {
        mouseDownTarget = e.target;
    });

    document.addEventListener('mouseup', function(e) {
        // Modal hanya ditutup jika mousedown DAN mouseup keduanya dilakukan langsung pada elemen backdrop yang sama
        if (mouseDownTarget === e.target && (e.target.id === 'create-user-modal' || e.target.id === 'edit-user-modal')) {
            closeModal(e.target.id);
        }
        mouseDownTarget = null;
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('create-user-modal');
            closeModal('edit-user-modal');
        }
    });
</script>
@endpush
@endsection
