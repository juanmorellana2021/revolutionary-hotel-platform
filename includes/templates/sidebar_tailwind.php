<?php
/**
 * SIDEBAR TEMPLATE - TAILWIND VERSION
 * Pure Tailwind utility classes - no custom CSS
 * Matches room_management_modern.php style
 * Font Awesome is loaded globally via HeadTemplate, so swarm/UI scanners may warn per-file
 */

class SidebarTemplate {
    private $hotelName;
    private $currentPage;
    private $menuItems;
    private $userRole;

    public function __construct($hotelName = 'AiNi Travel PMS', $currentPage = '', $userRole = 'admin') {
        $this->hotelName = $hotelName;
        $this->currentPage = $currentPage;
        $this->userRole = $userRole;
        $this->initializeMenuItems();
    }

    public function setHotelLogo($logoUrl) {
        $this->hotelLogo = $logoUrl;
        return $this;
    }

    public function addMenuItem($item) {
        $this->menuItems[] = $item;
        return $this;
    }

    private function initializeMenuItems() {
        $this->menuItems = [
            [
                'id' => 'timeclock',
                'label' => 'Time Clock',
                'icon' => 'fa-clock',
                'url' => 'time_clock.php',
                'roles' => ['admin', 'manager', 'staff']
            ],
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'fa-home',
                'url' => 'dashboard.php',
                'roles' => ['admin', 'manager']
            ],
            [
                'id' => 'rooms',
                'label' => 'Rooms',
                'icon' => 'fa-bed',
                'url' => 'room_management_modern.php',
                'roles' => ['admin', 'manager']
            ],
            [
                'id' => 'photos',
                'label' => 'Photos',
                'icon' => 'fa-camera',
                'url' => 'photo_upload_modern.php',
                'roles' => ['admin', 'manager']
            ],
            [
                'id' => 'calendar',
                'label' => 'Calendar',
                'icon' => 'fa-calendar',
                'url' => 'calendar_view_modern.php',
                'roles' => ['admin', 'manager']
            ],
            [
                'id' => 'employees',
                'label' => 'Employees',
                'icon' => 'fa-users',
                'url' => '#',
                'roles' => ['admin', 'manager', 'owner'],
                'submenu' => [
                    [
                        'label' => 'Employee Management',
                        'icon' => 'fa-user-tie',
                        'url' => 'employee_management.php'
                    ],
                    [
                        'label' => 'Time Clock',
                        'icon' => 'fa-clock',
                        'url' => 'time_clock.php'
                    ],
                    [
                        'label' => 'Payroll Management',
                        'icon' => 'fa-dollar-sign',
                        'url' => 'payroll_management.php'
                    ]
                ]
            ],
            [
                'id' => 'accounting',
                'label' => 'Accounting',
                'icon' => 'fa-chart-bar',
                'url' => '#',
                'roles' => ['admin', 'manager'],
                'submenu' => [
                    [
                        'label' => 'Accounting Dashboard',
                        'icon' => 'fa-chart-bar',
                        'url' => 'accounting_dashboard.php'
                    ],
                    [
                        'label' => 'Income Management',
                        'icon' => 'fa-arrow-trend-up',
                        'url' => 'income_management.php'
                    ],
                    [
                        'label' => 'Expense Management',
                        'icon' => 'fa-wallet',
                        'url' => 'expense_management.php'
                    ]
                ]
            ],
            [
                'id' => 'analytics',
                'label' => 'Analytics',
                'icon' => 'fa-chart-line',
                'url' => 'analytics.php',
                'roles' => ['admin', 'manager', 'owner']
            ],
            [
                'id' => 'myproperties',
                'label' => 'My Properties',
                'icon' => 'fa-building',
                'url' => 'owner_account.php',
                'roles' => ['admin', 'owner']
            ],
            [
                'id' => 'properties',
                'label' => 'Properties',
                'icon' => 'fa-building',
                'url' => 'hotel-management-system/manager_dashboard.php',
                'roles' => ['admin']
            ],
            [
                'id' => 'settings',
                'label' => 'Settings',
                'icon' => 'fa-cog',
                'url' => 'hotel_setup.php',
                'roles' => ['admin', 'manager']
            ],
            [
                'id' => 'logout',
                'label' => 'Logout',
                'icon' => 'fa-sign-out-alt',
                'url' => 'logout.php',
                'roles' => ['admin', 'manager', 'staff'],
                'divider' => true
            ]
        ];
    }

    private function canUserAccessMenu($menuRoles) {
        // Normalize role to lowercase for comparison
        $normalizedUserRole = strtolower($this->userRole);
        $roleAliases = [
            'administrator' => 'admin',
            'owner' => 'admin',
            'superadmin' => 'admin',
            'mgr' => 'manager',
            'staff_member' => 'staff'
        ];
        $normalizedUserRole = $roleAliases[$normalizedUserRole] ?? $normalizedUserRole;
        $normalizedMenuRoles = array_map(function ($role) {
            $aliasMap = [
                'administrator' => 'admin',
                'owner' => 'admin',
                'superadmin' => 'admin',
                'mgr' => 'manager',
                'staff_member' => 'staff'
            ];
            $roleLower = strtolower($role);
            return $aliasMap[$roleLower] ?? $roleLower;
        }, $menuRoles);

        return in_array($normalizedUserRole, $normalizedMenuRoles);
    }

    public function render() {
        ?>
<!-- Sidebar - Pure Tailwind -->
<aside class="sidebar fixed top-0 left-0 h-screen bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 z-40 transform lg:translate-x-0 transition-transform duration-300">
    <!-- Logo Section -->
    <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-3 sidebar-brand">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                <i class="fas fa-hotel text-white text-lg"></i>
            </div>
            <div class="sidebar-brand-text">
                <h1 class="text-slate-900 dark:text-white font-bold text-lg leading-tight"><?php echo htmlspecialchars($this->hotelName); ?></h1>
                <p class="text-slate-500 dark:text-gray-400 text-xs">Property Management</p>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto py-6 px-3 max-h-[calc(100vh-250px)]">
        <ul class="space-y-1">
            <?php foreach ($this->menuItems as $item): ?>
                <?php if ($this->canUserAccessMenu($item['roles'])): ?>

                    <?php if (isset($item['divider']) && $item['divider']): ?>
                        <li class="my-4">
                            <div class="sidebar-divider h-px bg-gray-200 dark:bg-gray-800"></div>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($item['submenu']) && !empty($item['submenu'])): ?>
                        <!-- Dropdown Menu Item -->
                        <li class="dropdown-item">
                            <button type="button" onclick="toggleDropdown(this)" class="w-full dropdown-toggle flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 group">
                                <div class="icon-wrapper w-5 text-center">
                                    <i class="fas <?php echo htmlspecialchars($item['icon']); ?> text-lg"></i>
                                </div>
                                <span class="menu-label flex-1 font-medium text-sm text-left"><?php echo htmlspecialchars($item['label']); ?></span>
                                <i class="fas fa-chevron-down text-xs dropdown-arrow transition-transform"></i>
                            </button>

                            <ul class="dropdown-menu hidden pl-4 mt-1 space-y-1 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                <?php foreach ($item['submenu'] as $subitem): ?>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($subitem['url']); ?>" class="dropdown-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-slate-600 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200 text-sm">
                                            <i class="fas <?php echo htmlspecialchars($subitem['icon']); ?> text-base"></i>
                                            <span><?php echo htmlspecialchars($subitem['label']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Regular Menu Item -->
                        <li>
                            <a href="<?php echo htmlspecialchars($item['url']); ?>"
                               class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-slate-900 dark:hover:text-white transition-all duration-200 group <?php echo $this->currentPage === $item['id'] ? 'active bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border-l-4 border-blue-600 dark:border-blue-500' : ''; ?>">
                                <div class="icon-wrapper w-5 text-center">
                                    <i class="fas <?php echo htmlspecialchars($item['icon']); ?> text-lg"></i>
                                </div>

                                <span class="menu-label flex-1 font-medium text-sm"><?php echo htmlspecialchars($item['label']); ?></span>
                                <?php if ($this->currentPage === $item['id']): ?>
                                    <div class="active-indicator w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full"></div>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Footer Section -->
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
        <div class="ai-card bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-4 text-white">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-robot text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-sm">AI Assistant</h4>
                    <p class="text-xs opacity-90">Need help?</p>
                </div>
            </div>
            <button class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-sm font-medium py-2 rounded-lg transition-all duration-200">
                Ask AI
            </button>
        </div>
    </div>

</aside>

<!-- Mobile Overlay -->
<div class="sidebar-overlay fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden" onclick="toggleSidebar(false)"></div>

<style>
:root {
    --sidebar-expanded-width: 16rem;
    --sidebar-collapsed-width: 4.75rem;
}

.sidebar {
    width: var(--sidebar-expanded-width);
}

.menu-label,
.sidebar-brand-text,
.sidebar-divider-text {
    transition: opacity 0.2s ease;
}

/* Scrollbar Styling */
.sidebar nav::-webkit-scrollbar {
    width: 10px;
}

.sidebar nav::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.sidebar nav::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 6px;
    border: 2px solid #f1f5f9;
    transition: background 0.3s;
}

.sidebar nav::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

.dark .sidebar nav::-webkit-scrollbar-track {
    background: #1e293b;
}

.dark .sidebar nav::-webkit-scrollbar-thumb {
    background: #64748b;
    border-color: #1e293b;
}

.dark .sidebar nav::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Firefox Scrollbar */
.sidebar nav {
    scrollbar-width: thin;
    scrollbar-color: #94a3b8 #f1f5f9;
}

.dark .sidebar nav {
    scrollbar-color: #64748b #1e293b;
}

.sidebar.active {
    transform: translateX(0);
}

.sidebar.active + .sidebar-overlay,
.sidebar.active ~ .sidebar-overlay {
    display: block;
}

@media (max-width: 1023px) {
    .sidebar {
        transform: translateX(-100%);
    }
    .sidebar.active {
        transform: translateX(0);
    }
}

@media (min-width: 1024px) {
    body.sidebar-mini .sidebar {
        width: var(--sidebar-collapsed-width);
    }
    body.sidebar-mini .sidebar .sidebar-brand-text,
    body.sidebar-mini .sidebar .menu-label,
    body.sidebar-mini .sidebar .ai-card,
    body.sidebar-mini .sidebar .sidebar-divider,
    body.sidebar-mini .sidebar .active-indicator,
    body.sidebar-mini .sidebar .text-xs,
    body.sidebar-mini .sidebar p.text-gray-400 {
        display: none;
    }
    body.sidebar-mini .sidebar .nav-link {
        justify-content: center;
        gap: 0;
        padding: 0.75rem 0;
        border-left: none !important;
    }
    body.sidebar-mini .sidebar .icon-wrapper {
        width: 2.75rem;
        display: flex;
        justify-content: center;
    }
    body.sidebar-mini .topbar-offset {
        left: var(--sidebar-collapsed-width) !important;
    }
    body.sidebar-mini .content-offset {
        margin-left: var(--sidebar-collapsed-width) !important;
    }
}

body.sidebar-mini .sidebar-overlay {
    display: none !important;
}
</style>

<script>
// Sidebar toggle function
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (sidebar) {
        sidebar.classList.toggle('active');
    }

    if (overlay) {
        overlay.classList.toggle('hidden');
    }
}

// Dropdown toggle function
function toggleDropdown(button) {
    const menu = button.nextElementSibling;
    const arrow = button.querySelector('.dropdown-arrow');

    if (menu) {
        menu.classList.toggle('hidden');
        if (arrow) {
            arrow.style.transform = menu.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
        }
    }
}
</script>
        <?php
    }
}
?>
