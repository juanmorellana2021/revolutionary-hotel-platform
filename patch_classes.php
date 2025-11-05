<?php
$path = '/var/www/html/includes/classes.php';
$bak = $path . '.bak';
if (!file_exists($path)) {
    echo "File not found: $path\n";
    exit(1);
}
$orig = file_get_contents($path);
if ($orig === false) {
    echo "Failed to read $path\n";
    exit(1);
}
file_put_contents($bak, $orig);
$pattern = '/\$connection_methods\s*=\s*\[.*?\];/s';
$replacement = "\$connection_methods = [['hotelapp', 'hotel123']];";
$new = preg_replace($pattern, $replacement, $orig, 1, $count);
if ($new === null) {
    echo "Regex error\n";
    exit(1);
}
if ($count === 0) {
    echo "No $connection_methods block found to replace.\n";
    exit(0);
}
if (file_put_contents($path, $new) === false) {
    echo "Failed to write updated file\n";
    exit(1);
}
echo "Replaced $count connection_methods block(s) and backed up original to $bak\n";
// print the new block for verification
if (preg_match($pattern, $new, $m)) {
    echo "New block:\n" . $m[0] . "\n";
}
?>