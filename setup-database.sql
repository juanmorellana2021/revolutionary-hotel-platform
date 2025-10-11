-- Hotel Booking System Database Setup
-- Run this script after installing MySQL on your VPS

CREATE DATABASE IF NOT EXISTS hotel_booking_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace 'your_secure_password_here' with a strong password)
CREATE USER IF NOT EXISTS 'hotel_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON hotel_booking_system.* TO 'hotel_user'@'localhost';
FLUSH PRIVILEGES;

USE hotel_booking_system;

-- Show current database
SELECT DATABASE() as current_database;

-- The application will create all necessary tables automatically when first accessed
-- including: users, rooms, bookings, booking_extensions, booking_notes, booking_guests, income, etc.

SELECT 'Database setup complete! Update your PHP config files with these credentials.' as status;