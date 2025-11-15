<?php
/**
 * 🛡️ SECURE PHP FORM TEMPLATE
 * 
 * This template demonstrates all security best practices:
 * - CSRF protection
 * - XSS prevention (htmlspecialchars)
 * - SQL injection prevention (prepared statements)
 * - Input validation
 * - Secure session handling
 */

// ==================== SECURE SESSION CONFIGURATION ====================

session_start([
    'cookie_httponly' => true,        // Prevent JavaScript access
    'cookie_secure' => true,          // HTTPS only (set to false for localhost)
    'cookie_samesite' => 'Strict',    // Prevent CSRF
    'use_strict_mode' => true,        // Regenerate session IDs
    'cookie_lifetime' => 0,           // Session expires on browser close
]);

// ==================== CSRF TOKEN GENERATION ====================

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==================== AUTHENTICATION CHECK ====================

if (!isset($_SESSION['user_phone'])) {
    header('Location: login.php');
    exit;
}

$userPhone = $_SESSION['user_phone'];

// ==================== HANDLE FORM SUBMISSION ====================

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. VALIDATE CSRF TOKEN
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid security token. Please refresh and try again.');
    }
    
    // 2. VALIDATE INPUTS
    $amount = $_POST['amount'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Amount validation
    if (empty($amount) || !is_numeric($amount)) {
        $errors[] = 'Please enter a valid amount';
    } elseif ($amount <= 0) {
        $errors[] = 'Amount must be greater than 0';
    } elseif ($amount > 100000) {
        $errors[] = 'Amount cannot exceed 100,000';
    }
    
    // Description validation
    if (strlen($description) > 500) {
        $errors[] = 'Description too long (max 500 characters)';
    }
    
    // 3. PROCESS IF NO ERRORS
    if (empty($errors)) {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=ainiflow', 'username', 'password');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Sanitize inputs
            $amount = floatval($amount);  // Convert to number
            $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');  // Prevent XSS
            
            // Use prepared statement (prevents SQL injection)
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_phone, amount, description, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([$userPhone, $amount, $description]);
            
            $success = true;
            
            // Regenerate CSRF token after successful submission
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());  // Log error
            $errors[] = 'An error occurred. Please try again.';  // Don't expose details
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';">
    <title>Secure Form Example</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        input, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<h1>🛡️ Secure Form Example</h1>

<!-- Display Errors -->
<?php if (!empty($errors)): ?>
    <div class="error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Display Success -->
<?php if ($success): ?>
    <div class="success">
        Transaction successful!
    </div>
<?php endif; ?>

<!-- Secure Form -->
<form method="POST" action="">
    
    <!-- CSRF Token (hidden field) -->
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    
    <label for="amount">Amount (coins):</label>
    <input 
        type="number" 
        id="amount" 
        name="amount" 
        min="1" 
        max="100000" 
        step="0.01" 
        required
        value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount'], ENT_QUOTES, 'UTF-8') : ''; ?>"
    >
    
    <label for="description">Description:</label>
    <textarea 
        id="description" 
        name="description" 
        maxlength="500" 
        rows="4"
    ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
    
    <button type="submit">Submit Transaction</button>
</form>

<hr>

<h3>🔒 Security Features in This Form:</h3>
<ul>
    <li>✅ CSRF token validation</li>
    <li>✅ XSS prevention (htmlspecialchars on all output)</li>
    <li>✅ SQL injection prevention (prepared statements)</li>
    <li>✅ Input validation (type, min, max, length)</li>
    <li>✅ Secure session configuration</li>
    <li>✅ Error messages don't expose sensitive info</li>
    <li>✅ Content Security Policy header</li>
</ul>

</body>
</html>
