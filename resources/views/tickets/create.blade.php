@extends('layouts.app')
@section('title', 'Buat Tiket Baru')
@section('page-title', 'Buat Tiket Baru')

@section('content')
<div class="page-header">
    <div style="display: flex; align-items: center; gap: 16px;">
        <a href="{{ route('tickets.index') }}" class="btn btn-ghost btn-sm" style="padding: 6px; color: var(--muted);" title="Kembali ke Riwayat Tiket">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 style="margin-bottom: 2px;">Buat Tiket Baru</h1>
            <div class="page-header-sub">Ikuti langkah berurutan untuk mencatat kendala, menganalisis bukti WhatsApp dengan AI, dan menyimpan tiket.</div>
        </div>
    </div>
</div>

{{-- SEQUENCE / STEPPER NAVIGATION BAR --}}
<div class="stepper-nav" id="stepper-nav">
    <div class="step-item active" id="step-nav-1" onclick="jumpToStep(1)" title="Langkah 1: Informasi Tiket">
        <div class="step-badge" id="step-badge-1">1</div>
        <span class="step-label">1. Informasi Tiket</span>
    </div>
    <div class="step-arrow">›</div>
    <div class="step-item" id="step-nav-2" onclick="jumpToStep(2)" title="Langkah 2: Bukti & AI">
        <div class="step-badge" id="step-badge-2">2</div>
        <span class="step-label">2. Bukti & AI</span>
    </div>
    <div class="step-arrow">›</div>
    <div class="step-item" id="step-nav-3" onclick="jumpToStep(3)" title="Langkah 3: Detail & Penanganan">
        <div class="step-badge" id="step-badge-3">3</div>
        <span class="step-label">3. Detail & Penanganan</span>
    </div>
    <div class="step-arrow">›</div>
    <div class="step-item" id="step-nav-4" onclick="jumpToStep(4)" title="Langkah 4: Penyelesaian & Review">
        <div class="step-badge" id="step-badge-4">4</div>
        <span class="step-label">4. Penyelesaian & Review</span>
    </div>
</div>

<form id="ticket-form">
@csrf

{{-- ============================================================
     LANGKAH 1: INFORMASI TIKET
     ============================================================ --}}
<div class="step-content active" id="step-content-1">
    <div class="step-footer" style="border-top: none; padding-top: 0; margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 14px;">
        <a href="{{ route('tickets.index') }}" class="btn btn-secondary">Batal</a>
        <button type="button" class="btn btn-primary" onclick="nextStep(2)">
            Lanjut: Bukti WhatsApp & AI →
        </button>
    </div>

    <div class="form-section">
        <div class="form-section-header blue">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
            Langkah 1: Informasi Dasar Tiket <span style="font-weight:400;color:#6B7280;margin-left:4px;">(Input Manual)</span>
        </div>
        <div class="form-section-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nomor Tiket <span class="required">*</span></label>
                    <input type="text" name="nomor_tiket" id="nomor_tiket" class="form-input" value="{{ $suggestNomor }}" placeholder="IT-DDMMYYYY-001" maxlength="30" required readonly style="background: var(--sidebar); color: var(--muted); cursor: not-allowed;">
                    <div class="form-hint">Otomatis di-generate secara berurutan oleh sistem (Read-only)</div>
                    <div class="form-error" id="err-nomor_tiket"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Kejadian <span class="required">*</span></label>
                    <input type="date" name="tanggal_kejadian" id="tanggal_kejadian" class="form-input" value="{{ date('Y-m-d') }}" required>
                    <div class="form-error" id="err-tanggal_kejadian"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Pelapor <span class="required">*</span></label>
                    <input type="text" name="nama_pelapor" id="nama_pelapor" class="form-input" placeholder="Contoh: Budi Santoso / Kepala Toko" required>
                    <div class="form-error" id="err-nama_pelapor"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Divisi / Cabang Toko <span class="required">*</span></label>
                    <input type="text" name="divisi_toko" id="divisi_toko" class="form-input" placeholder="Contoh: Cabang Sudirman / Kasir 1" required>
                    <div class="form-error" id="err-divisi_toko"></div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ============================================================
     LANGKAH 2: UNGGAH BUKTI WHATSAPP & ANALISIS AI
     ============================================================ --}}
<div class="step-content" id="step-content-2">
    <div class="step-footer" style="border-top: none; padding-top: 0; margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 14px;">
        <button type="button" class="btn btn-secondary" onclick="prevStep(1)">← Kembali ke Informasi</button>
        <button type="button" class="btn btn-primary" onclick="nextStep(3)">
            Lanjut: Detail Kendala →
        </button>
    </div>

    <div class="form-section">
        <div class="form-section-header green">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Langkah 2: Unggah Bukti WhatsApp & Analisis AI <span style="font-weight:400;color:var(--success);margin-left:4px;">(Maksimal 3 Foto)</span>
        </div>
        <div class="form-section-body">
            <div class="upload-zone" id="upload-zone">
                <input type="file" id="image-input" name="image_files" accept="image/*" multiple>
                <div class="upload-zone-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
                <div class="upload-zone-text">
                    <strong>Klik untuk memilih gambar</strong> atau seret & jatuhkan / tempel (Ctrl+V)<br>
                    <span style="font-size:11.5px;">Unggah hingga <strong>3 screenshot WhatsApp</strong> (JPG, PNG, WEBP · Maks. 10MB per foto)</span>
                </div>
            </div>

            {{-- Previews --}}
            <div id="upload-preview-grid" style="display:none;margin-top:14px;">
                <div style="font-size:12px;font-weight:600;color:var(--text-secondary);margin-bottom:8px;" id="preview-count-label">
                    Gambar Terpilih (0/3):
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(180px, 1fr));gap:12px;" id="preview-container"></div>
            </div>

            {{-- AI Analysis Action Box --}}
            <div style="background:#F8FAFC;border:1px solid var(--border);border-radius:6px;padding:12px 14px;margin-top:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <button type="button" class="btn btn-primary" id="btn-analyze" disabled>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        Analisis Gemini AI (<span id="analyze-count-badge">0</span> Foto)
                    </button>
                    <span id="analyze-status" style="font-size:12px;color:var(--muted);display:none;">
                        <span class="spinner"></span> AI sedang mengekstrak percakapan WhatsApp...
                    </span>
                </div>
                <div style="font-size:11.5px;color:var(--muted);">
                    ✦ Hasil analisis AI akan otomatis mengisi langkah 3 & 4.
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ============================================================
     LANGKAH 3: DETAIL KENDALA & PENANGANAN
     ============================================================ --}}
<div class="step-content" id="step-content-3">
    <div class="step-footer" style="border-top: none; padding-top: 0; margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 14px;">
        <button type="button" class="btn btn-secondary" onclick="prevStep(2)">← Kembali ke Bukti</button>
        <button type="button" class="btn btn-primary" onclick="nextStep(4)">
            Lanjut: Penyelesaian & Review →
        </button>
    </div>

    {{-- Detail Kendala --}}
    <div class="form-section">
        <div class="form-section-header purple">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Langkah 3.A: Detail Kendala
            <span class="ai-badge">✦ AI Auto-fill</span>
        </div>
        <div class="form-section-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kategori Kendala</label>
                    <select name="kategori" id="f-kategori" class="form-select">
                        <option value="">— Pilih Kategori —</option>
                        @foreach(['POS','Akun','Device','Lainnya'] as $k)
                        <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tingkat Prioritas</label>
                    <select name="prioritas" id="f-prioritas" class="form-select">
                        <option value="">— Pilih Prioritas —</option>
                        @foreach(['Low','Medium','High'] as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi Kendala</label>
                <textarea name="deskripsi_kendala" id="f-deskripsi_kendala" class="form-textarea" rows="3" placeholder="Rangkuman kendala dari percakapan atau input manual..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Dampak Operasional</label>
                <textarea name="dampak_operasional" id="f-dampak_operasional" class="form-textarea" rows="2" placeholder="Dampak terhadap penjualan / operasional toko..."></textarea>
            </div>
        </div>
    </div>

    {{-- Penanganan --}}
    <div class="form-section">
        <div class="form-section-header orange">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Langkah 3.B: Penanganan & Status
            <span class="ai-badge">✦ AI Auto-fill</span>
        </div>
        <div class="form-section-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Penanggung Jawab (PIC)</label>
                    <input type="text" name="pic" id="f-pic" class="form-input" value="{{ Auth::user()->name }}" placeholder="Nama PIC">
                    <div class="form-hint">Default: nama akun staff Anda</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status Tiket <span class="required">*</span></label>
                    <select name="status" id="f-status" class="form-select" required>
                        <option value="Open" selected>Open</option>
                        <option value="Closed">Closed</option>
                        <option value="Eskalasi">Eskalasi</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Tindakan yang Dilakukan</label>
                <textarea name="tindakan_dilakukan" id="f-tindakan_dilakukan" class="form-textarea" rows="2" placeholder="Tindakan awal / perbaikan sementara yang dilakukan..."></textarea>
            </div>
        </div>
    </div>

</div>

{{-- ============================================================
     LANGKAH 4: PENYELESAIAN & REVIEW RINGKASAN
     ============================================================ --}}
<div class="step-content" id="step-content-4">
    <div class="step-footer" style="border-top: none; padding-top: 0; margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 14px;">
        <button type="button" class="btn btn-secondary" onclick="prevStep(3)">← Kembali ke Penanganan</button>
        <button type="submit" class="btn btn-primary btn-lg" id="btn-submit">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Simpan & Buat Tiket
        </button>
    </div>

    {{-- Penyelesaian --}}
    <div class="form-section">
        <div class="form-section-header green">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Langkah 4: Penyelesaian Tiket
            <span class="ai-badge">✦ AI Auto-fill</span>
        </div>
        <div class="form-section-body">
            <div class="form-group">
                <label class="form-label">Tanggal Penyelesaian</label>
                <input type="date" name="tanggal_penyelesaian" id="f-tanggal_penyelesaian" class="form-input" style="max-width:220px;">
                <div class="form-hint">Kosongkan jika tiket masih berstatus Open atau Eskalasi</div>
            </div>
            <div class="form-group">
                <label class="form-label">Solusi yang Diberikan</label>
                <textarea name="solusi_diberikan" id="f-solusi_diberikan" class="form-textarea" rows="2" placeholder="Solusi permanen atau perbaikan yang telah diterapkan..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Root Cause (Akar Masalah)</label>
                <textarea name="root_cause" id="f-root_cause" class="form-textarea" rows="2" placeholder="Akar penyebab utama terjadinya kendala..."></textarea>
            </div>
        </div>
    </div>

    {{-- Review Ringkasan Tiket --}}
    <div class="card" style="background:#F9FAFB;border:1px solid var(--border);margin-bottom:16px;">
        <div class="card-header" style="background:#FFFFFF;border-bottom:1px solid var(--border);">
            <div class="card-title" style="color:var(--text-primary);font-size:13px;display:flex;align-items:center;gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Ringkasan Tiket Sebelum Disimpan
            </div>
        </div>
        <div style="padding:14px;">
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:12px;font-size:12px;">
                <div>
                    <span style="color:var(--muted);display:block;font-size:11px;">Nomor Tiket:</span>
                    <strong id="rev-nomor" class="ticket-no">-</strong>
                </div>
                <div>
                    <span style="color:var(--muted);display:block;font-size:11px;">Pelapor:</span>
                    <strong id="rev-pelapor">-</strong>
                </div>
                <div>
                    <span style="color:var(--muted);display:block;font-size:11px;">Cabang Toko:</span>
                    <strong id="rev-cabang">-</strong>
                </div>
                <div>
                    <span style="color:var(--muted);display:block;font-size:11px;">Kategori / Prioritas:</span>
                    <span id="rev-kategori-prioritas">-</span>
                </div>
                <div>
                    <span style="color:var(--muted);display:block;font-size:11px;">Status / PIC:</span>
                    <span id="rev-status-pic">-</span>
                </div>
                <div>
                    <span style="color:var(--muted);display:block;font-size:11px;">Lampiran Bukti:</span>
                    <strong id="rev-lampiran">0 Foto</strong>
                </div>
            </div>
        </div>
    </div>

</div>

</form>
@endsection

@push('scripts')
<script>
// ============================================================
// SEQUENCE / STEPPER STATE CONTROLLER
// ============================================================
let currentStep = 1;
const totalSteps = 4;
let completedSteps = new Set();

function showStep(step) {
    // Hide all step content
    for (let i = 1; i <= totalSteps; i++) {
        const content = document.getElementById(`step-content-${i}`);
        const nav = document.getElementById(`step-nav-${i}`);
        const badge = document.getElementById(`step-badge-${i}`);

        if (content) content.classList.remove('active');
        if (nav) nav.classList.remove('active');

        if (completedSteps.has(i)) {
            nav.classList.add('completed');
            badge.innerHTML = '✓';
        } else {
            nav.classList.remove('completed');
            badge.textContent = i;
        }
    }

    // Activate target step
    currentStep = step;
    const activeContent = document.getElementById(`step-content-${step}`);
    const activeNav = document.getElementById(`step-nav-${step}`);
    const activeBadge = document.getElementById(`step-badge-${step}`);

    if (activeContent) activeContent.classList.add('active');
    if (activeNav) {
        activeNav.classList.add('active');
        activeBadge.textContent = step;
    }

    // Update review data when reaching Step 4
    if (step === 4) {
        updateReviewSummary();
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep1() {
    clearErrors();
    let valid = true;

    const nomor = document.getElementById('nomor_tiket').value.trim();
    const tgl = document.getElementById('tanggal_kejadian').value.trim();
    const pelapor = document.getElementById('nama_pelapor').value.trim();
    const cabang = document.getElementById('divisi_toko').value.trim();

    if (!nomor) {
        document.getElementById('err-nomor_tiket').textContent = 'Nomor tiket wajib diisi.';
        valid = false;
    }
    if (!tgl) {
        document.getElementById('err-tanggal_kejadian').textContent = 'Tanggal kejadian wajib diisi.';
        valid = false;
    }
    if (!pelapor) {
        document.getElementById('err-nama_pelapor').textContent = 'Nama pelapor wajib diisi.';
        valid = false;
    }
    if (!cabang) {
        document.getElementById('err-divisi_toko').textContent = 'Divisi / Cabang toko wajib diisi.';
        valid = false;
    }

    return valid;
}

function nextStep(step) {
    if (currentStep === 1 && !validateStep1()) {
        Swal.fire({
            icon: 'warning',
            title: 'Lengkapi Data',
            text: 'Harap isi semua kolom wajib pada Informasi Tiket.',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }

    completedSteps.add(currentStep);
    showStep(step);
}

function prevStep(step) {
    showStep(step);
}

function jumpToStep(step) {
    if (step < currentStep || completedSteps.has(step - 1)) {
        if (currentStep === 1 && step > 1 && !validateStep1()) {
            return;
        }
        showStep(step);
    }
}

function updateReviewSummary() {
    document.getElementById('rev-nomor').textContent = document.getElementById('nomor_tiket').value || '-';
    document.getElementById('rev-pelapor').textContent = document.getElementById('nama_pelapor').value || '-';
    document.getElementById('rev-cabang').textContent = document.getElementById('divisi_toko').value || '-';
    
    const kat = document.getElementById('f-kategori').value || '—';
    const pri = document.getElementById('f-prioritas').value || '—';
    document.getElementById('rev-kategori-prioritas').textContent = `${kat} / ${pri}`;

    const sta = document.getElementById('f-status').value || 'Open';
    const pic = document.getElementById('f-pic').value || '—';
    document.getElementById('rev-status-pic').textContent = `${sta} (${pic})`;

    document.getElementById('rev-lampiran').textContent = `${selectedFiles.length} Foto`;
}

// ============================================================
// MULTI-IMAGE UPLOAD & PREVIEW (MAX 3)
// ============================================================
const uploadZone        = document.getElementById('upload-zone');
const imageInput        = document.getElementById('image-input');
const btnAnalyze        = document.getElementById('btn-analyze');
const analyzeCountBadge = document.getElementById('analyze-count-badge');
const analyzeStatus     = document.getElementById('analyze-status');
const uploadPreviewGrid = document.getElementById('upload-preview-grid');
const previewContainer  = document.getElementById('preview-container');
const previewCountLabel = document.getElementById('preview-count-label');

let selectedFiles = []; // Array of File objects (max 3)

function addFiles(files) {
    const arr = Array.from(files).filter(f => f.type.startsWith('image/'));
    if (!arr.length) return;

    for (const f of arr) {
        if (selectedFiles.length >= 3) {
            Swal.fire({
                icon: 'warning',
                title: 'Batas Maksimal',
                text: 'Maksimal 3 foto bukti WhatsApp yang dapat diunggah.',
                timer: 2000,
                showConfirmButton: false
            });
            break;
        }
        if (!selectedFiles.some(existing => existing.name === f.name && existing.size === f.size)) {
            selectedFiles.push(f);
        }
    }
    renderPreviews();
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    renderPreviews();
}

function renderPreviews() {
    previewContainer.innerHTML = '';
    
    if (selectedFiles.length === 0) {
        uploadPreviewGrid.style.display = 'none';
        btnAnalyze.disabled = true;
        analyzeCountBadge.textContent = '0';
        return;
    }

    uploadPreviewGrid.style.display = 'block';
    previewCountLabel.textContent = `Gambar Terpilih (${selectedFiles.length}/3):`;
    analyzeCountBadge.textContent = selectedFiles.length;
    btnAnalyze.disabled = false;

    selectedFiles.forEach((file, idx) => {
        const item = document.createElement('div');
        item.style.cssText = 'position:relative;border:1px solid var(--border);border-radius:6px;overflow:hidden;background:var(--bg);box-shadow:0 1px 2px rgba(0,0,0,0.04);';

        const imgUrl = URL.createObjectURL(file);
        
        item.innerHTML = `
            <div style="height:120px;background:#F9FAFB;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                <img src="${imgUrl}" alt="Preview ${idx + 1}" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div style="padding:6px 8px;display:flex;align-items:center;justify-content:space-between;background:#fff;border-top:1px solid var(--border);">
                <div style="font-size:11px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:110px;" title="${file.name}">
                    ${file.name}
                </div>
                <button type="button" onclick="removeFile(${idx})" title="Hapus foto" style="background:#FEE2E2;color:#DC2626;border:none;border-radius:4px;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:12px;cursor:pointer;font-weight:700;">✕</button>
            </div>
        `;
        previewContainer.appendChild(item);
    });
}

imageInput.addEventListener('change', e => {
    addFiles(e.target.files);
    imageInput.value = '';
});

// Drag & Drop
uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    if (e.dataTransfer.files) addFiles(e.dataTransfer.files);
});

// Paste (Ctrl+V)
document.addEventListener('paste', e => {
    const items = e.clipboardData?.items;
    if (!items) return;
    for (const item of items) {
        if (item.type.startsWith('image/')) {
            const blob = item.getAsFile();
            if (blob) addFiles([blob]);
        }
    }
});

// Auto-generate nomor tiket dari tanggal
document.getElementById('tanggal_kejadian').addEventListener('change', function() {
    const d = new Date(this.value);
    if (isNaN(d)) return;
    const dd   = String(d.getDate()).padStart(2,'0');
    const mm   = String(d.getMonth()+1).padStart(2,'0');
    const yyyy = d.getFullYear();
    const curr = document.getElementById('nomor_tiket').value;
    const parts = curr.split('-');
    const seq   = parts.length === 3 ? parts[2] : '001';
    document.getElementById('nomor_tiket').value = `IT-${dd}${mm}${yyyy}-${seq}`;
});

// ============================================================
// AI ANALYZE (MULTIPLE IMAGES)
// ============================================================
btnAnalyze.addEventListener('click', async () => {
    if (!selectedFiles.length) return;
    btnAnalyze.disabled = true;
    analyzeStatus.style.display = 'inline-flex';

    const fd = new FormData();
    selectedFiles.forEach(file => {
        fd.append('images[]', file);
    });
    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

    try {
        const res  = await fetch('{{ route("tickets.analyze") }}', { method: 'POST', body: fd });
        const json = await res.json();

        if (!json.success) throw new Error(json.message || 'Gagal menganalisis gambar.');

        const d = json.data;
        if (d.kategori)             setSelect('f-kategori', d.kategori);
        if (d.prioritas)            setSelect('f-prioritas', d.prioritas);
        if (d.deskripsi_kendala)    setValue('f-deskripsi_kendala', d.deskripsi_kendala);
        if (d.dampak_operasional)   setValue('f-dampak_operasional', d.dampak_operasional);
        if (d.status)               setSelect('f-status', d.status);
        if (d.tindakan_dilakukan)   setValue('f-tindakan_dilakukan', d.tindakan_dilakukan);
        if (d.tanggal_penyelesaian) setValue('f-tanggal_penyelesaian', d.tanggal_penyelesaian);
        if (d.solusi_diberikan)     setValue('f-solusi_diberikan', d.solusi_diberikan);
        if (d.root_cause)           setValue('f-root_cause', d.root_cause);

        await Swal.fire({ 
            icon: 'success', 
            title: 'Analisis Selesai!', 
            text: `AI berhasil mengekstrak detail dari ${selectedFiles.length} gambar WhatsApp. Mari lanjutkan ke Langkah 3 untuk meninjau data.`, 
            confirmButtonText: 'Lanjut ke Detail Kendala →',
            confirmButtonColor: '#2563EB'
        });

        // Automatically advance to Step 3
        completedSteps.add(1);
        completedSteps.add(2);
        showStep(3);

    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Analisis Gagal', text: err.message });
    } finally {
        btnAnalyze.disabled = false;
        analyzeStatus.style.display = 'none';
    }
});

function setSelect(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val;
}
function setValue(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val;
}

// ============================================================
// FINAL SUBMIT & KEYBOARD NAVIGATION
// ============================================================
document.getElementById('ticket-form').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'BUTTON') {
            return; // Biarkan default (new line di textarea atau klik button)
        }
        
        e.preventDefault(); // Cegah submit otomatis
        
        // Jadikan Enter berfungsi seperti Tab (pindah ke input berikutnya)
        const focusable = Array.from(this.querySelectorAll('input:not([readonly]):not([type="hidden"]), select, textarea'));
        const index = focusable.indexOf(e.target);
        
        if (index > -1 && index < focusable.length - 1) {
            focusable[index + 1].focus();
        }
    }
});

document.getElementById('ticket-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    clearErrors();
    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Menyimpan Tiket...';

    const fd = new FormData(this);
    fd.delete('image_files');
    fd.delete('lampiran_gambar[]');
    
    selectedFiles.forEach(file => {
        fd.append('lampiran_gambar[]', file);
    });

    try {
        const res  = await fetch('{{ route("tickets.store") }}', { 
            method: 'POST', 
            body: fd, 
            headers: { 'Accept': 'application/json' } 
        });
        const json = await res.json();

        if (json.success) {
            await Swal.fire({ 
                icon: 'success', 
                title: 'Tiket Berhasil Disimpan!', 
                text: `Tiket ${json.nomor_tiket} telah resmi diterbitkan.`, 
                timer: 2200, 
                showConfirmButton: false 
            });
            window.location.href = json.redirect;
        } else if (json.errors) {
            showErrors(json.errors);
            Swal.fire({ icon: 'warning', title: 'Periksa Form', text: 'Terdapat field yang belum sesuai. Harap periksa kembali.' });
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: json.message || 'Terjadi kesalahan.' });
        }
    } catch {
        Swal.fire({ icon: 'error', title: 'Kesalahan Jaringan', text: 'Tidak dapat terhubung ke server.' });
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Simpan & Buat Tiket`;
    }
});

function showErrors(errors) {
    Object.entries(errors).forEach(([field, msgs]) => {
        const cleanField = field.replace(/\.\d+$/, '');
        const el = document.getElementById('err-' + cleanField) || document.getElementById('err-' + field);
        if (el) el.textContent = msgs[0];
    });
}
function clearErrors() {
    document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
}
</script>
@endpush
