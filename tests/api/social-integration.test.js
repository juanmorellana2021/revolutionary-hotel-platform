/**
 * API Tests for AiniFlow Social Features - VPS Version
 * Tests against the live server via SSH tunnel
 */

const request = require('supertest');

// Test against production server
const BASE_URL = 'https://ainiflow.com';

describe('Social API Endpoints - Integration Tests', () => {
    
    describe('GET /api/social/cards', () => {
        
        test('should return array of profile cards', async () => {
            const response = await request(BASE_URL)
                .get('/api/social/cards?user_phone=%2B1234567890&limit=5')
                .expect('Content-Type', /json/)
                .expect(200);
            
            expect(Array.isArray(response.body)).toBe(true);
            expect(response.body.length).toBeGreaterThan(0);
            expect(response.body.length).toBeLessThanOrEqual(5);
        });
        
        test('should return profile with required fields', async () => {
            const response = await request(BASE_URL)
                .get('/api/social/cards?user_phone=%2B1234567890&limit=1')
                .expect(200);
            
            expect(response.body.length).toBeGreaterThan(0);
            const profile = response.body[0];
            
            // Required fields
            expect(profile).toHaveProperty('id');
            expect(profile).toHaveProperty('first_name');
            expect(profile).toHaveProperty('bio');
            expect(profile).toHaveProperty('interests');
            
            // Data types
            expect(typeof profile.id).toBe('string');
            expect(typeof profile.first_name).toBe('string');
            expect(typeof profile.level).toBe('number');
        });
        
        test('should respect limit parameter', async () => {
            const response = await request(BASE_URL)
                .get('/api/social/cards?user_phone=%2B1234567890&limit=2')
                .expect(200);
            
            expect(response.body.length).toBeLessThanOrEqual(2);
        });
        
        test('should handle missing user_phone gracefully', async () => {
            const response = await request(BASE_URL)
                .get('/api/social/cards?limit=5');
            
            // Should either return cards or proper error
            expect([200, 401]).toContain(response.status);
        });
    });
    
    describe('POST /api/social/swipe', () => {
        
        test('should accept valid swipe left', async () => {
            const response = await request(BASE_URL)
                .post('/api/social/swipe')
                .send({
                    user_phone: '+1234567890',
                    swiped_id: '+0987654321',
                    direction: 'left',
                    type: 'traveler_profile'
                })
                .set('Content-Type', 'application/json')
                .expect('Content-Type', /json/);
            
            expect([200, 400]).toContain(response.status);
            if (response.status === 200) {
                expect(response.body).toHaveProperty('success');
            }
        });
        
        test('should accept valid swipe right', async () => {
            const response = await request(BASE_URL)
                .post('/api/social/swipe')
                .send({
                    user_phone: '+1234567890',
                    swiped_id: '+14437965990',
                    direction: 'right',
                    type: 'traveler_profile'
                })
                .set('Content-Type', 'application/json')
                .expect('Content-Type', /json/);
            
            expect([200, 400]).toContain(response.status);
            if (response.status === 200) {
                expect(response.body).toHaveProperty('success');
                expect(response.body).toHaveProperty('match');
                expect(typeof response.body.match).toBe('boolean');
            }
        });
    });
    
    describe('GET /api/social/matches', () => {
        
        test('should return array of matches', async () => {
            const response = await request(BASE_URL)
                .get('/api/social/matches?user_phone=%2B1234567890')
                .expect('Content-Type', /json/);
            
            expect([200, 401]).toContain(response.status);
            if (response.status === 200) {
                expect(Array.isArray(response.body)).toBe(true);
            }
        });
    });
    
    describe('POST /api/social/friend-request', () => {
        
        test('should validate required fields', async () => {
            const response = await request(BASE_URL)
                .post('/api/social/friend-request')
                .send({
                    user_phone: '+1234567890',
                    // Missing receiver_id
                    message: 'Test'
                })
                .set('Content-Type', 'application/json');
            
            // Should return 400 for missing required field
            expect([400, 401]).toContain(response.status);
        });
    });
    
    describe('API Health & Performance', () => {
        
        test('should respond within 1 second', async () => {
            const start = Date.now();
            
            await request(BASE_URL)
                .get('/api/social/cards?user_phone=%2B1234567890&limit=10');
            
            const duration = Date.now() - start;
            expect(duration).toBeLessThan(1000);
        });
        
        test('should handle concurrent requests', async () => {
            const requests = Array(5).fill(null).map(() => 
                request(BASE_URL)
                    .get('/api/social/cards?user_phone=%2B1234567890&limit=5')
            );
            
            const responses = await Promise.all(requests);
            
            responses.forEach(response => {
                expect([200, 401]).toContain(response.status);
            });
        });
    });
});
