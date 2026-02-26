<?php
/**
 * Partner - Rooms Management
 * View rooms across all partner hotels, update status/availability
 */
$host='localhost'; $dbname='hotel_booking_system';
$dbuser='hoteluser'; $dbpass='hotelpass123';
$pid = $_SESSION['partner_id'];

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='toggle_available') {
    header('Content-Type: application/json');
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$dbuser,$dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $room_id = intval($_POST['room_id']??0);
        $is_available = intval($_POST['is_available']??0);
        // Verify room belongs to partner
        $stmt = $pdo->prepare("SELECT r.id FROM rooms r JOIN hotel_properties h ON r.hotel_id=h.id WHERE r.id=? AND (h.partner_business_id=? OR h.email=(SELECT email FROM aini_partner_businesses WHERE id=?))");
        $stmt->execute([$room_id,$pid,$pid]);
        if (!$stmt->fetch()) { echo json_encode(['success'=>false,'message'=>'Habitación no encontrada']); exit(); }
        $pdo->prepare("UPDATE rooms SET is_available=? WHERE id=?")->execute([$is_available,$room_id]);
        echo json_encode(['success'=>true]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>'Error']); }
    exit();
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='update_room_status') {
    header('Content-Type: application/json');
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$dbuser,$dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $room_id = intval($_POST['room_id']??0);
        $room_status = trim($_POST['room_status']??'clean');
        if (!in_array($room_status,['clean','dirty','maintenance','out_of_order'])) { echo json_encode(['success'=>false]); exit(); }
        $stmt = $pdo->prepare("SELECT r.id FROM rooms r JOIN hotel_properties h ON r.hotel_id=h.id WHERE r.id=? AND (h.partner_business_id=? OR h.email=(SELECT email FROM aini_partner_businesses WHERE id=?))");
        $stmt->execute([$room_id,$pid,$pid]);
        if (!$stmt->fetch()) { echo json_encode(['success'=>false]); exit(); }
        $pdo->prepare("UPDATE rooms SET room_status=? WHERE id=?")->execute([$room_status,$room_id]);
        echo json_encode(['success'=>true]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>'Error']); }
    exit();
}

$hotels = []; $roomsByHotel = []; $filterHotel = intval($_GET['hotel_id']??0);
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$dbuser,$dbpass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pEmail = $pdo->prepare("SELECT email FROM aini_partner_businesses WHERE id=?");
    $pEmail->execute([$pid]); $pe = $pEmail->fetchColumn()?:'';

    $stmt = $pdo->prepare("SELECT id,name,emoji FROM hotel_properties WHERE partner_business_id=? OR email=? ORDER BY name");
    $stmt->execute([$pid,$pe]); $hotels = $stmt->fetchAll();
    $hotelIds = array_column($hotels,'id');

    if (!empty($hotelIds)) {
        $inPh = implode(',',array_fill(0,count($hotelIds),'?'));
        $params = $hotelIds;
        $hWhere = "hotel_id IN ($inPh)";
        if ($filterHotel>0) { $hWhere .= " AND hotel_id=?"; $params[]=$filterHotel; }

        $stmt = $pdo->prepare("SELECT r.*, h.name as hotel_name, h.emoji as hotel_emoji FROM rooms r JOIN hotel_properties h ON r.hotel_id=h.id WHERE $hWhere ORDER BY h.name, r.room_number");
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $room) {
            $roomsByHotel[$room['hotel_id']][] = $room;
        }
    }
} catch (Exception $e) {}

$statusColors = ['clean'=>'#16a34a','dirty'=>'#f59e0b','maintenance'=>'#3b82f6','out_of_order'=>'#dc2626'];
$statusLabels = ['clean'=>'Limpia','dirty'=>'Sucia','maintenance'=>'Mantenimiento','out_of_order'=>'Fuera de servicio'];
?>

<!-- PARTNER ROOMS -->
<div class="container-fluid px-3 py-3">

    <!-- Hotel filter -->
    <?php if (count($hotels) > 1): ?>
    <div class="mb-3">
        <select class="form-select rounded-3" style="font-size:.83rem"
            onchange="window.location='?page=partner_rooms&hotel_id='+this.value">
            <option value="0">Todas las propiedades</option>
            <?php foreach ($hotels as $h): ?>
            <option value="<?= $h['id'] ?>" <?= $filterHotel===$h['id']?'selected':'' ?>><?= htmlspecialchars($h['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <!-- Legend -->
    <div class="d-flex gap-2 flex-wrap mb-3">
        <?php foreach ($statusColors as $k=>$c): ?>
        <span class="badge rounded-pill" style="background:<?= $c ?>;font-size:.68rem"><?= $statusLabels[$k] ?></span>
        <?php endforeach; ?>
    </div>

    <?php if (empty($roomsByHotel)): ?>
    <div class="text-center py-5 text-muted">
        <div style="font-size:3rem">🛏️</div>
        <div class="mt-2">No hay habitaciones registradas</div>
    </div>
    <?php else: foreach ($roomsByHotel as $hotelId => $rooms):
        // Find hotel name
        $hotelName = '';
        $hotelEmoji = '🏨';
        foreach ($hotels as $h) { if ($h['id']==$hotelId) { $hotelName=$h['name']; $hotelEmoji=$h['emoji']??'🏨'; break; } }
    ?>
    <div class="fw-semibold mb-2 mt-2" style="font-size:.9rem"><?= $hotelEmoji ?> <?= htmlspecialchars($hotelName) ?> <span class="text-muted fw-normal">(<?= count($rooms) ?> hab.)</span></div>

    <?php foreach ($rooms as $r):
        $rstatus = $r['room_status'] ?? 'clean';
        $statusColor = $statusColors[$rstatus] ?? '#9ca3af';
        $isAvailable = (bool)($r['is_available'] ?? 1);
    ?>
    <div class="card border-0 shadow-sm rounded-3 mb-2 overflow-hidden" id="room_<?= $r['id'] ?>">
        <div style="height:4px;background:<?= $statusColor ?>"></div>
        <div class="p-3">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <span class="fw-semibold" style="font-size:.92rem">🛏️ <?= htmlspecialchars($r['room_number']) ?></span>
                    <span class="ms-2 badge" style="background:<?= $statusColor ?>;font-size:.65rem"><?= $statusLabels[$rstatus] ?? $rstatus ?></span>
                </div>
                <!-- Available toggle -->
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                        id="avail_<?= $r['id'] ?>" <?= $isAvailable?'checked':'' ?>
                        onchange="toggleAvailable(<?= $r['id'] ?>, this.checked)"
                        style="width:2.3em;height:1.2em">
                    <label class="form-check-label" for="avail_<?= $r['id'] ?>" style="font-size:.72rem;color:#6b7280">Disponible</label>
                </div>
            </div>

            <div class="row g-1 mb-2" style="font-size:.77rem">
                <div class="col-6"><span class="text-muted">Tipo:</span> <strong><?= htmlspecialchars($r['room_type']) ?></strong></div>
                <div class="col-6"><span class="text-muted">Cap.:</span> <strong><?= $r['capacity'] ?? $r['max_occupancy'] ?> pers.</strong></div>
                <div class="col-6"><span class="text-muted">Precio:</span> <strong>$<?= number_format($r['price_per_night'] ?? $r['price'] ?? 0, 2) ?>/noche</strong></div>
                <div class="col-6"><span class="text-muted">Moneda:</span> <strong><?= htmlspecialchars($r['currency']??'USD') ?></strong></div>
            </div>

            <?php if (!empty($r['amenities'])): ?>
            <div class="text-muted text-truncate mb-2" style="font-size:.72rem">✨ <?= htmlspecialchars($r['amenities']) ?></div>
            <?php endif; ?>

            <!-- Status selector -->
            <select class="form-select form-select-sm rounded-3" style="font-size:.78rem"
                onchange="updateRoomStatus(<?= $r['id'] ?>, this.value, this)">
                <?php foreach ($statusLabels as $k=>$label): ?>
                <option value="<?= $k ?>" <?= $rstatus===$k?'selected':'' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endforeach; endforeach; endif; ?>

</div>

<script>
function toggleAvailable(roomId, isAvail) {
    const fd = new FormData();
    fd.append('action','toggle_available');
    fd.append('room_id', roomId);
    fd.append('is_available', isAvail ? 1 : 0);
    fetch('?page=partner_rooms',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(d=>{ if(!d.success) document.getElementById('avail_'+roomId).checked = !isAvail; })
        .catch(()=>{ document.getElementById('avail_'+roomId).checked = !isAvail; });
}

function updateRoomStatus(roomId, status, sel) {
    const origVal = sel.dataset.orig || sel.value;
    sel.disabled = true;
    const fd = new FormData();
    fd.append('action','update_room_status');
    fd.append('room_id', roomId);
    fd.append('room_status', status);
    const colors = {clean:'#16a34a',dirty:'#f59e0b',maintenance:'#3b82f6',out_of_order:'#dc2626'};
    fetch('?page=partner_rooms',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(d=>{
            sel.disabled=false;
            if (d.success) {
                const card = document.getElementById('room_'+roomId);
                if(card) {
                    card.querySelector('div[style*="height:4px"]').style.background = colors[status]||'#9ca3af';
                    const badge = card.querySelector('.badge');
                    if(badge) { badge.style.background=colors[status]||'#9ca3af'; badge.textContent={clean:'Limpia',dirty:'Sucia',maintenance:'Mantenimiento',out_of_order:'Fuera de servicio'}[status]||status; }
                }
            } else { sel.value = origVal; }
        })
        .catch(()=>{ sel.disabled=false; sel.value=origVal; });
}
</script>
