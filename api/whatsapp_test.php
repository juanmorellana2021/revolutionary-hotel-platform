<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'includes/whatsapp_bot.php';
require_once 'includes/classes.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$connection = getConnection();

// Get WhatsApp configuration
$stmt = $connection->prepare("
    SELECT config_key, config_value 
    FROM ai_chat_config 
    WHERE config_key IN ('whatsapp_enabled', 'whatsapp_access_token', 'whatsapp_phone_number_id')
");
$stmt->execute();
$config = [];
while ($row = $stmt->fetch()) {
    $config[$row['config_key']] = $row['config_value'];
}

// Check if WhatsApp is enabled
if (($config['whatsapp_enabled'] ?? '0') !== '1') {
    http_response_code(503);
    echo json_encode(['error' => 'WhatsApp integration is not enabled']);
    exit;
}

try {
    $whatsappBot = new WhatsAppHotelBot(
        $config['whatsapp_access_token'] ?? '',
        $config['whatsapp_phone_number_id'] ?? ''
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Test message endpoint
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }

        if (isset($input['action']) && $input['action'] === 'test_message') {
            // Test sending a message
            $phoneNumber = $input['phone_number'] ?? '';
            $message = $input['message'] ?? 'Hello from Revolutionary Hotel Platform! 🏨';
            
            if (empty($phoneNumber)) {
                http_response_code(400);
                echo json_encode(['error' => 'Phone number is required']);
                exit;
            }
            
            $response = $whatsappBot->sendMessage($phoneNumber, $message);
            
            if ($response) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Test message sent successfully',
                    'phone_number' => $phoneNumber,
                    'content' => $message
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to send message']);
            }
        } else if (isset($input['action']) && $input['action'] === 'test_booking') {
            // Test booking flow
            $phoneNumber = $input['phone_number'] ?? '';
            $testMessage = "I want to book a room for 2 guests from December 25th to December 30th";
            
            if (empty($phoneNumber)) {
                http_response_code(400);
                echo json_encode(['error' => 'Phone number is required']);
                exit;
            }
            
            // Simulate incoming message
            $mockMessage = [
                'from' => $phoneNumber,
                'text' => ['body' => $testMessage],
                'timestamp' => time()
            ];
            
            $response = $whatsappBot->handleIncomingMessage($mockMessage);
            
            echo json_encode([
                'success' => true,
                'message' => 'Test booking flow completed',
                'phone_number' => $phoneNumber,
                'test_input' => $testMessage,
                'ai_response' => $response
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action specified']);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Status endpoint
        $stats = [];
        
        // Get conversation stats
        $stmt = $connection->prepare("
            SELECT 
                COUNT(DISTINCT phone_number) as total_users,
                COUNT(*) as total_conversations,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as conversations_24h
            FROM whatsapp_conversations
        ");
        $stmt->execute();
        $conversationStats = $stmt->fetch();
        
        // Get booking stats
        $stmt = $connection->prepare("
            SELECT 
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as sessions_24h
            FROM whatsapp_booking_sessions
        ");
        $stmt->execute();
        $bookingStats = $stmt->fetch();
        
        echo json_encode([
            'status' => 'active',
            'whatsapp_enabled' => true,
            'has_credentials' => !empty($config['whatsapp_access_token']),
            'stats' => [
                'total_whatsapp_users' => intval($conversationStats['total_users'] ?? 0),
                'total_conversations' => intval($conversationStats['total_conversations'] ?? 0),
                'conversations_24h' => intval($conversationStats['conversations_24h'] ?? 0),
                'total_booking_sessions' => intval($bookingStats['total_sessions'] ?? 0),
                'completed_bookings' => intval($bookingStats['completed_bookings'] ?? 0),
                'booking_sessions_24h' => intval($bookingStats['sessions_24h'] ?? 0)
            ],
            'features' => [
                'ai_powered_responses' => true,
                'hotelcoin_integration' => true,
                'loyalty_program' => true,
                'automated_booking' => true,
                'multilingual_support' => true,
                'analytics_tracking' => true
            ]
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'WhatsApp service error',
        'message' => $e->getMessage()
    ]);
}
?>