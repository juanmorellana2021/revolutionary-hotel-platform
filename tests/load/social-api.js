import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');

// Test configuration
export const options = {
  stages: [
    { duration: '30s', target: 20 },   // Ramp up to 20 users
    { duration: '1m', target: 50 },    // Ramp up to 50 users
    { duration: '2m', target: 100 },   // Ramp up to 100 users
    { duration: '1m', target: 100 },   // Stay at 100 users
    { duration: '30s', target: 200 },  // Spike to 200 users
    { duration: '1m', target: 200 },   // Stay at 200
    { duration: '1m', target: 0 },     // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],  // 95% of requests under 500ms
    http_req_failed: ['rate<0.01'],    // Less than 1% errors
    errors: ['rate<0.1'],              // Less than 10% errors
  },
};

const BASE_URL = 'https://ainiflow.com';

// Test user phones (rotate to simulate different users)
const testUsers = [
  '+1234567890',
  '+0987654321',
  '+51997946667',
  '+14437965990',
];

export default function () {
  // Pick random user for this iteration
  const userPhone = testUsers[Math.floor(Math.random() * testUsers.length)];
  
  // Test 1: Get profile cards
  const cardsResponse = http.get(
    `${BASE_URL}/api/social/cards?user_phone=${encodeURIComponent(userPhone)}&limit=20`
  );
  
  const cardsCheck = check(cardsResponse, {
    'cards: status is 200': (r) => r.status === 200,
    'cards: response time < 500ms': (r) => r.timings.duration < 500,
    'cards: returns array': (r) => {
      try {
        const body = JSON.parse(r.body);
        return Array.isArray(body);
      } catch {
        return false;
      }
    },
  });
  
  errorRate.add(!cardsCheck);
  
  sleep(1);
  
  // Test 2: Swipe action
  const swipeResponse = http.post(
    `${BASE_URL}/api/social/swipe`,
    JSON.stringify({
      user_phone: userPhone,
      swiped_id: testUsers[Math.floor(Math.random() * testUsers.length)],
      direction: Math.random() > 0.5 ? 'right' : 'left',
      type: 'traveler_profile'
    }),
    {
      headers: { 'Content-Type': 'application/json' },
    }
  );
  
  const swipeCheck = check(swipeResponse, {
    'swipe: status is 200 or 400': (r) => r.status === 200 || r.status === 400,
    'swipe: response time < 300ms': (r) => r.timings.duration < 300,
  });
  
  errorRate.add(!swipeCheck);
  
  sleep(2);
  
  // Test 3: Get matches
  const matchesResponse = http.get(
    `${BASE_URL}/api/social/matches?user_phone=${encodeURIComponent(userPhone)}`
  );
  
  const matchesCheck = check(matchesResponse, {
    'matches: status is 200': (r) => r.status === 200,
    'matches: response time < 400ms': (r) => r.timings.duration < 400,
  });
  
  errorRate.add(!matchesCheck);
  
  sleep(1);
}

// Teardown function - runs once at the end
export function teardown(data) {
  console.log('Load test complete!');
}
