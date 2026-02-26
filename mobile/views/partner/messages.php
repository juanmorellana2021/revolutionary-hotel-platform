<?php
/**
 * Partner - Messages
 * Booking conversations from guests, partner is 'owner' side
 */
$pid = $_SESSION['partner_id'];
?>

<!-- PARTNER MESSAGES -->
<div id="partnerMsgApp" style="display:flex;flex-direction:column;height:calc(100vh - 112px);overflow:hidden;">

    <!-- Conversation list -->
    <div id="partnerConvPanel" style="flex:1;overflow-y:auto;padding:12px;">
        <div id="partnerConvLoading" class="text-center py-4 text-muted">
            <div class="spinner-border spinner-border-sm" role="status"></div>
            <div class="mt-2 small">Cargando conversaciones…</div>
        </div>
        <div id="partnerConvList"></div>
        <div id="partnerConvEmpty" class="text-center py-5 text-muted d-none">
            <div style="font-size:3rem">💬</div>
            <div class="mt-2">No hay mensajes de huéspedes aún</div>
            <div class="small mt-1">Los mensajes aparecerán cuando alguien reserve</div>
        </div>
    </div>

    <!-- Chat panel -->
    <div id="partnerChatPanel" class="d-none" style="flex:1;display:flex;flex-direction:column;overflow:hidden;">
        <!-- Header -->
        <div class="d-flex align-items-center px-3 py-2 border-bottom" style="background:#fff;gap:10px;">
            <button class="btn btn-sm btn-light rounded-circle p-1" onclick="closePartnerChat()" style="width:34px;height:34px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <div style="flex:1">
                <div id="partnerChatGuest" class="fw-semibold" style="font-size:.9rem"></div>
                <div id="partnerChatHotel" class="text-muted" style="font-size:.72rem"></div>
            </div>
            <div id="partnerChatPhone" style="font-size:.75rem"></div>
        </div>

        <!-- Messages -->
        <div id="partnerChatMessages" style="flex:1;overflow-y:auto;padding:12px 16px;background:#f5f5f5;display:flex;flex-direction:column;gap:10px;"></div>

        <!-- Input -->
        <div class="border-top px-3 py-2 d-flex align-items-center gap-2" style="background:#fff;">
            <input type="text" id="partnerChatInput" class="form-control rounded-pill" placeholder="Escribe al huésped…" style="font-size:.88rem;">
            <button class="btn rounded-circle d-flex align-items-center justify-content-center" id="partnerSendBtn"
                onclick="partnerSendMsg()" style="width:40px;height:40px;background:var(--aini-gradient);color:white;flex-shrink:0">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="white"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
            </button>
        </div>
    </div>
</div>

<style>
.aini-pconv-item { display:flex; align-items:center; gap:12px; padding:12px 10px; border-radius:14px; cursor:pointer; border:1px solid #e5e7eb; background:#fff; margin-bottom:8px; transition:box-shadow .15s; }
.aini-pconv-item:active { background:#f9f5ff; }
.aini-msg-bubble { max-width:78%; padding:8px 13px; border-radius:14px; font-size:.85rem; line-height:1.4; word-break:break-word; }
.aini-msg-bubble.me { background:var(--aini-gradient); color:white; border-radius:14px 14px 0 14px; align-self:flex-end; }
.aini-msg-bubble.them { background:#fff; color:#222; border-radius:14px 14px 14px 0; align-self:flex-start; box-shadow:0 1px 3px rgba(0,0,0,.1); }
.aini-msg-time { font-size:.65rem; margin-top:3px; opacity:.65; }
</style>

<script>
let pActiveConvId = null;
let pActivePoll = null;
let pLastMsgId = 0;
let pActiveGuestPhone = '';

loadPartnerConversations();

function loadPartnerConversations() {
    fetch('/booking_chat_api.php?action=get_conversations&role=owner')
        .then(r => r.json())
        .then(d => {
            document.getElementById('partnerConvLoading').classList.add('d-none');
            const convs = d.data?.conversations || [];
            if (convs.length > 0) {
                renderPartnerConvs(convs);
            } else {
                document.getElementById('partnerConvEmpty').classList.remove('d-none');
            }
        })
        .catch(() => {
            document.getElementById('partnerConvLoading').innerHTML = '<div class="text-danger small text-center">Error al cargar mensajes</div>';
        });
}

function renderPartnerConvs(convs) {
    const el = document.getElementById('partnerConvList');
    el.innerHTML = convs.map(c => {
        const unread = c.unread_count_owner > 0;
        const lastMsg = c.last_message_preview || 'Sin mensajes aún';
        const lastTime = c.last_message_at ? fmtTime(c.last_message_at) : '';
        const guestName = c.guest_name || 'Huésped';
        const initials = guestName.substring(0,2).toUpperCase();
        return `<div class="aini-pconv-item" onclick="openPartnerChat(${c.id},'${escH(guestName)}','${escH(c.hotel_name||'')}','${escH(c.guest_phone||'')}')">
            <div style="width:44px;height:44px;border-radius:50%;background:${unread?'var(--aini-gradient)':'#e5e7eb'};display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;color:${unread?'white':'#555'};flex-shrink:0">${initials}</div>
            <div style="flex:1;min-width:0">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold" style="font-size:.88rem;${unread?'color:var(--aini-purple)':''}">${escH(guestName)}</span>
                    <span style="font-size:.68rem;color:#9ca3af">${lastTime}</span>
                </div>
                <div class="text-truncate" style="font-size:.76rem;color:#6b7280">🏨 ${escH(c.hotel_name||'')} · ${escH(lastMsg)}</div>
            </div>
            ${unread ? `<span class="badge rounded-pill" style="background:var(--aini-gradient);font-size:.65rem">${c.unread_count_owner}</span>` : ''}
        </div>`;
    }).join('');
}

function openPartnerChat(convId, guestName, hotelName, guestPhone) {
    pActiveConvId = convId;
    pLastMsgId = 0;
    pActiveGuestPhone = guestPhone;
    document.getElementById('partnerChatGuest').textContent = guestName;
    document.getElementById('partnerChatHotel').textContent = hotelName;
    document.getElementById('partnerChatPhone').innerHTML = guestPhone
        ? `<a href="tel:${escH(guestPhone)}" style="color:var(--aini-purple);text-decoration:none">📱 ${escH(guestPhone)}</a>`
        : '';

    document.getElementById('partnerConvPanel').classList.add('d-none');
    const panel = document.getElementById('partnerChatPanel');
    panel.classList.remove('d-none');
    panel.style.display = 'flex';

    document.getElementById('partnerChatMessages').innerHTML = '<div class="text-center text-muted small py-3"><div class="spinner-border spinner-border-sm"></div></div>';

    loadPartnerMessages(convId);

    // Mark read (owner side)
    const fd = new FormData(); fd.append('action','mark_read'); fd.append('conversation_id',convId); fd.append('role','owner');
    fetch('/booking_chat_api.php',{method:'POST',body:fd});

    if (pActivePoll) clearInterval(pActivePoll);
    pActivePoll = setInterval(()=>loadPartnerMessages(convId),5000);

    document.getElementById('partnerChatInput').onkeydown = e => { if(e.key==='Enter') partnerSendMsg(); };
}

function closePartnerChat() {
    if (pActivePoll) { clearInterval(pActivePoll); pActivePoll=null; }
    document.getElementById('partnerChatPanel').classList.add('d-none');
    document.getElementById('partnerChatPanel').style.display = '';
    document.getElementById('partnerConvPanel').classList.remove('d-none');
    loadPartnerConversations();
}

function loadPartnerMessages(convId) {
    fetch(`/booking_chat_api.php?action=get_messages&conversation_id=${convId}&role=owner`)
        .then(r => r.json())
        .then(d => {
            if (!d.success || pActiveConvId !== convId) return;
            const msgs = d.data?.messages || [];
            if (msgs.length === 0 && pLastMsgId === 0) {
                document.getElementById('partnerChatMessages').innerHTML = '<div class="text-center text-muted small py-3">Empieza la conversación con el huésped 👋</div>';
                return;
            }
            const newest = msgs.length > 0 ? msgs[msgs.length-1].id : 0;
            if (newest === pLastMsgId) return;
            pLastMsgId = newest;
            renderPartnerMessages(msgs);
        })
        .catch(()=>{});
}

function renderPartnerMessages(msgs) {
    const el = document.getElementById('partnerChatMessages');
    const atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 60;
    el.innerHTML = msgs.map(m => {
        const isMe = m.sender_type === 'owner';
        return `<div style="display:flex;flex-direction:column;align-items:${isMe?'flex-end':'flex-start'}">
            <div class="aini-msg-bubble ${isMe?'me':'them'}">${escH(m.message)}</div>
            <div class="aini-msg-time" style="text-align:${isMe?'right':'left'}">${fmtTime(m.created_at)}</div>
        </div>`;
    }).join('');
    if (atBottom || pLastMsgId===0) el.scrollTop = el.scrollHeight;
}

function partnerSendMsg() {
    const input = document.getElementById('partnerChatInput');
    const msg = input.value.trim();
    if (!msg || !pActiveConvId) return;
    input.value = '';
    const fd = new FormData();
    fd.append('action','send_message');
    fd.append('conversation_id', pActiveConvId);
    fd.append('message', msg);
    fd.append('role','owner');
    fetch('/booking_chat_api.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(d=>{ if(d.success) loadPartnerMessages(pActiveConvId); })
        .catch(()=>{ input.value=msg; });
}

function fmtTime(ts) {
    const d = new Date(ts.replace(' ','T'));
    const now = new Date();
    if (d.toDateString()===now.toDateString()) return d.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    return d.toLocaleDateString([],{day:'2-digit',month:'2-digit'});
}
function escH(s) {
    if(typeof s!=='string') return s||'';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
