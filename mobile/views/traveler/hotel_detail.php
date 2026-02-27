<?php
/**
 * Mobile - Hotel Detail Page
 * Shows hotel profile: images, info, rooms grouped by type
 */

$hotel_id = intval($_GET['id'] ?? 0);
if (!$hotel_id) {
    header('Location: ?page=hotels');
    exit();
}

// Fetch hotel from API data (reuse same DB as the API)
$host = 'localhost'; $dbname = 'hotel_booking_system';
$dbuser = 'hoteluser'; $dbpass = 'hotelpass123';

$hotel = null;
$rooms_by_type = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Get hotel
    $stmt = $pdo->prepare("SELECT * FROM hotel_properties WHERE id = ? AND status = 'approved' AND is_active = 1");
    $stmt->execute([$hotel_id]);
    $hotel = $stmt->fetch();

    if (!$hotel) {
        header('Location: ?page=hotels');
        exit();
    }

    // Get rooms grouped by type
    $stmt = $pdo->prepare("
        SELECT r.*, 
               GROUP_CONCAT(rp.photo_path ORDER BY rp.is_primary DESC SEPARATOR '||') as photo_paths
        FROM rooms r
        LEFT JOIN room_photos rp ON r.id = rp.room_id
        WHERE r.hotel_id = ? AND r.is_available = 1
        GROUP BY r.id
        ORDER BY r.room_type, r.price_per_night ASC
    ");
    $stmt->execute([$hotel_id]);
    $raw_rooms = $stmt->fetchAll();

    foreach ($raw_rooms as $room) {
        $type = $room['room_type'];
        if (!isset($rooms_by_type[$type])) {
            $rooms_by_type[$type] = [
                'type'          => $type,
                'count'         => 0,
                'price'         => $room['price_per_night'],
                'currency'      => $room['currency'] ?? 'USD',
                'max_occupancy' => $room['max_occupancy'] ?? 2,
                'capacity'      => $room['capacity'] ?? 1,
                'description'   => $room['description'] ?? '',
                'amenities'     => $room['amenities'] ? array_map('trim', explode(',', $room['amenities'])) : [],
                'first_room_id' => $room['id'],
                'photo'         => $room['photo_paths'] ? explode('||', $room['photo_paths'])[0] : null,
                'extra_bed'     => $room['extra_bed_available'] ?? 0,
                'extra_bed_price' => $room['extra_bed_price'] ?? 0,
            ];
        }
        $rooms_by_type[$type]['count']++;
    }

} catch (Exception $e) {
    $hotel = null;
}

if (!$hotel) {
    header('Location: ?page=hotels');
    exit();
}

// Parse hotel data
$images   = json_decode($hotel['images'] ?? '[]', true) ?: [];
$features = json_decode($hotel['features'] ?? '[]', true) ?: [];
$currency_sym = ($hotel['currency'] ?? 'USD') === 'PEN' ? 'S/' : '$';

// ── Server-side translation for long content ─────────────────────────────────
// Always runs — handles owners who wrote descriptions in English/other languages
$lang = $_SESSION['lang'] ?? 'es';
$langNames = ['es'=>'Spanish','en'=>'English','pt'=>'Portuguese','fr'=>'French',
              'de'=>'German','it'=>'Italian','zh'=>'Chinese','ja'=>'Japanese'];
$targetLanguage = $langNames[$lang] ?? 'Spanish';

// Gather all strings needing translation
$toTranslate = [];
if (!empty($hotel['description']))   $toTranslate[] = $hotel['description'];
foreach ($features as $f)            $toTranslate[] = $f;
foreach ($rooms_by_type as $rt) {
    if (!empty($rt['description']))  $toTranslate[] = $rt['description'];
    foreach ($rt['amenities'] as $a) $toTranslate[] = $a;
}
$toTranslate = array_values(array_unique(array_filter($toTranslate)));

// Check DB cache for each
$translated = [];
$needsAI    = [];
foreach ($toTranslate as $str) {
    try {
        $st = $pdo->prepare("SELECT translated_text FROM translations_cache
            WHERE content_type='ui_mobile' AND content_id=0 AND target_lang=? AND original_text=? LIMIT 1");
        $st->execute([$lang, $str]);
        $row = $st->fetch();
        if ($row) $translated[$str] = $row['translated_text'];
        else       $needsAI[] = $str;
    } catch(Exception $e) { $needsAI[] = $str; }
}

// Batch AI call for cache misses — no source language assumed, AI auto-detects
if (!empty($needsAI)) {
    $BATCH = 8;
    for ($bi = 0; $bi < count($needsAI); $bi += $BATCH) {
        $batch  = array_slice($needsAI, $bi, $BATCH);
        $jlist  = json_encode(array_values($batch), JSON_UNESCAPED_UNICODE);
        $prompt = "Translate the following JSON array of strings to {$targetLanguage}. "
                . "Auto-detect the source language of each string. "
                . "Output ONLY a valid JSON array with exactly the same number of elements in the same order. "
                . "No explanations, no extra text, just the JSON array.\n\n{$jlist}";
        $payload = json_encode(['model'=>'qwen2.5:7b','prompt'=>$prompt,'stream'=>false,
                                'keep_alive'=>'5m','options'=>['temperature'=>0.2,'num_predict'=>4096,'num_ctx'=>4096]]);
        $ch = curl_init('http://72.60.1.16:11434/api/generate');
        curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$payload,
            CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_TIMEOUT=>120, CURLOPT_CONNECTTIMEOUT=>10]);
        $resp = curl_exec($ch); $err = curl_error($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$err && $code === 200) {
            $aiResult = json_decode($resp, true);
            $raw = trim($aiResult['response'] ?? '');
            if (preg_match('/\[.*\]/s', $raw, $m)) {
                $parsed = json_decode($m[0], true);
                if (is_array($parsed) && count($parsed) === count($batch)) {
                    foreach ($batch as $i => $orig) {
                        $tr = trim($parsed[$i] ?? $orig);
                        $translated[$orig] = $tr;
                        // Always cache — even if text looks same (e.g. Spanish→Spanish)
                        // so we skip AI on future visits
                        try {
                            $pdo->prepare("INSERT INTO translations_cache
                                (content_type,content_id,original_text,target_lang,translated_text)
                                VALUES('ui_mobile',0,?,?,?)
                                ON DUPLICATE KEY UPDATE translated_text=VALUES(translated_text),created_at=NOW()"
                            )->execute([$orig, $lang, $tr]);
                        } catch(Exception $e) {}
                    }
                }
            }
        }
    }
}

// Apply translations to data structures
if (!empty($hotel['description'])) {
    $hotel['description'] = $translated[$hotel['description']] ?? $hotel['description'];
}
$features = array_map(fn($f) => $translated[$f] ?? $f, $features);
foreach ($rooms_by_type as $type => &$rt) {
    if (!empty($rt['description'])) {
        $rt['description'] = $translated[$rt['description']] ?? $rt['description'];
    }
    $rt['amenities'] = array_map(fn($a) => $translated[$a] ?? $a, $rt['amenities']);
}
unset($rt);
?>

<!-- Back nav override -->
<div class="aini-detail-topbar d-flex align-items-center px-3 py-2 border-bottom bg-white sticky-top" style="z-index:100;">
    <button class="btn btn-sm btn-light me-2" onclick="history.back()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
        </svg>
    </button>
    <span class="fw-semibold text-truncate" style="font-size:.9rem;"><?= htmlspecialchars($hotel['name']) ?></span>
</div>

<!-- Image Carousel -->
<?php if (!empty($images)): ?>
<div id="hotelCarousel" class="carousel slide" data-bs-ride="carousel" style="height:230px;overflow:hidden;background:#eee;">
    <div class="carousel-inner h-100">
        <?php foreach ($images as $i => $img):
            $src = is_array($img) ? ($img['path'] ?? '') : $img;
        ?>
        <div class="carousel-item h-100 <?= $i === 0 ? 'active' : '' ?>">
            <img src="<?= htmlspecialchars($src) ?>?w=600&q=75"
                 class="d-block w-100 h-100"
                 style="object-fit:cover;"
                 loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
                 onerror="this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&q=75'">
        </div>
        <?php endforeach; ?>
    </div>
    <?php if (count($images) > 1): ?>
    <div class="carousel-indicators" style="bottom:6px;">
        <?php foreach ($images as $i => $img): ?>
        <button type="button" data-bs-target="#hotelCarousel" data-bs-slide-to="<?= $i ?>"
                class="<?= $i === 0 ? 'active' : '' ?>"
                style="width:8px;height:8px;border-radius:50%;"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div style="height:180px;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:4rem;">
    🏨
</div>
<?php endif; ?>

<!-- Hotel Header -->
<div class="px-3 pt-3 pb-2">
    <div class="d-flex justify-content-between align-items-start">
        <div class="flex-grow-1 me-2">
            <h1 class="fw-bold mb-1" style="font-size:1.25rem;"><?= htmlspecialchars($hotel['name']) ?></h1>
            <div class="text-muted small mb-1">
                📍 <?= htmlspecialchars($hotel['location']) ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill" style="background:#f59e0b;color:white;font-size:.75rem;">
                    ⭐ <?= htmlspecialchars($hotel['rating'] ?? '4.5') ?>
                </span>
                <?php if (!empty($hotel['category'])): ?>
                <span class="badge rounded-pill bg-light text-dark border" style="font-size:.72rem;">
                    <?= htmlspecialchars(ucfirst($hotel['category'])) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-end">
            <div class="fw-bold" style="font-size:1.3rem;color:var(--aini-purple);">
                <?= $currency_sym ?><?= number_format($hotel['price_per_night'] ?? $hotel['price'] ?? 0, 0) ?>
            </div>
            <div class="text-muted" style="font-size:.72rem;">/noche</div>
        </div>
    </div>
</div>

<!-- AiNi Coins badge -->
<?php if (!empty($hotel['aini_coins_per_night'])): ?>
<div class="mx-3 mb-3 rounded-3 px-3 py-2 d-flex align-items-center gap-2"
     style="background:linear-gradient(135deg,#667eea20,#764ba220);border:1px solid #667eea40;">
    <span style="font-size:1.1rem;">🪙</span>
    <span style="font-size:.82rem;">Gana <strong><?= $hotel['aini_coins_per_night'] ?> AiNi Coins</strong> por noche</span>
</div>
<?php endif; ?>

<!-- Description -->
<?php if (!empty($hotel['description'])): ?>
<div class="px-3 mb-3">
    <p class="text-muted" style="font-size:.85rem;line-height:1.5;"><?= htmlspecialchars($hotel['description']) ?></p>
</div>
<?php endif; ?>

<!-- Amenities / Features -->
<?php if (!empty($features)): ?>
<div class="px-3 mb-3">
    <div class="fw-semibold mb-2" style="font-size:.88rem;">✨ Servicios</div>
    <div class="d-flex flex-wrap gap-1">
        <?php foreach ($features as $f): ?>
        <span class="badge bg-light text-dark border" style="font-size:.75rem;font-weight:500;">
            ✓ <?= htmlspecialchars($f) ?>
        </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<hr class="mx-3 my-0">

<!-- Rooms Section -->
<div class="px-3 pt-3 pb-2">
    <div class="fw-bold mb-3" style="font-size:1rem;">🛏️ Habitaciones Disponibles</div>

    <?php if (empty($rooms_by_type)): ?>
    <div class="text-center py-4 text-muted">
        <div style="font-size:2.5rem;">😔</div>
        <div class="mt-2 small">No hay habitaciones disponibles en este momento</div>
    </div>
    <?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($rooms_by_type as $rt): ?>
        <?php $sym = ($rt['currency'] === 'PEN') ? 'S/' : '$'; ?>
        <div class="card border-0 shadow-sm rounded-3" style="overflow:hidden;">
            <?php if ($rt['photo']): ?>
            <img src="<?= htmlspecialchars($rt['photo']) ?>?w=400&q=70"
                 alt="<?= htmlspecialchars($rt['type']) ?>"
                 style="width:100%;height:120px;object-fit:cover;"
                 loading="lazy"
                 onerror="this.style.display='none'">
            <?php endif; ?>
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="fw-bold" style="font-size:.95rem;"><?= htmlspecialchars($rt['type']) ?></div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.72rem;">
                        ✓ <?= $rt['count'] ?> disp.
                    </span>
                </div>

                <!-- Capacity -->
                <div class="text-muted small mb-2">
                    👥 Hasta <?= $rt['max_occupancy'] ?> personas
                    <?php if ($rt['extra_bed']): ?>
                     · Cama extra disponible
                    <?php endif; ?>
                </div>

                <?php if (!empty($rt['description'])): ?>
                <p class="text-muted mb-2" style="font-size:.8rem;"><?= htmlspecialchars($rt['description']) ?></p>
                <?php endif; ?>

                <?php if (!empty($rt['amenities'])): ?>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <?php foreach (array_slice($rt['amenities'], 0, 4) as $a): ?>
                    <span class="badge bg-light text-dark border" style="font-size:.7rem;"><?= htmlspecialchars($a) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div>
                        <span class="fw-bold" style="font-size:1.15rem;color:var(--aini-purple);">
                            <?= $sym ?><?= number_format($rt['price'], 0) ?>
                        </span>
                        <span class="text-muted" style="font-size:.75rem;"> /noche</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <select id="qty_<?= $rt['first_room_id'] ?>" class="form-select form-select-sm"
                                style="width:65px;border-radius:10px;font-size:.82rem;">
                            <?php for ($q = 1; $q <= min($rt['count'], 5); $q++): ?>
                            <option value="<?= $q ?>"><?= $q ?></option>
                            <?php endfor; ?>
                        </select>
                        <button class="btn aini-btn-primary btn-sm px-3"
                                onclick="bookRoom(<?= $hotel_id ?>, '<?= htmlspecialchars(addslashes($rt['type'])) ?>', <?= $rt['price'] ?>, '<?= $rt['currency'] ?>', <?= $rt['first_room_id'] ?>)">
                            📅 Reservar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>



<script>
function bookRoom(hotelId, roomType, price, currency, roomId) {
    <?php if (!$isLoggedIn): ?>
    // Not logged in — redirect to login, then back
    window.location.href = '?page=login&redirect=' + encodeURIComponent('hotel&id=<?= $hotel_id ?>');
    return;
    <?php endif; ?>

    // Read qty selector for this room
    const qtyEl = document.getElementById('qty_' + roomId);
    const qty = qtyEl ? parseInt(qtyEl.value) : 1;

    // Pass params to booking confirm page
    const params = new URLSearchParams({
        page: 'booking_confirm',
        hotel_id: hotelId,
        room_type: roomType,
        price: price,
        currency: currency,
        room_id: roomId,
        qty: qty
    });
    window.location.href = '?' + params.toString();
}
</script>
