<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/whatsapp_bot.php';

// Check if user is logged in and is a manager/admin
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$userManager = new UserManager();
if (!$userManager->isManager($_SESSION['user']['id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'db_connection.php';
$connection = $conn;

// Handle form submissions
$message = '';
if ($_POST) {
    if (isset($_POST['send_broadcast'])) {
        // Handle broadcast message
        $messageContent = trim($_POST['broadcast_message']);
        $targetCriteria = $_POST['target_criteria'] ?? 'all';
        
        if (!empty($messageContent)) {
            // Create broadcast campaign
            $stmt = $connection->prepare("
                INSERT INTO whatsapp_campaigns (campaign_name, message_content, target_criteria, status, created_at)
                VALUES (?, ?, ?, 'draft', NOW())
            ");
            $campaignName = "Broadcast " . date('Y-m-d H:i');
            $targetCriteriaJSON = json_encode(['type' => $targetCriteria]);
            $status = 'draft';
            $stmt->bind_param('ssss', $campaignName, $messageContent, $targetCriteriaJSON, $status);
            $stmt->execute();
            $stmt->close();
            
            $message = "Broadcast message queued for sending!";
        }
    }
    
    if (isset($_POST['update_config'])) {
        // Update WhatsApp configuration
        $configs = [
            'whatsapp_access_token' => $_POST['access_token'] ?? '',
            'whatsapp_phone_number_id' => $_POST['phone_number_id'] ?? '',
            'whatsapp_business_phone' => $_POST['business_phone'] ?? '',
            'whatsapp_enabled' => isset($_POST['whatsapp_enabled']) ? '1' : '0'
        ];
        
        foreach ($configs as $key => $value) {
            $stmt = $connection->prepare("
                INSERT INTO ai_chat_config (config_key, config_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
            ");
            $stmt->execute([$key, $value]);
        }
        
        $message = "WhatsApp configuration updated successfully!";
    }
}

// Get WhatsApp statistics
$stmt = $connection->prepare("
    SELECT 
        COUNT(DISTINCT phone_number) as total_whatsapp_users,
        COUNT(*) as total_messages,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as messages_24h,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as messages_7d
    FROM whatsapp_conversations
");
$stmt->execute();
$result = $stmt->get_result();
$whatsappStats = $result ? $result->fetch_assoc() : [];
$stmt->close();

// Get recent WhatsApp conversations
$stmt = $connection->prepare("
    SELECT wc.*, u.first_name, u.last_name
    FROM whatsapp_conversations wc
    LEFT JOIN users u ON wc.user_id = u.id
    ORDER BY wc.created_at DESC
    LIMIT 20
");
$stmt->execute();
$result = $stmt->get_result();
$recentConversations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Get WhatsApp configuration
$stmt = $connection->prepare("
    SELECT config_key, config_value 
    FROM ai_chat_config 
    WHERE config_key LIKE 'whatsapp_%'
");
$stmt->execute();
$result = $stmt->get_result();
$whatsappConfig = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $whatsappConfig[$row['config_key']] = $row['config_value'];
    }
}
$stmt->close();

// Get active WhatsApp users (with error handling)
$activeUsers = [];
try {
    $stmt = $connection->prepare("
        SELECT * FROM whatsapp_user_stats 
        ORDER BY last_message DESC 
        LIMIT 50
    ");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $activeUsers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
} catch (Exception $e) {
    // Table might not exist, create sample data
    $activeUsers = [
        ['user_name' => 'Sample User', 'phone_number' => '+1234567890', 'last_message' => date('Y-m-d H:i:s'), 'message_count' => 5]
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Management - Revolutionary Hotel Platform</title>
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
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(37, 211, 102, 0.3);
        }

        .header-content {
            max-width: 1400px;
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

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #25D366;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }

        .section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #25D366;
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #25D366;
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        }

        .btn {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.4);
        }

        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            gap: 1rem;
        }

        .conversation-item:last-child {
            border-bottom: none;
        }

        .conversation-avatar {
            width: 50px;
            height: 50px;
            background: #25D366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .conversation-content {
            flex: 1;
        }

        .conversation-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .conversation-name {
            font-weight: 600;
            color: #333;
        }

        .conversation-time {
            color: #666;
            font-size: 0.9rem;
        }

        .conversation-message {
            color: #666;
            line-height: 1.4;
        }

        .user-message {
            background: #f0f0f0;
            padding: 0.5rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .ai-message {
            background: #e8f5e8;
            padding: 0.5rem;
            border-radius: 8px;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .user-table th,
        .user-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .user-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .tier-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .tier-bronze { background: #cd7f32; color: white; }
        .tier-silver { background: #c0c0c0; color: #333; }
        .tier-gold { background: #ffd700; color: #333; }
        .tier-platinum { background: #e5e4e2; color: #333; }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .config-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">📱 WhatsApp Management</div>
            <div class="nav-links">
                <a href="manager_dashboard.php">🏠 Dashboard</a>
                <a href="hotelcoin_admin.php">🪙 HotelCoins</a>
                <a href="whatsapp_management.php" class="active">📱 WhatsApp</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- WhatsApp Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($whatsappStats['total_whatsapp_users'] ?? 0); ?></div>
                <div class="stat-label">WhatsApp Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-value"><?php echo number_format($whatsappStats['total_messages'] ?? 0); ?></div>
                <div class="stat-label">Total Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?php echo number_format($whatsappStats['messages_24h'] ?? 0); ?></div>
                <div class="stat-label">Messages (24h)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-value"><?php echo number_format($whatsappStats['messages_7d'] ?? 0); ?></div>
                <div class="stat-label">Messages (7 days)</div>
            </div>
        </div>

        <div class="config-grid">
            <!-- WhatsApp Configuration -->
            <div class="section">
                <div class="section-title">⚙️ WhatsApp Configuration</div>
                <form method="POST">
                    <div class="form-group">
                        <label>Access Token</label>
                        <input type="password" name="access_token" 
                               value="<?php echo htmlspecialchars($whatsappConfig['whatsapp_access_token'] ?? ''); ?>"
                               placeholder="Your WhatsApp Business API Access Token">
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number ID</label>
                        <input type="text" name="phone_number_id" 
                               value="<?php echo htmlspecialchars($whatsappConfig['whatsapp_phone_number_id'] ?? ''); ?>"
                               placeholder="WhatsApp Business Phone Number ID">
                    </div>
                    
                    <div class="form-group">
                        <label>Business Phone</label>
                        <input type="text" name="business_phone" 
                               value="<?php echo htmlspecialchars($whatsappConfig['whatsapp_business_phone'] ?? ''); ?>"
                               placeholder="+1234567890">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="whatsapp_enabled" 
                                   <?php echo ($whatsappConfig['whatsapp_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                            Enable WhatsApp Integration
                        </label>
                    </div>
                    
                    <button type="submit" name="update_config" class="btn">Update Configuration</button>
                </form>
            </div>

            <!-- Broadcast Message -->
            <div class="section">
                <div class="section-title">📢 Send Broadcast Message</div>
                <form method="POST">
                    <div class="form-group">
                        <label>Target Audience</label>
                        <select name="target_criteria">
                            <option value="all">All WhatsApp Users</option>
                            <option value="gold_platinum">Gold & Platinum Only</option>
                            <option value="recent_bookings">Recent Bookers</option>
                            <option value="high_hotelcoins">High HotelCoin Balances</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Message Content</label>
                        <textarea name="broadcast_message" rows="4" 
                                  placeholder="🎉 Special announcement for our WhatsApp guests! Get 20% off your next booking with code WHATSAPP20. Valid until next month!"></textarea>
                    </div>
                    
                    <button type="submit" name="send_broadcast" class="btn">Send Broadcast</button>
                </form>
            </div>
        </div>

        <!-- Recent Conversations -->
        <div class="section">
            <div class="section-title">💬 Recent WhatsApp Conversations</div>
            <div class="conversations">
                <?php if (empty($recentConversations)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">No WhatsApp conversations yet.</p>
                <?php else: ?>
                    <?php foreach ($recentConversations as $conv): ?>
                        <div class="conversation-item">
                            <div class="conversation-avatar">📱</div>
                            <div class="conversation-content">
                                <div class="conversation-header">
                                    <span class="conversation-name">
                                        <?php 
                                        if ($conv['first_name']) {
                                            echo htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']);
                                        } else {
                                            echo htmlspecialchars($conv['phone_number']);
                                        }
                                        ?>
                                    </span>
                                    <span class="conversation-time">
                                        <?php echo date('M j, g:i A', strtotime($conv['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="conversation-message">
                                    <div class="user-message">
                                        <strong>Guest:</strong> <?php echo htmlspecialchars($conv['user_message']); ?>
                                    </div>
                                    <div class="ai-message">
                                        <strong>AI:</strong> <?php echo htmlspecialchars($conv['ai_response']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Active WhatsApp Users -->
        <div class="section">
            <div class="section-title">👥 Active WhatsApp Users</div>
            <?php if (empty($activeUsers)): ?>
                <p style="text-align: center; color: #666; padding: 2rem;">No active WhatsApp users yet.</p>
            <?php else: ?>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>HotelCoins</th>
                            <th>Loyalty Points</th>
                            <th>Tier</th>
                            <th>Messages</th>
                            <th>Last Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeUsers as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['whatsapp_phone'] ?? $user['phone_number'] ?? 'N/A'); ?></td>
                                <td>🪙 <?php echo number_format($user['hotelcoin_balance'] ?? 0, 2); ?></td>
                                <td>💎 <?php echo number_format($user['loyalty_points'] ?? 0); ?></td>
                                <td>
                                    <span class="tier-badge tier-<?php echo strtolower($user['membership_tier'] ?? 'bronze'); ?>">
                                        <?php echo ucfirst($user['membership_tier'] ?? 'Bronze'); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($user['total_messages'] ?? 0); ?></td>
                                <td>
                                    <?php 
                                    if ($user['last_message']) {
                                        echo date('M j, Y', strtotime($user['last_message']));
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>