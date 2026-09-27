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
        <a href="{{ route('tickets.export', request()->query()) }}" class="btn btn-success btn-sm" id="btn-export" onclick="return confirmExport({{ $tickets->total() }})">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Ekspor Excel
        </a>
    </div>
</div>

@php
    $filterKeys = ['status', 'kategori', 'prioritas', 'date_from', 'date_to'];
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

        @if(request()->hasAny(['search', 'status', 'kategori', 'prioritas', 'date_from', 'date_to']))
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
                            @foreach(['Open','Closed','Eskalasi'] as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label">Kategori</label>
                            <select name="kategori" class="form-select">
                                <option value="">Semua Kategori</option>
                                @foreach(['POS','Akun','Device','Email','Lainnya'] as $k)
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

                    <div class="form-group" style="margin-bottom: 0;">
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
        </div>

        <!-- Aksi Kanan -->
        <div class="actions" style="display: flex; gap: 8px;">
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
@endsection

@push('scripts')
<script>
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
            fetch(`/tickets/${id}`, {
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
</script>
@endpush
