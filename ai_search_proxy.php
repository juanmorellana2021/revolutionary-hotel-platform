<?php
/**
 * AI Search Proxy - Connects to Ollama AI Server
 * Avoids CORS issues by proxying requests from frontend
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request body
$input = file_get_contents('php://input');
error_log("AI Proxy - Raw input: " . $input);

$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    error_log("AI Proxy - JSON decode error: " . json_last_error_msg());
    echo json_encode([
        'error' => 'Invalid JSON',
        'details' => json_last_error_msg(),
        'raw_input' => $input
    ]);
    exit;
}

// AI Server configuration
$AI_SERVER = '72.60.1.16';
$AI_PORT = 11434;
$AI_MODEL = $data['model'] ?? 'qwen2.5:1.5b';
$AI_PROMPT = $data['prompt'] ?? '';

if (empty($AI_PROMPT)) {
    http_response_code(400);
    echo json_encode(['error' => 'Prompt is required']);
    exit;
}

// Prepare Ollama API request
$ollamaData = [
    'model' => $AI_MODEL,
    'prompt' => $AI_PROMPT,
    'stream' => false,
    'options' => [
        'temperature' => 0.3, // Lower = faster, more focused
        'top_p' => 0.8,
        'num_predict' => 100 // Limit response length
    ]
];

// Make request to Ollama
$ch = curl_init("http://{$AI_SERVER}:{$AI_PORT}/api/generate");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ollamaData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Reduced from 30 to 15 seconds
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Reduced from 10 to 5 seconds

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Log for debugging
error_log("AI Search - Model: {$AI_MODEL}, Prompt length: " . strlen($AI_PROMPT));
error_log("AI Server Response Code: {$httpCode}");

if ($error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to connect to AI server',
        'details' => $error,
        'server' => $AI_SERVER
    ]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode([
        'error' => 'AI server returned error',
        'code' => $httpCode,
        'response' => $response
    ]);
    exit;
}

// Return AI response
echo $response;
