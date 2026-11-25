<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Go-Note IT Ticketing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            <span class="auth-logo-text">Go-Note</span>
        </div>
        <p class="auth-subtitle">IT Ticketing System — Masuk ke akun Anda</p>

        <form method="POST" action="{{ route('login') }}" id="login-form">
            @csrf
            <div class="form-group">
                <label class="form-label">Email atau Username</label>
                <input type="text" name="email" id="login-email" class="form-input" value="{{ old('email') }}" required autofocus placeholder="Masukkan email atau username">
                @error('email') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="login-password" class="form-input" required placeholder="••••••••">
            </div>
            <div class="form-group flex-between" style="margin-bottom:18px;">
                <label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;">
                    <input type="checkbox" name="remember"> Ingat saya
                </label>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Masuk</button>
        </form>

        @if(\App\Models\Setting::isTrue('show_demo_credentials', true))
        <hr class="divider" style="margin: 20px 0 16px 0;">
        <div style="font-size:11.5px;color:var(--muted);text-align:center;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <span style="font-size:11px;font-weight:600;color:var(--text-secondary,#64748B);text-transform:uppercase;letter-spacing:0.5px;">Akun Demo (Klik untuk Isi)</span>
                <span style="font-size:9.5px;background:#EEF2FF;color:#4F46E5;padding:2px 6px;border-radius:4px;font-weight:600;">1-Klik</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;text-align:left;">
                <div onclick="fillLogin('it', 'it')" style="display:flex;align-items:center;justify-content:space-between;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:6px 10px;cursor:pointer;transition:all 0.15s ease;" onmouseover="this.style.borderColor='#3B82F6';this.style.background='#EFF6FF'" onmouseout="this.style.borderColor='#E2E8F0';this.style.background='#F8FAFC'" title="Gunakan Akun Staff IT">
                    <div>
                        <span style="font-weight:600;color:#1E293B;">Staff IT:</span>
                        <code style="background:#E2E8F0;padding:1px 5px;border-radius:3px;font-size:11px;color:#0F172A;">it</code> / <code style="background:#E2E8F0;padding:1px 5px;border-radius:3px;font-size:11px;color:#0F172A;">it</code>
                    </div>
                    <span style="font-size:11px;color:#2563EB;font-weight:600;">Gunakan &rsaquo;</span>
                </div>
                <div onclick="fillLogin('managerit@gmail.com', 'password')" style="display:flex;align-items:center;justify-content:space-between;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:6px 10px;cursor:pointer;transition:all 0.15s ease;" onmouseover="this.style.borderColor='#3B82F6';this.style.background='#EFF6FF'" onmouseout="this.style.borderColor='#E2E8F0';this.style.background='#F8FAFC'" title="Gunakan Akun Manager IT">
                    <div>
                        <span style="font-weight:600;color:#1E293B;">Manager IT:</span>
                        <code style="background:#E2E8F0;padding:1px 5px;border-radius:3px;font-size:11px;color:#0F172A;">managerit@gmail.com</code> / <code style="background:#E2E8F0;padding:1px 5px;border-radius:3px;font-size:11px;color:#0F172A;">password</code>
                    </div>
                    <span style="font-size:11px;color:#2563EB;font-weight:600;">Gunakan &rsaquo;</span>
                </div>
                <div onclick="fillLogin('superadmin@system.local', 'admin123')" style="display:flex;align-items:center;justify-content:space-between;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:6px 10px;cursor:pointer;transition:all 0.15s ease;" onmouseover="this.style.borderColor='#3B82F6';this.style.background='#EFF6FF'" onmouseout="this.style.borderColor='#E2E8F0';this.style.background='#F8FAFC'" title="Gunakan Akun Super Admin">
                    <div>
                        <span style="font-weight:600;color:#1E293B;">Super Admin:</span>
                        <code style="background:#E2E8F0;padding:1px 5px;border-radius:3px;font-size:11px;color:#0F172A;">superadmin@system.local</code> / <code style="background:#E2E8F0;padding:1px 5px;border-radius:3px;font-size:11px;color:#0F172A;">admin123</code>
                    </div>
                    <span style="font-size:11px;color:#2563EB;font-weight:600;">Gunakan &rsaquo;</span>
                </div>
            </div>
            <div style="margin-top:8px;font-size:10.5px;color:#94A3B8;">
                Tersedia juga alias: <em>staff@gonote.id</em> & <em>admin@hsitoperasional.com</em>
            </div>
        </div>
        @endif
    </div>
</div>
<script>
function fillLogin(email, password) {
    const emailInput = document.getElementById('login-email');
    const passwordInput = document.getElementById('login-password');
    if (emailInput && passwordInput) {
        emailInput.value = email;
        passwordInput.value = password;
        passwordInput.focus();
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
@if($errors->any())
<script>
Swal.fire({ icon:'error', title:'Login Gagal', text:'{{ $errors->first() }}' });
</script>
@endif
</body>
</html>
