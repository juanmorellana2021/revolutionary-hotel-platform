// 🛡️ SECURITY TESTS - Verify hardening works
// Run: npm test -- security.test.js

const request = require('supertest');
const app = require('./server4_app');

describe('🛡️ Security Testing Suite', () => {
    
    // ==================== SQL INJECTION TESTS ====================
    
    describe('SQL Injection Prevention', () => {
        test('Should reject SQL injection in phone field', async () => {
            const response = await request(app)
                .get('/api/social/cards')
                .query({ user_phone: "' OR '1'='1" });
            
            expect(response.status).toBe(401);
            expect(response.body.error).toContain('Invalid');
        });
        
        test('Should reject SQL injection in limit parameter', async () => {
            const response = await request(app)
                .get('/api/social/cards')
                .query({ 
                    user_phone: '+12345678901',
                    limit: "'; DROP TABLE users; --" 
                });
            
            // Should parse as NaN and use default limit
            expect(response.status).not.toBe(500);
        });
    });
    
    // ==================== XSS TESTS ====================
    
    describe('XSS (Cross-Site Scripting) Prevention', () => {
        test('Should sanitize message field in friend request', async () => {
            const xssPayload = '<script>alert("XSS")</script>';
            
            const response = await request(app)
                .post('/api/social/friend-request')
                .send({
                    user_phone: '+12345678901',
                    receiver_id: '+19876543210',
                    message: xssPayload
                });
            
            // Check database to ensure it's escaped
            const result = await pool.query(`
                SELECT message FROM friend_requests 
                WHERE sender_phone = '+12345678901' 
                ORDER BY created_at DESC LIMIT 1
            `);
            
            expect(result.rows[0].message).not.toContain('<script>');
            expect(result.rows[0].message).toContain('&lt;script&gt;');
        });
        
        test('Should escape HTML in user bio', async () => {
            const response = await request(app)
                .get('/api/social/cards')
                .query({ user_phone: '+12345678901' });
            
            const cards = response.body;
            
            cards.forEach(card => {
                expect(card.bio).not.toMatch(/<script|<iframe|onclick=/i);
            });
        });
    });
    
    // ==================== RATE LIMITING TESTS ====================
    
    describe('Rate Limiting', () => {
        test('Should block excessive swipes', async () => {
            const promises = [];
            
            // Send 60 swipes in quick succession (limit is 50/hour)
            for (let i = 0; i < 60; i++) {
                promises.push(
                    request(app)
                        .post('/api/social/swipe')
                        .send({
                            user_phone: '+12345678901',
                            swiped_id: `+1987654321${i}`,
                            direction: 'right',
                            type: 'traveler'
                        })
                );
            }
            
            const responses = await Promise.all(promises);
            
            // At least one should be rate limited
            const rateLimited = responses.some(r => r.status === 429);
            expect(rateLimited).toBe(true);
        });
        
        test('Should block excessive friend requests', async () => {
            const promises = [];
            
            // Send 25 friend requests (limit is 20/day)
            for (let i = 0; i < 25; i++) {
                promises.push(
                    request(app)
                        .post('/api/social/friend-request')
                        .send({
                            user_phone: '+12345678901',
                            receiver_id: `+1555000${i.toString().padStart(4, '0')}`,
                            message: 'Test request'
                        })
                );
            }
            
            const responses = await Promise.all(promises);
            
            // At least one should be rate limited
            const rateLimited = responses.some(r => r.status === 429);
            expect(rateLimited).toBe(true);
        });
    });
    
    // ==================== AUTHENTICATION TESTS ====================
    
    describe('Authentication & Authorization', () => {
        test('Should reject requests without phone number', async () => {
            const response = await request(app)
                .get('/api/social/cards');
            
            expect(response.status).toBe(401);
        });
        
        test('Should reject invalid phone format', async () => {
            const response = await request(app)
                .get('/api/social/cards')
                .query({ user_phone: 'invalid-phone' });
            
            expect(response.status).toBe(401);
        });
        
        test('Should prevent self-swipe', async () => {
            const response = await request(app)
                .post('/api/social/swipe')
                .send({
                    user_phone: '+12345678901',
                    swiped_id: '+12345678901',  // Same as user_phone
                    direction: 'right'
                });
            
            expect(response.status).toBe(400);
            expect(response.body.error).toContain('yourself');
        });
    });
    
    // ==================== INPUT VALIDATION TESTS ====================
    
    describe('Input Validation', () => {
        test('Should enforce limit bounds', async () => {
            const response = await request(app)
                .get('/api/social/cards')
                .query({ 
                    user_phone: '+12345678901',
                    limit: 999999999  // Try to fetch 999 million records
                });
            
            expect(response.status).toBe(200);
            expect(response.body.length).toBeLessThanOrEqual(100);  // Max 100
        });
        
        test('Should validate swipe direction', async () => {
            const response = await request(app)
                .post('/api/social/swipe')
                .send({
                    user_phone: '+12345678901',
                    swiped_id: '+19876543210',
                    direction: 'invalid-direction'  // Not 'left' or 'right'
                });
            
            expect(response.status).toBe(400);
        });
        
        test('Should truncate long messages', async () => {
            const longMessage = 'A'.repeat(10000);  // 10,000 characters
            
            const response = await request(app)
                .post('/api/social/friend-request')
                .send({
                    user_phone: '+12345678901',
                    receiver_id: '+19876543210',
                    message: longMessage
                });
            
            // Check database
            const result = await pool.query(`
                SELECT LENGTH(message) as len FROM friend_requests 
                WHERE sender_phone = '+12345678901' 
                ORDER BY created_at DESC LIMIT 1
            `);
            
            expect(result.rows[0].len).toBeLessThanOrEqual(500);
        });
    });
    
    // ==================== CSRF TESTS ====================
    
    describe('CSRF Protection', () => {
        test('Should reject POST without CSRF token', async () => {
            const response = await request(app)
                .post('/api/social/swipe')
                .send({
                    user_phone: '+12345678901',
                    swiped_id: '+19876543210',
                    direction: 'right'
                });
            
            expect(response.status).toBe(403);
        });
        
        test('Should accept POST with valid CSRF token', async () => {
            // First get token
            const tokenResponse = await request(app)
                .get('/api/csrf-token');
            
            const token = tokenResponse.body.csrfToken;
            
            // Then use it
            const response = await request(app)
                .post('/api/social/swipe')
                .set('CSRF-Token', token)
                .send({
                    user_phone: '+12345678901',
                    swiped_id: '+19876543210',
                    direction: 'right'
                });
            
            expect(response.status).not.toBe(403);
        });
    });
    
    // ==================== SECURITY HEADERS TESTS ====================
    
    describe('Security Headers', () => {
        test('Should include security headers', async () => {
            const response = await request(app).get('/health');
            
            expect(response.headers['x-frame-options']).toBe('DENY');
            expect(response.headers['x-content-type-options']).toBe('nosniff');
            expect(response.headers['x-xss-protection']).toBe('1; mode=block');
            expect(response.headers['strict-transport-security']).toContain('max-age=31536000');
        });
        
        test('Should include CSP header', async () => {
            const response = await request(app).get('/health');
            
            expect(response.headers['content-security-policy']).toBeDefined();
            expect(response.headers['content-security-policy']).toContain("default-src 'self'");
        });
    });
    
    // ==================== ERROR HANDLING TESTS ====================
    
    describe('Error Handling', () => {
        test('Should not expose stack traces in production', async () => {
            process.env.NODE_ENV = 'production';
            
            const response = await request(app)
                .get('/api/nonexistent-route');
            
            expect(response.body).not.toHaveProperty('stack');
            
            process.env.NODE_ENV = 'test';
        });
    });
});
