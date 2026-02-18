<?php
session_start();
require_once '../db_connection_pdo.php';

header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['partner_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = $input['booking_id'] ?? null;
$new_status = $input['status'] ?? null;

if (!$booking_id || !$new_status) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit();
}

// Validate status
$valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit();
}

try {
    // Get partner info
    $stmt = $pdo->prepare("SELECT * FROM aini_partner_businesses WHERE id = ?");
    $stmt->execute([$_SESSION['partner_id']]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partner) {
        echo json_encode(['success' => false, 'error' => 'Partner not found']);
        exit();
    }
    
    // Verify booking belongs to this partner's hotel
    $stmt = $pdo->prepare("
        SELECT gb.* 
        FROM guest_bookings gb
        JOIN hotel_properties hp ON gb.hotel_id = hp.id
        WHERE gb.id = ? AND (hp.partner_business_id = ? OR hp.email = ?)
    ");
    $stmt->execute([$booking_id, $_SESSION['partner_id'], $partner['email']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'error' => 'Booking not found or access denied']);
        exit();
    }
    
    // Update booking status
    $stmt = $pdo->prepare("
        UPDATE guest_bookings 
        SET status = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $booking_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Booking status updated',
        'new_status' => $new_status
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
