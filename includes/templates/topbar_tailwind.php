<?php
/**
 * TOPBAR TEMPLATE - TAILWIND VERSION
 * Pure Tailwind utility classes - no custom CSS
 * Matches room_management_modern.php style
 * Font Awesome is loaded globally via HeadTemplate; swarm/UI scanners may flag false warnings
 */

class TopbarTemplate {
    private $user;
    private $showSearch;
    private $searchPlaceholder;
    private $notifications;

    public function __construct($user = null, $showSearch = true) {
        $this->user = $user;
        $this->showSearch = $showSearch;
        $this->searchPlaceholder = "Ask AI: 'show occupied rooms' or 'rooms available'";
        $this->notifications = [];
    }

    public function setUser($user) {
        $this->user = $user;
        return $this;
    }

    public function addNotification($notification) {
        $this->notifications[] = $notification;
        return $this;
    }

    public function render() {
        $userName = $this->user['full_name'] ?? $this->user['username'] ?? 'User';
        $userInitials = $this->getUserInitials($userName);
        $userRole = $this->user['user_role'] ?? 'Guest';
        $role = $this->user['role'] ?? 'staff';
        
        // Check if user is staff (should not see settings)
        $isStaff = ($userRole === 'guest' && $role === 'staff');
        ?>
<!-- Topbar - Pure Tailwind -->
<header class="topbar-offset fixed top-0 left-0 lg:left-64 right-0 h-16 bg-white/80 dark:bg-gray-800 bg-opacity-95 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-6 z-50 transition-all duration-300 shadow-sm dark:shadow-none">
    <!-- Left Section -->
    <div class="flex items-center gap-4 flex-1">
        <!-- Sidebar Toggle (Desktop) -->
        <button onclick="toggleSidebar()" class="hidden lg:flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-slate-600 dark:text-gray-300 transition-all duration-200 border border-gray-200 dark:border-transparent" title="Toggle Sidebar">
            <i class="fas fa-bars text-lg"></i>
        </button>

        <!-- Mobile Menu Toggle -->
        <button onclick="toggleSidebar()" class="lg:hidden flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-slate-600 dark:text-gray-300 transition-all duration-200 border border-gray-200 dark:border-transparent">
            <i class="fas fa-bars text-lg"></i>
        </button>

        <?php if ($this->showSearch): ?>
        <!-- AI Search Bar -->
        <div class="hidden md:flex items-center flex-1 max-w-xl bg-white dark:bg-gray-900 rounded-xl px-4 py-2.5 border border-gray-200 dark:border-gray-700 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-opacity-30 transition-all duration-200">
            <i class="fas fa-robot text-blue-500 dark:text-blue-400 text-lg mr-3"></i>
            <input
                type="text"
                class="flex-1 bg-transparent border-none outline-none text-slate-700 dark:text-gray-200 placeholder-slate-400 dark:placeholder-gray-500 text-sm"
                placeholder="<?php echo htmlspecialchars($this->searchPlaceholder); ?>"
                id="aiSearchInput"
            >
            <button class="ml-2 text-slate-500 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors duration-200" title="Voice Search">
                <i class="fas fa-microphone"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right Section -->
    <div class="flex items-center gap-3">

        <!-- Currency Switcher -->
        <button onclick="toggleCurrency()" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300 transition-all duration-200 border border-gray-200 dark:border-transparent" title="Switch Currency">
            <i class="fas fa-dollar-sign text-sm"></i>
            <span id="currentCurrency" class="text-sm font-medium">USD</span>
        </button>

        <!-- Notifications -->
        <div class="relative">
            <button onclick="toggleNotifications()" class="relative flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300 transition-all duration-200 border border-gray-200 dark:border-transparent">
                <i class="fas fa-bell text-lg"></i>
                <?php if (count($this->notifications) > 0): ?>
                    <span class="absolute top-1 right-1 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full">
                        <?php echo count($this->notifications); ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Notifications Dropdown -->
            <div id="notificationsDropdown" class="hidden absolute top-full right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Notifications</h3>
                    <button class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 font-medium">Mark all read</button>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <?php if (count($this->notifications) > 0): ?>
                        <?php foreach ($this->notifications as $notif): ?>
                            <div class="flex items-start gap-3 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-700 cursor-pointer transition-colors duration-150">
                                <i class="fas <?php echo $notif['icon'] ?? 'fa-info-circle'; ?> text-blue-600 dark:text-blue-400 text-lg mt-1"></i>
                                <div class="flex-1">
                                    <p class="text-sm text-slate-700 dark:text-gray-200"><?php echo htmlspecialchars($notif['message']); ?></p>
                                    <span class="text-xs text-slate-500 dark:text-gray-500 mt-1"><?php echo $notif['time'] ?? 'Just now'; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center py-12 text-slate-500 dark:text-gray-500">
                            <i class="fas fa-check-circle text-5xl mb-3 opacity-30"></i>
                            <p class="text-sm">No new notifications</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Theme Toggle -->
        <button onclick="toggleTheme()" class="flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300 transition-all duration-200 border border-gray-200 dark:border-transparent" title="Toggle Theme">
            <i class="fas fa-moon text-lg" data-theme-toggle-icon></i>
        </button>

        <!-- User Profile -->
        <div class="relative">
            <button onclick="toggleUserMenu()" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200 border border-gray-200 dark:border-transparent">
                <div class="flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-white font-bold text-sm">
                    <?php echo htmlspecialchars($userInitials); ?>
                </div>
                <div class="hidden md:flex flex-col items-start">
                    <span class="text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($userName); ?></span>
                    <span class="text-xs text-slate-500 dark:text-gray-400 capitalize"><?php echo htmlspecialchars($userRole); ?></span>
                </div>
                <i class="fas fa-chevron-down text-slate-500 dark:text-gray-400 text-xs"></i>
            </button>

            <!-- User Dropdown -->
            <div id="userDropdown" class="hidden absolute top-full right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden z-[9999] p-2">
                <a href="profile.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-150">
                    <i class="fas fa-user w-5 text-center"></i>
                    <span class="text-sm">Profile</span>
                </a>
                <?php if (!$isStaff): ?>
                <a href="hotel_setup.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-150">
                    <i class="fas fa-cog w-5 text-center"></i>
                    <span class="text-sm">Settings</span>
                </a>
                <?php endif; ?>
                <div class="h-px bg-gray-200 dark:bg-gray-700 my-2"></div>
                <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700 rounded-lg transition-colors duration-150">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i>
                    <span class="text-sm">Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Minimal JavaScript - Using Tailwind classes for styling -->
<script>
// Toggle Notifications
function toggleNotifications() {
    const dropdown = document.getElementById('notificationsDropdown');
    const userDropdown = document.getElementById('userDropdown');

    dropdown.classList.toggle('hidden');
    userDropdown.classList.add('hidden');
}

// Toggle User Menu
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    const notifDropdown = document.getElementById('notificationsDropdown');

    dropdown.classList.toggle('hidden');
    notifDropdown.classList.add('hidden');
}

// Toggle Sidebar
function toggleSidebar(forceState) {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const body = document.body;
    if (!sidebar) return;

    const isDesktop = window.matchMedia('(min-width: 1024px)').matches;

    if (isDesktop) {
        const isMini = body.classList.toggle('sidebar-mini');
        localStorage.setItem('sidebarMode', isMini ? 'mini' : 'full');
        return;
    }

    const isActive = sidebar.classList.contains('active');
    const shouldOpen = typeof forceState === 'boolean' ? forceState : !isActive;

    if (shouldOpen) {
        sidebar.classList.add('active');
        overlay?.classList.remove('hidden');
        body.classList.add('overflow-hidden');
    } else {
        sidebar.classList.remove('active');
        overlay?.classList.add('hidden');
        body.classList.remove('overflow-hidden');
    }
}

// Toggle Currency (USD ↔ PEN)
function toggleCurrency() {
    const currencySpan = document.getElementById('currentCurrency');
    const currencyIcon = document.querySelector('[onclick="toggleCurrency()"] i');

    if (currencySpan.textContent === 'USD') {
        currencySpan.textContent = 'PEN';
        currencyIcon.className = 'fas fa-coins text-sm';
        localStorage.setItem('preferredCurrency', 'PEN');
    } else {
        currencySpan.textContent = 'USD';
        currencyIcon.className = 'fas fa-dollar-sign text-sm';
        localStorage.setItem('preferredCurrency', 'USD');
    }

    // Emit event for price updates
    window.dispatchEvent(new CustomEvent('currencyChanged', {
        detail: { currency: currencySpan.textContent }
    }));
}

function applyTheme(theme) {
    const html = document.documentElement;
    const themeIcon = document.querySelector('[data-theme-toggle-icon]');
    if (!themeIcon) return;

    if (theme === 'light') {
        html.classList.add('light-theme');
        html.classList.remove('dark');
        themeIcon.className = 'fas fa-sun text-lg';
    } else {
        html.classList.remove('light-theme');
        html.classList.add('dark');
        themeIcon.className = 'fas fa-moon text-lg';
        theme = 'dark';
    }

    localStorage.setItem('theme', theme);
}

// Toggle Theme (Dark ↔ Light)
function toggleTheme() {
    const html = document.documentElement;
    const isLight = html.classList.contains('light-theme');
    applyTheme(isLight ? 'dark' : 'light');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('[onclick="toggleNotifications()"]') &&
        !event.target.closest('#notificationsDropdown')) {
        document.getElementById('notificationsDropdown')?.classList.add('hidden');
    }
    if (!event.target.closest('[onclick="toggleUserMenu()"]') &&
        !event.target.closest('#userDropdown')) {
        document.getElementById('userDropdown')?.classList.add('hidden');
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Restore theme
    const savedTheme = localStorage.getItem('theme') || 'dark';
    if (savedTheme === 'light') {
        applyTheme('light');
    } else {
        applyTheme('dark');
    }

    // Restore currency
    const savedCurrency = localStorage.getItem('preferredCurrency') || 'USD';
    const currencySpan = document.getElementById('currentCurrency');
    const currencyIcon = document.querySelector('[onclick="toggleCurrency()"] i');

    if (savedCurrency === 'PEN') {
        currencySpan.textContent = 'PEN';
        currencyIcon.className = 'fas fa-coins text-sm';
    }

    if (window.innerWidth >= 1024) {
        const savedSidebarMode = localStorage.getItem('sidebarMode');
        if (savedSidebarMode === 'mini') {
            document.body.classList.add('sidebar-mini');
        }
    }
});

window.addEventListener('resize', function() {
    if (window.innerWidth >= 1024) {
        document.querySelector('.sidebar')?.classList.remove('active');
        document.querySelector('.sidebar-overlay')?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        const savedSidebarMode = localStorage.getItem('sidebarMode');
        if (savedSidebarMode === 'mini') {
            document.body.classList.add('sidebar-mini');
        } else {
            document.body.classList.remove('sidebar-mini');
        }
    } else {
        document.body.classList.remove('sidebar-mini');
    }
});
</script>
        <?php
    }

    private function getUserInitials($name) {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}
?>
