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

// Get user information and coin stats
try {
    $stmt = $pdo->prepare("SELECT * FROM ainitravel_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: login.php');
        exit();
    }
    
    // Get coin statistics
    $coinStats = $coinSystem->getUserCoinStats($user_id);
    
    // Get transaction history (last 20 transactions)
    $transactionHistory = $coinSystem->getTransactionHistory($user_id, 20);
    
    // Get exchange rates
    $stmt = $pdo->query("SELECT * FROM aini_coin_exchange_rates WHERE is_active = 1 ORDER BY currency_code");
    $exchangeRates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Wallet error: " . $e->getMessage());
    $error = "Error loading wallet data.";
}

// Handle coin purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_coins'])) {
    $amount_usd = floatval($_POST['amount_usd']);
    $currency = $_POST['currency'] ?? 'USD';
    
    if ($amount_usd >= 10) {
        // In production, this would integrate with payment gateway
        // For now, we'll simulate a successful purchase
        try {
            // Get exchange rate
            $stmt = $pdo->prepare("SELECT coins_per_unit FROM aini_coin_exchange_rates WHERE currency_code = ? AND is_active = 1");
            $stmt->execute([$currency]);
            $rate = $stmt->fetchColumn();
            
            if ($rate) {
                $coins_to_add = $amount_usd * $rate;
                $fee_percentage = 2.5; // From config
                $fee = ($coins_to_add * $fee_percentage) / 100;
                $net_coins = $coins_to_add - $fee;
                
                // Record purchase
                $stmt = $pdo->prepare("
                    INSERT INTO aini_coin_purchases 
                    (user_id, amount_paid, currency_code, exchange_rate, coins_purchased, platform_fee, net_coins, payment_method, payment_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'credit_card', 'completed')
                ");
                $stmt->execute([$user_id, $amount_usd, $currency, $rate, $coins_to_add, $fee, $net_coins]);
                
                // Record transaction
                $coinSystem->recordTransaction(
                    $user_id,
                    $net_coins,
                    'purchase',
                    null,
                    "Purchased coins: $amount_usd $currency",
                    ['currency' => $currency, 'exchange_rate' => $rate, 'fee' => $fee]
                );
                
                $success = "Successfully purchased " . number_format($net_coins, 2) . " AiNi Coins!";
                
                // Refresh user data
                header('Location: wallet.php?success=' . urlencode($success));
                exit();
            }
        } catch (PDOException $e) {
            error_log("Purchase error: " . $e->getMessage());
            $error = "Purchase failed. Please try again.";
        }
    } else {
        $error = "Minimum purchase is $10 USD.";
    }
}

$success_message = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $user['preferred_language'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet - AiNi Travel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .coin-animation {
            animation: coinFloat 3s ease-in-out infinite;
        }
        @keyframes coinFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .transaction-row:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Navigation Header (Same as public_booking.php) -->
    <nav class="gradient-bg shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="public_booking.php" class="text-white font-bold text-2xl">
                        <i class="fas fa-umbrella-beach mr-2"></i>AiNi Travel
                    </a>
                </div>

                <!-- Main Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="public_booking.php" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-hotel mr-1"></i> Experiences
                    </a>
                    <a href="travel_social.php" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-users mr-1"></i> Social
                    </a>
                    <a href="wallet.php" class="text-white hover:text-gray-200 font-semibold border-b-2 border-white transition">
                        <i class="fas fa-coins mr-1"></i> Coins
                    </a>
                </div>

                <!-- Right Side Icons -->
                <div class="flex items-center space-x-4">
                    <!-- Language Selector -->
                    <div class="relative">
                        <button class="text-white hover:text-gray-200 transition">
                            <i class="fas fa-globe"></i>
                            <span class="ml-1"><?php echo strtoupper($user['preferred_language'] ?? 'EN'); ?></span>
                        </button>
                    </div>

                    <!-- Notifications -->
                    <div class="relative">
                        <button class="text-white hover:text-gray-200 relative">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                        </button>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-white hover:text-gray-200">
                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-purple-600"></i>
                            </div>
                            <span class="hidden md:inline"><?php echo htmlspecialchars($user['full_name'] ?? 'Guest'); ?></span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-purple-50 rounded-t-lg">
                                <i class="fas fa-user mr-2"></i> My Profile
                            </a>
                            <a href="wallet.php" class="block px-4 py-2 text-gray-800 hover:bg-purple-50 bg-purple-50 font-semibold">
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Success/Error Messages -->
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">
                <i class="fas fa-wallet text-purple-600 mr-3"></i>My Wallet
            </h1>
            <p class="text-gray-600">Manage your AiNi Coins, buy more, and view transaction history</p>
        </div>

        <!-- Balance Overview Cards - DUAL COIN SYSTEM -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- AiNi Rewards Balance -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl shadow-xl p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold opacity-90">AiNi Rewards</h3>
                    <i class="fas fa-shield-alt text-3xl opacity-50"></i>
                </div>
                <div class="text-4xl font-bold mb-2">
                    <?php echo number_format($user['aini_rewards'] ?? 0); ?>
                </div>
                <div class="text-sm opacity-75">
                    💎 Stable · Multi-Asset Backed
                </div>
                <div class="text-xs opacity-60 mt-2">
                    ≈ $<?php echo number_format($user['aini_rewards'] ?? 0, 2); ?> USD
                </div>
            </div>

            <!-- AiNi Crypto Balance -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl shadow-xl p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold opacity-90">AiNi Crypto</h3>
                    <i class="fas fa-rocket text-3xl opacity-50 coin-animation"></i>
                </div>
                <div class="text-4xl font-bold mb-2">
                    <?php echo number_format($user['aini_crypto'] ?? 0); ?>
                </div>
                <div class="text-sm opacity-75">
                    🚀 Growth · Market-Driven
                </div>
                <div class="text-xs opacity-60 mt-2">
                    ≈ $<?php 
                        // Get current crypto price (will be $1.00 initially)
                        $stmt_price = $pdo->query("SELECT price_usd FROM aini_crypto_market_prices ORDER BY recorded_at DESC LIMIT 1");
                        $crypto_price = $stmt_price ? $stmt_price->fetchColumn() : 1.00;
                        echo number_format(($user['aini_crypto'] ?? 0) * $crypto_price, 2); 
                    ?> USD
                </div>
            </div>

            <!-- Total Earned -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-700">Total Earned</h3>
                    <i class="fas fa-arrow-trend-up text-3xl text-green-500"></i>
                </div>
                <div class="text-3xl font-bold text-gray-800 mb-2">
                    +<?php echo number_format($coinStats['lifetime_earned'], 2); ?>
                </div>
                <div class="text-sm text-gray-500">
                    From bookings & rewards
                </div>
            </div>

            <!-- Total Spent -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-700">Total Spent</h3>
                    <i class="fas fa-shopping-cart text-3xl text-orange-500"></i>
                </div>
                <div class="text-3xl font-bold text-gray-800 mb-2">
                    -<?php echo number_format($coinStats['lifetime_spent'], 2); ?>
                </div>
                <div class="text-sm text-gray-500">
                    On bookings & rewards
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Buy Coins & Quick Actions -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Buy Coins Card -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-shopping-bag text-purple-600 mr-2"></i>Buy Coins
                    </h2>
                    <p class="text-gray-600 mb-6 text-sm">Purchase AiNi Coins to use across our travel network. Save 10-15% on fees!</p>
                    
                    <form method="POST" action="wallet.php">
                        <!-- Amount Input -->
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Amount (USD)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-gray-500">$</span>
                                <input type="number" name="amount_usd" min="10" step="0.01" required
                                    class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    placeholder="10.00">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Minimum: $10.00 USD</p>
                        </div>

                        <!-- Currency Selector -->
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Currency</label>
                            <select name="currency" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <?php foreach ($exchangeRates as $rate): ?>
                                    <option value="<?php echo $rate['currency_code']; ?>" 
                                        <?php echo ($rate['currency_code'] === 'USD') ? 'selected' : ''; ?>>
                                        <?php echo $rate['currency_code']; ?> - <?php echo $rate['currency_name']; ?>
                                        (<?php echo number_format($rate['coins_per_unit'], 4); ?> coins per unit)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Fee Notice -->
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-purple-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Platform fee:</strong> 2.5% (much lower than credit card fees!)
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" name="buy_coins" 
                            class="w-full bg-gradient-to-r from-purple-600 to-purple-700 text-white py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-purple-800 transition shadow-lg">
                            <i class="fas fa-credit-card mr-2"></i>Buy Coins
                        </button>
                    </form>

                    <p class="text-xs text-gray-500 mt-4 text-center">
                        <i class="fas fa-lock mr-1"></i>Secure payment powered by Stripe
                    </p>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="convert_coins.php" class="block w-full bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600 text-white py-3 rounded-lg font-semibold text-center transition">
                            <i class="fas fa-exchange-alt mr-2"></i>Convert Coins
                        </a>
                        <a href="transfer.php" class="block w-full bg-purple-100 hover:bg-purple-200 text-purple-700 py-3 rounded-lg font-semibold text-center transition">
                            <i class="fas fa-paper-plane mr-2"></i>Send Coins
                        </a>
                        <a href="rewards.php" class="block w-full bg-orange-100 hover:bg-orange-200 text-orange-700 py-3 rounded-lg font-semibold text-center transition">
                            <i class="fas fa-gift mr-2"></i>Rewards Store
                        </a>
                        <a href="reserve_fund.php" class="block w-full bg-blue-100 hover:bg-blue-200 text-blue-700 py-3 rounded-lg font-semibold text-center transition">
                            <i class="fas fa-chart-line mr-2"></i>Reserve Fund
                        </a>
                        <a href="public_booking.php" class="block w-full bg-green-100 hover:bg-green-200 text-green-700 py-3 rounded-lg font-semibold text-center transition">
                            <i class="fas fa-hotel mr-2"></i>Book Experience
                        </a>
                    </div>
                </div>

            </div>

            <!-- Right Column: Transaction History -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-history text-purple-600 mr-2"></i>Transaction History
                        </h2>
                        <button class="text-purple-600 hover:text-purple-700 text-sm font-semibold">
                            <i class="fas fa-download mr-1"></i>Export
                        </button>
                    </div>

                    <?php if (empty($transactionHistory)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg">No transactions yet</p>
                            <p class="text-gray-400 text-sm">Your transaction history will appear here</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b-2 border-gray-200">
                                        <th class="text-left py-3 px-4 text-gray-600 font-semibold">Date</th>
                                        <th class="text-left py-3 px-4 text-gray-600 font-semibold">Type</th>
                                        <th class="text-left py-3 px-4 text-gray-600 font-semibold">Description</th>
                                        <th class="text-right py-3 px-4 text-gray-600 font-semibold">Amount</th>
                                        <th class="text-right py-3 px-4 text-gray-600 font-semibold">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactionHistory as $transaction): ?>
                                        <tr class="border-b border-gray-100 transaction-row">
                                            <td class="py-3 px-4 text-sm text-gray-600">
                                                <?php echo date('M d, Y', strtotime($transaction['created_at'])); ?>
                                                <br>
                                                <span class="text-xs text-gray-400">
                                                    <?php echo date('h:i A', strtotime($transaction['created_at'])); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php
                                                $typeIcons = [
                                                    'earned' => '<i class="fas fa-plus-circle text-green-500"></i>',
                                                    'spent' => '<i class="fas fa-shopping-cart text-orange-500"></i>',
                                                    'transfer' => '<i class="fas fa-exchange-alt text-blue-500"></i>',
                                                    'purchase' => '<i class="fas fa-credit-card text-purple-500"></i>',
                                                    'refund' => '<i class="fas fa-undo text-yellow-500"></i>',
                                                ];
                                                echo $typeIcons[$transaction['transaction_type']] ?? '<i class="fas fa-circle text-gray-500"></i>';
                                                ?>
                                                <span class="ml-2 text-sm capitalize"><?php echo $transaction['transaction_type']; ?></span>
                                            </td>
                                            <td class="py-3 px-4 text-sm text-gray-700">
                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                                <?php if ($transaction['transaction_hash']): ?>
                                                    <br>
                                                    <span class="text-xs text-gray-400 font-mono">
                                                        Hash: <?php echo substr($transaction['transaction_hash'], 0, 12); ?>...
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <?php
                                                $amountClass = ($transaction['amount'] >= 0) ? 'text-green-600' : 'text-red-600';
                                                $amountSign = ($transaction['amount'] >= 0) ? '+' : '';
                                                ?>
                                                <span class="font-semibold <?php echo $amountClass; ?>">
                                                    <?php echo $amountSign . number_format($transaction['amount'], 2); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-right text-gray-700 font-semibold">
                                                <?php echo number_format($transaction['balance_after'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination (if needed) -->
                        <div class="mt-6 flex items-center justify-between">
                            <p class="text-sm text-gray-500">
                                Showing last 20 transactions
                            </p>
                            <button class="text-purple-600 hover:text-purple-700 text-sm font-semibold">
                                View All <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Blockchain Verification Notice -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-shield-alt text-blue-600 text-2xl mr-3 mt-1"></i>
                        <div>
                            <h4 class="font-semibold text-blue-800 mb-1">Blockchain Security</h4>
                            <p class="text-sm text-blue-700">
                                All transactions are secured with SHA-256 hashing and stored in an immutable ledger. 
                                Your coins are protected by the same technology that powers cryptocurrencies.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Footer (Same as public_booking.php) -->
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

</body>
</html>
