<?php
// Fix corrupted emojis in manager_dashboard.php
$file = '/var/www/html/manager_dashboard.php';
$content = file_get_contents($file);

// Replace corrupted emoji placeholders with proper UTF-8 emojis
$replacements = [
    ' Welcome to Hotel Management!' => '🏨 Welcome to Hotel Management!',
    '<div class="banner-icon"></div>' => '<div class="banner-icon">🎯</div>',
    '<h3> Revolutionary Feature Available!</h3>' => '<h3>⭐ Revolutionary Feature Available!</h3>',
    'style="margin-left: 1rem; background: rgba(255,255,255,0.1);"> Full Setup</a>' => 'style="margin-left: 1rem; background: rgba(255,255,255,0.1);">🔧 Full Setup</a>',
    '<h2 class="h4 mb-0"> Recent Bookings</h2>' => '<h2 class="h4 mb-0">📅 Recent Bookings</h2>',
    ' Setup Hotel Info' => '⚙️ Setup Hotel Info',
    ' View Guest Experience' => '🎨 View Guest Experience',
    ' Refresh Dashboard' => '🔄 Refresh Dashboard',
    '<h3 class="h5 mb-0"> Recent Users</h3>' => '<h3 class="h5 mb-0">👥 Recent Users</h3>',
    '<h2 class="h4 mb-0"> Room Overview</h2>' => '<h2 class="h4 mb-0">🛏️ Room Overview</h2>',
];

// Also need to fix the stat card icons - they appear as empty divs
$content = preg_replace(
    '/<div class="h1 mb-3"><\/div>/',
    '<div class="h1 mb-3">📊</div>',
    $content,
    5  // Replace first 5 occurrences with a generic chart icon
);

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

// Backup original
copy($file, $file . '.before_emoji_fix');

// Write fixed content
file_put_contents($file, $content);

echo "✅ Fixed emojis in manager_dashboard.php\n";
echo "📁 Backup saved to: {$file}.before_emoji_fix\n";
?>
