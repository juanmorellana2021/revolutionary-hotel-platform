<?php
session_start();
require_once 'classes.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'] ?? 'admin';
            $_SESSION['user_type'] = $user['user_type'] ?? 'owner';
            
            // Get user's first hotel or default to 1
            $stmt = $conn->prepare("SELECT hotel_id FROM user_hotel_access WHERE user_id = ? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$user['id']]);
            $access = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['current_hotel_id'] = $access ? $access['hotel_id'] : $user['current_hotel_id'] ?? 1;
            
            // Redirect based on user type
            if ($user['user_type'] === 'owner') {
                header('Location: /owner_account.php');
            } elseif ($user['role'] === 'admin' || $user['role'] === 'manager') {
                header('Location: /hotel_setup.php');
            } else {
                header('Location: /dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AiNi Travel - Owner Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: flex;
        }
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-left h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .login-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        .login-left .features {
            list-style: none;
            padding: 0;
        }
        .login-left .features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .login-left .features i {
            margin-right: 15px;
            font-size: 1.3rem;
        }
        .login-right {
            flex: 1;
            padding: 60px 50px;
        }
        .login-right h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 30px;
        }
        .form-control {
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            margin-bottom: 20px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .divider {
            text-align: center;
            margin: 25px 0;
            color: #999;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .alert {
            border-radius: 10px;
        }
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            .login-left {
                padding: 40px 30px;
            }
            .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div>
                <h1><i class="fas fa-hotel"></i> AiNi Travel</h1>
                <p>Manage your properties with ease</p>
                <ul class="features">
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Multi-property management</span>
                    </li>
                    <li>
                        <i class="fas fa-chart-line"></i>
                        <span>Real-time analytics & reports</span>
                    </li>
                    <li>
                        <i class="fas fa-users"></i>
                        <span>Employee time tracking</span>
                    </li>
                    <li>
                        <i class="fas fa-calendar-alt"></i>
                        <span>Booking management</span>
                    </li>
                    <li>
                        <i class="fas fa-globe"></i>
                        <span>Support for 13+ property types</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="login-right">
            <h2>Welcome Back</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="divider">
                <span>or</span>
            </div>
            
            <div class="register-link">
                <p>Don't have an account? <a href="owner_registration.php">Register Your Property</a></p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
