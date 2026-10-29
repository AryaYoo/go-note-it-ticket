@extends('layouts.app')
@section('title', 'Pengecekan Harian')

@push('head')
<style>
    /* ── Layout ── */
    .check-header-bar {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; flex-wrap: wrap; margin-bottom: 20px;
    }
    .check-date-badge {
        display: inline-flex; align-items: center; gap: 6px;
        background: #EFF6FF; color: var(--primary);
        border: 1px solid #BFDBFE; border-radius: 6px;
        padding: 5px 12px; font-size: 12.5px; font-weight: 600;
    }
    .check-save-bar {
        position: sticky; top: 0; z-index: 50;
        background: #fff; border-bottom: 1px solid var(--border);
        padding: 10px 0; margin-bottom: 20px;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
    }
    .save-status { font-size: 12px; color: var(--muted); }
    .save-status.saving { color: var(--warning); }
    .save-status.saved  { color: var(--success); }

    /* ── Store Group ── */
    .store-group {
        background: #fff; border: 1px solid var(--border);
        border-radius: 8px; margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow: hidden;
    }
    .store-group-header {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 13px 18px; background: #F9FAFB; border-bottom: 1px solid var(--border);
        cursor: pointer; user-select: none;
    }
    .store-group-title {
        font-size: 14px; font-weight: 700; color: var(--text);
        display: flex; align-items: center; gap: 8px;
    }
    .store-group-meta { font-size: 12px; color: var(--muted); font-weight: 400; }
    .offline-count-badge {
        background: #FEF2F2; color: var(--danger); border: 1px solid #FECACA;
        border-radius: 12px; padding: 2px 10px; font-size: 11.5px; font-weight: 600;
    }
    .offline-count-badge.zero { background: #F0FDF4; color: var(--success); border-color: #BBF7D0; }

    /* Status dot indicators (bulat hijau/merah simpel & jelas) */
    .status-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        position: relative;
        vertical-align: middle;
        transition: all 0.15s ease;
    }
    .status-dot.status-ok {
        background: #16A34A;
        box-shadow: 0 0 0 3.5px rgba(22, 163, 74, 0.22);
    }
    .status-dot.status-offline {
        background: #DC2626;
        box-shadow: 0 0 0 3.5px rgba(220, 38, 38, 0.22);
    }
    .status-dot[title]:hover::after {
        content: attr(title);
        position: absolute;
        bottom: calc(100% + 7px);
        left: 50%;
        transform: translateX(-50%);
        background: #111827;
        color: #fff;
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 4px;
        white-space: nowrap;
        pointer-events: none;
        z-index: 100;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    /* ── Device Row ── */
    .device-list { border-collapse: collapse; width: 100%; font-size: 13px; }
    .device-list th {
        padding: 9px 18px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px;
        color: var(--muted); font-weight: 600; background: #FAFAFA; border-bottom: 1px solid var(--border); text-align: left;
    }
    .device-list td { padding: 11px 18px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
    .device-list tr:last-child td { border-bottom: none; }
    .device-row { transition: background 150ms; }
    .device-row:hover td { background: #FAFBFC; }
    .device-row.is-offline td { background: #FFF7F7; }

    /* ── Offline Fields ── */
    .offline-detail {
        display: none; padding: 12px 18px 14px 48px;
        border-bottom: 1px solid #F3F4F6; background: #FFFBFB;
    }
    .offline-detail.visible { display: block; }
    .offline-detail-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    }
    @media (max-width: 640px) { .offline-detail-grid { grid-template-columns: 1fr; } }

    /* ── Action Section ── */
    .result-row {
        display: none; padding: 10px 18px 12px 48px;
        border-bottom: 1px solid #F3F4F6; background: #F9FAFB;
        align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .result-row.visible { display: flex; }
    .result-label { font-size: 12px; font-weight: 600; color: var(--muted); }

    /* ── Empty State ── */
    .empty-state {
        text-align: center; padding: 60px 20px;
        color: var(--muted);
    }
    .empty-state svg { margin: 0 auto 16px; display: block; opacity: 0.35; }
    .empty-state h2 { font-size: 16px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
    .empty-state p { font-size: 13px; margin-bottom: 20px; }

    /* ── Ticket Selection Modal ── */
    .modal-backdrop { position: fixed; inset: 0; z-index: 1000; background: rgba(17,24,39,0.4); display: flex; align-items: center; justify-content: center; padding: 16px; }
    .modal-backdrop.hidden { display: none !important; }
    .modal-box { background: #fff; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden; }
    .modal-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .modal-title { font-size: 14px; font-weight: 600; color: var(--text); }
    .modal-body  { padding: 20px; display: flex; flex-direction: column; gap: 12px; }
    .modal-footer { padding: 14px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 8px; }

    .device-check-item {
        display: flex; align-items: center; gap: 10px; padding: 10px 12px;
        border: 1px solid var(--border); border-radius: 6px; cursor: pointer;
        transition: background 150ms, border-color 150ms;
    }
    .device-check-item:hover { background: #F9FAFB; }
    .device-check-item.selected { background: #EFF6FF; border-color: var(--primary); }
    .device-check-item input[type=checkbox] { accent-color: var(--primary); width: 15px; height: 15px; }

    /* result pill badges */
    .pill-fixed   { background: #F0FDF4; color: var(--success); }
    .pill-no_fault { background: #F9FAFB; color: var(--muted); }
    .pill-ticket_created { background: #EFF6FF; color: var(--primary); }

    /* Icon-only action buttons */
    .icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 30px; height: 30px; border-radius: 6px;
        border: 1px solid var(--border); background: #fff; color: var(--muted);
        cursor: pointer; transition: all 0.15s ease; position: relative;
    }
    .icon-btn:hover { background: #F3F4F6; color: var(--text); border-color: #D1D5DB; }
    .icon-btn.icon-btn-success { color: #16A34A; border-color: #BBF7D0; background: #F0FDF4; }
    .icon-btn.icon-btn-success:hover { background: #DCFCE7; border-color: #16A34A; }
    .icon-btn.icon-btn-danger { color: #EF4444; border-color: #FECACA; background: #FFF5F5; }
    .icon-btn.icon-btn-danger:hover { background: #FEE2E2; border-color: #EF4444; }
    .icon-btn.icon-btn-primary { color: var(--primary); border-color: #BFDBFE; background: #EFF6FF; }
    .icon-btn.icon-btn-primary:hover { background: #DBEAFE; border-color: var(--primary); }
    .icon-btn[title]:hover::after {
        content: attr(title); position: absolute; bottom: calc(100% + 6px); left: 50%;
        transform: translateX(-50%); background: #111827; color: #fff;
        font-size: 11px; padding: 3px 7px; border-radius: 4px;
        white-space: nowrap; pointer-events: none; z-index: 100;
    }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="check-header-bar">
    <div>
        <h1 style="font-size:18px; font-weight:700; margin-bottom:4px;">Pengecekan Harian</h1>
        <div class="check-date-badge">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            {{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        {{-- Search --}}
        <div class="search-input-wrap" style="min-width:200px; max-width:280px;">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="daily-check-search" class="search-input" placeholder="Cari toko, perangkat..." autocomplete="off" oninput="filterDailyCheck(this.value)">
            <button type="button" id="daily-check-search-clear" class="search-clear-btn" style="display:none; background:none; border:none; cursor:pointer;" onclick="clearDailyCheckSearch()" title="Hapus pencarian">✕</button>
        </div>
        {{-- Tombol Share Offline (Text) --}}
        <button
            type="button"
            id="btn-share-offline"
            class="btn btn-secondary"
            style="display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600;"
            onclick="shareOfflineText()"
            title="Salin ringkasan offline untuk dibagikan (WhatsApp, dll)"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Share Offline (Text)
        </button>

        {{-- Tombol Laporkan Semua --}}
        <button
            id="btn-report-all"
            class="btn btn-danger"
            style="display:none; align-items:center; gap:6px; font-size:13px;"
            onclick="reportAllOffline()"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Laporkan Semua Offline
            <span id="btn-report-all-count" style="background:rgba(255,255,255,0.25); border-radius:10px; padding:1px 7px; font-size:11.5px; font-weight:700;">0</span>
        </button>
    </div>
</div>

@if($itemsByStore->isEmpty())
{{-- Empty State: No devices --}}
<div class="empty-state">
    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
    <h2>Belum Ada Perangkat Terdaftar</h2>
    @if(Auth::user()->isManager() || Auth::user()->isSuperAdmin())
        <p>Tambahkan toko dan perangkat di Master Data terlebih dahulu sebelum melakukan pengecekan harian.</p>
        <div style="display:flex; gap:10px; justify-content:center;">
            <a href="{{ route('master.stores') }}" class="btn btn-secondary">Kelola Toko</a>
            <a href="{{ route('master.devices') }}" class="btn btn-primary">Kelola Perangkat</a>
        </div>
    @else
        <p style="font-size:13px; color:var(--muted); margin-top:8px;">Silakan hubungi Manager untuk mendaftarkan toko dan perangkat.</p>
    @endif
</div>

@else
{{-- Items grouped by store --}}
@foreach($itemsByStore as $storeId => $items)
@php
    $store      = $stores[$storeId] ?? null;
    $offlineItems = $items->filter(fn($i) => $i->is_offline);
    $offlineCount = $offlineItems->count();
@endphp
<div class="store-group" id="store-group-{{ $storeId }}" data-store-name="{{ strtolower(($store?->name ?? '') . ' ' . ($store?->branch ?? '')) }}">
    <div class="store-group-header" onclick="toggleGroup({{ $storeId }})">
        <div class="store-group-title">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
            {{ $store?->name ?? 'Toko tidak dikenal' }}
            @if($store?->branch)
                <span class="store-group-meta">— {{ $store->branch }}</span>
            @endif
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <span class="offline-count-badge {{ $offlineCount === 0 ? 'zero' : '' }}" id="offline-badge-{{ $storeId }}">
                {{ $offlineCount === 0 ? '✓ Semua OK' : "{$offlineCount} Offline" }}
            </span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="chevron-{{ $storeId }}" style="transition:transform 200ms; transform:rotate(-90deg);"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
    </div>

    <div id="store-body-{{ $storeId }}" style="display:none;">
        <table class="device-list">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>Perangkat</th>
                    <th>Tipe</th>
                    <th>Model / SN</th>
                    <th style="text-align:center;">Status</th>
                    <th>Hasil</th>
                </tr>
            </thead>
            <tbody>
            @foreach($items as $item)
            @php
                $device = $item->device;
                $isOffline = $item->is_offline;
                $openTicket = $device->tickets->first(fn($t) => $t->status !== 'Closed');
                $hasOpenTicket = (bool) $openTicket;
            @endphp
            <tr class="device-row {{ $isOffline ? 'is-offline' : '' }}" id="row-{{ $item->id }}" data-device-search="{{ strtolower(($store?->name ?? '') . ' ' . ($store?->branch ?? '') . ' ' . $device->type . ' ' . ($device->alias ?? '') . ' ' . ($device->model ?? '') . ' ' . ($device->serial_number ?? '')) }}">
                <td>
                    <input type="checkbox"
                        class="offline-toggle"
                        data-item="{{ $item->id }}"
                        data-store="{{ $storeId }}"
                        data-store-name="{{ $store?->name ?? '' }}"
                        data-store-branch="{{ $store?->branch ?? '' }}"
                        data-device-type="{{ strtolower(trim($device->type ?? '')) }}"
                        data-device-alias="{{ $device->alias ?? '' }}"
                        data-device-model="{{ $device->model ?? '' }}"
                        data-notes="{{ $item->notes ?? '' }}"
                        data-channels="{{ $item->affected_channels ?? '' }}"
                        data-result="{{ $item->result ?? '' }}"
                        data-has-open-ticket="{{ $hasOpenTicket ? '1' : '0' }}"
                        {{ $isOffline ? 'checked' : '' }}
                        {{ $hasOpenTicket ? 'disabled' : '' }}
                        onchange="toggleOffline(this)"
                        style="accent-color: var(--danger); width:16px; height:16px; {{ $hasOpenTicket ? 'cursor:not-allowed; opacity:0.8;' : 'cursor:pointer;' }}"
                        title="{{ $hasOpenTicket ? 'Perangkat memiliki tiket aktif (#' . $openTicket->nomor_tiket . ') yang belum Closed' : 'Centang jika device offline' }}">
                </td>
                <td>
                    <div style="font-weight:600;">{{ $device->alias ?: $device->type }}</div>
                    @if($device->alias)
                    <div style="font-size:11px; color:var(--muted);">{{ $device->type }}</div>
                    @endif
                </td>
                <td><span class="badge {{ $device->type_badge_class }}" style="font-size:11px;">{{ $device->type }}</span></td>
                <td style="font-size:12px; color:var(--muted);">
                    {{ $device->model ?: '—' }}
                    @if($device->serial_number)
                    <div style="font-size:10.5px;">SN: {{ $device->serial_number }}</div>
                    @endif
                </td>
                <td style="text-align:center;">
                    <span id="status-badge-{{ $item->id }}" class="status-dot {{ $isOffline ? 'status-offline' : 'status-ok' }}" title="{{ $hasOpenTicket ? 'Offline (Tiket Aktif #' . $openTicket->nomor_tiket . ')' : ($isOffline ? 'Offline' : 'OK') }}"></span>
                </td>
                <td>
                    @if($hasOpenTicket)
                        <a href="{{ route('tickets.show', $openTicket->id) }}" target="_blank" class="badge pill-ticket_created" style="text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-weight:600;" title="Lihat detail tiket #{{ $openTicket->nomor_tiket }}">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            #{{ $openTicket->nomor_tiket }}
                        </a>
                    @elseif($item->result)
                        <span class="badge pill-{{ $item->result }}" id="result-pill-{{ $item->id }}">{{ $item->resultLabel() }}</span>
                    @else
                        <span style="color:var(--muted); font-size:12px;" id="result-pill-{{ $item->id }}">—</span>
                    @endif
                </td>
            </tr>

            {{-- Report Offline Row --}}
            <tr id="result-action-row-{{ $item->id }}">
                <td colspan="6" style="padding:0; border:none;">
                    <div class="result-row {{ $isOffline ? 'visible' : '' }}" id="result-actions-{{ $item->id }}" style="padding:10px 18px 12px 48px;">
                        @if($hasOpenTicket)
                            <div style="display:inline-flex; align-items:center; gap:8px; background:#EFF6FF; border:1px solid #BFDBFE; border-radius:6px; padding:6px 12px; font-size:12px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span style="color:#1E40AF;">
                                    Tiket aktif: <a href="{{ route('tickets.show', $openTicket->id) }}" target="_blank" style="font-weight:700; color:#1D4ED8; text-decoration:underline;">#{{ $openTicket->nomor_tiket }}</a>
                                </span>
                                <span class="badge {{ $openTicket->statusBadgeClass() }}" style="font-size:10.5px;">{{ $openTicket->status }}</span>
                                <span style="color:#64748B; font-size:11.5px;">(Tidak dapat membuat tiket baru sampai tiket ini Closed)</span>
                            </div>
                        @elseif(!$item->result)
                            <button
                                class="btn btn-danger btn-sm"
                                style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;padding:6px 14px;"
                                onclick="reportOffline({{ $item->id }})"
                            >
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                Laporkan Offline
                            </button>
                        @else
                            <span style="font-size:12px; color:var(--muted);">
                                Sudah ditandai: <strong>{{ $item->resultLabel() }}</strong>
                            </span>
                            <button class="btn btn-ghost btn-sm" style="font-size:11.5px;" onclick="resolveItem({{ $item->id }}, null)">Reset</button>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

<div id="no-daily-results" style="display:none; background:#fff; border:1px solid var(--border); border-radius:8px; text-align:center; padding:48px 20px; color:var(--muted); margin-bottom:16px;">
    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px; display:block; opacity:0.4;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <div style="font-weight:600; color:var(--text); margin-bottom:4px;">Tidak ada toko atau perangkat yang cocok</div>
    <div style="font-size:13px; margin-bottom:14px;">Pencarian untuk "<strong id="daily-searched-term"></strong>" tidak menemukan hasil.</div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="clearDailyCheckSearch()">Reset Pencarian</button>
</div>



@endif

@endsection

@push('scripts')
<script>
// ── State ────────────────────────────────────────
const CSRF = document.querySelector('meta[name=csrf-token]').content;

// ── Group toggle ──────────────────────────────────
function toggleGroup(storeId) {
    const body    = document.getElementById('store-body-' + storeId);
    const chevron = document.getElementById('chevron-' + storeId);
    const isHidden = body.style.display === 'none';
    body.style.display = isHidden ? '' : 'none';
    chevron.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(-90deg)';
}

// ── Search & Filter ───────────────────────────────
function filterDailyCheck(term) {
    const raw = term;
    const q = raw.trim().toLowerCase();
    const clearBtn = document.getElementById('daily-check-search-clear');
    const resetBtn = document.getElementById('btn-reset-daily-search');
    const noResults = document.getElementById('no-daily-results');
    const groups = document.querySelectorAll('.store-group');

    if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';
    if (resetBtn) resetBtn.style.display = q ? 'inline-flex' : 'none';

    let visibleGroupCount = 0;

    groups.forEach(group => {
        const storeName = group.getAttribute('data-store-name') || '';
        const storeId = group.id.replace('store-group-', '');
        const body = document.getElementById('store-body-' + storeId);
        const chevron = document.getElementById('chevron-' + storeId);
        const deviceRows = group.querySelectorAll('.device-row');

        if (!q) {
            // Reset to default (all visible, but dropdowns closed)
            group.style.display = '';
            deviceRows.forEach(row => row.style.display = '');
            if (body) body.style.display = 'none';
            if (chevron) chevron.style.transform = 'rotate(-90deg)';
            visibleGroupCount++;
        } else {
            const storeMatches = storeName.includes(q);
            let matchingDeviceCount = 0;

            deviceRows.forEach(row => {
                const searchData = row.getAttribute('data-device-search') || '';
                if (storeMatches || searchData.includes(q)) {
                    row.style.display = '';
                    matchingDeviceCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (storeMatches || matchingDeviceCount > 0) {
                group.style.display = '';
                // Auto-expand group to reveal matching items
                if (body) body.style.display = '';
                if (chevron) chevron.style.transform = 'rotate(0deg)';
                visibleGroupCount++;
            } else {
                group.style.display = 'none';
            }
        }
    });

    if (noResults) {
        if (q && visibleGroupCount === 0) {
            noResults.style.display = 'block';
            const termEl = document.getElementById('daily-searched-term');
            if (termEl) termEl.textContent = raw.trim();
        } else {
            noResults.style.display = 'none';
        }
    }
}

function clearDailyCheckSearch() {
    const input = document.getElementById('daily-check-search');
    if (input) {
        input.value = '';
    }
    filterDailyCheck('');
    if (input) input.focus();
}

// ── Offline toggle (auto-save langsung) ──────────
function toggleOffline(cb) {
    const id      = cb.dataset.item;
    const storeId = cb.dataset.store;
    const row     = document.getElementById('row-' + id);
    const actions = document.getElementById('result-actions-' + id);
    const badge   = document.getElementById('status-badge-' + id);

    if (cb.checked) {
        row.classList.add('is-offline');
        actions.classList.add('visible');
        badge.className = 'status-dot status-offline';
        badge.setAttribute('title', 'Offline');
    } else {
        row.classList.remove('is-offline');
        actions.classList.remove('visible');
        badge.className = 'status-dot status-ok';
        badge.setAttribute('title', 'OK');
    }
    updateOfflineBadge(storeId);
    updateReportAllBtn();

    // Auto-save langsung tanpa tombol
    fetch('{{ route("daily-check.save-items") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ items: [{ id: parseInt(id), is_offline: cb.checked }] }),
    });
}

function updateOfflineBadge(storeId) {
    const checkboxes = document.querySelectorAll(`.offline-toggle[data-store="${storeId}"]`);
    const count = Array.from(checkboxes).filter(c => c.checked).length;
    const badge = document.getElementById('offline-badge-' + storeId);
    if (badge) {
        badge.textContent = count === 0 ? '✓ Semua OK' : count + ' Offline';
        badge.className = 'offline-count-badge ' + (count === 0 ? 'zero' : '');
    }
}

// ── Tombol Laporkan / Buat Tiket Semua Offline ────
function updateReportAllBtn() {
    const checkboxes = document.querySelectorAll('.offline-toggle');
    // Device offline yang belum dibuatkan tiket dan TIDAK memiliki tiket aktif yang belum Closed
    const pending = Array.from(checkboxes).filter(c => c.checked && c.dataset.result !== 'ticket_created' && c.dataset.hasOpenTicket !== '1');
    const btn = document.getElementById('btn-report-all');
    const countSpan = document.getElementById('btn-report-all-count');

    if (!btn) return;

    if (pending.length > 0) {
        btn.style.display = 'inline-flex';
        if (countSpan) countSpan.textContent = pending.length;
    } else {
        btn.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', updateReportAllBtn);

// ── Resolve Item ──────────────────────────────────
async function resolveItem(itemId, result) {
    if (!result) {
        // Reset
        const res = await fetch(`{{ url('/daily-check/items') }}/${itemId}/resolve`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ result: 'fixed', notes: '' }),
        });
        location.reload(); return;
    }

    // Konfirmasi
    const labels = { fixed: 'Diperbaiki Cepat (tanpa tiket)', no_fault: 'Bukan Kerusakan' };
    const confirm = await Swal.fire({
        title: 'Tandai sebagai?',
        text: labels[result],
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Tandai',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#16A34A',
    });
    if (!confirm.isConfirmed) return;

    const res = await fetch(`{{ url('/daily-check/items') }}/${itemId}/resolve`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ result }),
    });
    const json = await res.json();
    if (json.success) {
        const pill = document.getElementById('result-pill-' + itemId);
        if (pill) {
            pill.className = 'badge pill-' + result;
            pill.textContent = result === 'fixed' ? 'Diperbaiki Cepat' : 'Bukan Kerusakan';
        }
        const cb = document.querySelector(`.offline-toggle[data-item="${itemId}"]`);
        if (cb) cb.dataset.result = result;
        updateReportAllBtn();
        Swal.fire({ icon: 'success', title: 'Berhasil', timer: 1500, showConfirmButton: false });
    }
}

// ── Laporkan Offline (1 Perangkat via Form Tiket) ─
async function reportOffline(itemId) {
    const nowISO = new Date().toISOString().slice(0, 16);
    await fetch('{{ route("daily-check.save-items") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({
            items: [{ id: itemId, is_offline: true, offline_since: nowISO }]
        }),
    });

    const confirm = await Swal.fire({
        title: 'Laporkan Offline?',
        text: 'Anda akan dialihkan ke form pembuatan tiket untuk perangkat ini.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#DC2626',
    });
    if (!confirm.isConfirmed) return;

    const res = await fetch('{{ route("daily-check.create-ticket") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({
            item_ids: [itemId],
            tanggal_kejadian: '{{ $today }}',
        }),
    });
    const json = await res.json();
    if (json.success) {
        window.location.href = json.redirect_url;
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
    }
}

// ── Buat Tiket Semua Offline Sekaligus ────────────
async function reportAllOffline() {
    const checkboxes = document.querySelectorAll('.offline-toggle');
    const pendingOffline = Array.from(checkboxes).filter(cb => cb.checked && cb.dataset.result !== 'ticket_created' && cb.dataset.hasOpenTicket !== '1');

    if (pendingOffline.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Tidak Ada Device Offline Baru',
            text: 'Semua device offline sudah memiliki tiket atau tidak ada yang dicentang offline.',
        });
        return;
    }

    const itemIds = pendingOffline.map(cb => parseInt(cb.dataset.item));

    // Hitung jumlah toko terdampak
    const storeMap = {};
    pendingOffline.forEach(cb => {
        const storeId = cb.dataset.store;
        storeMap[storeId] = (storeMap[storeId] || 0) + 1;
    });
    const storeCount = Object.keys(storeMap).length;

    const confirm = await Swal.fire({
        title: 'Buat Tiket Semua Offline?',
        html: `Ditemukan <b>${pendingOffline.length} perangkat offline</b> di <b>${storeCount} toko</b>.<br><br>Sistem akan otomatis membuat tiket untuk masing-masing toko secara langsung.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Buat Semua Tiket',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#DC2626',
    });
    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Sedang Membuat Tiket...',
        text: 'Mohon tunggu beberapa saat...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
    });

    try {
        const res = await fetch('{{ route("daily-check.batch-create-tickets") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({
                item_ids: itemIds,
                tanggal_kejadian: '{{ $today }}',
            }),
        });
        const json = await res.json();

        if (json.success) {
            let listHtml = '<div style="text-align:left; max-height:220px; overflow-y:auto; margin-top:10px; font-size:13px; border:1px solid #E5E7EB; border-radius:6px; padding:6px 10px;">';
            json.tickets.forEach(t => {
                listHtml += `<div style="padding:6px 0; border-bottom:1px solid #F3F4F6; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <strong style="color:#2563EB;">${t.nomor_tiket}</strong>
                        <div style="font-size:11.5px; color:#6B7280;">${t.store_name}</div>
                    </div>
                    <span style="font-size:11px; background:#FEE2E2; color:#DC2626; padding:2px 8px; border-radius:10px; font-weight:600;">${t.count} device</span>
                </div>`;
            });
            listHtml += '</div>';

            await Swal.fire({
                icon: 'success',
                title: 'Tiket Berhasil Dibuat!',
                html: `${json.message}<br>${listHtml}`,
                confirmButtonText: 'Selesai & Muat Ulang',
                confirmButtonColor: '#16A34A',
            });
            window.location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: json.message || 'Terjadi kesalahan sistem.' });
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan atau server.' });
    }
}

// ── Format Helper untuk Lokasi & Toko ─────────────
function formatLocationTitle(str) {
    if (!str) return '';
    const acronyms = ['TP', 'LW', 'SCH', 'JCM', 'BIP', 'MOG', 'TSM', 'SDA', 'GRAGE', 'CIWALK'];
    return str.trim().split(/[\s_-]+/).map(w => {
        const up = w.toUpperCase();
        if (acronyms.includes(up)) return up;
        return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase();
    }).join(' ');
}

// ── Share Offline (Text) ─────────────────────────
function shareOfflineText() {
    const checkboxes = Array.from(document.querySelectorAll('.offline-toggle'));
    const offlineCheckboxes = checkboxes.filter(cb => cb.checked);

    const dateStr = '{{ \Carbon\Carbon::parse($today)->format("m/d/y") }}';

    const listOffline = [];
    const listCctvStore = [];
    const listCctvMoka = [];
    const listCctvGame = [];

    const pushUnique = (arr, val) => {
        if (val && !arr.includes(val)) arr.push(val);
    };

    offlineCheckboxes.forEach(cb => {
        const type        = (cb.dataset.deviceType || '').toLowerCase();
        const alias       = (cb.dataset.deviceAlias || '').trim();
        const aliasLower  = alias.toLowerCase();
        const storeName   = (cb.dataset.storeName || '').trim();
        const storeBranch = (cb.dataset.storeBranch || '').trim();
        const notes       = (cb.dataset.notes || '').trim();
        const channels    = (cb.dataset.channels || '').trim();

        // 1. CCTV Game
        if (aliasLower.includes('game')) {
            let loc = storeBranch || alias;
            loc = loc.replace(/\b(cctv|games?|channel\s*\d+|kamera|camera)\b/gi, '').trim();
            let locFormatted = loc.split(/[\s_-]+/).map(w => {
                const up = w.toUpperCase();
                if (['TP', 'LW', 'SCH', 'JCM', 'BIP', 'MOG', 'TSM', 'SDA'].includes(up)) return up;
                return w.toLowerCase();
            }).join(' ').trim();
            pushUnique(listCctvGame, `- game ${locFormatted}`);
        }
        // 2. CCTV Moka
        else if (aliasLower.includes('moka') || aliasLower.includes('kasir')) {
            let loc = storeBranch || alias;
            loc = loc.replace(/\b(cctv|moka(pos)?|channel\s*\d+|kamera|camera)\b/gi, '').trim();
            let locFormatted = loc.split(/[\s_-]+/).map(w => {
                const up = w.toUpperCase();
                if (['TP', 'LW', 'SCH', 'JCM', 'BIP', 'MOG', 'TSM', 'SDA'].includes(up)) return up;
                return w.toLowerCase();
            }).join(' ').trim();
            pushUnique(listCctvMoka, `- Moka ${locFormatted}`);
        }
        // 3. NVR / DVR / Modem / Router (List Offline utama)
        else if (['nvr', 'dvr', 'modem', 'router'].includes(type) || aliasLower.includes('modem') || aliasLower.includes('router')) {
            let storeLabel = '';
            const cleanBranch = formatLocationTitle(storeBranch);
            const aliasUpper = alias.toUpperCase();
            const branchUpper = storeBranch.toUpperCase();

            if (aliasUpper.startsWith('HS ') || storeName.toUpperCase().includes('HEAVEN SCENT')) {
                if (['SCH', 'JCM', 'BIP', 'MOG', 'TSM', 'SDA'].includes(branchUpper)) {
                    storeLabel = 'HS ' + branchUpper;
                } else if (!cleanBranch.toUpperCase().startsWith('HS ')) {
                    if (aliasUpper.startsWith('HS ')) {
                        storeLabel = 'HS ' + cleanBranch;
                    } else {
                        storeLabel = cleanBranch || alias;
                    }
                } else {
                    storeLabel = cleanBranch;
                }
            } else {
                storeLabel = cleanBranch || alias;
            }

            let extraNote = '';
            if (notes) {
                extraNote = ` (${notes})`;
            } else if (type === 'modem' || type === 'router' || aliasLower.includes('modem') || aliasLower.includes('simcard')) {
                extraNote = ' (Modem / SIMCARD)';
            }

            pushUnique(listOffline, `- ${storeLabel}${extraNote}`);
        }
        // 4. CCTV Store Offline (regular store camera / channels)
        else {
            let camPart = '';
            if (channels) {
                camPart = channels.toUpperCase().startsWith('CAM') ? `(${channels.toUpperCase()})` : `(CAM ${channels})`;
            } else {
                const m = alias.match(/(?:channel|cam(?:era)?)\s*(\d+)/i);
                if (m) {
                    camPart = `(CAM ${m[1]})`;
                } else {
                    camPart = `(${alias})`;
                }
            }

            let storeLabel = formatLocationTitle(storeBranch) || formatLocationTitle(storeName);
            if (alias.toUpperCase().startsWith('HS ') && !storeLabel.toUpperCase().startsWith('HS ')) {
                storeLabel = 'HS ' + storeLabel;
            }

            pushUnique(listCctvStore, `- ${storeLabel} ${camPart}`);
        }
    });

    const sections = [];

    if (listOffline.length > 0) {
        sections.push(`list offline ${dateStr}\n` + listOffline.join('\n'));
    }
    if (listCctvStore.length > 0) {
        sections.push(`list cctv store offline ${dateStr}\n` + listCctvStore.join('\n'));
    }
    if (listCctvMoka.length > 0) {
        sections.push(`list cctv moka offline ${dateStr}\n` + listCctvMoka.join('\n'));
    }
    if (listCctvGame.length > 0) {
        sections.push(`list cctv game offline ${dateStr}\n` + listCctvGame.join('\n'));
    }

    let fullText = sections.join('\n\n');

    if (!fullText) {
        fullText = `list offline ${dateStr}\n- Semua perangkat online / OK`;
    }

    // Salin ke Clipboard & Tampilkan Konfirmasi
    // navigator.clipboard hanya tersedia di HTTPS atau localhost.
    // Di HTTP biasa, clipboard undefined sehingga perlu fallback manual.
    const showManualCopy = () => {
        Swal.fire({
            title: 'Ringkasan Offline',
            html: `
                <div style="font-size:13px; color:#4B5563; margin-bottom:10px; text-align:left;">
                    Silakan salin teks di bawah ini:
                </div>
                <textarea id="copy-summary-area" style="width:100%; height:240px; font-family:monospace; font-size:12.5px; padding:10px; border:1px solid #D1D5DB; border-radius:6px; background:#F9FAFB; resize:vertical; line-height:1.5;">${fullText}</textarea>
            `,
            icon: 'info',
            confirmButtonText: 'Tutup',
            didOpen: () => {
                const area = document.getElementById('copy-summary-area');
                if (area) {
                    area.select();
                    area.focus();
                }
            }
        });
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(fullText).then(() => {
            Swal.fire({
                title: 'Berhasil Disalin!',
                html: `
                    <div style="font-size:13px; color:#4B5563; margin-bottom:10px; text-align:left;">
                        Teks ringkasan telah disalin ke clipboard dan siap di-paste ke WhatsApp:
                    </div>
                    <textarea id="copy-summary-area" readonly style="width:100%; height:240px; font-family:monospace; font-size:12.5px; padding:10px; border:1px solid #D1D5DB; border-radius:6px; background:#F9FAFB; resize:vertical; line-height:1.5;">${fullText}</textarea>
                `,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Salin Ulang',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#2563EB',
            }).then(result => {
                if (result.isConfirmed) {
                    const area = document.getElementById('copy-summary-area');
                    if (area) {
                        area.select();
                        navigator.clipboard.writeText(area.value).catch(() => {
                            document.execCommand('copy');
                        });
                    }
                }
            });
        }).catch(showManualCopy);
    } else {
        // Fallback untuk HTTP / browser tanpa Clipboard API
        showManualCopy();
    }
}

// Auto-save setiap 60 detik jika ada perubahan
setInterval(() => {
    document.querySelectorAll('.offline-toggle').forEach(cb => {
        fetch('{{ route("daily-check.save-items") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ items: [{ id: parseInt(cb.dataset.item), is_offline: cb.checked }] }),
        });
    });
}, 60000);
</script>
@endpush
