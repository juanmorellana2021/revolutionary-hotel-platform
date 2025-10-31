-- AiniFlow Database Schema (Social AINI - Server 4)
-- Server 4: AiniFlow + Social + Wallet + Reviews
-- Created: October 31, 2025

-- Drop existing tables if recreating
DROP TABLE IF EXISTS wallet_transactions CASCADE;
DROP TABLE IF EXISTS social_posts CASCADE;
DROP TABLE IF EXISTS reviews CASCADE;
DROP TABLE IF EXISTS room_members CASCADE;
DROP TABLE IF EXISTS chatrooms CASCADE;
DROP TABLE IF EXISTS messages CASCADE;
DROP TABLE IF EXISTS guest_profiles CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Users table (Phone number as primary ID)
CREATE TABLE users (
    phone VARCHAR(20) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    profile_photo TEXT,
    wallet_balance DECIMAL(10,2) DEFAULT 0.00,
    is_online BOOLEAN DEFAULT FALSE,
    last_seen TIMESTAMP DEFAULT NOW(),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_users_online ON users(is_online);
CREATE INDEX idx_users_created ON users(created_at);

-- Guest profiles (for reputation/review system)
CREATE TABLE guest_profiles (
    phone VARCHAR(20) PRIMARY KEY REFERENCES users(phone) ON DELETE CASCADE,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    total_stays INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    is_superguest BOOLEAN DEFAULT FALSE, -- 10+ stays, 4.8+ rating
    badges TEXT[], -- Array of badge names
    bio TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_guest_rating ON guest_profiles(average_rating);
CREATE INDEX idx_guest_superguest ON guest_profiles(is_superguest);

-- Messages table (DMs and chatroom messages)
CREATE TABLE messages (
    id SERIAL PRIMARY KEY,
    from_phone VARCHAR(20) NOT NULL REFERENCES users(phone) ON DELETE CASCADE,
    to_phone VARCHAR(20) REFERENCES users(phone) ON DELETE CASCADE, -- NULL if chatroom
    room_id INT, -- NULL if DM
    message_type VARCHAR(20) DEFAULT 'text', -- text, image, file, location
    content TEXT NOT NULL,
    media_url TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_messages_from ON messages(from_phone);
CREATE INDEX idx_messages_to ON messages(to_phone);
CREATE INDEX idx_messages_room ON messages(room_id);
CREATE INDEX idx_messages_sent ON messages(sent_at);

-- Chatrooms table
CREATE TABLE chatrooms (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    room_type VARCHAR(20) DEFAULT 'public', -- public, private, hotel
    hotel_id INT, -- NULL if not hotel-specific
    created_by VARCHAR(20) NOT NULL REFERENCES users(phone) ON DELETE CASCADE,
    photo_url TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_chatrooms_type ON chatrooms(room_type);
CREATE INDEX idx_chatrooms_hotel ON chatrooms(hotel_id);

-- Room members table
CREATE TABLE room_members (
    id SERIAL PRIMARY KEY,
    room_id INT NOT NULL REFERENCES chatrooms(id) ON DELETE CASCADE,
    phone VARCHAR(20) NOT NULL REFERENCES users(phone) ON DELETE CASCADE,
    is_admin BOOLEAN DEFAULT FALSE,
    is_muted BOOLEAN DEFAULT FALSE,
    joined_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(room_id, phone)
);

CREATE INDEX idx_room_members_room ON room_members(room_id);
CREATE INDEX idx_room_members_phone ON room_members(phone);

-- Reviews table (Mutual hotel ↔ guest reviews)
CREATE TABLE reviews (
    id SERIAL PRIMARY KEY,
    booking_id INT NOT NULL, -- Links to hotel booking system
    hotel_id INT NOT NULL,
    guest_phone VARCHAR(20) NOT NULL REFERENCES users(phone) ON DELETE CASCADE,
    
    -- Guest reviews hotel
    guest_to_hotel_rating INT CHECK (guest_to_hotel_rating >= 1 AND guest_to_hotel_rating <= 5),
    guest_to_hotel_review TEXT,
    guest_to_hotel_submitted_at TIMESTAMP,
    
    -- Hotel reviews guest
    hotel_to_guest_rating INT CHECK (hotel_to_guest_rating >= 1 AND hotel_to_guest_rating <= 5),
    hotel_to_guest_review TEXT,
    hotel_to_guest_submitted_at TIMESTAMP,
    
    -- Reviews publish when both submit (or 14 days pass)
    published_at TIMESTAMP,
    expires_at TIMESTAMP, -- 14 days after booking ends
    
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    UNIQUE(booking_id)
);

CREATE INDEX idx_reviews_booking ON reviews(booking_id);
CREATE INDEX idx_reviews_hotel ON reviews(hotel_id);
CREATE INDEX idx_reviews_guest ON reviews(guest_phone);
CREATE INDEX idx_reviews_published ON reviews(published_at);

-- Social posts table (AINI Social Network)
CREATE TABLE social_posts (
    id SERIAL PRIMARY KEY,
    author_phone VARCHAR(20) NOT NULL REFERENCES users(phone) ON DELETE CASCADE,
    content TEXT,
    media_urls TEXT[], -- Array of photo/video URLs
    post_type VARCHAR(20) DEFAULT 'text', -- text, photo, video, shared
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    shares_count INT DEFAULT 0,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_social_posts_author ON social_posts(author_phone);
CREATE INDEX idx_social_posts_created ON social_posts(created_at DESC);
CREATE INDEX idx_social_posts_public ON social_posts(is_public);

-- Wallet transactions table (AiniCoin payments)
CREATE TABLE wallet_transactions (
    id SERIAL PRIMARY KEY,
    from_phone VARCHAR(20) NOT NULL REFERENCES users(phone) ON DELETE CASCADE,
    to_phone VARCHAR(20) REFERENCES users(phone) ON DELETE CASCADE, -- NULL if hotel deposit
    amount DECIMAL(10,2) NOT NULL,
    transaction_type VARCHAR(30) NOT NULL, -- deposit, withdrawal, transfer, booking_payment, refund
    booking_id INT, -- NULL if not booking-related
    hotel_id INT, -- NULL if peer-to-peer transfer
    status VARCHAR(20) DEFAULT 'pending', -- pending, completed, failed, refunded
    description TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    completed_at TIMESTAMP
);

CREATE INDEX idx_wallet_from ON wallet_transactions(from_phone);
CREATE INDEX idx_wallet_to ON wallet_transactions(to_phone);
CREATE INDEX idx_wallet_type ON wallet_transactions(transaction_type);
CREATE INDEX idx_wallet_status ON wallet_transactions(status);
CREATE INDEX idx_wallet_booking ON wallet_transactions(booking_id);
CREATE INDEX idx_wallet_created ON wallet_transactions(created_at DESC);

-- Function to auto-publish reviews after 14 days
CREATE OR REPLACE FUNCTION auto_publish_reviews()
RETURNS void AS $$
BEGIN
    UPDATE reviews
    SET published_at = NOW()
    WHERE published_at IS NULL
      AND expires_at <= NOW();
END;
$$ LANGUAGE plpgsql;

-- Function to update guest average rating
CREATE OR REPLACE FUNCTION update_guest_rating(guest_phone_param VARCHAR(20))
RETURNS void AS $$
DECLARE
    avg_rating DECIMAL(3,2);
    review_count INT;
    stay_count INT;
BEGIN
    -- Calculate average rating from published reviews
    SELECT 
        AVG(hotel_to_guest_rating),
        COUNT(*),
        COUNT(DISTINCT booking_id)
    INTO avg_rating, review_count, stay_count
    FROM reviews
    WHERE guest_phone = guest_phone_param
      AND published_at IS NOT NULL
      AND hotel_to_guest_rating IS NOT NULL;
    
    -- Update guest profile
    UPDATE guest_profiles
    SET 
        average_rating = COALESCE(avg_rating, 0),
        total_reviews = COALESCE(review_count, 0),
        total_stays = COALESCE(stay_count, 0),
        is_superguest = (COALESCE(stay_count, 0) >= 10 AND COALESCE(avg_rating, 0) >= 4.8),
        updated_at = NOW()
    WHERE phone = guest_phone_param;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE users IS 'All platform users - phone number is primary ID';
COMMENT ON TABLE guest_profiles IS 'Guest reputation and review stats - Airbnb-style';
COMMENT ON TABLE messages IS 'AiniFlow messages - DMs and chatrooms';
COMMENT ON TABLE chatrooms IS 'AiniFlow chatrooms - public, private, hotel-specific';
COMMENT ON TABLE room_members IS 'Chatroom membership and permissions';
COMMENT ON TABLE reviews IS 'Mutual reviews - both hotel and guest review each other';
COMMENT ON TABLE social_posts IS 'AINI Social Network posts';
COMMENT ON TABLE wallet_transactions IS 'AiniCoin wallet transactions and payments';

-- Sample data for testing
INSERT INTO users (phone, name, profile_photo) VALUES 
('+1234567890', 'Test User', 'https://via.placeholder.com/150'),
('+0987654321', 'Hotel Manager', 'https://via.placeholder.com/150');

INSERT INTO guest_profiles (phone, is_verified) VALUES 
('+1234567890', TRUE);

INSERT INTO chatrooms (name, description, room_type, created_by) VALUES 
('Miami Beach Travelers', 'Chat about Miami Beach hotels and attractions', 'public', '+1234567890'),
('Hotel Ocean View - Guests', 'For guests staying at Hotel Ocean View', 'hotel', '+0987654321');

-- Grant permissions (adjust username as needed)
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO postgres;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO postgres;
