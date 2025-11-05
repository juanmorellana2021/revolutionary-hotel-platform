<?php
// Fix the formatting issue in manager_dashboard.php
$file = '/var/www/html/manage/manager_dashboard.php';
$content = file_get_contents($file);

// Fix the concatenated lines
$content = str_replace(
    '$rooms = $roomManager->getRooms();$stats = $bookingManager->getBookingStats();',
    '$rooms = $roomManager->getRooms();' . "\n" . '$stats = $bookingManager->getBookingStats();',
    $content
);

file_put_contents($file, $content);
echo "Manager dashboard formatting fixed!";
?>