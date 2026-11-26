@extends('layouts.app')
@section('title', 'Riwayat Tiket')
@section('page-title', 'Riwayat Tiket')

@section('content')
<div class="page-header">
    <div>
        <h1>Riwayat Tiket</h1>
        <div class="page-header-sub">{{ $tickets->total() }} tiket ditemukan</div>
    </div>
    <div class="flex gap-8">
        <button
            type="button"
            id="btn-share-ticket-text"
            class="btn btn-secondary btn-sm"
            style="display:inline-flex; align-items:center; gap:6px;"
            onclick="shareTicketText()"
            title="Salin ringkasan tiket (per tanggal / semua open ticket) ke clipboard"
        >
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Share Text
        </button>
        <a href="{{ route('tickets.export', request()->query()) }}" class="btn btn-success btn-sm" id="btn-export" onclick="return confirmExport({{ $tickets->total() }})">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Ekspor Excel
        </a>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('modal-import').style.display='flex'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Import Excel
        </button>
    </div>
</div>

@php
    $filterKeys = ['status', 'kategori', 'prioritas', 'date_from', 'date_to', 'exclude'];
    $activeFilterCount = collect($filterKeys)->filter(fn($k) => request()->filled($k))->count();
@endphp

{{-- Action & Filter Bar --}}
<div class="tickets-action-bar">
    <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-create-ticket">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Buat Tiket
    </a>

    <form method="GET" action="{{ route('tickets.index') }}" id="filter-form" class="filter-toolbar">
        {{-- Search Input with icon --}}
        <div class="search-input-wrap">
            <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" class="search-input" placeholder="Cari tiket, pelapor, divisi..." value="{{ request('search') }}">
            @if(request('search'))
                <a href="{{ route('tickets.index', request()->except('search')) }}" class="search-clear-btn" title="Hapus pencarian">✕</a>
            @endif
        </div>

        {{-- Filter Popup Trigger Button --}}
        <button type="button" class="btn btn-secondary btn-filter-toggle {{ $activeFilterCount > 0 ? 'active' : '' }}" onclick="openFilterModal()" title="Buka pengaturan filter">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span>Filter</span>
            @if($activeFilterCount > 0)
                <span class="filter-badge">{{ $activeFilterCount }}</span>
            @endif
        </button>

        @if(request()->hasAny(['search', 'status', 'kategori', 'prioritas', 'date_from', 'date_to', 'exclude']))
            <a href="{{ route('tickets.index') }}" class="btn btn-ghost btn-sm" title="Reset semua pencarian & filter">Reset</a>
        @endif

        {{-- Filter Settings Pop Up Modal --}}
        <div class="filter-modal-backdrop" id="filter-modal" onmousedown="filterMouseDownTarget = event.target" onmouseup="handleFilterMouseUp(event)">
            <div class="filter-modal-content" onclick="event.stopPropagation()">
                <div class="filter-modal-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div class="filter-modal-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        </div>
                        <div>
                            <div class="filter-modal-title">Pengaturan Filter</div>
                            <div class="filter-modal-subtitle">Saring daftar riwayat tiket berdasarkan kriteria</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close-modal" onclick="closeFilterModal()" title="Tutup">✕</button>
                </div>

                <div class="filter-modal-body">
                    <div class="form-group">
                        <label class="form-label">Status Tiket</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            @foreach(['Open','Closed','Selesai','Eskalasi'] as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label">Kategori</label>
                            <select name="kategori" class="form-select">
                                <option value="">Semua Kategori</option>
                                @foreach(['POS','Akun','Device','Laptop','CCTV','NVR','Email','Lainnya'] as $k)
                                <option value="{{ $k }}" {{ request('kategori') == $k ? 'selected' : '' }}>{{ $k }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prioritas</label>
                            <select name="prioritas" class="form-select">
                                <option value="">Semua Prioritas</option>
                                @foreach(['Low','Medium','High'] as $p)
                                <option value="{{ $p }}" {{ request('prioritas') == $p ? 'selected' : '' }}>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rentang Tanggal Kejadian</label>
                        <div class="form-row form-row-2">
                            <div>
                                <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}" title="Dari tanggal">
                                <div class="form-hint">Dari tanggal</div>
                            </div>
                            <div>
                                <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}" title="Sampai tanggal">
                                <div class="form-hint">Sampai tanggal</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Kecualikan Kata (Exclude)</label>
                        <input type="text" name="exclude" class="form-input" placeholder="Contoh: CCTV, Moka (pisahkan koma)" value="{{ request('exclude') }}">
                        <div class="form-hint">Tiket yang mengandung kata ini akan dikecualikan dari hasil</div>
                    </div>
                </div>

                <div class="filter-modal-footer">
                    @if($activeFilterCount > 0)
                        <a href="{{ route('tickets.index', request()->only('search')) }}" class="btn btn-ghost btn-sm" style="color: var(--danger);">Reset Filter</a>
                    @else
                        <div></div>
                    @endif
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="closeFilterModal()">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Terapkan Filter</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Active Filter Pills --}}
@if($activeFilterCount > 0)
<div style="display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 14px; font-size: 12px;">
    <span style="color: var(--muted); font-weight: 500;">Filter aktif:</span>
    @if(request('status'))
        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 12px; background: #EFF6FF; color: var(--primary); font-weight: 500;">
            Status: {{ request('status') }}
            <a href="{{ route('tickets.index', request()->except('status')) }}" style="text-decoration: none; color: inherit; font-size: 11px; margin-left: 2px;" title="Hapus filter status">✕</a>
        </span>
    @endif
    @if(request('kategori'))
        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 12px; background: #EFF6FF; color: var(--primary); font-weight: 500;">
            Kategori: {{ request('kategori') }}
            <a href="{{ route('tickets.index', request()->except('kategori')) }}" style="text-decoration: none; color: inherit; font-size: 11px; margin-left: 2px;" title="Hapus filter kategori">✕</a>
        </span>
    @endif
    @if(request('prioritas'))
        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 12px; background: #EFF6FF; color: var(--primary); font-weight: 500;">
            Prioritas: {{ request('prioritas') }}
            <a href="{{ route('tickets.index', request()->except('prioritas')) }}" style="text-decoration: none; color: inherit; font-size: 11px; margin-left: 2px;" title="Hapus filter prioritas">✕</a>
        </span>
    @endif
    @if(request('date_from') || request('date_to'))
        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 12px; background: #EFF6FF; color: var(--primary); font-weight: 500;">
            Tanggal: {{ request('date_from') ?: '...' }} s/d {{ request('date_to') ?: '...' }}
            <a href="{{ route('tickets.index', request()->except(['date_from', 'date_to'])) }}" style="text-decoration: none; color: inherit; font-size: 11px; margin-left: 2px;" title="Hapus filter tanggal">✕</a>
        </span>
    @endif
    @if(request('exclude'))
        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 12px; background: #FEF2F2; color: #DC2626; border: 1px solid #FEE2E2; font-weight: 500;">
            Kecualikan: "{{ request('exclude') }}"
            <a href="{{ route('tickets.index', request()->except('exclude')) }}" style="text-decoration: none; color: #DC2626; font-size: 11px; margin-left: 2px;" title="Hapus filter kecualikan">✕</a>
        </span>
    @endif
    <a href="{{ route('tickets.index', request()->only('search')) }}" style="color: var(--muted); text-decoration: underline; font-size: 11.5px; margin-left: 4px;">Reset</a>
</div>
@endif

{{-- Table --}}
@if($tickets->isEmpty())
<div class="card">
    <div class="empty-state">
        <div class="empty-state-icon">🎫</div>
        <div class="empty-state-text">Tidak ada tiket yang sesuai filter.</div>
    </div>
</div>
@else
<div style="display: flex; flex-direction: column; gap: 12px;">
    @foreach($tickets as $ticket)
    <div class="card" onclick="window.location.href='{{ route('tickets.show', $ticket) }}'" style="padding: 18px; display: flex; justify-content: space-between; align-items: center; gap: 16px; transition: all 0.2s; border-left: 4px solid var(--primary); cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'" onmouseout="this.style.transform='none'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.04)'">
        <!-- Info Kiri -->
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                <span class="ticket-no" style="font-size: 15px;">{{ $ticket->nomor_tiket }}</span>
                <span class="badge {{ $ticket->statusBadgeClass() }}">{{ $ticket->status }}</span>
                @if($ticket->prioritas)
                <span class="badge {{ $ticket->prioritasBadgeClass() }}">{{ $ticket->prioritas }}</span>
                @endif
                @if($ticket->kategori)
                <span class="badge badge-{{ strtolower($ticket->kategori) }}">{{ $ticket->kategori }}</span>
                @endif
            </div>
            
            <div style="font-size: 13.5px; color: var(--muted); margin-bottom: 6px;">
                Dilaporkan oleh <strong style="color: var(--text);">{{ $ticket->nama_pelapor }}</strong> ({{ $ticket->divisi_toko }}) 
                pada <strong>{{ $ticket->tanggal_kejadian->format('d/m/Y') }}</strong>
            </div>
            
            <div style="font-size: 12.5px; color: var(--muted);">
                <strong>PIC:</strong> <span style="color: var(--text);">{{ $ticket->pic ?? 'Belum Ditugaskan' }}</span>
                <span style="margin: 0 10px; color: var(--border);">|</span>
                <strong>Dibuat:</strong> {{ $ticket->created_at->format('d/m/Y H:i') }}
            </div>

            @if($ticket->catatan)
            <div class="ticket-note-preview" onclick="event.stopPropagation(); openNoteModal({{ $ticket->id }}, '{{ $ticket->nomor_tiket }}', {{ json_encode($ticket->catatan) }})" style="margin-top: 8px; padding: 6px 10px; background: #FFFBEB; border: 1px solid #FDE68A; border-left: 3px solid #F59E0B; border-radius: 6px; font-size: 12.5px; color: #92400E; display: flex; align-items: flex-start; gap: 8px; cursor: pointer; transition: background 0.15s;" title="Klik untuk mengubah catatan">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2.2" style="flex-shrink:0; margin-top:2px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L16.5 3.5z"/></svg>
                <div style="flex: 1; word-break: break-word; line-height: 1.45;">
                    <strong style="color: #B45309; margin-right: 4px;">Notes:</strong>{{ $ticket->catatan }}
                </div>
                <span style="color: #D97706; font-size: 11px; font-weight: 600; text-decoration: underline; margin-left: 4px; flex-shrink: 0;">Ubah</span>
            </div>
            @endif
        </div>

        <!-- Aksi Kanan -->
        <div class="actions" style="display: flex; gap: 8px;">
            @if(!in_array($ticket->status, ['Closed', 'Selesai']))
            <button
                class="btn btn-ghost btn-sm"
                title="Tandai Selesai / Closed"
                onclick="event.stopPropagation(); closeTicket({{ $ticket->id }}, '{{ $ticket->nomor_tiket }}', {{ json_encode(['tindakan' => $ticket->tindakan_dilakukan, 'solusi' => $ticket->solusi_diberikan, 'rootCause' => $ticket->root_cause, 'tanggalPenyelesaian' => $ticket->tanggal_penyelesaian?->format('Y-m-d'), 'catatan' => $ticket->catatan]) }})"
                style="color: #16A34A; border-color: #BBF7D0; background: #F0FDF4;"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
            @endif
            <button
                class="btn btn-ghost btn-sm"
                title="{{ $ticket->catatan ? 'Ubah Catatan Tiket' : 'Tambah Catatan Tiket' }}"
                onclick="event.stopPropagation(); openNoteModal({{ $ticket->id }}, '{{ $ticket->nomor_tiket }}', {{ json_encode($ticket->catatan ?? '') }})"
                style="color: #D97706; border-color: #FDE68A; background: {{ $ticket->catatan ? '#FEF3C7' : '#FFFBEB' }};"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10,9 9,9 8,9"/></svg>
            </button>
            <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-ghost btn-sm" title="Edit Tiket" onclick="event.stopPropagation()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
            <button class="btn btn-ghost btn-sm" title="Hapus Tiket" onclick="event.stopPropagation(); deleteTicket({{ $ticket->id }}, '{{ $ticket->nomor_tiket }}')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
            </button>
        </div>
    </div>
    @endforeach
</div>

{{-- Pagination --}}
<div class="pagination-wrap mt-8">
    {{ $tickets->links('vendor.pagination.simple-flat') }}
</div>
@endif

{{-- Floating Scroll To Top Button --}}
<button id="btn-scroll-top" class="btn-scroll-top" title="Kembali ke atas" aria-label="Scroll to top">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 15l-6-6-6 6"/>
    </svg>
</button>

@include('tickets.partials.modal-close')
@include('tickets.partials.modal-note')
@endsection

@push('scripts')
<script>
// Simpan URL index (beserta filter/search) ke sessionStorage
// agar tombol Back di halaman detail bisa kembali ke filter yang sama.
sessionStorage.setItem('tickets_list_url', window.location.href);

// Filter Modal Control
function openFilterModal() {
    const modal = document.getElementById('filter-modal');
    if (modal) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeFilterModal() {
    const modal = document.getElementById('filter-modal');
    if (modal) {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
}

let filterMouseDownTarget = null;
function handleFilterMouseUp(e) {
    if (filterMouseDownTarget === e.target && e.target.id === 'filter-modal') {
        closeFilterModal();
    }
    filterMouseDownTarget = null;
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeFilterModal();
    }
});

// Floating Scroll To Top
const btnScrollTop = document.getElementById('btn-scroll-top');
if (btnScrollTop) {
    const handleScroll = () => {
        if (window.scrollY > 200 || document.documentElement.scrollTop > 200) {
            btnScrollTop.classList.add('visible');
        } else {
            btnScrollTop.classList.remove('visible');
        }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });

    btnScrollTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

function confirmExport(total) {
    if (total === 0) {
        Swal.fire({ icon: 'warning', title: 'Tidak ada data', text: 'Tidak ada tiket untuk diekspor dengan filter saat ini.' });
        return false;
    }
    // Allow direct download without confirmation for simplicity
    return true;
}

function closeTicket(id, nomor, initialData = {}) {
    openCloseTicketModal(id, nomor, initialData);
}

function deleteTicket(id, nomor) {
    Swal.fire({
        title: 'Hapus Tiket?',
        html: `Tiket <strong>${nomor}</strong> akan dihapus secara permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#DC2626',
    }).then(result => {
        if (result.isConfirmed) {
            fetch(`{{ url('/tickets') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Dihapus', text: data.message, timer: 1800, showConfirmButton: false })
                        .then(() => location.reload());
                }
            });
        }
    });
}

// ── Share Ticket Text ──────────────────────────────
async function shareTicketText() {
    // Step 1: Minta user pilih mode & tanggal
    const today = new Date().toISOString().split('T')[0];
    const { value: selectedOption, isConfirmed } = await Swal.fire({
        title: 'Pilih Ringkasan Tiket',
        html: `
            <div style="font-size:13px;color:#4B5563;margin-bottom:14px;text-align:left;">
                Pilih cakupan tiket yang akan dirangkum dan disalin ke clipboard:
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;text-align:left;">
                <!-- Opsi 1: Berdasarkan Tanggal Tertentu -->
                <div id="swal-opt-date" style="border:1.5px solid #2563EB;background:#EFF6FF;border-radius:8px;padding:10px 12px;cursor:pointer;transition:all 0.15s ease;">
                    <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;margin:0;">
                        <input type="radio" name="swal_share_mode" id="swal-mode-date" value="date" checked style="margin-top:3px;accent-color:#2563EB;">
                        <div style="flex:1;">
                            <div style="font-weight:600;font-size:13px;color:#1F2937;">Berdasarkan Tanggal Tertentu</div>
                            <div style="font-size:12px;color:#6B7280;margin:2px 0 8px 0;">Hanya merangkum tiket pada tanggal yang dipilih</div>
                            <input
                                type="date"
                                id="swal-ticket-date"
                                value="${today}"
                                max="${today}"
                                style="width:100%;padding:7px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:13px;outline:none;background:#FFFFFF;box-sizing:border-box;"
                            >
                        </div>
                    </label>
                </div>

                <!-- Opsi 2: Semua Open Ticket -->
                <div id="swal-opt-all" style="border:1px solid #E5E7EB;background:#FFFFFF;border-radius:8px;padding:10px 12px;cursor:pointer;transition:all 0.15s ease;">
                    <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;margin:0;">
                        <input type="radio" name="swal_share_mode" id="swal-mode-all" value="all_open" style="margin-top:3px;accent-color:#2563EB;">
                        <div style="flex:1;">
                            <div style="font-weight:600;font-size:13px;color:#1F2937;">Semua Open Ticket</div>
                            <div style="font-size:12px;color:#6B7280;margin-top:2px;">Rangkum semua tiket yang belum selesai (Open & Eskalasi) tanpa memandang tanggal</div>
                        </div>
                    </label>
                </div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Lanjut',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#2563EB',
        didOpen: () => {
            const radioDate = document.getElementById('swal-mode-date');
            const radioAll  = document.getElementById('swal-mode-all');
            const cardDate  = document.getElementById('swal-opt-date');
            const cardAll   = document.getElementById('swal-opt-all');
            const inputDate = document.getElementById('swal-ticket-date');

            function syncSelection() {
                const isAll = radioAll && radioAll.checked;
                if (isAll) {
                    cardAll.style.borderColor = '#2563EB';
                    cardAll.style.background = '#EFF6FF';
                    cardDate.style.borderColor = '#E5E7EB';
                    cardDate.style.background = '#FFFFFF';
                    if (inputDate) {
                        inputDate.disabled = true;
                        inputDate.style.opacity = '0.45';
                        inputDate.style.cursor = 'not-allowed';
                    }
                } else {
                    cardDate.style.borderColor = '#2563EB';
                    cardDate.style.background = '#EFF6FF';
                    cardAll.style.borderColor = '#E5E7EB';
                    cardAll.style.background = '#FFFFFF';
                    if (inputDate) {
                        inputDate.disabled = false;
                        inputDate.style.opacity = '1';
                        inputDate.style.cursor = 'auto';
                    }
                }
            }

            cardDate?.addEventListener('click', (e) => {
                if (e.target !== inputDate) {
                    radioDate.checked = true;
                    syncSelection();
                }
            });

            cardAll?.addEventListener('click', () => {
                radioAll.checked = true;
                syncSelection();
            });

            radioDate?.addEventListener('change', syncSelection);
            radioAll?.addEventListener('change', syncSelection);

            inputDate?.addEventListener('focus', () => {
                radioDate.checked = true;
                syncSelection();
            });
        },
        preConfirm: () => {
            const isAll = document.getElementById('swal-mode-all')?.checked;
            if (isAll) {
                return { mode: 'all_open' };
            }
            const d = document.getElementById('swal-ticket-date')?.value;
            if (!d) {
                Swal.showValidationMessage('Harap pilih tanggal.');
                return false;
            }
            return { mode: 'date', date: d };
        }
    });

    if (!isConfirmed || !selectedOption) return;

    // Step 2: Fetch tiket dari server
    let tickets = [];
    const isAllOpen = selectedOption.mode === 'all_open';
    const query = isAllOpen ? 'all_open=1' : `date=${selectedOption.date}`;
    try {
        const resp = await fetch(`{{ route('tickets.share-text') }}?${query}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const json = await resp.json();
        tickets = json.tickets || [];
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat memuat data tiket. Periksa koneksi.' });
        return;
    }

    // Step 3: Format teks bergaya Daily Check
    const targetDate = isAllOpen ? today : selectedOption.date;
    const [yyyy, mm, dd] = targetDate.split('-');
    const dateLabel  = `${mm}/${dd}/${yyyy}`;          // e.g. 10/21/2026
    const dateShort  = `${mm}/${dd}/${yyyy.slice(2)}`; // e.g. 10/21/26

    // Hanya tiket yang belum ditutup (Open / Eskalasi)
    const openTickets = tickets.filter(t => t.status !== 'Closed' && t.status !== 'Selesai');

    // Helper: format lokasi dan nama toko
    const formatLocationTitle = (str) => {
        if (!str) return '';
        const acronyms = ['TP', 'LW', 'SCH', 'JCM', 'BIP', 'MOG', 'TSM', 'SDA', 'GRAGE', 'CIWALK', 'CSB', 'MAG', 'KCP', 'PIM'];
        return str.trim().split(/[\s_-]+/).map(w => {
            const up = w.toUpperCase();
            if (acronyms.includes(up)) return up;
            if (up.startsWith('HS')) return up;
            return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase();
        }).join(' ');
    };

    const formatGameLocation = (storeLabel) => {
        const acronyms = ['LW', 'TP', 'SCH', 'JCM', 'BIP', 'MOG', 'TSM', 'SDA', 'CSB', 'MAG', 'KCP', 'PIM'];
        return storeLabel.trim().split(/\s+/).map(w => {
            const up = w.toUpperCase();
            if (acronyms.includes(up)) return up;
            return w.toLowerCase();
        }).join(' ');
    };

    // Helper: apakah teks mengandung salah satu kata kunci
    const has = (text, ...words) => words.some(w => (text || '').toLowerCase().includes(w.toLowerCase()));

    // Klasifikasi tiket ke 5 bucket
    const listModem     = [];
    const listNvr       = [];
    const storeCctvMap  = new Map();
    const listCctvMoka  = [];
    const listCctvGame  = [];

    const pushUnique = (arr, val) => { if (val && !arr.includes(val)) arr.push(val); };

    openTickets.forEach(t => {
        const loc        = (t.divisi_toko       || '').trim();
        const desc       = (t.deskripsi_kendala || '').trim();
        const cat        = (t.kategori          || '').toLowerCase().trim();
        const combo      = (loc + ' ' + desc).toLowerCase();
        const storeLabel = formatLocationTitle(loc);

        // 1. CCTV Game
        if (has(combo, 'game', 'games')) {
            pushUnique(listCctvGame, `· game ${formatGameLocation(storeLabel)}`);
        }
        // 2. CCTV Moka (Kasir)
        else if (has(combo, 'moka', 'mokapos', 'kasir', 'pos')) {
            pushUnique(listCctvMoka, `· Moka ${storeLabel}`);
        }
        // 3. Modem
        else if (cat === 'wifi' || has(combo, 'modem', 'simcard', 'sim card', 'kuota', 'orbit', 'router')) {
            pushUnique(listModem, `· ${storeLabel}`);
        }
        // 4. NVR / DVR
        else if (cat === 'nvr' || has(combo, 'nvr', 'dvr')) {
            pushUnique(listNvr, `· ${storeLabel}`);
        }
        // 5. CCTV Store (regular camera)
        else {
            if (!storeCctvMap.has(storeLabel)) {
                storeCctvMap.set(storeLabel, new Set());
            }
            const chanSet = storeCctvMap.get(storeLabel);

            // Match CAM 1, CAM 2, ch1, etc.
            const m = desc.match(/(?:channel|cam(?:era)?|ch)\s*(\d+)/gi);
            if (m) {
                m.forEach(token => {
                    const digit = token.match(/\d+/);
                    if (digit) chanSet.add(parseInt(digit[0], 10));
                });
            } else {
                const singleNum = desc.match(/\b(\d+)\b/);
                if (singleNum && cat === 'cctv') {
                    chanSet.add(parseInt(singleNum[1], 10));
                }
            }
        }
    });

    // Format listCctvStore with grouped channels e.g. (CAM 1,2)
    const listCctvStore = [];
    storeCctvMap.forEach((chanSet, storeLabel) => {
        if (chanSet.size > 0) {
            const arr = Array.from(chanSet);
            const isAllNumeric = arr.every(x => typeof x === 'number' || (!isNaN(parseInt(x, 10)) && /^\d+$/.test(String(x))));
            let chanStr = '';
            if (isAllNumeric) {
                const sortedNums = arr.map(x => parseInt(x, 10)).sort((a, b) => a - b);
                chanStr = `(CAM ${sortedNums.join(',')})`;
            } else {
                chanStr = `(${arr.join(',')})`;
            }
            listCctvStore.push(`· ${storeLabel} ${chanStr}`);
        } else {
            listCctvStore.push(`· ${storeLabel}`);
        }
    });

    let fullText = '';
    const headerTitle = isAllOpen ? 'Open Ticket (Semua Open Ticket)' : `Open Ticket From ${dateLabel}`;

    if (openTickets.length === 0) {
        if (isAllOpen) {
            fullText = `${headerTitle}\n\nTidak ada tiket dengan status Open/Eskalasi saat ini.`;
        } else if (tickets.length === 0) {
            fullText = `${headerTitle}\n\nTidak ada tiket pada tanggal ini.`;
        } else {
            fullText = `${headerTitle}\n\nSemua tiket pada tanggal ini sudah Closed.`;
        }
    } else {
        const sections = [];
        if (listModem.length > 0)     sections.push(`list modem offline ${dateShort}\n${listModem.join('\n')}`);
        if (listNvr.length > 0)       sections.push(`list nvr offline ${dateShort}\n${listNvr.join('\n')}`);
        if (listCctvStore.length > 0) sections.push(`list cctv store offline ${dateShort}\n${listCctvStore.join('\n')}`);
        if (listCctvMoka.length > 0)  sections.push(`list cctv moka offline ${dateShort}\n${listCctvMoka.join('\n')}`);
        if (listCctvGame.length > 0)  sections.push(`list cctv game offline ${dateShort}\n${listCctvGame.join('\n')}`);

        fullText = `${headerTitle}\n\n` + sections.join('\n\n');
    }

    // Step 4: Salin ke clipboard
    const modalTitle = isAllOpen ? 'Semua Open Ticket' : `Open Ticket — ${dateLabel}`;
    const copySuccessDesc = isAllOpen
        ? 'Ringkasan <strong>Semua Open Ticket</strong> telah disalin ke clipboard dan siap di-paste ke WhatsApp.'
        : `Ringkasan tiket <strong>${dateLabel}</strong> telah disalin ke clipboard dan siap di-paste ke WhatsApp.`;

    const showManualCopy = () => {
        Swal.fire({
            title: modalTitle,
            html: `
                <div style="font-size:13px;color:#4B5563;margin-bottom:10px;text-align:left;">
                    Silakan salin teks di bawah ini:
                </div>
                <textarea id="swal-copy-area" style="width:100%;height:280px;font-family:monospace;font-size:12.5px;padding:10px;border:1px solid #D1D5DB;border-radius:6px;background:#F9FAFB;resize:vertical;line-height:1.6;">${fullText}</textarea>
            `,
            icon: 'info',
            confirmButtonText: 'Tutup',
            didOpen: () => {
                const area = document.getElementById('swal-copy-area');
                if (area) { area.select(); area.focus(); }
            }
        });
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(fullText).then(() => {
            Swal.fire({
                title: 'Berhasil Disalin!',
                html: `
                    <div style="font-size:13px;color:#4B5563;margin-bottom:10px;text-align:left;">
                        ${copySuccessDesc}
                    </div>
                    <textarea id="swal-copy-area" readonly style="width:100%;height:280px;font-family:monospace;font-size:12.5px;padding:10px;border:1px solid #D1D5DB;border-radius:6px;background:#F9FAFB;resize:vertical;line-height:1.6;">${fullText}</textarea>
                `,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Salin Ulang',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#2563EB',
            }).then(result => {
                if (result.isConfirmed) {
                    const area = document.getElementById('swal-copy-area');
                    if (area) {
                        area.select();
                        navigator.clipboard.writeText(area.value).catch(() => document.execCommand('copy'));
                    }
                }
            });
        }).catch(showManualCopy);
    } else {
        showManualCopy();
    }
}
</script>

{{-- Modal Import Excel --}}
<div id="modal-import" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <h3 style="margin:0 0 6px; font-size:16px; font-weight:700;">Import Tiket dari Excel</h3>
        <p style="margin:0 0 18px; font-size:13px; color:#666;">Format: kolom B–P mulai baris 4 (sama dengan format ekspor).<br>Tiket dengan nomor yang sudah ada akan dilewati.</p>

        @if(session('success'))
            <div style="background:#d1fae5; color:#065f46; padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:13px;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div style="background:#fee2e2; color:#991b1b; padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:13px;">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('tickets.import') }}" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Pilih File Excel (.xlsx / .xls)</label>
                <input type="file" name="file" accept=".xlsx,.xls" required
                    style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:8px; font-size:13px;">
                @error('file') <p style="color:#dc2626; font-size:12px; margin:4px 0 0;">{{ $message }}</p> @enderror
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-import').style.display='none'"
                    style="padding:8px 18px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font-size:13px; cursor:pointer;">
                    Batal
                </button>
                <button type="submit"
                    style="padding:8px 18px; border:none; border-radius:8px; background:#2563eb; color:#fff; font-size:13px; font-weight:600; cursor:pointer;">
                    Import
                </button>
            </div>
        </form>
    </div>
</div>

@endpush
