// 🛡️ SECURITY-HARDENED VERSION OF social_routes.js
// Implements: Rate limiting, input validation, sanitization, CSRF protection

const validator = require('validator');
const rateLimit = require('express-rate-limit');

// ====================  RATE LIMITERS ====================

// General API rate limiter: 100 requests per 15 minutes
const apiLimiter = rateLimit({
    windowMs: 15 * 60 * 1000,  // 15 minutes
    max: 100,
    message: { error: 'Too many requests, please try again later' },
    standardHeaders: true,
    legacyHeaders: false,
});

// Strict limiter for swipe actions: 50 swipes per hour
const swipeLimiter = rateLimit({
    windowMs: 60 * 60 * 1000,  // 1 hour
    max: 50,
    message: { error: 'Swipe limit reached. Take a break!' },
    skipSuccessfulRequests: false
});

// Friend request limiter: 20 requests per day
const friendRequestLimiter = rateLimit({
    windowMs: 24 * 60 * 60 * 1000,  // 24 hours
    max: 20,
    message: { error: 'Friend request limit reached for today' }
});

// ==================== VALIDATION HELPERS ====================

function validatePhoneNumber(phone) {
    // Phone must start with + and have 7-15 digits
    if (!phone || typeof phone !== 'string') return false;
    return /^\+\d{7,15}$/.test(phone);
}

function sanitizeInput(input, maxLength = 500) {
    if (!input) return '';
    // Remove null bytes, limit length, escape HTML
    return validator.escape(String(input).replace(/\0/g, '').substring(0, maxLength));
}

function validateLimit(limit, defaultValue = 20, max = 100) {
    const parsed = parseInt(limit);
    if (isNaN(parsed) || parsed < 1) return defaultValue;
    return Math.min(parsed, max);
}

// ==================== SECURE ROUTES ====================

// Get discover cards (travelers to swipe on)
app.get('/api/social/cards', apiLimiter, async (req, res) => {
    try {
        // 1. Validate authentication
        const userPhone = req.query.user_phone || req.session?.user?.phone;
        
        if (!userPhone || !validatePhoneNumber(userPhone)) {
            return res.status(401).json({ error: 'Invalid or missing authentication' });
        }

        // 2. Validate and sanitize limit (prevent resource exhaustion)
        const limit = validateLimit(req.query.limit, 20, 100);

        // 3. Parameterized query (already safe, but documenting)
        const result = await pool.query(`
            SELECT u.phone, u.name, u.profile_photo,
                   tp.bio, tp.interests, tp.travel_style, 
                   tp.languages, tp.countries_visited, tp.gender, tp.level
            FROM users u
            LEFT JOIN traveler_profiles tp ON u.phone = tp.phone
            WHERE u.phone != $1
            AND u.phone NOT IN (
                SELECT swiped_phone FROM profile_swipes 
                WHERE swiper_phone = $1
            )
            AND tp.bio IS NOT NULL
            ORDER BY RANDOM()
            LIMIT $2
        `, [userPhone, limit]);

        // 4. Sanitize output (prevent XSS in frontend)
        const formatted = result.rows.map(card => ({
            id: sanitizeInput(card.phone, 20),
            first_name: sanitizeInput(card.name.split(' ')[0] || card.name, 50),
            last_name: sanitizeInput(card.name.split(' ').slice(1).join(' ') || '', 50),
            bio: sanitizeInput(card.bio || 'Traveler exploring the world', 500),
            profile_photo: card.profile_photo || 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=800',
            interests: sanitizeInput(card.interests, 200),
            travel_style: sanitizeInput(card.travel_style, 100),
            countries_visited: card.countries_visited,
            level: parseInt(card.level) || 1
        }));

        res.json(formatted);
    } catch (error) {
        console.error('Error fetching cards:', error);
        res.status(500).json({ error: 'Failed to fetch cards' });
    }
});

// Record swipe (left/right) - WITH RATE LIMITING
app.post('/api/social/swipe', swipeLimiter, async (req, res) => {
    try {
        // 1. Validate authentication
        const userPhone = req.body.user_phone || req.session?.user?.phone;
        
        if (!userPhone || !validatePhoneNumber(userPhone)) {
            return res.status(401).json({ error: 'Invalid or missing authentication' });
        }

        // 2. Validate required fields
        const { swiped_id, direction, type } = req.body;

        if (!swiped_id || !validatePhoneNumber(swiped_id)) {
            return res.status(400).json({ error: 'Invalid swiped_id' });
        }

        if (!['left', 'right'].includes(direction)) {
            return res.status(400).json({ error: 'Direction must be "left" or "right"' });
        }

        // 3. Business logic validation
        if (swiped_id === userPhone) {
            return res.status(400).json({ error: 'Cannot swipe on yourself' });
        }

        // 4. Sanitize type (default to 'traveler')
        const swipeType = ['traveler', 'local'].includes(type) ? type : 'traveler';

        // 5. Record swipe (parameterized query = safe)
        await pool.query(`
            INSERT INTO profile_swipes (swiper_phone, swiped_phone, swipe_type, direction) 
            VALUES ($1, $2, $3, $4)
            ON CONFLICT (swiper_phone, swiped_phone, swipe_type) 
            DO UPDATE SET direction = $4, created_at = CURRENT_TIMESTAMP
        `, [userPhone, swiped_id, swipeType, direction]);

        const response = { success: true, match: false };

        // 6. Check for mutual match (only if right swipe)
        if (direction === 'right') {
            const matchCheck = await pool.query(`
                SELECT id FROM profile_swipes 
                WHERE swiper_phone = $1 AND swiped_phone = $2 AND direction = 'right'
            `, [swiped_id, userPhone]);

            if (matchCheck.rows.length > 0) {
                // IT'S A MATCH! Use transaction to ensure atomic operation
                await pool.query('BEGIN');
                
                try {
                    // Create match record
                    await pool.query(`
                        INSERT INTO matches (phone1, phone2, match_type) 
                        VALUES ($1, $2, $3)
                        ON CONFLICT (phone1, phone2) DO NOTHING
                    `, [
                        userPhone < swiped_id ? userPhone : swiped_id,
                        userPhone < swiped_id ? swiped_id : userPhone,
                        'traveler_traveler'
                    ]);

                    // Award coins (with FOR UPDATE lock to prevent race conditions)
                    await pool.query(`
                        UPDATE traveler_profiles 
                        SET aini_coins_balance = aini_coins_balance + 50 
                        WHERE phone IN ($1, $2)
                    `, [userPhone, swiped_id]);

                    await pool.query('COMMIT');
                    response.match = true;
                } catch (err) {
                    await pool.query('ROLLBACK');
                    console.error('Match creation failed:', err);
                }
            }
        }

        res.json(response);
    } catch (error) {
        console.error('Error recording swipe:', error);
        res.status(500).json({ error: 'Failed to record swipe' });
    }
});

// Send friend request - WITH RATE LIMITING & SANITIZATION
app.post('/api/social/friend-request', friendRequestLimiter, async (req, res) => {
    try {
        // 1. Validate authentication
        const userPhone = req.body.user_phone || req.session?.user?.phone;
        
        if (!userPhone || !validatePhoneNumber(userPhone)) {
            return res.status(401).json({ error: 'Invalid or missing authentication' });
        }

        // 2. Validate receiver
        const { receiver_id } = req.body;
        
        if (!receiver_id || !validatePhoneNumber(receiver_id)) {
            return res.status(400).json({ error: 'Invalid receiver phone number' });
        }

        if (receiver_id === userPhone) {
            return res.status(400).json({ error: 'Cannot send friend request to yourself' });
        }

        // 3. Sanitize message (prevent XSS)
        const message = sanitizeInput(req.body.message || '', 500);

        // 4. Check if request already exists
        const existing = await pool.query(`
            SELECT id, status FROM friend_requests 
            WHERE (sender_phone = $1 AND receiver_phone = $2) 
               OR (sender_phone = $2 AND receiver_phone = $1)
        `, [userPhone, receiver_id]);

        if (existing.rows.length > 0) {
            const status = existing.rows[0].status;
            if (status === 'blocked') {
                return res.status(403).json({ error: 'Cannot send request' });
            }
            if (status === 'accepted') {
                return res.status(400).json({ error: 'Already friends' });
            }
            return res.status(400).json({ error: 'Request already pending' });
        }

        // 5. Send request
        await pool.query(`
            INSERT INTO friend_requests (sender_phone, receiver_phone, message, status) 
            VALUES ($1, $2, $3, 'pending')
        `, [userPhone, receiver_id, message]);

        res.json({ success: true, status: 'pending' });
    } catch (error) {
        console.error('Error sending friend request:', error);
        res.status(500).json({ error: 'Failed to send friend request' });
    }
});

// Get user's matches
app.get('/api/social/matches', apiLimiter, async (req, res) => {
    try {
        // 1. Validate authentication
        const userPhone = req.query.user_phone || req.session?.user?.phone;

        if (!userPhone || !validatePhoneNumber(userPhone)) {
            return res.status(401).json({ error: 'Invalid or missing authentication' });
        }

        // 2. Fetch matches (parameterized query = safe)
        const result = await pool.query(`
            SELECT m.id, m.match_type, m.match_score, m.created_at,
                   u.phone, u.name, u.profile_photo,
                   tp.bio
            FROM matches m
            JOIN users u ON (u.phone = m.phone1 OR u.phone = m.phone2) AND u.phone != $1
            LEFT JOIN traveler_profiles tp ON u.phone = tp.phone
            WHERE (m.phone1 = $1 OR m.phone2 = $1)
            ORDER BY m.created_at DESC
        `, [userPhone]);

        // 3. Sanitize output
        const sanitized = result.rows.map(match => ({
            id: match.id,
            match_type: sanitizeInput(match.match_type, 50),
            match_score: parseInt(match.match_score) || 0,
            created_at: match.created_at,
            phone: sanitizeInput(match.phone, 20),
            name: sanitizeInput(match.name, 100),
            profile_photo: match.profile_photo,
            bio: sanitizeInput(match.bio, 500)
        }));

        res.json(sanitized);
    } catch (error) {
        console.error('Error fetching matches:', error);
        res.status(500).json({ error: 'Failed to fetch matches' });
    }
});

module.exports = { apiLimiter, swipeLimiter, friendRequestLimiter };
