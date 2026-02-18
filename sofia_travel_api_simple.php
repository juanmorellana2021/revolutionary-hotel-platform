<?php
// Minimal Sofia API - Just make it work!
session_start();
header('Content-Type: application/json');

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
            // Return top 5 hotels
            $stmt = $conn->prepare("SELECT id, name, location, price, rating FROM hotel_properties WHERE status = 'approved' AND is_active = 1 ORDER BY rating DESC LIMIT 5");
            $stmt->execute();
        } else {
            // Search hotels
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
            $hotelResults = "\n✅ FOUND " . count($hotels) . " HOTELS IN DATABASE:\n";
            foreach ($hotels as $h) {
                $hotelUrl = "https://ainitravel.com/hotel_details.php?id=" . $h['id'];
                $hotelResults .= "- {$h['name']} in {$h['location']} - \${$h['price']}/night ⭐{$h['rating']} [View hotel]($hotelUrl)\n";
            }
            $hotelResults .= "\n🚨 MANDATORY: Include ALL hotels above in your response.\n";
            $hotelResults .= "🚨 KEEP THE MARKDOWN LINKS EXACT - do NOT change the format.\n";
            $hotelResults .= "\nFormat based on language:\n";
            $hotelResults .= "- ENGLISH: \"I found " . count($hotels) . " hotel(s): [list each with name, location, price, rating, and EXACT [View hotel](url) link]\"\n";
            $hotelResults .= "- SPANISH: \"Encontré " . count($hotels) . " hotel(es): [list each with name, location, precio/noche, rating, and EXACT [Ver hotel](url) link]\"\n";
            $hotelResults .= "- OTHER LANGUAGES: Translate naturally but KEEP [View hotel](url) or use translated text with SAME link format\n";
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
        // Auto-detect using PHP keyword matching
        $messageLower = strtolower($message);
        
        // English detection
        if (preg_match('/\b(hello|hi|hey|how are you|good morning|good afternoon|hotel|hotels|where|what|can you|i am|im|i\'m|do you have|looking for|need|want|recommend|show me|find|search|book|help|yes|no|please|thanks|thank you|any)\b/i', $messageLower)) {
            $responseLanguage = 'ENGLISH';
        }
        // Spanish detection
        else if (preg_match('/\b(hola|cómo estás|buenos días|buenas tardes|hotel|hoteles|dónde|donde|qué|que|puedes|quiero|necesito|busco|tienes|recomienda|muestra|encuentra|reserva|reservar|ayuda|sí|si|no|por favor|gracias)\b/i', $messageLower)) {
            $responseLanguage = 'SPANISH';
        }
        // Portuguese
        else if (preg_match('/\b(olá|oi|como está|bom dia|boa tarde|hotel|hotéis|onde|você pode|eu quero|preciso|procuro|tem|recomenda|mostre|encontre|ajuda|sim|não|nao|por favor)\b/i', $messageLower)) {
            $responseLanguage = 'PORTUGUESE';
        }
        // French
        else if (preg_match('/\b(bonjour|salut|hôtel|hotel|où|ou|quoi|pouvez-vous|je veux|je cherche|avez-vous|recommandez|montrez|trouvez|réserver|reserver|oui|non|merci)\b/i', $messageLower)) {
            $responseLanguage = 'FRENCH';
        }
        // German  
        else if (preg_match('/\b(hallo|guten tag|hotel|wo|was|können sie|ich möchte|ich brauche|suche|haben sie|empfehlen|zeigen|finden|buchen|ja|nein|bitte|danke)\b/i', $messageLower)) {
            $responseLanguage = 'GERMAN';
        }
        // Default to English
        else {
            $responseLanguage = 'ENGLISH';
        }
        
        $languageInstruction = "LANGUAGE DETECTED: {$responseLanguage}\n\nYou MUST respond ONLY in {$responseLanguage}. Every word must be in {$responseLanguage}.\nDo NOT mix languages. Do NOT use Spanish unless detected language is SPANISH.";
    }
    
    // Build Sofia's character prompt (CONDENSED for speed)
    $systemPrompt = "{$languageInstruction}

You are Sofia, a warm travel agent for AiNi Travel.

HOTELS FROM DATABASE:
When the CONTEXT below shows hotel listings, include ALL of them in your response.
Keep the markdown links EXACTLY as shown: [View hotel](url) or [Ver hotel](url)

BOOKINGS:
- If user NOT logged in and wants to book: Ask them to create account or login
- If logged in: Help them complete their booking

CONTEXT: $userContext$contextText$databaseChecked$hotelResults

Current user message: $message

Sofia:";

    // Call Ollama with optimal balance of speed and quality
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://72.60.1.16:11434/api/generate");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'qwen2.5:7b',  // 7B model (4.7GB) - reliable multilingual language detection
        'prompt' => $systemPrompt,
        'stream' => false,
        'keep_alive' => '1h',        // CRITICAL: Keep model in memory for 1 hour
        'options' => [
            'temperature' => 0.9,
            'top_p' => 0.95,
            'num_predict' => 120,
            'num_ctx' => 1024
        ]
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);  // AI responses can take 20-30 seconds
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error || $httpCode !== 200) {
        // Check if it's a timeout (likely busy)
        if (strpos($error, 'timeout') !== false || strpos($error, 'timed out') !== false) {
            echo json_encode([
                'success' => true,
                'response' => "One moment! 🤖 I'm processing many queries right now. Give me a few seconds and try again, please. Or message me on WhatsApp: +51 987 654 321 for immediate help. 😊",
                'session_id' => session_id()
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => false,
            'error' => $error ?: "HTTP $httpCode",
            'response' => "Sorry, I'm having technical issues. 😔 Please contact our team:\n\n📱 WhatsApp: +51 987 654 321\n📧 hola@ainitravel.com"
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
