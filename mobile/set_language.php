<?php
/**
 * Set language preference (works for guests + logged-in users)
 */
session_start();

$valid = ['es','en','pt','fr','de','it','zh','ja'];
$lang  = $_GET['lang'] ?? 'es';
if (!in_array($lang, $valid)) $lang = 'es';

$_SESSION['lang'] = $lang;

// If logged in, persist to DB too
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../db_connection_pdo.php';
    try {
        $stmt = $pdo->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
        $stmt->execute([$lang, $_SESSION['user_id']]);
    } catch(Exception $e) { /* silently fail */ }
}

$redirect = $_GET['redirect'] ?? '/mobile/';
header('Location: ' . $redirect);
exit();
?>
