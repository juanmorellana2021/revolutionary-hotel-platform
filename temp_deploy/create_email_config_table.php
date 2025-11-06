<?php
// Quick script to create email_config table
require_once 'includes/classes.php';

$database = new Database();
$conn = $database->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS email_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    smtp_host VARCHAR(255) NOT NULL DEFAULT 'smtp.gmail.com',
    smtp_port INT NOT NULL DEFAULT 587,
    smtp_username VARCHAR(255) NOT NULL,
    smtp_password VARCHAR(255) NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) NOT NULL DEFAULT 'AiNi Hotel',
    reply_to VARCHAR(255) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    use_ssl TINYINT(1) NOT NULL DEFAULT 0,
    use_tls TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $conn->exec($sql);
    echo "✅ Table email_config created successfully!<br><br>";
    
    // Check if there's already a config
    $stmt = $conn->query("SELECT COUNT(*) FROM email_config");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert default config
        $insert = "INSERT INTO email_config (
            smtp_host, smtp_port, smtp_username, smtp_password, 
            from_email, from_name, reply_to, is_enabled
        ) VALUES (
            'smtp.gmail.com', 587, 'your-email@gmail.com', 'your-app-password',
            'reservas@ainihotel.com', 'AiNi Hotel', 'reservas@ainihotel.com', 0
        )";
        $conn->exec($insert);
        echo "✅ Default email configuration inserted!<br><br>";
    } else {
        echo "ℹ️ Email configuration already exists.<br><br>";
    }
    
    echo "<a href='hotel_setup.php'>Go to Hotel Setup</a>";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
