<?php
/**
 * Batch Update Script to Apply Shared Header System
 * This script will update all major pages to use the new shared header/navbar/footer
 */

echo "<h2>🔧 Applying Shared Header System to All Pages</h2>\n";

// List of main pages to update
$pages = [
    'employee_management.php',
    'room_management.php', 
    'income_management.php',
    'expense_management.php',
    'calendar_view.php',
    'hotel_setup.php',
    'dashboard.php',
    'accounting_dashboard.php',
    'hotelcoin_admin.php',
    'wallet.php'
];

$updatedCount = 0;
$errorCount = 0;

foreach ($pages as $page) {
    if (!file_exists($page)) {
        echo "<p>⚠️ Skipping $page - file not found</p>\n";
        continue;
    }
    
    echo "<p>🔄 Processing $page...</p>\n";
    
    try {
        $content = file_get_contents($page);
        
        // Extract page title from existing title tag
        $pageTitle = 'Hotel Management';
        if (preg_match('/<title>(.*?) - .*?<\/title>/', $content, $matches)) {
            $pageTitle = trim($matches[1]);
        }
        
        // Replace the HTML head section
        $pattern = '/<!DOCTYPE html>.*?<\/head>/s';
        $replacement = "<?php \$pageTitle = '$pageTitle'; include 'includes/header.php'; ?>";
        $content = preg_replace($pattern, $replacement, $content);
        
        // Replace body opening and old navigation
        $content = preg_replace('/<body[^>]*>.*?<div class="container[^"]*">/s', 
            "<body>\n    <?php include 'includes/navbar.php'; ?>\n    <div class=\"main-content\">", $content);
        
        // Replace closing tags
        $content = preg_replace('/<\/body>\s*<\/html>/', 
            "    </div>\n    <?php include 'includes/footer.php'; ?>", $content);
        
        // Write updated content
        if (file_put_contents($page, $content)) {
            echo "<p>✅ Successfully updated $page</p>\n";
            $updatedCount++;
        } else {
            echo "<p>❌ Failed to write $page</p>\n";
            $errorCount++;
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error processing $page: " . $e->getMessage() . "</p>\n";
        $errorCount++;
    }
}

echo "<h3>📊 Summary:</h3>\n";
echo "<p>✅ Successfully updated: $updatedCount pages</p>\n";
echo "<p>❌ Errors: $errorCount pages</p>\n";
echo "<p><strong>Note:</strong> Manual review recommended for complex pages</p>\n";
echo "<h3>🧪 Next Steps:</h3>\n";
echo "<p>1. Test each updated page: <code>php -l filename.php</code></p>\n";
echo "<p>2. Check pages in browser for functionality</p>\n";
echo "<p>3. Commit changes to git once verified</p>\n";
?>