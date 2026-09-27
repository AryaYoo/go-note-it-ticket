@extends('layouts.app')
@section('title', 'Detail Tiket — ' . $ticket->nomor_tiket)
@section('page-title', 'Detail Tiket')

@section('content')
<div class="page-header">
    <div style="display: flex; align-items: center; gap: 16px;">
        <a href="{{ route('tickets.index') }}" class="btn btn-ghost btn-sm" style="padding: 6px; color: var(--muted);" title="Kembali ke Riwayat Tiket">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 style="margin-bottom: 2px;"><span class="ticket-no" style="font-size:18px;">{{ $ticket->nomor_tiket }}</span></h1>
            <div class="page-header-sub">
                Dibuat {{ $ticket->created_at->diffForHumans() }} oleh {{ $ticket->user->name ?? '—' }}
            </div>
        </div>
    </div>
    <div class="flex gap-8">
        <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-secondary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit Tiket
        </a>
    </div>
</div>

{{-- Status & Priority row --}}
<div class="flex gap-8 mb-14">
    <span class="badge {{ $ticket->statusBadgeClass() }}" style="font-size:12px;padding:4px 10px;">{{ $ticket->status }}</span>
    @if($ticket->prioritas)
    <span class="badge {{ $ticket->prioritasBadgeClass() }}" style="font-size:12px;padding:4px 10px;">{{ $ticket->prioritas }}</span>
    @endif
    @if($ticket->kategori)
    <span class="badge badge-{{ strtolower($ticket->kategori) }}" style="font-size:12px;padding:4px 10px;">{{ $ticket->kategori }}</span>
    @endif
</div>

<div style="display: flex; flex-direction: column; gap: 16px;">

    {{-- Seksi 1: Informasi Tiket --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="color:var(--primary);">Informasi Tiket</div>
        </div>
        <div class="detail-field">
            <div class="detail-label">Nomor Tiket</div>
            <div class="detail-value"><span class="ticket-no">{{ $ticket->nomor_tiket }}</span></div>
        </div>
        <div class="detail-field">
            <div class="detail-label">Tanggal Kejadian</div>
            <div class="detail-value">{{ $ticket->tanggal_kejadian->format('d F Y') }}</div>
        </div>
        <div class="detail-field">
            <div class="detail-label">Nama Pelapor</div>
            <div class="detail-value">{{ $ticket->nama_pelapor }}</div>
        </div>
        <div class="detail-field">
            <div class="detail-label">Divisi / Cabang Toko</div>
            <div class="detail-value">{{ $ticket->divisi_toko }}</div>
        </div>

        @php
            $images = is_array($ticket->lampiran_gambar) 
                ? $ticket->lampiran_gambar 
                : ($ticket->lampiran_gambar ? [$ticket->lampiran_gambar] : []);
        @endphp

        @if(count($images) > 0)
        <div class="detail-field">
            <div class="detail-label">Lampiran Bukti WhatsApp ({{ count($images) }} foto)</div>
            <div style="display:flex;gap:12px;margin-top:8px;">
                @foreach($images as $idx => $img)
                <div style="width:120px;height:120px;border:1px solid var(--border);border-radius:4px;overflow:hidden;background:#F9FAFB;cursor:pointer;display:flex;align-items:center;justify-content:center;"
                     onclick="Swal.fire({ imageUrl: '{{ route('tickets.image', ['ticket' => $ticket, 'index' => $idx]) }}', imageAlt: 'Bukti Foto {{ $idx+1 }}', width: '85%', showConfirmButton: false, customClass: { popup: 'p-2' } })"
                     title="Klik untuk memperbesar">
                    <img src="{{ route('tickets.image', ['ticket' => $ticket, 'index' => $idx]) }}" 
                         alt="Bukti {{ $idx + 1 }}"
                         style="width:100%;height:100%;object-fit:cover;">
                </div>
                @endforeach
            </div>
            <div style="font-size:10.5px;color:var(--muted);margin-top:6px;">Klik foto untuk melihat ukuran penuh</div>
        </div>
        @endif
    </div>

    {{-- Seksi 2: Detail Kendala --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="color:#7C3AED;">Detail Kendala</div>
        </div>
        <div class="detail-field">
            <div class="detail-label">Deskripsi Kendala</div>
            <div class="detail-value {{ $ticket->deskripsi_kendala ? '' : 'empty' }}">
                {{ $ticket->deskripsi_kendala ?? 'Belum diisi' }}
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-label">Dampak Operasional</div>
            <div class="detail-value {{ $ticket->dampak_operasional ? '' : 'empty' }}">
                {{ $ticket->dampak_operasional ?? 'Belum diisi' }}
            </div>
        </div>
    </div>

    {{-- Seksi 3: Penanganan --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="color:#C2410C;">Penanganan</div>
        </div>
        <div class="detail-field">
            <div class="detail-label">PIC</div>
            <div class="detail-value {{ $ticket->pic ? '' : 'empty' }}">{{ $ticket->pic ?? 'Belum ditentukan' }}</div>
        </div>
        <div class="detail-field">
            <div class="detail-label">Tindakan yang Dilakukan</div>
            <div class="detail-value {{ $ticket->tindakan_dilakukan ? '' : 'empty' }}">
                {{ $ticket->tindakan_dilakukan ?? 'Belum ada tindakan' }}
            </div>
        </div>
    </div>

    {{-- Seksi 4: Penyelesaian --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="color:var(--success);">Penyelesaian</div>
        </div>
        <div class="detail-field">
            <div class="detail-label">Tanggal Penyelesaian</div>
            <div class="detail-value {{ $ticket->tanggal_penyelesaian ? '' : 'empty' }}">
                {{ $ticket->tanggal_penyelesaian ? $ticket->tanggal_penyelesaian->format('d F Y') : 'Belum selesai' }}
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-label">Solusi yang Diberikan</div>
            <div class="detail-value {{ $ticket->solusi_diberikan ? '' : 'empty' }}">
                {{ $ticket->solusi_diberikan ?? 'Belum ada solusi' }}
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-label">Root Cause (Akar Masalah)</div>
            <div class="detail-value {{ $ticket->root_cause ? '' : 'empty' }}">
                {{ $ticket->root_cause ?? 'Belum diidentifikasi' }}
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-label">Terakhir Diperbarui</div>
            <div class="detail-value">{{ $ticket->updated_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>

</div>
@endsection
