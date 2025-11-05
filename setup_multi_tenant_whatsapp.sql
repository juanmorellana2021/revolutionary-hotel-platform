-- Create hotels table for multi-tenant support
CREATE TABLE IF NOT EXISTS hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    address VARCHAR(500),
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100),
    postal_code VARCHAR(20),
    phone VARCHAR(50),
    email VARCHAR(255),
    website VARCHAR(255),
    star_rating TINYINT DEFAULT 3,
    check_in_time TIME DEFAULT '14:00:00',
    check_out_time TIME DEFAULT '11:00:00',
    currency_code VARCHAR(3) DEFAULT 'USD',
    timezone VARCHAR(50) DEFAULT 'America/New_York',
    logo_url VARCHAR(500),
    cover_image_url VARCHAR(500),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Samay Wasi as the first hotel
INSERT INTO hotels (id, name, slug, description, city, country, phone, email, star_rating, currency_code, timezone) 
VALUES (
    1,
    'Samay Wasi Hostel',
    'samay-wasi',
    'Boutique hostel in Cusco, Peru with comfortable rooms and excellent service',
    'Cusco',
    'Peru',
    '+51 984 123 456',
    'info@samaywasi.com',
    3,
    'PEN',
    'America/Lima'
)
ON DUPLICATE KEY UPDATE name=name;

-- Now create the WhatsApp config table
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
