-- Hotel Booking System Database (Updated Version)
-- This version checks for existing tables before creating them

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS hotel_booking_system;
USE hotel_booking_system;

-- Drop existing tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('guest', 'manager', 'admin') DEFAULT 'guest',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    max_occupancy INT NOT NULL,
    amenities TEXT,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    guests INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Insert sample rooms
INSERT INTO rooms (room_number, room_type, price, max_occupancy, amenities) VALUES
('101', 'Standard Single', 75.00, 1, 'Free WiFi, Air Conditioning, TV'),
('102', 'Standard Double', 95.00, 2, 'Free WiFi, Air Conditioning, TV, Mini Fridge'),
('201', 'Deluxe Queen', 125.00, 2, 'Free WiFi, Air Conditioning, TV, Mini Fridge, Balcony'),
('202', 'Executive Suite', 175.00, 4, 'Free WiFi, Air Conditioning, TV, Mini Fridge, Balcony, Kitchenette'),
('301', 'Presidential Suite', 250.00, 6, 'Free WiFi, Air Conditioning, TV, Full Kitchen, Living Room, Jacuzzi');

-- Insert default manager account
INSERT INTO users (first_name, last_name, email, password, role) VALUES
('Hotel', 'Manager', 'manager@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager');

-- Note: The manager password is 'manager123' (hashed with PHP password_hash)