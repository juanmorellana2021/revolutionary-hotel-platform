<?php
require_once 'db_connection.php';

echo "=== AiniTravel Admin Account Setup ===\n\n";

// Your password - CHANGE THIS!
$password = 'AiniTravel2025!';

// Hash the password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Create tables first
    echo "Creating tables...\n";
    
    $conn->query("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'admin', 'viewer') DEFAULT 'admin',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            INDEX idx_username (username)
        )
    ");
    
    $conn->query("
        CREATE TABLE IF NOT EXISTS investor_ndas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            company VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            investor_type ENUM('angel', 'vc', 'pe', 'corporate', 'family-office', 'other') NOT NULL,
            signature_path VARCHAR(500) NOT NULL,
            agreed_at DATETIME NOT NULL,
            ip_address VARCHAR(100),
            status ENUM('nda_signed', 'presentation_viewed', 'meeting_scheduled', 'due_diligence', 'term_sheet', 'closed', 'passed') DEFAULT 'nda_signed',
            notes TEXT,
            last_activity DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        )
    ");
    
    $conn->query("
        CREATE TABLE IF NOT EXISTS investor_activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            investor_id INT NOT NULL,
            activity_type ENUM('nda_signed', 'presentation_viewed', 'presentation_downloaded', 'email_sent', 'meeting_scheduled', 'note_added', 'status_changed') NOT NULL,
            description TEXT,
            metadata JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (investor_id) REFERENCES investor_ndas(id) ON DELETE CASCADE,
            INDEX idx_investor (investor_id),
            INDEX idx_activity (activity_type)
        )
    ");
    
    echo "✓ Tables created successfully!\n\n";
    
    // Check if admin already exists
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = 'juan.ceo'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->fetch_assoc()) {
        echo "Admin user 'juan.ceo' already exists. Updating password...\n";
        $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ? WHERE username = 'juan.ceo'");
        $stmt->bind_param('s', $passwordHash);
        $stmt->execute();
        echo "✓ Password updated!\n\n";
    } else {
        echo "Creating admin user...\n";
        $stmt = $conn->prepare("
            INSERT INTO admin_users (username, password_hash, full_name, email, role) 
            VALUES ('juan.ceo', ?, 'Juan Morellana', 'juan.ceo@ainitravel.com', 'super_admin')
        ");
        $stmt->bind_param('s', $passwordHash);
        $stmt->execute();
        echo "✓ Admin user created!\n\n";
    }
    
    echo "=== SETUP COMPLETE ===\n\n";
    echo "Login Credentials:\n";
    echo "Username: juan.ceo\n";
    echo "Password: $password\n\n";
    echo "Login URL: http://ainitravel.com/admin-login.php\n\n";
    echo "⚠️  IMPORTANT: Change your password after first login!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
