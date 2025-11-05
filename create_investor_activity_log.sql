CREATE TABLE IF NOT EXISTS investor_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investor_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investor_id) REFERENCES investor_ndas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
