<?php
/**
 * Reusable Navigation Header
 * Include this at the top of every page after session_start()
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'Guest';
$userRole = $_SESSION['role'] ?? 'user';

// Get notification count (placeholder - implement real notifications later)
$notificationCount = 0;
if ($isLoggedIn) {
    try {
        if (!isset($pdo)) {
            require_once __DIR__ . '/../db_connection_pdo.php';
        }
        // Example: Count unread notifications
        // $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        // $stmt->execute([$_SESSION['user_id']]);
        // $notificationCount = $stmt->fetchColumn();
    } catch (Exception $e) {
        // Silent fail for notifications
    }
}
?>

<!-- Navigation Styles -->
<style>
    .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .profile-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 0.5rem;
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        min-width: 220px;
        display: none;
        z-index: 1000;
    }
    
    .profile-dropdown.active {
        display: block;
        animation: slideDown 0.2s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .profile-dropdown a {
        display: block;
        padding: 0.75rem 1rem;
        color: #333;
        text-decoration: none;
        transition: background 0.2s;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .profile-dropdown a:last-child {
        border-bottom: none;
    }
    
    .profile-dropdown a:hover {
        background: #f7f7f7;
    }
    
    .profile-dropdown .user-info {
        padding: 1rem;
        border-bottom: 2px solid #667eea;
        background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    }
    
    .profile-dropdown .user-info .user-name {
        font-weight: bold;
        color: #667eea;
        margin-bottom: 0.25rem;
    }
    
    .profile-dropdown .user-info .user-role {
        font-size: 0.75rem;
        color: #666;
        text-transform: uppercase;
    }
    
    .mobile-menu {
        display: none;
        position: fixed;
        top: 64px;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 1rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 40;
    }
    
    .mobile-menu.active {
        display: block;
    }
    
    .mobile-menu a {
        display: block;
        color: white;
        padding: 0.75rem;
        margin: 0.25rem 0;
        border-radius: 0.5rem;
        text-decoration: none;
        transition: background 0.2s;
    }
    
    .mobile-menu a:hover {
        background: rgba(255,255,255,0.2);
    }
    
    .notification-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #ef4444;
        color: white;
        font-size: 0.65rem;
        font-weight: bold;
        padding: 0.15rem 0.4rem;
        border-radius: 9999px;
        min-width: 18px;
        text-align: center;
    }
    
    @media (max-width: 768px) {
        .desktop-nav {
            display: none;
        }
    }
    
    @media (min-width: 769px) {
        .mobile-menu-button {
            display: none;
        }
    }
</style>

<!-- Navigation Header -->
<header class="gradient-bg shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            
            <!-- Logo -->
            <a href="public_booking.php" class="flex items-center space-x-2 text-white">
                <span class="text-2xl">🪙</span>
                <span class="text-xl font-bold hidden sm:inline">AiNi Travel</span>
            </a>
            
            <!-- Mobile Menu Button -->
            <button class="mobile-menu-button text-white text-3xl md:hidden" onclick="toggleMobileMenu()">
                ☰
            </button>
            
            <!-- Desktop Navigation -->
            <nav class="desktop-nav hidden md:flex items-center space-x-1">
                <a href="experiences_list.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                    🎯 Experiences
                </a>
                <a href="travel_social.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                    🌍 Social
                </a>
                <a href="wallet.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                    🪙 Wallet
                </a>
            </nav>
            
            <!-- Right Side Icons -->
            <div class="hidden md:flex items-center space-x-3">
                <!-- Language Selector -->
                <button class="px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-full text-sm font-semibold text-white transition">
                    🌐 ES
                </button>
                
                <!-- Notifications -->
                <?php if ($isLoggedIn): ?>
                    <button class="relative px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-full transition text-white">
                        🔔
                        <?php if ($notificationCount > 0): ?>
                            <span class="notification-badge"><?php echo $notificationCount; ?></span>
                        <?php endif; ?>
                    </button>
                <?php endif; ?>
                
                <!-- Profile Dropdown or Login -->
                <?php if ($isLoggedIn): ?>
                    <div class="relative">
                        <button onclick="toggleProfileDropdown()" class="flex items-center space-x-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full transition text-white font-medium">
                            <span>👤</span>
                            <span class="hidden lg:inline"><?php echo htmlspecialchars(explode(' ', $userName)[0]); ?></span>
                            <span class="text-xs">▼</span>
                        </button>
                        
                        <div id="profileDropdown" class="profile-dropdown">
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                                <div class="user-role"><?php echo htmlspecialchars($userRole); ?></div>
                            </div>
                            
                            <a href="profile.php">
                                <i class="fas fa-user mr-2"></i> Mi Perfil
                            </a>
                            <a href="wallet.php">
                                <i class="fas fa-wallet mr-2"></i> Billetera
                            </a>
                            <a href="transfer.php">
                                <i class="fas fa-exchange-alt mr-2"></i> Transferir Coins
                            </a>
                            <a href="rewards.php">
                                <i class="fas fa-gift mr-2"></i> Recompensas
                            </a>
                            
                            <?php if ($userRole === 'admin'): ?>
                                <a href="admin_dashboard.php" style="background: #fef3c7; color: #92400e;">
                                    <i class="fas fa-tachometer-alt mr-2"></i> Admin Dashboard
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($userRole === 'partner'): ?>
                                <a href="partner_dashboard.php">
                                    <i class="fas fa-briefcase mr-2"></i> Dashboard Partner
                                </a>
                            <?php endif; ?>
                            
                            <a href="logout.php" style="color: #ef4444;">
                                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="px-4 py-2 bg-yellow-400 hover:bg-yellow-300 text-gray-900 rounded-full font-semibold transition">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div id="mobileMenu" class="mobile-menu">
    <a href="experiences_list.php">🎯 Experiences</a>
    <a href="travel_social.php">🌍 Social</a>
    <a href="wallet.php">🪙 Wallet</a>
    
    <?php if ($isLoggedIn): ?>
        <div class="border-t border-white/30 my-2 pt-2">
            <a href="profile.php">👤 Mi Perfil</a>
            <a href="rewards.php">🎁 Recompensas</a>
            <a href="transfer.php">💸 Transferir</a>
            
            <?php if ($userRole === 'admin'): ?>
                <a href="admin_dashboard.php" style="background: rgba(254, 243, 199, 0.2);">
                    ⚙️ Admin Dashboard
                </a>
            <?php endif; ?>
            
            <a href="logout.php" style="background: rgba(239, 68, 68, 0.2);">
                🚪 Cerrar Sesión
            </a>
        </div>
    <?php else: ?>
        <div class="border-t border-white/30 my-2 pt-2">
            <a href="login.php" style="background: rgba(251, 191, 36, 0.3);">
                🔐 Login / Registrarse
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Navigation JavaScript -->
<script>
    // Toggle profile dropdown
    function toggleProfileDropdown() {
        const dropdown = document.getElementById('profileDropdown');
        dropdown.classList.toggle('active');
    }
    
    // Toggle mobile menu
    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        menu.classList.toggle('active');
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('profileDropdown');
        const profileButton = event.target.closest('[onclick="toggleProfileDropdown()"]');
        
        if (dropdown && !profileButton && !dropdown.contains(event.target)) {
            dropdown.classList.remove('active');
        }
        
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileButton = event.target.closest('.mobile-menu-button');
        
        if (mobileMenu && !mobileButton && !mobileMenu.contains(event.target)) {
            mobileMenu.classList.remove('active');
        }
    });
</script>
