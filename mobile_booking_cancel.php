<?php
/**
 * Mobile Booking Cancel API
 * Standalone endpoint — called via AJAX from my_bookings.php
 * POST: action=request_cancel, booking_id=X
 */
error_reporting(0);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');

$uid = $_SESSION['user_id'] ?? 0;
if (!$uid) {
    echo json_encode(['success'=>false,'message'=>'No autenticado']); exit();
}

$action     = $_POST['action'] ?? '';
$booking_id = intval($_POST['booking_id'] ?? 0);

if ($action !== 'request_cancel' || !$booking_id) {
    echo json_encode(['success'=>false,'message'=>'Parámetros inválidos']); exit();
}

$host   = 'localhost';
$dbname = 'hotel_booking_system';
$dbuser = 'hoteluser';
$dbpass = 'hotelpass123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Error de base de datos']); exit();
}

$stmt = $pdo->prepare("SELECT * FROM guest_bookings WHERE id=? AND user_id=?");
$stmt->execute([$booking_id, $uid]);
$b = $stmt->fetch();

if (!$b) {
    echo json_encode(['success'=>false,'message'=>'Reserva no encontrada']); exit();
}
if (in_array($b['status'], ['cancelled','completed'])) {
    echo json_encode(['success'=>false,'message'=>'No se puede cancelar esta reserva']); exit();
}
if ($b['status'] === 'cancellation_requested') {
    echo json_encode(['success'=>false,'message'=>'Ya tienes una solicitud pendiente']); exit();
}

$pdo->prepare("UPDATE guest_bookings SET status='cancellation_requested' WHERE id=? AND user_id=?")
    ->execute([$booking_id, $uid]);

echo json_encode(['success'=>true,'message'=>'Solicitud de cancelación enviada al hotel']);
