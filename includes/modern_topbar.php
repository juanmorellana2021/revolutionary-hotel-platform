<?php
/**
 * Modern Top Bar Component
 * Reusable header with page title, currency toggle, and theme toggle
 * 
 * Required variables:
 * - $page_title: String with emoji and title (e.g., "💰 Financial Overview")
 * - $show_currency_toggle: Boolean (optional, default true)
 */

$show_currency_toggle = $show_currency_toggle ?? true;
?>

<!-- Top Bar with Title and Controls -->
<div class="top-bar">
    <div class="page-title">
        <span onclick="toggleSidebar()" style="cursor: pointer;">☰</span>
        <span><?php echo $page_title ?? '🏨 Hotel PMS'; ?></span>
    </div>
    <div class="controls">
        <?php if ($show_currency_toggle): ?>
        <button class="currency-toggle" onclick="toggleCurrency()">
            <span id="currency-symbol">S/.</span> 💱
        </button>
        <?php endif; ?>
        <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    </div>
</div>
