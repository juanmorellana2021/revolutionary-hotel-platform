<?php
/**
 * AI Search Handler - Extracts search params from conversation and queries hotels
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$userMessage = $data['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// Call Ollama to extract search parameters
$aiPrompt = "Eres un extractor de parámetros de búsqueda de hoteles. Analiza el mensaje del usuario y extrae SOLO los parámetros en formato JSON.

Usuario: \"{$userMessage}\"

Extrae:
- destination: ciudad o país mencionado
- minPrice: precio mínimo (número)
- maxPrice: precio máximo (número)  
- features: array de características (playa, wifi, piscina, etc)
- category: tipo de hotel (luxury, budget, business, boutique)

Responde SOLO con JSON válido, ejemplo:
{\"destination\":\"Cusco\",\"minPrice\":50,\"maxPrice\":150,\"features\":[\"wifi\",\"desayuno\"],\"category\":\"boutique\"}

Si no mencionan precio, usa minPrice:0, maxPrice:99999.
Si no hay features, usa array vacío.
JSON:";

// Call AI proxy
$ch = curl_init("http://localhost/manage/ai_search_proxy.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'qwen2.5:1.5b',
    'prompt' => $aiPrompt
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$aiResponse = curl_exec($ch);
curl_close($ch);

$aiData = json_decode($aiResponse, true);
$extractedText = $aiData['response'] ?? '';

// Try to extract JSON from response
preg_match('/\{[^}]+\}/', $extractedText, $matches);
$searchParams = [];

if (!empty($matches[0])) {
    $searchParams = json_decode($matches[0], true);
}

// Fallback: basic keyword extraction
if (empty($searchParams)) {
    $searchParams = [
        'destination' => '',
        'minPrice' => 0,
        'maxPrice' => 99999,
        'features' => [],
        'category' => ''
    ];
    
    // Extract destination
    if (preg_match('/(cusco|lima|miami|new york|cancun|tokyo|paris)/i', $userMessage, $m)) {
        $searchParams['destination'] = $m[1];
    }
    
    // Extract price keywords
    if (stripos($userMessage, 'barato') !== false || stripos($userMessage, 'económico') !== false) {
        $searchParams['maxPrice'] = 100;
    }
    if (stripos($userMessage, 'lujo') !== false || stripos($userMessage, 'lujoso') !== false) {
        $searchParams['minPrice'] = 150;
        $searchParams['category'] = 'luxury';
    }
    
    // Extract features
    $featureKeywords = ['wifi', 'piscina', 'playa', 'desayuno', 'spa', 'gimnasio'];
    foreach ($featureKeywords as $keyword) {
        if (stripos($userMessage, $keyword) !== false) {
            $searchParams['features'][] = $keyword;
        }
    }
}

// Now call the hotel search API
$ch = curl_init("http://localhost/manage/api_search_hotels.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($searchParams));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$hotelResults = curl_exec($ch);
curl_close($ch);

echo $hotelResults;
?>
