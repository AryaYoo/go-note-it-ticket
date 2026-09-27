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
                <input type="text" name="email" class="form-input" value="{{ old('email') }}" required autofocus placeholder="admin@hsitoperasional.com">
                @error('email') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required placeholder="••••••••">
            </div>
            <div class="form-group flex-between" style="margin-bottom:18px;">
                <label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;">
                    <input type="checkbox" name="remember"> Ingat saya
                </label>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Masuk</button>
        </form>

        @if(\App\Models\Setting::isTrue('show_demo_credentials', true))
        <hr class="divider">
        <div style="font-size:11.5px;color:var(--muted);text-align:center; line-height:1.5;">
            Demo Staff: <strong>staff@gonote.id</strong> / <strong>password</strong> <br>
            Demo Admin: <strong>admin@hsitoperasional.com</strong> / <strong>admin</strong>
        </div>
        @endif
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
@if($errors->any())
<script>
Swal.fire({ icon:'error', title:'Login Gagal', text:'{{ $errors->first() }}' });
</script>
@endif
</body>
</html>
