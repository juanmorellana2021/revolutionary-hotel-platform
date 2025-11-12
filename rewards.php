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
    
    // Get available rewards
    $stmt = $pdo->query("
        SELECT * FROM aini_coin_rewards 
        WHERE is_active = 1 
        ORDER BY cost_in_coins ASC
    ");
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's redeemed rewards
    $stmt = $pdo->prepare("
        SELECT r.*, rw.name as reward_name, rw.reward_type, rw.value_usd
        FROM aini_coin_redemptions r
        JOIN aini_coin_rewards rw ON r.reward_id = rw.id
        WHERE r.user_id = ?
        ORDER BY r.redeemed_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $myRedemptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Rewards page error: " . $e->getMessage());
    $error = "Error loading rewards data.";
}

// Handle reward redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_reward'])) {
    $reward_id = intval($_POST['reward_id']);
    
    try {
        // Get reward details
        $stmt = $pdo->prepare("SELECT * FROM aini_coin_rewards WHERE id = ? AND is_active = 1");
        $stmt->execute([$reward_id]);
        $reward = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reward) {
            $error = "Reward not found or no longer available.";
        } elseif ($user['aini_coins'] < $reward['cost_in_coins']) {
            $error = "Insufficient coins. You need " . number_format($reward['cost_in_coins'], 2) . " coins but have " . number_format($user['aini_coins'], 2) . " coins.";
        } else {
            // Generate redemption code
            $redemption_code = strtoupper(substr(md5(uniqid($user_id . $reward_id, true)), 0, 12));
            
            // Calculate expiry (90 days from now)
            $expires_at = date('Y-m-d H:i:s', strtotime('+90 days'));
            
            // Record redemption
            $stmt = $pdo->prepare("
                INSERT INTO aini_coin_redemptions 
                (user_id, reward_id, coins_spent, redemption_code, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $reward_id, $reward['cost_in_coins'], $redemption_code, $expires_at]);
            
            // Deduct coins via transaction
            $coinSystem->recordTransaction(
                $user_id,
                -$reward['cost_in_coins'],
                'spent',
                null,
                "Redeemed: " . $reward['name'],
                ['reward_id' => $reward_id, 'redemption_code' => $redemption_code]
            );
            
            $success = "Successfully redeemed " . htmlspecialchars($reward['name']) . "! Your redemption code is: <strong>" . $redemption_code . "</strong>";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM ainitravel_users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Refresh redemptions
            $stmt = $pdo->prepare("
                SELECT r.*, rw.name as reward_name, rw.reward_type, rw.value_usd
                FROM aini_coin_redemptions r
                JOIN aini_coin_rewards rw ON r.reward_id = rw.id
                WHERE r.user_id = ?
                ORDER BY r.redeemed_at DESC
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $myRedemptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Redemption error: " . $e->getMessage());
        $error = "Redemption failed: " . $e->getMessage();
    }
}

// Get reward type filter
$filterType = $_GET['type'] ?? 'all';
if ($filterType !== 'all') {
    $rewards = array_filter($rewards, function($r) use ($filterType) {
        return $r['reward_type'] === $filterType;
    });
}
?>
<!DOCTYPE html>
<html lang="<?php echo $user['preferred_language'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards Store - AiNi Travel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .reward-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .reward-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-gift text-orange-500 mr-3"></i>Rewards Store
                    </h1>
                    <p class="text-gray-600">Redeem your AiNi Coins for amazing perks and discounts</p>
                </div>
                <div class="bg-gradient-to-r from-purple-500 to-purple-700 text-white rounded-xl p-4 text-center">
                    <p class="text-sm opacity-75">Your Balance</p>
                    <p class="text-3xl font-bold"><?php echo number_format($user['aini_coins'], 2); ?></p>
                    <p class="text-xs opacity-75">AiNi Coins</p>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="mb-8 flex flex-wrap gap-3">
            <a href="?type=all" class="px-6 py-2 rounded-full font-semibold transition <?php echo $filterType === 'all' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-purple-100'; ?>">
                All Rewards
            </a>
            <a href="?type=discount" class="px-6 py-2 rounded-full font-semibold transition <?php echo $filterType === 'discount' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-purple-100'; ?>">
                Discounts
            </a>
            <a href="?type=upgrade" class="px-6 py-2 rounded-full font-semibold transition <?php echo $filterType === 'upgrade' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-purple-100'; ?>">
                Upgrades
            </a>
            <a href="?type=amenity" class="px-6 py-2 rounded-full font-semibold transition <?php echo $filterType === 'amenity' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-purple-100'; ?>">
                Amenities
            </a>
        </div>

        <!-- Rewards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <?php foreach ($rewards as $reward): ?>
                <?php
                $canAfford = $user['aini_coins'] >= $reward['cost_in_coins'];
                $typeColors = [
                    'discount' => 'green',
                    'upgrade' => 'blue',
                    'amenity' => 'orange',
                    'voucher' => 'purple'
                ];
                $color = $typeColors[$reward['reward_type']] ?? 'gray';
                ?>
                <div class="reward-card bg-white rounded-xl shadow-lg overflow-hidden <?php echo !$canAfford ? 'opacity-60' : ''; ?>">
                    <div class="bg-gradient-to-r from-<?php echo $color; ?>-400 to-<?php echo $color; ?>-600 p-6 text-white">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wide"><?php echo $reward['reward_type']; ?></span>
                            <?php if ($canAfford): ?>
                                <i class="fas fa-check-circle text-lg"></i>
                            <?php else: ?>
                                <i class="fas fa-lock text-lg"></i>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-2xl font-bold mb-1"><?php echo htmlspecialchars($reward['name']); ?></h3>
                        <p class="text-sm opacity-90"><?php echo htmlspecialchars($reward['description']); ?></p>
                    </div>
                    
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($reward['cost_in_coins'], 0); ?></p>
                                <p class="text-sm text-gray-500">AiNi Coins</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-green-600">$<?php echo number_format($reward['value_usd'], 2); ?></p>
                                <p class="text-xs text-gray-500">Value</p>
                            </div>
                        </div>

                        <?php if ($reward['terms_conditions']): ?>
                            <div class="text-xs text-gray-600 mb-4">
                                <p class="font-semibold mb-1">Terms:</p>
                                <p><?php echo htmlspecialchars($reward['terms_conditions']); ?></p>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="rewards.php">
                            <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
                            <button type="submit" name="redeem_reward" 
                                <?php echo !$canAfford ? 'disabled' : ''; ?>
                                class="w-full py-3 rounded-lg font-bold text-white transition <?php echo $canAfford ? 'bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800' : 'bg-gray-400 cursor-not-allowed'; ?>">
                                <?php if ($canAfford): ?>
                                    <i class="fas fa-gift mr-2"></i>Redeem Now
                                <?php else: ?>
                                    <i class="fas fa-lock mr-2"></i>Need <?php echo number_format($reward['cost_in_coins'] - $user['aini_coins'], 2); ?> more coins
                                <?php endif; ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- My Redemptions -->
        <?php if (!empty($myRedemptions)): ?>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-ticket-alt text-purple-600 mr-2"></i>My Redemptions
                </h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-200">
                                <th class="text-left py-3 px-4 text-gray-600 font-semibold">Reward</th>
                                <th class="text-left py-3 px-4 text-gray-600 font-semibold">Code</th>
                                <th class="text-left py-3 px-4 text-gray-600 font-semibold">Redeemed</th>
                                <th class="text-left py-3 px-4 text-gray-600 font-semibold">Expires</th>
                                <th class="text-center py-3 px-4 text-gray-600 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myRedemptions as $redemption): ?>
                                <?php
                                $isExpired = strtotime($redemption['expires_at']) < time();
                                $isUsed = $redemption['is_used'];
                                ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 px-4">
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($redemption['reward_name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo ucfirst($redemption['reward_type']); ?> · $<?php echo number_format($redemption['value_usd'], 2); ?> value</p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <code class="bg-purple-100 text-purple-800 px-2 py-1 rounded font-mono text-sm">
                                            <?php echo $redemption['redemption_code']; ?>
                                        </code>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-600">
                                        <?php echo date('M d, Y', strtotime($redemption['redeemed_at'])); ?>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-600">
                                        <?php echo date('M d, Y', strtotime($redemption['expires_at'])); ?>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <?php if ($isUsed): ?>
                                            <span class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs font-semibold">Used</span>
                                        <?php elseif ($isExpired): ?>
                                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-semibold">Expired</span>
                                        <?php else: ?>
                                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">Active</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

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

</body>
</html>
