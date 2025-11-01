<?php
session_start();
require_once 'classes.php';

echo "<h2>Login Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "Email: " . htmlspecialchars($email) . "<br>";
    echo "Password entered: " . (empty($password) ? "NO" : "YES") . "<br>";
    
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Database connected: YES<br>";
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User found: " . ($user ? "YES (ID: " . $user['id'] . ")" : "NO") . "<br>";
    
    if ($user) {
        echo "Password hash exists: " . (!empty($user['password']) ? "YES" : "NO") . "<br>";
        $verify = password_verify($password, $user['password']);
        echo "Password verify result: " . ($verify ? "SUCCESS" : "FAILED") . "<br>";
        
        if ($verify) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'] ?? 'admin';
            $_SESSION['user_type'] = $user['user_type'] ?? 'owner';
            
            $stmt = $conn->prepare("SELECT hotel_id FROM user_hotel_access WHERE user_id = ? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$user['id']]);
            $access = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['current_hotel_id'] = $access ? $access['hotel_id'] : ($user['current_hotel_id'] ?? 1);
            
            echo "<br><strong style='color:green'>LOGIN SUCCESSFUL!</strong><br>";
            echo "Session user_id: " . $_SESSION['user_id'] . "<br>";
            echo "Session user_role: " . $_SESSION['user_role'] . "<br>";
            echo "Session user_type: " . $_SESSION['user_type'] . "<br>";
            echo "Session current_hotel_id: " . $_SESSION['current_hotel_id'] . "<br>";
            echo "<br><a href='/hotel_setup.php'>Go to Hotel Setup</a>";
        }
    }
} else {
    ?>
    <form method="POST">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Test Login</button>
    </form>
    <?php
}
?>
