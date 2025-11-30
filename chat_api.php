<?php
/**
 * Secure Chat/Messaging API
 * Handles real-time messaging with security protections
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection_pdo.php';
session_start();

// Rate limiting helper
function checkRateLimit($user_id, $action = 'message', $limit = 60, $window = 60) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM messages 
        WHERE sender_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$user_id, $window]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] >= $limit) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded. Please slow down.']);
        exit;
    }
}

// Security: Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // ============ GET CONVERSATIONS LIST ============
        case 'get_conversations':
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.conversation_type,
                    c.title,
                    c.last_message_at,
                    m.message_text as last_message,
                    m.sender_id as last_sender_id,
                    m.created_at as last_message_time,
                    sender.first_name as last_sender_name,
                    -- Get other participant info for direct chats
                    CASE WHEN c.conversation_type = 'direct' THEN
                        (SELECT u.first_name FROM users u 
                         JOIN conversation_participants cp2 ON u.id = cp2.user_id 
                         WHERE cp2.conversation_id = c.id AND cp2.user_id != ? 
                         LIMIT 1)
                    END as other_user_name,
                    CASE WHEN c.conversation_type = 'direct' THEN
                        (SELECT u.id FROM users u 
                         JOIN conversation_participants cp2 ON u.id = cp2.user_id 
                         WHERE cp2.conversation_id = c.id AND cp2.user_id != ? 
                         LIMIT 1)
                    END as other_user_id,
                    -- Count unread messages
                    (SELECT COUNT(*) FROM messages m2 
                     WHERE m2.conversation_id = c.id 
                     AND m2.sender_id != ?
                     AND m2.created_at > COALESCE(cp.last_read_at, '2000-01-01')
                    ) as unread_count,
                    cp.is_muted,
                    cp.last_read_at
                FROM conversations c
                JOIN conversation_participants cp ON c.id = cp.conversation_id
                LEFT JOIN messages m ON c.id = m.conversation_id 
                    AND m.created_at = (
                        SELECT MAX(created_at) FROM messages WHERE conversation_id = c.id
                    )
                LEFT JOIN users sender ON m.sender_id = sender.id
                WHERE cp.user_id = ?
                AND c.is_active = TRUE
                AND cp.is_blocked = FALSE
                ORDER BY c.last_message_at DESC, c.created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'conversations' => $conversations]);
            break;
            
        // ============ GET CONVERSATION MESSAGES ============
        case 'get_messages':
            $conversation_id = $_GET['conversation_id'] ?? 0;
            $limit = min(intval($_GET['limit'] ?? 50), 100);
            $offset = intval($_GET['offset'] ?? 0);
            
            // Security: Check if user is participant in this conversation
            $stmt = $pdo->prepare("
                SELECT id FROM conversation_participants 
                WHERE conversation_id = ? AND user_id = ? AND is_blocked = FALSE
            ");
            $stmt->execute([$conversation_id, $user_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Access denied to this conversation');
            }
            
            // Get messages
            $stmt = $pdo->prepare("
                SELECT 
                    m.id,
                    m.sender_id,
                    m.message_text,
                    m.message_type,
                    m.attachment_url,
                    m.attachment_filename,
                    m.reply_to_message_id,
                    m.is_edited,
                    m.is_deleted,
                    m.created_at,
                    u.first_name as sender_name,
                    u.last_name as sender_last_name,
                    -- Count who read this message
                    (SELECT COUNT(*) FROM message_reads WHERE message_id = m.id) as read_count
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = ?
                AND m.is_deleted = FALSE
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$conversation_id, $limit, $offset]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Reverse to show oldest first
            $messages = array_reverse($messages);
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            break;
            
        // ============ SEND MESSAGE ============
        case 'send_message':
            $conversation_id = $_POST['conversation_id'] ?? 0;
            $message_text = trim($_POST['message'] ?? '');
            $message_type = $_POST['type'] ?? 'text';
            $reply_to = $_POST['reply_to'] ?? null;
            
            if (empty($message_text) || strlen($message_text) > 5000) {
                throw new Exception('Invalid message length');
            }
            
            // Rate limiting
            checkRateLimit($user_id, 'message', 60, 60); // 60 messages per minute
            
            // Security: Check if user is participant
            $stmt = $pdo->prepare("
                SELECT id FROM conversation_participants 
                WHERE conversation_id = ? AND user_id = ? AND is_blocked = FALSE
            ");
            $stmt->execute([$conversation_id, $user_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Access denied to this conversation');
            }
            
            // Security: Sanitize message (prevent XSS)
            $message_text = htmlspecialchars($message_text, ENT_QUOTES, 'UTF-8');
            
            // Insert message
            $stmt = $pdo->prepare("
                INSERT INTO messages (conversation_id, sender_id, message_text, message_type, reply_to_message_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$conversation_id, $user_id, $message_text, $message_type, $reply_to]);
            $message_id = $pdo->lastInsertId();
            
            // Update conversation last_message_at
            $stmt = $pdo->prepare("
                UPDATE conversations SET last_message_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$conversation_id]);
            
            // Create notification for other participants
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message)
                SELECT cp.user_id, 'message', CONCAT(?, ' sent you a message'), ?
                FROM conversation_participants cp
                WHERE cp.conversation_id = ? AND cp.user_id != ? AND cp.is_muted = FALSE
            ");
            $sender_name = $_SESSION['user']['first_name'] ?? 'Someone';
            $stmt->execute([$sender_name, substr($message_text, 0, 100), $conversation_id, $user_id]);
            
            echo json_encode([
                'success' => true, 
                'message_id' => $message_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============ CREATE CONVERSATION ============
        case 'create_conversation':
            $other_user_id = $_POST['user_id'] ?? 0;
            $initial_message = trim($_POST['message'] ?? '');
            
            if (!$other_user_id) {
                throw new Exception('Invalid user ID');
            }
            
            // Check if conversation already exists
            $stmt = $pdo->prepare("
                SELECT c.id 
                FROM conversations c
                JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
                JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
                WHERE c.conversation_type = 'direct'
                AND cp1.user_id = ? AND cp2.user_id = ?
                LIMIT 1
            ");
            $stmt->execute([$user_id, $other_user_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                $conversation_id = $existing['id'];
            } else {
                // Create new conversation
                $stmt = $pdo->prepare("
                    INSERT INTO conversations (conversation_type, created_by, last_message_at)
                    VALUES ('direct', ?, NOW())
                ");
                $stmt->execute([$user_id]);
                $conversation_id = $pdo->lastInsertId();
                
                // Add both participants
                $stmt = $pdo->prepare("
                    INSERT INTO conversation_participants (conversation_id, user_id)
                    VALUES (?, ?), (?, ?)
                ");
                $stmt->execute([$conversation_id, $user_id, $conversation_id, $other_user_id]);
            }
            
            // Send initial message if provided
            if (!empty($initial_message)) {
                $initial_message = htmlspecialchars($initial_message, ENT_QUOTES, 'UTF-8');
                $stmt = $pdo->prepare("
                    INSERT INTO messages (conversation_id, sender_id, message_text)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$conversation_id, $user_id, $initial_message]);
            }
            
            echo json_encode(['success' => true, 'conversation_id' => $conversation_id]);
            break;
            
        // ============ MARK AS READ ============
        case 'mark_read':
            $conversation_id = $_POST['conversation_id'] ?? 0;
            
            // Update last_read_at
            $stmt = $pdo->prepare("
                UPDATE conversation_participants 
                SET last_read_at = NOW() 
                WHERE conversation_id = ? AND user_id = ?
            ");
            $stmt->execute([$conversation_id, $user_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        // ============ DELETE MESSAGE ============
        case 'delete_message':
            $message_id = $_POST['message_id'] ?? 0;
            
            // Security: Only sender can delete their own messages
            $stmt = $pdo->prepare("
                UPDATE messages SET is_deleted = TRUE, deleted_at = NOW()
                WHERE id = ? AND sender_id = ?
            ");
            $stmt->execute([$message_id, $user_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        // ============ BLOCK USER ============
        case 'block_user':
            $blocked_id = $_POST['user_id'] ?? 0;
            $reason = $_POST['reason'] ?? '';
            
            $stmt = $pdo->prepare("
                INSERT INTO blocked_users (blocker_id, blocked_id, reason)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE reason = ?
            ");
            $stmt->execute([$user_id, $blocked_id, $reason, $reason]);
            
            // Block all conversations with this user
            $stmt = $pdo->prepare("
                UPDATE conversation_participants cp
                JOIN conversation_participants cp2 ON cp.conversation_id = cp2.conversation_id
                SET cp.is_blocked = TRUE
                WHERE cp.user_id = ? AND cp2.user_id = ?
            ");
            $stmt->execute([$user_id, $blocked_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
