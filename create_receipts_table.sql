-- Create receipts table to log all receipt generations
CREATE TABLE IF NOT EXISTS receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    receipt_type ENUM('view', 'pdf', 'print', 'email') NOT NULL,
    guest_name VARCHAR(255),
    guest_email VARCHAR(255),
    total_amount DECIMAL(10,2),
    payment_status VARCHAR(50),
    generated_by INT, -- user_id who generated the receipt
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    email_sent_to VARCHAR(255) NULL,
    email_sent_at DATETIME NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id),
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample query to get receipt history
-- SELECT r.*, b.booking_reference, u.first_name, u.last_name
-- FROM receipts r
-- JOIN bookings b ON r.booking_id = b.id
-- LEFT JOIN users u ON r.generated_by = u.id
-- ORDER BY r.generated_at DESC;
