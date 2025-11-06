<?php
// Fix navbar.php UserManager issue
$file = '/var/www/html/manage/includes/navbar.php';
$content = file_get_contents($file);

// Replace the problematic line
$content = str_replace(
    '$isManager = isset($_SESSION[\'user\']) && $userManager->isManager($_SESSION[\'user\'][\'id\']);',
    '$isManager = isset($_SESSION[\'user\']) && (($_SESSION[\'user\'][\'role\'] ?? \'guest\') === \'manager\' || ($_SESSION[\'user\'][\'role\'] ?? \'guest\') === \'admin\');',
    $content
);

file_put_contents($file, $content);
echo "Navbar UserManager issue fixed!";
?>