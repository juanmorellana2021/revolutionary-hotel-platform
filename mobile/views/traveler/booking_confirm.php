<?php
/**
 * Mobile - Booking Confirmation Page
 * Guest fills details, picks dates, submits to booking_api.php
 */

// Must be logged in (enforced by router, but double-check)
if (!$isLoggedIn) {
    header('Location: ?page=login&redirect=' . urlencode('booking_confirm&' . http_build_query($_GET)));
    exit();
}

$hotel_id  = intval($_GET['hotel_id'] ?? 0);
$room_type = $_GET['room_type'] ?? '';
$room_id   = intval($_GET['room_id'] ?? 0);
$price     = floatval($_GET['price'] ?? 0);
$currency  = in_array($_GET['currency'] ?? '', ['USD','PEN']) ? $_GET['currency'] : 'USD';
$default_qty = max(1, min(10, intval($_GET['qty'] ?? 1)));

if (!$hotel_id) {
    header('Location: ?page=hotels');
    exit();
}

$host = 'localhost'; $dbname = 'hotel_booking_system';
$dbuser = 'hoteluser'; $dbpass = 'hotelpass123';

$hotel = null;
$user  = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $stmt = $pdo->prepare("SELECT * FROM hotel_properties WHERE id = ? AND status = 'approved' AND is_active = 1");
    $stmt->execute([$hotel_id]);
    $hotel = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM ainitravel_users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

} catch (Exception $e) {}

if (!$hotel) {
    header('Location: ?page=hotels');
    exit();
}

$sym = $currency === 'PEN' ? 'S/' : '$';
$images = json_decode($hotel['images'] ?? '[]', true) ?: [];
$thumb  = !empty($images) ? (is_array($images[0]) ? $images[0]['path'] : $images[0]) : '';

$default_checkin  = date('Y-m-d', strtotime('+1 day'));
$default_checkout = date('Y-m-d', strtotime('+2 days'));

// If no price passed (direct URL), fall back to hotel base price
if (!$price) $price = $hotel['price_per_night'] ?? $hotel['price'] ?? 0;
?>

<!-- Back bar -->
<div class="d-flex align-items-center px-3 py-2 border-bottom bg-white sticky-top" style="z-index:100;">
    <button class="btn btn-sm btn-light me-2" onclick="history.back()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
        </svg>
    </button>
    <span class="fw-semibold" style="font-size:.9rem;">Confirmar Reserva</span>
</div>

<!-- Success screen (hidden until booking done) -->
<div id="successScreen" style="display:none;" class="px-3 py-5 text-center">
    <div style="font-size:4rem;">🎉</div>
    <h2 class="fw-bold mt-3 mb-1" style="color:var(--aini-purple);">¡Reserva Confirmada!</h2>
    <p class="text-muted small mb-4">Tu solicitud fue enviada exitosamente</p>

    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 text-start">
        <div class="small text-muted mb-1">Referencia</div>
        <div class="fw-bold fs-5" id="bookingRef" style="color:var(--aini-purple);letter-spacing:.05em;">-</div>
    </div>

    <div class="card border-0 bg-light rounded-3 p-3 mb-4 text-start small text-muted">
        📧 Recibirás un email de confirmación en breve.<br>
        El hotel te contactará para confirmar tu reserva.
    </div>

    <div class="d-flex flex-column gap-2">
        <a href="?page=bookings" class="btn aini-btn-primary w-100 py-2 fw-semibold">
            📋 Ver Mis Reservas
        </a>
        <a href="?page=hotels" class="btn btn-outline-secondary w-100 py-2">
            🏨 Ver Más Hoteles
        </a>
    </div>
</div>

<!-- Booking form -->
<div id="bookingForm" class="px-3 pb-5">

    <!-- Hotel summary card -->
    <div class="card border-0 shadow-sm rounded-3 my-3 overflow-hidden">
        <div class="d-flex">
            <?php if ($thumb): ?>
            <img src="<?= htmlspecialchars($thumb) ?>?w=200&q=70"
                 style="width:90px;height:90px;object-fit:cover;flex-shrink:0;"
                 loading="eager"
                 onerror="this.style.display='none'">
            <?php endif; ?>
            <div class="p-3 flex-grow-1">
                <div class="fw-bold" style="font-size:.9rem;"><?= htmlspecialchars($hotel['name']) ?></div>
                <div class="text-muted small">📍 <?= htmlspecialchars($hotel['location']) ?></div>
                <?php if ($room_type): ?>
                <span class="badge mt-1" style="background:var(--aini-gradient);color:white;font-size:.72rem;">
                    🛏️ <?= htmlspecialchars($room_type) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dates -->
    <div class="mb-3">
        <div class="fw-semibold mb-2" style="font-size:.88rem;">📅 Fechas de Estadía</div>
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label small text-muted mb-1">Check-in *</label>
                <input type="date" id="checkin" class="form-control form-control-sm"
                       value="<?= $default_checkin ?>"
                       min="<?= date('Y-m-d') ?>"
                       onchange="updateCheckout(); recalc();">
            </div>
            <div class="col-6">
                <label class="form-label small text-muted mb-1">Check-out *</label>
                <input type="date" id="checkout" class="form-control form-control-sm"
                       value="<?= $default_checkout ?>"
                       min="<?= $default_checkin ?>"
                       onchange="recalc();">
            </div>
        </div>
        <div class="small text-muted mt-1">
            <span id="nightsLabel">1 noche</span>
        </div>
    </div>

    <!-- Guest info -->
    <div class="mb-3">
        <div class="fw-semibold mb-2" style="font-size:.88rem;">👤 Datos del Huésped</div>
        <div class="d-flex flex-column gap-2">
            <input type="text" id="guestName" class="form-control form-control-sm"
                   placeholder="Nombre completo *"
                   value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                   required>
            <input type="email" id="guestEmail" class="form-control form-control-sm"
                   placeholder="Email *"
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                   required>
            <input type="tel" id="guestPhone" class="form-control form-control-sm"
                   placeholder="Teléfono * (ej: +51 999 999 999)"
                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                   required>
        </div>
    </div>

    <!-- Quantity -->
    <div class="mb-3">
        <div class="fw-semibold mb-2" style="font-size:.88rem;">🛏️ Cantidad de Habitaciones</div>
        <select id="roomQty" class="form-select form-select-sm" onchange="recalc()">
            <?php for ($q = 1; $q <= 5; $q++): ?>
            <option value="<?= $q ?>" <?= $q === $default_qty ? 'selected' : '' ?>>
                <?= $q ?> habitación<?= $q > 1 ? 'es' : '' ?>
            </option>
            <?php endfor; ?>
        </select>
    </div>

    <!-- Special requests -->
    <div class="mb-3">
        <div class="fw-semibold mb-2" style="font-size:.88rem;">💬 Solicitudes Especiales</div>
        <textarea id="specialRequests" class="form-control form-control-sm" rows="3"
                  placeholder="Ej: Cama extra, piso alto, llegada tarde..."></textarea>
    </div>

    <!-- Price summary -->
    <div class="card border-0 rounded-3 mb-4 p-3" style="background:linear-gradient(135deg,#667eea15,#764ba215);border:1px solid #667eea30!important;">
        <div class="fw-semibold mb-2" style="font-size:.88rem;">💰 Resumen de Precio</div>
        <div class="d-flex justify-content-between small mb-1">
            <span id="priceBreakdown"><?= $sym ?><?= number_format($price, 2) ?> × 1 noche × 1 hab.</span>
            <span id="subtotalLabel"><?= $sym ?><?= number_format($price, 2) ?></span>
        </div>
        <div class="d-flex justify-content-between small mb-1 text-success" id="coinsRow" style="display:<?= $hotel['aini_coins_per_night'] ? 'flex' : 'none' ?>!important;">
            <span>🪙 AiNi Coins que ganarás</span>
            <span id="coinsLabel">0</span>
        </div>
        <hr class="my-2">
        <div class="d-flex justify-content-between fw-bold">
            <span>Total</span>
            <span id="totalLabel" style="color:var(--aini-purple);"><?= $sym ?><?= number_format($price, 2) ?></span>
        </div>
        <div class="text-muted small mt-1">💳 Pagas en el hotel · Sin cargos online</div>
    </div>

    <!-- Error message -->
    <div id="errorMsg" class="alert alert-danger small py-2 mb-3" style="display:none;"></div>

    <!-- Submit -->
    <button id="submitBtn" onclick="submitBooking()" class="btn aini-btn-primary w-100 py-3 fw-bold mb-3" style="font-size:1rem;border-radius:12px;">
        ✅ Confirmar Reserva
    </button>

    <p class="text-center text-muted small mb-5">
        🔒 Tu información está segura · Cancelación gratuita hasta 24h antes
    </p>
</div>

<script>
const PRICE     = <?= json_encode($price) ?>;
const CURRENCY  = '<?= $sym ?>';
const HOTEL_ID  = <?= $hotel_id ?>;
const ROOM_TYPE = <?= json_encode($room_type) ?>;
const ROOM_ID   = <?= $room_id ?>;
const COINS_PER_NIGHT = <?= json_encode(intval($hotel['aini_coins_per_night'] ?? 0)) ?>;

function calcNights(c1, c2) {
    const d = (new Date(c2) - new Date(c1)) / 86400000;
    return d > 0 ? Math.round(d) : 1;
}

function updateCheckout() {
    const ci = document.getElementById('checkin').value;
    const co = document.getElementById('checkout');
    if (!ci) return;
    const next = new Date(ci);
    next.setDate(next.getDate() + 1);
    const nextStr = next.toISOString().split('T')[0];
    co.min = nextStr;
    if (co.value <= ci) co.value = nextStr;
}

function recalc() {
    const ci = document.getElementById('checkin').value;
    const co = document.getElementById('checkout').value;
    const qty = parseInt(document.getElementById('roomQty').value) || 1;
    const nights = calcNights(ci, co);
    const total = PRICE * nights * qty;
    const coins = COINS_PER_NIGHT * nights * qty;

    document.getElementById('nightsLabel').textContent = nights + ' noche' + (nights !== 1 ? 's' : '');
    document.getElementById('priceBreakdown').textContent =
        CURRENCY + parseFloat(PRICE).toFixed(2) + ' × ' + nights + ' noche' + (nights !== 1 ? 's' : '') + ' × ' + qty + ' hab.';
    document.getElementById('subtotalLabel').textContent = CURRENCY + total.toFixed(2);
    document.getElementById('totalLabel').textContent = CURRENCY + total.toFixed(2);
    if (COINS_PER_NIGHT) {
        document.getElementById('coinsLabel').textContent = coins + ' coins';
    }
}

async function submitBooking() {
    const name  = document.getElementById('guestName').value.trim();
    const email = document.getElementById('guestEmail').value.trim();
    const phone = document.getElementById('guestPhone').value.trim();
    const ci    = document.getElementById('checkin').value;
    const co    = document.getElementById('checkout').value;
    const qty   = parseInt(document.getElementById('roomQty').value) || 1;
    const notes = document.getElementById('specialRequests').value.trim();

    const errEl = document.getElementById('errorMsg');
    errEl.style.display = 'none';

    if (!name || !email || !phone || !ci || !co) {
        errEl.textContent = '⚠️ Por favor completa todos los campos obligatorios.';
        errEl.style.display = 'block';
        errEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

    // Build selected_rooms_json
    const selectedRooms = [{
        id: ROOM_ID,
        room_type: ROOM_TYPE,
        price: PRICE,
        currency: CURRENCY === 'S/' ? 'PEN' : 'USD',
        quantity: qty
    }];

    const formData = new FormData();
    formData.append('hotel_id', HOTEL_ID);
    formData.append('guest_name', name);
    formData.append('guest_email', email);
    formData.append('guest_phone', phone);
    formData.append('check_in', ci);
    formData.append('check_out', co);
    formData.append('special_requests', notes);
    formData.append('selected_rooms_json', JSON.stringify(selectedRooms));

    try {
        const resp = await fetch('/booking_api.php', { method: 'POST', body: formData });
        const data = await resp.json();

        if (data.success) {
            document.getElementById('bookingRef').textContent = data.booking_reference || data.booking_id || '—';
            document.getElementById('bookingForm').style.display = 'none';
            document.getElementById('successScreen').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            errEl.textContent = '❌ ' + (data.message || 'Error al procesar la reserva. Intenta de nuevo.');
            errEl.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '✅ Confirmar Reserva';
            errEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    } catch (e) {
        errEl.textContent = '❌ Error de conexión. Verifica tu internet e intenta de nuevo.';
        errEl.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '✅ Confirmar Reserva';
    }
}

// Init
updateCheckout();
recalc();
</script>
