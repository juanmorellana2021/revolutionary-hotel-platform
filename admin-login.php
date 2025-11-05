<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: investor-admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db_connection.php';
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Successful login
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Update last login
                $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param('i', $admin['id']);
                $updateStmt->execute();
                
                header('Location: investor-admin.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch (Exception $e) {
            $error = 'Database error. Please try again.';
        }
    } else {
        $error = 'Please enter both username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AiniTravel - Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
        }

        .logo {
            text-align: center;
            font-size: 3rem;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        h1 {
            text-align: center;
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 40px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .error {
            background: #fee;
            border: 2px solid #fcc;
            color: #c33;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #999;
            font-size: 0.85rem;
        }

        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            color: #666;
            font-size: 0.9rem;
        }

        .security-badge::before {
            content: "🔒";
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🚀 AiniTravel</div>
        <h1>Admin Login</h1>
        <p class="subtitle">Investor Dashboard Access</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Login to Dashboard</button>
        </form>

        <div class="security-badge">
            Secure Admin Access
        </div>

        <div class="footer">
            AiniTravel &copy; 2025 | Confidential
        </div>
    </div>
</body>
</html>
