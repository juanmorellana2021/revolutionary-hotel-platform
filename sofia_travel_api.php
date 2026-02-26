<?php
/**
 * Sofia Travel Agent API
 * Handles AI conversations for travel booking assistance
 * 
 * Version: 1.0.0
 * Date: December 14, 2025
 * AI Model: qwen2.5:7b via Ollama (72.60.1.16:11434)
 * Database: hotel_booking_system.ai_conversations
 */

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', 'sofia_api_errors.log');

// Start session for authenticated users
session_start();

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database configuration - Use existing db_connection
require_once 'db_connection.php';

// Convert mysqli to PDO for prepared statements

// Create PDO connection using the same credentials
try {
    $dsn = "mysql:host=localhost;dbname=hotel_booking_system;charset=utf8mb4";
    // Try the same credentials as db_connection.php
    $connection_methods = [
        ['hoteluser', 'hotelpass123'],
        ['root', 'password123'],
        ['root', '']
    ];
    
    $pdo = null;
    foreach ($connection_methods as $method) {
        try {
            $pdo = new PDO($dsn, $method[0], $method[1], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            break; // Success
        } catch (PDOException $e) {
            continue; // Try next method
        }
    }
    
    if (!$pdo) {
        throw new PDOException("All connection methods failed");
    }
} catch (PDOException $e) {
    error_log("Sofia API - Database connection failed: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database unavailable',
        'message' => 'Lo siento, estoy teniendo problemas técnicos. Por favor intenta más tarde.'
    ]);
    exit;
}

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Get or create unique session ID for conversation tracking
 * Stored in cookie for 7 days
 */
function getSofiaSessionId() {
    if (isset($_COOKIE['aini_sofia_session'])) {
        return $_COOKIE['aini_sofia_session'];
    }
    
    // Generate UUID v4
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    // Set cookie for 7 days
    setcookie('aini_sofia_session', $uuid, time() + (7 * 24 * 60 * 60), '/', '', false, true);
    return $uuid;
}

/**
 * Get authenticated user ID from session
 * Returns NULL for anonymous users
 */
function getUserId() {
    // Check multiple possible session variables
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
        return $_SESSION['id'];
    }
    if (isset($_SESSION['ainitravel_user_id']) && !empty($_SESSION['ainitravel_user_id'])) {
        return $_SESSION['ainitravel_user_id'];
    }
    return null; // Anonymous user
}

/**
 * Call Ollama API on AI VPS
 * @param string $prompt The prompt to send
 * @param array $conversationHistory Optional previous messages
 * @return array Result with success, response, tokens, response_time
 */
function callOllamaAPI($prompt, $conversationHistory = []) {
    $url = 'http://72.60.1.16:11434/api/generate';
    
    $data = [
        'model' => 'qwen2.5:7b',
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.7,
            'top_p' => 0.9,
            'num_predict' => 300  // Limit response length
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    // Log the request for debugging
    error_log("Sofia API - Ollama request: " . json_encode($data));
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $responseTime = (microtime(true) - $startTime) * 1000; // milliseconds
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Log response details
    error_log("Sofia API - Ollama raw response (first 300 chars): " . substr($response, 0, 300));
    error_log("Sofia API - HTTP Code: $httpCode, Response Time: {$responseTime}ms");
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        error_log("Sofia API - Ollama curl error: " . $error);
        return [
            'success' => false,
            'error' => $error,
            'response_time' => $responseTime
        ];
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Sofia API - Ollama HTTP error: " . $httpCode);
        return [
            'success' => false,
            'error' => "HTTP $httpCode",
            'response_time' => $responseTime
        ];
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['response'])) {
        error_log("Sofia API - Invalid Ollama response format. JSON decode error: " . json_last_error_msg());
        return [
            'success' => false,
            'error' => 'Invalid response format',
            'response_time' => $responseTime
        ];
    }
    
    error_log("Sofia API - Success! AI response length: " . strlen($result['response']));
    
    return [
        'success' => true,
        'response' => trim($result['response']),
        'tokens_used' => $result['eval_count'] ?? 0,
        'response_time' => $responseTime
    ];
}

/**
 * Build travel-specific prompt for AI
 * @param string $userMessage User's question
 * @param array $context Additional context (hotels, filters, etc)
 * @return string Complete prompt
 */
function buildTravelPrompt($userMessage, $context) {
    $prompt = "Eres Sofia, una asistente virtual experta en viajes para AiNi Travel.\n\n";
    $prompt .= "INSTRUCCIONES IMPORTANTES:\n";
    $prompt .= "- Responde SIEMPRE en español\n";
    $prompt .= "- Sé concisa: máximo 3-4 líneas de texto\n";
    $prompt .= "- Usa emojis apropiados (pero no en exceso)\n";
    $prompt .= "- Sé amigable, profesional y servicial\n";
    $prompt .= "- Si preguntan por reservas, guíalos al proceso de booking\n";
    $prompt .= "- Si no estás segura, ofrece contactar a soporte humano\n";
    $prompt .= "- Enfócate en hoteles, viajes y turismo\n\n";
    
    // Add hotel context if available
    if (!empty($context['current_hotel_ids'])) {
        $hotelCount = is_array($context['current_hotel_ids']) ? count($context['current_hotel_ids']) : $context['current_hotel_ids'];
        $prompt .= "CONTEXTO: Hay $hotelCount hoteles disponibles en la página actual.\n\n";
    }
    
    // Add location context
    if (!empty($context['user_location'])) {
        $prompt .= "Usuario ubicado en: {$context['user_location']}\n\n";
    }
    
    // Add active filters
    if (!empty($context['filters'])) {
        $prompt .= "Filtros activos: " . json_encode($context['filters']) . "\n\n";
    }
    
    $prompt .= "PREGUNTA DEL USUARIO:\n";
    $prompt .= $userMessage . "\n\n";
    $prompt .= "TU RESPUESTA (breve y útil):";
    
    return $prompt;
}

/**
 * Save conversation message to database
 */
function saveConversation($userId, $sessionId, $role, $content, $extraData = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO ai_conversations 
            (user_id, session_id, ai_persona, message_role, message_content, 
             context_data, model_used, tokens_used, response_time_ms, 
             ip_address, user_agent, platform, created_at)
            VALUES (?, ?, 'travel_agent', ?, ?, ?, ?, ?, ?, ?, ?, 'web', NOW())
        ");
        
        $stmt->execute([
            $userId,
            $sessionId,
            $role,
            $content,
            json_encode($extraData['context'] ?? []),
            $extraData['model'] ?? null,
            $extraData['tokens'] ?? null,
            $extraData['response_time'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Sofia API - Save conversation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update or create conversation session
 */
function updateConversationSession($sessionId, $userId) {
    global $pdo;
    
    try {
        // Check if session exists
        $stmt = $pdo->prepare("
            SELECT id, message_count 
            FROM ai_conversation_sessions 
            WHERE session_id = ? AND ai_persona = 'travel_agent'
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if ($session) {
            // Update existing session
            $stmt = $pdo->prepare("
                UPDATE ai_conversation_sessions 
                SET last_activity_at = NOW(),
                    message_count = message_count + 2,
                    user_id = COALESCE(?, user_id),
                    status = 'active'
                WHERE session_id = ? AND ai_persona = 'travel_agent'
            ");
            $stmt->execute([$userId, $sessionId]);
            return $session['id'];
        } else {
            // Create new session
            $stmt = $pdo->prepare("
                INSERT INTO ai_conversation_sessions 
                (user_id, session_id, ai_persona, title, message_count, 
                 started_at, last_activity_at, status)
                VALUES (?, ?, 'travel_agent', 'Conversación de viaje', 2, NOW(), NOW(), 'active')
            ");
            $stmt->execute([$userId, $sessionId]);
            return $pdo->lastInsertId();
        }
        
    } catch (PDOException $e) {
        error_log("Sofia API - Session update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Detect user intent from message
 */
function detectUserIntent($message) {
    $message = strtolower($message);
    
    if (preg_match('/\b(reserv|book|comprar|pagar)\b/i', $message)) {
        return 'booking';
    }
    if (preg_match('/\b(precio|cost|cuanto|barato|económico)\b/i', $message)) {
        return 'pricing';
    }
    if (preg_match('/\b(dónde|ubicación|location|lugar)\b/i', $message)) {
        return 'location';
    }
    if (preg_match('/\b(recomend|suger|mejor|top)\b/i', $message)) {
        return 'recommendation';
    }
    if (preg_match('/\b(familia|niños|kids|children)\b/i', $message)) {
        return 'family_friendly';
    }
    if (preg_match('/\b(playa|beach|mar|ocean)\b/i', $message)) {
        return 'beach';
    }
    if (preg_match('/\b(cancelar|cancel|reembolso|refund)\b/i', $message)) {
        return 'cancellation';
    }
    
    return 'general_inquiry';
}

// ==========================================
// MAIN ROUTER
// ==========================================

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'chat':
        handleChatRequest();
        break;
    case 'history':
        handleHistoryRequest();
        break;
    case 'feedback':
        handleFeedbackRequest();
        break;
    case 'clear':
        handleClearRequest();
        break;
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action',
            'message' => 'Please specify action: chat, history, feedback, or clear'
        ]);
}

// ==========================================
// ACTION HANDLERS
// ==========================================

/**
 * Handle chat message request
 */
function handleChatRequest() {
    global $pdo;
    
    // Get input
    $rawInput = file_get_contents('php://input');
    error_log("Sofia API - Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    error_log("Sofia API - Decoded input: " . json_encode($input));
    
    $userMessage = trim($input['message'] ?? '');
    $context = $input['context'] ?? [];
    
    error_log("Sofia API - User message: '$userMessage'");
    
    // Validate
    if (empty($userMessage)) {
        echo json_encode([
            'success' => false,
            'error' => 'Message required',
            'message' => 'Por favor escribe un mensaje.'
        ]);
        return;
    }
    
    // Get session and user
    $sessionId = getSofiaSessionId();
    $userId = getUserId();
    
    // Detect intent
    $userIntent = detectUserIntent($userMessage);
    
    // Save user message
    $userConvId = saveConversation($userId, $sessionId, 'user', $userMessage, [
        'context' => $context
    ]);
    
    if (!$userConvId) {
        error_log("Sofia API - Failed to save user message");
    }
    
    // Build AI prompt
    $prompt = buildTravelPrompt($userMessage, $context);
    
    // Call AI
    $aiResult = callOllamaAPI($prompt);
    
    if (!$aiResult['success']) {
        // AI service failed - return friendly error
        $fallbackMessage = "Lo siento, estoy teniendo problemas técnicos en este momento. 😔\n\n";
        $fallbackMessage .= "Por favor intenta:\n";
        $fallbackMessage .= "• Refrescar la página\n";
        $fallbackMessage .= "• Contactar soporte: +1234567890 📱\n";
        $fallbackMessage .= "• WhatsApp: wa.me/1234567890";
        
        echo json_encode([
            'success' => false,
            'error' => 'AI service unavailable',
            'response' => $fallbackMessage,
            'session_id' => $sessionId
        ]);
        return;
    }
    
    // Clean AI response
    $aiResponse = $aiResult['response'];
    $aiResponse = preg_replace('/\*\*/g', '', $aiResponse); // Remove markdown bold
    $aiResponse = trim($aiResponse);
    
    // Save AI response
    $aiConvId = saveConversation($userId, $sessionId, 'assistant', $aiResponse, [
        'context' => $context,
        'model' => 'qwen2.5:7b',
        'tokens' => $aiResult['tokens_used'],
        'response_time' => round($aiResult['response_time'])
    ]);
    
    if (!$aiConvId) {
        error_log("Sofia API - Failed to save AI response");
    }
    
    // Update session
    updateConversationSession($sessionId, $userId);
    
    // Return success
    echo json_encode([
        'success' => true,
        'response' => $aiResponse,
        'session_id' => $sessionId,
        'conversation_id' => $aiConvId,
        'tokens_used' => $aiResult['tokens_used'],
        'response_time_ms' => round($aiResult['response_time']),
        'user_intent' => $userIntent,
        'is_authenticated' => $userId !== null
    ]);
}

/**
 * Handle conversation history request
 */
function handleHistoryRequest() {
    global $pdo;
    
    $sessionId = $_COOKIE['aini_sofia_session'] ?? '';
    
    if (empty($sessionId)) {
        echo json_encode([
            'success' => true,
            'messages' => [],
            'session_id' => null
        ]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                message_role as role,
                message_content as content,
                created_at,
                tokens_used,
                response_time_ms
            FROM ai_conversations
            WHERE session_id = ? 
            AND ai_persona = 'travel_agent'
            ORDER BY created_at ASC
            LIMIT 100
        ");
        $stmt->execute([$sessionId]);
        $messages = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'session_id' => $sessionId,
            'count' => count($messages)
        ]);
        
    } catch (PDOException $e) {
        error_log("Sofia API - History error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Database error',
            'message' => 'No se pudo cargar el historial.'
        ]);
    }
}

/**
 * Handle user feedback request
 */
function handleFeedbackRequest() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $conversationId = $input['conversation_id'] ?? null;
    $rating = $input['rating'] ?? null;
    $sentiment = $input['sentiment'] ?? 'neutral';
    
    if (!$conversationId) {
        echo json_encode([
            'success' => false,
            'error' => 'Conversation ID required'
        ]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE ai_conversations 
            SET sentiment = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$sentiment, $conversationId]);
        
        echo json_encode([
            'success' => true,
            'message' => '¡Gracias por tu feedback!'
        ]);
        
    } catch (PDOException $e) {
        error_log("Sofia API - Feedback error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Failed to save feedback'
        ]);
    }
}

/**
 * Handle clear conversation request
 */
function handleClearRequest() {
    // Just clear the cookie - don't delete DB records for analytics
    setcookie('aini_sofia_session', '', time() - 3600, '/', '', false, true);
    
    echo json_encode([
        'success' => true,
        'message' => 'Conversación reiniciada'
    ]);
}

?>
