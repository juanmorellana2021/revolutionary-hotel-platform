<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

header('Content-Type: text/plain');

echo "=== MANAGER ACCESS TEST ===\n\n";

// Check session
echo "Session ID: " . session_id() . "\n";
echo "Session active: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "\n\n";

// Check if logged in
if (!isset($_SESSION['user'])) {
    echo "❌ NOT LOGGED IN - No user in session\n";
    echo "\nSession contents:\n";
    print_r($_SESSION);
    exit;
}

echo "✅ LOGGED IN\n";
echo "User ID: " . $_SESSION['user']['id'] . "\n";
echo "User Email: " . $_SESSION['user']['email'] . "\n";
echo "User Role: " . ($_SESSION['user']['role'] ?? 'not set') . "\n\n";

// Check manager status
$userManager = new UserManager();
$isManager = $userManager->isManager($_SESSION['user']['id']);

echo "Manager Check: " . ($isManager ? "✅ IS MANAGER" : "❌ NOT MANAGER") . "\n\n";

if ($isManager) {
    echo "🎉 SUCCESS! You can access WhatsApp management pages.\n";
    echo "\nYou can now access:\n";
    echo "- whatsapp_quick_start.php\n";
    echo "- whatsapp_management.php\n";
    echo "- whatsapp_setup_wizard.php\n";
} else {
    echo "⚠️ WARNING: You're logged in but not recognized as a manager.\n";
    echo "Checking database...\n\n";
    
    require_once 'db_connection.php';
    $stmt = $conn->prepare("SELECT id, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Database user info:\n";
    print_r($dbUser);
}
?>
