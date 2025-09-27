-- Add hotel information and manager tables to existing database
USE hotel_booking_system;

-- Hotel information table
CREATE TABLE hotel_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_name VARCHAR(200) NOT NULL,
    hotel_description TEXT,
    address_line1 VARCHAR(200),
    address_line2 VARCHAR(200),
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    country VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(200),
    check_in_time TIME DEFAULT '15:00:00',
    check_out_time TIME DEFAULT '11:00:00',
    total_rooms INT DEFAULT 0,
    hotel_rating DECIMAL(2,1) DEFAULT 0.0,
    hotel_logo VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Hotel services table
CREATE TABLE hotel_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    service_description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    service_icon VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hotel amenities table
CREATE TABLE hotel_amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amenity_name VARCHAR(100) NOT NULL,
    amenity_description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    amenity_icon VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add manager role to users table
ALTER TABLE users ADD COLUMN user_role ENUM('guest', 'manager', 'admin') DEFAULT 'guest';

-- Insert default hotel services
INSERT INTO hotel_services (service_name, service_description, service_icon) VALUES
('24/7 Front Desk', 'Round-the-clock reception and guest services', '🏨'),
('Room Service', 'In-room dining available', '🍽️'),
('Housekeeping', 'Daily room cleaning and maintenance', '🧹'),
('Concierge Service', 'Local recommendations and booking assistance', '🛎️'),
('Wake-up Calls', 'Personalized wake-up call service', '⏰'),
('Luggage Storage', 'Secure luggage storage for guests', '🧳'),
('Express Check-in/out', 'Quick and efficient check-in and check-out', '⚡'),
('Business Center', 'Computer and printing facilities', '💼');

-- Insert default hotel amenities
INSERT INTO hotel_amenities (amenity_name, amenity_description, amenity_icon) VALUES
('Free WiFi', 'Complimentary high-speed internet access', '📶'),
('Swimming Pool', 'Indoor/outdoor swimming pool', '🏊'),
('Fitness Center', 'Fully equipped gym and fitness facilities', '💪'),
('Spa & Wellness', 'Relaxation and wellness treatments', '🧘'),
('Restaurant', 'On-site dining restaurant', '🍴'),
('Bar/Lounge', 'Cocktail bar and lounge area', '🍸'),
('Parking', 'Free or paid parking facilities', '🚗'),
('Pet Friendly', 'Pets welcome with special accommodations', '🐕'),
('Airport Shuttle', 'Transportation to/from airport', '🚐'),
('Meeting Rooms', 'Conference and meeting facilities', '👥'),
('Laundry Service', 'Professional laundry and dry cleaning', '👔'),
('Safe Deposit Box', 'Secure storage for valuables', '🔒');

-- Create a default manager account (password: manager123)
INSERT INTO users (first_name, last_name, email, password, user_role) VALUES
('Hotel', 'Manager', 'manager@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager');