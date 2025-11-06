<?php
// Temporarily disable financial report to fix manager dashboard
$file = '/var/www/html/manage/manager_dashboard.php';
$content = file_get_contents($file);

// Comment out the financial report line that's causing the issue
$content = str_replace(
    '$financialData = $financialReportManager->generateReport($startDate, $endDate);',
    '// $financialData = $financialReportManager->generateReport($startDate, $endDate); // Temporarily disabled
$financialData = []; // Empty array for now',
    $content
);

file_put_contents($file, $content);
echo "Financial report temporarily disabled to fix dashboard loading!";
?>