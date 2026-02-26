<?php
// Minimal Sofia API - Just make it work!
session_start();
header('Content-Type: application/json');

// AI runs on flat-rate VPS (72.60.1.16) - no RunPod needed

// Database connection
function getHotels($searchTerm = '') {
    $host = 'localhost';
    $dbname = 'hotel_booking_system';
    $username = 'hoteluser';
    $password = 'hotelpass123';
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (empty($searchTerm)) {
            // Return top 5 hotels when no search term
            $stmt = $conn->prepare("SELECT id, name, location, price, rating FROM hotel_properties WHERE status = 'approved' AND is_active = 1 ORDER BY rating DESC LIMIT 5");
            $stmt->execute();
        } else {
            // Search hotels - show top 5 highest-rated matches
            $searchTerm = "%$searchTerm%";
            $stmt = $conn->prepare("SELECT id, name, location, price, rating FROM hotel_properties WHERE status = 'approved' AND is_active = 1 AND (name LIKE ? OR location LIKE ?) ORDER BY rating DESC LIMIT 5");
            $stmt->execute([$searchTerm, $searchTerm]);
        }
        
        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $hotels;
    } catch(PDOException $e) {
        return [];
    }
}

$action = $_GET['action'] ?? '';

if ($action === 'chat') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'] ?? '';
    $conversationHistory = $input['history'] ?? []; // Get recent messages
    $userLanguage = $input['language'] ?? 'auto'; // Get language preference (auto, en, es, pt, fr, de, zh, ja)
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'No message']);
        exit;
    }
    
    // Check if user is logged in
    $isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['id']) || !empty($_SESSION['ainitravel_user_id']);
    $userContext = $isLoggedIn ? "User IS logged in" : "User is NOT logged in (guest)";
    
    // Search for hotels if user is asking about destinations
    $hotelResults = "";
    $databaseChecked = "";
    $messageLower = strtolower($message);
    if (preg_match('/\b(hotel|hoteles|hotels|busco|buscar|search|looking|recomienda|recommend|recomendación|alojamiento|accommodation|donde|where|quedarse|hospedar|stay|cusco|lima|miami|cancun|arequipa|playa|beach|pisac|ollantaytambo|aguas|calientes|machu|picchu)\b/i', $messageLower)) {
        // Extract location if mentioned
        $hotels = [];
        if (preg_match('/(cusco|lima|miami|cancun|arequipa|peru|usa|mexico|pisac|ollantaytambo|aguas calientes|machu picchu)/i', $messageLower, $matches)) {
            $location = $matches[1];
            $hotels = getHotels($location);
        } else {
            $hotels = getHotels(); // Get top hotels
        }
        
        $databaseChecked = "\n\n🔍 DATABASE QUERY EXECUTED: YES";
        
        if (!empty($hotels)) {
            $hotelResults = "\n✅ AVAILABLE HOTELS (sorted by rating):\n\n";
            foreach ($hotels as $h) {
                $hotelUrl = "https://ainitravel.com/hotel_details.php?id=" . $h['id'];
                $hotelResults .= "- {$h['name']} in {$h['location']} - \${$h['price']}/night ⭐{$h['rating']} [View hotel]($hotelUrl)\n";
            }
            $hotelResults .= "\n💡 Be intelligent:\n";
            $hotelResults .= "- Recommend hotels that best match the user's request (budget, location, preferences)\n";
            $hotelResults .= "- If user asks for cheap/budget: recommend lower-priced options\n";
            $hotelResults .= "- If user asks for luxury/best: recommend highest-rated options\n";
            $hotelResults .= "- If user just asks 'show hotels': show the top 2-3 options\n";
            $hotelResults .= "- Always include the EXACT [View hotel](https://ainitravel.com/hotel_details.php?id=X) link for each recommendation\n";
        } else {
            $hotelResults = "\n❌ DATABASE RETURNED: 0 HOTELS for this location\n";
            $hotelResults .= "\n🚨 MANDATORY response format:\n";
            $hotelResults .= "If asked in SPANISH: 'No tenemos hoteles en [location] aún, pero puedo mostrarte opciones en Cusco, Lima o Arequipa.'\n";
            $hotelResults .= "If asked in ENGLISH: 'We don\'t have hotels in [location] yet, but I can show you options in Cusco, Lima, or Arequipa.'\n";
        }
    }
    
    // Build conversation context from history
    $contextText = "";
    if (!empty($conversationHistory)) {
        $contextText = "\n\nPREVIOUS CONVERSATION:\n";
        foreach (array_slice($conversationHistory, -4) as $msg) { // Last 4 messages
            $role = $msg['role'] ?? 'user';
            $content = $msg['content'] ?? '';
            $contextText .= ($role === 'user' ? "User: " : "Sofia: ") . $content . "\n";
        }
    }
    
    // Determine response language
    $languageMap = [
        'en' => 'ENGLISH',
        'es' => 'SPANISH',
        'pt' => 'PORTUGUESE',
        'fr' => 'FRENCH',
        'de' => 'GERMAN',
        'zh' => 'CHINESE',
        'ja' => 'JAPANESE',
        'it' => 'ITALIAN',
        'ko' => 'KOREAN',
        'ru' => 'RUSSIAN'
    ];
    
    $responseLanguage = '';
    if ($userLanguage !== 'auto' && isset($languageMap[$userLanguage])) {
        // User explicitly selected a language
        $responseLanguage = $languageMap[$userLanguage];
        $languageInstruction = "� MANDATORY: Respond ONLY in {$responseLanguage}. Every single word must be in {$responseLanguage}.";
    } else {
        // Auto-detect - CHECK FIRST WORDS for immediate language detection
        $messageLower = strtolower(trim($message));
        $firstWords = substr($messageLower, 0, 50); // Check first 50 chars
        
        // SPANISH DETECTION FIRST (priority for Peru market)
        if (preg_match('/^(hola|buenos|buenas|muestra|muéstrame|muestrame|busco|quiero|necesito|encuentra|dónde|donde|qué|que|cuál|cual|hay|tienes|puedes|recomienda|reserva|dame|ayuda)\b/i', $firstWords) ||
            preg_match('/\b(hoteles|habitación|habitacion|desayuno|incluye|precio|disponible|reservar|ubicado|centro|cerca)\b/i', $messageLower)) {
            $responseLanguage = 'SPANISH';
        }
        // ENGLISH DETECTION
        else if (preg_match('/^(hello|hi|hey|show|find|search|looking|i need|i want|where|what|can you|do you|get me|help)\b/i', $firstWords) ||
                 preg_match('/\b(hotels|rooms|breakfast|price|available|booking|located|downtown|near)\b/i', $messageLower)) {
            $responseLanguage = 'ENGLISH';
        }
        // Portuguese
        else if (preg_match('/^(olá|oi|mostre|procuro|quero|preciso|onde|bom dia|boa tarde)\b/i', $firstWords)) {
            $responseLanguage = 'PORTUGUESE';
        }
        // French
        else if (preg_match('/^(bonjour|salut|montrez|cherche|où|je veux)\b/i', $firstWords)) {
            $responseLanguage = 'FRENCH';
        }
        // German  
        else if (preg_match('/^(hallo|guten|zeigen|suche|ich möchte|wo)\b/i', $firstWords)) {
            $responseLanguage = 'GERMAN';
        }
        // Default to SPANISH for Peru
        else {
            $responseLanguage = 'SPANISH';
        }
        
        // STRONG language enforcement instruction
        $languageInstruction = "RESPOND IN: {$responseLanguage}\n\n⚠️ STRICT RULE: Every single word MUST be in {$responseLanguage}. No mixing languages. No English if SPANISH detected.";
    }
    
    // Build Sofia's character prompt - LANGUAGE FIRST for enforcement
    $systemPrompt = "⚠️ {$languageInstruction}\n\nYou are Sofia, AiNi Travel's friendly AI assistant. Be helpful, concise, and warm.\n\nUSER STATUS: {$userContext}\n\nRULES:\n1. When showing hotels from CONTEXT: List ALL of them with exact [View hotel](url) links from the context.\n2. When user wants to BOOK and is NOT logged in: tell them to login first and provide this link: [Login here](https://ainitravel.com/login.php)\n3. When user says they want to LOGIN (e.g. 'let me login', 'i need to login', 'quiero iniciar sesión', 'login', 'iniciar sesión'): respond with 'Great! Click here to login: [Login here](https://ainitravel.com/login.php) — once logged in I can help you book your stay!'\n4. Stay focused on travel, hotels, and bookings.\n5. Keep responses short and friendly.{$contextText}\n\nCONTEXT:{$hotelResults}\n\nUser: {$message}\nSofia:";

    // Call Ollama with optimal balance of speed and quality
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://72.60.1.16:11434/api/generate");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'qwen2.5:7b',
        'prompt' => $systemPrompt,
        'stream' => false,
        'keep_alive' => '5m',       // Free GPU after 5 min idle
        'options' => [
            'temperature' => 0.7,
            'top_p' => 0.9,
            'num_predict' => 350,    // Room for full hotel listings with URLs
            'num_ctx' => 2048        // Larger context for better comprehension
        ]
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);  // 7B still fast on GPU
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error || $httpCode !== 200) {
        // Log the error for debugging
        error_log("Sofia AI Error: " . ($error ?: "HTTP $httpCode"));
        
        // Friendly response instead of technical error
        $friendlyResponse = $responseLanguage === 'SPANISH' || $responseLanguage === '' 
            ? "¡Hola! ✨ Estoy aquí para ayudarte. En este momento estoy procesando tu consulta. Si necesitas ayuda inmediata, contáctanos:\n\n📱 WhatsApp: +51 987 654 321\n📧 Email: hola@ainitravel.com\n\n¿En qué más puedo ayudarte mientras tanto?"
            : "Hi there! ✨ I'm here to help you. I'm processing your request right now. If you need immediate assistance, contact us:\n\n📱 WhatsApp: +51 987 654 321\n📧 Email: hola@ainitravel.com\n\nHow else can I help you in the meantime?";
        
        echo json_encode([
            'success' => true,  // Changed to true so it displays nicely
            'response' => $friendlyResponse,
            'session_id' => session_id()
        ]);
        exit;
    }
    
    $result = json_decode($response, true);
    $aiText = $result['response'] ?? '';
    
    // Convert markdown links [text](url) to HTML <a> tags
    $aiText = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" style="color: #667eea; text-decoration: underline; font-weight: 600;">$1</a>', $aiText);
    
    echo json_encode([
        'success' => true,
        'response' => $aiText,
        'session_id' => session_id()
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
