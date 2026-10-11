@extends('layouts.app')
@section('title', 'Super Admin — Dashboard Platform')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    :root {
        --sa-purple:   #7C3AED;
        --sa-purple-l: #EDE9FE;
        --sa-indigo:   #4F46E5;
        --sa-green:    #059669;
        --sa-green-l:  #D1FAE5;
        --sa-amber:    #D97706;
        --sa-amber-l:  #FEF3C7;
        --sa-blue:     #2563EB;
        --sa-blue-l:   #DBEAFE;
    }

    .sa-hero {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 60%, #6D28D9 100%);
        border-radius: 14px;
        padding: 28px 32px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .sa-hero::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,0.06);
        pointer-events: none;
    }
    .sa-hero::after {
        content: '';
        position: absolute;
        bottom: -60px; left: 30%;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: rgba(255,255,255,0.04);
        pointer-events: none;
    }
    .sa-hero-icon {
        width: 56px; height: 56px;
        background: rgba(255,255,255,0.15);
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.2);
    }
    .sa-hero-title { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
    .sa-hero-sub   { font-size: 13.5px; opacity: .75; }

    .sa-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .sa-kpi {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: box-shadow .2s, transform .2s;
    }
    .sa-kpi:hover { box-shadow: 0 4px 14px rgba(0,0,0,0.08); transform: translateY(-1px); }
    .sa-kpi-icon {
        width: 46px; height: 46px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .sa-kpi-label { font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); margin-bottom: 4px; }
    .sa-kpi-value { font-size: 28px; font-weight: 700; color: var(--text); line-height: 1; }

    .sa-content-grid {
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 20px;
    }
    @media (max-width: 900px) { .sa-content-grid { grid-template-columns: 1fr; } }

    .sa-panel {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .sa-panel-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between;
        background: #FAFAFA;
    }
    .sa-panel-title { font-size: 14px; font-weight: 600; color: var(--text); }

    .company-row {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 20px;
        border-bottom: 1px solid #F3F4F6;
        transition: background .15s;
    }
    .company-row:last-child { border-bottom: none; }
    .company-row:hover { background: #F9FAFB; }
    .company-avatar {
        width: 38px; height: 38px;
        border-radius: 9px;
        background: linear-gradient(135deg, var(--sa-indigo), var(--sa-purple));
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; font-weight: 700; color: #fff;
        flex-shrink: 0;
    }
    .company-name  { font-size: 13.5px; font-weight: 600; color: var(--text); }
    .company-meta  { font-size: 12px; color: var(--muted); margin-top: 1px; }
    .company-badge {
        margin-left: auto;
        font-size: 11px; font-weight: 600;
        padding: 3px 9px; border-radius: 20px;
    }
    .badge-active   { background: var(--sa-green-l); color: var(--sa-green); }
    .badge-inactive { background: #F3F4F6; color: var(--muted); }

    .sa-actions-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        padding: 16px;
    }
    .sa-action-btn {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 8px; padding: 18px 12px;
        border-radius: 10px;
        border: 1.5px solid var(--border);
        background: #fff;
        cursor: pointer; text-decoration: none;
        transition: border-color .2s, background .2s, transform .2s;
        color: var(--text);
    }
    .sa-action-btn:hover { border-color: var(--sa-purple); background: var(--sa-purple-l); transform: translateY(-2px); }
    .sa-action-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    .sa-action-label { font-size: 12.5px; font-weight: 600; text-align: center; }
</style>
@endpush

@section('content')

{{-- Hero --}}
<div class="sa-hero">
    <div class="sa-hero-icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
            <path d="M2 17l10 5 10-5"/>
            <path d="M2 12l10 5 10-5"/>
        </svg>
    </div>
    <div>
        <div class="sa-hero-title">Selamat datang, {{ Auth::user()->name }}!</div>
        <div class="sa-hero-sub">Panel Super Admin — Kelola seluruh perusahaan dan akses di satu tempat.</div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="sa-kpi-grid">
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background: var(--sa-purple-l);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="7" width="20" height="14" rx="2"/>
                <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
            </svg>
        </div>
        <div>
            <div class="sa-kpi-label">Total Perusahaan</div>
            <div class="sa-kpi-value">{{ $totalCompanies }}</div>
        </div>
    </div>
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background: var(--sa-green-l);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div>
            <div class="sa-kpi-label">Perusahaan Aktif</div>
            <div class="sa-kpi-value">{{ $activeCompanies }}</div>
        </div>
    </div>
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background: var(--sa-blue-l);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div>
            <div class="sa-kpi-label">Total User Aktif</div>
            <div class="sa-kpi-value">{{ $totalUsers }}</div>
        </div>
    </div>
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background: var(--sa-amber-l);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
                <line x1="12" y1="11" x2="12" y2="16"/>
                <line x1="9" y1="14" x2="15" y2="14"/>
            </svg>
        </div>
        <div>
            <div class="sa-kpi-label">Total Manager</div>
            <div class="sa-kpi-value">{{ $totalManagers }}</div>
        </div>
    </div>
</div>

{{-- Main content --}}
<div class="sa-content-grid">

    {{-- Companies list --}}
    <div class="sa-panel">
        <div class="sa-panel-header">
            <span class="sa-panel-title">Daftar Perusahaan</span>
            <a href="{{ route('superadmin.companies') }}" style="font-size: 12.5px; color: var(--sa-purple); font-weight: 600; text-decoration: none; display:flex; align-items:center; gap:4px;">
                Lihat Semua
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>
        <div>
            @forelse($companies->take(6) as $company)
            <div class="company-row">
                <div class="company-avatar">{{ strtoupper(substr($company->name, 0, 1)) }}</div>
                <div>
                    <div class="company-name">{{ $company->name }}</div>
                    <div class="company-meta">{{ $company->users_count }} user &middot; {{ $company->tickets_count }} tiket</div>
                </div>
                <span class="company-badge {{ $company->is_active ? 'badge-active' : 'badge-inactive' }}">
                    {{ $company->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            @empty
            <div style="padding: 32px; text-align: center; color: var(--muted); font-size: 13px;">
                Belum ada perusahaan terdaftar.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="sa-panel">
        <div class="sa-panel-header">
            <span class="sa-panel-title">Aksi Cepat</span>
        </div>
        <div class="sa-actions-grid">
            <a href="{{ route('superadmin.companies') }}" class="sa-action-btn">
                <div class="sa-action-icon" style="background: var(--sa-purple-l);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2"/>
                        <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                    </svg>
                </div>
                <span class="sa-action-label">Kelola Perusahaan</span>
            </a>
            <a href="{{ route('superadmin.managers') }}" class="sa-action-btn">
                <div class="sa-action-icon" style="background: var(--sa-blue-l);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <span class="sa-action-label">Kelola Manager</span>
            </a>
        </div>
    </div>

</div>
@endsection
