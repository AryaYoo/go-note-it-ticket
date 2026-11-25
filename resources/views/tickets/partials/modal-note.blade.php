{{-- Modal Catatan Internal Tiket --}}
<div class="filter-modal-backdrop" id="ticket-note-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.45); backdrop-filter:blur(3px); z-index:9999; align-items:center; justify-content:center;" onmousedown="noteModalMouseDownTarget = event.target" onmouseup="handleNoteModalMouseUp(event)">
    <div class="filter-modal-content" style="background:#fff; border-radius:14px; width:100%; max-width:520px; box-shadow:0 20px 45px rgba(0,0,0,0.18); overflow:hidden; animation:noteModalPop .2s cubic-bezier(0.16,1,0.3,1);" onclick="event.stopPropagation()">
        
        {{-- Header Modal --}}
        <div style="background:#FFFBEB; border-bottom:1px solid #FEF3C7; padding:16px 20px; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:36px; height:36px; border-radius:10px; background:#FEF3C7; color:#D97706; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14,2 14,8 20,8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10,9 9,9 8,9"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:15px; font-weight:700; color:#92400E; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span>Catatan Tiket</span>
                        <span id="nm-nomor-badge" class="ticket-no" style="font-size:12px; font-weight:600; background:#FDE68A; color:#78350F; padding:2px 8px; border-radius:5px; border:1px solid #FCD34D;">-</span>
                    </div>
                    <div style="font-size:12px; color:#A16207; margin-top:2px;">
                        Tulis atau perbarui catatan internal untuk memantau progres tiket ini
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close-modal" onclick="closeNoteModal()" title="Tutup" style="background:none; border:none; font-size:18px; color:#92400E; cursor:pointer; padding:4px 8px; border-radius:6px; line-height:1;">✕</button>
        </div>

        {{-- Body Modal --}}
        <div style="padding:20px;">
            <div style="margin-bottom:8px;">
                <label for="nm-catatan-input" style="display:block; font-size:13px; font-weight:600; color:var(--text); margin-bottom:6px;">
                    Isi Catatan / Progres
                </label>
                <textarea 
                    id="nm-catatan-input" 
                    rows="4" 
                    placeholder="Tulis catatan tiket di sini (misal: Menunggu sparepart dikirim vendor, sudah dikoordinasikan dengan toko, dll)..." 
                    style="width:100%; border-radius:8px; border:1.5px solid var(--border); padding:10px 12px; font-size:13.5px; line-height:1.5; resize:vertical; box-sizing:border-box; outline:none; transition:border-color 0.15s; font-family:inherit;"
                    onfocus="this.style.borderColor='#D97706'; this.style.boxShadow='0 0 0 3px rgba(217,119,6,0.12)';"
                    onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none';"
                ></textarea>
            </div>

            {{-- Template / Quick Tags --}}
            <div style="margin-bottom:14px;">
                <div style="font-size:11px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">
                    Pilihan Cepat (Klik untuk menyisipkan):
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                    <button type="button" onclick="insertNoteTag('Menunggu konfirmasi dari pihak toko.')" style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:15px; padding:3px 10px; font-size:11.5px; color:#4B5563; cursor:pointer; transition:all 0.15s;" onmouseover="this.style.borderColor='#D97706'; this.style.color='#92400E';" onmouseout="this.style.borderColor='#E5E7EB'; this.style.color='#4B5563';">+ Konfirmasi Toko</button>
                    <button type="button" onclick="insertNoteTag('Sparepart sedang dalam proses pemesanan/pengiriman.')" style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:15px; padding:3px 10px; font-size:11.5px; color:#4B5563; cursor:pointer; transition:all 0.15s;" onmouseover="this.style.borderColor='#D97706'; this.style.color='#92400E';" onmouseout="this.style.borderColor='#E5E7EB'; this.style.color='#4B5563';">+ Tunggu Sparepart</button>
                    <button type="button" onclick="insertNoteTag('Sedang ditangani oleh vendor/pihak ketiga.')" style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:15px; padding:3px 10px; font-size:11.5px; color:#4B5563; cursor:pointer; transition:all 0.15s;" onmouseover="this.style.borderColor='#D97706'; this.style.color='#92400E';" onmouseout="this.style.borderColor='#E5E7EB'; this.style.color='#4B5563';">+ Vendor Eksternal</button>
                    <button type="button" onclick="insertNoteTag('Perbaikan sementara selesai, dalam pemantauan berkala.')" style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:15px; padding:3px 10px; font-size:11.5px; color:#4B5563; cursor:pointer; transition:all 0.15s;" onmouseover="this.style.borderColor='#D97706'; this.style.color='#92400E';" onmouseout="this.style.borderColor='#E5E7EB'; this.style.color='#4B5563';">+ Pemantauan Berkala</button>
                </div>
            </div>

            {{-- Info Alert --}}
            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:7px; padding:8px 12px; display:flex; align-items:flex-start; gap:8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" style="flex-shrink:0; margin-top:2px;">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <span style="font-size:12px; color:#475569; line-height:1.4;">
                    Catatan ini akan langsung tampil di kartu tiket pada daftar riwayat tiket agar tim mudah membaca tindak lanjut kendala.
                </span>
            </div>
            
            <div id="nm-error" style="display:none; color:#DC2626; font-size:12px; margin-top:10px; background:#FEF2F2; padding:7px 10px; border-radius:6px; border:1px solid #FECACA;"></div>
        </div>

        {{-- Footer Modal --}}
        <div style="display:flex; justify-content:flex-end; gap:10px; padding:14px 20px; background:#F9FAFB; border-top:1px solid var(--border);">
            <button type="button" class="btn btn-secondary" onclick="closeNoteModal()" style="font-size:13px; font-weight:600; padding:8px 16px;">
                Batal
            </button>
            <button type="button" id="btn-save-note" onclick="submitTicketNote()" class="btn" style="background:#D97706; color:#fff; border:none; font-size:13px; font-weight:600; padding:8px 20px; border-radius:7px; display:inline-flex; align-items:center; gap:6px; cursor:pointer; box-shadow:0 1px 2px rgba(217,119,6,0.3);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Simpan Catatan</span>
            </button>
        </div>

    </div>
</div>

<style>
@keyframes noteModalPop {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>

<script>
let currentNoteTicketId = null;
let currentNoteTicketNomor = null;
let noteModalMouseDownTarget = null;

function handleNoteModalMouseUp(event) {
    if (event.target === document.getElementById('ticket-note-modal') && noteModalMouseDownTarget === event.target) {
        closeNoteModal();
    }
}

function openNoteModal(id, nomor, currentNote = '') {
    currentNoteTicketId = id;
    currentNoteTicketNomor = nomor;

    const modal = document.getElementById('ticket-note-modal');
    const badge = document.getElementById('nm-nomor-badge');
    const input = document.getElementById('nm-catatan-input');
    const errorBox = document.getElementById('nm-error');
    const btnSave = document.getElementById('btn-save-note');

    if (badge) badge.textContent = nomor;
    if (input) input.value = currentNote || '';
    if (errorBox) { errorBox.style.display = 'none'; errorBox.textContent = ''; }
    if (btnSave) {
        btnSave.disabled = false;
        btnSave.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> <span>Simpan Catatan</span>`;
    }

    if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => {
            if (input) {
                input.focus();
                input.setSelectionRange(input.value.length, input.value.length);
            }
        }, 80);
    }
}

function closeNoteModal() {
    const modal = document.getElementById('ticket-note-modal');
    if (modal) modal.style.display = 'none';
    currentNoteTicketId = null;
    currentNoteTicketNomor = null;
}

function insertNoteTag(text) {
    const input = document.getElementById('nm-catatan-input');
    if (!input) return;
    if (input.value.trim() === '') {
        input.value = text;
    } else {
        input.value += (input.value.endsWith(' ') ? '' : ' ') + text;
    }
    input.focus();
}

async function submitTicketNote() {
    if (!currentNoteTicketId) return;

    const input = document.getElementById('nm-catatan-input');
    const errorBox = document.getElementById('nm-error');
    const btnSave = document.getElementById('btn-save-note');
    const noteText = input ? input.value : '';

    if (errorBox) { errorBox.style.display = 'none'; errorBox.textContent = ''; }
    if (btnSave) {
        btnSave.disabled = true;
        btnSave.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 10 10"/></svg> <span>Menyimpan...</span>`;
    }

    try {
        const resp = await fetch(`{{ url('/tickets') }}/${currentNoteTicketId}/note`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ catatan: noteText })
        });

        const json = await resp.json();
        if (!resp.ok || !json.success) {
            throw new Error(json.message || 'Gagal menyimpan catatan tiket.');
        }

        closeNoteModal();

        Swal.fire({
            icon: 'success',
            title: 'Catatan Tersimpan',
            text: json.message || `Catatan tiket ${currentNoteTicketNomor} berhasil diperbarui.`,
            timer: 1300,
            showConfirmButton: false
        }).then(() => {
            location.reload();
        });

    } catch (err) {
        if (btnSave) {
            btnSave.disabled = false;
            btnSave.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> <span>Simpan Catatan</span>`;
        }
        if (errorBox) {
            errorBox.style.display = 'block';
            errorBox.textContent = err.message || 'Terjadi kesalahan saat menyimpan catatan.';
        }
    }
}

// Support menutup modal dengan tombol Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('ticket-note-modal');
        if (modal && modal.style.display === 'flex') {
            closeNoteModal();
        }
    }
});
</script>
