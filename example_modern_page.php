<?php
/**
 * Example Page Using Modern Theme Components
 * Shows how easy it is to create consistent pages
 */
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    header('Location: index.php');
    exit;
}

// Set page title for topbar
$page_title = "📊 Example Dashboard";
$show_currency_toggle = true; // Show currency toggle
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo strip_tags($page_title); ?></title>
    
    <!-- Include Modern Theme Styles -->
    <?php include 'includes/modern_theme_styles.php'; ?>
    
    <!-- Page-specific styles can go here -->
    <style>
        .example-card {
            background: rgba(30, 41, 59, 0.8);
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
        }
        
        body.light-theme .example-card {
            background: white;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body class="dark-theme">
    
    <!-- Include Modern Sidebar -->
    <?php include 'includes/modern_sidebar.php'; ?>
    
    <div class="container">
        <!-- Include Modern Top Bar -->
        <?php include 'includes/modern_topbar.php'; ?>
        
        <!-- Your page content here -->
        <div class="example-card">
            <h2>Welcome to the Modern Theme System!</h2>
            <p>This page demonstrates how easy it is to create consistent pages using our theme components.</p>
            <br>
            <h3>How to use:</h3>
            <ol>
                <li>Include modern_theme_styles.php in &lt;head&gt;</li>
                <li>Include modern_sidebar.php after &lt;body&gt;</li>
                <li>Wrap content in &lt;div class="container"&gt;</li>
                <li>Include modern_topbar.php (set $page_title first)</li>
                <li>Include modern_theme_scripts.php before &lt;/body&gt;</li>
            </ol>
            <br>
            <p><strong>That's it!</strong> You get:</p>
            <ul>
                <li>✅ Consistent sidebar navigation</li>
                <li>✅ Dark/Light theme toggle</li>
                <li>✅ Currency toggle (optional)</li>
                <li>✅ Responsive sidebar collapse</li>
                <li>✅ localStorage persistence</li>
            </ul>
        </div>
        
        <div class="example-card">
            <h3>Currency Amount Example:</h3>
            <p>Use the currency-amount class with data attributes:</p>
            <div style="font-size: 24px; margin: 20px 0;">
                <span class="currency-amount" 
                      data-pen="350.00" 
                      data-usd="100.00">
                    S/. 350.00
                </span>
            </div>
            <p><small>Click the currency toggle button to switch!</small></p>
        </div>
    </div>
    
    <!-- Include Modern Theme Scripts -->
    <?php include 'includes/modern_theme_scripts.php'; ?>
</body>
</html>
