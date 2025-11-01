-- Create hotels table for AiNi Travel Platform
-- Testing data with 6 hotels including Samay Wasi Casa de Paz

CREATE TABLE IF NOT EXISTS `hotels` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `emoji` VARCHAR(10) DEFAULT '🏨',
  `rating` DECIMAL(2,1) DEFAULT 4.0,
  `price` DECIMAL(10,2) NOT NULL,
  `features` TEXT NOT NULL COMMENT 'JSON array of features',
  `aini_coins` INT(11) NOT NULL DEFAULT 0,
  `category` VARCHAR(100) DEFAULT 'standard',
  `latitude` DECIMAL(10,6) NOT NULL,
  `longitude` DECIMAL(10,6) NOT NULL,
  `images` TEXT NOT NULL COMMENT 'JSON array of image URLs',
  `description` TEXT,
  `amenities` TEXT COMMENT 'JSON array of amenities',
  `rooms_available` INT(11) DEFAULT 10,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_price` (`price`),
  KEY `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert testing data (6 hotels)
INSERT INTO `hotels` (`name`, `location`, `emoji`, `rating`, `price`, `features`, `aini_coins`, `category`, `latitude`, `longitude`, `images`, `description`) VALUES
-- 1. SAMAY WASI CASA DE PAZ (Your real hotel!)
('Samay Wasi Casa de Paz', 'Cusco, Perú', '🏡', 4.9, 95, 
'["Vista Montaña", "WiFi Gratis", "Desayuno Incluido", "Terraza", "Tours"]', 
28, 'boutique mountain', 
-13.5186, -71.9788,
'["https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80", "https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&q=80", "https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800&q=80", "https://images.unsplash.com/photo-1584132915807-fd1f5fbc078f?w=800&q=80"]',
'Hotel boutique en el corazón de Cusco. Ambiente familiar y acogedor con vistas espectaculares a las montañas andinas.'),

-- 2. Ocean View Resort
('Ocean View Resort', 'Miami Beach, FL', '🏖️', 4.8, 180, 
'["Vista al Mar", "Piscina", "WiFi", "Spa"]', 
36, 'luxury beach', 
25.7907, -80.1300,
'["https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=800&q=80", "https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&q=80", "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80", "https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80"]',
'Luxury beachfront resort with stunning ocean views and world-class amenities.'),

-- 3. Downtown Business Hotel
('Downtown Business Hotel', 'New York, NY', '🏙️', 4.6, 220, 
'["Centro Negocios", "Gimnasio", "WiFi", "Restaurante"]', 
44, 'business luxury', 
40.7580, -73.9855,
'["https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800&q=80", "https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&q=80", "https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=800&q=80", "https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&q=80"]',
'Modern business hotel in the heart of Manhattan. Perfect for corporate travelers.'),

-- 4. Budget Traveler Inn
('Budget Traveler Inn', 'Austin, TX', '🏨', 4.3, 85, 
'["WiFi", "Estacionamiento", "Desayuno"]', 
17, 'budget', 
30.2672, -97.7431,
'["https://images.unsplash.com/photo-1568495248636-6432b97bd949?w=800&q=80", "https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&q=80", "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?w=800&q=80", "https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=800&q=80"]',
'Affordable and comfortable hotel for budget-conscious travelers. Great location in Austin.'),

-- 5. Family Paradise Resort
('Family Paradise Resort', 'Orlando, FL', '👨‍👩‍👧‍👦', 4.7, 195, 
'["Kids Club", "Piscina", "Transporte Parques", "Restaurante"]', 
39, 'family luxury', 
28.3852, -81.5639,
'["https://images.unsplash.com/photo-1563911302283-d2bc129e7570?w=800&q=80", "https://images.unsplash.com/photo-1584132915807-fd1f5fbc078f?w=800&q=80", "https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=800&q=80", "https://images.unsplash.com/photo-1601918774946-25832a4be0d6?w=800&q=80"]',
'Family-friendly resort near Disney World and Universal Studios. Kids activities and entertainment.'),

-- 6. Beachfront Paradise
('Beachfront Paradise', 'Cancún, México', '🌴', 4.9, 250, 
'["Todo Incluido", "Playa", "Spa", "Varias Piscinas"]', 
50, 'luxury beach', 
21.1619, -86.8515,
'["https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=800&q=80", "https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800&q=80", "https://images.unsplash.com/photo-1510414842594-a61c69b5ae57?w=800&q=80", "https://images.unsplash.com/photo-1596436889106-be35e843f974?w=800&q=80"]',
'All-inclusive luxury resort on the beautiful beaches of Cancún. Paradise on Earth.');
