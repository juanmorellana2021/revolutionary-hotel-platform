<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/hotelcoin_manager.php';
require_once 'includes/loyalty_manager.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$userManager = new UserManager();
if (!$userManager->isManager($_SESSION['user']['id'])) {
    header('Location: dashboard.php');
    exit;
}

$hotelCoinManager = new HotelCoinManager();
$loyaltyManager = new LoyaltyManager();
$database = new Database();
$connection = $database->getConnection();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_exchange_rate'])) {
        $usdRate = (float)$_POST['usd_rate'];
        $penRate = (float)$_POST['pen_rate'];
        
        if ($hotelCoinManager->updateExchangeRate($usdRate, $penRate)) {
            $message = "Exchange rate updated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to update exchange rate.";
            $messageType = "error";
        }
    }
    
    if (isset($_POST['award_bonus'])) {
        $username = trim($_POST['username']);
        $coinsAmount = (float)$_POST['coins_amount'];
        $pointsAmount = (float)$_POST['points_amount'];
        $description = trim($_POST['description']);
        
        // Get user by username
        $user = $userManager->getUserByUsername($username);
        if (!$user) {
            $message = "User '{$username}' not found.";
            $messageType = "error";
        } else {
            $success = true;
            
            if ($coinsAmount > 0) {
                $success = $success && $hotelCoinManager->addCoins($user['id'], $coinsAmount, 'admin', null, $description);
            }
            
            if ($pointsAmount > 0) {
                $success = $success && $loyaltyManager->addPoints($user['id'], $pointsAmount, 'admin', null, $description);
            }
            
            if ($success) {
                $message = "Bonus awarded successfully to {$username}!";
                $messageType = "success";
            } else {
                $message = "Failed to award bonus.";
                $messageType = "error";
            }
        }
    }
}

// Get system statistics
$stats = [];

// HotelCoin stats
$stmt = $connection->prepare("
    SELECT 
        COUNT(*) as total_wallets,
        SUM(balance) as total_circulation,
        SUM(total_earned) as total_earned,
        AVG(balance) as avg_balance
    FROM hotelcoin_wallets
");
$stmt->execute();
$stats['hotelcoin'] = $stmt->fetch();

// Loyalty stats
$stmt = $connection->prepare("
    SELECT 
        COUNT(*) as total_members,
        SUM(balance) as total_points,
        SUM(total_earned) as total_points_earned,
        COUNT(CASE WHEN membership_tier = 'Bronze' THEN 1 END) as bronze_members,
        COUNT(CASE WHEN membership_tier = 'Silver' THEN 1 END) as silver_members,
        COUNT(CASE WHEN membership_tier = 'Gold' THEN 1 END) as gold_members,
        COUNT(CASE WHEN membership_tier = 'Platinum' THEN 1 END) as platinum_members
    FROM loyalty_wallets
");
$stmt->execute();
$stats['loyalty'] = $stmt->fetch();

// Recent transactions
$stmt = $connection->prepare("
    SELECT ht.*, 
           CONCAT(u.first_name, ' ', u.last_name) as user_name,
           u.email as user_email
    FROM hotelcoin_transactions ht
    LEFT JOIN users u ON (ht.from_user_id = u.id OR ht.to_user_id = u.id)
    ORDER BY ht.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recentTransactions = $stmt->fetchAll();

$currentRate = $hotelCoinManager->getCurrentRate('USD');
$currentRatePEN = $hotelCoinManager->getCurrentRate('PEN');

$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelCoin & Loyalty Admin - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        .nav-links {
            margin-bottom: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            padding: 8px 15px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        .stat-card h3 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(0,0,0,0.05);
            border-radius: 8px;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        .stat-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .admin-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-hotelcoin {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #333;
        }

        .btn-hotelcoin:hover {
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .transaction-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }

        .transaction-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .tier-distribution {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 15px;
        }

        .tier-item {
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9em;
        }

        .tier-bronze { background: linear-gradient(135deg, #CD7F32, #B87333); color: white; }
        .tier-silver { background: linear-gradient(135deg, #C0C0C0, #A8A8A8); color: white; }
        .tier-gold { background: linear-gradient(135deg, #FFD700, #FFA500); color: white; }
        .tier-platinum { background: linear-gradient(135deg, #E5E4E2, #BCC6CC); color: #333; }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .tier-distribution {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="manager_dashboard.php">← Manager Dashboard</a>
            <a href="wallet.php">My Wallet</a>
            <a href="calendar_view.php">Calendar</a>
            <a href="room_management.php">Rooms</a>
        </div>

        <div class="header">
            <h1 style="color: #333; display: flex; align-items: center; gap: 15px;">
                🪙 HotelCoin & Loyalty System Admin
            </h1>
            <p style="color: #666; margin-top: 10px;">Manage your hotel's digital currency and loyalty program</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>🪙 HotelCoin Statistics</h3>
                <div class="stat-item">
                    <span class="stat-label">Total Wallets</span>
                    <span class="stat-value"><?php echo number_format($stats['hotelcoin']['total_wallets']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Coins in Circulation</span>
                    <span class="stat-value"><?php echo number_format($stats['hotelcoin']['total_circulation'], 4); ?> HC</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Coins Earned</span>
                    <span class="stat-value"><?php echo number_format($stats['hotelcoin']['total_earned'], 4); ?> HC</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Average Balance</span>
                    <span class="stat-value"><?php echo number_format($stats['hotelcoin']['avg_balance'], 4); ?> HC</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">USD Value</span>
                    <span class="stat-value">$<?php echo number_format($stats['hotelcoin']['total_circulation'] * $currentRate, 2); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <h3>⭐ Loyalty Program Statistics</h3>
                <div class="stat-item">
                    <span class="stat-label">Total Members</span>
                    <span class="stat-value"><?php echo number_format($stats['loyalty']['total_members']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Points in Circulation</span>
                    <span class="stat-value"><?php echo number_format($stats['loyalty']['total_points']); ?> pts</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Points Earned</span>
                    <span class="stat-value"><?php echo number_format($stats['loyalty']['total_points_earned']); ?> pts</span>
                </div>
                
                <div class="tier-distribution">
                    <div class="tier-item tier-bronze">
                        <strong><?php echo $stats['loyalty']['bronze_members']; ?></strong><br>Bronze
                    </div>
                    <div class="tier-item tier-silver">
                        <strong><?php echo $stats['loyalty']['silver_members']; ?></strong><br>Silver
                    </div>
                    <div class="tier-item tier-gold">
                        <strong><?php echo $stats['loyalty']['gold_members']; ?></strong><br>Gold
                    </div>
                    <div class="tier-item tier-platinum">
                        <strong><?php echo $stats['loyalty']['platinum_members']; ?></strong><br>Platinum
                    </div>
                </div>
            </div>
        </div>

        <!-- Exchange Rate Management -->
        <div class="admin-section">
            <div class="section-title">
                💱 Exchange Rate Management
            </div>
            
            <div style="background: rgba(0, 0, 0, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>Current Rates:</strong><br>
                1 HotelCoin = $<?php echo number_format($currentRate, 4); ?> USD<br>
                1 HotelCoin = S/<?php echo number_format($currentRatePEN, 4); ?> PEN
            </div>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>USD Rate (1 HC = X USD)</label>
                        <input type="number" name="usd_rate" step="0.0001" value="<?php echo $currentRate; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>PEN Rate (1 HC = X PEN)</label>
                        <input type="number" name="pen_rate" step="0.0001" value="<?php echo $currentRatePEN; ?>" required>
                    </div>
                </div>
                <button type="submit" name="update_exchange_rate" class="btn btn-hotelcoin">Update Exchange Rate</button>
            </form>
        </div>

        <!-- Award Bonus -->
        <div class="admin-section">
            <div class="section-title">
                🎁 Award Bonus
            </div>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required placeholder="Enter username">
                    </div>
                    <div class="form-group">
                        <label>HotelCoins Amount</label>
                        <input type="number" name="coins_amount" step="0.0001" min="0" placeholder="0.0000">
                    </div>
                    <div class="form-group">
                        <label>Loyalty Points Amount</label>
                        <input type="number" name="points_amount" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="Reason for bonus award" required>
                </div>
                <button type="submit" name="award_bonus" class="btn">Award Bonus</button>
            </form>
        </div>

        <!-- Recent Transactions -->
        <div class="admin-section">
            <div class="section-title">
                📊 Recent HotelCoin Transactions
            </div>
            
            <div class="transaction-list">
                <?php if (empty($recentTransactions)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No transactions yet.</p>
                <?php else: ?>
                    <?php foreach ($recentTransactions as $transaction): ?>
                        <div class="transaction-item">
                            <div>
                                <strong><?php echo ucfirst($transaction['transaction_type']); ?></strong>
                                <?php echo number_format($transaction['amount'], 4); ?> HC<br>
                                <small style="color: #666;">
                                    <?php echo htmlspecialchars($transaction['description'] ?? 'No description'); ?>
                                </small>
                            </div>
                            <div style="text-align: right; font-size: 0.9em; color: #666;">
                                <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?><br>
                                <span style="color: <?php echo $transaction['status'] === 'completed' ? '#27ae60' : '#e74c3c'; ?>;">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>