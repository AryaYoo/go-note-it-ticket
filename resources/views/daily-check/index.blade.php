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

{{-- Sticky Save Bar --}}
<div class="check-save-bar" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
    <div>
        <h1 style="font-size:18px; font-weight:700; margin-bottom:2px;">Pengecekan Harian</h1>
        <div class="check-date-badge">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            {{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        {{-- Search Input --}}
        <div class="search-input-wrap" style="min-width:240px; max-width:320px;">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="daily-check-search" class="search-input" placeholder="Cari toko, cabang, perangkat..." autocomplete="off" oninput="filterDailyCheck(this.value)">
            <button type="button" id="daily-check-search-clear" class="search-clear-btn" style="display:none; background:none; border:none; cursor:pointer;" onclick="clearDailyCheckSearch()" title="Hapus pencarian">✕</button>
        </div>

        <button type="button" id="btn-reset-daily-search" class="btn btn-ghost btn-sm" style="display:none; align-items:center; gap:5px; border:1px solid var(--border); color:var(--muted);" onclick="clearDailyCheckSearch()" title="Reset pencarian">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.73"/></svg>
            Reset
        </button>

        <span class="save-status" id="save-status">Siap disimpan</span>
        <button class="btn btn-primary" id="btn-save-all" onclick="saveAll()" title="Simpan Semua">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
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
            @php $device = $item->device; $isOffline = $item->is_offline; @endphp
            <tr class="device-row {{ $isOffline ? 'is-offline' : '' }}" id="row-{{ $item->id }}" data-device-search="{{ strtolower(($store?->name ?? '') . ' ' . ($store?->branch ?? '') . ' ' . $device->type . ' ' . ($device->alias ?? '') . ' ' . ($device->model ?? '') . ' ' . ($device->serial_number ?? '')) }}">
                <td>
                    <input type="checkbox"
                        class="offline-toggle"
                        data-item="{{ $item->id }}"
                        data-store="{{ $storeId }}"
                        {{ $isOffline ? 'checked' : '' }}
                        onchange="toggleOffline(this)"
                        style="accent-color: var(--danger); width:16px; height:16px; cursor:pointer;"
                        title="Centang jika device offline">
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
                    <span id="status-badge-{{ $item->id }}" class="status-dot {{ $isOffline ? 'status-offline' : 'status-ok' }}" title="{{ $isOffline ? 'Offline' : 'OK' }}"></span>
                </td>
                <td>
                    @if($item->result)
                        <span class="badge pill-{{ $item->result }}" id="result-pill-{{ $item->id }}">{{ $item->resultLabel() }}</span>
                    @else
                        <span style="color:var(--muted); font-size:12px;" id="result-pill-{{ $item->id }}">—</span>
                    @endif
                </td>
            </tr>
            {{-- Offline Detail Row --}}
            <tr id="detail-row-{{ $item->id }}">
                <td colspan="6" style="padding:0; border:none;">
                    <div class="offline-detail {{ $isOffline ? 'visible' : '' }}" id="detail-{{ $item->id }}">
                        <div class="offline-detail-grid">
                            <div class="form-group">
                                <label class="form-label" style="font-size:11.5px;">Offline Sejak</label>
                                <input type="datetime-local" class="form-input" style="font-size:12px;"
                                    id="since-{{ $item->id }}"
                                    value="{{ $item->offline_since ? $item->offline_since->format('Y-m-d\TH:i') : '' }}"
                                    onchange="markDirty()">
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="font-size:11.5px;">Kanal Terdampak</label>
                                <input type="text" class="form-input" style="font-size:12px;"
                                    id="channels-{{ $item->id }}"
                                    value="{{ $item->affected_channels ?? '' }}"
                                    placeholder="Contoh: Kanal 1,3,5"
                                    onchange="markDirty()">
                            </div>
                        </div>
                        <div class="form-group" style="margin-top:10px;">
                            <label class="form-label" style="font-size:11.5px;">Catatan</label>
                            <input type="text" class="form-input" style="font-size:12px;"
                                id="notes-{{ $item->id }}"
                                value="{{ $item->notes ?? '' }}"
                                placeholder="Catatan tambahan..."
                                onchange="markDirty()">
                        </div>
                    </div>
                </td>
            </tr>
            {{-- Result Action Row --}}
            <tr id="result-action-row-{{ $item->id }}">
                <td colspan="6" style="padding:0; border:none;">
                    <div class="result-row {{ $isOffline ? 'visible' : '' }}" id="result-actions-{{ $item->id }}">
                        <span class="result-label">Hasil:</span>
                        @if(!$item->result || $item->result === 'ticket_created')
                            <button class="icon-btn icon-btn-success" title="Diperbaiki Cepat" onclick="resolveItem({{ $item->id }}, 'fixed')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            </button>
                            <button class="icon-btn" title="Bukan Kerusakan" style="border:1px solid var(--border);" onclick="resolveItem({{ $item->id }}, 'no_fault')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                            <button class="icon-btn icon-btn-primary" title="Buat Tiket" onclick="addToTicketSelection({{ $item->id }}, {{ $storeId }}, '{{ addslashes($device->alias ?: $device->type) }}')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 5H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9l-4-4z"/><polyline points="15 5 15 9 19 9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="12" y2="17"/></svg>
                            </button>
                        @else
                            <span style="font-size:12px; color:var(--muted);">
                                Sudah ditandai: <strong>{{ $item->resultLabel() }}</strong>
                            </span>
                            <button class="icon-btn" title="Reset Status" onclick="resolveItem({{ $item->id }}, null)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.73"/></svg>
                            </button>
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

{{-- Floating action: Create ticket from selection --}}
<div id="ticket-selection-bar" style="display:none; position:fixed; bottom:24px; left:50%; transform:translateX(-50%); z-index:200; background:#fff; border:1px solid var(--border); border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.12); padding:12px 20px; display:none; align-items:center; gap:14px; min-width:380px;">
    <div>
        <div style="font-size:13px; font-weight:600;" id="selection-label">0 device dipilih</div>
        <div style="font-size:11px; color:var(--muted);" id="selection-store-name"></div>
    </div>
    <button class="btn btn-primary" onclick="proceedCreateTicket()">
        Buat Tiket →
    </button>
    <button class="btn btn-ghost btn-sm" onclick="clearTicketSelection()">Batal</button>
</div>
@endif

{{-- Modal: konfirmasi buat tiket --}}
<div class="modal-backdrop hidden" id="modal-ticket">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Buat Tiket dari Pengecekan Harian</div>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-ticket').classList.add('hidden')">✕</button>
        </div>
        <div class="modal-body">
            <div style="font-size:13px; color:var(--muted); line-height:1.6;">
                Device yang akan dimasukkan ke tiket:
            </div>
            <div id="modal-device-list" style="display:flex; flex-direction:column; gap:8px;"></div>
            <div class="form-group">
                <label class="form-label">Tanggal Kejadian <span class="required">*</span></label>
                <input type="date" id="ticket-date" class="form-input" value="{{ $today }}">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="document.getElementById('modal-ticket').classList.add('hidden')">Batal</button>
            <button class="btn btn-primary" onclick="submitCreateTicket()">Lanjut Buat Tiket →</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── State ────────────────────────────────────────
const CSRF = document.querySelector('meta[name=csrf-token]').content;
let isDirty = false;
let ticketSelection = []; // array of {itemId, storeId, label}
let ticketSelectionStoreId = null;

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

// ── Offline toggle ────────────────────────────────
function toggleOffline(cb) {
    const id      = cb.dataset.item;
    const storeId = cb.dataset.store;
    const row     = document.getElementById('row-' + id);
    const detail  = document.getElementById('detail-' + id);
    const actions = document.getElementById('result-actions-' + id);
    const badge   = document.getElementById('status-badge-' + id);

    if (cb.checked) {
        row.classList.add('is-offline');
        detail.classList.add('visible');
        actions.classList.add('visible');
        badge.className = 'status-dot status-offline';
        badge.setAttribute('title', 'Offline');
        badge.innerHTML = '';
    } else {
        row.classList.remove('is-offline');
        detail.classList.remove('visible');
        actions.classList.remove('visible');
        badge.className = 'status-dot status-ok';
        badge.setAttribute('title', 'OK');
        badge.innerHTML = '';
    }
    updateOfflineBadge(storeId);
    markDirty();
}

function updateOfflineBadge(storeId) {
    const checkboxes = document.querySelectorAll(`.offline-toggle[data-store="${storeId}"]`);
    const count = Array.from(checkboxes).filter(c => c.checked).length;
    const badge = document.getElementById('offline-badge-' + storeId);
    badge.textContent = count === 0 ? '✓ Semua OK' : count + ' Offline';
    badge.className = 'offline-count-badge ' + (count === 0 ? 'zero' : '');
}

// ── Dirty / Save ──────────────────────────────────
function markDirty() {
    isDirty = true;
    const s = document.getElementById('save-status');
    s.textContent = 'Ada perubahan belum disimpan';
    s.className = 'save-status saving';
}

async function saveAll() {
    const btn = document.getElementById('btn-save-all');
    const statusEl = document.getElementById('save-status');
    btn.disabled = true;
    statusEl.textContent = 'Menyimpan...';
    statusEl.className = 'save-status saving';

    // Kumpulkan semua item
    const items = [];
    document.querySelectorAll('.offline-toggle').forEach(cb => {
        const id = cb.dataset.item;
        items.push({
            id:               parseInt(id),
            is_offline:       cb.checked,
            offline_since:    document.getElementById('since-' + id)?.value || null,
            affected_channels:document.getElementById('channels-' + id)?.value || null,
            notes:            document.getElementById('notes-' + id)?.value || null,
        });
    });

    try {
        const res = await fetch('{{ route("daily-check.save-items") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ items }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        isDirty = false;
        statusEl.textContent = '✓ Tersimpan';
        statusEl.className = 'save-status saved';
        setTimeout(() => { statusEl.textContent = 'Siap disimpan'; statusEl.className = 'save-status'; }, 2500);
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: err.message });
        statusEl.textContent = 'Gagal menyimpan';
        statusEl.className = 'save-status';
    } finally {
        btn.disabled = false;
    }
}

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
        Swal.fire({ icon: 'success', title: 'Berhasil', timer: 1500, showConfirmButton: false });
    }
}

// ── Ticket Selection ──────────────────────────────
function addToTicketSelection(itemId, storeId, label) {
    // Validasi: harus toko yang sama
    if (ticketSelectionStoreId && ticketSelectionStoreId !== storeId) {
        Swal.fire({
            icon: 'warning',
            title: 'Beda Toko',
            text: 'Hanya device dari toko yang sama yang bisa digabung dalam satu tiket.',
        });
        return;
    }

    // Toggle selection
    const existing = ticketSelection.findIndex(i => i.itemId === itemId);
    if (existing >= 0) {
        ticketSelection.splice(existing, 1);
    } else {
        ticketSelection.push({ itemId, storeId, label });
        ticketSelectionStoreId = storeId;
    }

    if (ticketSelection.length === 0) {
        ticketSelectionStoreId = null;
    }

    // Langsung buka modal jika ada selection
    if (ticketSelection.length > 0) {
        openTicketModal();
    }
}

function openTicketModal() {
    const list = document.getElementById('modal-device-list');
    list.innerHTML = '';
    ticketSelection.forEach(sel => {
        const div = document.createElement('div');
        div.className = 'device-check-item selected';
        div.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span style="font-size:13px; font-weight:500;">${sel.label}</span>`;
        list.appendChild(div);
    });
    document.getElementById('modal-ticket').classList.remove('hidden');
}

function clearTicketSelection() {
    ticketSelection = [];
    ticketSelectionStoreId = null;
}

async function proceedCreateTicket() {
    openTicketModal();
}

async function submitCreateTicket() {
    const date = document.getElementById('ticket-date').value;
    if (!date) {
        Swal.fire({ icon: 'warning', title: 'Tanggal wajib diisi', timer: 1800, showConfirmButton: false });
        return;
    }

    const res = await fetch('{{ route("daily-check.create-ticket") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({
            item_ids: ticketSelection.map(s => s.itemId),
            tanggal_kejadian: date,
        }),
    });

    const json = await res.json();
    if (json.success) {
        document.getElementById('modal-ticket').classList.add('hidden');
        window.location.href = json.redirect_url;
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
    }
}

// Auto-save setiap 60 detik jika ada perubahan
setInterval(() => { if (isDirty) saveAll(); }, 60000);

// Warn sebelum tutup halaman
window.addEventListener('beforeunload', e => {
    if (isDirty) { e.preventDefault(); e.returnValue = ''; }
});
</script>
@endpush
