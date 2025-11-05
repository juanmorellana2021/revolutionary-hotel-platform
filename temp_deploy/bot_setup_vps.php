<?php
// Quick setup script for bot data in VPS
// Run this file on the VPS to complete bot setup

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel_booking_system";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "✅ Connected to hotel_booking_system database\n\n";
    
    // Insert default prices
    $prices = [
        ['rooms', 'individual', 45.00],
        ['rooms', 'doble', 65.00],
        ['rooms', 'suite', 90.00],
        ['ceremonies', 'ayahuasca_1day', 180.00],
        ['ceremonies', 'sanpedro_1day', 120.00],
        ['ceremonies', 'retiro_7days', 980.00],
        ['tours', 'machupicchu_1day', 180.00],
        ['tours', 'valle_sagrado', 65.00],
        ['tours', 'waqrapukara', 85.00]
    ];
    
    $stmt = $conn->prepare("INSERT INTO bot_prices (category, item, price, updated_by) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE price = VALUES(price)");
    
    foreach ($prices as $price) {
        $stmt->bind_param("ssd", $price[0], $price[1], $price[2]);
        $stmt->execute();
    }
    
    echo "✅ Default prices inserted\n\n";
    
    // Insert default responses
    $responses = [
        ['ayuda', '🌟 *CASA DE PAZ - HOTEL ESPIRITUAL* 🌟\n\n*Comandos disponibles:*\n\n🏨 *!habitaciones* - Ver habitaciones disponibles\n💰 *!precios* - Lista completa de precios\n🍄 *!ceremonias* - Información sobre ceremonias\n🏔️ *!tours* - Tours disponibles\n📞 *!contacto* - Información de contacto\n🪙 *!hotelcoins* - Sistema de recompensas\n📍 *!ubicacion* - Cómo llegar\n❓ *!info* - Información general\n\n¿En qué te puedo ayudar? 🙏'],
        ['precios', 'Precios dinámicos cargados desde base de datos'],
        ['ceremonias', 'Información de ceremonias cargada desde base de datos'],
        ['tours', 'Tours cargados desde base de datos']
    ];
    
    $stmt2 = $conn->prepare("INSERT INTO bot_responses (command, response, updated_by) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE response = VALUES(response)");
    
    foreach ($responses as $response) {
        $stmt2->bind_param("ss", $response[0], $response[1]);
        $stmt2->execute();
    }
    
    echo "✅ Default bot responses inserted\n\n";
    
    // Check tables
    echo "📊 Bot tables status:\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM bot_prices");
    $count = $result->fetch_assoc()['count'];
    echo "- bot_prices: $count records\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM bot_responses");
    $count = $result->fetch_assoc()['count'];
    echo "- bot_responses: $count records\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM whatsapp_conversations");
    $count = $result->fetch_assoc()['count'];
    echo "- whatsapp_conversations: $count records\n\n";
    
    echo "🎉 Bot setup complete!\n";
    echo "🔗 Access admin panel at: /whatsapp_bot_admin.php\n";
    echo "🤖 Test bot at: /casa_de_paz_bot_dynamic.php?test=1&message=!precios\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>