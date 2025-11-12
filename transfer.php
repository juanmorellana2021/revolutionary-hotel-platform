<?php
session_start();
require_once 'db_connection_pdo.php';
require_once 'AiniCoinSystem.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$coinSystem = new AiniCoinSystem($pdo);

// Get user information
try {
    $stmt = $pdo->prepare("SELECT * FROM ainitravel_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: login.php');
        exit();
    }
    
    // Get transfer limits
    $transferLimits = $coinSystem->checkTransferLimits($user_id);
    
} catch (PDOException $e) {
    error_log("Transfer page error: " . $e->getMessage());
    $error = "Error loading transfer data.";
}

// Handle transfer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_coins'])) {
    $recipient_email = trim($_POST['recipient_email']);
    $amount = floatval($_POST['amount']);
    $coin_type = $_POST['coin_type'] ?? 'rewards'; // 'rewards' or 'crypto'
    $note = trim($_POST['note'] ?? '');
    
    try {
        // Find recipient by email
        $stmt = $pdo->prepare("SELECT id, full_name, email FROM ainitravel_users WHERE email = ? AND id != ?");
        $stmt->execute([$recipient_email, $user_id]);
        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Determine which balance to check
        $balance_column = ($coin_type === 'crypto') ? 'aini_crypto' : 'aini_rewards';
        $current_balance = floatval($user[$balance_column]);
        $coin_label = ($coin_type === 'crypto') ? 'AiNi Crypto' : 'AiNi Rewards';
        
        if (!$recipient) {
            $error = "Recipient not found with email: " . htmlspecialchars($recipient_email);
        } elseif ($amount <= 0) {
            $error = "Transfer amount must be greater than 0.";
        } elseif ($amount > $current_balance) {
            $error = "Insufficient balance. You have " . number_format($current_balance, 2) . " " . $coin_label . ".";
        } else {
            // Check transfer limits
            $limitsCheck = $coinSystem->checkTransferLimits($user_id, $amount);
            
            if (!$limitsCheck['can_transfer']) {
                $error = $limitsCheck['message'];
            } else {
                // Begin transaction
                $pdo->beginTransaction();
                
                try {
                    // Deduct from sender
                    $stmt = $pdo->prepare("
                        UPDATE ainitravel_users 
                        SET $balance_column = $balance_column - ? 
                        WHERE id = ? AND $balance_column >= ?
                    ");
                    $stmt->execute([$amount, $user_id, $amount]);
                    
                    if ($stmt->rowCount() === 0) {
                        throw new Exception("Insufficient balance or concurrent modification");
                    }
                    
                    // Add to recipient
                    $stmt = $pdo->prepare("
                        UPDATE ainitravel_users 
                        SET $balance_column = $balance_column + ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$amount, $recipient['id']]);
                    
                    // Record transaction
                    $transaction_hash = hash('sha256', $user_id . $recipient['id'] . $amount . $coin_type . microtime(true));
                    $stmt = $pdo->prepare("
                        INSERT INTO aini_coin_transactions 
                        (from_user_id, to_user_id, amount, transaction_type, transaction_hash, description, coin_type)
                        VALUES (?, ?, ?, 'transfer', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $user_id,
                        $recipient['id'],
                        $amount,
                        $transaction_hash,
                        $note ?: "Transfer to " . $recipient['full_name'],
                        $coin_type
                    ]);
                    
                    $pdo->commit();
                    
                    $success = "Successfully transferred " . number_format($amount, 2) . " " . $coin_label . " to " . htmlspecialchars($recipient['full_name']) . "!";
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM ainitravel_users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Refresh transfer limits
                    $transferLimits = $coinSystem->checkTransferLimits($user_id);
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Transfer error: " . $e->getMessage());
        $error = "Transfer failed: " . $e->getMessage();
    } catch (Exception $e) {
        error_log("Transfer error: " . $e->getMessage());
        $error = "Transfer failed: " . $e->getMessage();
    }
}

// Get recent transfers
try {
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            sender.full_name as sender_name,
            sender.email as sender_email,
            recipient.full_name as recipient_name,
            recipient.email as recipient_email
        FROM aini_coin_transactions t
        LEFT JOIN ainitravel_users sender ON t.from_user_id = sender.id
        LEFT JOIN ainitravel_users recipient ON t.to_user_id = recipient.id
        WHERE t.transaction_type = 'transfer' 
        AND (t.from_user_id = ? OR t.to_user_id = ?)
        ORDER BY t.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id, $user_id]);
    $recentTransfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Recent transfers error: " . $e->getMessage());
    $recentTransfers = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $user['preferred_language'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Coins - AiNi Travel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .pulse-animation {
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Navigation Header -->
    <nav class="gradient-bg shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex-shrink-0 flex items-center">
                    <a href="public_booking.php" class="text-white font-bold text-2xl">
                        <i class="fas fa-umbrella-beach mr-2"></i>AiNi Travel
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="public_booking.php" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-hotel mr-1"></i> Experiences
                    </a>
                    <a href="travel_social.php" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-users mr-1"></i> Social
                    </a>
                    <a href="wallet.php" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-coins mr-1"></i> Coins
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="text-white hover:text-gray-200 transition">
                            <i class="fas fa-globe"></i>
                            <span class="ml-1"><?php echo strtoupper($user['preferred_language'] ?? 'EN'); ?></span>
                        </button>
                    </div>

                    <div class="relative">
                        <button class="text-white hover:text-gray-200 relative">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                        </button>
                    </div>

                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-white hover:text-gray-200">
                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-purple-600"></i>
                            </div>
                            <span class="hidden md:inline"><?php echo htmlspecialchars($user['full_name'] ?? 'Guest'); ?></span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-purple-50 rounded-t-lg">
                                <i class="fas fa-user mr-2"></i> My Profile
                            </a>
                            <a href="wallet.php" class="block px-4 py-2 text-gray-800 hover:bg-purple-50">
                                <i class="fas fa-wallet mr-2"></i> My Wallet
                            </a>
                            <a href="my_bookings.php" class="block px-4 py-2 text-gray-800 hover:bg-purple-50">
                                <i class="fas fa-calendar-check mr-2"></i> My Bookings
                            </a>
                            <hr class="my-1">
                            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50 rounded-b-lg">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Back Button -->
        <div class="mb-6">
            <a href="wallet.php" class="text-purple-600 hover:text-purple-700 font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>Back to Wallet
            </a>
        </div>

        <!-- Messages -->
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">
                <i class="fas fa-paper-plane text-purple-600 mr-3"></i>Send AiNi Coins
            </h1>
            <p class="text-gray-600">Transfer coins to friends and family instantly</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Transfer Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Transfer Details</h2>
                    
                    <!-- Current Balance Display - Dual Coins -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <!-- AiNi Rewards -->
                        <div class="bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-lg p-4 cursor-pointer hover:shadow-lg transition" onclick="selectCoinType('rewards')">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs opacity-75 mb-1">💎 AiNi Rewards</p>
                                    <p class="text-2xl font-bold"><?php echo number_format($user['aini_rewards'], 2); ?></p>
                                    <p class="text-xs opacity-75">≈ $<?php echo number_format($user['aini_rewards'], 2); ?></p>
                                </div>
                                <i class="fas fa-gem text-3xl opacity-30"></i>
                            </div>
                        </div>
                        
                        <!-- AiNi Crypto -->
                        <div class="bg-gradient-to-br from-purple-500 to-purple-700 text-white rounded-lg p-4 cursor-pointer hover:shadow-lg transition" onclick="selectCoinType('crypto')">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs opacity-75 mb-1">🚀 AiNi Crypto</p>
                                    <p class="text-2xl font-bold"><?php echo number_format($user['aini_crypto'], 2); ?></p>
                                    <?php
                                    // Get crypto price
                                    $stmt = $pdo->query("SELECT price_usd FROM aini_crypto_market_prices ORDER BY id DESC LIMIT 1");
                                    $crypto_price = $stmt->fetchColumn() ?: 1.00;
                                    ?>
                                    <p class="text-xs opacity-75">≈ $<?php echo number_format($user['aini_crypto'] * $crypto_price, 2); ?></p>
                                </div>
                                <i class="fas fa-rocket text-3xl opacity-30"></i>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="transfer.php" id="transferForm">
                        <!-- Coin Type Selection -->
                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-layer-group mr-2 text-purple-600"></i>Select Coin Type
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="relative">
                                    <input type="radio" name="coin_type" value="rewards" checked onchange="updateCoinTypeDisplay()" class="peer sr-only">
                                    <div class="border-2 border-gray-300 rounded-lg p-4 cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                        <div class="flex items-center">
                                            <i class="fas fa-gem text-blue-500 text-xl mr-2"></i>
                                            <div>
                                                <p class="font-semibold text-gray-800">AiNi Rewards</p>
                                                <p class="text-xs text-gray-500">Stable value</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                <label class="relative">
                                    <input type="radio" name="coin_type" value="crypto" onchange="updateCoinTypeDisplay()" class="peer sr-only">
                                    <div class="border-2 border-gray-300 rounded-lg p-4 cursor-pointer peer-checked:border-purple-500 peer-checked:bg-purple-50 transition">
                                        <div class="flex items-center">
                                            <i class="fas fa-rocket text-purple-500 text-xl mr-2"></i>
                                            <div>
                                                <p class="font-semibold text-gray-800">AiNi Crypto</p>
                                                <p class="text-xs text-gray-500">Market price</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Recipient Email -->
                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-envelope mr-2 text-purple-600"></i>Recipient Email
                            </label>
                            <input type="email" name="recipient_email" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="friend@example.com">
                            <p class="text-sm text-gray-500 mt-2">Enter the email address of the AiNi Travel user</p>
                        </div>

                        <!-- Amount -->
                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-coins mr-2 text-purple-600"></i>Amount (AiNi Coins)
                            </label>
                            <input type="number" name="amount" step="0.01" min="<?php echo $transferLimits['min_transfer']; ?>" 
                                max="<?php echo min($user['aini_coins'], $transferLimits['daily_remaining']); ?>" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="0.00"
                                oninput="updateTransferPreview(this.value)">
                            <div class="flex justify-between mt-2 text-sm text-gray-500">
                                <span>Min: <?php echo number_format($transferLimits['min_transfer'], 2); ?> coins</span>
                                <span>Max today: <?php echo number_format($transferLimits['daily_remaining'], 2); ?> coins</span>
                            </div>
                        </div>

                        <!-- Quick Amount Buttons -->
                        <div class="mb-6">
                            <p class="text-sm text-gray-600 mb-2">Quick amounts:</p>
                            <div class="grid grid-cols-4 gap-2">
                                <button type="button" onclick="setAmount(10)" class="bg-purple-100 hover:bg-purple-200 text-purple-700 py-2 rounded-lg font-semibold transition">10</button>
                                <button type="button" onclick="setAmount(25)" class="bg-purple-100 hover:bg-purple-200 text-purple-700 py-2 rounded-lg font-semibold transition">25</button>
                                <button type="button" onclick="setAmount(50)" class="bg-purple-100 hover:bg-purple-200 text-purple-700 py-2 rounded-lg font-semibold transition">50</button>
                                <button type="button" onclick="setAmount(100)" class="bg-purple-100 hover:bg-purple-200 text-purple-700 py-2 rounded-lg font-semibold transition">100</button>
                            </div>
                        </div>

                        <!-- Note -->
                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-comment mr-2 text-purple-600"></i>Note (Optional)
                            </label>
                            <textarea name="note" rows="3" maxlength="200"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="Add a message for the recipient..."></textarea>
                            <p class="text-sm text-gray-500 mt-2">Maximum 200 characters</p>
                        </div>

                        <!-- Transfer Preview -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6" id="transferPreview" style="display: none;">
                            <h3 class="font-semibold text-gray-700 mb-3">Transfer Summary</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Amount:</span>
                                    <span class="font-semibold" id="previewAmount">0.00 coins</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">USD Value:</span>
                                    <span class="font-semibold" id="previewUSD">$0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Fee:</span>
                                    <span class="font-semibold text-green-600">FREE</span>
                                </div>
                                <hr class="my-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-700 font-semibold">Balance After:</span>
                                    <span class="font-bold text-purple-600" id="previewBalance"><?php echo number_format($user['aini_coins'], 2); ?> coins</span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" name="transfer_coins" 
                            class="w-full bg-gradient-to-r from-purple-600 to-purple-700 text-white py-4 rounded-lg font-bold text-lg hover:from-purple-700 hover:to-purple-800 transition shadow-lg">
                            <i class="fas fa-paper-plane mr-2"></i>Send Coins
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sidebar: Transfer Limits & Recent Transfers -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Transfer Limits -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-shield-alt text-purple-600 mr-2"></i>Transfer Limits
                    </h3>
                    
                    <?php if (!$transferLimits['can_transfer']): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-yellow-800">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <?php echo htmlspecialchars($transferLimits['message']); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">Daily Limit:</span>
                            <span class="font-semibold"><?php echo number_format($transferLimits['daily_limit'], 0); ?> coins</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">Used Today:</span>
                            <span class="font-semibold text-orange-600"><?php echo number_format($transferLimits['daily_used'], 2); ?> coins</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">Remaining:</span>
                            <span class="font-semibold text-green-600"><?php echo number_format($transferLimits['daily_remaining'], 2); ?> coins</span>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-3">
                            <div class="bg-gray-200 rounded-full h-2">
                                <?php 
                                $usagePercent = ($transferLimits['daily_used'] / $transferLimits['daily_limit']) * 100;
                                ?>
                                <div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo min($usagePercent, 100); ?>%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 text-center">
                                <?php echo number_format($usagePercent, 1); ?>% of daily limit used
                            </p>
                        </div>

                        <hr class="my-3">
                        
                        <div class="text-sm text-gray-600">
                            <p class="mb-2"><i class="fas fa-info-circle text-purple-600 mr-2"></i>Transfer Info:</p>
                            <ul class="list-disc list-inside space-y-1 text-xs">
                                <li>Min: <?php echo $transferLimits['min_transfer']; ?> coins per transfer</li>
                                <li>Max: <?php echo number_format($transferLimits['max_transfer'], 0); ?> coins per transfer</li>
                                <li>Account age: <?php echo $transferLimits['account_age_days']; ?> days</li>
                                <li>No transfer fees!</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Recent Transfers -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-history text-purple-600 mr-2"></i>Recent Transfers
                    </h3>
                    
                    <?php if (empty($recentTransfers)): ?>
                        <p class="text-gray-500 text-sm text-center py-4">No transfers yet</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach (array_slice($recentTransfers, 0, 5) as $transfer): ?>
                                <?php
                                $isSent = ($transfer['from_user_id'] == $user_id);
                                $otherUser = $isSent ? $transfer['recipient_name'] : $transfer['sender_name'];
                                ?>
                                <div class="border-b border-gray-100 pb-3 last:border-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-semibold <?php echo $isSent ? 'text-red-600' : 'text-green-600'; ?>">
                                            <?php echo $isSent ? '-' : '+'; ?><?php echo number_format(abs($transfer['amount']), 2); ?>
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            <?php echo date('M d', strtotime($transfer['created_at'])); ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600">
                                        <?php echo $isSent ? 'To: ' : 'From: '; ?>
                                        <?php echo htmlspecialchars($otherUser); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </div>

    <!-- Footer -->
    <footer class="gradient-bg text-white mt-16 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">AiNi Travel</h3>
                    <p class="text-sm opacity-75">Your trusted travel companion for unforgettable experiences.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="public_booking.php" class="hover:text-gray-200">Book Experience</a></li>
                        <li><a href="wallet.php" class="hover:text-gray-200">My Wallet</a></li>
                        <li><a href="rewards.php" class="hover:text-gray-200">Rewards</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-gray-200">Help Center</a></li>
                        <li><a href="#" class="hover:text-gray-200">Contact Us</a></li>
                        <li><a href="#" class="hover:text-gray-200">Terms & Conditions</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Follow Us</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="hover:text-gray-200"><i class="fab fa-facebook text-xl"></i></a>
                        <a href="#" class="hover:text-gray-200"><i class="fab fa-instagram text-xl"></i></a>
                        <a href="#" class="hover:text-gray-200"><i class="fab fa-twitter text-xl"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-white border-opacity-20 mt-8 pt-8 text-center text-sm opacity-75">
                <p>&copy; <?php echo date('Y'); ?> AiNi Travel. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        const balances = {
            rewards: <?php echo $user['aini_rewards']; ?>,
            crypto: <?php echo $user['aini_crypto']; ?>
        };
        const cryptoPrice = <?php echo $crypto_price; ?>;
        
        let currentCoinType = 'rewards';
        
        function selectCoinType(type) {
            currentCoinType = type;
            const radio = document.querySelector(`input[name="coin_type"][value="${type}"]`);
            if (radio) {
                radio.checked = true;
                updateCoinTypeDisplay();
            }
        }
        
        function updateCoinTypeDisplay() {
            const selectedType = document.querySelector('input[name="coin_type"]:checked').value;
            currentCoinType = selectedType;
            
            // Update max amount
            const amountInput = document.querySelector('input[name="amount"]');
            const maxAmount = Math.min(balances[selectedType], <?php echo $transferLimits['daily_remaining']; ?>);
            amountInput.max = maxAmount;
            
            // Update quick buttons
            updateTransferPreview(amountInput.value);
        }
        
        function setAmount(amount) {
            const input = document.querySelector('input[name="amount"]');
            input.value = amount;
            updateTransferPreview(amount);
        }
        
        function updateTransferPreview(amount) {
            const preview = document.getElementById('transferPreview');
            amount = parseFloat(amount) || 0;
            
            if (amount > 0) {
                const selectedType = document.querySelector('input[name="coin_type"]:checked').value;
                const coinLabel = selectedType === 'crypto' ? 'AiNi Crypto' : 'AiNi Rewards';
                const currentBalance = balances[selectedType];
                const usdValue = selectedType === 'crypto' ? (amount * cryptoPrice) : amount;
                
                preview.style.display = 'block';
                document.getElementById('previewAmount').textContent = amount.toFixed(2) + ' ' + coinLabel;
                document.getElementById('previewUSD').textContent = '$' + usdValue.toFixed(2);
                document.getElementById('previewBalance').textContent = (currentBalance - amount).toFixed(2) + ' ' + coinLabel;
            } else {
                preview.style.display = 'none';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCoinTypeDisplay();
        });
    </script>

</body>
</html>
