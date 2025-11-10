// ==================== SOCIAL/DISCOVER ROUTES ====================
// Add these routes to index.js before the "START SERVER" section

// Get discover cards (travelers to swipe on)
app.get('/api/social/cards', async (req, res) => {
    try {
        const userPhone = req.query.user_phone || req.session?.user?.phone;
        const limit = parseInt(req.query.limit) || 20;

        const result = await pool.query(`
            SELECT u.phone, u.name, u.profile_photo,
                   tp.bio, tp.interests, tp.travel_style, 
                   tp.languages, tp.countries_visited, tp.gender, tp.level
            FROM users u
            LEFT JOIN traveler_profiles tp ON u.phone = tp.phone
            WHERE u.phone != COALESCE($1, '')
            AND u.phone NOT IN (
                SELECT swiped_phone FROM profile_swipes 
                WHERE swiper_phone = COALESCE($1, '')
            )
            AND tp.bio IS NOT NULL
            ORDER BY RANDOM()
            LIMIT $2
        `, [userPhone, limit]);

        // Format response for frontend
        const formatted = result.rows.map(card => ({
            id: card.phone,
            first_name: card.name.split(' ')[0] || card.name,
            last_name: card.name.split(' ').slice(1).join(' ') || '',
            bio: card.bio || 'Traveler exploring the world',
            profile_photo: card.profile_photo || 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=800',
            interests: card.interests,
            travel_style: card.travel_style,
            countries_visited: card.countries_visited,
            level: card.level || 1
        }));

        res.json(formatted);
    } catch (error) {
        console.error('Error fetching cards:', error);
        res.status(500).json({ error: 'Failed to fetch cards' });
    }
});

// Record swipe (left/right)
app.post('/api/social/swipe', async (req, res) => {
    try {
        const userPhone = req.body.user_phone || req.session?.user?.phone;
        const { swiped_id, direction, type } = req.body;

        if (!userPhone) {
            return res.status(401).json({ error: 'Not authenticated' });
        }

        if (!swiped_id || !['left', 'right'].includes(direction)) {
            return res.status(400).json({ error: 'Invalid swipe data' });
        }

        if (swiped_id === userPhone) {
            return res.status(400).json({ error: 'Cannot swipe on yourself' });
        }

        // Record swipe
        await pool.query(`
            INSERT INTO profile_swipes (swiper_phone, swiped_phone, swipe_type, direction) 
            VALUES ($1, $2, $3, $4)
            ON CONFLICT (swiper_phone, swiped_phone, swipe_type) 
            DO UPDATE SET direction = $4, created_at = CURRENT_TIMESTAMP
        `, [userPhone, swiped_id, type || 'traveler', direction]);

        const response = { success: true, match: false };

        // Check for mutual match if right swipe
        if (direction === 'right') {
            const matchCheck = await pool.query(`
                SELECT id FROM profile_swipes 
                WHERE swiper_phone = $1 AND swiped_phone = $2 AND direction = 'right'
            `, [swiped_id, userPhone]);

            if (matchCheck.rows.length > 0) {
                // IT'S A MATCH!
                await pool.query(`
                    INSERT INTO matches (phone1, phone2, match_type) 
                    VALUES ($1, $2, 'traveler_traveler')
                    ON CONFLICT (phone1, phone2) DO NOTHING
                `, [
                    userPhone < swiped_id ? userPhone : swiped_id,
                    userPhone < swiped_id ? swiped_id : userPhone
                ]);

                // Award coins to both users
                await pool.query(`
                    UPDATE traveler_profiles 
                    SET aini_coins_balance = aini_coins_balance + 50 
                    WHERE phone IN ($1, $2)
                `, [userPhone, swiped_id]);

                response.match = true;
            }
        }

        res.json(response);
    } catch (error) {
        console.error('Error recording swipe:', error);
        res.status(500).json({ error: 'Failed to record swipe' });
    }
});

// Send friend request
app.post('/api/social/friend-request', async (req, res) => {
    try {
        const userPhone = req.body.user_phone || req.session?.user?.phone;
        const { receiver_id, message } = req.body;

        if (!userPhone) {
            return res.status(401).json({ error: 'Not authenticated' });
        }

        if (!receiver_id) {
            return res.status(400).json({ error: 'Receiver phone required' });
        }

        // Check if request already exists
        const existing = await pool.query(`
            SELECT id, status FROM friend_requests 
            WHERE (sender_phone = $1 AND receiver_phone = $2) 
               OR (sender_phone = $2 AND receiver_phone = $1)
        `, [userPhone, receiver_id]);

        if (existing.rows.length > 0) {
            const status = existing.rows[0].status;
            if (status === 'blocked') {
                return res.status(400).json({ error: 'Cannot send request' });
            }
            if (status === 'accepted') {
                return res.status(400).json({ error: 'Already friends' });
            }
            return res.status(400).json({ error: 'Request already pending' });
        }

        // Send request
        await pool.query(`
            INSERT INTO friend_requests (sender_phone, receiver_phone, message, status) 
            VALUES ($1, $2, $3, 'pending')
        `, [userPhone, receiver_id, message || '']);

        res.json({ success: true, status: 'pending' });
    } catch (error) {
        console.error('Error sending friend request:', error);
        res.status(500).json({ error: 'Failed to send friend request' });
    }
});

// Get user's matches
app.get('/api/social/matches', async (req, res) => {
    try {
        const userPhone = req.query.user_phone || req.session?.user?.phone;

        if (!userPhone) {
            return res.status(401).json({ error: 'Not authenticated' });
        }

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

        res.json(result.rows);
    } catch (error) {
        console.error('Error fetching matches:', error);
        res.status(500).json({ error: 'Failed to fetch matches' });
    }
});
