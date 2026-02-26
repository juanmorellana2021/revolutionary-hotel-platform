<?php
/**
 * Partner - Transactions / Revenue
 * Table: aini_partner_transactions, partner_dashboard view
 */
$host='localhost'; $dbname='hotel_booking_system';
$dbuser='hoteluser'; $dbpass='hotelpass123';
$pid = $_SESSION['partner_id'];

$summary = ['total_revenue_usd'=>0,'total_transactions'=>0,'total_coins_accepted'=>0,'total_platform_fees'=>0];
$txList = [];
$pendingPayout = 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$dbuser,$dbpass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);

    // Summary from view
    $stmt = $pdo->prepare("SELECT * FROM partner_dashboard WHERE partner_id=?");
    $stmt->execute([$pid]);
    $row = $stmt->fetch();
    if ($row) $summary = array_merge($summary, $row);

    // Pending payout
    $stmt = $pdo->prepare("SELECT SUM(partner_receives_usd) as pending FROM aini_partner_transactions WHERE partner_id=? AND partner_settlement_status='pending'");
    $stmt->execute([$pid]);
    $pendingPayout = (float)($stmt->fetchColumn() ?: 0);

    // Transaction list
    $stmt = $pdo->prepare("SELECT t.*, gb.booking_reference, gb.guest_name FROM aini_partner_transactions t LEFT JOIN guest_bookings gb ON t.reference_id=gb.id WHERE t.partner_id=? ORDER BY t.created_at DESC LIMIT 50");
    $stmt->execute([$pid]);
    $txList = $stmt->fetchAll();
} catch (Exception $e) {}

$settleBadge = fn($s) => match($s) {
    'paid'=>'<span class="badge bg-success" style="font-size:.65rem">Pagado</span>',
    'processing'=>'<span class="badge bg-info text-dark" style="font-size:.65rem">En proceso</span>',
    default=>'<span class="badge bg-warning text-dark" style="font-size:.65rem">Pendiente</span>'
};
?>

<!-- PARTNER TRANSACTIONS -->
<div class="container-fluid px-3 py-3">

    <!-- Revenue hero -->
    <div class="rounded-4 p-4 mb-3 text-white" style="background:var(--aini-gradient);position:relative;overflow:hidden">
        <div style="position:absolute;top:-20px;right:-10px;width:100px;height:100px;background:rgba(255,255,255,.08);border-radius:50%"></div>
        <div style="font-size:.78rem;opacity:.85">💰 Ingresos totales</div>
        <div class="fw-bold my-1" style="font-size:2.4rem;letter-spacing:-1px">$<?= number_format((float)$summary['total_revenue_usd'],2) ?></div>
        <div style="font-size:.72rem;opacity:.75">USD · <?= number_format((int)$summary['total_transactions']) ?> transacciones</div>

        <div class="row g-2 mt-2">
            <div class="col-6">
                <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:10px">
                    <div style="font-size:.68rem;opacity:.8">🕐 Por cobrar</div>
                    <div class="fw-semibold">$<?= number_format($pendingPayout,2) ?></div>
                </div>
            </div>
            <div class="col-6">
                <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:10px">
                    <div style="font-size:.68rem;opacity:.8">🪙 Coins aceptados</div>
                    <div class="fw-semibold"><?= number_format((int)$summary['total_coins_accepted']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Platform fees note -->
    <?php if ((float)$summary['total_platform_fees'] > 0): ?>
    <div class="alert alert-light border rounded-3 p-2 mb-3" style="font-size:.78rem">
        💡 Comisión plataforma (12%): <strong>$<?= number_format((float)$summary['total_platform_fees'],2) ?> USD</strong>
    </div>
    <?php endif; ?>

    <!-- Transaction list -->
    <div class="fw-semibold mb-2 d-flex justify-content-between" style="font-size:.9rem">
        <span>📋 Historial</span>
        <span class="text-muted" style="font-size:.75rem"><?= count($txList) ?> registros</span>
    </div>

    <?php if (empty($txList)): ?>
    <div class="text-center py-5 text-muted">
        <div style="font-size:3rem">📭</div>
        <div class="mt-2">No hay transacciones aún</div>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
        <?php foreach ($txList as $i => $tx): ?>
        <div class="px-3 py-2 d-flex align-items-start gap-2 <?= $i < count($txList)-1 ? 'border-bottom' : '' ?>">
            <div style="font-size:1.3rem;margin-top:2px">
                <?= match($tx['partner_settlement_status']??'pending') { 'paid'=>'✅','processing'=>'⏳',default=>'🕐' } ?>
            </div>
            <div style="flex:1;min-width:0">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-semibold" style="font-size:.82rem">
                        <?= htmlspecialchars($tx['guest_name'] ?? ($tx['description'] ? substr($tx['description'],0,30) : 'Transacción')) ?>
                    </div>
                    <div class="fw-bold" style="color:#16a34a;font-size:.88rem">$<?= number_format((float)($tx['partner_receives_usd']??$tx['total_amount_usd']??0),2) ?></div>
                </div>
                <?php if (!empty($tx['booking_reference'])): ?>
                <div class="text-muted" style="font-size:.7rem">Ref: <?= htmlspecialchars($tx['booking_reference']) ?></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <div class="text-muted" style="font-size:.68rem"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></div>
                    <?= ($settleBadge)($tx['partner_settlement_status']??'pending') ?>
                </div>
                <?php if ($tx['coins_used']>0): ?>
                <div style="font-size:.68rem;color:#f59e0b">🪙 <?= number_format($tx['coins_used']) ?> coins usados · Desc: $<?= number_format((float)($tx['discount_amount_usd']??0),2) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
