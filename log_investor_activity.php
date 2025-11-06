<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['investor_logged_in']) || !$_SESSION['investor_logged_in']) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$details = $data['details'] ?? '';
$investor_id = $_SESSION['investor_id'] ?? 0;

if ($investor_id && $action) {
    $stmt = $conn->prepare("INSERT INTO investor_activity_log (investor_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("isss", $investor_id, $action, $details, $ip);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
}
?>
