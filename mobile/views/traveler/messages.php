<?php
/**
 * Traveler - Messages
 * Conversation list + Bootstrap offcanvas-bottom chat (same pattern as Sofia AI)
 */
?>

<!-- MESSAGES — conversation list -->
<div style="padding: 72px 12px 80px;">

    <div id="convListLoading" class="text-center py-4 text-muted">
        <div class="spinner-border spinner-border-sm" role="status"></div>
        <div class="mt-2 small">Cargando conversaciones…</div>
    </div>

    <div id="convList"></div>

    <div id="convEmpty" class="text-center py-5 text-muted d-none">
        <div style="font-size:3rem">💬</div>
        <div class="mt-2">No tienes mensajes aún</div>
        <div class="small mt-1">Los mensajes aparecen al hacer una reserva</div>
        <a href="?page=hotels" class="btn btn-sm mt-3" style="background:var(--aini-gradient);color:white;border-radius:20px;">Explorar hoteles</a>
    </div>

</div>

<!-- CHAT OFFCANVAS — same pattern as Sofia AI, slides up from bottom -->
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="chatOffcanvas" style="height:90vh;border-radius:20px 20px 0 0;">

    <!-- Header -->
    <div class="offcanvas-header py-2 px-3" style="background:var(--aini-gradient);color:white;border-radius:20px 20px 0 0;flex-shrink:0;">
        <div style="flex:1">
            <div id="chatHotelName" class="fw-semibold" style="font-size:.95rem;color:white;"></div>
            <div id="chatBookingRef" style="font-size:.72rem;color:rgba(255,255,255,.8);"></div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- Body: messages + input -->
    <div class="offcanvas-body d-flex flex-column p-0">

        <!-- Messages -->
        <div id="chatMessages" style="flex:1;overflow-y:auto;padding:12px 16px;background:#f5f5f5;display:flex;flex-direction:column;gap:10px;"></div>

        <!-- Input bar — naturally at bottom of flex column, Bootstrap handles everything -->
        <div class="p-2 border-top" style="background:#fff;flex-shrink:0;">
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="chatInput" class="form-control rounded-pill"
                       placeholder="Escribe un mensaje…" style="font-size:.88rem;">
                <button class="btn rounded-circle d-flex align-items-center justify-content-center"
                        onclick="sendMsg()"
                        style="width:42px;height:42px;background:var(--aini-gradient);color:white;flex-shrink:0;border:none;">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="white"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
                </button>
            </div>
        </div>

    </div>
</div>

<style>
.aini-conv-item { display:flex;align-items:center;gap:12px;padding:12px 10px;border-radius:14px;cursor:pointer;border:1px solid #e5e7eb;background:#fff;margin-bottom:8px;transition:box-shadow .15s; }
.aini-conv-item:active { background:#f9f5ff; }
.aini-conv-avatar { width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;background:linear-gradient(135deg,#667eea22,#764ba222);flex-shrink:0; }
.aini-msg-bubble { max-width:78%;padding:8px 13px;border-radius:14px;font-size:.85rem;line-height:1.4;word-break:break-word; }
.aini-msg-bubble.me { background:var(--aini-gradient);color:white;border-radius:14px 14px 0 14px;align-self:flex-end; }
.aini-msg-bubble.them { background:#fff;color:#222;border-radius:14px 14px 14px 0;align-self:flex-start;box-shadow:0 1px 3px rgba(0,0,0,.1); }
.aini-msg-time { font-size:.65rem;margin-top:3px;opacity:.65; }
</style>

<script>
let activeBookingId     = null;  // booking_id — used for get_messages & send_message
let activeConversationId = null; // conversation_id — returned by get_messages, used for mark_read
let activeHotelName     = '';
let activePoll    = null;
let lastMsgId     = 0;
let chatOffcanvas = null;

// Auto-open a specific chat if booking_id is in URL
const urlBookingId = new URLSearchParams(window.location.search).get('booking_id');

document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('chatOffcanvas');
    chatOffcanvas = new bootstrap.Offcanvas(el);
    el.addEventListener('hidden.bs.offcanvas', function() {
        if (activePoll) { clearInterval(activePoll); activePoll = null; }
        activeBookingId = null;
        activeConversationId = null;
        loadConversations();
    });

    // Auto-open from booking card link (?booking_id=X)
    if (urlBookingId) {
        openChat(parseInt(urlBookingId), '', '');
    }
});

loadConversations();

function loadConversations() {
    fetch('/booking_chat_api.php?action=get_conversations')
        .then(r => r.json())
        .then(d => {
            document.getElementById('convListLoading').classList.add('d-none');
            if (d.success && d.data && d.data.conversations && d.data.conversations.length > 0) {
                renderConversations(d.data.conversations);
            } else {
                document.getElementById('convEmpty').classList.remove('d-none');
            }
        })
        .catch(() => {
            document.getElementById('convListLoading').innerHTML = '<div class="text-danger small text-center">Error al cargar mensajes</div>';
        });
}

function renderConversations(convs) {
    const el = document.getElementById('convList');
    el.innerHTML = convs.map(c => {
        const unread   = c.unread_count_user > 0;
        const lastMsg  = c.last_message_preview || 'Toca para ver mensajes';
        const lastTime = c.last_message_at ? formatTime(c.last_message_at) : '';
        const hotel    = c.hotel_name || 'Hotel';
        const ref      = c.booking_reference || '';
        return `<div class="aini-conv-item" onclick="openChat(${c.booking_id},'${escH(hotel)}','${escH(ref)}')">
            <div class="aini-conv-avatar">🏨</div>
            <div style="flex:1;min-width:0">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold" style="font-size:.88rem;${unread?'color:var(--aini-purple)':''}">${escH(hotel)}</span>
                    <span style="font-size:.68rem;color:#9ca3af">${lastTime}</span>
                </div>
                <div class="text-truncate" style="font-size:.78rem;color:#6b7280">${escH(lastMsg)}</div>
            </div>
            ${unread?`<span class="badge rounded-pill" style="background:var(--aini-gradient);font-size:.65rem">${c.unread_count_user}</span>`:''}
        </div>`;
    }).join('');
}

function openChat(bookingId, hotelName, bookingRef) {
    activeBookingId      = bookingId;
    activeConversationId = null;
    lastMsgId     = 0;

    // If hotel name wasn't passed (auto-open from URL), load from conv list then update
    document.getElementById('chatHotelName').textContent  = hotelName || 'Hotel';
    document.getElementById('chatBookingRef').textContent = bookingRef ? 'Ref: ' + bookingRef : '';
    document.getElementById('chatMessages').innerHTML = '<div class="text-center text-muted small py-3"><div class="spinner-border spinner-border-sm"></div></div>';

    if (!chatOffcanvas) chatOffcanvas = new bootstrap.Offcanvas(document.getElementById('chatOffcanvas'));
    chatOffcanvas.show();

    loadMessages(bookingId);

    if (activePoll) clearInterval(activePoll);
    activePoll = setInterval(() => loadMessages(bookingId), 5000);

    document.getElementById('chatInput').onkeydown = e => { if (e.key==='Enter') sendMsg(); };
    setTimeout(() => document.getElementById('chatInput').focus(), 400);
}

function loadMessages(bookingId) {
    fetch(`/booking_chat_api.php?action=get_messages&booking_id=${bookingId}`)
        .then(r => r.json())
        .then(d => {
            if (!d.success || activeBookingId !== bookingId) return;

            // Store conversation_id for mark_read
            if (d.data.conversation_id) {
                activeConversationId = d.data.conversation_id;
                // Mark as read
                const fd = new FormData();
                fd.append('action', 'mark_read');
                fd.append('conversation_id', activeConversationId);
                fd.append('user_type', 'traveler');
                fetch('/booking_chat_api.php', {method:'POST', body:fd});
            }

            const msgs = d.data.messages || [];
            if (msgs.length === 0 && lastMsgId === 0) {
                document.getElementById('chatMessages').innerHTML = '<div class="text-center text-muted small py-3">Envía el primer mensaje al hotel 👋</div>';
                return;
            }
            const newest = msgs.length > 0 ? msgs[msgs.length-1].id : 0;
            if (newest === lastMsgId) return;
            lastMsgId = newest;
            renderMessages(msgs);
        })
        .catch(() => {});
}

function renderMessages(msgs) {
    const el = document.getElementById('chatMessages');
    const atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 60;
    el.innerHTML = msgs.map(m => {
        const isMe = m.sender_type === 'traveler';
        return `<div style="display:flex;flex-direction:column;align-items:${isMe?'flex-end':'flex-start'}">
            <div class="aini-msg-bubble ${isMe?'me':'them'}">${escH(m.message)}</div>
            <div class="aini-msg-time" style="text-align:${isMe?'right':'left'}">${formatTime(m.created_at)}</div>
        </div>`;
    }).join('');
    if (atBottom || lastMsgId === 0) el.scrollTop = el.scrollHeight;
}

function sendMsg() {
    const input = document.getElementById('chatInput');
    const msg   = input.value.trim();
    if (!msg || !activeBookingId) return;
    input.value = '';
    const fd = new FormData();
    fd.append('action',      'send_message');
    fd.append('booking_id',  activeBookingId);
    fd.append('message',     msg);
    fd.append('sender_type', 'traveler');
    fetch('/booking_chat_api.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(d => { if (d.success) loadMessages(activeBookingId); })
        .catch(() => { input.value = msg; });
}

function formatTime(ts) {
    const d   = new Date(ts.replace(' ','T'));
    const now = new Date();
    if (d.toDateString() === now.toDateString()) return d.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    return d.toLocaleDateString([],{day:'2-digit',month:'2-digit'});
}

function escH(s) {
    if (typeof s !== 'string') return s || '';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
