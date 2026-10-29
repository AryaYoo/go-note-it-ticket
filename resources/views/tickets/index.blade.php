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
            title="Salin ringkasan tiket per tanggal ke clipboard"
        >
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Share Text
        </button>
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
            @if($ticket->status !== 'Closed')
            <button
                class="btn btn-ghost btn-sm"
                title="Tandai Closed"
                onclick="event.stopPropagation(); closeTicket({{ $ticket->id }}, '{{ $ticket->nomor_tiket }}', {{ json_encode(['tindakan' => $ticket->tindakan_dilakukan, 'solusi' => $ticket->solusi_diberikan, 'rootCause' => $ticket->root_cause, 'tanggalPenyelesaian' => $ticket->tanggal_penyelesaian?->format('Y-m-d')]) }})"
                style="color: #16A34A; border-color: #BBF7D0; background: #F0FDF4;"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
            @endif
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
    // Step 1: Minta user pilih tanggal
    const today = new Date().toISOString().split('T')[0];
    const { value: selectedDate, isConfirmed } = await Swal.fire({
        title: 'Pilih Tanggal Tiket',
        html: `
            <div style="font-size:13px;color:#4B5563;margin-bottom:12px;text-align:left;">
                Pilih tanggal untuk merangkum tiket yang akan dibagikan:
            </div>
            <input
                type="date"
                id="swal-ticket-date"
                value="${today}"
                max="${today}"
                style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:14px;outline:none;"
            >
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Lanjut',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#2563EB',
        preConfirm: () => {
            const d = document.getElementById('swal-ticket-date').value;
            if (!d) { Swal.showValidationMessage('Harap pilih tanggal.'); return false; }
            return d;
        }
    });

    if (!isConfirmed || !selectedDate) return;

    // Step 2: Fetch tiket dari server
    let tickets = [];
    try {
        const resp = await fetch(`{{ route('tickets.share-text') }}?date=${selectedDate}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const json = await resp.json();
        tickets = json.tickets || [];
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat memuat data tiket. Periksa koneksi.' });
        return;
    }

    // Step 3: Format teks bergaya Daily Check
    const [yyyy, mm, dd] = selectedDate.split('-');
    const dateLabel  = `${mm}/${dd}/${yyyy}`;          // e.g. 10/21/2026
    const dateShort  = `${mm}/${dd}/${yyyy.slice(2)}`; // e.g. 10/21/26

    // Hanya tiket yang belum ditutup (Open / Eskalasi)
    const openTickets = tickets.filter(t => t.status !== 'Closed');

    // Helper: apakah teks mengandung salah satu kata kunci
    const has = (text, ...words) => words.some(w => (text || '').toLowerCase().includes(w.toLowerCase()));

    // Klasifikasi tiket ke 4 bucket
    const listNvrModem  = []; // NVR / DVR / Modem / Router
    const listCctvStore = []; // CCTV Store biasa
    const listCctvMoka  = []; // CCTV Moka / kasir POS
    const listCctvGame  = []; // CCTV Game / hiburan

    const pushUnique = (arr, val) => { if (val && !arr.includes(val)) arr.push(val); };

    openTickets.forEach(t => {
        const loc   = (t.divisi_toko   || '').trim();
        const desc  = (t.deskripsi_kendala || '').trim();
        const combo = loc + ' ' + desc;

        // Deteksi catatan tambahan dari deskripsi
        let note = '';
        if (has(combo, 'modem', 'simcard', 'sim card')) note = '(Modem / Simcard bermasalah)';
        else if (has(desc, 'cam 1', 'camera 1', 'ch1', 'channel 1')) note = '(cam 1)';
        else if (has(desc, 'cam 2', 'camera 2', 'ch2', 'channel 2')) note = '(cam 2)';
        else if (has(desc, 'cam 3', 'camera 3', 'ch3', 'channel 3')) note = '(cam 3)';
        else if (has(desc, 'cam 4', 'camera 4', 'ch4', 'channel 4')) note = '(cam 4)';

        const line = note ? `- ${loc} ${note}` : `- ${loc}`;

        if (t.kategori === 'NVR' || has(combo, 'nvr', 'dvr', 'modem', 'router')) {
            pushUnique(listNvrModem, line);
        } else if (has(combo, 'game', 'games')) {
            pushUnique(listCctvGame, line);
        } else if (has(combo, 'moka', 'mokapos', 'kasir', 'pos ')) {
            pushUnique(listCctvMoka, line);
        } else if (t.kategori === 'CCTV' || has(combo, 'cctv', 'kamera', 'camera', 'cam')) {
            pushUnique(listCctvStore, line);
        } else {
            // Tiket non-CCTV: masuk ke NVR/Modem sebagai fallback agar tetap tercantum
            pushUnique(listNvrModem, line);
        }
    });

    let fullText = '';
    if (openTickets.length === 0 && tickets.length === 0) {
        fullText = `Open Ticket From ${dateLabel}\n\nTidak ada tiket pada tanggal ini.`;
    } else if (openTickets.length === 0) {
        fullText = `Open Ticket From ${dateLabel}\n\nSemua tiket pada tanggal ini sudah Closed.`;
    } else {
        const sections = [];
        if (listNvrModem.length > 0)  sections.push(`list nvr/modem store offline ${dateShort}\n${listNvrModem.join('\n')}`);
        if (listCctvStore.length > 0) sections.push(`list cctv store offline ${dateShort}\n${listCctvStore.join('\n')}`);
        if (listCctvMoka.length > 0)  sections.push(`list cctv moka offline ${dateShort}\n${listCctvMoka.join('\n')}`);
        if (listCctvGame.length > 0)  sections.push(`list cctv game offline ${dateShort}\n${listCctvGame.join('\n')}`);

        fullText = `Open Ticket From ${dateLabel}\n\n` + sections.join('\n\n');
    }

    // Step 4: Salin ke clipboard
    const showManualCopy = () => {
        Swal.fire({
            title: `Open Ticket — ${dateLabel}`,
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
                        Ringkasan tiket <strong>${dateLabel}</strong> telah disalin ke clipboard dan siap di-paste ke WhatsApp.
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
@endpush
