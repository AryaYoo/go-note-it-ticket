@extends('layouts.app')
@section('title', 'Pengaturan Sistem')
@section('page-title', 'Pengaturan Sistem')

@push('head')
<style>
    .settings-container {
        max-width: 800px;
    }
    .settings-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .settings-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid var(--border);
        background: #FAFAFA;
    }
    .settings-card-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--text);
    }
    .settings-card-desc {
        font-size: 12px;
        color: var(--muted);
        margin-top: 2px;
    }
    .settings-item {
        padding: 20px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        border-bottom: 1px solid #F3F4F6;
    }
    .settings-item:last-child {
        border-bottom: none;
    }
    .settings-info {
        flex: 1;
    }
    .settings-label {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 4px;
    }
    .settings-subtext {
        font-size: 12px;
        color: var(--muted);
        line-height: 1.5;
    }

    /* iOS-style toggle switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 26px;
        flex-shrink: 0;
    }
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #D1D5DB;
        transition: .25s ease;
        border-radius: 26px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .25s ease;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    input:checked + .slider {
        background-color: var(--primary);
    }
    input:focus + .slider {
        box-shadow: 0 0 1px var(--primary);
    }
    input:checked + .slider:before {
        transform: translateX(22px);
    }

    /* Preview Box */
    .preview-section {
        background: #F9FAFB;
        border: 1px dashed var(--border);
        border-radius: 8px;
        padding: 20px;
        margin-top: 14px;
    }
    .preview-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .preview-badge {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--muted);
    }
    .preview-status {
        font-size: 11.5px;
        font-weight: 600;
    }
    .preview-demo-box {
        background: #FFFFFF;
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 14px;
        text-align: center;
        font-size: 12px;
        color: var(--muted);
        line-height: 1.6;
        transition: opacity 0.3s ease, filter 0.3s ease;
    }
    .preview-demo-box.hidden-state {
        opacity: 0.35;
        filter: grayscale(1);
    }
</style>
@endpush

@section('content')
<div class="settings-container">
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 20px; font-weight: 700; color: var(--text);">Pengaturan Sistem</h1>
            <div class="page-header-sub">Konfigurasi preferensi sistem dan visibilitas tampilan login</div>
        </div>
    </div>

    <div class="settings-card">
        <div class="settings-card-header">
            <div class="settings-card-title">Tampilan & Aksesibilitas Login</div>
            <div class="settings-card-desc">Atur elemen yang tampil untuk pengguna umum saat membuka form autentikasi</div>
        </div>

        <form id="settings-form" action="{{ route('manager.settings.update') }}" method="POST">
            @csrf
            
            <div class="settings-item">
                <div class="settings-info">
                    <div class="settings-label">Tampilkan Info Akun Demo</div>
                    <div class="settings-subtext">
                        Menampilkan informasi bantuan kredensial default demo (<em>staff@gonote.id</em> & <em>admin@hsitoperasional.com</em>) di bawah tombol masuk pada halaman login.
                    </div>
                </div>
                <div>
                    <label class="switch" title="Toggle Tampilkan / Sembunyikan Info Demo">
                        <input type="checkbox" id="toggle-demo" name="show_demo_credentials" value="1" {{ $showDemoCredentials ? 'checked' : '' }} onchange="handleToggle(this)">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div style="padding: 0 22px 22px 22px;">
                <div class="preview-section">
                    <div class="preview-header">
                        <span class="preview-badge">Preview Elemen Halaman Login:</span>
                        <span id="preview-status" class="preview-status" style="color: {{ $showDemoCredentials ? 'var(--success)' : 'var(--danger)' }};">
                            {{ $showDemoCredentials ? '● Aktif (Tampil di Login)' : '○ Tersembunyi (Disembunyikan)' }}
                        </span>
                    </div>

                    <div id="demo-preview-card" class="preview-demo-box {{ $showDemoCredentials ? '' : 'hidden-state' }}">
                        Demo Staff: <strong>staff@gonote.id</strong> / <strong>password</strong> <br>
                        Demo Admin: <strong>admin@hsitoperasional.com</strong> / <strong>admin</strong>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function handleToggle(checkbox) {
    const isChecked = checkbox.checked;
    const previewCard = document.getElementById('demo-preview-card');
    const previewStatus = document.getElementById('preview-status');

    // Update preview appearance immediately
    if (isChecked) {
        previewCard.classList.remove('hidden-state');
        previewStatus.textContent = '● Aktif (Tampil di Login)';
        previewStatus.style.color = 'var(--success)';
    } else {
        previewCard.classList.add('hidden-state');
        previewStatus.textContent = '○ Tersembunyi (Disembunyikan)';
        previewStatus.style.color = 'var(--danger)';
    }

    // Auto-save via AJAX Fetch
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    if (isChecked) {
        formData.append('show_demo_credentials', '1');
    }

    fetch('{{ route('manager.settings.update') }}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: data.message || 'Pengaturan berhasil diperbarui',
                showConfirmButton: false,
                timer: 2000
            });
        }
    })
    .catch(err => {
        console.error('Save failed:', err);
        // Fallback: submit form normally if fetch fails
        document.getElementById('settings-form').submit();
    });
}
</script>
@endpush
