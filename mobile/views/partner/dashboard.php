<?php
/**
 * Partner - Dashboard
 */
$pid   = (int)($_SESSION['partner_id'] ?? 0);
$pname = $_SESSION['partner_name'] ?? 'Partner';

$partner = []; $hotels = []; $stats = []; $recentBookings = []; $dbError = '';

try {
    $dpdo = new PDO('mysql:host=localhost;dbname=hotel_booking_system;charset=utf8mb4',
        'hoteluser', 'hotelpass123',
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);

    $st = $dpdo->prepare("SELECT * FROM aini_partner_businesses WHERE id=?");
    $st->execute([$pid]);
    $partner = $st->fetch() ?: [];
    $pEmail  = $partner['email'] ?? ($_SESSION['partner_email'] ?? '');

    $st = $dpdo->prepare("SELECT id,name,location,status,is_active,star_category,images FROM hotel_properties WHERE partner_business_id=? OR (email=? AND email!='') ORDER BY created_at DESC");
    $st->execute([$pid, $pEmail]);
    $hotels = $st->fetchAll();

    $hids = array_column($hotels, 'id');
    if (!empty($hids)) {
        $ph = implode(',', array_fill(0, count($hids), '?'));
        $st = $dpdo->prepare("SELECT COUNT(*) as total, SUM(status='pending') as pending, SUM(status='confirmed') as confirmed, SUM(CASE WHEN status IN('confirmed','completed') THEN total_price ELSE 0 END) as revenue, SUM(check_in>=CURDATE() AND status!='cancelled') as upcoming FROM guest_bookings WHERE hotel_id IN($ph)");
        $st->execute($hids);
        $stats = $st->fetch() ?: [];

        $st = $dpdo->prepare("SELECT SUM(unread_count_owner) as unread FROM booking_conversations WHERE hotel_id IN($ph)");
        $st->execute($hids);
        $ur = $st->fetch();
        $stats['unread_msgs'] = (int)($ur['unread'] ?? 0);

        $st = $dpdo->prepare("SELECT b.*,h.name as hotel_name FROM guest_bookings b JOIN hotel_properties h ON b.hotel_id=h.id WHERE b.hotel_id IN($ph) ORDER BY b.created_at DESC LIMIT 6");
        $st->execute($hids);
        $recentBookings = $st->fetchAll();
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
    error_log("Partner dashboard: " . $e->getMessage());
}

$activeHotels  = count(array_filter($hotels, fn($h) => $h['status']==='approved' && $h['is_active']));
$pendingHotels = count(array_filter($hotels, fn($h) => $h['status']==='pending'));
$revenue       = number_format((float)($stats['revenue'] ?? 0), 2);
$firstName     = htmlspecialchars(explode(' ', $pname)[0]);
?>
<div class="container-fluid px-3 py-3 pb-5 mb-3">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="fw-bold" style="font-size:1.05rem">Hola, <?= $firstName ?>!</div>
            <div class="text-muted" style="font-size:.75rem">Panel de socio AiNi Travel</div>
        </div>
        <?php if (!empty($stats['unread_msgs'])): ?>
        <a href="?page=partner_messages" class="btn btn-sm rounded-pill" style="background:var(--aini-gradient);color:white;font-size:.75rem">
            <?= (int)$stats['unread_msgs'] ?> nuevos mensajes
        </a>
        <?php endif; ?>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="card border-0 shadow-sm rounded-3 p-3" style="background:var(--aini-gradient);color:white">
                <div style="font-size:.72rem;opacity:.85">Ingresos totales</div>
                <div class="fw-bold mt-1" style="font-size:1.3rem">$<?= $revenue ?></div>
                <div style="font-size:.68rem;opacity:.7">USD</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted" style="font-size:.72rem">Proximas reservas</div>
                <div class="fw-bold mt-1" style="font-size:1.3rem;color:var(--aini-purple)"><?= (int)($stats['upcoming'] ?? 0) ?></div>
                <div class="text-muted" style="font-size:.68rem">check-ins pendientes</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 p-2 text-center">
                <div class="fw-bold" style="color:#f59e0b;font-size:1.1rem"><?= (int)($stats['pending'] ?? 0) ?></div>
                <div class="text-muted" style="font-size:.68rem">Por confirmar</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 p-2 text-center">
                <div class="fw-bold text-success" style="font-size:1.1rem"><?= (int)($stats['confirmed'] ?? 0) ?></div>
                <div class="text-muted" style="font-size:.68rem">Confirmadas</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 p-2 text-center">
                <div class="fw-bold" style="font-size:1.1rem"><?= (int)($stats['total'] ?? 0) ?></div>
                <div class="text-muted" style="font-size:.68rem">Total</div>
            </div>
        </div>
    </div>

    <?php if (($activeHotelId ?? 0) > 0): ?>
    <div class="alert border-0 rounded-3 mb-2 py-2 px-3 d-flex align-items-center gap-2" style="background:#f0eaff;font-size:.80rem">
        <span style="color:var(--aini-purple)">&#9679;</span>
        <span class="fw-semibold text-truncate" style="color:var(--aini-purple);flex:1"><?= htmlspecialchars($activeHotelName ?: 'Propiedad seleccionada') ?></span>
        <a href="/mobile/?page=partner_dashboard&clear_hotel=1" class="text-muted text-decoration-none" style="font-size:.72rem;white-space:nowrap">Ver todas &times;</a>
    </div>
    <?php endif; ?>

    <div class="fw-semibold mb-2 d-flex justify-content-between align-items-center" style="font-size:.9rem">
        <span>Mis propiedades</span>
        <span class="text-muted" style="font-size:.75rem">
            <?= $activeHotels ?> activa<?= $activeHotels != 1 ? 's' : '' ?>
            <?php if ($pendingHotels > 0): ?>&middot; <?= $pendingHotels ?> pendiente<?= $pendingHotels != 1 ? 's' : '' ?><?php endif; ?>
        </span>
    </div>

    <?php if ($dbError): ?><div class="alert alert-danger small">Error: <?= htmlspecialchars($dbError) ?></div><?php endif; ?>

    <?php if (empty($hotels) && !$dbError): ?>
    <div class="card border-0 shadow-sm rounded-3 p-4 text-center text-muted mb-3">
        <div class="small">No tienes propiedades registradas aun.</div>
        <a href="/partner_register.php" class="btn btn-sm mt-2" style="background:var(--aini-gradient);color:white;border-radius:10px">+ Registrar propiedad</a>
    </div>
    <?php endif; ?>

    <?php foreach ($hotels as $hotel): ?>
    <?php
        $img = '';
        $ri = json_decode($hotel['images'] ?? '[]', true);
        if (is_array($ri) && !empty($ri)) {
            if (is_string($ri[0])) {
                $img = $ri[0];
            } elseif (is_array($ri[0]) && !empty($ri[0]['path'])) {
                $img = $ri[0]['path'];
            }
        }
        $hs = $hotel['status'] ?? 'pending';
        $sc = 'bg-secondary'; $sl = htmlspecialchars($hs);
        if ($hs === 'approved')  { $sc = 'bg-success';           $sl = 'Activo'; }
        if ($hs === 'pending')   { $sc = 'bg-warning text-dark'; $sl = 'Pendiente'; }
        if ($hs === 'rejected')  { $sc = 'bg-danger';            $sl = 'Rechazado'; }
    ?>
    <a href="/mobile/?page=partner_bookings&hotel_id=<?= $hotel['id'] ?>&hotel_name=<?= urlencode($hotel['name']) ?>" class="card border-0 shadow-sm rounded-3 overflow-hidden mb-2 text-decoration-none text-dark" style="display:block">
        <div class="d-flex align-items-center">
            <?php if ($img): ?>
            <div style="width:70px;height:70px;background:url('<?= htmlspecialchars($img) ?>') center/cover no-repeat;flex-shrink:0"></div>
            <?php else: ?>
            <div class="d-flex align-items-center justify-content-center bg-light" style="width:70px;height:70px;flex-shrink:0">&#127976;</div>
            <?php endif; ?>
            <div class="p-2" style="flex:1;min-width:0">
                <div class="fw-semibold text-truncate" style="font-size:.85rem"><?= htmlspecialchars($hotel['name']) ?></div>
                <div class="text-muted text-truncate" style="font-size:.73rem"><?= htmlspecialchars($hotel['location']) ?></div>
                <div class="mt-1"><span class="badge <?= $sc ?>" style="font-size:.65rem"><?= $sl ?></span></div>
            </div>
            <div class="pe-3 text-muted">&rsaquo;</div>
        </div>
    </a>
    <?php endforeach; ?>

    <div class="row g-2 mb-3 mt-1">
        <div class="col-6"><a href="?page=partner_bookings" class="card border-0 shadow-sm rounded-3 p-3 text-decoration-none text-dark d-flex flex-row align-items-center gap-2"><span class="fw-semibold" style="font-size:.85rem">Reservas</span><span class="ms-auto text-muted">&rsaquo;</span></a></div>
        <div class="col-6"><a href="?page=partner_messages" class="card border-0 shadow-sm rounded-3 p-3 text-decoration-none text-dark d-flex flex-row align-items-center gap-2"><span class="fw-semibold" style="font-size:.85rem">Mensajes</span><span class="ms-auto text-muted">&rsaquo;</span></a></div>
        <div class="col-6"><a href="?page=partner_rooms" class="card border-0 shadow-sm rounded-3 p-3 text-decoration-none text-dark d-flex flex-row align-items-center gap-2"><span class="fw-semibold" style="font-size:.85rem">Habitaciones</span><span class="ms-auto text-muted">&rsaquo;</span></a></div>
        <div class="col-6"><a href="?page=partner_transactions" class="card border-0 shadow-sm rounded-3 p-3 text-decoration-none text-dark d-flex flex-row align-items-center gap-2"><span class="fw-semibold" style="font-size:.85rem">Ingresos</span><span class="ms-auto text-muted">&rsaquo;</span></a></div>
    </div>

    <?php if (!empty($recentBookings)): ?>
    <div class="fw-semibold mb-2" style="font-size:.9rem">Reservas recientes</div>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
        <?php foreach ($recentBookings as $i => $b): ?>
        <?php
            $bs = $b['status'] ?? '';
            $bc = 'bg-secondary';
            if ($bs === 'confirmed') $bc = 'bg-success';
            if ($bs === 'pending')   $bc = 'bg-warning text-dark';
            if ($bs === 'cancelled') $bc = 'bg-danger';
        ?>
        <div class="px-3 py-2 d-flex align-items-center gap-2<?= $i < count($recentBookings)-1 ? ' border-bottom' : '' ?>">
            <div style="flex:1;min-width:0">
                <div class="fw-semibold text-truncate" style="font-size:.82rem"><?= htmlspecialchars($b['guest_name'] ?? '') ?></div>
                <div class="text-muted text-truncate" style="font-size:.72rem"><?= htmlspecialchars($b['hotel_name'] ?? '') ?> &middot; <?= date('d/m', strtotime($b['check_in'])) ?>-<?= date('d/m', strtotime($b['check_out'])) ?></div>
            </div>
            <div class="text-end">
                <span class="badge <?= $bc ?>" style="font-size:.65rem"><?= htmlspecialchars($bs) ?></span>
                <div class="text-muted mt-1" style="font-size:.7rem">$<?= number_format((float)($b['total_price'] ?? 0), 2) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <a href="?page=partner_bookings" class="btn w-100 rounded-3 btn-outline-secondary mb-2" style="font-size:.85rem">Ver todas las reservas</a>
    <?php endif; ?>

</div>