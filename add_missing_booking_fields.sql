-- Add missing currency and other booking fields from XAMPP to VPS
ALTER TABLE bookings ADD COLUMN selected_currency ENUM('USD','PEN') DEFAULT 'USD';

-- Add other missing booking fields that might be needed
ALTER TABLE bookings ADD COLUMN booking_reference VARCHAR(100) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN booking_source VARCHAR(50) DEFAULT 'direct';
ALTER TABLE bookings ADD COLUMN sync_status ENUM('pending','synced','failed') DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN synced_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN guest_name VARCHAR(255) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN guest_email VARCHAR(255) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN guest_phone VARCHAR(50) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN passport_number VARCHAR(50) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN id_number VARCHAR(50) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN client_photo VARCHAR(255) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN special_requests TEXT DEFAULT NULL;

-- Verify the additions
SELECT 'BOOKING FIELDS SYNCHRONIZED' as status;
SHOW COLUMNS FROM bookings LIKE '%currency%';