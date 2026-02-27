<?php
/**
 * Partner - Messages
 * Desktop-compatible flow:
 * - List conversations from partner bookings
 * - Open chat using booking_id
 * - Send as sender_type=owner
 */
$pid = intval($_SESSION['partner_id'] ?? 0);
$conversations = [];

try {
    $pdo = new PDO('mysql:host=localhost;dbname=hotel_booking_system;charset=utf8mb4', 'hoteluser', 'hotelpass123', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $st = $pdo->prepare('SELECT email FROM aini_partner_businesses WHERE id = ?');
    $st->execute([$pid]);
    $partnerEmail = $st->fetchColumn() ?: '';

    $sql = "SELECT
                b.id AS booking_id,
                b.guest_name,
                b.guest_phone,
                b.booking_reference,
                h.name AS hotel_name,
                bc.id AS conversation_id,
                bc.last_message_preview,
                bc.last_message_at,
                COALESCE(bc.unread_count_owner, 0) AS unread_count_owner,
                COALESCE(bc.created_at, b.created_at) AS sort_time
            FROM guest_bookings b
            JOIN hotel_properties h ON b.hotel_id = h.id
            LEFT JOIN booking_conversations bc ON bc.booking_id = b.id
            WHERE (h.partner_business_id = ? OR h.email = ?)
              AND bc.id IS NOT NULL
            ORDER BY sort_time DESC
            LIMIT 80";

    $st = $pdo->prepare($sql);
    $st->execute([$pid, $partnerEmail]);
    $conversations = $st->fetchAll();
} catch (Exception $e) {
    $conversations = [];
}
?>

<div style="padding: 72px 12px 80px;">

    <div id="partnerConvList">
        <?php foreach ($conversations as $c):
            $guest = trim((string)($c['guest_name'] ?? 'Huésped'));
            if ($guest === '') $guest = 'Huésped';
            $hotel = (string)($c['hotel_name'] ?? 'Hotel');
            $phone = (string)($c['guest_phone'] ?? '');
            $ref = (string)($c['booking_reference'] ?? '');
            $last = (string)($c['last_message_preview'] ?? 'Toca para ver mensajes');
            $time = (string)($c['last_message_at'] ?? '');
            $unread = intval($c['unread_count_owner'] ?? 0);
        ?>
        <div class="aini-pconv-item" onclick="openPartnerChat(<?= intval($c['booking_id']) ?>, '<?= htmlspecialchars($guest, ENT_QUOTES) ?>', '<?= htmlspecialchars($hotel, ENT_QUOTES) ?>', '<?= htmlspecialchars($phone, ENT_QUOTES) ?>', '<?= htmlspecialchars($ref, ENT_QUOTES) ?>', <?= intval($c['conversation_id']) ?>)">
            <div class="aini-pconv-avatar">&#128100;</div>
            <div style="flex:1;min-width:0">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold" style="font-size:.88rem;<?= $unread > 0 ? 'color:var(--aini-purple);' : '' ?>"><?= htmlspecialchars($guest) ?></span>
                    <span style="font-size:.68rem;color:#9ca3af"><?= htmlspecialchars($time ? date('d/m H:i', strtotime($time)) : '') ?></span>
                </div>
                <div class="text-truncate" style="font-size:.78rem;color:#6b7280"><?= htmlspecialchars($hotel) ?><?= $ref ? ' · Ref: '.htmlspecialchars($ref) : '' ?> · <?= htmlspecialchars($last) ?></div>
            </div>
            <?php if ($unread > 0): ?>
            <span class="badge rounded-pill" style="background:var(--aini-gradient);font-size:.65rem"><?= $unread ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div id="partnerConvEmpty" class="text-center py-5 text-muted <?= !empty($conversations) ? 'd-none' : '' ?>">
        <div style="font-size:3rem">&#128172;</div>
        <div class="mt-2">No hay mensajes de huéspedes aún</div>
        <div class="small mt-1">Los mensajes aparecen cuando llega una reserva</div>
    </div>

</div>

<div class="offcanvas offcanvas-bottom" tabindex="-1" id="partnerChatOffcanvas" style="height:90vh;border-radius:20px 20px 0 0;">
    <div class="offcanvas-header py-2 px-3" style="background:var(--aini-gradient);color:white;border-radius:20px 20px 0 0;flex-shrink:0;">
        <div style="flex:1">
            <div id="partnerChatGuest" class="fw-semibold" style="font-size:.95rem;color:white;"></div>
            <div id="partnerChatHotel" style="font-size:.72rem;color:rgba(255,255,255,.85);"></div>
        </div>
        <div id="partnerChatPhone" class="me-2" style="font-size:.78rem;"></div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-0">
        <div id="partnerChatMessages" style="flex:1;overflow-y:auto;padding:12px 16px;background:#f5f5f5;display:flex;flex-direction:column;gap:10px;"></div>

        <div class="p-2 border-top" style="background:#fff;flex-shrink:0;">
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="partnerChatInput" class="form-control rounded-pill" placeholder="Escribe al huésped..." style="font-size:.88rem;">
                <button class="btn rounded-circle d-flex align-items-center justify-content-center" onclick="partnerSendMsg()" style="width:42px;height:42px;background:var(--aini-gradient);color:white;flex-shrink:0;border:none;">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="white"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.aini-pconv-item { display:flex;align-items:center;gap:12px;padding:12px 10px;border-radius:14px;cursor:pointer;border:1px solid #e5e7eb;background:#fff;margin-bottom:8px;transition:box-shadow .15s; }
.aini-pconv-item:active { background:#f9f5ff; }
.aini-pconv-avatar { width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;background:linear-gradient(135deg,#667eea22,#764ba222);flex-shrink:0; }
.aini-msg-bubble { max-width:78%;padding:8px 13px;border-radius:14px;font-size:.85rem;line-height:1.4;word-break:break-word; }
.aini-msg-bubble.me { background:var(--aini-gradient);color:white;border-radius:14px 14px 0 14px;align-self:flex-end; }
.aini-msg-bubble.them { background:#fff;color:#222;border-radius:14px 14px 14px 0;align-self:flex-start;box-shadow:0 1px 3px rgba(0,0,0,.1); }
.aini-msg-time { font-size:.65rem;margin-top:3px;opacity:.65; }
</style>

<script>
let pActiveBookingId = null;
let pActiveConversationId = null;
let pPoll = null;
let pLastMsgId = 0;
let partnerOffcanvas = null;
const pUrl = new URLSearchParams(window.location.search);
const pStartBookingId = parseInt(pUrl.get('booking_id') || '0', 10);
const pStartGuest = pUrl.get('guest_name') || 'Huésped';
const pStartHotel = pUrl.get('hotel_name') || 'Hotel';
const pStartPhone = pUrl.get('guest_phone') || '';
const pStartRef = pUrl.get('booking_ref') || '';
const pStartConvId = parseInt(pUrl.get('conversation_id') || '0', 10);

document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('partnerChatOffcanvas');
    partnerOffcanvas = new bootstrap.Offcanvas(el);

    el.addEventListener('hidden.bs.offcanvas', function() {
        if (pPoll) { clearInterval(pPoll); pPoll = null; }
        pActiveBookingId = null;
        pActiveConversationId = null;
        pLastMsgId = 0;
    });

    if (pStartBookingId > 0) {
        setTimeout(function() {
            openPartnerChat(pStartBookingId, pStartGuest, pStartHotel, pStartPhone, pStartRef, pStartConvId);
        }, 120);
    }
});

function openPartnerChat(bookingId, guestName, hotelName, guestPhone, bookingRef, conversationId) {
    pActiveBookingId = bookingId;
    pActiveConversationId = conversationId;
    pLastMsgId = 0;

    document.getElementById('partnerChatGuest').textContent = guestName || 'Huésped';
    document.getElementById('partnerChatHotel').textContent = (hotelName || 'Hotel') + (bookingRef ? (' · Ref: ' + bookingRef) : '');
    document.getElementById('partnerChatPhone').innerHTML = guestPhone
        ? '<a href="tel:' + escH(guestPhone) + '" style="color:white;text-decoration:none">' + escH(guestPhone) + '</a>'
        : '';

    document.getElementById('partnerChatMessages').innerHTML = '<div class="text-center text-muted small py-3"><div class="spinner-border spinner-border-sm"></div></div>';

    if (!partnerOffcanvas) partnerOffcanvas = new bootstrap.Offcanvas(document.getElementById('partnerChatOffcanvas'));
    partnerOffcanvas.show();

    markPartnerRead();
    loadPartnerMessages(bookingId);

    if (pPoll) clearInterval(pPoll);
    pPoll = setInterval(() => loadPartnerMessages(bookingId), 5000);

    document.getElementById('partnerChatInput').onkeydown = function(e){ if (e.key === 'Enter') partnerSendMsg(); };
    setTimeout(() => document.getElementById('partnerChatInput').focus(), 350);
}

function markPartnerRead() {
    if (!pActiveConversationId) return;
    const fd = new FormData();
    fd.append('action', 'mark_read');
    fd.append('conversation_id', pActiveConversationId);
    fd.append('user_type', 'owner');
    fetch('/booking_chat_api.php', { method: 'POST', body: fd });
}

function loadPartnerMessages(bookingId) {
    fetch('/booking_chat_api.php?action=get_messages&booking_id=' + bookingId)
        .then(r => r.json())
        .then(d => {
            if (!d.success || pActiveBookingId !== bookingId) return;

            if (d.data && d.data.conversation_id) {
                pActiveConversationId = d.data.conversation_id;
                markPartnerRead();
            }

            const msgs = (d.data && d.data.messages) ? d.data.messages : [];
            if (msgs.length === 0 && pLastMsgId === 0) {
                document.getElementById('partnerChatMessages').innerHTML = '<div class="text-center text-muted small py-3">Empieza la conversación con el huésped</div>';
                return;
            }

            const newest = msgs.length > 0 ? msgs[msgs.length - 1].id : 0;
            if (newest === pLastMsgId) return;
            pLastMsgId = newest;
            renderPartnerMessages(msgs);
        })
        .catch(() => {});
}

function renderPartnerMessages(msgs) {
    const el = document.getElementById('partnerChatMessages');
    const atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 60;

    el.innerHTML = msgs.map(m => {
        const isMe = m.sender_type === 'owner';
        return '<div style="display:flex;flex-direction:column;align-items:' + (isMe ? 'flex-end' : 'flex-start') + '">' +
               '<div class="aini-msg-bubble ' + (isMe ? 'me' : 'them') + '">' + escH(m.message) + '</div>' +
               '<div class="aini-msg-time" style="text-align:' + (isMe ? 'right' : 'left') + '">' + formatTime(m.created_at) + '</div>' +
               '</div>';
    }).join('');

    if (atBottom || pLastMsgId === 0) el.scrollTop = el.scrollHeight;
}

function partnerSendMsg() {
    const input = document.getElementById('partnerChatInput');
    const msg = input.value.trim();
    if (!msg || !pActiveBookingId) return;

    input.value = '';
    const fd = new FormData();
    fd.append('action', 'send_message');
    fd.append('booking_id', pActiveBookingId);
    fd.append('message', msg);
    fd.append('sender_type', 'owner');

    fetch('/booking_chat_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) loadPartnerMessages(pActiveBookingId); })
        .catch(() => { input.value = msg; });
}

function formatTime(ts) {
    if (!ts) return '';
    const d = new Date(String(ts).replace(' ', 'T'));
    const now = new Date();
    if (d.toDateString() === now.toDateString()) return d.toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' });
    return d.toLocaleDateString([], { day:'2-digit', month:'2-digit' });
}

function escH(s) {
    if (typeof s !== 'string') return s || '';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
