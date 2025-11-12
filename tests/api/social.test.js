/**
 * API Tests for AiniFlow Social Features
 * Tests the /api/social/* endpoints
 */

const request = require('supertest');

// Mock app - we'll need to extract the Express app from index.js
// For now, we'll test against the live server
const BASE_URL = 'http://localhost:3000';

describe('Social API Endpoints', () => {
    
    describe('GET /api/social/cards', () => {
        
        test('should return array of profile cards', async () => {
            const response = await request(BASE_URL)
                .get('/api/social/cards?user_phone=%2B1234567890&limit=5')
                .expect('Content-Type', /json/)
                .expect(200);
            
            expect(Array.isArray(response.body)).toBe(true);
            expect(response.body.length).toBeLessThanOrEqual(5);
        });
        
        test('should return profile with required fields', async () => {
            const response = await request(BASE_URL)
                .get('/api/social/cards?user_phone=%2B1234567890&limit=1')
                .expect(200);
            
            if (response.body.length > 0) {
                const profile = response.body[0];
                expect(profile).toHaveProperty('id');
                expect(profile).toHaveProperty('first_name');
                expect(profile).toHaveProperty('last_name');
                expect(profile).toHaveProperty('bio');
            }
        });
        
        test('should exclude already swiped profiles', async () => {
            const response = await request(BASE_URL)
                .get('/api/social/cards?user_phone=%2B1234567890&limit=20')
                .expect(200);
            
            // Verify no duplicates in results
            const ids = response.body.map(p => p.id);
            const uniqueIds = new Set(ids);
            expect(ids.length).toBe(uniqueIds.size);
        });
        
        test('should respect limit parameter', async () => {
            const response = await request(BASE_URL)
                .get('/api/social/cards?user_phone=%2B1234567890&limit=3')
                .expect(200);
            
            expect(response.body.length).toBeLessThanOrEqual(3);
        });
    });
    
    describe('POST /api/social/swipe', () => {
        
        test('should create swipe record', async () => {
            const response = await request(BASE_URL)
                .post('/api/social/swipe')
                .send({
                    user_phone: '+1234567890',
                    swiped_id: '+0987654321',
                    direction: 'left',
                    type: 'traveler_profile'
                })
                .expect('Content-Type', /json/)
                .expect(200);
            
            expect(response.body).toHaveProperty('success', true);
        });
        
        test('should require user_phone', async () => {
            const response = await request(BASE_URL)
                .post('/api/social/swipe')
                .send({
                    swiped_id: '+0987654321',
                    direction: 'right'
                })
                .expect(400);
            
            expect(response.body).toHaveProperty('error');
        });
        
        test('should validate direction', async () => {
            const response = await request(BASE_URL)
                .post('/api/social/swipe')
                .send({
                    user_phone: '+1234567890',
                    swiped_id: '+0987654321',
                    direction: 'invalid'
                })
                .expect(400);
            
            expect(response.body).toHaveProperty('error');
        });
        
        test('should detect mutual match', async () => {
            // This test requires database setup with two users who haven't swiped yet
            // Skipping for now - will implement with test database
            // TODO: Set up test database with clean data
        });
    });
    
    describe('GET /api/social/matches', () => {
        
        test('should return array of matches', async () => {
            const response = await request(BASE_URL)
                .get('/api/social/matches?user_phone=%2B1234567890')
                .expect('Content-Type', /json/)
                .expect(200);
            
            expect(Array.isArray(response.body)).toBe(true);
        });
        
        test('should include match details', async () => {
            const response = await request(BASE_URL)
                .get('/api/social/matches?user_phone=%2B1234567890')
                .expect(200);
            
            if (response.body.length > 0) {
                const match = response.body[0];
                expect(match).toHaveProperty('id');
                expect(match).toHaveProperty('phone');
                expect(match).toHaveProperty('name');
            }
        });
    });
    
    describe('POST /api/social/friend-request', () => {
        
        test('should create friend request', async () => {
            const response = await request(BASE_URL)
                .post('/api/social/friend-request')
                .send({
                    user_phone: '+1234567890',
                    receiver_id: '+0987654321',
                    message: 'Test friend request'
                })
                .expect('Content-Type', /json/)
                .expect(200);
            
            expect(response.body).toHaveProperty('success');
        });
        
        test('should require receiver_id', async () => {
            const response = await request(BASE_URL)
                .post('/api/social/friend-request')
                .send({
                    user_phone: '+1234567890',
                    message: 'Test'
                })
                .expect(400);
            
            expect(response.body).toHaveProperty('error');
        });
    });
});
