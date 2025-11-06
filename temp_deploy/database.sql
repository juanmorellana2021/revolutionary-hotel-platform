-- Hotel Booking System Database
-- Run this in phpMyAdmin

CREATE DATABASE IF NOT EXISTS hotel_booking_system;
USE hotel_booking_system;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Sample rooms data
INSERT INTO rooms (room_number, room_type, capacity, price_per_night, description) VALUES
('101', 'Standard Single', 1, 89.99, 'Comfortable single room with city view'),
('102', 'Standard Double', 2, 129.99, 'Spacious double room with queen bed'),
('201', 'Deluxe Suite', 4, 299.99, 'Luxury suite with separate living area'),
('202', 'Family Room', 6, 199.99, 'Perfect for families with connecting rooms'),
('301', 'Presidential Suite', 8, 499.99, 'Ultimate luxury with panoramic views');