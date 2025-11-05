<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

$type = $data['type']; // 'traveler' or 'property'
$benefits = $data['benefits'] ?? '';
$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];

try {
    // Create table if not exists
    $conn->query("
        CREATE TABLE IF NOT EXISTS waitlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            type ENUM('traveler', 'property') NOT NULL,
            benefits TEXT,
            ip_address VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_created (created_at)
        )
    ");
    
    // Insert or update
    $stmt = $conn->prepare("
        INSERT INTO waitlist (email, type, benefits, ip_address) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            type = VALUES(type),
            benefits = VALUES(benefits),
            ip_address = VALUES(ip_address)
    ");
    
    $stmt->bind_param('ssss', $email, $type, $benefits, $ipAddress);
    $stmt->execute();
    
    // Send email notification for property registrations
    if ($type === 'property') {
        $to = 'juan.ceo@ainitravel.com';
        $subject = '🏨 New Hotel Property Registration - AiniTravel';
        $message = "
New hotel property registered for early access!

Email: $email
Benefits: $benefits
IP Address: $ipAddress
Registered: " . date('Y-m-d H:i:s') . "

Action Required: Contact them within 24 hours!

View all registrations: http://ainitravel.com/admin-login.php
        ";
        
        $headers = "From: noreply@ainitravel.com\r\n";
        $headers .= "Reply-To: $email\r\n";
        
        mail($to, $subject, $message, $headers);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $type === 'property' ? 'Property registered successfully' : 'Added to waitlist successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
