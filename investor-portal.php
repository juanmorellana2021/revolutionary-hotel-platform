<?php
session_start();

// Check if investor is logged in
if (!isset($_SESSION['investor_logged_in']) || !$_SESSION['investor_logged_in']) {
    header('Location: investor-login.php');
    exit;
}

require_once 'db_connection.php';

// Get investor details
$investor_email = $_SESSION['investor_email'];
$stmt = $conn->prepare("SELECT * FROM investor_ndas WHERE email = ? AND status = 'active' ORDER BY agreed_at DESC LIMIT 1");
$stmt->bind_param("s", $investor_email);
$stmt->execute();
$investor = $stmt->get_result()->fetch_assoc();

// Get activity log
$activity_stmt = $conn->prepare("SELECT * FROM investor_activity_log WHERE investor_id = ? ORDER BY created_at DESC LIMIT 10");
$activity_stmt->bind_param("i", $investor['id']);
$activity_stmt->execute();
$activities = $activity_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investor Portal - AiniTravel</title>
    <script>
        // Set NDA accepted flag immediately when portal loads
        localStorage.setItem('nda_accepted', 'true');
    </script>
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
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .header h1 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .header .welcome {
            color: #666;
            font-size: 1.1rem;
        }

        .logout-btn {
            float: right;
            padding: 10px 20px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover {
            background: #dc2626;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .card h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .card-btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .card-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .card-btn.secondary {
            background: #10b981;
        }

        .card-btn.secondary:hover {
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .info-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .info-section h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .info-item {
            padding: 15px;
            background: #f9fafb;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .info-item label {
            display: block;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-item value {
            display: block;
            color: #333;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .activity-log {
            margin-top: 20px;
        }

        .activity-item {
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item .action {
            color: #333;
            font-weight: 500;
        }

        .activity-item .time {
            color: #999;
            font-size: 0.9rem;
        }

        .download-section {
            margin-top: 20px;
            padding: 20px;
            background: #f0f4ff;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .download-section h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .download-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .download-link {
            padding: 10px 20px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            border: 2px solid #667eea;
            font-weight: 600;
            transition: all 0.3s;
        }

        .download-link:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="logout.php" class="logout-btn">Logout</a>
            <h1>🚀 AiniTravel Investor Portal</h1>
            <p class="welcome">Welcome back, <?php echo htmlspecialchars($investor['full_name']); ?>!</p>
        </div>

        <div class="grid">
            <div class="card">
                <div class="card-icon">📊</div>
                <h3>Investor Presentation</h3>
                <p>Interactive slide deck with our vision, market opportunity, financial projections, and team.</p>
                <a href="investors.html?authorized=true" class="card-btn" target="_blank">View Presentation</a>
            </div>

            <div class="card">
                <div class="card-icon">⚠️</div>
                <h3>Risk Analysis</h3>
                <p>Comprehensive investment risk assessment with mitigation strategies and sensitivity analysis.</p>
                <a href="INVESTMENT_RISK_ANALYSIS.md" class="card-btn secondary" target="_blank" download>Download Study</a>
            </div>

            <div class="card">
                <div class="card-icon">📧</div>
                <h3>Contact CEO</h3>
                <p>Have questions? Reach out directly to our founding team.</p>
                <a href="mailto:juan.ceo@ainitravel.com" class="card-btn">Email Juan</a>
            </div>
        </div>

        <div class="info-section">
            <h2>Your NDA Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Signed On</label>
                    <value><?php echo date('F j, Y', strtotime($investor['agreed_at'])); ?></value>
                </div>
                <div class="info-item">
                    <label>Company</label>
                    <value><?php echo htmlspecialchars($investor['company'] ?: 'Individual'); ?></value>
                </div>
                <div class="info-item">
                    <label>Investor Type</label>
                    <value><?php echo htmlspecialchars(ucfirst($investor['investor_type'])); ?></value>
                </div>
                <div class="info-item">
                    <label>Status</label>
                    <value style="color: #10b981;">✓ Active</value>
                </div>
            </div>

            <div class="download-section">
                <h3>📥 Download Materials</h3>
                <p style="color: #666; margin-bottom: 15px;">All investor documents available for download</p>
                <div class="download-links">
                    <a href="investors.html" class="download-link" download>💼 Pitch Deck (HTML)</a>
                    <a href="INVESTMENT_RISK_ANALYSIS.md" class="download-link" download>📊 Risk Analysis (PDF)</a>
                    <a href="investor-access.html?view=nda" class="download-link">📄 Your NDA Signature</a>
                </div>
            </div>

            <?php if (count($activities) > 0): ?>
            <div class="activity-log">
                <h3>Recent Activity</h3>
                <?php foreach ($activities as $activity): ?>
                <div class="activity-item">
                    <span class="action"><?php echo htmlspecialchars($activity['action']); ?>: <?php echo htmlspecialchars($activity['details']); ?></span>
                    <span class="time"><?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Set NDA accepted flag for presentation access
        localStorage.setItem('nda_accepted', 'true');
        
        // Log portal view
        fetch('log_investor_activity.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'portal_view',
                details: 'Investor viewed portal dashboard'
            })
        });
    </script>
</body>
</html>
