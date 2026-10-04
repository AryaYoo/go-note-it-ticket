@extends('layouts.app')
@section('title', 'Dokumentasi')
@section('page-title', 'Dokumentasi')

@push('head')
<style>
    html, body { height: 100%; overflow: hidden; }
    .app-layout  { height: 100%; min-height: unset; }
    .main-content { height: 100%; min-height: unset; overflow: hidden; }
    .content-area {
        padding: 0 !important;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .doc-layout {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 20px;
        box-sizing: border-box;
        overflow: hidden;
    }

    /* Chat window */
    .chat-window {
        flex: 1;
        overflow-y: auto;
        padding: 4px 0 12px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        min-height: 0;
    }

    /* Empty state */
    .chat-empty {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--muted);
        font-size: 13px;
        text-align: center;
        line-height: 1.6;
    }

    /* Message rows */
    .msg-row { display: flex; gap: 8px; align-items: flex-end; }
    .msg-row.user-row {
        flex-direction: row-reverse;
    }
    .msg-row.anim-enter {
        animation: aiMsgIn 0.32s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .msg-row.user-row.anim-enter {
        animation: userMsgIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes aiMsgIn {
        0% {
            opacity: 0;
            transform: translateY(10px) scale(0.98);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes userMsgIn {
        0% {
            opacity: 0;
            transform: translateY(6px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulseHighlight {
        0%, 100% { filter: none; }
        50% { filter: brightness(0.93) drop-shadow(0 0 6px rgba(37,99,235,0.35)); }
    }
    .highlight-flash {
        animation: pulseHighlight 1.4s ease-in-out;
    }

    .msg-avatar {
        width: 26px; height: 26px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 10px; font-weight: 700;
        flex-shrink: 0; margin-bottom: 2px;
    }
    .ai-avatar   { background: var(--primary); color: #fff; }
    .user-avatar { background: #374151; color: #fff; }

    .msg-bubble {
        max-width: 78%;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 13px;
        line-height: 1.65;
    }
    .msg-bubble.user {
        background: var(--primary);
        color: #fff;
        border-bottom-right-radius: 4px;
    }
    .msg-bubble.ai {
        background: #fff;
        border: 1px solid var(--border);
        color: var(--text);
        border-bottom-left-radius: 4px;
    }
    .msg-bubble.ai strong { font-weight: 600; }
    .msg-bubble.ai ul, .msg-bubble.ai ol { padding-left: 18px; margin: 5px 0; }
    .msg-bubble.ai li { margin: 2px 0; }

    /* Bubble Images */
    .user-bubble-images {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 6px;
    }
    .user-bubble-img-link {
        display: block;
        width: 64px;
        height: 64px;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.3);
        background: rgba(0,0,0,0.1);
        transition: transform 120ms, opacity 120ms;
        flex-shrink: 0;
    }
    .user-bubble-img-link:hover {
        transform: scale(1.04);
        opacity: 0.95;
    }
    .user-bubble-img-link img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* Citations */
    .citations-wrap { margin-top: 10px; display: flex; flex-direction: column; gap: 5px; }
    .citations-label {
        font-size: 11px; font-weight: 600;
        color: var(--muted); text-transform: uppercase;
        letter-spacing: 0.4px; margin-bottom: 2px;
    }
    .citation-btn {
        display: flex; align-items: center; gap: 8px;
        padding: 7px 11px;
        background: #F8FAFF; border: 1px solid #DBEAFE;
        border-radius: 6px; text-decoration: none;
        color: var(--primary); font-size: 12px;
        transition: background 120ms, border-color 120ms;
    }
    .citation-btn:hover { background: #EFF6FF; border-color: #93C5FD; }
    .citation-btn-nomor { font-weight: 700; font-size: 11.5px; white-space: nowrap; }
    .citation-btn-desc  { color: var(--muted); font-size: 11.5px; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .citation-badge { font-size: 10px; padding: 2px 6px; border-radius: 3px; font-weight: 500; white-space: nowrap; }
    .citation-badge.open     { background: #E0F2FE; color: #0891B2; }
    .citation-badge.closed   { background: #F0FDF4; color: #16A34A; }
    .citation-badge.eskalasi { background: #FEF2F2; color: #DC2626; }

    /* Typing */
    .typing-indicator {
        display: flex; align-items: center; gap: 4px;
        padding: 11px 14px;
        background: #fff; border: 1px solid var(--border);
        border-radius: 12px; border-bottom-left-radius: 4px;
        width: fit-content;
    }
    .typing-dot {
        width: 6px; height: 6px; background: #9CA3AF;
        border-radius: 50%; animation: tbounce 1.4s infinite;
    }
    .typing-dot:nth-child(2) { animation-delay: .2s; }
    .typing-dot:nth-child(3) { animation-delay: .4s; }
    @keyframes tbounce {
        0%,80%,100% { transform: translateY(0); opacity: .5; }
        40% { transform: translateY(-4px); opacity: 1; }
    }

    /* Error */
    .msg-error {
        background: #FEF2F2; border: 1px solid #FECACA;
        color: #DC2626; padding: 9px 13px; border-radius: 8px;
        font-size: 12.5px;
    }

    /* Input & Image Previews */
    .chat-input-area { padding-top: 10px; border-top: 1px solid var(--border); }

    .image-preview-strip {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }
    .img-preview-card {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 8px 4px 6px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 7px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        animation: userMsgIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .img-preview-card img {
        width: 32px;
        height: 32px;
        border-radius: 4px;
        object-fit: cover;
        background: #F3F4F6;
        border: 1px solid var(--border);
    }
    .img-preview-info {
        display: flex;
        flex-direction: column;
        max-width: 140px;
    }
    .img-preview-name {
        font-size: 11px;
        font-weight: 500;
        color: var(--text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .img-preview-size {
        font-size: 10px;
        color: var(--muted);
    }
    .compressed-tag {
        color: #059669;
        font-weight: 600;
    }
    .img-preview-del {
        background: #FEE2E2;
        color: #DC2626;
        border: none;
        border-radius: 4px;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        cursor: pointer;
        font-weight: 700;
        margin-left: 2px;
        line-height: 1;
    }
    .img-preview-del:hover {
        background: #FCA5A5;
    }

    .chat-input-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        padding: 6px 8px 6px 10px;
        transition: border-color 200ms;
    }
    .chat-input-wrap:focus-within {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37,99,235,.07);
    }

    .btn-attach {
        background: none;
        border: none;
        color: var(--muted);
        cursor: pointer;
        padding: 6px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: color 150ms, background 150ms;
    }
    .btn-attach:hover {
        color: var(--primary);
        background: #EFF6FF;
    }

    #chat-input {
        flex: 1; border: none; outline: none;
        font-family: 'Inter', system-ui, sans-serif;
        font-size: 13.5px; color: var(--text);
        resize: none; background: transparent;
        max-height: 120px; line-height: 1.5;
        padding: 4px 0; margin: 0; display: block;
        box-sizing: border-box;
    }
    #chat-input::placeholder { color: var(--muted); }

    #send-btn {
        width: 32px; height: 32px;
        background: var(--primary); border: none; border-radius: 7px;
        color: #fff; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: background 150ms, transform 100ms;
    }
    #send-btn:hover:not(:disabled) { background: var(--primary-hover); }
    #send-btn:active:not(:disabled) { transform: scale(.93); }
    #send-btn:disabled { background: #D1D5DB; cursor: not-allowed; }

    /* Modal styles */
    .custom-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1000;
        background: rgba(17, 24, 39, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .custom-modal-backdrop.hidden {
        display: none !important;
    }
    .custom-modal {
        background: #fff;
        border-radius: 8px;
        width: 100%;
        max-width: 520px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        overflow: hidden;
        animation: modalFadeIn 0.15s ease-out;
        display: flex;
        flex-direction: column;
    }
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.97); }
        to { opacity: 1; transform: scale(1); }
    }
    .custom-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border);
    }
    .custom-modal-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--text);
        margin: 0;
    }
    .custom-modal-close {
        background: none;
        border: none;
        font-size: 20px;
        color: var(--muted);
        cursor: pointer;
        padding: 2px 6px;
        line-height: 1;
        border-radius: 4px;
    }
    .custom-modal-close:hover {
        color: var(--text);
        background: var(--border);
    }
    .custom-modal-body {
        padding: 16px 18px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .custom-modal-footer {
        padding: 12px 18px;
        background: #F9FAFB;
        border-top: 1px solid var(--border);
    }

    /* History item styles */
    .history-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px;
        background: #F8FAFC;
        border: 1px solid var(--border);
        border-radius: 6px;
        cursor: pointer;
        transition: background 120ms, border-color 120ms;
    }
    .history-item:hover {
        background: #EFF6FF;
        border-color: #BFDBFE;
    }
    .history-item-content {
        flex: 1;
        min-width: 0;
    }
    .history-item-q {
        font-size: 12.5px;
        font-weight: 500;
        color: var(--text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .history-item-meta {
        font-size: 11px;
        color: var(--muted);
        margin-top: 2px;
    }
    .history-item-del {
        background: none;
        border: none;
        color: #9CA3AF;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 4px;
        font-size: 13px;
        line-height: 1;
    }
    .history-item-del:hover {
        color: #DC2626;
        background: #FEE2E2;
    }
</style>
@endpush

@section('content')
<div class="doc-layout">

    {{-- Page Header --}}
    <div class="page-header" style="flex-shrink: 0; margin-bottom: 14px;">
        <div>
            <h1>Dokumentasi</h1>
            <div class="page-header-sub">Tanya jawab AI seputar kendala IT & solusi tiket</div>
        </div>
        <div class="flex gap-8">
            <button type="button" class="btn btn-secondary btn-sm" id="btn-chat-history" onclick="openHistoryModal()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                Riwayat Chat
            </button>
        </div>
    </div>

    {{-- Chat Window --}}
    <div class="chat-window" id="chat-window">
        @if($chats->isEmpty())
            <div class="chat-empty" id="chat-empty">
                <div>
                    <div>Tanyakan kendala IT, AI akan mencari referensi dari riwayat tiket.</div>
                    <div style="font-size: 11.5px; color: #9CA3AF; margin-top: 4px;">Anda juga dapat melampirkan screenshot percakapan WhatsApp via <strong>Ctrl+V</strong> (maks. 3 gambar).</div>
                </div>
            </div>
        @else
            @foreach($chats as $c)
                {{-- User bubble --}}
                <div class="msg-row user-row" data-chat-id="{{ $c->id }}">
                    <div class="msg-avatar user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                    <div class="msg-bubble user">
                        @if(!empty($c->images))
                            <div class="user-bubble-images">
                                @foreach(array_keys($c->images) as $idx)
                                    <a href="{{ route('documentation.image', ['chat' => $c->id, 'index' => $idx]) }}" target="_blank" class="user-bubble-img-link" title="Lihat Screenshot">
                                        <img src="{{ route('documentation.image', ['chat' => $c->id, 'index' => $idx]) }}" alt="Screenshot WhatsApp">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        <div>{{ $c->question }}</div>
                    </div>
                </div>

                {{-- AI bubble --}}
                <div class="msg-row ai-row" data-chat-id="{{ $c->id }}">
                    <div class="msg-avatar ai-avatar">AI</div>
                    <div class="msg-bubble ai">
                        <div>{!! \Illuminate\Support\Str::markdown($c->answer ?? '') !!}</div>
                        @if(!empty($c->citations))
                            <div class="citations-wrap">
                                <div class="citations-label">Referensi Tiket</div>
                                @foreach($c->citations as $cite)
                                    @php
                                        $bc = ($cite['status'] ?? '') === 'Closed' ? 'closed' : (($cite['status'] ?? '') === 'Eskalasi' ? 'eskalasi' : 'open');
                                    @endphp
                                    <a href="{{ $cite['url'] ?? '#' }}" class="citation-btn" target="_blank">
                                        <span class="citation-btn-nomor">{{ $cite['nomor_tiket'] ?? '' }}</span>
                                        <span class="citation-btn-desc">{{ $cite['deskripsi'] ?? '' }}</span>
                                        <span class="citation-badge {{ $bc }}">{{ $cite['status'] ?? '' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Chat Input Bar & Image Preview --}}
    <div class="chat-input-area">
        {{-- Preview gambar yang dilampirkan via Ctrl+V atau tombol klip --}}
        <div id="image-preview-strip" class="image-preview-strip" style="display: none;"></div>

        <div class="chat-input-wrap">
            {{-- Tombol Lampirkan Gambar --}}
            <input type="file" id="image-file-input" accept="image/*" multiple style="display: none;">
            <button type="button" class="btn-attach" id="btn-attach" onclick="document.getElementById('image-file-input').click()" title="Lampirkan screenshot WhatsApp (atau tekan Ctrl+V)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                </svg>
            </button>

            <textarea id="chat-input" placeholder="Ketik pertanyaan atau tekan Ctrl+V untuk melampirkan screenshot WhatsApp…" rows="1" autocomplete="off"></textarea>
            
            <button id="send-btn" title="Kirim" disabled>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </div>
    </div>
</div>

{{-- Modal Riwayat Chat --}}
<div id="history-modal" class="custom-modal-backdrop hidden" onclick="handleHistoryBackdrop(event)">
    <div class="custom-modal" onclick="event.stopPropagation()">
        <div class="custom-modal-header">
            <div>
                <h3 class="custom-modal-title">Riwayat Chat</h3>
                <div style="font-size: 11.5px; color: var(--muted); margin-top: 2px;">Daftar pertanyaan dan referensi sebelumnya</div>
            </div>
            <button type="button" onclick="closeHistoryModal()" class="custom-modal-close" title="Tutup">&times;</button>
        </div>
        <div class="custom-modal-body" id="history-list" style="max-height: 380px; overflow-y: auto; padding: 14px 18px; gap: 8px;">
            <!-- Diisi via JavaScript -->
        </div>
        <div class="custom-modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
            <button type="button" onclick="clearAllHistory()" class="btn btn-danger btn-sm" id="btn-clear-history" style="display: none;">
                Hapus Semua
            </button>
            <div style="margin-left: auto;">
                <button type="button" onclick="closeHistoryModal()" class="btn btn-secondary btn-sm">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;
const chatWindow  = document.getElementById('chat-window');
const chatInput   = document.getElementById('chat-input');
const sendBtn     = document.getElementById('send-btn');
const imageInput  = document.getElementById('image-file-input');
const previewStrip= document.getElementById('image-preview-strip');
const userInitial = '{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}';

// State lampiran gambar (maks. 3)
let selectedFiles = [];

// Inisialisasi riwayat chat dari database
let chatHistory = @js($chats->map(fn($c) => [
    'id'         => $c->id,
    'question'   => $c->question,
    'answer'     => $c->answer,
    'citations'  => $c->citations ?? [],
    'images'     => !empty($c->images) ? array_map(fn($idx) => route('documentation.image', ['chat' => $c->id, 'index' => $idx]), array_keys($c->images)) : [],
    'created_at' => $c->created_at->toISOString(),
]));

// Auto scroll ke bawah saat pertama kali dibuka
scrollBot();

// Format ukuran file
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

// Kompresi gambar client-side via HTML5 Canvas (seperti di Buat Tiket)
async function compressImage(file, maxWidth = 1600, quality = 0.8) {
    if (!file.type.startsWith('image/') || file.type === 'image/svg+xml') {
        return file;
    }

    return new Promise((resolve) => {
        const img = new Image();
        const objectUrl = URL.createObjectURL(file);

        img.onload = () => {
            URL.revokeObjectURL(objectUrl);

            let width = img.width;
            let height = img.height;

            if (width > maxWidth) {
                height = Math.round((height * maxWidth) / width);
                width = maxWidth;
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, width, height);
            ctx.drawImage(img, 0, 0, width, height);

            canvas.toBlob((blob) => {
                if (!blob || blob.size >= file.size) {
                    return resolve(file);
                }

                const originalName = file.name || 'screenshot.png';
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

// Tambah file ke antrean lampiran (maks 3)
async function addFiles(files) {
    const arr = Array.from(files).filter(f => f.type.startsWith('image/'));
    if (!arr.length) return;

    for (const f of arr) {
        if (selectedFiles.length >= 3) {
            Swal.fire({
                icon: 'warning',
                title: 'Batas Maksimal',
                text: 'Maksimal 3 foto tangkapan layar WhatsApp yang dapat dilampirkan.',
                timer: 2200,
                showConfirmButton: false
            });
            break;
        }

        const processed = await compressImage(f);
        if (!selectedFiles.some(existing => existing.name === processed.name && existing.size === processed.size)) {
            selectedFiles.push(processed);
        }
    }
    renderPreviews();
    updateSendBtnState();
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    renderPreviews();
    updateSendBtnState();
}

function renderPreviews() {
    previewStrip.innerHTML = '';
    if (selectedFiles.length === 0) {
        previewStrip.style.display = 'none';
        return;
    }
    previewStrip.style.display = 'flex';

    selectedFiles.forEach((file, idx) => {
        const item = el('div', 'img-preview-card');
        const imgUrl = URL.createObjectURL(file);
        item.innerHTML = `
            <img src="${imgUrl}" alt="Preview ${idx + 1}">
            <div class="img-preview-info">
                <span class="img-preview-name" title="${esc(file.name)}">${esc(file.name)}</span>
                <span class="img-preview-size">${formatFileSize(file.size)} <span class="compressed-tag">(dikompres)</span></span>
            </div>
            <button type="button" class="img-preview-del" onclick="removeFile(${idx})" title="Hapus foto">&times;</button>
        `;
        previewStrip.appendChild(item);
    });
}

// Paste Listener (Ctrl+V)
document.addEventListener('paste', async (e) => {
    const items = e.clipboardData?.items;
    if (!items) return;
    const imgBlobs = [];
    for (const item of items) {
        if (item.type.startsWith('image/')) {
            const blob = item.getAsFile();
            if (blob) imgBlobs.push(blob);
        }
    }
    if (imgBlobs.length > 0) {
        await addFiles(imgBlobs);
    }
});

// File picker input change
imageInput.addEventListener('change', async (e) => {
    if (e.target.files) await addFiles(e.target.files);
    imageInput.value = '';
});

function updateSendBtnState() {
    const hasText = chatInput.value.trim().length >= 3;
    const hasImages = selectedFiles.length > 0;
    sendBtn.disabled = !(hasText || hasImages);
}

chatInput.addEventListener('input', () => {
    chatInput.style.height = 'auto';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 120) + 'px';
    updateSendBtnState();
});

chatInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); if (!sendBtn.disabled) sendMessage(); }
});

sendBtn.addEventListener('click', sendMessage);

async function sendMessage() {
    const rawQuestion = chatInput.value.trim();
    if (rawQuestion.length < 3 && selectedFiles.length === 0) return;

    const question = rawQuestion || 'Mohon analisis screenshot percakapan WhatsApp terlampir dan berikan solusinya.';

    const emptyEl = document.getElementById('chat-empty');
    if (emptyEl) emptyEl.remove();

    // URLs preview lokal untuk user bubble
    const localImgUrls = selectedFiles.map(f => URL.createObjectURL(f));

    appendUserBubble(question, null, true, localImgUrls);

    // Ambil snapshot file yang akan dikirim
    const filesToSend = [...selectedFiles];
    selectedFiles = [];
    renderPreviews();

    chatInput.value = '';
    chatInput.style.height = 'auto';
    updateSendBtnState();

    const typingId = appendTyping();

    const formData = new FormData();
    formData.append('question', question);
    filesToSend.forEach(f => formData.append('images[]', f));

    try {
        const res  = await fetch('{{ route("documentation.chat") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData,
        });
        const data = await res.json();
        removeTyping(typingId);
        if (data.success) {
            appendAiBubble(data.answer, data.citations, data.id, true);
            chatHistory.push({
                id: data.id,
                question: question,
                answer: data.answer,
                citations: data.citations || [],
                images: data.images || [],
                created_at: data.created_at || new Date().toISOString()
            });
            const lastUserRow = chatWindow.querySelector('.msg-row.user-row:last-of-type');
            if (lastUserRow && !lastUserRow.getAttribute('data-chat-id')) {
                lastUserRow.setAttribute('data-chat-id', data.id);
            }
        } else {
            appendError(data.message || 'Terjadi kesalahan.');
        }
    } catch {
        removeTyping(typingId);
        appendError('Gagal terhubung ke server.');
    }
}

function appendUserBubble(text, id = null, animate = false, images = []) {
    const row = el('div', 'msg-row user-row' + (animate ? ' anim-enter' : ''));
    if (id) row.setAttribute('data-chat-id', id);

    let imgHtml = '';
    if (images && images.length) {
        imgHtml = `<div class="user-bubble-images">` +
            images.map(url => `<a href="${esc(url)}" target="_blank" class="user-bubble-img-link" title="Lihat gambar"><img src="${esc(url)}" alt="Screenshot"></a>`).join('') +
            `</div>`;
    }

    row.innerHTML = `<div class="msg-avatar user-avatar">${userInitial}</div><div class="msg-bubble user">${imgHtml}<div>${esc(text)}</div></div>`;
    chatWindow.appendChild(row); scrollBot();
}

function appendAiBubble(answer, citations, id = null, animate = false) {
    const row = el('div', 'msg-row ai-row' + (animate ? ' anim-enter' : ''));
    if (id) row.setAttribute('data-chat-id', id);
    row.innerHTML = `
        <div class="msg-avatar ai-avatar">AI</div>
        <div class="msg-bubble ai">
            <div>${renderMd(answer)}</div>
            ${buildCitations(citations)}
        </div>`;
    chatWindow.appendChild(row); scrollBot();
}

function buildCitations(citations) {
    if (!citations || !citations.length) return '';
    let html = `<div class="citations-wrap"><div class="citations-label">Referensi Tiket</div>`;
    citations.forEach(c => {
        const bc = c.status === 'Closed' ? 'closed' : c.status === 'Eskalasi' ? 'eskalasi' : 'open';
        html += `<a href="${esc(c.url)}" class="citation-btn" target="_blank">
            <span class="citation-btn-nomor">${esc(c.nomor_tiket)}</span>
            <span class="citation-btn-desc">${esc(c.deskripsi)}</span>
            <span class="citation-badge ${bc}">${esc(c.status)}</span>
        </a>`;
    });
    return html + '</div>';
}

function appendError(msg) {
    const row = el('div', 'msg-row');
    row.innerHTML = `<div class="msg-avatar ai-avatar">AI</div><div class="msg-error">${esc(msg)}</div>`;
    chatWindow.appendChild(row); scrollBot();
}

let tc = 0;
function appendTyping() {
    const id = 'ty-' + (++tc);
    const row = el('div', 'msg-row'); row.id = id;
    row.innerHTML = `<div class="msg-avatar ai-avatar">AI</div><div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>`;
    chatWindow.appendChild(row); scrollBot(); return id;
}
function removeTyping(id) { const e = document.getElementById(id); if (e) e.remove(); }
function scrollBot() { chatWindow.scrollTop = chatWindow.scrollHeight; }
function el(tag, cls) { const e = document.createElement(tag); e.className = cls; return e; }
function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function renderMd(text) {
    if (!text) return '';
    let t = esc(text);
    t = t.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    t = t.replace(/\*(.+?)\*/g, '<em>$1</em>');
    t = t.replace(/^[\-•] (.+)/gm, '<li>$1</li>');
    t = t.replace(/^\d+\. (.+)/gm, '<li>$1</li>');
    t = t.replace(/(<li>.*<\/li>(\n|$))+/g, m => '<ul>' + m + '</ul>');
    t = t.replace(/\n/g, '<br>');
    return t;
}

// -------------------------------------------------------------
// History Management (Database-backed)
// -------------------------------------------------------------
function openHistoryModal() {
    renderHistoryList();
    document.getElementById('history-modal').classList.remove('hidden');
}

function closeHistoryModal() {
    document.getElementById('history-modal').classList.add('hidden');
}

function handleHistoryBackdrop(e) {
    if (e.target.id === 'history-modal') closeHistoryModal();
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeHistoryModal();
});

function renderHistoryList() {
    const listEl = document.getElementById('history-list');
    const clearBtn = document.getElementById('btn-clear-history');

    if (!chatHistory.length) {
        listEl.innerHTML = `<div style="text-align: center; color: var(--muted); padding: 28px 0; font-size: 13px;">Belum ada riwayat percakapan tersimpan.</div>`;
        clearBtn.style.display = 'none';
        return;
    }

    clearBtn.style.display = 'inline-block';
    listEl.innerHTML = '';

    const reversed = [...chatHistory].reverse();

    reversed.forEach(item => {
        const itemEl = el('div', 'history-item');
        const d = new Date(item.created_at);
        const timeStr = isNaN(d) ? '' : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        const citCount = (item.citations && item.citations.length) ? `· ${item.citations.length} referensi` : '';
        const imgBadge = (item.images && item.images.length) ? `· 📷 ${item.images.length} foto` : '';

        itemEl.innerHTML = `
            <div class="history-item-content" onclick="loadHistoryItem(${item.id})">
                <div class="history-item-q">${esc(item.question)}</div>
                <div class="history-item-meta">${timeStr} ${citCount} ${imgBadge}</div>
            </div>
            <button type="button" class="history-item-del" onclick="deleteHistoryItem(${item.id}, event)" title="Hapus">&times;</button>
        `;
        listEl.appendChild(itemEl);
    });
}

function loadHistoryItem(id) {
    closeHistoryModal();
    const targetEl = document.querySelector(`.msg-row[data-chat-id="${id}"]`);
    if (targetEl) {
        targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        targetEl.classList.add('highlight-flash');
        setTimeout(() => targetEl.classList.remove('highlight-flash'), 1600);
    }
}

async function deleteHistoryItem(id, event) {
    event.stopPropagation();
    try {
        const res = await fetch(`{{ url('/documentation/chat') }}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            chatHistory = chatHistory.filter(h => h.id !== id);
            document.querySelectorAll(`.msg-row[data-chat-id="${id}"]`).forEach(el => el.remove());
            if (!chatHistory.length) {
                chatWindow.innerHTML = `<div class="chat-empty" id="chat-empty"><div><div>Tanyakan kendala IT, AI akan mencari referensi dari riwayat tiket.</div><div style="font-size: 11.5px; color: #9CA3AF; margin-top: 4px;">Anda juga dapat melampirkan screenshot percakapan WhatsApp via <strong>Ctrl+V</strong> (maks. 3 gambar).</div></div></div>`;
            }
            renderHistoryList();
        }
    } catch (e) {
        console.error('Gagal menghapus pesan riwayat:', e);
    }
}

async function clearAllHistory() {
    if (!confirm('Hapus seluruh riwayat percakapan dari database?')) return;

    try {
        const res = await fetch('{{ route("documentation.clear-all") }}', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            chatHistory = [];
            chatWindow.innerHTML = `<div class="chat-empty" id="chat-empty"><div><div>Tanyakan kendala IT, AI akan mencari referensi dari riwayat tiket.</div><div style="font-size: 11.5px; color: #9CA3AF; margin-top: 4px;">Anda juga dapat melampirkan screenshot percakapan WhatsApp via <strong>Ctrl+V</strong> (maks. 3 gambar).</div></div></div>`;
            renderHistoryList();
            closeHistoryModal();
        }
    } catch (e) {
        console.error('Gagal menghapus semua riwayat:', e);
    }
}
</script>
@endpush
