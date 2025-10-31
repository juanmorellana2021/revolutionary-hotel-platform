-- AiniFlow Sample Chatrooms
-- Location-based and Topic-based chatrooms

-- Get a test user phone for created_by (or use the one you just created)
-- Replace with your actual phone number if needed

-- Location-Based Chatrooms
INSERT INTO chatrooms (name, description, room_type, created_by) VALUES 
-- USA
('Miami Beach', 'Connect with travelers in Miami Beach, Florida', 'public', '+1234567890'),
('New York City', 'The Big Apple - hotels, restaurants, and attractions', 'public', '+1234567890'),
('Las Vegas', 'Vegas tips, shows, and casino recommendations', 'public', '+1234567890'),
('Los Angeles', 'LA travel guide - beaches, Hollywood, and more', 'public', '+1234567890'),

-- Mexico
('Cancun', 'Cancun & Riviera Maya beach paradise', 'public', '+1234567890'),
('Playa del Carmen', 'Playa del Carmen travelers community', 'public', '+1234567890'),
('Tulum', 'Tulum ruins, cenotes, and beach clubs', 'public', '+1234567890'),

-- Europe
('Paris', 'Paris - the city of lights and romance', 'public', '+1234567890'),
('Barcelona', 'Barcelona - Gaudi, beaches, and tapas', 'public', '+1234567890'),
('Rome', 'Rome - ancient history and Italian cuisine', 'public', '+1234567890'),
('London', 'London travelers - pubs, museums, and royal sites', 'public', '+1234567890'),

-- Asia
('Tokyo', 'Tokyo - sushi, temples, and neon lights', 'public', '+1234567890'),
('Bangkok', 'Bangkok - street food and temples', 'public', '+1234567890'),
('Bali', 'Bali - beaches, surfing, and culture', 'public', '+1234567890');

-- Topic-Based Chatrooms
INSERT INTO chatrooms (name, description, room_type, created_by) VALUES 
-- Travel Styles
('Solo Travelers', 'For those exploring the world alone', 'public', '+1234567890'),
('Family Vacations', 'Family-friendly travel tips and destinations', 'public', '+1234567890'),
('Couples Getaways', 'Romantic destinations and date ideas', 'public', '+1234567890'),
('Budget Backpackers', 'Travel cheap, travel far', 'public', '+1234567890'),
('Luxury Resorts', 'High-end hotels and exclusive experiences', 'public', '+1234567890'),
('Digital Nomads', 'Work remotely from anywhere in the world', 'public', '+1234567890'),

-- Interests
('Foodies & Restaurants', 'Best local food and dining experiences', 'public', '+1234567890'),
('Adventure Seekers', 'Hiking, diving, skydiving, and extreme sports', 'public', '+1234567890'),
('Beach Lovers', 'Best beaches, water sports, and island life', 'public', '+1234567890'),
('City Explorers', 'Urban adventures, museums, and nightlife', 'public', '+1234567890'),
('Nature & Wildlife', 'National parks, safaris, and eco-tourism', 'public', '+1234567890'),
('Photography Travel', 'Capture the world through your lens', 'public', '+1234567890'),
('Festival & Events', 'Music festivals, carnivals, and cultural events', 'public', '+1234567890'),

-- Practical
('Travel Tips & Hacks', 'Money-saving tips and travel tricks', 'public', '+1234567890'),
('Visa & Immigration', 'Visa requirements and border crossing tips', 'public', '+1234567890'),
('Flight Deals', 'Share and find the best flight deals', 'public', '+1234567890'),
('Hotel Reviews', 'Honest reviews of hotels and accommodations', 'public', '+1234567890');
