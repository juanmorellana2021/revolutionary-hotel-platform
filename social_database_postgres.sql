-- AiniFlow Social Features Database Schema (PostgreSQL)
-- Compatible with existing aini_platform database structure (phone-based users)

-- Traveler Profiles (extended user info)
CREATE TABLE IF NOT EXISTS traveler_profiles (
    phone VARCHAR(20) PRIMARY KEY,
    bio TEXT,
    profile_photo TEXT,
    interests VARCHAR(500),
    travel_style VARCHAR(100),
    languages VARCHAR(200),
    countries_visited INT DEFAULT 0,
    date_of_birth DATE,
    gender VARCHAR(50),
    verification_status VARCHAR(20) DEFAULT 'unverified',
    is_superguest BOOLEAN DEFAULT FALSE,
    aini_coins_balance INT DEFAULT 0,
    level INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phone) REFERENCES users(phone) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_traveler_phone ON traveler_profiles(phone);

-- Profile Swipes (left/right on profiles)
CREATE TABLE IF NOT EXISTS profile_swipes (
    id SERIAL PRIMARY KEY,
    swiper_phone VARCHAR(20) NOT NULL,
    swiped_phone VARCHAR(20) NOT NULL,
    swipe_type VARCHAR(20) NOT NULL DEFAULT 'traveler',
    direction VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (swiper_phone) REFERENCES users(phone) ON DELETE CASCADE,
    UNIQUE (swiper_phone, swiped_phone, swipe_type)
);

CREATE INDEX IF NOT EXISTS idx_swiper ON profile_swipes(swiper_phone);
CREATE INDEX IF NOT EXISTS idx_swiped ON profile_swipes(swiped_phone);

-- Matches (mutual right swipes)
CREATE TABLE IF NOT EXISTS matches (
    id SERIAL PRIMARY KEY,
    phone1 VARCHAR(20) NOT NULL,
    phone2 VARCHAR(20) NOT NULL,
    match_type VARCHAR(30) DEFAULT 'traveler_traveler',
    match_score INT DEFAULT 0,
    conversation_started BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phone1) REFERENCES users(phone) ON DELETE CASCADE,
    FOREIGN KEY (phone2) REFERENCES users(phone) ON DELETE CASCADE,
    UNIQUE (phone1, phone2)
);

CREATE INDEX IF NOT EXISTS idx_match_phone1 ON matches(phone1);
CREATE INDEX IF NOT EXISTS idx_match_phone2 ON matches(phone2);

-- Friend Requests
CREATE TABLE IF NOT EXISTS friend_requests (
    id SERIAL PRIMARY KEY,
    sender_phone VARCHAR(20) NOT NULL,
    receiver_phone VARCHAR(20) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (sender_phone) REFERENCES users(phone) ON DELETE CASCADE,
    FOREIGN KEY (receiver_phone) REFERENCES users(phone) ON DELETE CASCADE,
    UNIQUE (sender_phone, receiver_phone)
);

CREATE INDEX IF NOT EXISTS idx_fr_sender ON friend_requests(sender_phone);
CREATE INDEX IF NOT EXISTS idx_fr_receiver ON friend_requests(receiver_phone);

-- Sample Data (create profiles for existing users)
INSERT INTO traveler_profiles (phone, bio, interests, travel_style, languages, countries_visited, gender, level) VALUES
('+1234567890', 'Love exploring new cities and trying local cuisine!', 'Food, Culture, Photography', 'Cultural Explorer', 'English, Spanish', 15, 'female', 3),
('+0987654321', 'Hotel manager who loves meeting travelers', 'Hospitality, Culture, Business', 'Professional', 'English', 5, 'male', 2),
('+51997946667', 'Adventure seeker exploring South America 🏔️', 'Adventure, Nature, Photography', 'Adventurer', 'Spanish, English', 8, 'male', 4),
('+14437965990', 'Digital nomad building cool projects', 'Technology, Startups, Travel', 'Digital Nomad', 'English', 12, 'male', 5)
ON CONFLICT (phone) DO NOTHING;
