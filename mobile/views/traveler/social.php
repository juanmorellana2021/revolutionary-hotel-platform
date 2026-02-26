<?php
/**
 * Traveler - Social Feed
 * Shows friend requests, leaderboard, and community activity
 */
$host = 'localhost'; $dbname = 'hotel_booking_system';
$dbuser = 'hoteluser'; $dbpass = 'hotelpass123';
$uid = $_SESSION['user_id'];

$friends = [];
$pendingReqs = [];
$leaderboard = [];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pending friend requests TO this user
    $stmt = $pdo->prepare("
        SELECT fr.id as req_id, fr.from_user_id, u.name as from_name, u.profile_photo, u.member_tier
        FROM friend_requests fr
        JOIN ainitravel_users u ON fr.from_user_id = u.id
        WHERE fr.to_user_id = ? AND fr.status = 'pending'
        LIMIT 10
    ");
    $stmt->execute([$uid]);
    $pendingReqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top travelers by coins (leaderboard)
    $stmt = $pdo->query("
        SELECT name, aini_coins, member_tier, profile_photo
        FROM ainitravel_users WHERE is_active=1 AND aini_coins > 0
        ORDER BY aini_coins DESC LIMIT 15
    ");
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { /* graceful */ }

function tierIcon($t) {
    return match($t) { 'gold'=>'🥇', 'platinum'=>'💎', 'silver'=>'🥈', default=>'⚪' };
}

$initials = function($name) {
    $parts = explode(' ', trim($name));
    return strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
};
?>

<!-- SOCIAL -->
<div class="container-fluid px-3 py-3">

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="fw-bold" style="font-size:1.05rem">🌎 Comunidad</div>
            <div class="text-muted" style="font-size:.75rem">Viajeros AiNi Travel</div>
        </div>
    </div>

    <!-- Pending requests -->
    <?php if (!empty($pendingReqs)): ?>
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3">
        <div class="fw-semibold mb-2" style="font-size:.88rem">👥 Solicitudes de amistad (<?= count($pendingReqs) ?>)</div>
        <?php foreach ($pendingReqs as $r):
            $rInitials = strtoupper(substr($r['from_name'],0,2));
        ?>
        <div class="d-flex align-items-center gap-3 mb-2" id="req_<?= $r['req_id'] ?>">
            <div style="width:40px;height:40px;border-radius:50%;background:var(--aini-gradient);display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:.85rem;flex-shrink:0">
                <?= htmlspecialchars($rInitials) ?>
            </div>
            <div style="flex:1">
                <div class="fw-semibold" style="font-size:.85rem"><?= htmlspecialchars($r['from_name']) ?></div>
                <div class="text-muted" style="font-size:.72rem"><?= tierIcon($r['member_tier']) ?> <?= ucfirst($r['member_tier']) ?></div>
            </div>
            <div class="d-flex gap-1">
                <button class="btn btn-sm py-0 px-2 rounded-pill" style="background:var(--aini-gradient);color:white;font-size:.72rem"
                    onclick="respondReq(<?= $r['req_id'] ?>,'accept',this)">Aceptar</button>
                <button class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" style="font-size:.72rem"
                    onclick="respondReq(<?= $r['req_id'] ?>,'decline',this)">Rechazar</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 🏆 Leaderboard -->
    <div class="fw-semibold mb-2" style="font-size:.88rem">🏆 Viajeros top</div>
    <?php if (empty($leaderboard)): ?>
    <div class="text-center py-4 text-muted">
        <div style="font-size:2.5rem">🌟</div>
        <div class="mt-2 small">¡Sé el primero en el ranking!</div>
        <div class="small">Haz reservas para ganar AiNi Coins</div>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
        <?php foreach ($leaderboard as $i => $traveler):
            $tInitials = strtoupper(substr($traveler['name'],0,2));
            $rankIcon = match($i) { 0=>'🥇', 1=>'🥈', 2=>'🥉', default=>($i+1).'.' };
            $isMe = (isset($traveler['id']) && $traveler['id'] == $uid);
        ?>
        <div class="d-flex align-items-center px-3 py-2 <?= $i < count($leaderboard)-1 ? 'border-bottom' : '' ?>"
             style="<?= $isMe ? 'background:#f9f5ff;' : '' ?>">
            <div class="me-2 fw-bold" style="width:28px;text-align:center;font-size:<?= $i<3 ? '1.1rem' : '.8rem' ?>"><?= $rankIcon ?></div>
            <div style="width:36px;height:36px;border-radius:50%;background:<?= $isMe?'var(--aini-gradient)':'#e5e7eb' ?>;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:600;color:<?= $isMe?'white':'#555' ?>;flex-shrink:0;margin-right:10px">
                <?= htmlspecialchars($tInitials) ?>
            </div>
            <div style="flex:1">
                <div style="font-size:.83rem;font-weight:<?= $isMe?'700':'500' ?>"><?= htmlspecialchars($traveler['name']) ?><?= $isMe ? ' (Tú)' : '' ?></div>
                <div class="text-muted" style="font-size:.7rem"><?= tierIcon($traveler['member_tier']) ?> <?= ucfirst($traveler['member_tier']) ?></div>
            </div>
            <div class="fw-semibold" style="color:#f59e0b;font-size:.85rem">🪙 <?= number_format($traveler['aini_coins']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Invite friends CTA -->
    <div class="rounded-4 p-3 text-center text-white mb-3" style="background:var(--aini-gradient)">
        <div style="font-size:1.6rem">👥</div>
        <div class="fw-bold mb-1">Invita amigos</div>
        <div style="font-size:.8rem;opacity:.9">Gana 200 AiNi Coins por cada amigo que se una</div>
        <button class="btn btn-sm mt-2 fw-semibold" style="background:rgba(255,255,255,.25);color:white;border:1px solid rgba(255,255,255,.4);border-radius:20px;"
            onclick="shareInvite()">🔗 Compartir enlace</button>
    </div>

</div>

<script>
function respondReq(reqId, action, btn) {
    btn.disabled = true;
    const fd = new FormData();
    fd.append('req_id', reqId);
    fd.append('action', action);
    fetch('/api/friend_request.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            document.getElementById('req_' + reqId)?.remove();
        })
        .catch(() => {
            btn.disabled = false;
        });
}

function shareInvite() {
    const url = 'https://ainitravel.com/?ref=<?= urlencode($_SESSION['user_id'] ?? '') ?>';
    if (navigator.share) {
        navigator.share({ title:'AiNi Travel', text:'¡Únete a AiNi Travel y gana coins!', url });
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            alert('¡Enlace copiado! Compártelo con tus amigos.');
        });
    }
}
</script>
