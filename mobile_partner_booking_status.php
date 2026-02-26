<?php
/**
 * Partner Booking Status Update API
 * Standalone endpoint — called via AJAX from partner/bookings.php
 * POST: action=update_status, booking_id=X, status=Y
 */
session_start();
header('Content-Type: application/json');

$pid = $_SESSION['partner_id'] ?? 0;
if (!$pid) {
    echo json_encode(['success'=>false,'message'=>'No autenticado']); exit();
}

$action     = $_POST['action'] ?? '';
$booking_id = intval($_POST['booking_id'] ?? 0);
$newStatus  = trim($_POST['status'] ?? '');

if ($action !== 'update_status' || !$booking_id) {
    echo json_encode(['success'=>false,'message'=>'Parámetros inválidos']); exit();
}
if (!in_array($newStatus, ['confirmed','cancelled','completed'])) {
    echo json_encode(['success'=>false,'message'=>'Estado inválido']); exit();
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

// Verify booking belongs to this partner's hotel
$stmt = $pdo->prepare("
    SELECT b.id FROM guest_bookings b
    JOIN hotel_properties h ON b.hotel_id = h.id
    WHERE b.id = ? AND (h.partner_business_id = ? OR h.email = (SELECT email FROM aini_partner_businesses WHERE id = ?))
");
$stmt->execute([$booking_id, $pid, $pid]);
if (!$stmt->fetch()) {
    echo json_encode(['success'=>false,'message'=>'Reserva no encontrada']); exit();
}

$pdo->prepare("UPDATE guest_bookings SET status=? WHERE id=?")->execute([$newStatus, $booking_id]);
echo json_encode(['success'=>true,'message'=>'Estado actualizado']);
