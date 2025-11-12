-- PostgreSQL Query Patterns for AiniFlow
-- Common queries used throughout the platform

-- ==================== USER QUERIES ====================

-- Get user by phone
SELECT phone, name, profile_photo, wallet_balance, is_online
FROM users
WHERE phone = $1;

-- Get user with traveler profile
SELECT u.phone, u.name, u.profile_photo, u.wallet_balance,
       tp.bio, tp.interests, tp.travel_style, tp.level, tp.aini_coins_balance
FROM users u
LEFT JOIN traveler_profiles tp ON u.phone = tp.phone
WHERE u.phone = $1;

-- Update user online status
UPDATE users 
SET is_online = true, last_seen = CURRENT_TIMESTAMP
WHERE phone = $1;

-- ==================== SOCIAL QUERIES ====================

-- Get profiles user hasn't swiped on
SELECT u.phone, u.name, u.profile_photo,
       tp.bio, tp.interests, tp.travel_style, tp.level
FROM users u
LEFT JOIN traveler_profiles tp ON u.phone = tp.phone
WHERE u.phone != $1
AND u.phone NOT IN (
    SELECT swiped_phone FROM profile_swipes 
    WHERE swiper_phone = $1
)
AND tp.bio IS NOT NULL
ORDER BY RANDOM()
LIMIT $2;

-- Record swipe (with conflict handling)
INSERT INTO profile_swipes (swiper_phone, swiped_phone, swipe_type, direction) 
VALUES ($1, $2, $3, $4)
ON CONFLICT (swiper_phone, swiped_phone, swipe_type) 
DO UPDATE SET direction = $4, created_at = CURRENT_TIMESTAMP;

-- Check for mutual match
SELECT id FROM profile_swipes 
WHERE swiper_phone = $1 
AND swiped_phone = $2 
AND direction = 'right';

-- Create match (with conflict prevention)
INSERT INTO matches (phone1, phone2, match_type) 
VALUES ($1, $2, 'traveler_traveler')
ON CONFLICT (phone1, phone2) DO NOTHING;

-- Get user's matches
SELECT m.id, m.created_at,
       u.phone, u.name, u.profile_photo,
       tp.bio
FROM matches m
JOIN users u ON (u.phone = m.phone1 OR u.phone = m.phone2) AND u.phone != $1
LEFT JOIN traveler_profiles tp ON u.phone = tp.phone
WHERE (m.phone1 = $1 OR m.phone2 = $1)
ORDER BY m.created_at DESC;

-- ==================== FRIEND REQUEST QUERIES ====================

-- Send friend request
INSERT INTO friend_requests (sender_phone, receiver_phone, message, status) 
VALUES ($1, $2, $3, 'pending');

-- Check existing request
SELECT id, status FROM friend_requests 
WHERE (sender_phone = $1 AND receiver_phone = $2) 
   OR (sender_phone = $2 AND receiver_phone = $1);

-- Get pending requests received
SELECT fr.id, fr.message, fr.created_at,
       u.phone, u.name, u.profile_photo,
       tp.bio
FROM friend_requests fr
JOIN users u ON fr.sender_phone = u.phone
LEFT JOIN traveler_profiles tp ON u.phone = tp.phone
WHERE fr.receiver_phone = $1 
AND fr.status = 'pending'
ORDER BY fr.created_at DESC;

-- Accept friend request
UPDATE friend_requests 
SET status = 'accepted', responded_at = CURRENT_TIMESTAMP 
WHERE id = $1 AND receiver_phone = $2;

-- ==================== WALLET/COINS QUERIES ====================

-- Update coins balance
UPDATE traveler_profiles 
SET aini_coins_balance = aini_coins_balance + $1 
WHERE phone = $2;

-- Get wallet balance
SELECT wallet_balance, aini_coins_balance
FROM users u
LEFT JOIN traveler_profiles tp ON u.phone = tp.phone
WHERE u.phone = $1;

-- Record coin transaction
INSERT INTO aini_coin_transactions (user_phone, amount, transaction_type, description, balance_after) 
VALUES ($1, $2, $3, $4, $5);

-- ==================== MESSAGING QUERIES ====================

-- Get chatrooms for user
SELECT c.id, c.name, c.created_at,
       COUNT(DISTINCT rm.phone) as member_count,
       (SELECT COUNT(*) FROM messages WHERE chatroom_id = c.id) as message_count
FROM chatrooms c
JOIN room_members rm ON c.id = rm.chatroom_id
WHERE rm.phone = $1
GROUP BY c.id
ORDER BY c.created_at DESC;

-- Get messages in chatroom
SELECT m.id, m.from_phone, m.message, m.created_at,
       u.name as sender_name, u.profile_photo
FROM messages m
JOIN users u ON m.from_phone = u.phone
WHERE m.chatroom_id = $1
ORDER BY m.created_at DESC
LIMIT $2 OFFSET $3;

-- Send message
INSERT INTO messages (chatroom_id, from_phone, to_phone, message) 
VALUES ($1, $2, $3, $4)
RETURNING *;

-- ==================== PERFORMANCE PATTERNS ====================

-- Use indexes for frequent queries
CREATE INDEX IF NOT EXISTS idx_swipes_swiper ON profile_swipes(swiper_phone);
CREATE INDEX IF NOT EXISTS idx_swipes_swiped ON profile_swipes(swiped_phone);
CREATE INDEX IF NOT EXISTS idx_matches_phones ON matches(phone1, phone2);
CREATE INDEX IF NOT EXISTS idx_messages_chatroom ON messages(chatroom_id);

-- Use EXPLAIN ANALYZE to check query performance
EXPLAIN ANALYZE
SELECT * FROM users WHERE phone = '+1234567890';

-- Batch updates (more efficient than individual updates)
UPDATE traveler_profiles 
SET aini_coins_balance = aini_coins_balance + 50 
WHERE phone IN ($1, $2);
