<?php
/**
 * Partner - Bookings Management
 * List, confirm, cancel bookings for this partner's hotels
 */
$host='localhost'; $dbname='hotel_booking_system';
$dbuser='hoteluser'; $dbpass='hotelpass123';
$pid = $_SESSION['partner_id'];

// Handle status update (AJAX)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='update_status') {
    header('Content-Type: application/json');
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$dbuser,$dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $newStatus  = trim($_POST['status'] ?? '');
        if (!in_array($newStatus, ['confirmed','cancelled','completed'])) { echo json_encode(['success'=>false,'message'=>'Estado inválido']); exit(); }

        // Verify booking belongs to this partner's hotel
        $stmt = $pdo->prepare("
            SELECT b.id FROM guest_bookings b
            JOIN hotel_properties h ON b.hotel_id=h.id
            WHERE b.id=? AND (h.partner_business_id=? OR h.email=(SELECT email FROM aini_partner_businesses WHERE id=?))
        ");
        $stmt->execute([$booking_id, $pid, $pid]);
        if (!$stmt->fetch()) { echo json_encode(['success'=>false,'message'=>'Reserva no encontrada']); exit(); }

        $pdo->prepare("UPDATE guest_bookings SET status=? WHERE id=?")->execute([$newStatus, $booking_id]);
        echo json_encode(['success'=>true,'message'=>'Estado actualizado']);
    } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>'Error']); }
    exit();
}

// Load data
$bookings = [];
$filterStatus = $_GET['status'] ?? 'all';
$filterHotel  = intval($_GET['hotel_id'] ?? 0);
$hotels = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$dbuser,$dbpass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);

    $partnerEmail = $pdo->prepare("SELECT email FROM aini_partner_businesses WHERE id=?");
    $partnerEmail->execute([$pid]);
    $pEmail = $partnerEmail->fetchColumn() ?: '';

    $stmt = $pdo->prepare("SELECT id,name FROM hotel_properties WHERE partner_business_id=? OR email=? ORDER BY name");
    $stmt->execute([$pid,$pEmail]);
    $hotels = $stmt->fetchAll();
    $hotelIds = array_column($hotels,'id');

    if (!empty($hotelIds)) {
        $inPh = implode(',', array_fill(0, count($hotelIds), '?'));
        $params = $hotelIds;
        $where = "b.hotel_id IN ($inPh)";

        if ($filterStatus !== 'all') { $where .= " AND b.status=?"; $params[] = $filterStatus; }
        if ($filterHotel > 0)        { $where .= " AND b.hotel_id=?"; $params[] = $filterHotel; }

        $stmt = $pdo->prepare("
            SELECT b.*, h.name as hotel_name, h.emoji as hotel_emoji
            FROM guest_bookings b
            JOIN hotel_properties h ON b.hotel_id=h.id
            WHERE $where
            ORDER BY b.created_at DESC
            LIMIT 80
        ");
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();
    }
} catch (Exception $e) {}
?>

<!-- PARTNER BOOKINGS -->
<div class="container-fluid px-3 py-3">

    <!-- Filter bar -->
    <div class="d-flex gap-2 mb-3 overflow-auto pb-1" style="scrollbar-width:none">
        <?php
        $filters = ['all'=>'Todas','cancellation_requested'=>'⚠️ Cancelaciones','pending'=>'Pendientes','confirmed'=>'Confirmadas','completed'=>'Completadas','cancelled'=>'Canceladas'];
        foreach ($filters as $val => $label):
            $active = $filterStatus === $val;
        ?>
        <a href="?page=partner_bookings&status=<?= $val ?><?= $filterHotel?"&hotel_id=$filterHotel":'' ?>"
           class="btn btn-sm flex-shrink-0 rounded-pill <?= $active?'text-white':'btn-outline-secondary' ?>"
           style="font-size:.75rem;<?= $active?'background:var(--aini-gradient);border:none':'' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Hotel filter -->
    <?php if (count($hotels) > 1): ?>
    <div class="mb-3">
        <select class="form-select rounded-3" style="font-size:.83rem"
            onchange="window.location='?page=partner_bookings&status=<?= $filterStatus ?>&hotel_id='+this.value">
            <option value="0">Todas las propiedades</option>
            <?php foreach ($hotels as $h): ?>
            <option value="<?= $h['id'] ?>" <?= $filterHotel===$h['id']?'selected':'' ?>><?= htmlspecialchars($h['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <!-- Count -->
    <div class="text-muted mb-2" style="font-size:.78rem"><?= count($bookings) ?> reserva<?= count($bookings)!=1?'s':'' ?></div>

    <!-- Booking cards -->
    <?php if (empty($bookings)): ?>
    <div class="text-center py-5 text-muted">
        <div style="font-size:3rem">📭</div>
        <div class="mt-2">No hay reservas<?= $filterStatus!=='all'?" con estado «{$filters[$filterStatus]}»":'' ?></div>
    </div>
    <?php else: foreach ($bookings as $b):
        $status = $b['status'] ?? 'pending';
        $badgeCls = match($status) {
            'confirmed'              => 'bg-success',
            'pending'                => 'bg-warning text-dark',
            'cancelled'              => 'bg-danger',
            'completed'              => 'bg-secondary',
            'cancellation_requested' => 'text-white',
            default                  => 'bg-light text-dark'
        };
        $badgeStyle = $status === 'cancellation_requested' ? 'background:#f97316' : '';
        $badgeLabel = match($status) {
            'confirmed'              => 'Confirmada',
            'pending'                => 'Pendiente',
            'cancelled'              => 'Cancelada',
            'completed'              => 'Completada',
            'cancellation_requested' => '⏳ Cancelación solicitada',
            default                  => ucfirst($status)
        };
        $canConfirm       = $status === 'pending';
        $canComplete      = $status === 'confirmed' && $b['check_out'] <= date('Y-m-d');
        $canCancel        = in_array($status, ['pending','confirmed']);
        $canConfirmCancel = $status === 'cancellation_requested';
    ?>
    <div class="card border-0 shadow-sm rounded-3 mb-2 overflow-hidden" id="bcard_<?= $b['id'] ?>">
        <!-- Colour strip by status -->
        <div style="height:4px;background:<?= match($status) { 'confirmed'=>'#16a34a','pending'=>'#f59e0b','cancelled'=>'#dc2626','cancellation_requested'=>'#f97316',default=>'#9ca3af' } ?>"></div>
        <div class="p-3">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="fw-semibold" style="font-size:.88rem"><?= htmlspecialchars($b['guest_name']) ?></div>
                <span class="badge <?= $badgeCls ?>" style="font-size:.68rem;<?= $badgeStyle ?>"><?= $badgeLabel ?></span>
            </div>
            <div class="text-muted mb-2" style="font-size:.75rem">
                <?= ($b['hotel_emoji']??'🏨') ?> <?= htmlspecialchars($b['hotel_name']) ?>
            </div>
            <div class="row g-1 mb-2" style="font-size:.77rem">
                <div class="col-6"><span class="text-muted">📅 In:</span> <strong><?= date('d/m/Y', strtotime($b['check_in'])) ?></strong></div>
                <div class="col-6"><span class="text-muted">📅 Out:</span> <strong><?= date('d/m/Y', strtotime($b['check_out'])) ?></strong></div>
                <div class="col-6"><span class="text-muted">👥 Huéspedes:</span> <strong><?= $b['guests'] ?></strong></div>
                <div class="col-6"><span class="text-muted">🛏️ Noches:</span> <strong><?= $b['nights'] ?></strong></div>
            </div>

            <?php if (!empty($b['guest_phone'])): ?>
            <div class="mb-2" style="font-size:.77rem">
                📱 <a href="tel:<?= htmlspecialchars($b['guest_phone']) ?>" class="text-decoration-none"><?= htmlspecialchars($b['guest_phone']) ?></a>
                &nbsp;
                <a href="https://wa.me/<?= preg_replace('/\D/','',$b['guest_phone']) ?>" target="_blank" style="color:#25d366">WhatsApp</a>
            </div>
            <?php endif; ?>

            <?php if (!empty($b['special_requests'])): ?>
            <div class="mb-2 p-2 rounded-2" style="background:#fef9e7;font-size:.75rem">
                💬 <?= htmlspecialchars($b['special_requests']) ?>
            </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center">
                <div class="fw-bold" style="color:var(--aini-purple);font-size:.9rem">
                    $<?= number_format($b['total_price'],2) ?> <?= htmlspecialchars($b['currency']??'USD') ?>
                </div>
                <div class="d-flex gap-1">
                    <?php if ($canConfirm): ?>
                    <button class="btn btn-sm btn-success py-0 px-2 rounded-pill" style="font-size:.72rem"
                        onclick="updateStatus(<?= $b['id'] ?>,'confirmed',this,'Confirmada','bg-success')">✅ Confirmar</button>
                    <?php endif; ?>
                    <?php if ($canConfirmCancel): ?>
                    <button class="btn btn-sm btn-danger py-0 px-2 rounded-pill" style="font-size:.72rem"
                        onclick="updateStatus(<?= $b['id'] ?>,'cancelled',this,'Cancelada','bg-danger')">✅ Confirmar cancelación</button>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" style="font-size:.72rem"
                        onclick="updateStatus(<?= $b['id'] ?>,'confirmed',this,'Confirmada','bg-success')">↩️ Rechazar</button>
                    <?php endif; ?>
                    <?php if ($canComplete): ?>
                    <button class="btn btn-sm btn-secondary py-0 px-2 rounded-pill" style="font-size:.72rem"
                        onclick="updateStatus(<?= $b['id'] ?>,'completed',this,'Completada','bg-secondary')">☑️ Completar</button>
                    <?php endif; ?>
                    <?php if ($canCancel): ?>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill" style="font-size:.72rem"
                        onclick="updateStatus(<?= $b['id'] ?>,'cancelled',this,'Cancelada','bg-danger')">❌ Cancelar</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($b['booking_reference'])): ?>
            <div class="text-muted mt-1" style="font-size:.68rem">Ref: <?= htmlspecialchars($b['booking_reference']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<script>
const AINI_PID = <?= intval($_SESSION['partner_id'] ?? 0) ?>;
const AINI_PTOK = '<?= sha1(intval($_SESSION['partner_id'] ?? 0) . date('Y-m-d') . 'aini2024_mobile_secret_partner') ?>';
function updateStatus(id, newStatus, btn, label, badgeCls) {
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = '…';
    const fd = new FormData();
    fd.append('action','update_status');
    fd.append('booking_id', id);
    fd.append('status', newStatus);
    fd.append('pid', AINI_PID);
    fd.append('ptok', AINI_PTOK);
    fetch('/mobile/?page=api', {method:'POST', body:fd})
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const card = document.getElementById('bcard_'+id);
                if (card) {
                    const badge = card.querySelector('.badge');
                    const stripColors = {confirmed:'#16a34a',cancelled:'#dc2626',completed:'#9ca3af'};
                    if (badge) {
                        badge.className = 'badge ' + (badgeCls || 'bg-secondary');
                        badge.style.background = '';
                        badge.textContent = label || newStatus;
                    }
                    card.querySelector('div[style*="height:4px"]').style.background = stripColors[newStatus] || '#9ca3af';
                    // Remove action buttons
                    card.querySelectorAll('button').forEach(b => b.remove());
                    showToast('✅ ' + d.message);
                }
            } else {
                showToast('❌ ' + (d.message||'Error'), 'danger');
                btn.disabled = false; btn.textContent = orig;
            }
        })
        .catch(e => { btn.disabled=false; btn.textContent=orig; showToast('❌ Error: '+e.message,'danger'); });
}

function showToast(msg, type='success') {
    const t = document.createElement('div');
    t.className='position-fixed bottom-0 start-50 translate-middle-x mb-5 alert alert-'+(type==='danger'?'danger':'success')+' shadow py-2 px-3 rounded-3';
    t.style.cssText='z-index:9999;font-size:.85rem;min-width:200px;text-align:center';
    t.textContent=msg;
    document.body.appendChild(t);
    setTimeout(()=>t.remove(),3000);
}
</script>
