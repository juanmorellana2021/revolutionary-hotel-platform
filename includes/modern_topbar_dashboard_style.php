<?php
/**
 * Modern Top Bar Component - Dashboard Style
 * Reusable header matching dashboard.php style with search box and user info
 * 
 * Optional variables:
 * - $show_currency_toggle: Boolean (default false - currency toggle moved to content area)
 * - $search_placeholder: String (default "Search...")
 */

$show_currency_toggle = $show_currency_toggle ?? false;
$search_placeholder = $search_placeholder ?? "Search...";
?>

<!-- Top Bar with Search and User Info -->
<div class="top-bar">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="<?php echo htmlspecialchars($search_placeholder); ?>">
    </div>
    
    <div style="display: flex; align-items: center;">
        <?php if ($show_currency_toggle): ?>
        <button class="currency-toggle" onclick="toggleCurrency()" title="Toggle Currency" style="margin-right: 1rem;">
            <span id="currency-symbol">S/.</span> 💱
        </button>
        <?php endif; ?>
        
        <div class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark/Light Theme">
            <i class="fas fa-sun" id="theme-icon"></i>
        </div>
        
        <div class="user-info">
            <div>
                <div style="text-align: right; margin-bottom: 0.25rem;">
                    <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Manager'); ?></strong>
                </div>
                <div style="font-size: 0.875rem; color: #64748b;">
                    <?php echo ucfirst($_SESSION['user_role'] ?? 'Manager'); ?>
                </div>
            </div>
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'M', 0, 1)); ?>
            </div>
        </div>
    </div>
</div>
