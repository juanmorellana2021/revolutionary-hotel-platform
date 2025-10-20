-- ============================================
-- AINI.com Multi-Tenant Hotel Platform
-- Database Schema Migration Script
-- ============================================
-- Purpose: Convert single-tenant hotel management system to multi-tenant marketplace
-- Date: October 2025
-- Version: 1.0
-- ============================================

-- Step 1: Create hotels table (Core multi-tenant entity)
-- ============================================

CREATE TABLE IF NOT EXISTS hotels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    owner_user_id INT NOT NULL,
    
    -- Basic Information
    hotel_description TEXT,
    tagline VARCHAR(255),
    star_rating INT CHECK (star_rating BETWEEN 1 AND 5),
    property_type ENUM('hotel', 'hostel', 'resort', 'apartment', 'villa', 'guest_house', 'boutique', 'other') DEFAULT 'hotel',
    
    -- Location
    address TEXT,
    city VARCHAR(100),
    region VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Peru',
    postal_code VARCHAR(20),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    
    -- Contact
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    
    -- Amenities & Features (JSON for flexibility)
    amenities JSON COMMENT 'Array of amenity names: ["Free WiFi", "Pool", "Parking", etc]',
    policies JSON COMMENT 'Check-in/out times, cancellation policy, pet policy, etc',
    
    -- Media
    logo_url VARCHAR(500),
    cover_photo_url VARCHAR(500),
    photo_gallery JSON COMMENT 'Array of photo URLs',
    
    -- Platform Settings
    featured BOOLEAN DEFAULT 0 COMMENT 'Show in featured hotels carousel',
    verified BOOLEAN DEFAULT 0 COMMENT 'Hotel verified by AINI.com staff',
    status ENUM('pending', 'active', 'suspended', 'closed') DEFAULT 'pending',
    
    -- Business Settings
    commission_rate DECIMAL(5, 2) DEFAULT 15.00 COMMENT 'Platform commission percentage',
    currency VARCHAR(3) DEFAULT 'PEN',
    timezone VARCHAR(50) DEFAULT 'America/Lima',
    
    -- SEO
    meta_title VARCHAR(255),
    meta_description TEXT,
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    
    -- Indexes
    INDEX idx_slug (slug),
    INDEX idx_city (city),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_owner (owner_user_id),
    
    FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Step 2: Add hotel_id column to all existing tables
-- ============================================

-- Users table (employees, managers belong to hotels; guests don't)
ALTER TABLE users 
    ADD COLUMN hotel_id INT NULL AFTER id,
    ADD INDEX idx_hotel_id (hotel_id),
    ADD FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE RESTRICT;

-- Rooms table
ALTER TABLE rooms 
    ADD COLUMN hotel_id INT NOT NULL AFTER id,
    ADD INDEX idx_hotel_id (hotel_id),
    ADD FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE;

-- Bookings table
ALTER TABLE bookings 
    ADD COLUMN hotel_id INT NOT NULL AFTER id,
    ADD INDEX idx_hotel_id (hotel_id),
    ADD FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE RESTRICT;

-- Employees table (if exists separately from users)
ALTER TABLE employees 
    ADD COLUMN hotel_id INT NOT NULL AFTER id,
    ADD INDEX idx_hotel_id (hotel_id),
    ADD FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE;

-- Time clock table
ALTER TABLE time_clock 
    ADD COLUMN hotel_id INT NOT NULL AFTER id,
    ADD INDEX idx_hotel_id (hotel_id),
    ADD FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE;

-- Payroll table (if exists)
ALTER TABLE IF EXISTS payroll 
    ADD COLUMN hotel_id INT NOT NULL AFTER id,
    ADD INDEX idx_hotel_id (hotel_id),
    ADD FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE;

-- Income table
ALTER TABLE IF EXISTS income 
    ADD COLUMN hotel_id INT NOT NULL AFTER id,
    ADD INDEX idx_hotel_id (hotel_id),
    ADD FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE;

-- Expenses table
ALTER TABLE IF EXISTS expenses 
    ADD COLUMN hotel_id INT NOT NULL AFTER id,
    ADD INDEX idx_hotel_id (hotel_id),
    ADD FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE;


-- Step 3: Create new tables for multi-tenant features
-- ============================================

-- Hotel Photos table (separate from hotels for better management)
CREATE TABLE IF NOT EXISTS hotel_photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    photo_url VARCHAR(500) NOT NULL,
    caption VARCHAR(255),
    display_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT 0,
    photo_category ENUM('exterior', 'lobby', 'room', 'restaurant', 'pool', 'gym', 'other') DEFAULT 'other',
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_hotel_id (hotel_id),
    INDEX idx_primary (is_primary),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    booking_id INT NOT NULL,
    guest_id INT NOT NULL,
    
    -- Overall Rating
    rating_overall DECIMAL(2,1) NOT NULL CHECK (rating_overall BETWEEN 1.0 AND 10.0),
    
    -- Category Ratings (1-10 scale like Booking.com)
    rating_cleanliness INT CHECK (rating_cleanliness BETWEEN 1 AND 10),
    rating_comfort INT CHECK (rating_comfort BETWEEN 1 AND 10),
    rating_location INT CHECK (rating_location BETWEEN 1 AND 10),
    rating_facilities INT CHECK (rating_facilities BETWEEN 1 AND 10),
    rating_staff INT CHECK (rating_staff BETWEEN 1 AND 10),
    rating_value INT CHECK (rating_value BETWEEN 1 AND 10),
    
    -- Review Content
    review_title VARCHAR(255),
    review_text TEXT,
    traveler_type ENUM('solo', 'couple', 'family', 'business', 'group') DEFAULT 'solo',
    
    -- Guest Info (anonymized)
    guest_name VARCHAR(100),
    guest_country VARCHAR(50),
    
    -- Hotel Response
    hotel_response TEXT NULL,
    hotel_response_date DATETIME NULL,
    
    -- Moderation
    status ENUM('pending', 'approved', 'rejected', 'flagged') DEFAULT 'pending',
    verified_stay BOOLEAN DEFAULT 1 COMMENT 'Review from confirmed booking',
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_hotel_id (hotel_id),
    INDEX idx_booking_id (booking_id),
    INDEX idx_guest_id (guest_id),
    INDEX idx_status (status),
    INDEX idx_rating (rating_overall),
    
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE RESTRICT,
    FOREIGN KEY (guest_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    UNIQUE KEY unique_booking_review (booking_id) COMMENT 'One review per booking'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Availability Calendar table
CREATE TABLE IF NOT EXISTS room_availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    room_id INT NOT NULL,
    date DATE NOT NULL,
    available_quantity INT NOT NULL DEFAULT 0,
    base_price DECIMAL(10, 2) NOT NULL,
    special_price DECIMAL(10, 2) NULL COMMENT 'Override price for special dates',
    min_stay INT DEFAULT 1 COMMENT 'Minimum nights required',
    status ENUM('available', 'blocked', 'maintenance') DEFAULT 'available',
    
    INDEX idx_hotel_id (hotel_id),
    INDEX idx_room_id (room_id),
    INDEX idx_date (date),
    INDEX idx_status (status),
    UNIQUE KEY unique_room_date (room_id, date),
    
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Promotions/Discounts table
CREATE TABLE IF NOT EXISTS promotions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    promotion_name VARCHAR(255) NOT NULL,
    promotion_code VARCHAR(50) UNIQUE,
    discount_type ENUM('percentage', 'fixed_amount', 'free_nights') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    
    -- Validity
    valid_from DATE NOT NULL,
    valid_to DATE NOT NULL,
    booking_window_from DATE COMMENT 'Must book between these dates',
    booking_window_to DATE,
    
    -- Restrictions
    min_nights INT DEFAULT 1,
    max_uses INT COMMENT 'Total times this promo can be used',
    uses_count INT DEFAULT 0,
    min_booking_value DECIMAL(10, 2),
    applicable_room_types JSON COMMENT 'Array of room IDs',
    
    -- Status
    status ENUM('active', 'paused', 'expired') DEFAULT 'active',
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_hotel_id (hotel_id),
    INDEX idx_code (promotion_code),
    INDEX idx_status (status),
    INDEX idx_dates (valid_from, valid_to),
    
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Payment transactions table
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    hotel_id INT NOT NULL,
    
    -- Transaction Details
    transaction_reference VARCHAR(100) UNIQUE NOT NULL,
    payment_gateway ENUM('culqi', 'mercadopago', 'paypal', 'stripe', 'bank_transfer', 'cash') NOT NULL,
    gateway_transaction_id VARCHAR(255),
    
    -- Amounts
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PEN',
    platform_fee DECIMAL(10, 2) NOT NULL COMMENT 'AINI commission',
    hotel_payout DECIMAL(10, 2) NOT NULL COMMENT 'Amount to hotel',
    gateway_fee DECIMAL(10, 2) DEFAULT 0,
    
    -- Status
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded', 'partially_refunded') DEFAULT 'pending',
    
    -- Details
    payment_method_details JSON COMMENT 'Card last 4 digits, etc',
    failure_reason TEXT,
    
    -- Payout tracking
    payout_status ENUM('pending', 'processing', 'paid', 'hold') DEFAULT 'pending',
    payout_date DATE NULL,
    payout_reference VARCHAR(100),
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    
    INDEX idx_booking_id (booking_id),
    INDEX idx_hotel_id (hotel_id),
    INDEX idx_status (status),
    INDEX idx_payout_status (payout_status),
    INDEX idx_reference (transaction_reference),
    
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE RESTRICT,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Search history/analytics table
CREATE TABLE IF NOT EXISTS search_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL COMMENT 'NULL for guest searches',
    
    -- Search Parameters
    location VARCHAR(255),
    checkin_date DATE,
    checkout_date DATE,
    guests INT,
    rooms INT DEFAULT 1,
    
    -- Filters applied
    min_price DECIMAL(10, 2),
    max_price DECIMAL(10, 2),
    star_rating JSON COMMENT 'Array of selected ratings',
    amenities JSON COMMENT 'Array of selected amenities',
    
    -- Results
    results_count INT,
    clicked_hotel_id INT NULL,
    
    -- Context
    ip_address VARCHAR(45),
    user_agent TEXT,
    searched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_location (location),
    INDEX idx_dates (checkin_date, checkout_date),
    INDEX idx_clicked_hotel (clicked_hotel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Wishlists/Favorites table
CREATE TABLE IF NOT EXISTS wishlists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_hotel_id (hotel_id),
    UNIQUE KEY unique_user_hotel (user_id, hotel_id),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Platform admin logs table
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_user_id INT NOT NULL,
    action_type ENUM('hotel_approved', 'hotel_suspended', 'user_banned', 'review_moderated', 'payout_processed', 'settings_changed') NOT NULL,
    target_type ENUM('hotel', 'user', 'booking', 'review', 'payment') NOT NULL,
    target_id INT NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_admin (admin_user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at),
    
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Step 4: Create views for common queries
-- ============================================

-- Hotel search view with aggregated ratings
CREATE OR REPLACE VIEW hotel_search_view AS
SELECT 
    h.id,
    h.hotel_name,
    h.slug,
    h.city,
    h.region,
    h.star_rating,
    h.property_type,
    h.amenities,
    h.cover_photo_url,
    h.featured,
    h.status,
    
    -- Average ratings
    ROUND(AVG(r.rating_overall), 1) as avg_rating,
    COUNT(r.id) as review_count,
    ROUND(AVG(r.rating_cleanliness), 1) as avg_cleanliness,
    ROUND(AVG(r.rating_comfort), 1) as avg_comfort,
    ROUND(AVG(r.rating_location), 1) as avg_location,
    ROUND(AVG(r.rating_staff), 1) as avg_staff,
    
    -- Room pricing (minimum)
    MIN(ro.price) as starting_price,
    
    -- Booking stats
    COUNT(DISTINCT b.id) as total_bookings
    
FROM hotels h
LEFT JOIN reviews r ON h.id = r.hotel_id AND r.status = 'approved'
LEFT JOIN rooms ro ON h.id = ro.hotel_id
LEFT JOIN bookings b ON h.id = b.hotel_id
WHERE h.status = 'active'
GROUP BY h.id;


-- Hotel dashboard stats view
CREATE OR REPLACE VIEW hotel_dashboard_stats AS
SELECT 
    h.id as hotel_id,
    h.hotel_name,
    
    -- Current month bookings
    COUNT(DISTINCT CASE 
        WHEN MONTH(b.created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(b.created_at) = YEAR(CURRENT_DATE())
        THEN b.id 
    END) as bookings_this_month,
    
    -- Current month revenue
    SUM(CASE 
        WHEN MONTH(b.created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(b.created_at) = YEAR(CURRENT_DATE())
        THEN b.total_amount 
        ELSE 0 
    END) as revenue_this_month,
    
    -- Average rating
    ROUND(AVG(r.rating_overall), 1) as avg_rating,
    COUNT(DISTINCT r.id) as total_reviews,
    
    -- Pending reviews response
    COUNT(DISTINCT CASE WHEN r.hotel_response IS NULL THEN r.id END) as pending_responses,
    
    -- Upcoming check-ins (next 7 days)
    COUNT(DISTINCT CASE 
        WHEN b.check_in_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
        AND b.status = 'confirmed'
        THEN b.id 
    END) as upcoming_checkins
    
FROM hotels h
LEFT JOIN bookings b ON h.id = b.hotel_id
LEFT JOIN reviews r ON h.id = r.hotel_id
GROUP BY h.id;


-- Step 5: Update existing bookings table structure
-- ============================================

-- Enhance bookings table for multi-tenant platform
ALTER TABLE bookings
    ADD COLUMN booking_source ENUM('website', 'mobile_app', 'phone', 'walk_in', 'partner') DEFAULT 'website' AFTER booking_reference,
    ADD COLUMN promo_code VARCHAR(50) NULL AFTER total_amount,
    ADD COLUMN discount_amount DECIMAL(10, 2) DEFAULT 0 AFTER promo_code,
    ADD COLUMN original_amount DECIMAL(10, 2) NULL AFTER discount_amount,
    ADD COLUMN guest_notes TEXT COMMENT 'Special requests from guest' AFTER special_requests,
    ADD COLUMN cancellation_reason TEXT NULL,
    ADD COLUMN cancelled_at DATETIME NULL,
    ADD COLUMN checked_in_at DATETIME NULL,
    ADD COLUMN checked_out_at DATETIME NULL,
    ADD INDEX idx_check_in_date (check_in_date),
    ADD INDEX idx_check_out_date (check_out_date),
    ADD INDEX idx_booking_source (booking_source),
    ADD INDEX idx_promo_code (promo_code);


-- Step 6: Insert sample data (for testing)
-- ============================================

-- Insert platform admin role
INSERT INTO permissions (permission_name, description) VALUES
('platform_admin', 'Full platform administration access'),
('approve_hotels', 'Can approve new hotel registrations'),
('moderate_reviews', 'Can moderate and remove reviews'),
('process_payouts', 'Can process hotel payouts'),
('view_analytics', 'Can view platform-wide analytics')
ON DUPLICATE KEY UPDATE permission_name=permission_name;

-- Assign platform admin permissions to owner role
INSERT INTO role_permissions (role, permission_id)
SELECT 'owner', id FROM permissions 
WHERE permission_name IN ('platform_admin', 'approve_hotels', 'moderate_reviews', 'process_payouts', 'view_analytics')
ON DUPLICATE KEY UPDATE role=role;


-- Step 7: Create stored procedures for common operations
-- ============================================

DELIMITER //

-- Procedure to check room availability
CREATE PROCEDURE check_room_availability(
    IN p_room_id INT,
    IN p_check_in DATE,
    IN p_check_out DATE,
    OUT p_available BOOLEAN,
    OUT p_price DECIMAL(10,2)
)
BEGIN
    DECLARE conflict_count INT;
    DECLARE room_price DECIMAL(10,2);
    
    -- Check for booking conflicts
    SELECT COUNT(*) INTO conflict_count
    FROM bookings
    WHERE room_id = p_room_id
    AND status IN ('confirmed', 'checked_in')
    AND (
        (check_in_date <= p_check_in AND check_out_date > p_check_in)
        OR (check_in_date < p_check_out AND check_out_date >= p_check_out)
        OR (check_in_date >= p_check_in AND check_out_date <= p_check_out)
    );
    
    -- Get room base price
    SELECT price INTO room_price FROM rooms WHERE id = p_room_id;
    
    SET p_available = (conflict_count = 0);
    SET p_price = room_price;
END //

-- Procedure to calculate hotel stats
CREATE PROCEDURE calculate_hotel_stats(IN p_hotel_id INT)
BEGIN
    SELECT 
        COUNT(DISTINCT b.id) as total_bookings,
        SUM(b.total_amount) as total_revenue,
        AVG(r.rating_overall) as avg_rating,
        COUNT(DISTINCT r.id) as total_reviews,
        COUNT(DISTINCT CASE WHEN b.status = 'cancelled' THEN b.id END) as cancellations,
        ROUND(
            COUNT(DISTINCT CASE WHEN b.status = 'cancelled' THEN b.id END) * 100.0 / 
            NULLIF(COUNT(DISTINCT b.id), 0), 
            2
        ) as cancellation_rate_pct
    FROM hotels h
    LEFT JOIN bookings b ON h.id = b.hotel_id
    LEFT JOIN reviews r ON h.id = r.hotel_id AND r.status = 'approved'
    WHERE h.id = p_hotel_id
    GROUP BY h.id;
END //

DELIMITER ;


-- ============================================
-- Migration Complete!
-- ============================================
-- Next steps:
-- 1. Run this script on a TEST database first
-- 2. Create tenant_context.php for query filtering
-- 3. Migrate existing Samay Wasi data (see separate migration script)
-- 4. Test data isolation thoroughly
-- 5. Deploy to production
-- ============================================
