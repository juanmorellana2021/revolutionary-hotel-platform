-- Create table for per-hotel WhatsApp configuration
CREATE TABLE IF NOT EXISTS hotel_whatsapp_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    whatsapp_access_token TEXT,
    whatsapp_phone_number_id VARCHAR(50),
    whatsapp_business_phone VARCHAR(20),
    whatsapp_webhook_token VARCHAR(100) DEFAULT 'hotel_booking_webhook_2024',
    whatsapp_enabled TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_hotel (hotel_id),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index for fast lookups by phone number ID
CREATE INDEX idx_phone_number_id ON hotel_whatsapp_config(whatsapp_phone_number_id);
