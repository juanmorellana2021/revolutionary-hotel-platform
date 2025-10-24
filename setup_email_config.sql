-- Create email_config table for storing SMTP settings
CREATE TABLE IF NOT EXISTS email_config (
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
    hotel_name VARCHAR(255) NOT NULL DEFAULT 'AiNi Hotel',
    hotel_address VARCHAR(255) NOT NULL DEFAULT '123 Main Street',
    hotel_city VARCHAR(255) NOT NULL DEFAULT 'Lima, Peru 15001',
    hotel_phone VARCHAR(50) NOT NULL DEFAULT '+51 1 234 5678',
    hotel_email VARCHAR(255) NOT NULL DEFAULT 'reservas@ainihotel.com',
    hotel_website VARCHAR(255) NOT NULL DEFAULT 'www.ainihotel.com',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default configuration (disabled by default)
INSERT INTO email_config (
    smtp_host, smtp_port, smtp_username, smtp_password, 
    from_email, from_name, reply_to, is_enabled
) VALUES (
    'smtp.gmail.com', 587, 'your-email@gmail.com', 'your-app-password',
    'reservas@ainihotel.com', 'AiNi Hotel', 'reservas@ainihotel.com', 0
) ON DUPLICATE KEY UPDATE id=id;
