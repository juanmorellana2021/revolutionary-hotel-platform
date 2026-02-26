<?php
/**
 * Traveler - Wallet (AiNi Coins + Rewards)
 * Tables: ainitravel_users, aini_coin_transactions
 */
$host = 'localhost'; $dbname = 'hotel_booking_system';
$dbuser = 'hoteluser'; $dbpass = 'hotelpass123';
$uid = $_SESSION['user_id'];

$walletUser = ['aini_coins'=>0,'aini_rewards'=>0,'aini_crypto'=>0,'xp_points'=>0,'member_tier'=>'standard'];
$txHistory = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $stmt = $pdo->prepare("SELECT aini_coins, aini_rewards, aini_crypto, xp_points, member_tier FROM ainitravel_users WHERE id=?");
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    if ($row) $walletUser = $row;

    // Last 30 transactions
    $stmt = $pdo->prepare("SELECT id, transaction_type, amount, coin_type, description, created_at FROM aini_coin_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 30");
    $stmt->execute([$uid]);
    $txHistory = $stmt->fetchAll();
} catch (Exception $e) { /* graceful degradation */ }

// Tier info
$tierData = [
    'standard'  => ['label'=>'Estándar',  'color'=>'#9ca3af', 'icon'=>'⚪'],
    'silver'    => ['label'=>'Plata',     'color'=>'#94a3b8', 'icon'=>'🥈'],
    'gold'      => ['label'=>'Oro',       'color'=>'#f59e0b', 'icon'=>'🥇'],
    'platinum'  => ['label'=>'Platino',   'color'=>'#7c3aed', 'icon'=>'💎'],
];
$tier = $tierData[$walletUser['member_tier']] ?? $tierData['standard'];

function txIcon($type) {
    if (str_starts_with($type,'earned')) return '⬆️';
    if (str_starts_with($type,'spent'))  return '⬇️';
    if ($type==='transfer_out') return '📤';
    if ($type==='transfer_in')  return '📥';
    if ($type==='refund')       return '↩️';
    return '🔄';
}
function txLabel($type) {
    return match($type) {
        'earned_booking'   => 'Reserva completada',
        'earned_referral'  => 'Referido',
        'earned_bonus'     => 'Bono',
        'spent_discount'   => 'Descuento aplicado',
        'spent_upgrade'    => 'Upgrade de habitación',
        'transfer_out'     => 'Transferencia enviada',
        'transfer_in'      => 'Transferencia recibida',
        'refund'           => 'Reembolso',
        'admin_adjustment' => 'Ajuste admin',
        'rewards_purchase' => 'Compra con rewards',
        default            => ucwords(str_replace('_',' ',$type))
    };
}
?>

<!-- WALLET -->
<div class="container-fluid px-3 py-3">

    <!-- Balance hero card -->
    <div class="rounded-4 p-4 mb-3 text-white" style="background:var(--aini-gradient);position:relative;overflow:hidden">
        <div style="position:absolute;top:-30px;right:-20px;width:120px;height:120px;background:rgba(255,255,255,.08);border-radius:50%"></div>
        <div style="position:absolute;bottom:-20px;left:40px;width:80px;height:80px;background:rgba(255,255,255,.05);border-radius:50%"></div>

        <div class="d-flex justify-content-between align-items-start mb-1">
            <span style="font-size:.8rem;opacity:.85">Balance AiNi Coins</span>
            <span class="badge" style="background:rgba(255,255,255,.2);font-size:.72rem"><?= $tier['icon'] ?> <?= $tier['label'] ?></span>
        </div>
        <div class="fw-bold mb-3" style="font-size:2.8rem;letter-spacing:-1px">
            🪙 <?= number_format($walletUser['aini_coins']) ?>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:10px">
                    <div style="font-size:.7rem;opacity:.8">⭐ Rewards</div>
                    <div class="fw-semibold"><?= number_format($walletUser['aini_rewards']) ?></div>
                </div>
            </div>
            <div class="col-6">
                <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:10px">
                    <div style="font-size:.7rem;opacity:.8">💎 Crypto</div>
                    <div class="fw-semibold"><?= number_format($walletUser['aini_crypto']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- XP bar -->
    <?php
    $xp = $walletUser['xp_points'];
    $nextXp = 1000; // simplified
    $xpPct = min(100, ($xp % $nextXp) / $nextXp * 100);
    ?>
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3">
        <div class="d-flex justify-content-between mb-1" style="font-size:.78rem">
            <span>⚡ XP: <strong><?= number_format($xp) ?></strong></span>
            <span class="text-muted">Próximo nivel: <?= number_format($nextXp - ($xp % $nextXp)) ?> XP</span>
        </div>
        <div class="progress" style="height:6px;border-radius:10px">
            <div class="progress-bar" role="progressbar" style="width:<?= $xpPct ?>%;background:var(--aini-gradient)" aria-valuenow="<?= $xpPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>

    <!-- How to earn -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3">
        <div class="fw-semibold mb-2" style="font-size:.9rem">💡 Cómo ganar monedas</div>
        <div class="row g-2">
            <?php $tips = [
                ['🏨','Hacer reservas','20 coins por noche'],
                ['⭐','Dejar reseñas','50 coins'],
                ['👥','Invitar amigos','200 coins'],
                ['✅','Verificar email','100 coins'],
            ];
            foreach ($tips as $tip): ?>
            <div class="col-6">
                <div class="p-2 rounded-3" style="background:#f9f5ff;font-size:.75rem">
                    <div style="font-size:1.3rem"><?= $tip[0] ?></div>
                    <div class="fw-semibold"><?= $tip[1] ?></div>
                    <div class="text-muted"><?= $tip[2] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Transaction history -->
    <div class="fw-semibold mb-2 d-flex justify-content-between align-items-center" style="font-size:.9rem">
        <span>📋 Historial de transacciones</span>
        <span class="text-muted" style="font-size:.75rem"><?= count($txHistory) ?> registros</span>
    </div>

    <?php if (empty($txHistory)): ?>
    <div class="text-center py-4 text-muted">
        <div style="font-size:2.5rem">📭</div>
        <div class="mt-2 small">No hay transacciones aún</div>
        <div class="small">¡Haz tu primera reserva para ganar coins!</div>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
        <?php foreach ($txHistory as $i => $tx):
            $earned = str_starts_with($tx['transaction_type'],'earned') || $tx['transaction_type']==='transfer_in' || $tx['transaction_type']==='refund';
            $amtColor = $earned ? '#16a34a' : '#dc2626';
            $amtSign  = $earned ? '+' : '-';
        ?>
        <div class="d-flex align-items-center px-3 py-2 <?= $i < count($txHistory)-1 ? 'border-bottom' : '' ?>">
            <div class="me-3" style="font-size:1.4rem;width:28px;text-align:center"><?= txIcon($tx['transaction_type']) ?></div>
            <div style="flex:1;min-width:0">
                <div style="font-size:.83rem;font-weight:500"><?= txLabel($tx['transaction_type']) ?></div>
                <?php if (!empty($tx['description'])): ?>
                <div class="text-truncate text-muted" style="font-size:.72rem"><?= htmlspecialchars($tx['description']) ?></div>
                <?php endif; ?>
                <div class="text-muted" style="font-size:.68rem"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></div>
            </div>
            <div class="text-end ms-2">
                <div class="fw-semibold" style="color:<?= $amtColor ?>;font-size:.88rem"><?= $amtSign ?><?= number_format($tx['amount']) ?></div>
                <div class="text-muted" style="font-size:.65rem"><?= ucfirst($tx['coin_type'] ?? 'coins') ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
