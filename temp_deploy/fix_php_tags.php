<?php
// Fix the PHP tag issue in manager_dashboard.php
$file = '/var/www/html/manage/manager_dashboard.php';
$content = file_get_contents($file);

// Remove the erroneous closing tag and ensure PHP continues
$content = str_replace(
    '];' . "\n" . '?>' . "\n" . '// Get financial data',
    '];' . "\n" . "\n" . '// Get financial data',
    $content
);

file_put_contents($file, $content);
echo "PHP tag issue fixed!";
?>