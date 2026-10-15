<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Go-Note') — IT Ticketing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="">
<div class="app-layout">
    {{-- Mobile Top Navbar --}}
    <header class="mobile-navbar">
        <button id="mobile-menu-toggle" class="mobile-nav-btn" aria-label="Buka Menu" title="Menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        @php
            $brandRoute = route('tickets.index');
            if (Auth::check()) {
                if (Auth::user()->isSuperAdmin()) $brandRoute = route('superadmin.dashboard');
                elseif (Auth::user()->isManager()) $brandRoute = route('manager.dashboard');
            }
        @endphp
        <a href="{{ $brandRoute }}" class="mobile-brand">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            <span class="mobile-brand-title">Go-Note</span>
        </a>

        <div class="mobile-user-avatar" title="{{ Auth::user()->name }}">
            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
        </div>
    </header>

    {{-- Mobile Backdrop Overlay --}}
    <div id="sidebar-backdrop" class="sidebar-backdrop" onclick="closeMobileSidebar()"></div>

    {{-- Sidebar --}}
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                <span class="brand-text">Go-Note</span>
            </div>
            <button id="sidebar-toggle" class="sidebar-toggle-btn desktop-only" title="Toggle Sidebar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <button class="mobile-close-btn mobile-only" onclick="closeMobileSidebar()" title="Tutup Menu">
                ✕
            </button>
        </div>

        <nav class="sidebar-nav">
            @if(Auth::check() && !Auth::user()->isManager() && !Auth::user()->isSuperAdmin())
                <a href="{{ route('tickets.index') }}" class="nav-item {{ request()->routeIs('tickets.*') ? 'active' : '' }}" title="Tiket">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10,9 9,9 8,9"/></svg>
                    <span>Tiket</span>
                </a>
                <a href="{{ route('daily-check.index') }}" class="nav-item {{ request()->routeIs('daily-check.*') ? 'active' : '' }}" title="Pengecekan Harian">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span>Pengecekan Harian</span>
                </a>
                <div class="sidebar-section-label" style="padding: 6px 16px 2px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; color: var(--muted);">Master Data</div>
                <a href="{{ route('master.stores') }}" class="nav-item {{ request()->routeIs('master.stores*') ? 'active' : '' }}" title="Master Toko">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
                    <span>Toko</span>
                </a>
                <a href="{{ route('master.devices') }}" class="nav-item {{ request()->routeIs('master.devices*') ? 'active' : '' }}" title="Master Perangkat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    <span>Perangkat</span>
                </a>
                <a href="{{ route('documentation') }}" class="nav-item {{ request()->routeIs('documentation') ? 'active' : '' }}" title="Dokumentasi">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="9" y1="7" x2="15" y2="7"/><line x1="9" y1="11" x2="13" y2="11"/></svg>
                    <span>Dokumentasi</span>
                </a>
            @endif

            {{-- ── Super Admin Menu ───────────────────────── --}}
            @if(Auth::check() && Auth::user()->isSuperAdmin())
                <div class="sidebar-admin-group" style="background: linear-gradient(135deg,#4F46E5,#7C3AED); border-radius: 10px; margin-bottom: 8px;">
                    <div class="sidebar-group-header">
                        <span class="sidebar-group-title" style="color:#fff;">Platform Admin</span>
                        <span class="sidebar-group-badge" style="background:rgba(255,255,255,0.2); color:#fff;">SA</span>
                    </div>

                    <a href="{{ route('superadmin.dashboard') }}" class="nav-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}" title="Dashboard" style="color:#E0E7FF;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('superadmin.companies') }}" class="nav-item {{ request()->routeIs('superadmin.companies*') ? 'active' : '' }}" title="Perusahaan" style="color:#E0E7FF;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                        <span>Perusahaan</span>
                    </a>
                    <a href="{{ route('superadmin.managers') }}" class="nav-item {{ request()->routeIs('superadmin.managers*') ? 'active' : '' }}" title="Manager" style="color:#E0E7FF;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Manager</span>
                    </a>
                </div>
            @endif

            @if(Auth::check() && Auth::user()->isManager())

                {{-- Kelompok Menu Khusus Admin (Background Biru Brand) --}}
                <div class="sidebar-admin-group">
                    <div class="sidebar-group-header">
                        <span class="sidebar-group-title">Menu Admin</span>
                        <span class="sidebar-group-badge">Admin</span>
                    </div>

                    <a href="{{ route('manager.dashboard') }}" class="nav-item {{ request()->routeIs('manager.dashboard') ? 'active' : '' }}" title="Dashboard">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('manager.analysis.index') }}" class="nav-item {{ request()->routeIs('manager.analysis.*') ? 'active' : '' }}" title="Monthly Analysis">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        <span>Analysis (AI)</span>
                    </a>
                    <a href="{{ route('manager.users') }}" class="nav-item {{ request()->routeIs('manager.users') ? 'active' : '' }}" title="Users">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Users</span>
                    </a>
                    <a href="{{ route('manager.settings') }}" class="nav-item {{ request()->routeIs('manager.settings*') ? 'active' : '' }}" title="Settings">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span>Settings</span>
                    </a>
                </div>

                {{-- Menu Umum / Operasional --}}
                <div class="sidebar-section-label">Operasional</div>

                <a href="{{ route('tickets.index') }}" class="nav-item {{ request()->routeIs('tickets.*') ? 'active' : '' }}" title="Tiket">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10,9 9,9 8,9"/></svg>
                    <span>Tiket</span>
                </a>
                <a href="{{ route('daily-check.index') }}" class="nav-item {{ request()->routeIs('daily-check.*') ? 'active' : '' }}" title="Pengecekan Harian">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span>Pengecekan Harian</span>
                </a>
                <div class="sidebar-section-label" style="padding: 6px 16px 2px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; color: var(--muted);">Master Data</div>
                <a href="{{ route('master.stores') }}" class="nav-item {{ request()->routeIs('master.stores*') ? 'active' : '' }}" title="Master Toko">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
                    <span>Toko</span>
                </a>
                <a href="{{ route('master.devices') }}" class="nav-item {{ request()->routeIs('master.devices*') ? 'active' : '' }}" title="Master Perangkat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    <span>Perangkat</span>
                </a>
                <a href="{{ route('documentation') }}" class="nav-item {{ request()->routeIs('documentation*') ? 'active' : '' }}" title="Dokumentasi">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="9" y1="7" x2="15" y2="7"/><line x1="9" y1="11" x2="13" y2="11"/></svg>
                    <span>Dokumentasi</span>
                </a>
            @endif

        </nav>

        {{-- Live Real-time Clock Widget --}}
        <div class="sidebar-clock" style="padding: 10px 14px; margin: 0 12px 14px 12px; background: #FFFFFF; border: 1px solid var(--border); border-radius: 8px; text-align: center; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
            <div id="sidebar-live-time" style="font-size: 15px; font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums; letter-spacing: 0.5px; font-family: 'Inter', monospace;">00:00:00</div>
            <div id="sidebar-live-date" style="font-size: 11px; color: var(--muted); margin-top: 2px;">-- --- ----</div>
        </div>

        <div class="sidebar-user">
            <div class="user-info">
                <div class="user-avatar" title="{{ Auth::user()->name }} ({{ Auth::user()->divisi ?? 'IT Support' }})">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div class="user-details">
                    <div class="user-name">{{ Auth::user()->name }}</div>
                    <div class="user-meta">
                        @if(Auth::user()->isSuperAdmin())
                            Platform Admin · <span style="color:#7C3AED; font-weight:700;">Super Admin</span>
                        @elseif(Auth::user()->isManager())
                            {{ Auth::user()->divisi ?? 'IT Support' }} · {{ ucfirst(Auth::user()->role) }}
                        @else
                            {{ Auth::user()->divisi ?? 'IT Support' }} · {{ ucfirst(Auth::user()->role) }}
                        @endif
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </aside>

    {{-- Main content --}}
    <main class="main-content">
        <div class="content-area">
            @yield('content')
            {{ $slot ?? '' }}
        </div>
    </main>
</div>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

@if(session('success'))
<script>
Swal.fire({ icon: 'success', title: 'Berhasil', text: '{{ session('success') }}', timer: 2500, showConfirmButton: false });
</script>
@endif
@if(session('error'))
<script>
Swal.fire({ icon: 'error', title: 'Gagal', text: '{{ session('error') }}' });
</script>
@endif
@if($errors->any())
<script>
Swal.fire({
    icon: 'error',
    title: 'Validasi Gagal',
    html: '{!! implode("<br>", $errors->all()) !!}'
});
</script>
@endif

@stack('scripts')
<script>
    // Live Sidebar Clock (Jam, Menit, Detik & Tanggal Realtime)
    function updateSidebarClock() {
        const now = new Date();
        const timeEl = document.getElementById('sidebar-live-time');
        const dateEl = document.getElementById('sidebar-live-date');
        
        if (timeEl) {
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            timeEl.textContent = `${h}:${m}:${s}`;
        }
        
        if (dateEl) {
            const options = { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' };
            dateEl.textContent = now.toLocaleDateString('id-ID', options);
        }
    }
    updateSidebarClock();
    setInterval(updateSidebarClock, 1000);

    // Desktop sidebar collapse toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
            if (document.body.classList.contains('sidebar-collapsed')) {
                localStorage.setItem('sidebar-state', 'collapsed');
            } else {
                localStorage.setItem('sidebar-state', 'expanded');
            }
        });
    }

    // Restore desktop collapsed state on load
    if (localStorage.getItem('sidebar-state') === 'collapsed' && window.innerWidth > 768) {
        document.body.classList.add('sidebar-collapsed');
    }

    // Mobile Sidebar Drawer Controls
    function openMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (sidebar && backdrop) {
            sidebar.classList.add('mobile-open');
            backdrop.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (sidebar && backdrop) {
            sidebar.classList.remove('mobile-open');
            backdrop.classList.remove('open');
            document.body.style.overflow = '';
        }
    }

    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', openMobileSidebar);
    }
</script>
</body>
</html>
