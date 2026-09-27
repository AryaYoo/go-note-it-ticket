@extends('layouts.app')
@section('title', 'Edit Tiket — ' . $ticket->nomor_tiket)
@section('page-title', 'Edit Tiket')

@section('content')
<div class="page-header">
    <div style="display: flex; align-items: center; gap: 16px;">
        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-ghost btn-sm" style="padding: 6px; color: var(--muted);" title="Kembali ke Detail Tiket">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 style="margin-bottom: 2px;">Edit Tiket</h1>
            <div class="page-header-sub"><span class="ticket-no" style="font-size:14px;">{{ $ticket->nomor_tiket }}</span></div>
        </div>
    </div>
</div>

<form id="edit-form">
@csrf
@method('PUT')

{{-- SEKSI 1: Informasi Tiket --}}
<div class="form-section">
    <div class="form-section-header blue">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
        1. Informasi Tiket
    </div>
    <div class="form-section-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nomor Tiket <span class="required">*</span></label>
                <input type="text" name="nomor_tiket" class="form-input" value="{{ $ticket->nomor_tiket }}" required readonly style="background: var(--sidebar); color: var(--muted); cursor: not-allowed;">
                <div class="form-error" id="err-nomor_tiket"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Kejadian <span class="required">*</span></label>
                <input type="date" name="tanggal_kejadian" class="form-input" value="{{ $ticket->tanggal_kejadian->format('Y-m-d') }}" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nama Pelapor <span class="required">*</span></label>
                <input type="text" name="nama_pelapor" class="form-input" value="{{ $ticket->nama_pelapor }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Divisi / Cabang Toko <span class="required">*</span></label>
                <input type="text" name="divisi_toko" class="form-input" value="{{ $ticket->divisi_toko }}" required>
            </div>
        </div>
    </div>
</div>

{{-- SEKSI 2: Detail Kendala --}}
<div class="form-section">
    <div class="form-section-header purple">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        2. Detail Kendala
    </div>
    <div class="form-section-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Kategori Kendala</label>
                <select name="kategori" class="form-select">
                    <option value="">— Pilih —</option>
                    @foreach(['POS','Akun','Device','Lainnya'] as $k)
                    <option value="{{ $k }}" {{ $ticket->kategori === $k ? 'selected' : '' }}>{{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tingkat Prioritas</label>
                <select name="prioritas" class="form-select">
                    <option value="">— Pilih —</option>
                    @foreach(['Low','Medium','High'] as $p)
                    <option value="{{ $p }}" {{ $ticket->prioritas === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Deskripsi Kendala</label>
            <textarea name="deskripsi_kendala" class="form-textarea" rows="3">{{ $ticket->deskripsi_kendala }}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Dampak Operasional</label>
            <textarea name="dampak_operasional" class="form-textarea" rows="2">{{ $ticket->dampak_operasional }}</textarea>
        </div>
        
        @php
            $images = is_array($ticket->lampiran_gambar) 
                ? $ticket->lampiran_gambar 
                : ($ticket->lampiran_gambar ? [$ticket->lampiran_gambar] : []);
        @endphp

        <div class="form-group">
            <label class="form-label">Ganti Lampiran Bukti WhatsApp (Maks 3 Foto)</label>
            @if(count($images) > 0)
            <div style="margin-bottom:8px;">
                <div style="font-size:11px;color:var(--text-secondary);margin-bottom:4px;">Gambar saat ini ({{ count($images) }} foto):</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @foreach($images as $idx => $img)
                    <img src="{{ route('tickets.image', ['ticket' => $ticket, 'index' => $idx]) }}" 
                         alt="Lampiran {{ $idx+1 }}" 
                         style="height:64px;width:64px;object-fit:cover;border:1px solid var(--border);border-radius:4px;">
                    @endforeach
                </div>
            </div>
            @endif
            <input type="file" name="lampiran_gambar[]" class="form-input" accept="image/*" multiple style="padding:6px;">
            <div class="form-hint">Pilih hingga 3 gambar baru jika ingin mengganti seluruh lampiran sebelumnya. Kosongkan jika tetap menggunakan lampiran saat ini.</div>
        </div>
    </div>
</div>

{{-- SEKSI 3: Penanganan --}}
<div class="form-section">
    <div class="form-section-header orange">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        3. Penanganan
    </div>
    <div class="form-section-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">PIC</label>
                <input type="text" name="pic" class="form-input" value="{{ $ticket->pic }}">
            </div>
            <div class="form-group">
                <label class="form-label">Status Tiket <span class="required">*</span></label>
                <select name="status" class="form-select" required>
                    @foreach(['Open','Closed','Eskalasi'] as $s)
                    <option value="{{ $s }}" {{ $ticket->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Tindakan yang Dilakukan</label>
            <textarea name="tindakan_dilakukan" class="form-textarea" rows="2">{{ $ticket->tindakan_dilakukan }}</textarea>
        </div>
    </div>
</div>

{{-- SEKSI 4: Penyelesaian --}}
<div class="form-section">
    <div class="form-section-header green">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        4. Penyelesaian
    </div>
    <div class="form-section-body">
        <div class="form-group">
            <label class="form-label">Tanggal Penyelesaian</label>
            <input type="date" name="tanggal_penyelesaian" class="form-input" style="max-width:220px;" value="{{ $ticket->tanggal_penyelesaian?->format('Y-m-d') }}">
        </div>
        <div class="form-group">
            <label class="form-label">Solusi yang Diberikan</label>
            <textarea name="solusi_diberikan" class="form-textarea" rows="2">{{ $ticket->solusi_diberikan }}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Root Cause (Akar Masalah)</label>
            <textarea name="root_cause" class="form-textarea" rows="2">{{ $ticket->root_cause }}</textarea>
        </div>
    </div>
</div>

<div class="flex-between" style="padding-top:4px;">
    <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-secondary">Batal</a>
    <button type="submit" class="btn btn-primary btn-lg" id="btn-update">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg>
        Simpan Perubahan
    </button>
</div>
</form>
@endsection

@push('scripts')
<script>
document.getElementById('edit-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-update');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Menyimpan...';

    const fd = new FormData(this);

    try {
        const res  = await fetch('{{ route("tickets.update", $ticket) }}', {
            method: 'POST',
            body: fd,
            headers: {
                'Accept': 'application/json',
                'X-HTTP-Method-Override': 'PUT',
            }
        });
        const json = await res.json();

        if (json.success) {
            await Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message, timer: 1800, showConfirmButton: false });
            window.location.href = json.redirect;
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: json.message || 'Terjadi kesalahan.' });
        }
    } catch {
        Swal.fire({ icon: 'error', title: 'Kesalahan', text: 'Tidak dapat terhubung ke server.' });
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg> Simpan Perubahan`;
    }
});
</script>
@endpush
