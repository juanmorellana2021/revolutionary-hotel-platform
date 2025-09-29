<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - Simple Navigation Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .nav-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .test-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="nav-header">
        <h1>🏨 Manager Dashboard - Navigation Test</h1>
        <div class="nav-links">
            <a href="ai_admin.php">🤖 AI Configuration</a>
            <a href="whatsapp_management.php">📱 WhatsApp Management</a>
            <a href="whatsapp_setup_wizard.php">🚀 WhatsApp Setup</a>
            <a href="travel_social.php">🌍 Travel Social</a>
            <a href="public_booking.php">🌐 Public Booking</a>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Navigation Test</h2>
        <p>Click on any link above. If they don't work, there's a server/browser issue.</p>
        <p>If they DO work, then the issue is with the full manager dashboard CSS/JS.</p>
        
        <h3>Direct URL Tests:</h3>
        <ul>
            <li><a href="http://localhost/hotel-booking-system/ai_admin.php" target="_blank">AI Admin (New Tab)</a></li>
            <li><a href="http://localhost/hotel-booking-system/whatsapp_management.php" target="_blank">WhatsApp Management (New Tab)</a></li>
            <li><a href="http://localhost/hotel-booking-system/travel_social.php" target="_blank">Travel Social (New Tab)</a></li>
        </ul>
        
        <p><strong>If the "New Tab" links work but the navigation doesn't, it's a CSS/JS issue.</strong></p>
        <p><strong>If nothing works, it's a server/browser issue.</strong></p>
    </div>
    
    <div class="test-section">
        <h3>Return to Full Dashboard</h3>
        <a href="manager_dashboard.php">← Back to Full Manager Dashboard</a>
    </div>
    
    <script>
        console.log('Simple navigation test loaded successfully');
        
        // Log all link clicks
        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                console.log('Link clicked:', this.href);
                console.log('Target:', this.target);
            });
        });
    </script>
</body>
</html>