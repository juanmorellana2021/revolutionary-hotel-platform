<?php
/**
 * Casa de Paz Bot - WhatsApp Bot with Dynamic Pricing
 * Enhanced version with database integration and admin panel support
 */

// Database connection
$servername = "localhost";
$username = "hotelapp";
$password = "hotel123";
$dbname = "hotel_booking_system"; // Correct database name

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8");
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $conn = null;
}

/**
 * Get price from database or fallback to default
 */
function getPrice($category, $item, $default = 0) {
    global $conn;
    
    if (!$conn) return $default;
    
    try {
        $stmt = $conn->prepare("SELECT price FROM bot_prices WHERE category = ? AND item = ?");
        $stmt->bind_param("ss", $category, $item);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return floatval($row['price']);
        }
    } catch (Exception $e) {
        error_log("Error getting price: " . $e->getMessage());
    }
    
    return $default;
}

/**
 * Get custom response from database or fallback to default
 */
function getResponse($command, $default = '') {
    global $conn;
    
    if (!$conn) return $default;
    
    try {
        $stmt = $conn->prepare("SELECT response FROM bot_responses WHERE command = ?");
        $stmt->bind_param("s", $command);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['response'];
        }
    } catch (Exception $e) {
        error_log("Error getting response: " . $e->getMessage());
    }
    
    return $default;
}

/**
 * Log conversation for analytics
 */
function logConversation($phone, $message, $response) {
    global $conn;
    
    if (!$conn) return;
    
    try {
        $stmt = $conn->prepare("INSERT INTO whatsapp_conversations (phone_number, message, bot_response) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $phone, $message, $response);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error logging conversation: " . $e->getMessage());
    }
}

/**
 * Get real room availability from database
 */
function getRoomAvailability() {
    global $conn;
    
    if (!$conn) {
        return "🏨 *HABITACIONES CASA DE PAZ* 🏨\n\n*Consulta disponibilidad directamente:*\n📞 +51 984 123 456\n📧 info@casadepaz.com";
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT rt.type_name, rt.base_price,
                COUNT(r.id) as total_rooms,
                COUNT(CASE WHEN b.room_id IS NULL THEN 1 END) as available_rooms
            FROM room_types rt
            LEFT JOIN rooms r ON rt.id = r.room_type_id
            LEFT JOIN bookings b ON r.id = b.room_id 
                AND b.check_out_date >= CURDATE() 
                AND b.status IN ('confirmed', 'checked_in')
            GROUP BY rt.id, rt.type_name, rt.base_price
            ORDER BY rt.base_price
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $response = "🏨 *DISPONIBILIDAD CASA DE PAZ* 🏨\n\n";
        
        while ($row = $result->fetch_assoc()) {
            $status = $row['available_rooms'] > 0 ? "✅ Disponible" : "❌ Ocupado";
            $response .= "🛏️ *{$row['type_name']}*\n";
            $response .= "💰 ${$row['base_price']}/noche\n";
            $response .= "📊 {$row['available_rooms']}/{$row['total_rooms']} disponibles\n";
            $response .= "$status\n\n";
        }
        
        $response .= "📞 Reservas: +51 984 123 456\n";
        $response .= "🌐 casadepaz.com";
        
        return $response;
        
    } catch (Exception $e) {
        error_log("Error getting availability: " . $e->getMessage());
        return "🏨 *HABITACIONES CASA DE PAZ* 🏨\n\n*Error al consultar disponibilidad*\n📞 Contacta: +51 984 123 456";
    }
}

// Main bot logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['messages'])) {
        foreach ($input['messages'] as $message) {
            $phone = $message['from'] ?? '';
            $text = strtolower(trim($message['body'] ?? ''));
            $response = '';
            
            // Command processing
            if (strpos($text, '!') === 0) {
                $command = substr($text, 1);
                
                switch ($command) {
                    case 'ayuda':
                    case 'help':
                        $response = getResponse('ayuda', "🌟 *CASA DE PAZ - HOTEL ESPIRITUAL* 🌟\n\n*Comandos disponibles:*\n\n🏨 *!habitaciones* - Ver habitaciones disponibles\n💰 *!precios* - Lista completa de precios\n🍄 *!ceremonias* - Información sobre ceremonias\n🏔️ *!tours* - Tours disponibles\n📞 *!contacto* - Información de contacto\n🪙 *!hotelcoins* - Sistema de recompensas\n📍 *!ubicacion* - Cómo llegar\n❓ *!info* - Información general\n\n¿En qué te puedo ayudar? 🙏");
                        break;
                        
                    case 'precios':
                        // Get dynamic prices from database
                        $roomIndividual = getPrice('rooms', 'individual', 45);
                        $roomDoble = getPrice('rooms', 'doble', 65);
                        $roomSuite = getPrice('rooms', 'suite', 90);
                        
                        $ayahuasca1day = getPrice('ceremonies', 'ayahuasca_1day', 180);
                        $sanpedro1day = getPrice('ceremonies', 'sanpedro_1day', 120);
                        $retiro7days = getPrice('ceremonies', 'retiro_7days', 980);
                        
                        $machupicchu1day = getPrice('tours', 'machupicchu_1day', 180);
                        $vallesagrado = getPrice('tours', 'valle_sagrado', 65);
                        $waqrapukara = getPrice('tours', 'waqrapukara', 85);
                        
                        $response = "💰 *PRECIOS CASA DE PAZ* 💰\n\n";
                        $response .= "🏨 *HABITACIONES (por noche):*\n";
                        $response .= "• Individual: $${roomIndividual} USD\n";
                        $response .= "• Doble: $${roomDoble} USD\n";
                        $response .= "• Suite Valle Sagrado: $${roomSuite} USD\n\n";
                        
                        $response .= "🍄 *CEREMONIAS:*\n";
                        $response .= "• Ayahuasca (1 día): $${ayahuasca1day} USD\n";
                        $response .= "• San Pedro (1 día): $${sanpedro1day} USD\n";
                        $response .= "• Retiro 7 días: $${retiro7days} USD\n\n";
                        
                        $response .= "🏔️ *TOURS:*\n";
                        $response .= "• Machu Picchu (1 día): $${machupicchu1day} USD\n";
                        $response .= "• Valle Sagrado: $${vallesagrado} USD\n";
                        $response .= "• Waqrapukara: $${waqrapukara} USD\n\n";
                        
                        $response .= "*Incluye:*\n✅ Alojamiento\n✅ Alimentación sagrada\n✅ Guías especializados\n✅ Transporte\n\n📞 Contacto: +51 984 123 456";
                        break;
                        
                    case 'habitaciones':
                    case 'disponibilidad':
                        $response = getRoomAvailability();
                        break;
                        
                    case 'ceremonias':
                        $response = getResponse('ceremonias', "🍄 *CEREMONIAS SAGRADAS* 🍄\n\n*Casa de Paz* es un espacio sagrado dedicado a la sanación y el crecimiento espiritual.\n\n🌱 *AYAHUASCA:*\n• Precio: $" . getPrice('ceremonies', 'ayahuasca_1day', 180) . " USD (1 día)\n• Incluye: Ceremonia nocturna, preparación, integración\n• Maestros shipibos experimentados\n• Dieta previa incluida\n\n🌵 *SAN PEDRO (HUACHUMA):*\n• Precio: $" . getPrice('ceremonies', 'sanpedro_1day', 120) . " USD (1 día)\n• Ceremonia diurna en la naturaleza\n• Conexión con los Apus (montañas sagradas)\n• Caminata meditativa\n\n🏔️ *RETIRO 7 DÍAS:*\n• Precio: $" . getPrice('ceremonies', 'retiro_7days', 980) . " USD\n• 3 ceremonias de Ayahuasca\n• 2 ceremonias de San Pedro\n• Talleres de integración\n• Alojamiento y alimentación completa\n\n⚠️ *Importante:* Requiere preparación previa y entrevista.");
                        break;
                        
                    case 'tours':
                        $response = getResponse('tours', "🏔️ *TOURS MÍSTICOS* 🏔️\n\n🏛️ *MACHU PICCHU SAGRADO:*\n• $" . getPrice('tours', 'machupicchu_1day', 180) . " USD - Tour de 1 día\n• Salida 4:00 AM desde Cusco\n• Tren + Bus panorámico\n• Guía especializado en historia Inca\n• Ceremonia de gratitud en la ciudadela\n\n🌄 *VALLE SAGRADO:*\n• $" . getPrice('tours', 'valle_sagrado', 65) . " USD - Tour día completo\n• Pisaq, Ollantaytambo, Chinchero\n• Mercado artesanal\n• Almuerzo típico incluido\n\n⛰️ *WAQRAPUKARA:*\n• $" . getPrice('tours', 'waqrapukara', 85) . " USD - Fortaleza en las nubes\n• Trekking moderado 2-3 horas\n• Vista panorámica increíble\n• Ceremonia de apreciación\n\n*Todos incluyen:*\n✅ Transporte\n✅ Guía profesional\n✅ Entradas\n✅ Refrigerios\n\n📞 Reservas: +51 984 123 456");
                        break;
                        
                    case 'contacto':
                        $response = "📞 *CONTACTO CASA DE PAZ* 📞\n\n";
                        $response .= "🏨 *Hotel Espiritual Casa de Paz*\n";
                        $response .= "📍 Valle Sagrado, Cusco - Perú\n\n";
                        $response .= "*Contactos:*\n";
                        $response .= "📱 WhatsApp: +51 984 123 456\n";
                        $response .= "📧 Email: info@casadepaz.com\n";
                        $response .= "🌐 Web: www.casadepaz.com\n\n";
                        $response .= "*Horarios de atención:*\n";
                        $response .= "🕒 24 horas via WhatsApp\n";
                        $response .= "☎️ Llamadas: 8:00 AM - 10:00 PM\n\n";
                        $response .= "¡Estamos aquí para acompañarte en tu viaje espiritual! 🙏✨";
                        break;
                        
                    case 'ubicacion':
                        $response = "📍 *UBICACIÓN CASA DE PAZ* 📍\n\n";
                        $response .= "🏔️ *Valle Sagrado de los Incas*\n";
                        $response .= "Cusco, Perú\n\n";
                        $response .= "*Cómo llegar:*\n";
                        $response .= "✈️ Vuelo a Cusco (Aeropuerto Alejandro Velasco Astete)\n";
                        $response .= "🚐 Transporte incluido desde/hacia aeropuerto\n";
                        $response .= "⏱️ 1 hora desde Cusco ciudad\n\n";
                        $response .= "*Coordenadas:*\n";
                        $response .= "-13.2543, -71.9718\n\n";
                        $response .= "🚐 *Transporte gratuito* para huéspedes\n";
                        $response .= "📞 Coordinar: +51 984 123 456";
                        break;
                        
                    case 'hotelcoins':
                        $response = "🪙 *SISTEMA HOTELCOINS* 🪙\n\n";
                        $response .= "💎 *Programa de Lealtad Casa de Paz*\n\n";
                        $response .= "*Gana HotelCoins:*\n";
                        $response .= "• 💰 $1 USD = 1 HotelCoin\n";
                        $response .= "• 🎁 Bonos por ceremonias\n";
                        $response .= "• 🌟 Referidos: 50 HotelCoins\n\n";
                        $response .= "*Beneficios:*\n";
                        $response .= "• 🏨 Descuentos en habitaciones\n";
                        $response .= "• 🍄 Ceremonias preferenciales\n";
                        $response .= "• 🎯 Tours con descuento\n";
                        $response .= "• 🎁 Productos de la tienda\n\n";
                        $response .= "*Canje:*\n";
                        $response .= "100 HotelCoins = $10 USD descuento\n\n";
                        $response .= "📱 Consulta tu saldo: !saldo";
                        break;
                        
                    case 'info':
                        $response = "🌟 *CASA DE PAZ* 🌟\n*Hotel Espiritual en el Valle Sagrado*\n\n";
                        $response .= "🏔️ Ubicado en el corazón del Valle Sagrado de los Incas, Casa de Paz es un santuario dedicado a la sanación, el crecimiento espiritual y la conexión con la sabiduría ancestral andina.\n\n";
                        $response .= "*Especialidades:*\n";
                        $response .= "🍄 Ceremonias de Ayahuasca y San Pedro\n";
                        $response .= "🏛️ Tours místicos a lugares sagrados\n";
                        $response .= "🧘 Talleres de meditación y yoga\n";
                        $response .= "🌿 Medicina tradicional andina\n";
                        $response .= "🎵 Ceremonias de sonido y música\n\n";
                        $response .= "*Filosofía:*\n";
                        $response .= "Conectamos a nuestros huéspedes con la sabiduría milenaria de los Andes, facilitando experiencias transformadoras en un ambiente seguro y sagrado.\n\n";
                        $response .= "🙏 *Pachamama* - Vivimos en armonía con la Madre Tierra";
                        break;
                        
                    case 'saldo':
                        $response = "🪙 *CONSULTA DE HOTELCOINS* 🪙\n\n";
                        $response .= "Para consultar tu saldo de HotelCoins, necesitamos verificar tu identidad.\n\n";
                        $response .= "📧 Envía tu email de registro a:\n";
                        $response .= "💬 WhatsApp: +51 984 123 456\n\n";
                        $response .= "Responderemos con:\n";
                        $response .= "• 💰 Saldo actual\n";
                        $response .= "• 📊 Historial de transacciones\n";
                        $response .= "• 🎁 Recompensas disponibles";
                        break;
                        
                    default:
                        $response = "❓ Comando no reconocido. Escribe *!ayuda* para ver todos los comandos disponibles.\n\n🌟 *Casa de Paz* - Hotel Espiritual 🌟";
                        break;
                }
            } else {
                // Natural language processing (basic)
                if (strpos($text, 'hola') !== false || strpos($text, 'hi') !== false) {
                    $response = "¡Hola! 🙏 Bienvenido a *Casa de Paz*, tu hotel espiritual en el Valle Sagrado.\n\n✨ Escribe *!ayuda* para ver todos nuestros servicios.\n\n¿En qué puedo ayudarte hoy?";
                } else if (strpos($text, 'precio') !== false) {
                    $response = "💰 Para ver todos nuestros precios, escribe *!precios*\n\nTambién puedes consultar:\n🏨 *!habitaciones*\n🍄 *!ceremonias*\n🏔️ *!tours*";
                } else if (strpos($text, 'reserva') !== false || strpos($text, 'booking') !== false) {
                    $response = "📅 *RESERVAS CASA DE PAZ* 📅\n\n*Para realizar tu reserva:*\n\n📱 WhatsApp: +51 984 123 456\n📧 Email: reservas@casadepaz.com\n🌐 Web: www.casadepaz.com\n\n*Información necesaria:*\n• 📅 Fechas de estadía\n• 👥 Número de personas\n• 🛏️ Tipo de habitación\n• 🍄 Ceremonias de interés\n\n¡Te ayudaremos a crear la experiencia perfecta! ✨";
                } else {
                    $response = "🤔 No estoy seguro de entender tu consulta.\n\n✨ Escribe *!ayuda* para ver todos los comandos disponibles.\n\n📞 O contacta directamente:\n+51 984 123 456";
                }
            }
            
            // Log conversation
            logConversation($phone, $text, $response);
            
            // Send response (this would be handled by your WhatsApp API)
            echo json_encode(['response' => $response, 'phone' => $phone]);
        }
    }
}

// Manual testing endpoint
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test'])) {
    $message = $_GET['message'] ?? '!ayuda';
    
    // Simulate message processing
    $testInput = [
        'messages' => [
            [
                'from' => '+51999999999',
                'body' => $message
            ]
        ]
    ];
    
    $_POST = ['test' => true];
    $GLOBALS['_POST'] = $_POST;
    
    // Process message
    ob_start();
    
    $phone = '+51999999999';
    $text = strtolower(trim($message));
    $response = '';
    
    // Same command processing logic as above
    if (strpos($text, '!') === 0) {
        $command = substr($text, 1);
        
        switch ($command) {
            case 'precios':
                // Get dynamic prices from database
                $roomIndividual = getPrice('rooms', 'individual', 45);
                $roomDoble = getPrice('rooms', 'doble', 65);
                $roomSuite = getPrice('rooms', 'suite', 90);
                
                $response = "💰 *PRECIOS CASA DE PAZ* 💰\n\n";
                $response .= "🏨 *HABITACIONES (por noche):*\n";
                $response .= "• Individual: $${roomIndividual} USD\n";
                $response .= "• Doble: $${roomDoble} USD\n";
                $response .= "• Suite Valle Sagrado: $${roomSuite} USD\n\n";
                $response .= "📞 Contacto: +51 984 123 456";
                break;
                
            case 'ayuda':
                $response = "🌟 *CASA DE PAZ - HOTEL ESPIRITUAL* 🌟\n\n*Comandos disponibles:*\n!precios, !habitaciones, !ceremonias, !tours, !contacto";
                break;
                
            default:
                $response = "✨ Bot funcionando correctamente\n🤖 Comando de prueba: $command";
        }
    } else {
        $response = "🤖 Bot activo - Escribe !ayuda para comenzar";
    }
    
    ob_end_clean();
    
    echo "<h2>🤖 Bot Test Response</h2>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($message) . "</p>";
    echo "<p><strong>Respuesta:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
?>