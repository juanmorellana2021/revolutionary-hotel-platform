// Redis Caching Implementation for AiniFlow Social API
// Add this to index.js after the Redis client connection

const CACHE_TTL = {
  CARDS: 300,      // 5 minutes
  MATCHES: 60,     // 1 minute  
  PROFILE: 600,    // 10 minutes
};

// Cache helper functions
async function getCached(key) {
  try {
    const data = await redisClient.get(key);
    if (data) {
      return JSON.parse(data);
    }
    return null;
  } catch (error) {
    console.error('Cache get error:', error);
    return null;
  }
}

async function setCache(key, data, ttl) {
  try {
    await redisClient.setEx(key, ttl, JSON.stringify(data));
  } catch (error) {
    console.error('Cache set error:', error);
  }
}

async function invalidateCache(pattern) {
  try {
    const keys = await redisClient.keys(pattern);
    if (keys.length > 0) {
      await redisClient.del(keys);
    }
  } catch (error) {
    console.error('Cache invalidate error:', error);
  }
}

// Update GET /api/social/cards with caching
app.get('/api/social/cards', async (req, res) => {
  try {
    const userPhone = req.query.user_phone || req.session?.user?.phone;
    const limit = parseInt(req.query.limit) || 20;
    
    // Create cache key
    const cacheKey = `cards:${userPhone}:${limit}`;
    
    // Check cache first
    const cached = await getCached(cacheKey);
    if (cached) {
      console.log('Cache HIT:', cacheKey);
      return res.json(cached);
    }
    
    console.log('Cache MISS:', cacheKey);
    
    // Query database
    const result = await pool.query(`
      SELECT u.phone, u.name AS first_name, '' AS last_name,
             u.profile_photo, tp.bio, tp.interests, tp.travel_style,
             tp.countries_visited, tp.level
      FROM users u
      LEFT JOIN traveler_profiles tp ON u.phone = tp.phone
      WHERE u.phone != COALESCE($1, '')
      AND u.phone NOT IN (
        SELECT swiped_phone FROM profile_swipes 
        WHERE swiper_phone = $1
      )
      AND tp.bio IS NOT NULL
      ORDER BY RANDOM()
      LIMIT $2
    `, [userPhone, limit]);
    
    // Format response
    const formatted = result.rows.map(row => ({
      id: row.phone,
      first_name: row.first_name,
      last_name: row.last_name,
      bio: row.bio,
      profile_photo: row.profile_photo,
      interests: row.interests,
      travel_style: row.travel_style,
      countries_visited: row.countries_visited,
      level: row.level,
    }));
    
    // Cache the result
    await setCache(cacheKey, formatted, CACHE_TTL.CARDS);
    
    res.json(formatted);
    
  } catch (error) {
    console.error('Error in /api/social/cards:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update POST /api/social/swipe to invalidate cache
app.post('/api/social/swipe', async (req, res) => {
  try {
    const userPhone = req.body.user_phone || req.session?.user?.phone;
    const swipedPhone = req.body.swiped_id;
    const direction = req.body.direction;
    
    if (!userPhone || !swipedPhone || !direction) {
      return res.status(400).json({ error: 'Missing required fields' });
    }
    
    // Record swipe
    await pool.query(`
      INSERT INTO profile_swipes (swiper_phone, swiped_phone, swipe_type, direction)
      VALUES ($1, $2, 'traveler_profile', $3)
      ON CONFLICT (swiper_phone, swiped_phone, swipe_type)
      DO UPDATE SET direction = $3, created_at = CURRENT_TIMESTAMP
    `, [userPhone, swipedPhone, direction]);
    
    // Invalidate user's card cache
    await invalidateCache(`cards:${userPhone}:*`);
    
    // Check for match if right swipe
    let isMatch = false;
    if (direction === 'right') {
      const mutual = await pool.query(`
        SELECT id FROM profile_swipes
        WHERE swiper_phone = $1 AND swiped_phone = $2 AND direction = 'right'
      `, [swipedPhone, userPhone]);
      
      if (mutual.rows.length > 0) {
        isMatch = true;
        
        // Create match
        await pool.query(`
          INSERT INTO matches (phone1, phone2, match_type)
          VALUES ($1, $2, 'traveler_traveler')
          ON CONFLICT DO NOTHING
        `, [userPhone, swipedPhone]);
        
        // Award coins
        await pool.query(`
          UPDATE traveler_profiles
          SET aini_coins_balance = aini_coins_balance + 50
          WHERE phone IN ($1, $2)
        `, [userPhone, swipedPhone]);
        
        // Invalidate matches cache for both users
        await invalidateCache(`matches:${userPhone}`);
        await invalidateCache(`matches:${swipedPhone}`);
      }
    }
    
    res.json({ success: true, match: isMatch });
    
  } catch (error) {
    console.error('Error in /api/social/swipe:', error);
    res.status(500).json({ error: 'Failed to process swipe' });
  }
});

// Update GET /api/social/matches with caching
app.get('/api/social/matches', async (req, res) => {
  try {
    const userPhone = req.query.user_phone || req.session?.user?.phone;
    
    // Cache key
    const cacheKey = `matches:${userPhone}`;
    
    // Check cache
    const cached = await getCached(cacheKey);
    if (cached) {
      return res.json(cached);
    }
    
    // Query database
    const result = await pool.query(`
      SELECT m.id, m.created_at,
             u.phone, u.name, u.profile_photo,
             tp.bio
      FROM matches m
      JOIN users u ON (u.phone = m.phone1 OR u.phone = m.phone2) AND u.phone != $1
      LEFT JOIN traveler_profiles tp ON u.phone = tp.phone
      WHERE (m.phone1 = $1 OR m.phone2 = $1)
      ORDER BY m.created_at DESC
    `, [userPhone]);
    
    // Cache result
    await setCache(cacheKey, result.rows, CACHE_TTL.MATCHES);
    
    res.json(result.rows);
    
  } catch (error) {
    console.error('Error in /api/social/matches:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});
