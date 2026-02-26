<?php
/**
 * Partner - Profile Settings
 * Table: aini_partner_businesses, hotel_properties
 */
$host='localhost'; $dbname='hotel_booking_system';
$dbuser='hoteluser'; $dbpass='hotelpass123';
$pid = $_SESSION['partner_id'];

// Handle update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='update_partner') {
    header('Content-Type: application/json');
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$dbuser,$dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $contactPerson = trim($_POST['contact_person']??'');
        $phone         = trim($_POST['phone']??'');
        $website       = trim($_POST['website_url']??'');

        if (empty($contactPerson)) { echo json_encode(['success'=>false,'message'=>'El nombre de contacto es requerido']); exit(); }
        $pdo->prepare("UPDATE aini_partner_businesses SET contact_person=?,phone=?,website_url=? WHERE id=?")
            ->execute([$contactPerson,$phone,$website,$pid]);
        $_SESSION['partner_name'] = $contactPerson;
        echo json_encode(['success'=>true,'message'=>'Perfil actualizado']);
    } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>'Error al guardar']); }
    exit();
}

$partner = []; $hotels = [];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$dbuser,$dbpass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $stmt = $pdo->prepare("SELECT * FROM aini_partner_businesses WHERE id=?");
    $stmt->execute([$pid]); $partner = $stmt->fetch() ?: [];

    $pEmail = $partner['email'] ?? '';
    $stmt = $pdo->prepare("SELECT id,name,location,status,star_category,images,emoji,property_type FROM hotel_properties WHERE partner_business_id=? OR email=? ORDER BY name");
    $stmt->execute([$pid,$pEmail]); $hotels = $stmt->fetchAll();
} catch (Exception $e) {}

$p = $partner;
$businessName  = $p['business_name'] ?? $_SESSION['partner_name'] ?? 'Partner';
$businessType  = $p['business_type'] ?? 'hotel';
$email         = $p['email'] ?? '';
$contactPerson = $p['contact_person'] ?? '';
$phone         = $p['phone'] ?? '';
$website       = $p['website_url'] ?? '';
$commission    = $p['commission_percentage'] ?? 12.00;
$status        = $p['status'] ?? 'pending';

$statusLabel = match($status) { 'approved'=>'✅ Aprobado','pending'=>'⏳ Pendiente revisión','rejected'=>'❌ Rechazado','suspended'=>'⚠️ Suspendido',default=>$status };
$statusCls   = match($status) { 'approved'=>'bg-success','pending'=>'bg-warning text-dark','rejected'=>'bg-danger','suspended'=>'bg-warning text-dark',default=>'bg-secondary' };

$initials = strtoupper(substr($businessName,0,2));
?>

<!-- PARTNER PROFILE -->
<div class="container-fluid px-3 py-3">

    <!-- Hero -->
    <div class="text-center mb-4">
        <div style="width:80px;height:80px;border-radius:50%;background:var(--aini-gradient);display:inline-flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700;color:white;border:3px solid var(--aini-purple);margin-bottom:10px">
            <?= htmlspecialchars($initials) ?>
        </div>
        <div class="fw-bold" style="font-size:1.1rem"><?= htmlspecialchars($businessName) ?></div>
        <div class="text-muted small"><?= htmlspecialchars($email) ?></div>
        <div class="mt-2 d-flex justify-content-center gap-2">
            <span class="badge <?= $statusCls ?>" style="font-size:.72rem"><?= $statusLabel ?></span>
            <span class="badge bg-light text-dark" style="font-size:.72rem">🏷️ <?= number_format((float)$commission,0) ?>% comisión</span>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-2 mb-4">
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 text-center py-2">
                <div class="fw-bold" style="color:var(--aini-purple)"><?= count($hotels) ?></div>
                <div class="text-muted" style="font-size:.7rem">🏨 Hoteles</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 text-center py-2">
                <div class="fw-bold text-success"><?= count(array_filter($hotels, fn($h)=>$h['status']==='approved')) ?></div>
                <div class="text-muted" style="font-size:.7rem">✅ Activos</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-3 text-center py-2">
                <div class="fw-bold" style="color:#f59e0b"><?= count(array_filter($hotels, fn($h)=>$h['status']==='pending')) ?></div>
                <div class="text-muted" style="font-size:.7rem">⏳ Revisión</div>
            </div>
        </div>
    </div>

    <!-- Edit form -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3">
        <div class="fw-semibold mb-3" style="font-size:.9rem">✏️ Información de contacto</div>

        <div id="partnerProfileMsg" class="alert d-none py-2 mb-2" style="font-size:.82rem"></div>

        <div class="mb-2">
            <label class="form-label mb-1" style="font-size:.78rem;color:#6b7280">Nombre de contacto *</label>
            <input type="text" id="ppContact" class="form-control rounded-3" value="<?= htmlspecialchars($contactPerson) ?>" style="font-size:.88rem">
        </div>
        <div class="mb-2">
            <label class="form-label mb-1" style="font-size:.78rem;color:#6b7280">Teléfono</label>
            <input type="tel" id="ppPhone" class="form-control rounded-3" value="<?= htmlspecialchars($phone) ?>" placeholder="+1 555 0000" style="font-size:.88rem">
        </div>
        <div class="mb-3">
            <label class="form-label mb-1" style="font-size:.78rem;color:#6b7280">Sitio web</label>
            <input type="url" id="ppWebsite" class="form-control rounded-3" value="<?= htmlspecialchars($website) ?>" placeholder="https://tuhotel.com" style="font-size:.88rem">
        </div>
        <button class="btn w-100 rounded-3 fw-semibold" id="savePartnerBtn" onclick="savePartnerProfile()"
                style="background:var(--aini-gradient);color:white">
            Guardar cambios
        </button>
    </div>

    <!-- My properties -->
    <?php if (!empty($hotels)): ?>
    <div class="fw-semibold mb-2" style="font-size:.9rem">🏨 Mis propiedades</div>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
        <?php foreach ($hotels as $i => $h):
            $img = '';
            if (!empty($h['images'])) { $imgs = json_decode($h['images'],true); if(is_array($imgs)&&count($imgs)>0) $img=$imgs[0]; }
            $emoji = $h['emoji']??'🏨';
            $hStatus = $h['status']??'pending';
            $hBadge = match($hStatus){ 'approved'=>'<span class="badge bg-success" style="font-size:.62rem">Activo</span>','pending'=>'<span class="badge bg-warning text-dark" style="font-size:.62rem">Revisión</span>','rejected'=>'<span class="badge bg-danger" style="font-size:.62rem">Rechazado</span>',default=>"<span class='badge bg-secondary' style='font-size:.62rem'>{$hStatus}</span>" };
        ?>
        <div class="d-flex align-items-center px-3 py-2 <?= $i<count($hotels)-1?'border-bottom':'' ?>">
            <div style="width:38px;height:38px;border-radius:10px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;margin-right:10px">
                <?= $emoji ?>
            </div>
            <div style="flex:1;min-width:0">
                <div class="fw-semibold text-truncate" style="font-size:.83rem"><?= htmlspecialchars($h['name']) ?></div>
                <div class="text-muted text-truncate" style="font-size:.72rem"><?= htmlspecialchars($h['location']??'') ?> · <?= ucfirst($h['property_type']??'hotel') ?></div>
            </div>
            <div class="ms-2 d-flex flex-column align-items-end gap-1">
                <?= $hBadge ?>
                <?php if ($h['star_category']): ?>
                <span style="font-size:.65rem"><?= str_repeat('⭐',(int)$h['star_category']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Navigation links -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
        <a href="?page=partner_dashboard" class="d-flex align-items-center px-3 py-3 border-bottom text-decoration-none text-dark">
            <span class="me-3">📊</span><span style="font-size:.88rem">Dashboard</span><span class="ms-auto text-muted">›</span>
        </a>
        <a href="?page=partner_bookings" class="d-flex align-items-center px-3 py-3 border-bottom text-decoration-none text-dark">
            <span class="me-3">🗓️</span><span style="font-size:.88rem">Reservas</span><span class="ms-auto text-muted">›</span>
        </a>
        <a href="?page=partner_transactions" class="d-flex align-items-center px-3 py-3 border-bottom text-decoration-none text-dark">
            <span class="me-3">💳</span><span style="font-size:.88rem">Ingresos</span><span class="ms-auto text-muted">›</span>
        </a>
        <a href="/partners/logout.php" class="d-flex align-items-center px-3 py-3 text-decoration-none text-danger">
            <span class="me-3">🚪</span><span style="font-size:.88rem">Cerrar sesión</span><span class="ms-auto">›</span>
        </a>
    </div>

</div>

<script>
function savePartnerProfile() {
    const btn = document.getElementById('savePartnerBtn');
    const msg = document.getElementById('partnerProfileMsg');
    btn.disabled = true; btn.textContent = 'Guardando…';
    msg.className = 'alert d-none py-2 mb-2';

    const fd = new FormData();
    fd.append('action','update_partner');
    fd.append('contact_person', document.getElementById('ppContact').value);
    fd.append('phone', document.getElementById('ppPhone').value);
    fd.append('website_url', document.getElementById('ppWebsite').value);

    fetch('?page=partner_profile',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(d=>{
            msg.className='alert py-2 mb-2 alert-'+(d.success?'success':'danger');
            msg.classList.remove('d-none');
            msg.textContent = d.message;
            if(d.success) setTimeout(()=>msg.classList.add('d-none'),3000);
        })
        .catch(()=>{
            msg.className='alert py-2 mb-2 alert-danger';
            msg.classList.remove('d-none');
            msg.textContent='Error de conexión';
        })
        .finally(()=>{ btn.disabled=false; btn.textContent='Guardar cambios'; });
}
</script>
