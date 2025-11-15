-- Create partner records for existing experiences
INSERT INTO aini_experience_partners (id, business_name, owner_name, email, phone, country, city, status) VALUES
(1, 'Machu Picchu Tours', 'Admin Test', 'partner1@ainitravel.com', '+51999999999', 'Peru', 'Cusco', 'active'),
(2, 'Ayahuasca Experience Center', 'Admin Test', 'partner2@ainitravel.com', '+51888888888', 'Peru', 'Pisac, Cusco, Peru', 'active')
ON DUPLICATE KEY UPDATE business_name = VALUES(business_name);
