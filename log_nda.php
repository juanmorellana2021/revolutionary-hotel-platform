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

// Validate required fields
$required = ['fullName', 'email', 'company', 'title', 'investorType', 'signature', 'agreedAt'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

// Get IP address
$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];

// Convert JavaScript ISO 8601 datetime to MySQL format
$agreedAt = date('Y-m-d H:i:s', strtotime($data['agreedAt']));

// Save signature image to file
$signatureData = $data['signature'];
$signatureData = str_replace('data:image/png;base64,', '', $signatureData);
$signatureData = base64_decode($signatureData);
$signatureFilename = 'signatures/signature_' . time() . '_' . md5($data['email']) . '.png';
$signaturePath = __DIR__ . '/' . $signatureFilename;

// Create signatures directory if it doesn't exist
if (!is_dir(__DIR__ . '/signatures')) {
    mkdir(__DIR__ . '/signatures', 0755, true);
}

file_put_contents($signaturePath, $signatureData);

try {
    // Insert into database
    $stmt = $conn->prepare("
        INSERT INTO investor_ndas 
        (full_name, email, company, title, investor_type, signature_path, agreed_at, ip_address, status, created_at) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, 'nda_signed', NOW())
    ");
    
    $stmt->bind_param('ssssssss', 
        $data['fullName'],
        $data['email'],
        $data['company'],
        $data['title'],
        $data['investorType'],
        $signatureFilename,
        $agreedAt,
        $ipAddress
    );
    
    $stmt->execute();
    $ndaId = $conn->insert_id;
    
    // Send email notification to admin
    $to = 'juan.ceo@ainitravel.com';
    $subject = '🎯 New Investor NDA Signed - ' . $data['fullName'];
    $message = "
    New investor has signed the NDA!
    
    Name: {$data['fullName']}
    Email: {$data['email']}
    Company: {$data['company']}
    Title: {$data['title']}
    Type: {$data['investorType']}
    Signed At: {$data['agreedAt']}
    IP Address: $ipAddress
    
    View all investors: http://ainitravel.com/investor-admin.php
    ";
    
    $headers = "From: noreply@ainitravel.com\r\n";
    $headers .= "Reply-To: {$data['email']}\r\n";
    
    mail($to, $subject, $message, $headers);
    
    echo json_encode([
        'success' => true,
        'message' => 'NDA recorded successfully',
        'id' => $ndaId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
