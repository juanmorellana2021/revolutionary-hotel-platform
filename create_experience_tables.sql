-- Create Experience Partner Businesses Table
CREATE TABLE IF NOT EXISTS aini_experience_partners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    business_name VARCHAR(255) NOT NULL,
    owner_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    country VARCHAR(100),
    city VARCHAR(100),
    address TEXT,
    business_registration VARCHAR(100),
    tax_id VARCHAR(100),
    status ENUM('pending', 'active', 'suspended', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Experiences Table
CREATE TABLE IF NOT EXISTS aini_experiences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    partner_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    short_description VARCHAR(500),
    category ENUM('adventure', 'cultural', 'food', 'wellness', 'nature', 'sports', 'nightlife', 'shopping', 'educational') NOT NULL,
    price_usd DECIMAL(10,2) NOT NULL,
    price_aini_rewards INT,
    price_aini_crypto DECIMAL(10,4),
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    duration_hours INT,
    duration_days INT DEFAULT 1,
    max_participants INT DEFAULT 10,
    min_participants INT DEFAULT 1,
    difficulty_level ENUM('easy', 'moderate', 'challenging', 'extreme') DEFAULT 'moderate',
    country VARCHAR(100),
    city VARCHAR(100),
    meeting_point TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    start_date DATE,
    end_date DATE,
    available_days JSON COMMENT '["monday", "tuesday", ...]',
    images TEXT COMMENT 'Comma-separated image paths',
    cover_image VARCHAR(500),
    included_items TEXT,
    excluded_items TEXT,
    requirements TEXT,
    cancellation_policy TEXT,
    status ENUM('pending_review', 'active', 'inactive', 'rejected') DEFAULT 'pending_review',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    avg_rating DECIMAL(3,2) DEFAULT 0,
    total_reviews INT DEFAULT 0,
    total_bookings INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES aini_experience_partners(id) ON DELETE CASCADE,
    INDEX idx_partner (partner_id),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_location (country, city),
    INDEX idx_price (price_usd)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Experience Bookings Table
CREATE TABLE IF NOT EXISTS aini_experience_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    experience_id INT NOT NULL,
    user_id INT NOT NULL,
    booking_date DATE NOT NULL,
    participants INT NOT NULL,
    total_price_usd DECIMAL(10,2),
    payment_method ENUM('usd', 'aini_rewards', 'aini_crypto', 'mixed') NOT NULL,
    payment_status ENUM('pending', 'confirmed', 'cancelled', 'refunded') DEFAULT 'pending',
    booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    special_requests TEXT,
    cancellation_reason TEXT,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (experience_id) REFERENCES aini_experiences(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES ainitravel_users(id) ON DELETE CASCADE,
    INDEX idx_experience (experience_id),
    INDEX idx_user (user_id),
    INDEX idx_booking_date (booking_date),
    INDEX idx_status (booking_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Experience Reviews Table
CREATE TABLE IF NOT EXISTS aini_experience_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    experience_id INT NOT NULL,
    user_id INT NOT NULL,
    booking_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT,
    photos TEXT COMMENT 'Comma-separated photo paths',
    verified_booking BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    reported_count INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (experience_id) REFERENCES aini_experiences(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES ainitravel_users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES aini_experience_bookings(id) ON DELETE SET NULL,
    INDEX idx_experience (experience_id),
    INDEX idx_user (user_id),
    INDEX idx_rating (rating),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify tables created
SELECT 'Tables created successfully!' AS status;
SHOW TABLES LIKE 'aini_experience%';
