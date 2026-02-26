<?php
/**
 * Traveler - Profile
 * Tables: ainitravel_users
 */
$host = 'localhost'; $dbname = 'hotel_booking_system';
$dbuser = 'hoteluser'; $dbpass = 'hotelpass123';
$uid = $_SESSION['user_id'];

// Handle profile update (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    header('Content-Type: application/json');
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $name     = trim($_POST['name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $bio      = trim($_POST['bio'] ?? '');
        $country  = trim($_POST['country'] ?? '');
        $lang     = trim($_POST['preferred_language'] ?? 'es');
        $currency = trim($_POST['currency_preference'] ?? 'USD');
        if (empty($name)) { echo json_encode(['success'=>false,'message'=>'El nombre es requerido']); exit(); }
        $pdo->prepare("UPDATE ainitravel_users SET name=?,phone=?,bio=?,country=?,preferred_language=?,currency_preference=? WHERE id=?")
            ->execute([$name,$phone,$bio,$country,$lang,$currency,$uid]);
        $_SESSION['user_name'] = $name;
        echo json_encode(['success'=>true,'message'=>'Perfil actualizado']);
    } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>'Error al actualizar']); }
    exit();
}

// Load user
$user = [];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT * FROM ainitravel_users WHERE id=?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $user = []; }

$u = $user;
$name    = $u['name'] ?? $_SESSION['user_name'] ?? '';
$email   = $u['email'] ?? $_SESSION['user_email'] ?? '';
$phone   = $u['phone'] ?? '';
$bio     = $u['bio'] ?? '';
$country = $u['country'] ?? '';
$lang    = $u['preferred_language'] ?? 'es';
$currency = $u['currency_preference'] ?? 'USD';
$photo   = $u['profile_photo'] ?? '';
$coins   = $u['aini_coins'] ?? 0;
$tier    = $u['member_tier'] ?? 'standard';
$bookings_count = $u['total_bookings'] ?? 0;
$verified = $u['email_verified'] ?? 0;

$tierLabels = ['standard'=>'⚪ Estándar','silver'=>'🥈 Plata','gold'=>'🥇 Oro','platinum'=>'💎 Platino'];
$tierLabel = $tierLabels[$tier] ?? '⚪ Estándar';

// Avatar initials
$initials = strtoupper(substr($name, 0, 1) ?: 'U') . (strpos($name,' ')!==false ? strtoupper(substr(strstr($name,' '),1,1)) : '');
?>

<!-- PROFILE -->
<div class="container-fluid px-3 py-3">

    <!-- Profile hero -->
    <div class="text-center mb-4">
        <!-- Avatar -->
        <div class="position-relative d-inline-block mb-2">
            <?php if ($photo): ?>
            <img src="<?= htmlspecialchars($photo) ?>" alt="Foto"
                 id="profileAvatarImg"
                 style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--aini-purple)">
            <?php else: ?>
            <div id="profileAvatarInitials" style="width:88px;height:88px;border-radius:50%;background:var(--aini-gradient);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700;color:white;border:3px solid var(--aini-purple)">
                <?= htmlspecialchars($initials) ?>
            </div>
            <?php endif; ?>
            <button class="btn btn-sm position-absolute" onclick="document.getElementById('photoInput').click()"
                    style="bottom:0;right:0;width:28px;height:28px;border-radius:50%;background:var(--aini-gradient);border:none;padding:0;display:flex;align-items:center;justify-content:center">
                <span style="font-size:.8rem;color:white">📷</span>
            </button>
            <input type="file" id="photoInput" accept="image/*" class="d-none" onchange="uploadPhoto(this)">
        </div>
        <div class="fw-bold" style="font-size:1.1rem"><?= htmlspecialchars($name) ?></div>
        <div class="text-muted small"><?= htmlspecialchars($email) ?></div>
        <div class="d-flex justify-content-center gap-2 mt-2">
            <span class="badge rounded-pill" style="background:#f3f4f6;color:#555;font-size:.72rem"><?= $tierLabel ?></span>
            <?php if ($verified): ?>
            <span class="badge rounded-pill bg-success" style="font-size:.72rem">✅ Verificado</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick stats -->
    <div class="row g-2 mb-4">
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 text-center py-2">
                <div class="fw-bold" style="color:var(--aini-purple)"><?= number_format($coins) ?></div>
                <div class="text-muted" style="font-size:.7rem">🪙 Coins</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 text-center py-2">
                <div class="fw-bold text-success"><?= $bookings_count ?></div>
                <div class="text-muted" style="font-size:.7rem">🏨 Reservas</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 text-center py-2">
                <div class="fw-bold" style="color:#f59e0b"><?= number_format($u['xp_points'] ?? 0) ?></div>
                <div class="text-muted" style="font-size:.7rem">⚡ XP</div>
            </div>
        </div>
    </div>

    <!-- Edit form -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3">
        <div class="fw-semibold mb-3" style="font-size:.9rem">✏️ Editar perfil</div>

        <div id="profileMsg" class="alert d-none py-2 mb-2" style="font-size:.82rem"></div>

        <div class="mb-2">
            <label class="form-label mb-1" style="font-size:.78rem;color:#6b7280">Nombre completo *</label>
            <input type="text" id="pName" class="form-control rounded-3" value="<?= htmlspecialchars($name) ?>" style="font-size:.88rem">
        </div>
        <div class="mb-2">
            <label class="form-label mb-1" style="font-size:.78rem;color:#6b7280">Teléfono</label>
            <input type="tel" id="pPhone" class="form-control rounded-3" value="<?= htmlspecialchars($phone) ?>" placeholder="+1 555 0000" style="font-size:.88rem">
        </div>
        <div class="mb-2">
            <label class="form-label mb-1" style="font-size:.78rem;color:#6b7280">País</label>
            <input type="text" id="pCountry" class="form-control rounded-3" value="<?= htmlspecialchars($country) ?>" placeholder="México, Argentina…" style="font-size:.88rem">
        </div>
        <div class="mb-2">
            <label class="form-label mb-1" style="font-size:.78rem;color:#6b7280">Bio</label>
            <textarea id="pBio" class="form-control rounded-3" rows="2" placeholder="Cuéntanos sobre ti…" style="font-size:.88rem"><?= htmlspecialchars($bio) ?></textarea>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label mb-1" style="font-size:.78rem;color:#6b7280">Idioma</label>
                <select id="pLang" class="form-select rounded-3" style="font-size:.85rem">
                    <option value="es" <?= $lang==='es'?'selected':'' ?>>🇪🇸 Español</option>
                    <option value="en" <?= $lang==='en'?'selected':'' ?>>🇬🇧 English</option>
                    <option value="pt" <?= $lang==='pt'?'selected':'' ?>>🇧🇷 Português</option>
                    <option value="fr" <?= $lang==='fr'?'selected':'' ?>>🇫🇷 Français</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label mb-1" style="font-size:.78rem;color:#6b7280">Moneda</label>
                <select id="pCurrency" class="form-select rounded-3" style="font-size:.85rem">
                    <option value="USD" <?= $currency==='USD'?'selected':'' ?>>$ USD</option>
                    <option value="EUR" <?= $currency==='EUR'?'selected':'' ?>>€ EUR</option>
                    <option value="MXN" <?= $currency==='MXN'?'selected':'' ?>>$ MXN</option>
                    <option value="ARS" <?= $currency==='ARS'?'selected':'' ?>>$ ARS</option>
                    <option value="COP" <?= $currency==='COP'?'selected':'' ?>>$ COP</option>
                    <option value="BRL" <?= $currency==='BRL'?'selected':'' ?>>R$ BRL</option>
                </select>
            </div>
        </div>

        <button class="btn w-100 rounded-3 fw-semibold" id="saveProfileBtn" onclick="saveProfile()"
                style="background:var(--aini-gradient);color:white">
            Guardar cambios
        </button>
    </div>

    <!-- Logout & links -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
        <a href="?page=bookings" class="d-flex align-items-center px-3 py-3 border-bottom text-decoration-none text-dark">
            <span class="me-3">🗓️</span><span style="font-size:.88rem">Mis Reservas</span>
            <span class="ms-auto text-muted">›</span>
        </a>
        <a href="?page=wallet" class="d-flex align-items-center px-3 py-3 border-bottom text-decoration-none text-dark">
            <span class="me-3">🪙</span><span style="font-size:.88rem">Wallet</span>
            <span class="ms-auto text-muted">›</span>
        </a>
        <a href="?page=messages" class="d-flex align-items-center px-3 py-3 border-bottom text-decoration-none text-dark">
            <span class="me-3">💬</span><span style="font-size:.88rem">Mensajes</span>
            <span class="ms-auto text-muted">›</span>
        </a>
        <a href="/ainitravel_logout.php" class="d-flex align-items-center px-3 py-3 text-decoration-none text-danger">
            <span class="me-3">🚪</span><span style="font-size:.88rem">Cerrar sesión</span>
            <span class="ms-auto">›</span>
        </a>
    </div>

</div>

<script>
function saveProfile() {
    const btn = document.getElementById('saveProfileBtn');
    const msg = document.getElementById('profileMsg');
    btn.disabled = true; btn.textContent = 'Guardando…';
    msg.className = 'alert d-none py-2 mb-2'; msg.textContent = '';

    const fd = new FormData();
    fd.append('action','update_profile');
    fd.append('name', document.getElementById('pName').value);
    fd.append('phone', document.getElementById('pPhone').value);
    fd.append('bio', document.getElementById('pBio').value);
    fd.append('country', document.getElementById('pCountry').value);
    fd.append('preferred_language', document.getElementById('pLang').value);
    fd.append('currency_preference', document.getElementById('pCurrency').value);

    fetch('?page=profile', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            msg.className = 'alert py-2 mb-2 alert-' + (d.success ? 'success' : 'danger');
            msg.classList.remove('d-none');
            msg.textContent = d.message;
            if (d.success) setTimeout(() => msg.classList.add('d-none'), 3000);
        })
        .catch(() => {
            msg.className = 'alert py-2 mb-2 alert-danger';
            msg.classList.remove('d-none');
            msg.textContent = 'Error de conexión';
        })
        .finally(() => { btn.disabled = false; btn.textContent = 'Guardar cambios'; });
}

function uploadPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const fd = new FormData();
    fd.append('photo', file);
    // Get phone from field
    const phone = document.getElementById('pPhone')?.value || '';
    fd.append('phone', phone);

    // Show progress
    const btn = input.previousElementSibling;
    btn.innerHTML = '<span style="font-size:.6rem;color:white">⏳</span>';

    fetch('/profile_api.php?action=upload_photo', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            btn.innerHTML = '<span style="font-size:.8rem;color:white">📷</span>';
            if (d.success && d.photo_url) {
                // Replace avatar with actual image
                const wrap = btn.closest('.position-relative');
                const existing = wrap.querySelector('img') || wrap.querySelector('div[id]');
                if (existing) {
                    existing.outerHTML = `<img src="${d.photo_url}" alt="Foto" style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--aini-purple)">`;
                }
            }
        })
        .catch(() => {
            btn.innerHTML = '<span style="font-size:.8rem;color:white">📷</span>';
        });
}
</script>
