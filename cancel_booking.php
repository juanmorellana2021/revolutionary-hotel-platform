<?php
/**
 * Cancel Booking API
 * Mirrors booking_api.php pattern exactly.
 * POST: booking_id, action=request_cancel
 */
session_start();

$host     = 'localhost';
$database = 'hotel_booking_system';
$username = 'hoteluser';
$password = 'hotelpass123';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
    exit();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
    exit();
}

$user_id    = intval($_SESSION['user_id']);
$booking_id = intval($_POST['booking_id'] ?? 0);
$action     = $_POST['action'] ?? '';

if ($action !== 'request_cancel' || !$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM guest_bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit();
    }

    if (in_array($booking['status'], ['cancelled', 'completed'])) {
        echo json_encode(['success' => false, 'message' => 'No se puede cancelar esta reserva']);
        exit();
    }

    if ($booking['status'] === 'cancellation_requested') {
        echo json_encode(['success' => false, 'message' => 'Solicitud ya enviada']);
        exit();
    }

    $pdo->prepare("UPDATE guest_bookings SET status = 'cancellation_requested' WHERE id = ? AND user_id = ?")
        ->execute([$booking_id, $user_id]);

    echo json_encode(['success' => true, 'message' => 'Solicitud enviada al hotel']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
