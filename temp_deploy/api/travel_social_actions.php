<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once '../includes/classes.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$connection = getConnection();
$user = $_SESSION['user'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['action'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request format']);
            exit;
        }
        
        $action = $input['action'];
        
        switch ($action) {
            case 'like_post':
                $postId = intval($input['post_id'] ?? 0);
                
                if ($postId <= 0) {
                    throw new Exception('Invalid post ID');
                }
                
                // Check if already liked
                $stmt = $connection->prepare("
                    SELECT id FROM travel_post_likes 
                    WHERE post_id = ? AND user_id = ?
                ");
                $stmt->execute([$postId, $user['id']]);
                $existingLike = $stmt->fetch();
                
                if ($existingLike) {
                    // Unlike the post
                    $stmt = $connection->prepare("
                        DELETE FROM travel_post_likes 
                        WHERE post_id = ? AND user_id = ?
                    ");
                    $stmt->execute([$postId, $user['id']]);
                    
                    echo json_encode([
                        'success' => true,
                        'action' => 'unliked',
                        'message' => 'Post unliked'
                    ]);
                } else {
                    // Like the post
                    $stmt = $connection->prepare("
                        INSERT INTO travel_post_likes (post_id, user_id, created_at)
                        VALUES (?, ?, NOW())
                    ");
                    $stmt->execute([$postId, $user['id']]);
                    
                    // Award AiNi coins for liking (small amount)
                    $stmt = $connection->prepare("
                        INSERT INTO aini_social_earnings (user_id, activity_type, activity_id, aini_amount, description)
                        VALUES (?, 'post_liked', ?, 0.25, 'Liked travel post')
                    ");
                    $stmt->execute([$user['id'], $postId]);
                    
                    // Get post author to send notification
                    $stmt = $connection->prepare("
                        SELECT user_id FROM travel_posts WHERE id = ?
                    ");
                    $stmt->execute([$postId]);
                    $postAuthor = $stmt->fetch();
                    
                    if ($postAuthor && $postAuthor['user_id'] != $user['id']) {
                        // Send notification to post author
                        $stmt = $connection->prepare("
                            INSERT INTO travel_notifications (user_id, type, title, message, related_id, related_type, aini_reward)
                            VALUES (?, 'post_like', 'Post Liked!', ?, ?, 'post', 1.00)
                        ");
                        $notificationMessage = $user['first_name'] . ' ' . $user['last_name'] . ' liked your travel post!';
                        $stmt->execute([$postAuthor['user_id'], $notificationMessage, $postId]);
                        
                        // Award AiNi to post author for engagement
                        $stmt = $connection->prepare("
                            INSERT INTO aini_social_earnings (user_id, activity_type, activity_id, aini_amount, description)
                            VALUES (?, 'post_liked', ?, 1.00, 'Your post was liked')
                        ");
                        $stmt->execute([$postAuthor['user_id'], $postId]);
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'action' => 'liked',
                        'message' => 'Post liked! You earned 0.25 AiNi coins!',
                        'aini_earned' => 0.25
                    ]);
                }
                break;
                
            case 'add_comment':
                $postId = intval($input['post_id'] ?? 0);
                $content = trim($input['content'] ?? '');
                $parentCommentId = intval($input['parent_comment_id'] ?? 0) ?: null;
                
                if ($postId <= 0 || empty($content)) {
                    throw new Exception('Invalid post ID or empty content');
                }
                
                // Insert comment
                $stmt = $connection->prepare("
                    INSERT INTO travel_post_comments (post_id, user_id, parent_comment_id, content, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$postId, $user['id'], $parentCommentId, $content]);
                $commentId = $connection->lastInsertId();
                
                // Award AiNi coins for commenting
                $stmt = $connection->prepare("
                    INSERT INTO aini_social_earnings (user_id, activity_type, activity_id, aini_amount, description)
                    VALUES (?, 'comment_made', ?, 1.50, 'Commented on travel post')
                ");
                $stmt->execute([$user['id'], $commentId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Comment added! You earned 1.50 AiNi coins!',
                    'aini_earned' => 1.50,
                    'comment_id' => $commentId
                ]);
                break;
                
            case 'join_meetup':
                $meetupId = intval($input['meetup_id'] ?? 0);
                $message = trim($input['message'] ?? '');
                
                if ($meetupId <= 0) {
                    throw new Exception('Invalid meetup ID');
                }
                
                // Check if meetup exists and has space
                $stmt = $connection->prepare("
                    SELECT * FROM travel_meetups 
                    WHERE id = ? AND status = 'confirmed' AND current_participants < max_participants
                ");
                $stmt->execute([$meetupId]);
                $meetup = $stmt->fetch();
                
                if (!$meetup) {
                    throw new Exception('Meetup not found or full');
                }
                
                // Check if already joined
                $stmt = $connection->prepare("
                    SELECT id FROM travel_meetup_participants 
                    WHERE meetup_id = ? AND user_id = ?
                ");
                $stmt->execute([$meetupId, $user['id']]);
                
                if ($stmt->fetch()) {
                    throw new Exception('Already joined this meetup');
                }
                
                // Join meetup
                $stmt = $connection->prepare("
                    INSERT INTO travel_meetup_participants (meetup_id, user_id, status, message, joined_at)
                    VALUES (?, ?, 'confirmed', ?, NOW())
                ");
                $stmt->execute([$meetupId, $user['id'], $message]);
                
                // Update participant count
                $stmt = $connection->prepare("
                    UPDATE travel_meetups 
                    SET current_participants = current_participants + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$meetupId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Successfully joined meetup!'
                ]);
                break;
                
            case 'search_travelers':
                $location = trim($input['location'] ?? '');
                $interests = $input['interests'] ?? [];
                $travelStyle = trim($input['travel_style'] ?? '');
                $dateRange = $input['date_range'] ?? [];
                
                $query = "
                    SELECT DISTINCT u.id, u.first_name, u.last_name, u.profile_photo,
                           tp.bio, tp.interests, tp.travel_style, tp.languages, tp.age_range,
                           b.check_in_date, b.check_out_date, h.hotel_name, h.city, h.state,
                           (SELECT COUNT(*) FROM travel_connections tc 
                            WHERE (tc.user_id = u.id OR tc.friend_id = u.id) 
                            AND tc.status = 'accepted') as connection_count
                    FROM users u
                    LEFT JOIN traveler_profiles tp ON u.id = tp.user_id
                    LEFT JOIN bookings b ON u.id = b.user_id
                    LEFT JOIN rooms r ON b.room_id = r.id
                    LEFT JOIN hotel_info h ON r.hotel_id = h.id
                    WHERE u.travel_social_enabled = 1 AND u.id != ?
                ";
                
                $params = [$user['id']];
                
                if (!empty($location)) {
                    $query .= " AND (h.city LIKE ? OR h.state LIKE ? OR h.country LIKE ?)";
                    $locationPattern = '%' . $location . '%';
                    $params[] = $locationPattern;
                    $params[] = $locationPattern;
                    $params[] = $locationPattern;
                }
                
                if (!empty($travelStyle)) {
                    $query .= " AND tp.travel_style = ?";
                    $params[] = $travelStyle;
                }
                
                $query .= " ORDER BY connection_count DESC, b.check_in_date ASC LIMIT 50";
                
                $stmt = $connection->prepare($query);
                $stmt->execute($params);
                $travelers = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'travelers' => $travelers,
                    'count' => count($travelers)
                ]);
                break;
                
            case 'get_travel_feed':
                $limit = intval($input['limit'] ?? 20);
                $offset = intval($input['offset'] ?? 0);
                
                $stmt = $connection->prepare("
                    SELECT tp.*, u.first_name, u.last_name, u.profile_photo,
                           h.hotel_name, h.city, h.state,
                           (SELECT COUNT(*) FROM travel_post_likes tpl WHERE tpl.post_id = tp.id) as like_count,
                           (SELECT COUNT(*) FROM travel_post_comments tpc WHERE tpc.post_id = tp.id) as comment_count,
                           (SELECT COUNT(*) FROM travel_post_likes tpl WHERE tpl.post_id = tp.id AND tpl.user_id = ?) as user_liked
                    FROM travel_posts tp
                    JOIN users u ON tp.user_id = u.id
                    LEFT JOIN hotel_info h ON tp.location_id = h.id
                    WHERE tp.privacy = 'public' OR tp.user_id IN (
                        SELECT CASE 
                            WHEN tc.user_id = ? THEN tc.friend_id 
                            ELSE tc.user_id 
                        END
                        FROM travel_connections tc 
                        WHERE (tc.user_id = ? OR tc.friend_id = ?) 
                        AND tc.status = 'accepted'
                    )
                    ORDER BY tp.created_at DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $limit, $offset]);
                $posts = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'posts' => $posts,
                    'count' => count($posts)
                ]);
                break;
                
            case 'get_notifications':
                $limit = intval($input['limit'] ?? 20);
                
                $stmt = $connection->prepare("
                    SELECT * FROM travel_notifications 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$user['id'], $limit]);
                $notifications = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'notifications' => $notifications,
                    'count' => count($notifications)
                ]);
                break;
                
            case 'mark_notification_read':
                $notificationId = intval($input['notification_id'] ?? 0);
                
                if ($notificationId <= 0) {
                    throw new Exception('Invalid notification ID');
                }
                
                $stmt = $connection->prepare("
                    UPDATE travel_notifications 
                    SET read_status = TRUE 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$notificationId, $user['id']]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);
                break;
                
            default:
                throw new Exception('Unknown action: ' . $action);
        }
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle GET requests for reading data
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'stats':
                // Get user's social stats
                $stmt = $connection->prepare("
                    SELECT 
                        (SELECT COUNT(*) FROM travel_posts WHERE user_id = ?) as posts_count,
                        (SELECT COUNT(*) FROM travel_connections WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted') as connections_count,
                        (SELECT COALESCE(SUM(aini_amount), 0) FROM aini_social_earnings WHERE user_id = ?) as total_aini_earned,
                        (SELECT COUNT(*) FROM travel_post_likes tpl 
                         INNER JOIN travel_posts tp ON tpl.post_id = tp.id 
                         WHERE tp.user_id = ?) as total_likes_received
                ");
                $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id']]);
                $stats = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'stats' => $stats
                ]);
                break;
                
            default:
                throw new Exception('Unknown GET action: ' . $action);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>