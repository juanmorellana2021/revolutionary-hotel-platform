<?php
/**
 * AI Chat API Endpoint
 * Handles chat requests and returns AI responses
 */

session_start();
require_once 'db_connection.php';
require_once 'includes/ollama_ai.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['message'])) {
    echo json_encode(['success' => false, 'error' => 'Missing message']);
    exit;
}

$message = trim($input['message']);
if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Empty message']);
    exit;
}

try {
    // Initialize AI system
    $ai = new OllamaAI();
    
    // Get user ID
    $userId = $_SESSION['user']['id'];
    
    // Add session context if provided
    $context = [];
    if (isset($input['context'])) {
        $context = $input['context'];
    }
    
    // Get AI response
    $result = $ai->chat($message, $userId, $context);
    
    // Return response
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'AI service temporarily unavailable',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>