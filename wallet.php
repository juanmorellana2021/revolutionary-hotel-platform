<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/hotelcoin_manager.php';
require_once 'includes/loyalty_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$hotelCoinManager = new HotelCoinManager();
$loyaltyManager = new LoyaltyManager();

// Initialize wallets if they don't exist
$hotelCoinManager->createWallet($userId);
$loyaltyManager->createLoyaltyWallet($userId);

// Get wallet information
$hotelCoinWallet = $hotelCoinManager->getWalletInfo($userId);
$loyaltyProfile = $loyaltyManager->getLoyaltyProfile($userId);
$currentRate = $hotelCoinManager->getCurrentRate('USD');
$currentRatePEN = $hotelCoinManager->getCurrentRate('PEN');

// Get transaction history
$hotelCoinHistory = $hotelCoinManager->getTransactionHistory($userId, 20);

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['transfer_coins'])) {
        $toUsername = trim($_POST['to_username']);
        $amount = (float)$_POST['amount'];
        $description = trim($_POST['description']);
        
        // Get recipient user ID
        $userManager = new UserManager();
        $recipient = $userManager->getUserByUsername($toUsername);
        
        if (!$recipient) {
            $message = "User '{$toUsername}' not found.";
            $messageType = "error";
        } elseif ($recipient['id'] == $userId) {
            $message = "Cannot transfer to yourself.";
            $messageType = "error";
        } elseif ($amount <= 0) {
            $message = "Transfer amount must be greater than 0.";
            $messageType = "error";
        } else {
            $result = $hotelCoinManager->transferCoins($userId, $recipient['id'], $amount, $description);
            if ($result['success']) {
                $message = "Successfully transferred {$amount} HotelCoins to {$toUsername}!";
                $messageType = "success";
                // Refresh wallet info
                $hotelCoinWallet = $hotelCoinManager->getWalletInfo($userId);
            } else {
                $message = "Transfer failed: " . $result['error'];
                $messageType = "error";
            }
        }
    }
    
    if (isset($_POST['redeem_points'])) {
        $redemptionType = $_POST['redemption_type'];
        $pointsRequired = (int)$_POST['points_required'];
        $description = $_POST['description'];
        
        if ($loyaltyManager->spendPoints($userId, $pointsRequired, $redemptionType, $description)) {
            $message = "Points redeemed successfully! Redemption code will be sent to your email.";
            $messageType = "success";
            // Refresh loyalty profile
            $loyaltyProfile = $loyaltyManager->getLoyaltyProfile($userId);
        } else {
            $message = "Failed to redeem points. Please check your balance.";
            $messageType = "error";
        }
    }
}

$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
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

        .header h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header h1::before {
            content: "💰";
            font-size: 1.2em;
        }

        .wallet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .wallet-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .wallet-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4);
        }

        .hotelcoin-card::before {
            background: linear-gradient(90deg, #FFD700, #FFA500, #FF8C00);
        }

        .loyalty-card::before {
            background: linear-gradient(90deg, #667eea, #764ba2, #6B73FF);
        }

        .wallet-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .wallet-title {
            font-size: 1.4em;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .balance {
            font-size: 2.2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .balance-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
        }

        .tier-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tier-bronze { background: linear-gradient(135deg, #CD7F32, #B87333); color: white; }
        .tier-silver { background: linear-gradient(135deg, #C0C0C0, #A8A8A8); color: white; }
        .tier-gold { background: linear-gradient(135deg, #FFD700, #FFA500); color: white; }
        .tier-platinum { background: linear-gradient(135deg, #E5E4E2, #BCC6CC); color: #333; }

        .wallet-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 10px;
            background: rgba(0,0,0,0.05);
            border-radius: 10px;
        }

        .stat-value {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
        }

        .stat-label {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }

        .action-section {
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

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.8em;
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

        .transaction-history {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }

        .transaction-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .transaction-info {
            flex: 1;
        }

        .transaction-amount {
            font-weight: 600;
            font-size: 1.1em;
        }

        .transaction-earn { color: #27ae60; }
        .transaction-spend { color: #e74c3c; }
        .transaction-transfer { color: #3498db; }

        .transaction-date {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
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

        .exchange-rate {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #666;
        }

        .redemption-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .redemption-option {
            background: rgba(0, 0, 0, 0.05);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
        }

        .redemption-option:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .redemption-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .redemption-points {
            font-size: 1.2em;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 8px;
        }

        .redemption-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .redemption-value {
            font-size: 0.9em;
            color: #666;
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

        @media (max-width: 768px) {
            .wallet-grid {
                grid-template-columns: 1fr;
            }
            
            .wallet-stats {
                grid-template-columns: 1fr;
            }
            
            .redemption-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="calendar_view.php">Calendar</a>
            <a href="room_management.php">Rooms</a>
        </div>

        <div class="header">
            <h1>My Digital Wallet</h1>
            <p>Manage your HotelCoins and Loyalty Points</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="wallet-grid">
            <!-- HotelCoin Wallet -->
            <div class="wallet-card hotelcoin-card">
                <div class="wallet-header">
                    <div class="wallet-title">
                        🪙 HotelCoin Wallet
                    </div>
                </div>
                
                <div class="balance"><?php echo number_format($hotelCoinWallet['balance'], 4); ?> HC</div>
                <div class="balance-label">Digital Currency Balance</div>
                
                <div class="exchange-rate">
                    <strong>Current Rate:</strong><br>
                    1 HC = $<?php echo number_format($currentRate, 4); ?> USD<br>
                    1 HC = S/<?php echo number_format($currentRatePEN, 4); ?> PEN
                </div>
                
                <div class="wallet-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($hotelCoinWallet['total_earned'], 2); ?></div>
                        <div class="stat-label">Total Earned</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($hotelCoinWallet['total_spent'], 2); ?></div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                </div>
                
                <div style="margin-top: 15px; font-size: 0.8em; color: #666;">
                    <strong>Wallet Address:</strong><br>
                    <code style="word-break: break-all;"><?php echo $hotelCoinWallet['wallet_address']; ?></code>
                </div>
            </div>

            <!-- Loyalty Points Wallet -->
            <div class="wallet-card loyalty-card">
                <div class="wallet-header">
                    <div class="wallet-title">
                        ⭐ Loyalty Points
                    </div>
                    <div class="tier-badge tier-<?php echo strtolower($loyaltyProfile['tier']); ?>">
                        <?php echo $loyaltyProfile['tier']; ?>
                    </div>
                </div>
                
                <div class="balance"><?php echo number_format($loyaltyProfile['balance']); ?> pts</div>
                <div class="balance-label">Available Points (<?php echo $loyaltyProfile['tier_multiplier']; ?>x multiplier)</div>
                
                <?php if ($loyaltyProfile['next_tier']): ?>
                    <div style="background: rgba(0,0,0,0.1); padding: 10px; border-radius: 8px; margin: 15px 0;">
                        <strong><?php echo $loyaltyProfile['points_to_next_tier']; ?> points</strong> to reach <strong><?php echo $loyaltyProfile['next_tier']; ?></strong> tier
                    </div>
                <?php endif; ?>
                
                <div class="wallet-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($loyaltyProfile['total_earned']); ?></div>
                        <div class="stat-label">Total Earned</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($loyaltyProfile['total_spent']); ?></div>
                        <div class="stat-label">Total Redeemed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer HotelCoins -->
        <div class="action-section">
            <div class="section-title">
                💸 Transfer HotelCoins
            </div>
            
            <form method="POST">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label>Recipient (Email or Name)</label>
                        <input type="text" name="to_username" required placeholder="Enter email or first name">
                        <small style="color: #666; font-size: 0.8em;">Enter recipient's email or first name</small>
                    </div>
                    <div class="form-group">
                        <label>Amount (HC)</label>
                        <input type="number" name="amount" step="0.0001" min="0.0001" max="<?php echo $hotelCoinWallet['balance']; ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Message (Optional)</label>
                    <input type="text" name="description" placeholder="Add a note for the transfer">
                </div>
                <button type="submit" name="transfer_coins" class="btn btn-hotelcoin">Transfer HotelCoins</button>
            </form>
        </div>

        <!-- Redeem Loyalty Points -->
        <div class="action-section">
            <div class="section-title">
                🎁 Redeem Loyalty Points
            </div>
            
            <div class="redemption-grid">
                <?php foreach ($loyaltyProfile['redemption_options'] as $option): ?>
                    <div class="redemption-option <?php echo !$option['available'] ? 'disabled' : ''; ?>" 
                         onclick="<?php echo $option['available'] ? "selectRedemption('{$option['type']}', {$option['points_required']}, '{$option['name']}')" : ''; ?>">
                        <div class="redemption-points"><?php echo number_format($option['points_required']); ?> pts</div>
                        <div class="redemption-name"><?php echo $option['name']; ?></div>
                        <div class="redemption-value"><?php echo $option['value']; ?></div>
                        <?php if (!$option['available']): ?>
                            <div style="color: #e74c3c; font-size: 0.8em; margin-top: 5px;">Insufficient Points</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" id="redemptionForm" style="display: none; margin-top: 20px;">
                <input type="hidden" name="redemption_type" id="redemption_type">
                <input type="hidden" name="points_required" id="points_required">
                <input type="hidden" name="description" id="redemption_description">
                <button type="submit" name="redeem_points" class="btn">Confirm Redemption</button>
                <button type="button" onclick="cancelRedemption()" class="btn" style="background: #666; margin-left: 10px;">Cancel</button>
            </form>
        </div>

        <!-- Transaction History -->
        <div class="transaction-history">
            <div class="section-title">
                📊 Recent HotelCoin Transactions
            </div>
            
            <?php if (empty($hotelCoinHistory)): ?>
                <p style="text-align: center; color: #666; padding: 20px;">No transactions yet. Start earning HotelCoins by making bookings!</p>
            <?php else: ?>
                <?php foreach ($hotelCoinHistory as $transaction): ?>
                    <div class="transaction-item">
                        <div class="transaction-info">
                            <div class="transaction-amount transaction-<?php echo $transaction['transaction_type']; ?>">
                                <?php 
                                $prefix = $transaction['transaction_type'] === 'earn' ? '+' : '-';
                                echo $prefix . number_format($transaction['amount'], 4) . ' HC';
                                ?>
                            </div>
                            <div style="font-size: 0.9em; color: #333; margin-top: 2px;">
                                <?php echo htmlspecialchars($transaction['description']); ?>
                            </div>
                            <?php if ($transaction['from_username'] || $transaction['to_username']): ?>
                                <div style="font-size: 0.8em; color: #666; margin-top: 2px;">
                                    <?php 
                                    if ($transaction['from_username'] && $transaction['from_username'] !== $_SESSION['user']['username']) {
                                        echo "From: " . htmlspecialchars($transaction['from_username']);
                                    }
                                    if ($transaction['to_username'] && $transaction['to_username'] !== $_SESSION['user']['username']) {
                                        echo "To: " . htmlspecialchars($transaction['to_username']);
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                            <div class="transaction-date">
                                <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?>
                            </div>
                        </div>
                        <div style="text-align: right; font-size: 0.8em; color: #666;">
                            $<?php echo number_format($transaction['usd_equivalent'], 2); ?> USD<br>
                            S/<?php echo number_format($transaction['pen_equivalent'], 2); ?> PEN
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function selectRedemption(type, points, name) {
            document.getElementById('redemption_type').value = type;
            document.getElementById('points_required').value = points;
            document.getElementById('redemption_description').value = name + ' redemption';
            document.getElementById('redemptionForm').style.display = 'block';
            
            // Highlight selected option
            document.querySelectorAll('.redemption-option').forEach(opt => {
                opt.style.border = '2px solid transparent';
            });
            event.target.closest('.redemption-option').style.border = '2px solid #667eea';
        }

        function cancelRedemption() {
            document.getElementById('redemptionForm').style.display = 'none';
            document.querySelectorAll('.redemption-option').forEach(opt => {
                opt.style.border = '2px solid transparent';
            });
        }
    </script>
</body>
</html>