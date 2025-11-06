-- Hotel Setup Database Extension (Clean Version)
-- This extends the main database with hotel management features
USE hotel_booking_system;

-- Drop existing hotel tables if they exist
DROP TABLE IF EXISTS hotel_amenities;
DROP TABLE IF EXISTS hotel_services;
DROP TABLE IF EXISTS hotel_info;

-- Hotel information table
CREATE TABLE hotel_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(50) NOT NULL,
    state VARCHAR(50) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    country VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    website VARCHAR(100),
    description TEXT,
    check_in_time TIME DEFAULT '15:00:00',
    check_out_time TIME DEFAULT '11:00:00',
    total_rooms INT DEFAULT 0,
    star_rating INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Hotel services table
CREATE TABLE hotel_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hotel amenities table
CREATE TABLE hotel_amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amenity_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default hotel information (can be updated by manager)
INSERT INTO hotel_info (hotel_name, address, city, state, zip_code, country, phone, email, website, description, total_rooms) VALUES
('Grand Hotel & Resort', '123 Luxury Avenue', 'Miami', 'Florida', '33101', 'USA', '+1 (555) 123-4567', 'info@grandhotel.com', 'www.grandhotel.com', 'Experience luxury and comfort at our premier hotel located in the heart of Miami.', 50);

-- Insert default services
INSERT INTO hotel_services (service_name, is_active) VALUES
('24/7 Front Desk', TRUE),
('Room Service', TRUE),
('Housekeeping', TRUE),
('Concierge Service', TRUE),
('Wake-up Calls', TRUE),
('Luggage Storage', TRUE),
('Express Check-in/out', TRUE),
('Business Center', TRUE),
('Valet Parking', FALSE),
('Airport Shuttle', FALSE),
('Laundry Service', TRUE),
('Car Rental', FALSE);

-- Insert default amenities
INSERT INTO hotel_amenities (amenity_name, is_active) VALUES
('Free WiFi', TRUE),
('Swimming Pool', TRUE),
('Fitness Center', TRUE),
('Spa & Wellness', TRUE),
('Restaurant', TRUE),
('Bar/Lounge', TRUE),
('Parking', TRUE),
('Pet Friendly', FALSE),
('Airport Shuttle', FALSE),
('Meeting Rooms', TRUE),
('Laundry Facilities', TRUE),
('Safe Deposit Box', TRUE),
('ATM/Banking', FALSE),
('Gift Shop', FALSE);