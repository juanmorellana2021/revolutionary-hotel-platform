<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$connection = $conn;
$user = $_SESSION['user'];

// Get user's current and upcoming bookings for location-based matching
$stmt = $connection->prepare("
    SELECT b.*, r.room_type, r.booking_room_name as room_name, h.hotel_name, h.city, h.state, h.country
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN hotel_info h ON h.id = 1
    WHERE b.user_id = ? 
    AND (b.check_out_date >= CURDATE() OR b.check_in_date >= CURDATE())
    ORDER BY b.check_in_date ASC
");

// For now, let's just get basic bookings without complex joins
$basic_query = "SELECT * FROM bookings WHERE user_id = ? AND check_out_date >= CURDATE() ORDER BY check_in_date ASC";
$stmt = $connection->prepare($basic_query);

if ($stmt) {
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $userBookings = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $userBookings = [];
}

// Get travelers in same locations (simplified for current database structure)
$travelersNearby = [];

// For now, let's create some sample travelers data since we don't have the full social database yet
$sampleTravelers = [
    [
        'id' => 1,
        'first_name' => 'Sarah',
        'last_name' => 'Johnson', 
        'profile_photo' => '/images/avatars/sarah.jpg',
        'bio' => 'Love exploring new cities and trying local cuisine!',
        'interests' => 'Food, Culture, Photography',
        'travel_style' => 'Cultural Explorer',
        'languages' => 'English, Spanish',
        'connection_count' => 12,
        'city' => 'Miami Beach',
        'room_name' => 'Ocean View Suite',
        'check_in_date' => date('Y-m-d', strtotime('+3 days')),
        'check_out_date' => date('Y-m-d', strtotime('+7 days'))
    ],
    [
        'id' => 2,
        'first_name' => 'Mike',
        'last_name' => 'Chen',
        'profile_photo' => '/images/avatars/mike.jpg', 
        'bio' => 'Digital nomad always looking for great coffee and co-working spaces',
        'interests' => 'Technology, Coffee, Beaches',
        'travel_style' => 'Digital Nomad',
        'languages' => 'English, Mandarin',
        'connection_count' => 8,
        'city' => 'Miami Beach',
        'room_name' => 'Deluxe Room',
        'check_in_date' => date('Y-m-d', strtotime('+1 day')),
        'check_out_date' => date('Y-m-d', strtotime('+5 days'))
    ]
];

if (!empty($userBookings)) {
    // Structure the data correctly for the frontend
    $travelersNearby = ['Miami Beach, Florida' => $sampleTravelers];
} else {
    $travelersNearby = ['Miami Beach, Florida' => $sampleTravelers];
}

// Sample travel feed data (replace with real database queries once social tables are set up)
$travelFeed = [
    [
        'id' => 1,
        'user_id' => 2,
        'first_name' => 'Sarah',
        'last_name' => 'Johnson',
        'profile_photo' => '/images/avatars/sarah.jpg',
        'hotel_name' => 'Ocean View Resort Miami',
        'city' => 'Miami Beach',
        'state' => 'Florida',
        'content' => 'Amazing sunrise view from my hotel room! Miami Beach is absolutely stunning 🌅 #MiamiVibes #BeachLife',
        'photo_url' => '/images/posts/miami-sunrise.jpg',
        'like_count' => 15,
        'comment_count' => 3,
        'user_liked' => 0,
        'post_type' => 'experience',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
    ],
    [
        'id' => 2,
        'user_id' => 3,
        'first_name' => 'Mike',
        'last_name' => 'Chen',
        'profile_photo' => '/images/avatars/mike.jpg',
        'hotel_name' => 'Ocean View Resort Miami',
        'city' => 'Miami Beach', 
        'state' => 'Florida',
        'content' => 'Found the perfect coffee shop near the hotel! Anyone want to join for a working session? ☕💻',
        'photo_url' => '/images/posts/coffee-shop.jpg',
        'like_count' => 8,
        'comment_count' => 2,
        'user_liked' => 1,
        'post_type' => 'looking_for_buddy',
        'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
    ]
];

// Sample traveler profile (replace with real database query once profile tables are set up)
$travelProfile = [
    'user_id' => $user['id'],
    'bio' => 'Passionate traveler and culture enthusiast',
    'interests' => 'Food, Art, History',
    'travel_style' => 'Cultural Explorer',
    'languages' => 'English, Spanish',
    'countries_visited' => 15,
    'aini_coins_earned' => 2500
];

// Handle form submissions
$message = '';
if ($_POST) {
    if (isset($_POST['create_post'])) {
        $content = trim($_POST['post_content']);
        $location_id = $_POST['location_id'] ?? null;
        $privacy = $_POST['privacy'] ?? 'public';
        $post_type = $_POST['post_type'] ?? 'general';
        
        if (!empty($content)) {
            $stmt = $connection->prepare("
                INSERT INTO travel_posts (user_id, content, location_id, privacy, post_type, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user['id'], $content, $location_id, $privacy, $post_type]);
            $message = "Travel post shared successfully! 🌟";
        }
    }
    
    if (isset($_POST['send_connection_request'])) {
        $friend_id = intval($_POST['friend_id']);
        $message_text = trim($_POST['connection_message']);
        
        // Check if connection already exists
        $stmt = $connection->prepare("
            SELECT id FROM travel_connections 
            WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
        ");
        $stmt->execute([$user['id'], $friend_id, $friend_id, $user['id']]);
        
        if (!$stmt->fetch()) {
            $stmt = $connection->prepare("
                INSERT INTO travel_connections (user_id, friend_id, status, message, created_at)
                VALUES (?, ?, 'pending', ?, NOW())
            ");
            $stmt->execute([$user['id'], $friend_id, $message_text]);
            $message = "Connection request sent! 🤝";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Social Network - AiNi Coin Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 300px 1fr 300px;
            gap: 2rem;
        }

        .sidebar {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .main-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .traveler-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }

        .traveler-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .traveler-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .traveler-info h4 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .traveler-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .interests {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .interest-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .connect-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .connect-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.4);
        }

        .post-composer {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .post-composer textarea {
            width: 100%;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            font-size: 1rem;
            resize: vertical;
            min-height: 100px;
        }

        .post-composer textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .post-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .post-controls {
            display: flex;
            gap: 1rem;
        }

        .post-controls select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .post-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .post-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .travel-post {
            border: 1px solid #eee;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            background: white;
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .post-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .post-author {
            flex: 1;
        }

        .post-author h4 {
            margin-bottom: 0.3rem;
            color: #333;
        }

        .post-meta {
            font-size: 0.9rem;
            color: #666;
        }

        .post-content {
            margin-bottom: 1rem;
            line-height: 1.6;
            color: #333;
        }

        .post-location {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .post-actions {
            display: flex;
            gap: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .post-action {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            cursor: pointer;
            transition: color 0.3s;
        }

        .post-action:hover {
            color: #667eea;
        }

        .post-action.liked {
            color: #e91e63;
        }

        .location-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .location-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.3s;
        }

        .location-item:hover {
            background: #f8f9fa;
        }

        .location-item h4 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .location-stats {
            font-size: 0.9rem;
            color: #666;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            
            .sidebar {
                order: 2;
            }
        }

        @media (max-width: 768px) {
            .post-options {
                flex-direction: column;
                gap: 1rem;
            }
            
            .post-controls {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🌍 Travel Social Network</div>
            <div class="nav-links">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="travel_social.php" class="active">🌍 Travel Network</a>
                <a href="wallet.php">🪙 AiNi Wallet</a>
                <a href="profile.php">👤 Profile</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Left Sidebar: Travelers Nearby -->
        <div class="sidebar">
            <div class="section-title">🗺️ Travelers Near You</div>
            
            <?php if (empty($travelersNearby)): ?>
                <p style="text-align: center; color: #666; padding: 2rem;">
                    📍 Book a trip to connect with fellow travelers in your destination!
                </p>
            <?php else: ?>
                <?php foreach ($travelersNearby as $location => $travelers): ?>
                    <h4 style="margin-bottom: 1rem; color: #667eea;">📍 <?php echo htmlspecialchars($location); ?></h4>
                    <?php foreach ($travelers as $traveler): ?>
                        <div class="traveler-card">
                            <div class="traveler-avatar">
                                <?php echo strtoupper(substr($traveler['first_name'], 0, 1)); ?>
                            </div>
                            <div class="traveler-info" style="flex: 1;">
                                <h4><?php echo htmlspecialchars($traveler['first_name'] . ' ' . $traveler['last_name']); ?></h4>
                                <div class="traveler-meta">
                                    🏨 <?php echo htmlspecialchars($traveler['room_name']); ?><br>
                                    📅 <?php echo date('M j', strtotime($traveler['check_in_date'])); ?> - <?php echo date('M j', strtotime($traveler['check_out_date'])); ?><br>
                                    🤝 <?php echo $traveler['connection_count']; ?> connections
                                </div>
                                <?php if ($traveler['interests']): ?>
                                    <div class="interests">
                                        <?php foreach (explode(',', $traveler['interests']) as $interest): ?>
                                            <span class="interest-tag"><?php echo htmlspecialchars(trim($interest)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <button class="connect-btn" onclick="connectWith(<?php echo $traveler['id']; ?>, '<?php echo htmlspecialchars($traveler['first_name']); ?>')">
                                    🤝 Connect
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Main Content: Travel Feed -->
        <div class="main-content">
            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($travelersNearby); ?></div>
                    <div class="stat-label">Destinations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo array_sum(array_map('count', $travelersNearby)); ?></div>
                    <div class="stat-label">Travelers Nearby</div>
                </div>
            </div>

            <!-- Post Composer -->
            <div class="post-composer">
                <form method="POST">
                    <textarea name="post_content" placeholder="Share your travel experience, ask for recommendations, or find travel buddies! 🌟" required></textarea>
                    
                    <div class="post-options">
                        <div class="post-controls">
                            <select name="post_type">
                                <option value="general">✍️ General Post</option>
                                <option value="recommendation">💡 Recommendation</option>
                                <option value="looking_for_buddy">👥 Looking for Travel Buddy</option>
                                <option value="experience">🌟 Experience Share</option>
                                <option value="question">❓ Question</option>
                            </select>
                            
                            <select name="location_id">
                                <option value="">📍 Select Location</option>
                                <?php foreach ($userBookings as $booking): ?>
                                    <option value="<?php echo $booking['id']; ?>">
                                        <?php echo htmlspecialchars($booking['hotel_name'] . ' - ' . $booking['city']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="privacy">
                                <option value="public">🌍 Public</option>
                                <option value="connections">🤝 Connections Only</option>
                                <option value="nearby">📍 Travelers Nearby</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="create_post" class="post-btn">📝 Share Post</button>
                    </div>
                </form>
            </div>

            <!-- Travel Feed -->
            <div class="travel-feed">
                <?php if (empty($travelFeed)): ?>
                    <div style="text-align: center; padding: 3rem; color: #666;">
                        <h3>🌟 Welcome to the Travel Social Network!</h3>
                        <p>Connect with fellow travelers, share experiences, and discover amazing places together.</p>
                        <p style="margin-top: 1rem;">Share your first post above to get started! ✈️</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($travelFeed as $post): ?>
                        <div class="travel-post">
                            <div class="post-header">
                                <div class="post-avatar">
                                    <?php echo strtoupper(substr($post['first_name'], 0, 1)); ?>
                                </div>
                                <div class="post-author">
                                    <h4><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></h4>
                                    <div class="post-meta">
                                        📅 <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?>
                                        <?php if (isset($post['post_type']) && $post['post_type'] !== 'general'): ?>
                                            • <?php 
                                            $types = [
                                                'recommendation' => '💡 Recommendation',
                                                'looking_for_buddy' => '👥 Looking for Buddy',
                                                'experience' => '🌟 Experience',
                                                'question' => '❓ Question'
                                            ];
                                            echo $types[$post['post_type']] ?? $post['post_type'];
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($post['hotel_name']): ?>
                                <div class="post-location">
                                    📍 <?php echo htmlspecialchars($post['hotel_name'] . ' - ' . $post['city'] . ', ' . $post['state']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-content">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            </div>
                            
                            <div class="post-actions">
                                <div class="post-action <?php echo $post['user_liked'] ? 'liked' : ''; ?>" onclick="likePost(<?php echo $post['id']; ?>)">
                                    ❤️ <?php echo $post['like_count']; ?> Likes
                                </div>
                                <div class="post-action" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                    💬 <?php echo $post['comment_count']; ?> Comments
                                </div>
                                <div class="post-action">
                                    🪙 Earn AiNi
                                </div>
                                <div class="post-action">
                                    🤝 Connect
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Sidebar: Trending & Profile -->
        <div class="sidebar">
            <div class="section-title">🔥 Trending Destinations</div>
            
            <div class="location-list">
                <div class="location-item">
                    <h4>🏖️ Miami Beach</h4>
                    <div class="location-stats">127 travelers • 89 posts this week</div>
                </div>
                <div class="location-item">
                    <h4>🗽 New York City</h4>
                    <div class="location-stats">203 travelers • 156 posts this week</div>
                </div>
                <div class="location-item">
                    <h4>🌴 Bali</h4>
                    <div class="location-stats">89 travelers • 67 posts this week</div>
                </div>
                <div class="location-item">
                    <h4>🏔️ Swiss Alps</h4>
                    <div class="location-stats">45 travelers • 34 posts this week</div>
                </div>
            </div>

            <div class="section-title" style="margin-top: 2rem;">🎯 Your Travel Goals</div>
            
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 10px;">
                <p style="margin-bottom: 1rem; color: #666;">Complete your traveler profile to connect with like-minded adventurers!</p>
                <a href="traveler_profile.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                    ✏️ Complete Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Connection Modal -->
    <div id="connectionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 2rem; border-radius: 15px; max-width: 400px; width: 90%;">
            <h3 id="connectionTitle" style="margin-bottom: 1rem;">🤝 Connect with Traveler</h3>
            <form method="POST">
                <input type="hidden" id="friendId" name="friend_id">
                <textarea name="connection_message" placeholder="Hi! I'd love to connect and share travel experiences..." style="width: 100%; height: 100px; padding: 1rem; border: 2px solid #eee; border-radius: 10px; margin-bottom: 1rem;"></textarea>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeConnectionModal()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 5px; cursor: pointer;">Cancel</button>
                    <button type="submit" name="send_connection_request" style="padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; cursor: pointer;">Send Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function connectWith(friendId, firstName) {
            document.getElementById('friendId').value = friendId;
            document.getElementById('connectionTitle').textContent = '🤝 Connect with ' + firstName;
            document.getElementById('connectionModal').style.display = 'flex';
        }

        function closeConnectionModal() {
            document.getElementById('connectionModal').style.display = 'none';
        }

        function likePost(postId) {
            // AJAX call to like/unlike post
            fetch('api/travel_social_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'like_post',
                    post_id: postId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Simple reload for now
                }
            });
        }

        function toggleComments(postId) {
            // Show/hide comments section
            alert('Comments feature coming soon! 💬');
        }

        // Close modal when clicking outside
        document.getElementById('connectionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeConnectionModal();
            }
        });
    </script>
</body>
</html>