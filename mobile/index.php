<?php
/**
 * AiNi Travel - Mobile MVC Router
 * Handles all mobile views. UA detection in public_booking.php redirects here.
 * URL: ainitravel.com/mobile/?page=hotels
 */
session_start();

// ── AJAX API intercept (must be before ANY html output) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['page'] ?? '') === 'api') {
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json');

    $uid  = $_SESSION['user_id']   ?? 0;
    $pid  = $_SESSION['partner_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $booking_id = intval($_POST['booking_id'] ?? 0);

    try {
        $pdo = new PDO('mysql:host=localhost;dbname=hotel_booking_system;charset=utf8mb4',
            'hoteluser', 'hotelpass123',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'DB error']); exit();
    }

    // Traveler: request cancellation
    if ($action === 'request_cancel') {
        // Accept session OR page-embedded token (daily HMAC)
        $secret = 'aini2024_mobile_secret';
        $tok_uid = intval($_POST['uid'] ?? 0);
        $tok_val = $_POST['tok'] ?? '';
        $expected = sha1($tok_uid . date('Y-m-d') . $secret);
        if ($uid) {
            // session OK, use it
        } elseif ($tok_uid && hash_equals($expected, $tok_val)) {
            $uid = $tok_uid; // token valid
        } else {
            echo json_encode(['success'=>false,'message'=>'No autenticado (sesion expirada, recarga la pagina)']); exit();
        }
        if (!$booking_id) { echo json_encode(['success'=>false,'message'=>'Reserva inválida']); exit(); }
        $stmt = $pdo->prepare('SELECT * FROM guest_bookings WHERE id=? AND user_id=?');
        $stmt->execute([$booking_id, $uid]);
        $b = $stmt->fetch();
        if (!$b) { echo json_encode(['success'=>false,'message'=>'Reserva no encontrada']); exit(); }
        if (in_array($b['status'], ['cancelled','completed'])) { echo json_encode(['success'=>false,'message'=>'No se puede cancelar']); exit(); }
        if ($b['status'] === 'cancellation_requested') { echo json_encode(['success'=>false,'message'=>'Solicitud ya enviada']); exit(); }
        $pdo->prepare('UPDATE guest_bookings SET status=\'cancellation_requested\' WHERE id=? AND user_id=?')->execute([$booking_id, $uid]);
        echo json_encode(['success'=>true,'message'=>'Solicitud enviada al hotel']); exit();
    }

    // Partner: update booking status
    if ($action === 'update_status') {
        // Accept session OR page-embedded token (daily HMAC)
        $secret = 'aini2024_mobile_secret';
        $tok_pid = intval($_POST['pid'] ?? 0);
        $tok_val = $_POST['ptok'] ?? '';
        $expected = sha1($tok_pid . date('Y-m-d') . $secret . '_partner');
        if ($pid) {
            // session OK
        } elseif ($tok_pid && hash_equals($expected, $tok_val)) {
            $pid = $tok_pid;
        } else {
            echo json_encode(['success'=>false,'message'=>'No autenticado (recarga la pagina)']); exit();
        }
        $new_status = $_POST['status'] ?? '';
        if (!$booking_id || !in_array($new_status, ['confirmed','cancelled','completed'])) {
            echo json_encode(['success'=>false,'message'=>'Parámetros inválidos']); exit();
        }
        $stmt = $pdo->prepare('SELECT b.* FROM guest_bookings b JOIN hotel_properties h ON b.hotel_id=h.id WHERE b.id=? AND h.partner_id=?');
        $stmt->execute([$booking_id, $pid]);
        if (!$stmt->fetch()) { echo json_encode(['success'=>false,'message'=>'Reserva no encontrada']); exit(); }
        $pdo->prepare('UPDATE guest_bookings SET status=? WHERE id=?')->execute([$new_status, $booking_id]);
        echo json_encode(['success'=>true,'message'=>'Estado actualizado']); exit();
    }

    echo json_encode(['success'=>false,'message'=>'Acción desconocida']); exit();
}

require_once __DIR__ . '/views/partials/icons.php';

// ── Auth helpers ──────────────────────────────────────────────────────────────
$isLoggedIn   = isset($_SESSION['user_id']);
$isPartner    = isset($_SESSION['partner_id']);
$userName     = $_SESSION['user_name']  ?? '';
$ainiCoins    = $_SESSION['aini_coins'] ?? 0;
$partnerId    = $_SESSION['partner_id'] ?? null;
$partnerName  = $_SESSION['partner_name'] ?? '';

// "View desktop site" cookie — skip all mobile logic
if (isset($_COOKIE['force_desktop'])) {
    header('Location: ../public_booking.php');
    exit();
}

// ── Route map ──────────────────────────────────────────────────────────────────
$page = $_GET['page'] ?? 'hotels';

// Pages that require traveler login
$travelerProtected = ['wallet', 'bookings', 'profile', 'messages', 'booking_confirm'];
// Pages that require partner login
$partnerProtected  = ['partner_dashboard', 'partner_bookings', 'partner_rooms',
                      'partner_photos', 'partner_transactions', 'partner_messages',
                      'partner_profile'];

// Redirect to login if not authenticated
if (in_array($page, $travelerProtected) && !$isLoggedIn) {
    header('Location: ?page=login&redirect=' . urlencode($page));
    exit();
}
if (in_array($page, $partnerProtected) && !$isPartner) {
    header('Location: ?page=partner_login&redirect=' . urlencode($page));
    exit();
}

// Redirect already-logged-in users away from auth pages
if (in_array($page, ['login', 'register']) && $isLoggedIn) {
    header('Location: ?page=hotels');
    exit();
}
if (in_array($page, ['partner_login']) && $isPartner) {
    header('Location: ?page=partner_dashboard');
    exit();
}

// ── View files map ─────────────────────────────────────────────────────────────
$views = [
    // Public
    'hotels'               => 'views/traveler/home.php',
    'hotel'                => 'views/traveler/hotel_detail.php',
    'booking_confirm'      => 'views/traveler/booking_confirm.php',
    // Auth - traveler
    'login'                => 'views/auth/login.php',
    'register'             => 'views/auth/register.php',
    // Auth - partner
    'partner_login'        => 'views/auth/partner_login.php',
    'partner_register'     => 'views/auth/partner_register.php',
    // Traveler
    'bookings'             => 'views/traveler/my_bookings.php',
    'wallet'               => 'views/traveler/wallet.php',
    'profile'              => 'views/traveler/profile.php',
    'messages'             => 'views/traveler/messages.php',
    'social'               => 'views/traveler/social.php',
    // Partner
    'partner_dashboard'    => 'views/partner/dashboard.php',
    'partner_bookings'     => 'views/partner/bookings.php',
    'partner_rooms'        => 'views/partner/rooms.php',
    'partner_photos'       => 'views/partner/photos.php',
    'partner_transactions' => 'views/partner/transactions.php',
    'partner_messages'     => 'views/partner/messages.php',
    'partner_profile'      => 'views/partner/profile.php',
    // Info
    'contact'              => 'views/info/contact.php',
    'terms'                => 'views/info/terms.php',
    'privacy'              => 'views/info/privacy.php',
];

$viewFile = $views[$page] ?? 'views/traveler/home.php';

// Navbar type: partner or traveler
$navType = $isPartner ? 'partner' : 'traveler';

// ── Render ─────────────────────────────────────────────────────────────────────
include 'views/partials/head.php';
include 'views/partials/navbar_top.php';

if (file_exists(__DIR__ . '/' . $viewFile)) {
    include $viewFile;
} else {
    include 'views/traveler/home.php';
}

include 'views/partials/navbar_bottom.php';
include 'views/partials/footer.php';
?>
