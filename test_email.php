<?php
session_start();
require_once __DIR__ . '/includes/classes.php';
require_once __DIR__ . '/includes/hotel_classes.php';
require_once __DIR__ . '/includes/ReceiptEmailSender.php';
require_once __DIR__ . '/config/EmailConfig.php';

// Database connection
$database = new Database();
$connection = $database->getConnection();

// Get the most recent booking
$stmt = $connection->prepare("
    SELECT b.*, r.room_number, r.room_type, r.price as room_price,
           u.first_name, u.last_name, u.email, u.phone,
           GROUP_CONCAT(DISTINCT CONCAT(bg.guest_name, '|', COALESCE(bg.guest_email, ''), '|', COALESCE(bg.guest_phone, ''), '|', bg.is_primary) SEPARATOR ';;;') as all_guests
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN users u ON b.user_id = u.id 
    LEFT JOIN booking_guests bg ON b.id = bg.booking_id
    WHERE b.id IS NOT NULL
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT 1
");
$stmt->execute();
$booking = $stmt->fetch();

if (!$booking) {
    die('No bookings found in database');
}

echo "<h2>📧 Testing Email System</h2>";
echo "<h3>Email Configuration Status:</h3>";
echo "<ul>";
echo "<li><strong>Configured:</strong> " . (EmailConfig::isConfigured() ? '✅ Yes' : '❌ No - Please update config/EmailConfig.php') . "</li>";
echo "<li><strong>SMTP Host:</strong> " . EmailConfig::SMTP_HOST . "</li>";
echo "<li><strong>SMTP Port:</strong> " . EmailConfig::SMTP_PORT . "</li>";
echo "<li><strong>From Email:</strong> " . EmailConfig::FROM_EMAIL . "</li>";
echo "</ul>";

echo "<h3>Found Booking:</h3>";
echo "<ul>";
echo "<li><strong>Reference:</strong> " . htmlspecialchars($booking['booking_reference']) . "</li>";
echo "<li><strong>Guest:</strong> " . htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) . "</li>";
echo "<li><strong>Email:</strong> " . htmlspecialchars($booking['email']) . "</li>";
echo "<li><strong>Room:</strong> " . htmlspecialchars($booking['room_number']) . "</li>";
echo "</ul>";

if (EmailConfig::isConfigured()) {
    echo '<h3>Actions:</h3>';
    echo '<form method="post" style="margin: 20px 0;">';
    echo '<label>Send to email: <input type="email" name="test_email" value="' . htmlspecialchars($booking['email']) . '" style="padding: 5px; width: 300px;"></label><br><br>';
    echo '<button type="submit" name="send_email" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">📧 Send Test Email</button>';
    echo '</form>';
    
    if (isset($_POST['send_email'])) {
        $testEmail = $_POST['test_email'] ?? $booking['email'];
        
        echo '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;">';
        echo '⏳ Sending email to ' . htmlspecialchars($testEmail) . '...<br>';
        
        $emailSender = new ReceiptEmailSender();
        $result = $emailSender->sendReceiptEmail($booking, $testEmail);
        
        if ($result['success']) {
            echo '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 10px;">';
            echo '✅ ' . htmlspecialchars($result['message']);
            echo '</div>';
        } else {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 10px;">';
            echo '❌ ' . htmlspecialchars($result['message']);
            echo '<br><br><strong>Troubleshooting:</strong>';
            echo '<ul>';
            echo '<li>Check that SMTP credentials are correct in config/EmailConfig.php</li>';
            echo '<li>For Gmail: Make sure you\'re using an App Password, not your regular password</li>';
            echo '<li>Check server error logs: /var/log/apache2/error.log</li>';
            echo '</ul>';
            echo '</div>';
        }
        
        echo '</div>';
    }
} else {
    echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">';
    echo '<h4>⚠️ Email Not Configured</h4>';
    echo '<p>To enable email functionality:</p>';
    echo '<ol>';
    echo '<li>Open <code>config/EmailConfig.php</code></li>';
    echo '<li>Update SMTP_USERNAME and SMTP_PASSWORD with your email credentials</li>';
    echo '<li>For Gmail: Enable 2FA and create an App Password</li>';
    echo '<li>Save the file and refresh this page</li>';
    echo '</ol>';
    echo '</div>';
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; }
    h2, h3 { color: #333; }
    ul { line-height: 1.8; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>
