<?php
// Fix MySQL connection sync issues in accounting classes
$file = '/var/www/html/manage/includes/accounting_classes.php';
$content = file_get_contents($file);

// Add proper result closing after each query
$content = str_replace(
    '$result = $stmt->get_result();',
    '$result = $stmt->get_result();
        if ($stmt) $stmt->close();',
    $content
);

// Also fix any unclosed result sets
$content = str_replace(
    'return $result->fetch_assoc();',
    '$data = $result->fetch_assoc();
        if ($result) $result->close();
        return $data;',
    $content
);

file_put_contents($file, $content);
echo "MySQL connection sync issues fixed in accounting classes!";
?>