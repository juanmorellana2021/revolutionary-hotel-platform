<?php
/**
 * Traveler - My Bookings
 * Queries: guest_bookings + hotel_properties
 */
$host = 'localhost'; $dbname = 'hotel_booking_system';
$dbuser = 'hoteluser'; $dbpass = 'hotelpass123';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) { $pdo = null; }

// Handle cancel action (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    header('Content-Type: application/json');
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $uid = $_SESSION['user_id'];
    if (!$pdo || !$booking_id) { echo json_encode(['success'=>false,'message'=>'Error']); exit(); }
    try {
        $stmt = $pdo->prepare("SELECT * FROM guest_bookings WHERE id=? AND user_id=?");
        $stmt->execute([$booking_id, $uid]);
        $b = $stmt->fetch();
        if (!$b) { echo json_encode(['success'=>false,'message'=>'Reserva no encontrada']); exit(); }
        if (in_array($b['status'], ['cancelled','completed'])) { echo json_encode(['success'=>false,'message'=>'No se puede cancelar']); exit(); }
        if ($b['status'] === 'cancellation_requested') { echo json_encode(['success'=>false,'message'=>'Ya tienes una solicitud de cancelación pendiente']); exit(); }
        $pdo->prepare("UPDATE guest_bookings SET status='cancellation_requested' WHERE id=? AND user_id=?")->execute([$booking_id, $uid]);
        echo json_encode(['success'=>true,'message'=>'Solicitud de cancelación enviada al hotel']); exit();
    } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>'Error al cancelar']); exit(); }
}

// Load bookings
$bookings = [];
$stats = ['upcoming'=>0, 'past'=>0, 'cancelled'=>0];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, h.name as hotel_name, h.location, h.images as hotel_images, h.emoji as hotel_emoji,
                   h.email as hotel_email, h.phone as hotel_phone
            FROM guest_bookings b
            LEFT JOIN hotel_properties h ON b.hotel_id = h.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $bookings = $stmt->fetchAll();
        $today = date('Y-m-d');
        foreach ($bookings as $b) {
            if ($b['status'] === 'cancelled') $stats['cancelled']++;
            elseif ($b['check_in'] >= $today) $stats['upcoming']++;
            else $stats['past']++;
        }
    } catch (Exception $e) { $bookings = []; }
}
?>

<!-- MY BOOKINGS -->
<div class="container-fluid px-3 py-3">

    <!-- Stats row -->
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card text-center border-0 shadow-sm rounded-3 py-2">
                <div class="fw-bold fs-4" style="color:var(--aini-purple)"><?= $stats['upcoming'] ?></div>
                <div class="text-muted" style="font-size:.72rem">Próximas</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center border-0 shadow-sm rounded-3 py-2">
                <div class="fw-bold fs-4 text-success"><?= $stats['past'] ?></div>
                <div class="text-muted" style="font-size:.72rem">Completadas</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center border-0 shadow-sm rounded-3 py-2">
                <div class="fw-bold fs-4 text-danger"><?= $stats['cancelled'] ?></div>
                <div class="text-muted" style="font-size:.72rem">Canceladas</div>
            </div>
        </div>
    </div>

    <!-- Filter tabs -->
    <ul class="nav nav-pills nav-fill mb-3" id="bookingTabs">
        <li class="nav-item"><button class="nav-link active py-1 px-2" onclick="filterBookings('all',this)" style="font-size:.82rem">Todas</button></li>
        <li class="nav-item"><button class="nav-link py-1 px-2" onclick="filterBookings('upcoming',this)" style="font-size:.82rem">Próximas</button></li>
        <li class="nav-item"><button class="nav-link py-1 px-2" onclick="filterBookings('past',this)" style="font-size:.82rem">Pasadas</button></li>
        <li class="nav-item"><button class="nav-link py-1 px-2" onclick="filterBookings('cancelled',this)" style="font-size:.82rem">Canceladas</button></li>
    </ul>

    <!-- Booking cards -->
    <div id="bookingsList">
        <?php if (empty($bookings)): ?>
        <div class="text-center py-5 text-muted">
            <div style="font-size:3rem">🏨</div>
            <div class="mt-2">No tienes reservas aún</div>
            <a href="?page=hotels" class="btn btn-sm mt-3" style="background:var(--aini-gradient);color:white;border-radius:20px;">Explorar hoteles</a>
        </div>
        <?php else: ?>
        <?php $today = date('Y-m-d'); foreach ($bookings as $b):
            $status = $b['status'] ?? 'pending';
            $checkin = $b['check_in'] ?? '';
            if ($status === 'cancelled') $filterClass = 'filter-cancelled';
            elseif ($status === 'cancellation_requested') $filterClass = 'filter-upcoming';
            elseif ($checkin >= $today) $filterClass = 'filter-upcoming';
            else $filterClass = 'filter-past';

            $statusBadge = match($status) {
                'confirmed'               => '<span class="badge bg-success">Confirmada</span>',
                'pending'                 => '<span class="badge bg-warning text-dark">Pendiente</span>',
                'cancelled'               => '<span class="badge bg-danger">Cancelada</span>',
                'completed'               => '<span class="badge bg-secondary">Completada</span>',
                'cancellation_requested'  => '<span class="badge text-white" style="background:#f97316">⏳ Cancelación solicitada</span>',
                default                   => '<span class="badge bg-light text-dark">'.ucfirst($status).'</span>'
            };

            // Hotel image
            $img = '';
            if (!empty($b['hotel_images'])) {
                $imgs = json_decode($b['hotel_images'], true);
                if (is_array($imgs) && count($imgs) > 0) {
                    $img = is_array($imgs[0]) ? ($imgs[0]['path'] ?? '') : $imgs[0];
                }
            }
            $emoji = $b['hotel_emoji'] ?? '🏨';
        ?>
        <div class="aini-booking-card mb-3 <?= $filterClass ?>" data-status="<?= $status ?>" data-checkin="<?= $checkin ?>">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <!-- Hotel image strip -->
                <?php if ($img): ?>
                <div style="height:90px;background:url('<?= htmlspecialchars($img) ?>') center/cover no-repeat;"></div>
                <?php else: ?>
                <div class="d-flex align-items-center justify-content-center" style="height:60px;background:linear-gradient(135deg,#667eea22,#764ba222);font-size:2rem"><?= $emoji ?></div>
                <?php endif; ?>

                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="fw-semibold" style="font-size:.93rem"><?= htmlspecialchars($b['hotel_name'] ?? 'Hotel') ?></div>
                        <?= $statusBadge ?>
                    </div>
                    <div class="text-muted mb-2" style="font-size:.78rem">
                        <?= htmlspecialchars($b['location'] ?? '') ?>
                    </div>

                    <!-- Room + dates grid -->
                    <div class="row g-1 mb-2" style="font-size:.78rem">
                        <div class="col-6">
                            <span class="text-muted">🛏️ Habitación:</span><br>
                            <strong>
                                <?php
                                    $rc = intval($b['room_count'] ?? 1);
                                    echo ($rc > 1 ? $rc . 'x ' : '') . htmlspecialchars($b['room_type'] ?? 'Estándar');
                                ?>
                            </strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted">🌙 Noches:</span><br>
                            <strong><?= $b['nights'] ?? 1 ?></strong>
                        </div>
                        <div class="col-6 mt-1">
                            <span class="text-muted">📅 Check-in:</span><br>
                            <strong><?= date('d/m/Y', strtotime($b['check_in'])) ?></strong>
                        </div>
                        <div class="col-6 mt-1">
                            <span class="text-muted">📅 Check-out:</span><br>
                            <strong><?= date('d/m/Y', strtotime($b['check_out'])) ?></strong>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold" style="color:var(--aini-purple)">
                                <?= number_format($b['total_price'], 2) ?> <?= htmlspecialchars($b['currency'] ?? 'USD') ?>
                            </span>
                        </div>
                        <div class="d-flex gap-1">
                            <?php if (!empty($b['aini_coins_earned']) && $b['aini_coins_earned'] > 0): ?>
                            <span class="badge" style="background:#ff9800;font-size:.68rem">🪙 +<?= $b['aini_coins_earned'] ?></span>
                            <?php endif; ?>
                            <?php if (!in_array($status, ['cancelled','completed','cancellation_requested'])): ?>
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:.72rem;border-radius:12px;"
                                onclick="cancelBooking(<?= $b['id'] ?>, this)">Cancelar</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($b['booking_reference'])): ?>
                    <div class="mt-1" style="font-size:.72rem">
                        <span class="text-muted">Ref:</span>
                        <span class="fw-bold" style="color:var(--aini-purple);letter-spacing:.04em"><?= htmlspecialchars($b['booking_reference']) ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- Action buttons -->
                    <div class="d-flex gap-2 mt-2">
                        <?php
                            $wa_num = preg_replace('/[^0-9]/', '', $b['hotel_phone'] ?? '');
                            $wa_msg = urlencode('Hola! Tengo una reserva con referencia ' . ($b['booking_reference'] ?? '') . ' en ' . ($b['hotel_name'] ?? 'el hotel'));
                        ?>
                        <?php if ($wa_num): ?>
                        <a href="https://wa.me/<?= $wa_num ?>?text=<?= $wa_msg ?>" target="_blank"
                           class="btn btn-sm flex-fill fw-semibold py-1"
                           style="background:#25D366;color:white;border-radius:10px;font-size:.76rem;">💬 WhatsApp</a>
                        <?php endif; ?>
                        <a href="?page=messages&booking_id=<?= $b['id'] ?>"
                           class="btn btn-sm flex-fill btn-outline-secondary py-1"
                           style="border-radius:10px;font-size:.76rem;">✉️ Mensajes</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Cancel confirm modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-body text-center p-4">
                <div style="font-size:2.5rem">⚠️</div>
                <h5 class="mt-2 mb-1">¿Solicitar cancelación?</h5>
                <p class="text-muted small mb-3">Se enviará una solicitud al hotel. La cancelación se confirmará cuando el hotel la apruebe.</p>
                <button class="btn btn-danger w-100 mb-2 rounded-3" id="confirmCancelBtn">Sí, solicitar cancelación</button>
                <button class="btn btn-light w-100 rounded-3" data-bs-dismiss="modal">Mantener reserva</button>
            </div>
        </div>
    </div>
</div>

<style>
.aini-booking-card { animation: cardSlideIn .35s ease both; }
</style>

<script>
const AINI_UID = <?= intval($_SESSION['user_id'] ?? 0) ?>;
const AINI_TOK = '<?= sha1(intval($_SESSION['user_id'] ?? 0) . date('Y-m-d') . 'aini2024_mobile_secret') ?>';
let activeCancelId = null;
let activeCancelBtn = null;

function filterBookings(type, btn) {
    document.querySelectorAll('#bookingTabs .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.aini-booking-card').forEach(card => {
        if (type === 'all') { card.style.display = ''; return; }
        if (type === 'upcoming') card.style.display = card.classList.contains('filter-upcoming') ? '' : 'none';
        if (type === 'past') card.style.display = card.classList.contains('filter-past') ? '' : 'none';
        if (type === 'cancelled') card.style.display = card.classList.contains('filter-cancelled') ? '' : 'none';
    });
}

function cancelBooking(id, btn) {
    activeCancelId = id;
    activeCancelBtn = btn;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}

document.getElementById('confirmCancelBtn').addEventListener('click', function() {
    if (!activeCancelId) return;
    this.disabled = true;
    this.textContent = 'Cancelando…';
    const fd = new FormData();
    fd.append('action', 'request_cancel');
    fd.append('booking_id', activeCancelId);
    fd.append('uid', AINI_UID);
    fd.append('tok', AINI_TOK);
    fetch('/mobile/?page=api', { method: 'POST', body: fd })
        .then(r => r.text())
        .then(txt => {
            let d;
            try { d = JSON.parse(txt); } catch(e) {
                showToast('❌ Error: ' + txt.substring(0,80), 'danger');
                return;
            }
            bootstrap.Modal.getInstance(document.getElementById('cancelModal')).hide();
            if (d.success) {
                // Update card to show cancellation requested
                const card = activeCancelBtn.closest('.aini-booking-card');
                const badge = card.querySelector('.badge');
                badge.className = 'badge text-white';
                badge.style.background = '#f97316';
                badge.textContent = '⏳ Cancelación solicitada';
                activeCancelBtn.remove();
                showToast('✅ Solicitud enviada al hotel');
            } else {
                showToast('❌ ' + (d.message || 'Error al cancelar'), 'danger');
            }
        })
        .catch(e => showToast('❌ Fetch error: ' + e.message, 'danger'))
        .finally(() => { this.disabled = false; this.textContent = 'Sí, cancelar'; });
});

function showToast(msg, type='success') {
    const t = document.createElement('div');
    t.className = 'position-fixed bottom-0 start-50 translate-middle-x mb-5 alert alert-' + (type==='danger'?'danger':'success') + ' shadow py-2 px-3 rounded-3';
    t.style.cssText = 'z-index:9999;font-size:.85rem;min-width:200px;text-align:center';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}
</script>
