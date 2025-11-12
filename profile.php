<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : 'Guest';
$userEmail = $isLoggedIn ? $_SESSION['user_email'] : '';

// Check if user is logged in
if (!$isLoggedIn) {
    header('Location: login.php');
    exit();
}

require_once 'db_connection_pdo.php';

$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM ainitravel_users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = htmlspecialchars($_POST['name']);
    $phone = htmlspecialchars($_POST['phone']);
    $country = htmlspecialchars($_POST['country']);
    $preferred_language = $_POST['preferred_language'];
    $currency_preference = $_POST['currency_preference'];
    
    $stmt = $pdo->prepare("UPDATE ainitravel_users SET name = ?, phone = ?, country = ?, preferred_language = ?, currency_preference = ?, updated_at = NOW() WHERE id = ?");
    
    if ($stmt->execute([$name, $phone, $country, $preferred_language, $currency_preference, $_SESSION['user_id']])) {
        $success = 'Profile updated successfully!';
        $_SESSION['user_name'] = $name;
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM ainitravel_users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    } else {
        $error = 'Failed to update profile';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!password_verify($current_password, $user['password'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE ainitravel_users SET password = ?, updated_at = NOW() WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
            $success = 'Password changed successfully!';
        } else {
            $error = 'Failed to change password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - AiNi Travel</title>

    <!-- Google Translate -->
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'es',
                includedLanguages: 'en,es,pt,fr,de,it,zh-CN,ja,ko,ru,ar,hi,nl,sv,no,da,fi,pl,tr,th,vi,id',
                layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>    

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#667eea',
                        secondary: '#764ba2',
                    }
                }
            }
        }
    </script>

    <style>
        body {
            padding-top: 64px; /* Space for fixed header */
            background-color: #f9fafb;
        }

        .mobile-menu {
            display: none;
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem;
            z-index: 40;
            flex-direction: column;
            gap: 0.5rem;
        }

        .mobile-menu.active {
            display: flex;
        }

        .mobile-menu a {
            color: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
        }

        .mobile-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 768px) {
            .mobile-menu-button {
                display: block !important;
            }
        }

        @media (min-width: 769px) {
            .mobile-menu-button {
                display: none !important;
            }
        }

        /* Google Translate Styling */
        #google_translate_element {
            display: inline-block;
        }
        .goog-te-gadget {
            font-family: inherit !important;
            font-size: 0 !important;
            color: white !important;
        }
        .goog-te-gadget-simple {
            background-color: rgba(255, 255, 255, 0.2) !important;
            border: none !important;
            padding: 6px 12px !important;
            border-radius: 9999px !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: white !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
            line-height: 1.5rem !important;
        }
        .goog-te-gadget-simple:hover {
            background-color: rgba(255, 255, 255, 0.3) !important;
        }
        .goog-te-gadget-icon {
            display: none !important;
        }
        .goog-te-menu-value {
            color: white !important;
        }
        .goog-te-menu-value span {
            color: white !important;
        }
        .goog-te-menu-value span:first-child {
            display: none !important;
        }
        .goog-te-menu-value:before {
            content: "🌐 ";
            font-size: 1rem;
        }
        .goog-te-gadget .goog-te-gadget-simple .goog-te-menu-value span:nth-child(3) {
            display: inline !important;
            color: white !important;
            border: none !important;
        }
        .goog-te-gadget .goog-te-gadget-simple .goog-te-menu-value span:nth-child(5) {
            color: white !important;
        }
        /* Hide Google Translate banner */
        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }
        body {
            top: 0 !important;
        }
        .skiptranslate {
            color: white !important;
        }
        iframe.skiptranslate {
            visibility: hidden !important;
        }
        .goog-logo-link {
            display: none !important;
        }
        .goog-te-gadget .goog-te-combo {
            margin: 0 !important;
            padding: 4px 8px !important;
            background: rgba(255,255,255,0.2) !important;
            border: none !important;
            border-radius: 9999px !important;
            color: white !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header Navigation (Same as public_booking.php) -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-primary to-secondary shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="public_booking.php" class="text-2xl font-bold text-white">
                        🪙 AiNi Travel
                    </a>
                </div>

                <!-- Mobile Hamburger Button -->
                <button class="mobile-menu-button text-white text-3xl" onclick="toggleMobileMenu()">
                    ☰
                </button>

                <!-- Navigation Links (Center - Desktop) -->
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="public_booking.php#experiences" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                        🎯 Experiences
                    </a>
                    <a href="https://ainiflow.com" target="_blank" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                        🌍 Social
                    </a>
                    <a href="wallet.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                        🪙 Coins
                    </a>
                    <a href="#" onclick="openReservationsCart(event)" class="bg-cyan-400 text-white hover:bg-cyan-500 px-4 py-2 rounded-full transition font-medium relative shadow-lg">
                        🛒 Cart
                        <span id="cartCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                    </a>
                </nav>

                <!-- Header Icons (Right) -->
                <div class="flex items-center space-x-2">
                    <!-- Custom Language Selector -->
                    <div class="relative" id="languageSelector">
                        <button onclick="toggleLanguageMenu()" class="px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-full text-sm font-semibold text-white transition flex items-center gap-1">
                            🌐 <span id="currentLang">ES</span> <span class="text-xs">▼</span>
                        </button>
                        <div id="languageMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 max-h-96 overflow-y-auto">
                            <a href="#" onclick="changeLanguage('es', 'ES')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇪🇸 Español</a>
                            <a href="#" onclick="changeLanguage('en', 'EN')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇬🇧 English</a>
                            <a href="#" onclick="changeLanguage('pt', 'PT')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇵🇹 Português</a>
                            <a href="#" onclick="changeLanguage('fr', 'FR')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇫🇷 Français</a>
                            <a href="#" onclick="changeLanguage('de', 'DE')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇩🇪 Deutsch</a>
                            <a href="#" onclick="changeLanguage('it', 'IT')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇮🇹 Italiano</a>
                            <a href="#" onclick="changeLanguage('zh-CN', '中文')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇨🇳 中文</a>
                            <a href="#" onclick="changeLanguage('ja', '日本語')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇯🇵 日本語</a>
                            <a href="#" onclick="changeLanguage('ko', '한국어')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇰🇷 한국어</a>
                            <a href="#" onclick="changeLanguage('ru', 'RU')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇷🇺 Русский</a>
                            <a href="#" onclick="changeLanguage('ar', 'AR')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇸🇦 العربية</a>
                            <a href="#" onclick="changeLanguage('hi', 'HI')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇮🇳 हिन्दी</a>
                            <a href="#" onclick="changeLanguage('nl', 'NL')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇳🇱 Nederlands</a>
                            <a href="#" onclick="changeLanguage('sv', 'SV')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇸🇪 Svenska</a>
                            <a href="#" onclick="changeLanguage('tr', 'TR')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇹🇷 Türkçe</a>
                            <a href="#" onclick="changeLanguage('pl', 'PL')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇵🇱 Polski</a>
                            <a href="#" onclick="changeLanguage('th', 'TH')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇹🇭 ไทย</a>
                            <a href="#" onclick="changeLanguage('vi', 'VI')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇻🇳 Tiếng Việt</a>
                            <a href="#" onclick="changeLanguage('id', 'ID')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇮🇩 Indonesia</a>
                        </div>
                    </div>
                    <!-- Hidden Google Translate Element -->
                    <div id="google_translate_element" style="display:none;"></div>

                    <button class="relative px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-full transition text-white">
                        🔔
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                    </button>

                    <!-- Profile Button -->
                    <div class="relative" id="profileSelector">
                        <button onclick="toggleProfileMenu()" class="px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-full transition text-white">
                            👤
                        </button>
                        <!-- Profile Dropdown -->
                        <div id="profileMenu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-2 z-50">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($userName); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($userEmail); ?></p>
                            </div>
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">👤 My Profile</a>
                            <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">📋 My Bookings</a>
                            <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">⚙️ Settings</a>
                            <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🪙 My Coins</a>
                            <div class="border-t border-gray-200 mt-2 pt-2">
                                <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">🚪 Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Menu (Hidden by default) -->
    <div id="mobileMenu" class="mobile-menu">
        <a href="public_booking.php#experiences">🎯 Experiences</a>
        <a href="https://ainiflow.com" target="_blank">🌍 Social</a>
        <a href="wallet.php">🪙 Coins</a>
        <a href="#" onclick="openReservationsCart(event)" class="relative" style="background: #22d3ee; padding: 0.75rem; border-radius: 0.5rem; font-weight: 600;">
            🛒 Cart
            <span id="cartCountMobile" class="absolute top-0 right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
        </a>
        <div class="border-t border-white/20 my-2 pt-2">
            <a href="#" class="text-sm">🌐 ES</a>
            <a href="#" class="text-sm">🔔 Notifications (3)</a>
            <a href="profile.php" class="text-sm">👤 Profile</a>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Profile Container -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary to-secondary p-8 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center text-4xl">
                        👤
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($user['name']); ?></h1>
                        <p class="text-white/90"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="text-white/80 text-sm mt-1">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 mt-6">
                    <div class="bg-white/20 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold"><?php echo $user['total_bookings']; ?></div>
                        <div class="text-sm text-white/80">Bookings</div>
                    </div>
                    <div class="bg-white/20 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold">🪙 <?php echo $user['aini_coins']; ?></div>
                        <div class="text-sm text-white/80">AiNi Coins</div>
                    </div>
                    <div class="bg-white/20 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold"><?php echo $user['email_verified'] ? '✓' : '✗'; ?></div>
                        <div class="text-sm text-white/80">Verified</div>
                    </div>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-6 mb-0" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-6 mb-0" role="alert">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="p-6">
                <div class="border-b border-gray-200 mb-6">
                    <nav class="flex gap-4">
                        <button onclick="showTab('info')" id="tab-info" class="tab-button border-b-2 border-primary text-primary pb-2 px-4 font-semibold">
                            Profile Information
                        </button>
                        <button onclick="showTab('security')" id="tab-security" class="tab-button pb-2 px-4 text-gray-600 hover:text-gray-800">
                            Security
                        </button>
                    </nav>
                </div>
                
                <!-- Profile Information Tab -->
                <div id="content-info" class="tab-content">
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                    required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                                    disabled>
                                <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="+1 234 567 8900">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                                <input type="text" name="country" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="Your country">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Language</label>
                                <select name="preferred_language" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="es" <?php echo $user['preferred_language'] === 'es' ? 'selected' : ''; ?>>Español</option>
                                    <option value="en" <?php echo $user['preferred_language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="pt" <?php echo $user['preferred_language'] === 'pt' ? 'selected' : ''; ?>>Português</option>
                                    <option value="fr" <?php echo $user['preferred_language'] === 'fr' ? 'selected' : ''; ?>>Français</option>
                                    <option value="de" <?php echo $user['preferred_language'] === 'de' ? 'selected' : ''; ?>>Deutsch</option>
                                    <option value="it" <?php echo $user['preferred_language'] === 'it' ? 'selected' : ''; ?>>Italiano</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Currency Preference</label>
                                <select name="currency_preference" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="USD" <?php echo $user['currency_preference'] === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="EUR" <?php echo $user['currency_preference'] === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                    <option value="GBP" <?php echo $user['currency_preference'] === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                    <option value="PEN" <?php echo $user['currency_preference'] === 'PEN' ? 'selected' : ''; ?>>PEN (S/)</option>
                                    <option value="MXN" <?php echo $user['currency_preference'] === 'MXN' ? 'selected' : ''; ?>>MXN ($)</option>
                                    <option value="BRL" <?php echo $user['currency_preference'] === 'BRL' ? 'selected' : ''; ?>>BRL (R$)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="update_profile" 
                                class="px-8 py-3 bg-gradient-to-r from-primary to-secondary text-white rounded-lg hover:shadow-lg transform hover:scale-105 transition duration-200 font-semibold">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Security Tab -->
                <div id="content-security" class="tab-content hidden">
                    <form method="POST" class="space-y-6 max-w-md">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Change Password</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password" name="current_password" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" name="new_password" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                required minlength="6">
                            <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                required minlength="6">
                        </div>
                        
                        <div>
                            <button type="submit" name="change_password" 
                                class="px-8 py-3 bg-gradient-to-r from-primary to-secondary text-white rounded-lg hover:shadow-lg transform hover:scale-105 transition duration-200 font-semibold">
                                Change Password
                            </button>
                        </div>
                    </form>
                    
                    <!-- Account Actions -->
                    <div class="mt-12 pt-8 border-t border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Account Settings</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium">Email Verification</p>
                                    <p class="text-sm text-gray-600">
                                        <?php echo $user['email_verified'] ? 'Your email is verified ✓' : 'Verify your email to unlock all features'; ?>
                                    </p>
                                </div>
                                <?php if (!$user['email_verified']): ?>
                                    <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                        Send Verification
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium">Account Status</p>
                                    <p class="text-sm text-gray-600">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-primary to-secondary text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">🪙 AiNi Travel</h3>
                    <p class="text-white/80 text-sm">Your gateway to extraordinary travel experiences</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Quick Links</h4>
                    <ul class="space-y-2 text-sm text-white/80">
                        <li><a href="public_booking.php" class="hover:text-white">Find Hotels</a></li>
                        <li><a href="#" class="hover:text-white">Experiences</a></li>
                        <li><a href="wallet.php" class="hover:text-white">AiNi Coins</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Support</h4>
                    <ul class="space-y-2 text-sm text-white/80">
                        <li><a href="#" class="hover:text-white">Help Center</a></li>
                        <li><a href="#" class="hover:text-white">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Connect</h4>
                    <ul class="space-y-2 text-sm text-white/80">
                        <li><a href="https://ainiflow.com" target="_blank" class="hover:text-white">AiniFlow Social</a></li>
                        <li><a href="#" class="hover:text-white">Facebook</a></li>
                        <li><a href="#" class="hover:text-white">Instagram</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/20 mt-8 pt-6 text-center text-sm text-white/70">
                <p>&copy; 2025 AiNi Travel. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active styling from all tabs
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-b-2', 'border-primary', 'text-primary');
                button.classList.add('text-gray-600');
            });
            
            // Show selected tab content
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Add active styling to selected tab
            const activeTab = document.getElementById('tab-' + tabName);
            activeTab.classList.add('border-b-2', 'border-primary', 'text-primary');
            activeTab.classList.remove('text-gray-600');
        }

        function toggleMobileMenu() {
            document.getElementById('mobileMenu').classList.toggle('active');
        }

        // Language Selector Functions
        function toggleLanguageMenu() {
            const menu = document.getElementById('languageMenu');
            menu.classList.toggle('hidden');
        }

        function changeLanguage(langCode, langDisplay) {
            // Update display
            document.getElementById('currentLang').textContent = langDisplay;

            // Close menu
            document.getElementById('languageMenu').classList.add('hidden');

            // Trigger Google Translate
            const select = document.querySelector('.goog-te-combo');
            if (select) {
                select.value = langCode;
                select.dispatchEvent(new Event('change'));
            }
        }

        // Profile Menu Functions
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('hidden');
        }

        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            const languageSelector = document.getElementById('languageSelector');
            const languageMenu = document.getElementById('languageMenu');
            const profileSelector = document.getElementById('profileSelector');
            const profileMenu = document.getElementById('profileMenu');

            if (languageSelector && !languageSelector.contains(event.target)) {
                languageMenu.classList.add('hidden');
            }

            if (profileSelector && !profileSelector.contains(event.target)) {
                profileMenu.classList.add('hidden');
            }
        });

        // Cart placeholder function
        function openReservationsCart(event) {
            event.preventDefault();
            window.location.href = 'public_booking.php';
        }
    </script>
</body>
</html>
