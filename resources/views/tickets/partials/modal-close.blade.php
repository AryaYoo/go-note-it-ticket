{{-- Modal Tutup Tiket dengan 3 Slot Placeholder Foto (Ctrl+C & Ctrl+V), Analisis Gambar AI & Form Penyelesaian --}}
<div class="filter-modal-backdrop" id="close-ticket-modal" style="display:none;" onmousedown="closeModalMouseDownTarget = event.target" onmouseup="handleCloseModalMouseUp(event)">
    <div class="filter-modal-content" style="max-width: 600px; width: 95%; max-height: 92vh; display: flex; flex-direction: column;" onclick="event.stopPropagation()">
        
        {{-- Header Modal --}}
        <div class="filter-modal-header" style="background: #F0FDF4; border-bottom: 1px solid #DCFCE7;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #DCFCE7; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #16A34A;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div>
                    <div class="filter-modal-title" style="color: #15803D; font-size: 14.5px;">
                        Tutup Tiket <span id="cm-nomor-badge" class="ticket-no" style="font-size: 13.5px; margin-left: 4px; color: #166534;">-</span>
                    </div>
                    <div class="filter-modal-subtitle" style="color: #4B5563;">
                        Tandai tiket sebagai <strong>Closed</strong> dan lengkapi data penyelesaian
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close-modal" onclick="closeCloseTicketModal()" title="Tutup">✕</button>
        </div>

        {{-- Body Modal (Scrollable) --}}
        <div class="filter-modal-body" style="padding: 16px 20px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 14px;">
            
            {{-- Field Tanggal Penyelesaian --}}
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-weight: 600; color: var(--text);">
                    Tanggal Penutupan / Penyelesaian <span class="required" style="color:#DC2626;">*</span>
                </label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input 
                        type="date" 
                        id="cm-tgl-penyelesaian" 
                        class="form-input" 
                        style="width: 100%; max-width: 260px; font-weight: 500;"
                        required
                    >
                    <span style="font-size: 11.5px; color: #16A34A; background: #F0FDF4; border: 1px solid #BBF7D0; padding: 4px 8px; border-radius: 4px; white-space: nowrap;">
                        ✓ Tanggal Sekarang
                    </span>
                </div>
                <div class="form-hint" style="font-size: 11px; margin-top: 3px;">
                    Otomatis terisi tanggal hari ini saat modal dibuka.
                </div>
            </div>

            {{-- Seksi Upload Bukti Tambahan (3 Slot Placeholder Foto dengan Ctrl+V) --}}
            <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px;">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                    <div>
                        <div style="font-size: 12.5px; font-weight: 600; color: var(--text); display: flex; align-items: center; gap: 6px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            Unggah Foto Bukti Tambahan (Maks 3 Slot)
                        </div>
                        <div style="font-size: 11px; color: var(--muted); margin-top: 1px;">
                            Bisa pakai <strong>Ctrl+C</strong> &amp; <strong>Ctrl+V</strong> langsung untuk mengisi atau menimpa slot foto
                        </div>
                    </div>

                    {{-- Tombol Trigger AI --}}
                    <button 
                        type="button" 
                        id="btn-cm-ai-analyze" 
                        onclick="analyzeCloseModalImages()" 
                        class="btn btn-sm"
                        style="background: linear-gradient(135deg, #4F46E5, #7C3AED); color: #fff; border: none; font-size: 11.5px; padding: 5px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; box-shadow: 0 1px 2px rgba(79, 70, 229, 0.2);"
                        title="Analisis foto bukti untuk mengisi otomatis Solusi, Tindakan, dan Root Cause"
                    >
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <span>✦ Analisis AI (<span id="cm-slot-count-badge">0</span>/3)</span>
                    </button>
                </div>

                {{-- Alert Helper Tips Paste --}}
                <div style="display: flex; align-items: center; gap: 6px; background: #EEF2FF; border: 1px solid #C7D2FE; border-radius: 6px; padding: 6px 10px; font-size: 11.5px; color: #3730A3; margin-bottom: 10px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>Salin gambar (<strong>Ctrl+C</strong>) di WhatsApp/layar, lalu tekan <strong>Ctrl+V</strong> di sini. Klik slot untuk memilih target ganti foto.</span>
                </div>

                {{-- Hidden File Input untuk pemilihan file per slot --}}
                <input type="file" id="cm-slot-file-input" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="handleSlotFileChange(this.files)">

                {{-- Container 3 Slot Placeholder Foto --}}
                <div id="cm-slots-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                    {{-- Diisi secara dinamis oleh renderSlots() --}}
                </div>

                {{-- Indikator Status AI --}}
                <div id="cm-ai-status" style="display: none; margin-top: 10px; font-size: 11.5px; padding: 7px 10px; border-radius: 6px;"></div>
            </div>

            {{-- Form Penyelesaian Kendala --}}
            <div style="border-top: 1px solid var(--border); padding-top: 12px;">
                <div style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                    <span>Formulir Penyelesaian Tiket</span>
                    <span style="font-size: 11px; font-weight: normal; color: var(--muted);">AI dapat mengisi field yang masih kosong / belum selesai</span>
                </div>

                <div class="form-group" style="margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                        <label class="form-label" style="font-size: 12px; margin-bottom: 0;">Solusi yang Diberikan</label>
                        <span id="badge-ai-solusi" style="display: none; font-size: 10px; color: #16A34A; background: #DCFCE7; padding: 1px 6px; border-radius: 4px;">✦ Diisi AI</span>
                    </div>
                    <textarea 
                        id="cm-solusi" 
                        class="form-textarea" 
                        rows="2" 
                        placeholder="Contoh: Mengganti kabel HDMI kasir, merestart aplikasi POS, dan transaksi sudah kembali lancar..."
                        style="font-size: 12.5px; transition: border-color 0.3s, background-color 0.3s;"
                    ></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                        <label class="form-label" style="font-size: 12px; margin-bottom: 0;">Tindakan yang Dilakukan</label>
                        <span id="badge-ai-tindakan" style="display: none; font-size: 10px; color: #16A34A; background: #DCFCE7; padding: 1px 6px; border-radius: 4px;">✦ Diisi AI</span>
                    </div>
                    <textarea 
                        id="cm-tindakan" 
                        class="form-textarea" 
                        rows="2" 
                        placeholder="Contoh: Pengecekan hardware, reconnect socket kabel, pengujian print struk..."
                        style="font-size: 12.5px; transition: border-color 0.3s, background-color 0.3s;"
                    ></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                        <label class="form-label" style="font-size: 12px; margin-bottom: 0;">Root Cause (Akar Masalah)</label>
                        <span id="badge-ai-rootcause" style="display: none; font-size: 10px; color: #16A34A; background: #DCFCE7; padding: 1px 6px; border-radius: 4px;">✦ Diisi AI</span>
                    </div>
                    <textarea 
                        id="cm-rootcause" 
                        class="form-textarea" 
                        rows="2" 
                        placeholder="Contoh: Pin port kabel HDMI aus karena tertekan drawer kasir..."
                        style="font-size: 12.5px; transition: border-color 0.3s, background-color 0.3s;"
                    ></textarea>
                </div>
            </div>

        </div>

        {{-- Footer Modal --}}
        <div class="filter-modal-footer" style="padding: 12px 20px; background: #F9FAFB; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeCloseTicketModal()">Batal</button>
            <button type="button" class="btn btn-sm" id="btn-cm-submit" onclick="submitCloseTicket()" style="background: #16A34A; color: #fff; border-color: #16A34A; padding: 6px 16px; font-weight: 600;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 4px;"><polyline points="20 6 9 17 4 12"/></svg>
                Ya, Tutup Tiket
            </button>
        </div>

    </div>
</div>

<style>
/* Animasi glow hijau lembut saat form diisi oleh AI */
@keyframes aiGlow {
    0% { background-color: #DCFCE7; border-color: #16A34A; box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2); }
    100% { background-color: #FFFFFF; border-color: #D1D5DB; box-shadow: none; }
}
.ai-highlight-glow {
    animation: aiGlow 1.8s ease-out;
}

/* Animasi pop saat gambar berhasil ditempel / diupload ke slot */
@keyframes slotPop {
    0% { transform: scale(0.95); opacity: 0.8; }
    50% { transform: scale(1.03); }
    100% { transform: scale(1); opacity: 1; }
}
.slot-pop {
    animation: slotPop 250ms ease-out;
}
</style>

<script>
let currentCloseTicketId = null;
let currentCloseTicketNomor = '';
let closeModalMouseDownTarget = null;

// Array tepat 3 slot foto (maks 3 foto)
let closeTicketSlots = [null, null, null];
let activeSlotIndex = 0; // slot yang sedang dipilih/ditarget

function handleCloseModalMouseUp(e) {
    if (closeModalMouseDownTarget === e.currentTarget && e.target === e.currentTarget) {
        closeCloseTicketModal();
    }
    closeModalMouseDownTarget = null;
}

/**
 * Kompresi gambar ringan sebelum disimpan/dianalisis
 */
function compressSlotImage(file, maxWidth = 1600, quality = 0.82) {
    return new Promise((resolve) => {
        if (!file.type.match(/^image\/(jpeg|png|webp)$/)) return resolve(file);
        const img = new Image();
        const objectUrl = URL.createObjectURL(file);
        img.onload = () => {
            URL.revokeObjectURL(objectUrl);
            let { width, height } = img;
            if (width > maxWidth) {
                height = Math.round((height * maxWidth) / width);
                width = maxWidth;
            }
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);
            canvas.toBlob((blob) => {
                if (!blob || blob.size >= file.size) return resolve(file);
                const originalName = file.name || 'pasted_image.png';
                const baseName = originalName.substring(0, originalName.lastIndexOf('.')) || originalName;
                const newFile = new File([blob], `${baseName}.jpg`, {
                    type: 'image/jpeg',
                    lastModified: Date.now()
                });
                resolve(newFile);
            }, 'image/jpeg', quality);
        };
        img.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            resolve(file);
        };
        img.src = objectUrl;
    });
}

/**
 * Buka modal tutup tiket
 */
function openCloseTicketModal(id, nomor, initialData = {}) {
    currentCloseTicketId = id;
    currentCloseTicketNomor = nomor;
    closeTicketSlots = [null, null, null];
    activeSlotIndex = 0;

    // Header info
    document.getElementById('cm-nomor-badge').textContent = nomor;

    // Otomatis terisi tanggal hari ini (format YYYY-MM-DD lokal)
    const today = new Date().toLocaleDateString('en-CA');
    const tglInput = document.getElementById('cm-tgl-penyelesaian');
    tglInput.value = initialData.tanggalPenyelesaian || today;
    tglInput.max = today;

    // Isi field form penyelesaian dari data awal tiket
    document.getElementById('cm-solusi').value = initialData.solusi || '';
    document.getElementById('cm-tindakan').value = initialData.tindakan || '';
    document.getElementById('cm-rootcause').value = initialData.rootCause || '';

    // Reset status & badge AI
    hideAiBadges();
    resetAiStatus();

    // Render 3 slot placeholder
    renderSlots();

    // Reset submit button
    const btnSubmit = document.getElementById('btn-cm-submit');
    btnSubmit.disabled = false;
    btnSubmit.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 4px;"><polyline points="20 6 9 17 4 12"/></svg> Ya, Tutup Tiket`;

    // Tampilkan modal
    const modal = document.getElementById('close-ticket-modal');
    modal.style.display = 'flex';
    modal.classList.add('open');
}

function closeCloseTicketModal() {
    const modal = document.getElementById('close-ticket-modal');
    modal.classList.remove('open');
    modal.style.display = 'none';
    closeTicketSlots = [null, null, null];
}

/**
 * Render 3 slot placeholder foto
 */
function renderSlots() {
    const grid = document.getElementById('cm-slots-grid');
    grid.innerHTML = '';

    const filledCount = closeTicketSlots.filter(s => s !== null).length;
    document.getElementById('cm-slot-count-badge').textContent = filledCount;

    closeTicketSlots.forEach((file, index) => {
        const slotEl = document.createElement('div');
        const isFilled = file !== null;
        const isActive = activeSlotIndex === index;

        slotEl.style.cssText = `
            position: relative;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            box-sizing: border-box;
            background: ${isFilled ? '#000' : '#FFFFFF'};
            border: ${isActive ? '2px solid #4F46E5' : (isFilled ? '2px solid #10B981' : '2px dashed #CBD5E1')};
            box-shadow: ${isActive ? '0 0 0 3px rgba(79, 70, 229, 0.2)' : 'none'};
        `;

        // Badge Nomor Slot di Kiri Atas
        const badge = document.createElement('div');
        badge.style.cssText = `
            position: absolute;
            top: 5px;
            left: 5px;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            z-index: 2;
            background: ${isActive ? '#4F46E5' : (isFilled ? '#10B981' : '#E2E8F0')};
            color: ${isActive || isFilled ? '#FFFFFF' : '#475569'};
        `;
        badge.textContent = `Foto ${index + 1}` + (isActive ? ' ✦' : '');
        slotEl.appendChild(badge);

        if (isFilled) {
            // Tampilan Foto Terisi
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; opacity: 0.9;';
            img.alt = `Foto ${index + 1}`;
            slotEl.appendChild(img);

            // Overlay Hover "Ganti Foto"
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: absolute;
                inset: 0;
                background: rgba(0,0,0,0.45);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 11px;
                font-weight: 600;
                opacity: 0;
                transition: opacity 0.2s;
            `;
            overlay.innerHTML = `<span>Ganti Foto</span>`;
            slotEl.onmouseenter = () => { overlay.style.opacity = '1'; };
            slotEl.onmouseleave = () => { overlay.style.opacity = '0'; };
            slotEl.appendChild(overlay);

            // Tombol Hapus ✕
            const btnDelete = document.createElement('button');
            btnDelete.type = 'button';
            btnDelete.innerHTML = '✕';
            btnDelete.title = 'Hapus foto dari slot ini';
            btnDelete.style.cssText = `
                position: absolute;
                top: 5px;
                right: 5px;
                width: 20px;
                height: 20px;
                background: #EF4444;
                color: #FFFFFF;
                border: none;
                border-radius: 50%;
                font-size: 11px;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 3;
                box-shadow: 0 1px 3px rgba(0,0,0,0.3);
            `;
            btnDelete.onclick = (e) => {
                e.stopPropagation();
                closeTicketSlots[index] = null;
                activeSlotIndex = index;
                renderSlots();
            };
            slotEl.appendChild(btnDelete);

            // Klik slot terisi untuk menjadikannya target ganti foto via file dialog atau Ctrl+V
            slotEl.onclick = () => {
                activeSlotIndex = index;
                renderSlots();
                triggerSlotFileInput(index);
            };

        } else {
            // Tampilan Slot Kosong (Placeholder)
            slotEl.innerHTML += `
                <div style="text-align: center; padding: 8px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="${isActive ? '#4F46E5' : '#94A3B8'}" stroke-width="1.8" style="margin-bottom: 4px;">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <div style="font-size: 11px; font-weight: 600; color: ${isActive ? '#4F46E5' : '#475569'};">
                        ${isActive ? 'Target Tempel' : 'Kosong'}
                    </div>
                    <div style="font-size: 10px; color: #94A3B8; margin-top: 2px;">
                        Ctrl+V / Klik
                    </div>
                </div>
            `;

            slotEl.onclick = () => {
                activeSlotIndex = index;
                renderSlots();
                triggerSlotFileInput(index);
            };
        }

        grid.appendChild(slotEl);
    });
}

function triggerSlotFileInput(index) {
    activeSlotIndex = index;
    const input = document.getElementById('cm-slot-file-input');
    input.value = '';
    input.click();
}

async function handleSlotFileChange(files) {
    if (!files || !files.length) return;
    const file = files[0];
    if (!file.type.match(/^image\/(jpeg|png|webp)$/)) {
        Swal.fire({ icon: 'error', title: 'Format Tidak Sesuai', text: 'Pilih gambar format JPG, PNG, atau WebP.' });
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        Swal.fire({ icon: 'error', title: 'Ukuran Terlalu Besar', text: 'Ukuran file maksimal 10 MB.' });
        return;
    }

    const compressed = await compressSlotImage(file);
    closeTicketSlots[activeSlotIndex] = compressed;

    // Geser fokus ke slot kosong berikutnya jika ada
    const nextEmpty = closeTicketSlots.findIndex(s => s === null);
    if (nextEmpty !== -1) {
        activeSlotIndex = nextEmpty;
    }

    renderSlots();
}

/**
 * Event Listener Global untuk Paste (Ctrl+V)
 * Mengisi atau menimpa slot foto secara instan
 */
document.addEventListener('paste', async function(e) {
    const modal = document.getElementById('close-ticket-modal');
    if (!modal || modal.style.display === 'none' || !modal.classList.contains('open')) {
        return;
    }

    const items = e.clipboardData?.items;
    if (!items || !items.length) return;

    for (const item of items) {
        if (item.type.startsWith('image/')) {
            e.preventDefault();
            const blob = item.getAsFile();
            if (!blob) continue;

            // Cari target slot:
            // 1. Jika activeSlotIndex saat ini kosong, gunakan activeSlotIndex
            // 2. Jika activeSlotIndex terisi, cari slot kosong pertama
            // 3. Jika semua 3 slot sudah terisi, timpa slot activeSlotIndex yang sedang dipilih
            let targetIndex = activeSlotIndex;
            if (closeTicketSlots[targetIndex] !== null) {
                const firstEmpty = closeTicketSlots.findIndex(s => s === null);
                if (firstEmpty !== -1) {
                    targetIndex = firstEmpty;
                }
            }

            const compressed = await compressSlotImage(blob);
            closeTicketSlots[targetIndex] = compressed;

            // Animasi visual feedback
            activeSlotIndex = targetIndex;
            renderSlots();

            // Pindahkan slot aktif berikutnya ke yang masih kosong jika ada
            const nextEmpty = closeTicketSlots.findIndex(s => s === null);
            if (nextEmpty !== -1) {
                activeSlotIndex = nextEmpty;
                setTimeout(() => renderSlots(), 150);
            }

            // Tampilkan pesan mini toast
            const statusDiv = document.getElementById('cm-ai-status');
            statusDiv.style.display = 'block';
            statusDiv.style.background = '#F0FDF4';
            statusDiv.style.border = '1px solid #BBF7D0';
            statusDiv.style.color = '#15803D';
            statusDiv.innerHTML = `✓ Foto berhasil ditempel (Ctrl+V) ke <strong>Foto ${targetIndex + 1}</strong>!`;
            setTimeout(() => {
                if (statusDiv.innerHTML.includes('berhasil ditempel')) {
                    statusDiv.style.display = 'none';
                }
            }, 3000);

            break; // 1 gambar per event paste
        }
    }
});

function hideAiBadges() {
    ['solusi', 'tindakan', 'rootcause'].forEach(key => {
        const badge = document.getElementById(`badge-ai-${key}`);
        if (badge) badge.style.display = 'none';
    });
}

function resetAiStatus() {
    const statusDiv = document.getElementById('cm-ai-status');
    statusDiv.style.display = 'none';
    statusDiv.innerHTML = '';
}

/**
 * Trigger Analisis Gemini AI pada foto-foto yang ada di slot
 */
async function analyzeCloseModalImages() {
    const validFiles = closeTicketSlots.filter(f => f !== null);

    if (validFiles.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Slot Foto Masih Kosong',
            text: 'Silakan tempel foto dengan Ctrl+V atau klik slot placeholder untuk melampirkan minimal 1 foto bukti penyelesaian.'
        });
        return;
    }

    const btn = document.getElementById('btn-cm-ai-analyze');
    const statusDiv = document.getElementById('cm-ai-status');
    
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner" style="width:12px;height:12px;border-width:2px;display:inline-block;margin-right:4px;"></span> Menganalisis...`;
    
    statusDiv.style.display = 'block';
    statusDiv.style.background = '#EEF2FF';
    statusDiv.style.border = '1px solid #C7D2FE';
    statusDiv.style.color = '#3730A3';
    statusDiv.innerHTML = `✦ Sedang menganalisis ${validFiles.length} foto bukti dengan AI...`;

    const fd = new FormData();
    validFiles.forEach(file => {
        fd.append('images[]', file);
    });

    try {
        const resp = await fetch(`{{ url('/tickets') }}/${currentCloseTicketId}/analyze-resolution`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            },
            body: fd
        });

        const json = await resp.json();
        if (!resp.ok || !json.success) {
            throw new Error(json.message || 'Gagal menganalisis gambar.');
        }

        const data = json.data || {};

        // Helper untuk memeriksa apakah field masih bernilai kosong atau placeholder
        const isUnfilled = (val) => {
            if (!val) return true;
            const clean = val.trim().toLowerCase();
            return clean === '' || clean === '-' || clean === 'belum diselesaikan' || 
                   clean === 'belum ada solusi' || clean === 'belum diisi' || 
                   clean === 'belum diidentifikasi' || clean === 'belum ada tindakan';
        };

        const applyAiField = (elementId, badgeId, aiValue) => {
            if (!aiValue) return;
            const el = document.getElementById(elementId);
            const badge = document.getElementById(badgeId);
            
            // Isi jika belum terisi, atau masih placeholder seperti '-' atau 'belum diselesaikan', atau isi langsung
            if (isUnfilled(el.value) || el.value.trim() === '') {
                el.value = aiValue;
            } else {
                el.value = aiValue;
            }

            // Animasi highlight glow
            el.classList.remove('ai-highlight-glow');
            void el.offsetWidth; // trigger reflow
            el.classList.add('ai-highlight-glow');

            if (badge) badge.style.display = 'inline-block';
        };

        applyAiField('cm-solusi', 'badge-ai-solusi', data.solusi_diberikan);
        applyAiField('cm-tindakan', 'badge-ai-tindakan', data.tindakan_dilakukan);
        applyAiField('cm-rootcause', 'badge-ai-rootcause', data.root_cause);

        // Jika AI mendeteksi tanggal penyelesaian tertentu
        if (data.tanggal_penyelesaian) {
            const tglInput = document.getElementById('cm-tgl-penyelesaian');
            if (tglInput && !tglInput.value) {
                tglInput.value = data.tanggal_penyelesaian;
            }
        }

        statusDiv.style.background = '#F0FDF4';
        statusDiv.style.border = '1px solid #BBF7D0';
        statusDiv.style.color = '#15803D';
        statusDiv.innerHTML = `✓ <strong>Analisis Selesai!</strong> Form penyelesaian berhasil dilengkapi oleh AI. Silakan periksa atau sesuaikan sebelum menutup tiket.`;

    } catch (err) {
        statusDiv.style.background = '#FEF2F2';
        statusDiv.style.border = '1px solid #FECACA';
        statusDiv.style.color = '#991B1B';
        statusDiv.innerHTML = `✕ Gagal menganalisis: ${err.message}`;
    } finally {
        btn.disabled = false;
        const count = closeTicketSlots.filter(s => s !== null).length;
        btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg> <span>✦ Analisis Ulang (${count}/3)</span>`;
    }
}

/**
 * Kirim request Tutup Tiket beserta Form Penyelesaian & Gambar Tambahan dari 3 Slot
 */
async function submitCloseTicket() {
    const tgl = document.getElementById('cm-tgl-penyelesaian').value;
    if (!tgl) {
        Swal.fire({ icon: 'warning', title: 'Tanggal Kosong', text: 'Harap tentukan tanggal penyelesaian tiket.' });
        return;
    }

    const btnSubmit = document.getElementById('btn-cm-submit');
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = `<span class="spinner" style="width:13px;height:13px;border-width:2px;display:inline-block;margin-right:6px;"></span> Menyimpan...`;

    const fd = new FormData();
    fd.append('tanggal_penyelesaian', tgl);
    fd.append('solusi_diberikan', document.getElementById('cm-solusi').value);
    fd.append('tindakan_dilakukan', document.getElementById('cm-tindakan').value);
    fd.append('root_cause', document.getElementById('cm-rootcause').value);

    // Ambil semua foto dari 3 slot yang terisi
    const validFiles = closeTicketSlots.filter(s => s !== null);
    validFiles.forEach(file => {
        fd.append('lampiran_gambar[]', file);
    });

    try {
        const resp = await fetch(`{{ url('/tickets') }}/${currentCloseTicketId}/close`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            },
            body: fd
        });

        const json = await resp.json();
        if (!resp.ok || !json.success) {
            throw new Error(json.message || 'Gagal menutup tiket.');
        }

        closeCloseTicketModal();

        Swal.fire({
            icon: 'success',
            title: 'Tiket Berhasil Ditutup',
            text: json.message || `Tiket ${currentCloseTicketNomor} telah ditandai sebagai Closed.`,
            timer: 1800,
            showConfirmButton: false
        }).then(() => {
            location.reload();
        });

    } catch (err) {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 4px;"><polyline points="20 6 9 17 4 12"/></svg> Ya, Tutup Tiket`;
        Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: err.message });
    }
}
</script>
