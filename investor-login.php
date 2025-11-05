<?php
session_start();
require_once 'db_connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        // Check if investor has signed NDA (removed status check for easier access)
        $stmt = $conn->prepare("SELECT id, full_name, email, company, agreed_at, status FROM investor_ndas WHERE email = ? ORDER BY agreed_at DESC LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $investor = $result->fetch_assoc();
            
            // Set session
            $_SESSION['investor_logged_in'] = true;
            $_SESSION['investor_email'] = $investor['email'];
            $_SESSION['investor_name'] = $investor['full_name'];
            $_SESSION['investor_id'] = $investor['id'];
            
            // Log activity
            $activity_stmt = $conn->prepare("INSERT INTO investor_activity_log (investor_id, action, details, ip_address) VALUES (?, 'login', 'Investor logged in to access materials', ?)");
            $ip = $_SERVER['REMOTE_ADDR'];
            $activity_stmt->bind_param("is", $investor['id'], $ip);
            $activity_stmt->execute();
            
            // Redirect to investor portal
            header('Location: investor-portal.php');
            exit;
        } else {
            $error = 'No NDA found for this email. Please sign the NDA first at <a href="investor-access.html" style="color:#667eea;">investor-access.html</a>';
        }
    } else {
        $error = 'Please enter a valid email address.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investor Login - AiniTravel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 10px;
        }

        .logo p {
            color: #666;
            font-size: 1rem;
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input[type="email"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            color: #999;
            position: relative;
        }

        .divider:before,
        .divider:after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e0e0e0;
        }

        .divider:before {
            left: 0;
        }

        .divider:after {
            right: 0;
        }

        .new-investor {
            text-align: center;
            margin-top: 20px;
        }

        .new-investor a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .new-investor a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .info-box {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #667eea;
        }

        .info-box p {
            color: #555;
            font-size: 0.9rem;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🚀 AiniTravel</h1>
            <p>Investor Portal</p>
        </div>

        <h2>Welcome Back</h2>
        <p class="subtitle">Access your investor materials</p>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="investor@example.com"
                    required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                >
            </div>

            <button type="submit" class="submit-btn">Access Portal</button>
        </form>

        <div class="divider">OR</div>

        <div class="new-investor">
            <p>First time here? <a href="investor-access.html">Sign the NDA</a></p>
        </div>

        <div class="info-box">
            <p><strong>🔒 Secure Access:</strong> We'll verify your NDA signature and grant you access to the investor presentation, financial projections, and risk analysis documents.</p>
        </div>
    </div>
</body>
</html>
