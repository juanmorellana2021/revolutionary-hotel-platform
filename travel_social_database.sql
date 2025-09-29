-- Social Travel Network Database Schema
-- Revolutionary Addition to AiNi Coin Platform

-- Create traveler profiles table
CREATE TABLE traveler_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bio TEXT,
    interests TEXT, -- Comma-separated list of interests
    travel_style ENUM('budget', 'luxury', 'adventure', 'relaxation', 'business', 'cultural', 'foodie', 'solo', 'group', 'family') DEFAULT 'relaxation',
    languages VARCHAR(255), -- Comma-separated languages spoken
    age_range ENUM('18-25', '26-35', '36-45', '46-55', '56-65', '65+') DEFAULT '26-35',
    travel_frequency ENUM('rarely', 'few_times_year', 'monthly', 'weekly', 'constantly') DEFAULT 'few_times_year',
    hometown VARCHAR(100),
    occupation VARCHAR(100),
    verified BOOLEAN DEFAULT FALSE,
    social_media_links JSON, -- Store social media profiles
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_travel_style (travel_style),
    INDEX idx_verified (verified)
);

-- Create travel connections table (friend system)
CREATE TABLE travel_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'declined', 'blocked') DEFAULT 'pending',
    message TEXT, -- Connection request message
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_connection (user_id, friend_id),
    INDEX idx_user_connections (user_id, status),
    INDEX idx_friend_connections (friend_id, status),
    INDEX idx_status (status)
);

-- Create travel posts table
CREATE TABLE travel_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    post_type ENUM('general', 'recommendation', 'looking_for_buddy', 'experience', 'question', 'meetup', 'activity') DEFAULT 'general',
    location_id INT, -- Reference to hotel_info or custom location
    location_type ENUM('hotel', 'city', 'country', 'custom') DEFAULT 'hotel',
    location_name VARCHAR(255), -- For custom locations
    privacy ENUM('public', 'connections', 'nearby') DEFAULT 'public',
    media_urls JSON, -- Store photo/video URLs
    tags VARCHAR(500), -- Hashtags and searchable tags
    language VARCHAR(10) DEFAULT 'en',
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES hotel_info(id) ON DELETE SET NULL,
    INDEX idx_user_posts (user_id),
    INDEX idx_location (location_id, location_type),
    INDEX idx_post_type (post_type),
    INDEX idx_privacy (privacy),
    INDEX idx_created_at (created_at),
    INDEX idx_featured (featured),
    FULLTEXT(content, tags)
);

-- Create travel post likes table
CREATE TABLE travel_post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES travel_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id),
    INDEX idx_post_likes (post_id),
    INDEX idx_user_likes (user_id)
);

-- Create travel post comments table
CREATE TABLE travel_post_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT NULL, -- For nested comments/replies
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES travel_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES travel_post_comments(id) ON DELETE CASCADE,
    INDEX idx_post_comments (post_id),
    INDEX idx_user_comments (user_id),
    INDEX idx_parent_comments (parent_comment_id),
    INDEX idx_created_at (created_at)
);

-- Create travel meetups table
CREATE TABLE travel_meetups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location_id INT,
    location_type ENUM('hotel', 'city', 'custom') DEFAULT 'hotel',
    location_name VARCHAR(255),
    meetup_date DATETIME NOT NULL,
    max_participants INT DEFAULT 10,
    current_participants INT DEFAULT 1,
    activity_type ENUM('dining', 'sightseeing', 'nightlife', 'adventure', 'cultural', 'shopping', 'business', 'other') DEFAULT 'other',
    cost_estimate DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'USD',
    requirements TEXT, -- Age, language, etc.
    status ENUM('planning', 'confirmed', 'cancelled', 'completed') DEFAULT 'planning',
    privacy ENUM('public', 'connections', 'nearby') DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES hotel_info(id) ON DELETE SET NULL,
    INDEX idx_organizer (organizer_id),
    INDEX idx_location_meetup (location_id),
    INDEX idx_meetup_date (meetup_date),
    INDEX idx_activity_type (activity_type),
    INDEX idx_status (status)
);

-- Create travel meetup participants table
CREATE TABLE travel_meetup_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meetup_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('interested', 'confirmed', 'declined', 'no_show') DEFAULT 'interested',
    message TEXT, -- Why they want to join
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meetup_id) REFERENCES travel_meetups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participation (meetup_id, user_id),
    INDEX idx_meetup_participants (meetup_id, status),
    INDEX idx_user_meetups (user_id, status)
);

-- Create travel experiences table (detailed reviews/stories)
CREATE TABLE travel_experiences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    location_id INT,
    location_type ENUM('hotel', 'city', 'country', 'custom') DEFAULT 'hotel',
    location_name VARCHAR(255),
    experience_type ENUM('accommodation', 'dining', 'activity', 'transportation', 'general') DEFAULT 'general',
    rating INT CHECK (rating >= 1 AND rating <= 5),
    cost_estimate DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'USD',
    duration_days INT,
    best_season ENUM('spring', 'summer', 'fall', 'winter', 'year_round') DEFAULT 'year_round',
    difficulty_level ENUM('easy', 'moderate', 'challenging', 'expert') DEFAULT 'easy',
    media_urls JSON, -- Photos, videos
    tags VARCHAR(500),
    featured BOOLEAN DEFAULT FALSE,
    verified BOOLEAN DEFAULT FALSE,
    views_count INT DEFAULT 0,
    helpful_votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES hotel_info(id) ON DELETE SET NULL,
    INDEX idx_user_experiences (user_id),
    INDEX idx_location_experience (location_id),
    INDEX idx_experience_type (experience_type),
    INDEX idx_rating (rating),
    INDEX idx_featured (featured),
    INDEX idx_verified (verified),
    FULLTEXT(title, content, tags)
);

-- Create travel buddy requests table
CREATE TABLE travel_buddy_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    destination VARCHAR(255) NOT NULL,
    travel_dates_start DATE,
    travel_dates_end DATE,
    flexible_dates BOOLEAN DEFAULT TRUE,
    group_size INT DEFAULT 2,
    budget_range VARCHAR(50), -- e.g., "$1000-2000", "Budget", "Luxury"
    interests TEXT,
    looking_for TEXT, -- What kind of travel buddy they want
    contact_preference ENUM('platform', 'whatsapp', 'email') DEFAULT 'platform',
    status ENUM('active', 'matched', 'cancelled', 'expired') DEFAULT 'active',
    expires_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_requester (requester_id),
    INDEX idx_destination (destination),
    INDEX idx_travel_dates (travel_dates_start, travel_dates_end),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    FULLTEXT(destination, interests, looking_for)
);

-- Create travel notifications table
CREATE TABLE travel_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('connection_request', 'connection_accepted', 'post_like', 'post_comment', 'meetup_invite', 'buddy_match', 'aini_earned') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id INT, -- ID of related post, connection, etc.
    related_type VARCHAR(50), -- Type of related object
    aini_reward DECIMAL(10,2) DEFAULT 0, -- AiNi coins earned from this action
    read_status BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, read_status),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
);

-- Create location popularity tracking
CREATE TABLE location_social_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT,
    location_type ENUM('hotel', 'city', 'country') DEFAULT 'hotel',
    location_name VARCHAR(255),
    posts_count INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    total_likes INT DEFAULT 0,
    total_comments INT DEFAULT 0,
    trending_score DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES hotel_info(id) ON DELETE SET NULL,
    INDEX idx_location_stats (location_id, location_type),
    INDEX idx_trending_score (trending_score),
    INDEX idx_posts_count (posts_count)
);

-- Create AiNi earning activities for social features
CREATE TABLE aini_social_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type ENUM('post_created', 'post_liked', 'comment_made', 'connection_made', 'meetup_organized', 'experience_shared', 'helpful_vote') NOT NULL,
    activity_id INT, -- ID of the post, comment, etc.
    aini_amount DECIMAL(10,2) NOT NULL,
    bonus_multiplier DECIMAL(3,2) DEFAULT 1.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_earnings (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
);

-- Add social features to users table
ALTER TABLE users 
ADD COLUMN travel_social_enabled BOOLEAN DEFAULT TRUE,
ADD COLUMN social_privacy_level ENUM('public', 'connections', 'private') DEFAULT 'public',
ADD COLUMN profile_photo VARCHAR(255),
ADD COLUMN last_seen_social TIMESTAMP NULL,
ADD COLUMN social_reputation_score INT DEFAULT 100;

-- Create indexes for better performance
CREATE INDEX idx_users_social ON users(travel_social_enabled, social_privacy_level);
CREATE INDEX idx_users_last_seen ON users(last_seen_social);

-- Insert sample traveler interests
INSERT INTO ai_chat_config (config_key, config_value) VALUES 
('travel_interests_list', 'Adventure,Beach,Culture,Food,History,Nightlife,Nature,Photography,Shopping,Sports,Art,Music,Architecture,Local Experiences,Wellness,Business,Family,Solo Travel,Group Travel,Budget Travel,Luxury Travel'),
('travel_languages_list', 'English,Spanish,French,German,Italian,Portuguese,Chinese,Japanese,Korean,Arabic,Russian,Hindi,Dutch,Swedish,Norwegian');

-- Create triggers to update AiNi balances when social activities earn coins
DELIMITER //

CREATE TRIGGER after_aini_social_earning
AFTER INSERT ON aini_social_earnings
FOR EACH ROW
BEGIN
    -- Add AiNi coins to user's wallet
    INSERT INTO wallet_transactions (user_id, transaction_type, amount, description, created_at)
    VALUES (NEW.user_id, 'earned', NEW.aini_amount, 
            CONCAT('Social Activity: ', NEW.description), NOW());
    
    -- Update user's AiNi balance
    UPDATE users 
    SET aini_balance = aini_balance + NEW.aini_amount
    WHERE id = NEW.user_id;
END//

CREATE TRIGGER after_travel_post_insert
AFTER INSERT ON travel_posts
FOR EACH ROW
BEGIN
    -- Award AiNi coins for creating a post
    INSERT INTO aini_social_earnings (user_id, activity_type, activity_id, aini_amount, description)
    VALUES (NEW.user_id, 'post_created', NEW.id, 2.50, 'Created travel post');
END//

CREATE TRIGGER after_travel_connection_accepted
AFTER UPDATE ON travel_connections
FOR EACH ROW
BEGIN
    IF NEW.status = 'accepted' AND OLD.status = 'pending' THEN
        -- Award AiNi coins to both users for connecting
        INSERT INTO aini_social_earnings (user_id, activity_type, activity_id, aini_amount, description)
        VALUES (NEW.user_id, 'connection_made', NEW.id, 5.00, 'Made new travel connection');
        
        INSERT INTO aini_social_earnings (user_id, activity_type, activity_id, aini_amount, description)
        VALUES (NEW.friend_id, 'connection_made', NEW.id, 5.00, 'Made new travel connection');
    END IF;
END//

DELIMITER ;

-- Create sample data for testing
INSERT INTO traveler_profiles (user_id, bio, interests, travel_style, languages, age_range, hometown, occupation) VALUES
(1, 'Adventure seeker and food lover. Always looking for the next great experience!', 'Adventure,Food,Photography,Culture', 'adventure', 'English,Spanish', '26-35', 'Miami, FL', 'Digital Marketing'),
(2, 'Business traveler who loves to explore local culture during downtime.', 'Culture,Business,Architecture,Local Experiences', 'business', 'English,French', '36-45', 'New York, NY', 'Consultant');

-- Insert some sample travel posts
INSERT INTO travel_posts (user_id, content, post_type, privacy) VALUES
(1, 'Just discovered the most amazing rooftop restaurant! The view of the city is incredible and the food is even better. Anyone else been here? 🌆🍽️', 'recommendation', 'public'),
(2, 'Looking for someone to explore the local art scene with tomorrow. I heard there''s a great gallery district nearby! 🎨', 'looking_for_buddy', 'public');

-- Update location stats
INSERT INTO location_social_stats (location_name, posts_count, unique_visitors, trending_score) VALUES
('Miami Beach', 127, 89, 95.5),
('New York City', 203, 156, 98.2),
('Bali', 89, 67, 87.3),
('Swiss Alps', 45, 34, 78.9);

COMMIT;