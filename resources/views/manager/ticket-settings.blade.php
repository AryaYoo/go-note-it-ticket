@extends('layouts.app')

@section('title', 'Pengaturan Tiket')
@section('page-title', 'Pengaturan Tiket')

@push('head')
<style>
    .settings-container {
        max-width: 820px;
    }
    .settings-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        margin-bottom: 24px;
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }
    .settings-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid var(--border);
        background: #FAFAFA;
        cursor: pointer;
        user-select: none;
        transition: background-color 0.15s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }
    .settings-card-header:hover {
        background: #F4F5F7;
    }
    .settings-card.is-collapsed .settings-card-header {
        border-bottom: none;
    }
    .settings-card.is-collapsed .settings-card-body {
        display: none;
    }
    .card-collapse-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: #FFFFFF;
        border: 1px solid var(--border);
        color: var(--muted);
        cursor: pointer;
        transition: all 0.2s ease;
        padding: 0;
        flex-shrink: 0;
    }
    .card-collapse-btn:hover {
        background: #E5E7EB;
        color: var(--text);
    }
    .card-collapse-btn svg {
        transition: transform 0.25s ease;
    }
    .settings-card.is-collapsed .card-collapse-btn svg {
        transform: rotate(-90deg);
    }
    .settings-card-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--text);
    }
    .settings-card-desc {
        font-size: 12px;
        color: var(--muted);
        margin-top: 2px;
    }
    .settings-item {
        padding: 18px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        border-bottom: 1px solid #F3F4F6;
    }
    .settings-item:last-child {
        border-bottom: none;
    }
    .settings-info {
        flex: 1;
    }
    .settings-label {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 3px;
    }
    .settings-subtext {
        font-size: 12px;
        color: var(--muted);
        line-height: 1.5;
    }

    /* iOS-style toggle switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #D1D5DB;
        transition: .3s;
        border-radius: 24px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    input:checked + .slider {
        background-color: var(--primary, #2563EB);
    }
    input:checked + .slider:before {
        transform: translateX(20px);
    }

    .form-control-custom {
        padding: 8px 12px;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 13px;
        color: var(--text);
        background: #FFF;
        width: 100%;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .form-control-custom:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        outline: none;
    }
</style>
@endpush

@section('content')
<div class="settings-container">
    <div class="page-header" style="margin-bottom: 22px;">
        <div>
            <h1 style="font-size: 20px; font-weight: 700; color: var(--text); margin-bottom: 4px;">Pengaturan Tiket</h1>
            <div class="page-header-sub" style="font-size: 13px; color: var(--muted);">
                Konfigurasi format penomoran, kategori, SLA penyelesaian, dan integrasi operasional tiket untuk divisi {{ auth()->user()->divisi ?? 'Anda' }}.
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #DEF7EC; border: 1px solid #BCF0DA; color: #03543F; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <form action="{{ route('manager.ticket-settings.update') }}" method="POST">
        @csrf

        <!-- Card 1: Format & Penomoran Tiket -->
        <div class="settings-card" id="card-format-tiket">
            <div class="settings-card-header" onclick="toggleCardCollapse('card-format-tiket')">
                <div>
                    <div class="settings-card-title">Penomoran & Default Form Tiket</div>
                    <div class="settings-card-desc">Atur prefiks kode tiket dan nilai awal saat staf membuat tiket baru</div>
                </div>
                <button type="button" class="card-collapse-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>
            <div class="settings-card-body">
                <div class="settings-item">
                    <div class="settings-info">
                        <div class="settings-label">Prefiks Nomor Tiket</div>
                        <div class="settings-subtext">Kode awalan nomor tiket otomatis (contoh: <code>IT</code> menghasilkan <code>IT-21112026-001</code>).</div>
                    </div>
                    <div style="width: 140px;">
                        <input type="text" name="ticket_prefix" class="form-control-custom" style="font-weight: 700; text-transform: uppercase;" value="{{ old('ticket_prefix', $prefix) }}" required maxlength="10">
                    </div>
                </div>

                <div class="settings-item">
                    <div class="settings-info">
                        <div class="settings-label">Prioritas Default</div>
                        <div class="settings-subtext">Tingkat prioritas standar yang terpilih otomatis pada form tiket baru.</div>
                    </div>
                    <div style="width: 140px;">
                        <select name="ticket_default_priority" class="form-control-custom">
                            <option value="Low" {{ old('ticket_default_priority', $defaultPriority) === 'Low' ? 'selected' : '' }}>Low</option>
                            <option value="Medium" {{ old('ticket_default_priority', $defaultPriority) === 'Medium' ? 'selected' : '' }}>Medium</option>
                            <option value="High" {{ old('ticket_default_priority', $defaultPriority) === 'High' ? 'selected' : '' }}>High</option>
                            <option value="Urgent" {{ old('ticket_default_priority', $defaultPriority) === 'Urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="settings-item">
                    <div class="settings-info">
                        <div class="settings-label">Wajib Bukti Foto saat Tutup Tiket</div>
                        <div class="settings-subtext">Mewajibkan staf mengunggah foto bukti penyelesaian sebelum tiket diubah ke status Closed.</div>
                    </div>
                    <div>
                        <label class="switch">
                            <input type="checkbox" name="ticket_require_evidence" value="1" {{ $requireEvidence ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Target Waktu Penyelesaian (SLA) -->
        <div class="settings-card" id="card-sla-tiket">
            <div class="settings-card-header" onclick="toggleCardCollapse('card-sla-tiket')">
                <div>
                    <div class="settings-card-title">Target Waktu Penyelesaian (SLA)</div>
                    <div class="settings-card-desc">Batas maksimal penyelesaian kendala berdasarkan tingkat urgensi tiket</div>
                </div>
                <button type="button" class="card-collapse-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>
            <div class="settings-card-body">
                <div class="settings-item">
                    <div class="settings-info">
                        <div class="settings-label" style="color: #DC2626;">Prioritas High / Urgent (Jam)</div>
                        <div class="settings-subtext">Target maksimal kendala mendesak/kritis harus sudah selesai ditangani.</div>
                    </div>
                    <div style="width: 120px; display: flex; align-items: center; gap: 6px;">
                        <input type="number" name="ticket_sla_high" class="form-control-custom" min="1" max="720" value="{{ old('ticket_sla_high', $slaHigh) }}" required>
                        <span style="font-size: 12px; color: var(--muted);">Jam</span>
                    </div>
                </div>

                <div class="settings-item">
                    <div class="settings-info">
                        <div class="settings-label" style="color: #D97706;">Prioritas Medium (Jam)</div>
                        <div class="settings-subtext">Target standar untuk kendala harian operasional toko.</div>
                    </div>
                    <div style="width: 120px; display: flex; align-items: center; gap: 6px;">
                        <input type="number" name="ticket_sla_medium" class="form-control-custom" min="1" max="720" value="{{ old('ticket_sla_medium', $slaMedium) }}" required>
                        <span style="font-size: 12px; color: var(--muted);">Jam</span>
                    </div>
                </div>

                <div class="settings-item">
                    <div class="settings-info">
                        <div class="settings-label" style="color: #16A34A;">Prioritas Low (Jam)</div>
                        <div class="settings-subtext">Target untuk permintaan minor atau perbaikan non-kritis.</div>
                    </div>
                    <div style="width: 120px; display: flex; align-items: center; gap: 6px;">
                        <input type="number" name="ticket_sla_low" class="form-control-custom" min="1" max="720" value="{{ old('ticket_sla_low', $slaLow) }}" required>
                        <span style="font-size: 12px; color: var(--muted);">Jam</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Daftar Kategori Tiket -->
        <div class="settings-card" id="card-kategori-tiket">
            <div class="settings-card-header" onclick="toggleCardCollapse('card-kategori-tiket')">
                <div>
                    <div class="settings-card-title">Daftar Kategori Tiket</div>
                    <div class="settings-card-desc">Kategori yang muncul pada dropdown pilihan form tiket dan filter pencarian</div>
                </div>
                <button type="button" class="card-collapse-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>
            <div class="settings-card-body" style="padding: 20px 22px;">
                <div style="margin-bottom: 8px;">
                    <label class="settings-label">Kategori Aktif (Pisahkan dengan koma)</label>
                    <div class="settings-subtext" style="margin-bottom: 12px;">Masukkan nama-nama kategori yang relevan untuk operasional divisi Anda.</div>
                    <textarea name="ticket_categories" class="form-control-custom" rows="3" style="font-family: monospace; font-size: 12.5px; line-height: 1.6;" required>{{ old('ticket_categories', $categories) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Card 4: WhatsApp Share Text & Asisten AI -->
        <div class="settings-card" id="card-ai-whatsapp">
            <div class="settings-card-header" onclick="toggleCardCollapse('card-ai-whatsapp')">
                <div>
                    <div class="settings-card-title">Fitur WhatsApp & Asisten AI Gemini</div>
                    <div class="settings-card-desc">Preferensi penyalinan laporan tiket ke WhatsApp dan auto-suggestion AI</div>
                </div>
                <button type="button" class="card-collapse-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>
            <div class="settings-card-body">
                <div class="settings-item">
                    <div class="settings-info">
                        <div class="settings-label">Header Teks Laporan Share WhatsApp</div>
                        <div class="settings-subtext">Judul pembuka pada modal 'Share Text' saat menyalin rekap tiket ke grup WhatsApp.</div>
                    </div>
                    <div style="width: 200px;">
                        <input type="text" name="ticket_share_report_header" class="form-control-custom" value="{{ old('ticket_share_report_header', $shareReportHeader) }}">
                    </div>
                </div>

                <div class="settings-item">
                    <div class="settings-info">
                        <div class="settings-label">Saran Tindakan Otomatis (Gemini AI)</div>
                        <div class="settings-subtext">Otomatis memberikan analisis dan rekomendasi tindakan saat kendala perangkat dilaporkan (seperti isi ulang kuota modem saat modem offline).</div>
                    </div>
                    <div>
                        <label class="switch">
                            <input type="checkbox" name="ticket_auto_ai_suggestion" value="1" {{ $autoAiSuggestion ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 10px; margin-bottom: 40px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 600; font-size: 13.5px; display: inline-flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Simpan Pengaturan Tiket
            </button>
        </div>
    </form>
</div>

<script>
function toggleCardCollapse(cardId) {
    const card = document.getElementById(cardId);
    if (card) {
        card.classList.toggle('is-collapsed');
    }
}
</script>
@endsection
