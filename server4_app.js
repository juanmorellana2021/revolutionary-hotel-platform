/**
 * AiniFlow Server (Social AINI - Server 4)
 * Main application server
 * Features: AiniFlow messaging, AINI Social, AiniCoin Wallet, Mutual Reviews
 */

require('dotenv').config();
const express = require('express');
const http = require('http');
const socketIO = require('socket.io');
const cors = require('cors');
const { Pool } = require('pg');
const redis = require('redis');

// Initialize Express app
const app = express();
const server = http.createServer(app);
const io = socketIO(server, {
    cors: {
        origin: "*", // Configure properly for production
        methods: ["GET", "POST"]
    }
});

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files from public directory
app.use(express.static('public'));

// PostgreSQL connection
const poolConfig = {
    user: process.env.DB_USER || 'postgres',
    host: process.env.DB_HOST || 'localhost',
    database: process.env.DB_NAME || 'aini_platform',
    port: process.env.DB_PORT || 5432,
};

// Only add password if it exists
if (process.env.DB_PASSWORD) {
    poolConfig.password = process.env.DB_PASSWORD;
}

const pool = new Pool(poolConfig);

// Redis client for real-time features
const redisClient = redis.createClient({
    host: process.env.REDIS_HOST || 'localhost',
    port: process.env.REDIS_PORT || 6379
});

redisClient.on('error', (err) => console.log('Redis Client Error', err));
redisClient.on('connect', () => console.log('Redis Client Connected'));

// Connect Redis
(async () => {
    await redisClient.connect();
})();

// ==================== ROUTES ====================

// Homepage
app.get('/', (req, res) => {
    res.send(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>AiniFlow - Social AINI Platform</title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                }
                .container {
                    text-align: center;
                    padding: 40px;
                    background: rgba(255,255,255,0.1);
                    backdrop-filter: blur(10px);
                    border-radius: 20px;
                    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
                    max-width: 600px;
                }
                h1 { font-size: 3em; margin-bottom: 20px; }
                .emoji { font-size: 4em; margin-bottom: 20px; }
                .features {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 15px;
                    margin: 30px 0;
                }
                .feature {
                    background: rgba(255,255,255,0.15);
                    padding: 20px;
                    border-radius: 10px;
                    font-size: 1.1em;
                }
                .endpoints {
                    background: rgba(0,0,0,0.3);
                    padding: 20px;
                    border-radius: 10px;
                    text-align: left;
                    margin-top: 30px;
                }
                .endpoints a {
                    color: #6ee7b7;
                    text-decoration: none;
                    display: block;
                    padding: 5px 0;
                }
                .endpoints a:hover { text-decoration: underline; }
                .status {
                    display: inline-block;
                    background: #10b981;
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-size: 0.9em;
                    margin-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="emoji">🚀</div>
                <h1>AiniFlow</h1>
                <p style="font-size: 1.2em; margin-bottom: 10px;">Social AINI Platform - Server 4</p>
                <div class="status">● ONLINE</div>
                
                <div class="features">
                    <div class="feature">💬 AiniFlow Messaging</div>
                    <div class="feature">👥 Social Network</div>
                    <div class="feature">💰 AiniCoin Wallet</div>
                    <div class="feature">⭐ Mutual Reviews</div>
                </div>

                <div class="endpoints">
                    <strong>API Endpoints:</strong><br><br>
                    <a href="/health" target="_blank">GET /health</a>
                    <a href="/api" target="_blank">GET /api</a>
                    <a>POST /api/auth/request-code</a>
                    <a>POST /api/auth/verify-code</a>
                    <a>GET /api/users/:phone</a>
                    <a>GET /api/chatrooms/public</a>
                    <a>GET /api/messages/conversation/:phone1/:phone2</a>
                </div>

                <p style="margin-top: 30px; opacity: 0.8; font-size: 0.9em;">
                    WebSocket available at: ws://72.61.217.65:3000
                </p>
                
                <a href="/login.html" style="display: inline-block; margin-top: 30px; padding: 15px 40px; background: white; color: #667eea; text-decoration: none; border-radius: 10px; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                    Login / Sign Up
                </a>
            </div>
        </body>
        </html>
    `);
});

// Health check
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        service: 'AiniFlow Server (Social AINI)',
        timestamp: new Date().toISOString(),
        features: ['AiniFlow', 'Social', 'Wallet', 'Reviews']
    });
});

// API info
app.get('/api', (req, res) => {
    res.json({
        name: 'AiniFlow API',
        version: '1.0.0',
        endpoints: {
            auth: '/api/auth/*',
            messaging: '/api/messages/*',
            chatrooms: '/api/chatrooms/*',
            social: '/api/social/*',
            wallet: '/api/wallet/*',
            reviews: '/api/reviews/*',
            users: '/api/users/*'
        }
    });
});

// ==================== AUTH ROUTES ====================
const authRouter = express.Router();

// Request SMS verification code
authRouter.post('/request-code', async (req, res) => {
    const { phone } = req.body;
    
    if (!phone) {
        return res.status(400).json({ error: 'Phone number required' });
    }
    
    // TODO: Integrate Twilio to send SMS
    // For now, generate a random code and store in Redis
    const code = Math.floor(100000 + Math.random() * 900000).toString();
    await redisClient.setEx(`verify:${phone}`, 300, code); // 5 min expiry
    
    console.log(`Verification code for ${phone}: ${code}`);
    
    res.json({ 
        success: true, 
        message: 'Verification code sent',
        // Remove in production
        dev_code: code 
    });
});

// Verify code and login/register
authRouter.post('/verify-code', async (req, res) => {
    const { phone, code, name } = req.body;
    
    if (!phone || !code) {
        return res.status(400).json({ error: 'Phone and code required' });
    }
    
    // Verify code from Redis
    const storedCode = await redisClient.get(`verify:${phone}`);
    
    if (storedCode !== code) {
        return res.status(401).json({ error: 'Invalid verification code' });
    }
    
    // Code is valid - check if user exists
    const userResult = await pool.query('SELECT * FROM users WHERE phone = $1', [phone]);
    
    let user;
    if (userResult.rows.length === 0) {
        // Create new user
        const insertResult = await pool.query(
            'INSERT INTO users (phone, name) VALUES ($1, $2) RETURNING *',
            [phone, name || 'User']
        );
        user = insertResult.rows[0];
        
        // Create guest profile
        await pool.query(
            'INSERT INTO guest_profiles (phone) VALUES ($1)',
            [phone]
        );
    } else {
        user = userResult.rows[0];
    }
    
    // Delete verification code
    await redisClient.del(`verify:${phone}`);
    
    // TODO: Generate JWT token
    const token = 'jwt_token_here'; // Implement JWT
    
    res.json({
        success: true,
        user,
        token
    });
});

app.use('/api/auth', authRouter);

// ==================== USER ROUTES ====================
const userRouter = express.Router();

// Get user profile
userRouter.get('/:phone', async (req, res) => {
    try {
        const { phone } = req.params;
        const result = await pool.query(
            `SELECT u.*, gp.average_rating, gp.total_reviews, gp.total_stays, 
                    gp.is_verified, gp.is_superguest, gp.badges, gp.bio
             FROM users u
             LEFT JOIN guest_profiles gp ON u.phone = gp.phone
             WHERE u.phone = $1`,
            [phone]
        );
        
        if (result.rows.length === 0) {
            return res.status(404).json({ error: 'User not found' });
        }
        
        res.json(result.rows[0]);
    } catch (error) {
        console.error('Error fetching user:', error);
        res.status(500).json({ error: 'Server error' });
    }
});

// Update user profile
userRouter.put('/:phone', async (req, res) => {
    try {
        const { phone } = req.params;
        const { name, profile_photo, bio } = req.body;
        
        // Update users table
        if (name || profile_photo) {
            await pool.query(
                'UPDATE users SET name = COALESCE($1, name), profile_photo = COALESCE($2, profile_photo), updated_at = NOW() WHERE phone = $3',
                [name, profile_photo, phone]
            );
        }
        
        // Update guest_profiles table
        if (bio) {
            await pool.query(
                'UPDATE guest_profiles SET bio = $1, updated_at = NOW() WHERE phone = $2',
                [bio, phone]
            );
        }
        
        res.json({ success: true, message: 'Profile updated' });
    } catch (error) {
        console.error('Error updating user:', error);
        res.status(500).json({ error: 'Server error' });
    }
});

// Check which phone numbers are registered on AiniFlow
userRouter.post('/check-contacts', async (req, res) => {
    try {
        const { phone_numbers, requester } = req.body;
        
        if (!phone_numbers || !Array.isArray(phone_numbers)) {
            return res.status(400).json({ error: 'Invalid request' });
        }
        
        // Query database for matching users
        const result = await pool.query(
            `SELECT phone, name, bio, profile_photo, is_online, last_seen 
             FROM users 
             WHERE phone = ANY($1) AND phone != $2`,
            [phone_numbers, requester]
        );
        
        const onAiniFlow = result.rows;
        const onAiniFlowPhones = onAiniFlow.map(u => u.phone);
        
        // Find contacts NOT on AiniFlow
        const notOnAiniFlow = phone_numbers
            .filter(phone => phone !== requester && !onAiniFlowPhones.includes(phone))
            .map(phone => ({ phone, name: phone })); // Name will be from device contacts
        
        res.json({
            on_ainiflow: onAiniFlow,
            not_on_ainiflow: notOnAiniFlow
        });
    } catch (error) {
        console.error('Error checking contacts:', error);
        res.status(500).json({ error: 'Server error' });
    }
});

app.use('/api/users', userRouter);

// ==================== MESSAGING ROUTES ====================
const messageRouter = express.Router();

// Get conversation history
messageRouter.get('/conversation/:phone1/:phone2', async (req, res) => {
    try {
        const { phone1, phone2 } = req.params;
        const result = await pool.query(
            `SELECT m.*, u.name as sender_name, u.profile_photo as sender_photo
             FROM messages m
             JOIN users u ON m.from_phone = u.phone
             WHERE (m.from_phone = $1 AND m.to_phone = $2)
                OR (m.from_phone = $2 AND m.to_phone = $1)
             ORDER BY m.sent_at ASC`,
            [phone1, phone2]
        );
        
        res.json(result.rows);
    } catch (error) {
        console.error('Error fetching messages:', error);
        res.status(500).json({ error: 'Server error' });
    }
});

app.use('/api/messages', messageRouter);

// ==================== CHATROOM ROUTES ====================
const chatroomRouter = express.Router();

// Get all public chatrooms
chatroomRouter.get('/public', async (req, res) => {
    try {
        const result = await pool.query(
            `SELECT c.*, COUNT(rm.id) as member_count
             FROM chatrooms c
             LEFT JOIN room_members rm ON c.id = rm.room_id
             WHERE c.room_type = 'public'
             GROUP BY c.id
             ORDER BY member_count DESC`,
            []
        );
        
        res.json(result.rows);
    } catch (error) {
        console.error('Error fetching chatrooms:', error);
        res.status(500).json({ error: 'Server error' });
    }
});

// Get chatroom messages
chatroomRouter.get('/:roomId/messages', async (req, res) => {
    try {
        const { roomId } = req.params;
        const result = await pool.query(
            `SELECT m.*, u.name as sender_name, u.profile_photo as sender_photo
             FROM messages m
             JOIN users u ON m.from_phone = u.phone
             WHERE m.room_id = $1
             ORDER BY m.sent_at ASC`,
            [roomId]
        );
        
        res.json(result.rows);
    } catch (error) {
        console.error('Error fetching room messages:', error);
        res.status(500).json({ error: 'Server error' });
    }
});

// Join chatroom
chatroomRouter.post('/:roomId/join', async (req, res) => {
    try {
        const { roomId } = req.params;
        const { phone } = req.body;
        
        await pool.query(
            'INSERT INTO room_members (room_id, phone) VALUES ($1, $2) ON CONFLICT DO NOTHING',
            [roomId, phone]
        );
        
        res.json({ success: true, message: 'Joined chatroom' });
    } catch (error) {
        console.error('Error joining chatroom:', error);
        res.status(500).json({ error: 'Server error' });
    }
});

app.use('/api/chatrooms', chatroomRouter);

// ==================== WALLET ROUTES ====================
const walletRouter = express.Router();

// Get transaction history for a user
walletRouter.get('/transactions/:phone', async (req, res) => {
    try {
        const { phone } = req.params;
        
        const result = await pool.query(
            `SELECT * FROM wallet_transactions 
             WHERE from_phone = $1 OR to_phone = $1 
             ORDER BY created_at DESC 
             LIMIT 100`,
            [phone]
        );
        
        res.json(result.rows);
    } catch (error) {
        console.error('Error fetching transactions:', error);
        res.status(500).json({ error: 'Server error' });
    }
});

// Deposit AiniCoin (Dev mode - instant credit)
walletRouter.post('/deposit', async (req, res) => {
    try {
        const { phone, amount } = req.body;
        
        if (!phone || !amount || amount <= 0) {
            return res.status(400).json({ error: 'Invalid request' });
        }
        
        const client = await pool.connect();
        
        try {
            await client.query('BEGIN');
            
            // Update user balance
            await client.query(
                'UPDATE users SET wallet_balance = wallet_balance + $1 WHERE phone = $2',
                [amount, phone]
            );
            
            // Create transaction record
            await client.query(
                `INSERT INTO wallet_transactions (from_phone, to_phone, amount, transaction_type, status, description)
                 VALUES ($1, $2, $3, $4, $5, $6)`,
                [phone, phone, amount, 'deposit', 'completed', 'Instant deposit (dev mode)']
            );
            
            await client.query('COMMIT');
            
            res.json({ 
                success: true, 
                message: 'Deposit successful',
                new_balance: (await pool.query('SELECT wallet_balance FROM users WHERE phone = $1', [phone])).rows[0].wallet_balance
            });
        } catch (error) {
            await client.query('ROLLBACK');
            throw error;
        } finally {
            client.release();
        }
    } catch (error) {
        console.error('Error processing deposit:', error);
        res.status(500).json({ error: 'Server error' });
    }
});

// Transfer AiniCoin to another user
walletRouter.post('/transfer', async (req, res) => {
    try {
        const { from_phone, to_phone, amount, description } = req.body;
        
        if (!from_phone || !to_phone || !amount || amount <= 0) {
            return res.status(400).json({ error: 'Invalid request' });
        }
        
        if (from_phone === to_phone) {
            return res.status(400).json({ error: 'Cannot transfer to yourself' });
        }
        
        const client = await pool.connect();
        
        try {
            await client.query('BEGIN');
            
            // Check sender balance
            const senderResult = await client.query(
                'SELECT wallet_balance FROM users WHERE phone = $1',
                [from_phone]
            );
            
            if (senderResult.rows.length === 0) {
                throw new Error('Sender not found');
            }
            
            const senderBalance = parseFloat(senderResult.rows[0].wallet_balance);
            
            if (senderBalance < amount) {
                await client.query('ROLLBACK');
                return res.status(400).json({ error: 'Insufficient balance' });
            }
            
            // Check if recipient exists
            const recipientResult = await client.query(
                'SELECT phone FROM users WHERE phone = $1',
                [to_phone]
            );
            
            if (recipientResult.rows.length === 0) {
                await client.query('ROLLBACK');
                return res.status(400).json({ error: 'Recipient not found' });
            }
            
            // Deduct from sender
            await client.query(
                'UPDATE users SET wallet_balance = wallet_balance - $1 WHERE phone = $2',
                [amount, from_phone]
            );
            
            // Add to recipient
            await client.query(
                'UPDATE users SET wallet_balance = wallet_balance + $1 WHERE phone = $2',
                [amount, to_phone]
            );
            
            // Create transaction record
            await client.query(
                `INSERT INTO wallet_transactions (from_phone, to_phone, amount, transaction_type, status, description)
                 VALUES ($1, $2, $3, $4, $5, $6)`,
                [from_phone, to_phone, amount, 'transfer', 'completed', description || 'AiniCoin transfer']
            );
            
            await client.query('COMMIT');
            
            res.json({ 
                success: true, 
                message: 'Transfer successful'
            });
        } catch (error) {
            await client.query('ROLLBACK');
            throw error;
        } finally {
            client.release();
        }
    } catch (error) {
        console.error('Error processing transfer:', error);
        res.status(500).json({ error: error.message || 'Server error' });
    }
});

app.use('/api/wallet', walletRouter);

// ==================== REVIEW ROUTES ====================
const reviewRouter = express.Router();

// Submit guest review of hotel
reviewRouter.post('/guest-review', async (req, res) => {
    try {
        const { booking_id, guest_phone, rating, review } = req.body;
        
        await pool.query(
            `UPDATE reviews 
             SET guest_to_hotel_rating = $1, 
                 guest_to_hotel_review = $2, 
                 guest_to_hotel_submitted_at = NOW()
             WHERE booking_id = $3`,
            [rating, review, booking_id]
        );
        
        // Check if both reviews submitted - publish if yes
        const result = await pool.query(
            'SELECT * FROM reviews WHERE booking_id = $1',
            [booking_id]
        );
        
        const reviewRecord = result.rows[0];
        if (reviewRecord.guest_to_hotel_submitted_at && reviewRecord.hotel_to_guest_submitted_at) {
            await pool.query(
                'UPDATE reviews SET published_at = NOW() WHERE booking_id = $1',
                [booking_id]
            );
            
            // Update guest rating
            await pool.query('SELECT update_guest_rating($1)', [guest_phone]);
        }
        
        res.json({ success: true, message: 'Review submitted' });
    } catch (error) {
        console.error('Error submitting review:', error);
        res.status(500).json({ error: 'Server error' });
    }
});

app.use('/api/reviews', reviewRouter);

// ==================== SOCKET.IO (Real-time messaging) ====================

const connectedUsers = new Map(); // phone -> socketId

io.on('connection', (socket) => {
    console.log('New client connected:', socket.id);
    
    // User authentication
    socket.on('authenticate', async (phone) => {
        connectedUsers.set(phone, socket.id);
        socket.phone = phone;
        
        // Update online status
        await pool.query('UPDATE users SET is_online = TRUE WHERE phone = $1', [phone]);
        await redisClient.set(`online:${phone}`, '1');
        
        console.log(`User ${phone} authenticated`);
    });
    
    // Send direct message
    socket.on('send_message', async (data) => {
        const { to_phone, content, message_type } = data;
        
        // Save message to database
        const result = await pool.query(
            'INSERT INTO messages (from_phone, to_phone, content, message_type) VALUES ($1, $2, $3, $4) RETURNING *',
            [socket.phone, to_phone, content, message_type || 'text']
        );
        
        const message = result.rows[0];
        
        // Send to recipient if online
        const recipientSocketId = connectedUsers.get(to_phone);
        if (recipientSocketId) {
            io.to(recipientSocketId).emit('new_message', message);
        }
        
        // Confirm to sender
        socket.emit('message_sent', message);
    });
    
    // Send chatroom message
    socket.on('send_room_message', async (data) => {
        const { room_id, content, message_type } = data;
        
        // Save message to database
        const result = await pool.query(
            'INSERT INTO messages (from_phone, room_id, content, message_type) VALUES ($1, $2, $3, $4) RETURNING *',
            [socket.phone, room_id, content, message_type || 'text']
        );
        
        const message = result.rows[0];
        
        // Broadcast to all room members
        io.to(`room_${room_id}`).emit('new_room_message', message);
    });
    
    // Join chatroom
    socket.on('join_room', (room_id) => {
        socket.join(`room_${room_id}`);
        console.log(`User ${socket.phone} joined room ${room_id}`);
    });
    
    // Typing indicator
    socket.on('typing', (data) => {
        const recipientSocketId = connectedUsers.get(data.to_phone);
        if (recipientSocketId) {
            io.to(recipientSocketId).emit('user_typing', { from_phone: socket.phone });
        }
    });
    
    // Disconnect
    socket.on('disconnect', async () => {
        if (socket.phone) {
            connectedUsers.delete(socket.phone);
            await pool.query('UPDATE users SET is_online = FALSE, last_seen = NOW() WHERE phone = $1', [socket.phone]);
            await redisClient.del(`online:${socket.phone}`);
            console.log(`User ${socket.phone} disconnected`);
        }
    });
});

// ==================== START SERVER ====================

const PORT = process.env.PORT || 3000;

server.listen(PORT, '0.0.0.0', () => {
    console.log(`
    ╔══════════════════════════════════════════════════════════╗
    ║                                                          ║
    ║           🚀 AINIFLOW SERVER (SOCIAL AINI) 🚀           ║
    ║                                                          ║
    ║  Features:                                               ║
    ║  • AiniFlow Messaging (Phone-based auth)                ║
    ║  • AINI Social Network                                  ║
    ║  • AiniCoin Wallet                                      ║
    ║  • Mutual Review System (Hotel ↔ Guest)                ║
    ║                                                          ║
    ║  Server running on: http://0.0.0.0:${PORT}                ║
    ║  WebSocket: ws://0.0.0.0:${PORT}                          ║
    ║                                                          ║
    ╚══════════════════════════════════════════════════════════╝
    `);
});

// Graceful shutdown
process.on('SIGTERM', async () => {
    console.log('SIGTERM received, shutting down gracefully...');
    server.close(() => {
        console.log('Server closed');
        pool.end();
        redisClient.quit();
        process.exit(0);
    });
});
