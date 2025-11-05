<?php
/**
 * AI Room Query Endpoint - OPTIMIZED FOR MULTI-HOTEL PLATFORM
 * Efficient natural language querying with caching and minimal DB hits
 */
session_start();
header('Content-Type: application/json');

require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/ollama_ai.php';
require_once 'includes/cache_manager.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get query from request
$query = $_POST['query'] ?? '';
if (empty($query)) {
    echo json_encode(['success' => false, 'error' => 'No query provided']);
    exit;
}

// Get current hotel ID
$currentHotelId = $_SESSION['current_hotel_id'] ?? 1;

// OPTIMIZATION 1: Use intelligent cache manager
$cache = CacheManager::getInstance();
$rooms = $cache->get('room_inventory', $currentHotelId);

if ($rooms === null) {
    // Cache miss - query database
    $database = new Database();
    $conn = $database->getConnection();
    
    // OPTIMIZATION 2: Single efficient query with subquery for occupancy
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT 
            r.id,
            r.room_number,
            r.room_type,
            r.price,
            r.max_occupancy,
            r.description,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM bookings b 
                    WHERE b.room_id = r.id 
                    AND b.check_in_date <= ? 
                    AND b.check_out_date > ? 
                    AND b.status IN ('confirmed', 'checked_in')
                )
                THEN 1 
                ELSE 0 
            END as is_occupied
        FROM rooms r
        WHERE r.hotel_id = ?
        ORDER BY r.room_number
    ");
    $stmt->execute([$today, $today, $currentHotelId]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cache for 5 minutes (300 seconds)
    $cache->set('room_inventory', $rooms, $currentHotelId, 300);
    $fromCache = false;
} else {
    $fromCache = true;
}

// OPTIMIZATION 3: Build compact context (solo lo necesario)
$roomContext = buildCompactContext($rooms);

// OPTIMIZATION 4: Smart AI routing - use lighter model for simple queries
$isSimpleQuery = isSimpleQuery($query);
$model = $isSimpleQuery ? 'tinyllama' : 'qwen2.5:1.5b';

// Create specialized prompt
$prompt = buildRoomQueryPrompt($query, $roomContext, $isSimpleQuery);

// OPTIMIZATION 5: Short timeout for fast responses
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://72.60.1.16:11434/api/generate");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'keep_alive' => '10m',  // Keep warm longer for multiple hotels
        'options' => [
            'temperature' => 0.1,      // Very focused
            'top_p' => 0.9,
            'num_predict' => $isSimpleQuery ? 50 : 150,  // Shorter for simple queries
        ]
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, $isSimpleQuery ? 3 : 8);  // Faster timeout for simple
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("AI service error");
    }
    
    $decoded = json_decode($response, true);
    $aiResponse = trim($decoded['response'] ?? '');
    
    // Parse AI response to extract filter criteria
    $filters = parseAIResponse($aiResponse, $query);
    
    // Apply filters to room list
    $filteredRooms = filterRooms($rooms, $filters);
    
    echo json_encode([
        'success' => true,
        'response' => $aiResponse,
        'filters' => $filters,
        'rooms' => $filteredRooms,
        'total_rooms' => count($rooms),
        'filtered_count' => count($filteredRooms),
        'from_cache' => $fromCache ?? false,
        'model_used' => $model,
        'hotel_id' => $currentHotelId
    ]);
    
} catch (Exception $e) {
    // Fallback without AI - still works!
    $fallbackRooms = basicFilter($rooms, $query);
    echo json_encode([
        'success' => true,
        'response' => 'Resultados filtrados (modo básico)',
        'rooms' => $fallbackRooms,
        'total_rooms' => count($rooms),
        'filtered_count' => count($fallbackRooms),
        'fallback' => true
    ]);
}

/**
 * Detect if query is simple (occupied/available only)
 */
function isSimpleQuery($query) {
    $simplePatterns = [
        '/^(ocupad|available|disponib|libre|booked)/i',
        '/^(cuant|how many|count)/i',
        '/^(list|dame|show|muestra)/i'
    ];
    
    foreach ($simplePatterns as $pattern) {
        if (preg_match($pattern, trim($query))) {
            return true;
        }
    }
    
    return false;
}

/**
 * Build COMPACT context - solo estadísticas clave
 */
function buildCompactContext($rooms) {
    $available = array_filter($rooms, fn($r) => $r['is_occupied'] == 0);
    $occupied = array_filter($rooms, fn($r) => $r['is_occupied'] > 0);
    
    $prices = array_column($rooms, 'price');
    $avgPrice = count($prices) > 0 ? array_sum($prices) / count($prices) : 0;
    
    // Group by type
    $types = [];
    foreach ($rooms as $room) {
        $type = $room['room_type'];
        if (!isset($types[$type])) {
            $types[$type] = ['total' => 0, 'available' => 0, 'occupied' => 0];
        }
        $types[$type]['total']++;
        if ($room['is_occupied'] == 0) {
            $types[$type]['available']++;
        } else {
            $types[$type]['occupied']++;
        }
    }
    
    // Build compact context
    $context = "HOTEL INVENTORY:\n";
    $context .= "Total: " . count($rooms) . " | Available: " . count($available) . " | Occupied: " . count($occupied) . "\n";
    $context .= "Avg Price: $" . number_format($avgPrice, 0) . "\n\n";
    
    $context .= "BY TYPE:\n";
    foreach ($types as $type => $stats) {
        $context .= "$type: {$stats['total']} total ({$stats['available']} free, {$stats['occupied']} busy)\n";
    }
    
    return $context;
}

/**
 * Build optimized prompt based on query complexity
 */
function buildRoomQueryPrompt($query, $roomContext, $isSimple) {
    if ($isSimple) {
        // Ultra-short prompt for simple queries
        $prompt = "Hotel data:\n$roomContext\n\nQuestion: $query\n\nAnswer (1 sentence):";
    } else {
        // Full prompt for complex queries
        $prompt = "You are a hotel admin assistant. Answer questions about room inventory.\n\n";
        $prompt .= $roomContext . "\n\n";
        $prompt .= "RULES:\n";
        $prompt .= "1. Answer in Spanish or English based on question\n";
        $prompt .= "2. Be brief and specific\n";
        $prompt .= "3. Give exact numbers when asked\n\n";
        $prompt .= "Question: $query\n\nAnswer:";
    }
    
    return $prompt;
}

/**
 * REMOVED: buildRoomContext() - replaced with buildCompactContext()
 */

/**
 * Parse AI response to extract filter criteria
 */
function parseAIResponse($aiResponse, $originalQuery) {
    $filters = [
        'availability' => null,  // 'available', 'occupied', or null
        'type' => null,
        'min_price' => null,
        'max_price' => null,
        'min_occupancy' => null
    ];
    
    $queryLower = strtolower($originalQuery . ' ' . $aiResponse);
    
    // Detect availability queries
    if (preg_match('/\b(ocupad|booked|reservad)\w*/i', $queryLower)) {
        $filters['availability'] = 'occupied';
    } elseif (preg_match('/\b(disponib|available|libre|vacant)\w*/i', $queryLower)) {
        $filters['availability'] = 'available';
    }
    
    // Detect room type
    $types = ['single', 'double', 'suite', 'deluxe', 'twin', 'king', 'queen'];
    foreach ($types as $type) {
        if (stripos($queryLower, $type) !== false) {
            $filters['type'] = $type;
            break;
        }
    }
    
    // Detect price queries
    if (preg_match('/\b(barato|cheap|económico)\w*/i', $queryLower)) {
        $filters['max_price'] = 100;
    } elseif (preg_match('/\b(caro|expensive|premium)\w*/i', $queryLower)) {
        $filters['min_price'] = 150;
    } elseif (preg_match('/\$(\d+)/i', $queryLower, $matches)) {
        $price = intval($matches[1]);
        if (stripos($queryLower, 'menos') !== false || stripos($queryLower, 'under') !== false) {
            $filters['max_price'] = $price;
        } else {
            $filters['min_price'] = $price;
        }
    }
    
    return $filters;
}

/**
 * Filter rooms based on criteria
 */
function filterRooms($rooms, $filters) {
    return array_values(array_filter($rooms, function($room) use ($filters) {
        // Check availability
        if ($filters['availability'] === 'available' && $room['is_occupied'] > 0) {
            return false;
        }
        if ($filters['availability'] === 'occupied' && $room['is_occupied'] == 0) {
            return false;
        }
        
        // Check room type
        if ($filters['type'] && stripos($room['room_type'], $filters['type']) === false) {
            return false;
        }
        
        // Check price range
        if ($filters['min_price'] && $room['price'] < $filters['min_price']) {
            return false;
        }
        if ($filters['max_price'] && $room['price'] > $filters['max_price']) {
            return false;
        }
        
        // Check occupancy
        if ($filters['min_occupancy'] && $room['max_occupancy'] < $filters['min_occupancy']) {
            return false;
        }
        
        return true;
    }));
}

/**
 * Fallback basic filter if AI fails
 */
function basicFilter($rooms, $query) {
    $queryLower = strtolower($query);
    
    if (stripos($queryLower, 'ocupad') !== false || stripos($queryLower, 'booked') !== false) {
        return array_values(array_filter($rooms, fn($r) => $r['is_occupied'] > 0));
    }
    
    if (stripos($queryLower, 'disponib') !== false || stripos($queryLower, 'available') !== false) {
        return array_values(array_filter($rooms, fn($r) => $r['is_occupied'] == 0));
    }
    
    return $rooms;
}
?>
