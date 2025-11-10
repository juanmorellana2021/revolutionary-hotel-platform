<?php
/**
 * AiniFlow Social API - PostgreSQL Version
 * Uses phone numbers as user identifiers (not user_id)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
session_start();

// Database connection (PostgreSQL)
try {
    $pdo = new PDO(
        "pgsql:host=localhost;dbname=aini_platform",
        "postgres",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Check authentication - for testing, allow without session
$user_phone = $_SESSION['user']['phone'] ?? $_GET['test_phone'] ?? null;

if (!$user_phone && !isset($_GET['action']) && $_GET['action'] !== 'get_cards') {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // ============ GET DISCOVER CARDS ============
        case 'get_cards':
            $limit = $_GET['limit'] ?? 20;
            
            // Get travelers user hasn't swiped on yet
            $stmt = $pdo->prepare("
                SELECT u.phone, u.name, u.profile_photo,
                       tp.bio, tp.interests, tp.travel_style, 
                       tp.languages, tp.countries_visited, tp.gender, tp.level
                FROM users u
                LEFT JOIN traveler_profiles tp ON u.phone = tp.phone
                WHERE u.phone != COALESCE(:user_phone, '')
                AND u.phone NOT IN (
                    SELECT swiped_phone FROM profile_swipes 
                    WHERE swiper_phone = COALESCE(:user_phone, '')
                )
                AND tp.bio IS NOT NULL
                ORDER BY RANDOM()
                LIMIT :limit
            ");
            $stmt->bindValue(':user_phone', $user_phone);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format response
            $formatted = array_map(function($card) {
                return [
                    'id' => $card['phone'],
                    'first_name' => explode(' ', $card['name'])[0] ?? $card['name'],
                    'last_name' => explode(' ', $card['name'], 2)[1] ?? '',
                    'bio' => $card['bio'] ?? 'Traveler exploring the world',
                    'profile_photo' => $card['profile_photo'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=800',
                    'interests' => $card['interests'],
                    'travel_style' => $card['travel_style'],
                    'countries_visited' => $card['countries_visited'],
                    'level' => $card['level'] ?? 1
                ];
            }, $cards);
            
            echo json_encode($formatted);
            break;
            
        // ============ SWIPE ACTION ============
        case 'swipe':
            if (!$user_phone) {
                throw new Exception('Not authenticated');
            }
            
            $swiped_phone = $_POST['swiped_id'] ?? '';
            $direction = $_POST['direction'] ?? '';
            $swipe_type = $_POST['type'] ?? 'traveler';
            
            if (!$swiped_phone || !in_array($direction, ['left', 'right'])) {
                throw new Exception('Invalid swipe data');
            }
            
            if ($swiped_phone === $user_phone) {
                throw new Exception('Cannot swipe on yourself');
            }
            
            // Record swipe (INSERT or UPDATE)
            $stmt = $pdo->prepare("
                INSERT INTO profile_swipes (swiper_phone, swiped_phone, swipe_type, direction) 
                VALUES (:swiper, :swiped, :type, :direction)
                ON CONFLICT (swiper_phone, swiped_phone, swipe_type) 
                DO UPDATE SET direction = :direction2, created_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                ':swiper' => $user_phone,
                ':swiped' => $swiped_phone,
                ':type' => $swipe_type,
                ':direction' => $direction,
                ':direction2' => $direction
            ]);
            
            $response = ['success' => true, 'match' => false];
            
            // Check for mutual match (both swiped right)
            if ($direction === 'right') {
                $stmt = $pdo->prepare("
                    SELECT id FROM profile_swipes 
                    WHERE swiper_phone = :other AND swiped_phone = :me AND direction = 'right'
                ");
                $stmt->execute([':other' => $swiped_phone, ':me' => $user_phone]);
                
                if ($stmt->fetch()) {
                    // IT'S A MATCH!
                    $stmt = $pdo->prepare("
                        INSERT INTO matches (phone1, phone2, match_type) 
                        VALUES (:p1, :p2, 'traveler_traveler')
                        ON CONFLICT (phone1, phone2) DO NOTHING
                    ");
                    $stmt->execute([
                        ':p1' => min($user_phone, $swiped_phone),
                        ':p2' => max($user_phone, $swiped_phone)
                    ]);
                    
                    // Award coins
                    awardCoins($pdo, $user_phone, 50);
                    awardCoins($pdo, $swiped_phone, 50);
                    
                    $response['match'] = true;
                }
            }
            
            echo json_encode($response);
            break;
            
        // ============ SEND FRIEND REQUEST ============
        case 'send_friend_request':
            if (!$user_phone) {
                throw new Exception('Not authenticated');
            }
            
            $receiver_phone = $_POST['receiver_id'] ?? '';
            $message = $_POST['message'] ?? '';
            
            if (!$receiver_phone) {
                throw new Exception('Receiver phone required');
            }
            
            // Check if request already exists
            $stmt = $pdo->prepare("
                SELECT id, status FROM friend_requests 
                WHERE (sender_phone = :sender AND receiver_phone = :receiver) 
                   OR (sender_phone = :receiver AND receiver_phone = :sender)
            ");
            $stmt->execute([':sender' => $user_phone, ':receiver' => $receiver_phone]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                if ($existing['status'] === 'blocked') {
                    throw new Exception('Cannot send request');
                }
                if ($existing['status'] === 'accepted') {
                    throw new Exception('Already friends');
                }
                throw new Exception('Request already pending');
            }
            
            // Send request
            $stmt = $pdo->prepare("
                INSERT INTO friend_requests (sender_phone, receiver_phone, message, status) 
                VALUES (:sender, :receiver, :message, 'pending')
            ");
            $stmt->execute([
                ':sender' => $user_phone,
                ':receiver' => $receiver_phone,
                ':message' => $message
            ]);
            
            echo json_encode(['success' => true, 'status' => 'pending']);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

// Helper function to award AiNi Coins
function awardCoins($pdo, $phone, $amount) {
    $stmt = $pdo->prepare("
        UPDATE traveler_profiles 
        SET aini_coins_balance = aini_coins_balance + :amount 
        WHERE phone = :phone
    ");
    $stmt->execute([':amount' => $amount, ':phone' => $phone]);
}
?>
