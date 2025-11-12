// Node.js API Endpoint Template
// Use this pattern for all new AiniFlow API endpoints

// GET endpoint - Fetch data
app.get('/api/feature/action', async (req, res) => {
    try {
        // 1. Get user from session or query param
        const userPhone = req.query.user_phone || req.session?.user?.phone;
        
        // 2. Validate authentication (optional based on endpoint)
        if (!userPhone) {
            return res.status(401).json({ error: 'Not authenticated' });
        }
        
        // 3. Get query parameters with defaults
        const limit = parseInt(req.query.limit) || 20;
        const offset = parseInt(req.query.offset) || 0;
        
        // 4. Database query (use parameterized queries!)
        const result = await pool.query(`
            SELECT column1, column2, column3
            FROM table_name
            WHERE condition = $1
            ORDER BY created_at DESC
            LIMIT $2 OFFSET $3
        `, [userPhone, limit, offset]);
        
        // 5. Format response (transform DB data to API format)
        const formatted = result.rows.map(row => ({
            id: row.id,
            field1: row.column1,
            field2: row.column2
        }));
        
        // 6. Return JSON
        res.json(formatted);
        
    } catch (error) {
        // 7. Error handling
        console.error('Error in /api/feature/action:', error);
        res.status(500).json({ 
            error: 'Internal server error',
            message: error.message // Remove in production
        });
    }
});

// POST endpoint - Create/update data
app.post('/api/feature/action', async (req, res) => {
    try {
        // 1. Get user authentication
        const userPhone = req.body.user_phone || req.session?.user?.phone;
        
        if (!userPhone) {
            return res.status(401).json({ error: 'Not authenticated' });
        }
        
        // 2. Extract and validate request body
        const { param1, param2 } = req.body;
        
        if (!param1 || !param2) {
            return res.status(400).json({ error: 'Missing required fields' });
        }
        
        // 3. Business logic validation
        if (param1 === userPhone) {
            return res.status(400).json({ error: 'Cannot perform action on yourself' });
        }
        
        // 4. Database operation (INSERT/UPDATE)
        const result = await pool.query(`
            INSERT INTO table_name (phone, field1, field2) 
            VALUES ($1, $2, $3)
            ON CONFLICT (phone) 
            DO UPDATE SET field1 = $2, updated_at = CURRENT_TIMESTAMP
            RETURNING *
        `, [userPhone, param1, param2]);
        
        // 5. Additional operations (awards, notifications, etc.)
        if (result.rows.length > 0) {
            await awardCoins(pool, userPhone, 10); // Helper function
        }
        
        // 6. Success response
        res.json({ 
            success: true,
            data: result.rows[0]
        });
        
    } catch (error) {
        console.error('Error in POST /api/feature/action:', error);
        res.status(500).json({ error: 'Failed to complete action' });
    }
});

// Helper function pattern
async function helperFunction(pool, param1, param2) {
    const result = await pool.query(`
        UPDATE table_name 
        SET field = field + $1 
        WHERE condition = $2
    `, [param1, param2]);
    
    return result.rows;
}
