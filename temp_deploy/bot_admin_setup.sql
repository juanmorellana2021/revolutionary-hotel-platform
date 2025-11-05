-- Bot Admin Database Setup
-- Run this SQL to create the necessary tables for WhatsApp bot administration

-- Bot prices table
CREATE TABLE IF NOT EXISTS bot_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    item VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_price (category, item),
    INDEX idx_category (category)
);

-- Bot responses table
CREATE TABLE IF NOT EXISTS bot_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    command VARCHAR(50) NOT NULL UNIQUE,
    response TEXT NOT NULL,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_command (command)
);

-- WhatsApp conversations tracking
CREATE TABLE IF NOT EXISTS whatsapp_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    bot_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone_number),
    INDEX idx_created (created_at)
);

-- Insert default prices for Casa de Paz
INSERT INTO bot_prices (category, item, price, updated_by) VALUES
-- Rooms
('rooms', 'individual', 45.00, 1),
('rooms', 'doble', 65.00, 1),
('rooms', 'suite', 90.00, 1),
-- Ceremonies
('ceremonies', 'ayahuasca_1day', 180.00, 1),
('ceremonies', 'sanpedro_1day', 120.00, 1),
('ceremonies', 'retiro_7days', 980.00, 1),
-- Tours
('tours', 'machupicchu_1day', 180.00, 1),
('tours', 'valle_sagrado', 65.00, 1),
('tours', 'waqrapukara', 85.00, 1)
ON DUPLICATE KEY UPDATE 
    price = VALUES(price),
    updated_at = CURRENT_TIMESTAMP;

-- Insert default bot responses
INSERT INTO bot_responses (command, response, updated_by) VALUES
('ayuda', '🌟 *CASA DE PAZ - HOTEL ESPIRITUAL* 🌟\n\n*Comandos disponibles:*\n\n🏨 *!habitaciones* - Ver habitaciones disponibles\n💰 *!precios* - Lista completa de precios\n🍄 *!ceremonias* - Información sobre ceremonias\n🏔️ *!tours* - Tours disponibles\n📞 *!contacto* - Información de contacto\n🪙 *!hotelcoins* - Sistema de recompensas\n📍 *!ubicacion* - Cómo llegar\n❓ *!info* - Información general\n\n¿En qué te puedo ayudar? 🙏', 1),

('precios', '💰 *PRECIOS CASA DE PAZ* 💰\n\n🏨 *HABITACIONES (por noche):*\n• Individual: $45 USD\n• Doble: $65 USD  \n• Suite Valle Sagrado: $90 USD\n\n🍄 *CEREMONIAS:*\n• Ayahuasca (1 día): $180 USD\n• San Pedro (1 día): $120 USD\n• Retiro 7 días: $980 USD\n\n🏔️ *TOURS:*\n• Machu Picchu (1 día): $180 USD\n• Valle Sagrado: $65 USD\n• Waqrapukara: $85 USD\n\n*Incluye:*\n✅ Alojamiento\n✅ Alimentación sagrada\n✅ Guías especializados\n✅ Transporte\n\n📞 Contacto: +51 984 123 456', 1),

('ceremonias', '🍄 *CEREMONIAS SAGRADAS* 🍄\n\n*Casa de Paz* es un espacio sagrado dedicado a la sanación y el crecimiento espiritual.\n\n🌱 *AYAHUASCA:*\n• Precio: $180 USD (1 día)\n• Incluye: Ceremonia nocturna, preparación, integración\n• Maestros shipibos experimentados\n• Dieta previa incluida\n\n🌵 *SAN PEDRO (HUACHUMA):*\n• Precio: $120 USD (1 día)\n• Ceremonia diurna en la naturaleza\n• Conexión con los Apus (montañas sagradas)\n• Caminata meditativa\n\n🏔️ *RETIRO 7 DÍAS:*\n• Precio: $980 USD\n• 3 ceremonias de Ayahuasca\n• 2 ceremonias de San Pedro\n• Talleres de integración\n• Alojamiento y alimentación completa\n\n⚠️ *Importante:* Requiere preparación previa y entrevista.', 1),

('tours', '🏔️ *TOURS MÍSTICOS* 🏔️\n\n🏛️ *MACHU PICCHU SAGRADO:*\n• $180 USD - Tour de 1 día\n• Salida 4:00 AM desde Cusco\n• Tren + Bus panorámico\n• Guía especializado en historia Inca\n• Ceremonia de gratitud en la ciudadela\n\n🌄 *VALLE SAGRADO:*\n• $65 USD - Tour día completo\n• Pisaq, Ollantaytambo, Chinchero\n• Mercado artesanal\n• Almuerzo típico incluido\n\n⛰️ *WAQRAPUKARA:*\n• $85 USD - Fortaleza en las nubes\n• Trekking moderado 2-3 horas\n• Vista panorámica increíble\n• Ceremonia de apreciación\n\n*Todos incluyen:*\n✅ Transporte\n✅ Guía profesional\n✅ Entradas\n✅ Refrigerios\n\n📞 Reservas: +51 984 123 456', 1)

ON DUPLICATE KEY UPDATE 
    response = VALUES(response),
    updated_at = CURRENT_TIMESTAMP;