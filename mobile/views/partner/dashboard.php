<?php
/**
 * Partner - Dashboard
 * Stats: hotels, bookings, revenue, pending requests
 */
$host = 'localhost'; $dbname = 'hotel_booking_system';
$dbuser = 'hoteluser'; $dbpass = 'hotelpass123';
$pid = $_SESSION['partner_id'];
$pname = $_SESSION['partner_name'] ?? 'Partner';

$partner = []; $hotels = []; $stats = [];
$recentBookings = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Partner info
    $stmt = $pdo->prepare("SELECT * FROM aini_partner_businesses WHERE id=?");
    $stmt->execute([$pid]);
    $partner = $stmt->fetch() ?: [];

    // Partner's hotels
    $partnerEmail = $partner['email'] ?? '';
    $stmt = $pdo->prepare("SELECT id, name, location, status, is_active, star_category, images, emoji FROM hotel_properties WHERE partner_business_id=? OR email=? ORDER BY created_at DESC");
    $stmt->execute([$pid, $partnerEmail]);
    $hotels = $stmt->fetchAll();

    $hotelIds = array_column($hotels, 'id');

    if (!empty($hotelIds)) {
        $inPh = implode(',', array_fill(0, count($hotelIds), '?'));

        // Booking stats
        $stmt = $pdo->prepare("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status IN ('confirmed','completed') THEN total_price ELSE 0 END) as revenue,
            SUM(CASE WHEN check_in >= CURDATE() AND status != 'cancelled' THEN 1 ELSE 0 END) as upcoming
            FROM guest_bookings WHERE hotel_id IN ($inPh)");
        $stmt->execute($hotelIds);
        $stats = $stmt->fetch() ?: [];

        // Unread messages
        $stmt = $pdo->prepare("SELECT SUM(unread_count_owner) as unread FROM booking_conversations WHERE hotel_id IN ($inPh)");
        $stmt->execute($hotelIds);
        $unreadRow = $stmt->fetch();
        $stats['unread_msgs'] = (int)($unreadRow['unread'] ?? 0);

        // Recent 5 bookings
        $stmt = $pdo->prepare("SELECT b.*, h.name as hotel_name FROM guest_bookings b JOIN hotel_properties h ON b.hotel_id=h.id WHERE b.hotel_id IN ($inPh) ORDER BY b.created_at DESC LIMIT 6");
        $stmt->execute($hotelIds);
        $recentBookings = $stmt->fetchAll();
    }
} catch (Exception $e) { /* graceful */ }

$activeHotels  = count(array_filter($hotels, fn($h) => $h['status']==='approved' && $h['is_active']));
$pendingHotels = count(array_filter($hotels, fn($h) => $h['status']==='pending'));
$revenue = number_format((float)($stats['revenue'] ?? 0), 2);

function statusBadge($s) {
    return match($s) {
        'confirmed'  => '<span class="badge bg-success">Confirmada</span>',
        'pending'    => '<span class="badge bg-warning text-dark">Pendiente</span>',
        'cancelled'  => '<span class="badge bg-danger">Cancelada</span>',
        'completed'  => '<span class="badge bg-secondary">Completa</span>',
        default      => '<span class="badge bg-light text-dark">'.htmlspecialchars($s).'</span>'
    };
}
?>

<!-- PARTNER DASHBOARD -->
<div class="container-fluid px-3 py-3">

    <!-- Welcome -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="fw-bold" style="font-size:1.05rem">👋 Hola, <?= htmlspecialchars(explode(' ', $pname)[0]) ?>!</div>
            <div class="text-muted" style="font-size:.75rem">Panel de socio AiNi Travel</div>
        </div>
        <?php if (!empty($stats['unread_msgs']) && $stats['unread_msgs'] > 0): ?>
        <a href="?page=partner_messages" class="btn btn-sm rounded-pill" style="background:var(--aini-gradient);color:white;font-size:.75rem">
            💬 <?= $stats['unread_msgs'] ?> nuevos
        </a>
        <?php endif; ?>
    </div>

    <!-- Stats grid -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="card border-0 shadow-sm rounded-3 p-3" style="background:var(--aini-gradient);color:white">
                <div style="font-size:.72rem;opacity:.85">💰 Ingresos totales</div>
                <div class="fw-bold mt-1" style="font-size:1.3rem">$<?= $revenue ?></div>
                <div style="font-size:.68rem;opacity:.7">USD</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted" style="font-size:.72rem">🗓️ Próximas reservas</div>
                <div class="fw-bold mt-1" style="font-size:1.3rem;color:var(--aini-purple)"><?= $stats['upcoming'] ?? 0 ?></div>
                <div class="text-muted" style="font-size:.68rem">check-ins pendientes</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 p-2 text-center">
                <div class="fw-bold" style="color:#f59e0b;font-size:1.1rem"><?= $stats['pending'] ?? 0 ?></div>
                <div class="text-muted" style="font-size:.68rem">Por confirmar</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 p-2 text-center">
                <div class="fw-bold text-success" style="font-size:1.1rem"><?= $stats['confirmed'] ?? 0 ?></div>
                <div class="text-muted" style="font-size:.68rem">Confirmadas</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 p-2 text-center">
                <div class="fw-bold" style="font-size:1.1rem"><?= $stats['total'] ?? 0 ?></div>
                <div class="text-muted" style="font-size:.68rem">Total</div>
            </div>
        </div>
    </div>

    <!-- Hotels list -->
    <div class="fw-semibold mb-2 d-flex justify-content-between align-items-center" style="font-size:.9rem">
        <span>🏨 Mis propiedades</span>
        <span class="text-muted" style="font-size:.75rem"><?= $activeHotels ?> activa<?= $activeHotels!=1?'s':'' ?><?= $pendingHotels>0?" · {$pendingHotels} pendiente".($pendingHotels!=1?'s':''):'' ?></span>
    </div>

    <?php if (empty($hotels)): ?>
    <div class="card border-0 shadow-sm rounded-3 p-4 text-center text-muted mb-3">
        <div style="font-size:2.5rem">🏨</div>
        <div class="mt-2 small">No tienes propiedades registradas aún</div>
    </div>
    <?php else: foreach ($hotels as $hotel):
        $img = '';
        if (!empty($hotel['images'])) { $imgs = json_decode($hotel['images'],true); if(is_array($imgs)&&count($imgs)>0) $img=$imgs[0]; }
        $emoji = $hotel['emoji'] ?? '🏨';
        $hStatus = $hotel['status'] ?? 'pending';
        $statusCls = match($hStatus) { 'approved'=>'bg-success', 'pending'=>'bg-warning text-dark', 'rejected'=>'bg-danger', default=>'bg-secondary' };
        $statusLabel = match($hStatus) { 'approved'=>'Activo', 'pending'=>'Pendiente aprobación', 'rejected'=>'Rechazado', default=>$hStatus };
    ?>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-2">
        <div class="d-flex">
            <?php if ($img): ?>
            <div style="width:70px;height:70px;background:url('<?= htmlspecialchars($img) ?>') center/cover no-repeat;flex-shrink:0"></div>
            <?php else: ?>
            <div class="d-flex align-items-center justify-content-center" style="width:70px;height:70px;background:#f3f4f6;font-size:1.8rem;flex-shrink:0"><?= $emoji ?></div>
            <?php endif; ?>
            <div class="p-2 d-flex flex-column justify-content-center" style="flex:1;min-width:0">
                <div class="fw-semibold text-truncate" style="font-size:.85rem"><?= htmlspecialchars($hotel['name']) ?></div>
                <div class="text-muted text-truncate" style="font-size:.73rem"><?= htmlspecialchars($hotel['location']) ?></div>
                <div class="mt-1">
                    <span class="badge <?= $statusCls ?>" style="font-size:.65rem"><?= $statusLabel ?></span>
                    <?php if ($hotel['star_category']): ?>
                    <span class="ms-1" style="font-size:.72rem">
                        <?= str_repeat('⭐', (int)$hotel['star_category']) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>

    <!-- Quick nav -->
    <div class="row g-2 mb-3 mt-1">
        <?php $quickLinks = [
            ['?page=partner_bookings','🗓️','Reservas'],
            ['?page=partner_messages','💬','Mensajes'],
            ['?page=partner_rooms','🛏️','Habitaciones'],
            ['?page=partner_transactions','💳','Ingresos'],
        ]; foreach ($quickLinks as $ql): ?>
        <div class="col-6">
            <a href="<?= $ql[0] ?>" class="card border-0 shadow-sm rounded-3 p-3 text-decoration-none text-dark d-flex flex-row align-items-center gap-2">
                <span style="font-size:1.3rem"><?= $ql[1] ?></span>
                <span class="fw-semibold" style="font-size:.85rem"><?= $ql[2] ?></span>
                <span class="ms-auto text-muted">›</span>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent bookings -->
    <?php if (!empty($recentBookings)): ?>
    <div class="fw-semibold mb-2" style="font-size:.9rem">📋 Reservas recientes</div>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
        <?php foreach ($recentBookings as $i => $b): ?>
        <div class="px-3 py-2 d-flex align-items-center gap-2 <?= $i < count($recentBookings)-1 ? 'border-bottom' : '' ?>">
            <div style="flex:1;min-width:0">
                <div class="fw-semibold text-truncate" style="font-size:.82rem"><?= htmlspecialchars($b['guest_name']) ?></div>
                <div class="text-muted text-truncate" style="font-size:.72rem"><?= htmlspecialchars($b['hotel_name']) ?> · <?= date('d/m', strtotime($b['check_in'])) ?>–<?= date('d/m', strtotime($b['check_out'])) ?></div>
            </div>
            <div class="text-end">
                <?= statusBadge($b['status']) ?>
                <div class="text-muted mt-1" style="font-size:.7rem">$<?= number_format($b['total_price'],2) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <a href="?page=partner_bookings" class="btn w-100 rounded-3 btn-outline-secondary mb-2" style="font-size:.85rem">Ver todas las reservas</a>
    <?php endif; ?>

</div>
