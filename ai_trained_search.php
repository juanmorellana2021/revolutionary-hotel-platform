<?php
/**
 * AI Training System for Vicky
 * Feeds Q&A data to improve AI responses for hotel/travel queries
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Load training data
$trainingDataFile = __DIR__ . '/ai_training_data.json';
$trainingData = json_decode(file_get_contents($trainingDataFile), true);

/**
 * Find best matching training answer
 */
function findTrainingMatch($userQuery, $trainingData) {
    $userQuery = strtolower($userQuery);
    $bestMatch = null;
    $highestScore = 0;
    
    foreach ($trainingData['training_dataset'] as $item) {
        $score = 0;
        
        // Check keywords
        foreach ($item['keywords'] as $keyword) {
            if (strpos($userQuery, strtolower($keyword)) !== false) {
                $score += 10;
            }
        }
        
        // Check question similarity
        similar_text(strtolower($item['question']), $userQuery, $similarity);
        $score += $similarity;
        
        if ($score > $highestScore) {
            $highestScore = $score;
            $bestMatch = $item;
        }
    }
    
    // Return match if score is above threshold (lowered from 15 to 10 for better coverage)
    if ($highestScore > 10) {
        return [
            'answer' => $bestMatch['answer'],
            'category' => $bestMatch['category'],
            'confidence' => min(100, $highestScore),
            'source' => 'training_data'
        ];
    }
    
    return null;
}

/**
 * Enhance AI prompt with training context
 */
function buildEnhancedPrompt($userQuery, $trainingData) {
    $systemPrompt = $trainingData['system_prompt'];
    
    // Add relevant examples
    $examples = [];
    foreach (array_slice($trainingData['training_dataset'], 0, 5) as $item) {
        $examples[] = "Q: {$item['question']}\nA: {$item['answer']}";
    }
    
    $examplesText = implode("\n\n", $examples);
    
    $prompt = <<<PROMPT
{$systemPrompt}

EJEMPLOS DE RESPUESTAS:
{$examplesText}

CONSULTA DEL USUARIO: {$userQuery}

RESPUESTA (máximo 15 palabras):
PROMPT;

    return $prompt;
}

/**
 * Call Ollama with enhanced training
 */
function callTrainedAI($userQuery, $trainingData) {
    // First try to find direct match in training data
    $trainingMatch = findTrainingMatch($userQuery, $trainingData);
    
    // Use training data for ANY match above 30% (much faster than AI)
    if ($trainingMatch && $trainingMatch['confidence'] > 30) {
        // Use training answer directly - FAST response
        return [
            'response' => $trainingMatch['answer'],
            'method' => 'training_direct',
            'confidence' => $trainingMatch['confidence'],
            'category' => $trainingMatch['category']
        ];
    }
    
    // Otherwise, enhance AI prompt with training context
    $enhancedPrompt = buildEnhancedPrompt($userQuery, $trainingData);
    
    // Call Ollama
    $ollamaUrl = 'http://72.60.1.16:11434/api/generate';
    
    $payload = [
        'model' => 'qwen2.5:1.5b',
        'prompt' => $enhancedPrompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.3,
            'num_predict' => 50
        ]
    ];
    
    $ch = curl_init($ollamaUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    
    if ($curlErrno) {
        error_log("AI Training - CURL Error #{$curlErrno}: {$curlError}");
        curl_close($ch);
        
        // If AI fails, ALWAYS use training match if available (even low confidence)
        if ($trainingMatch) {
            return [
                'response' => $trainingMatch['answer'],
                'method' => 'training_fallback',
                'confidence' => $trainingMatch['confidence'],
                'category' => $trainingMatch['category'],
                'note' => 'AI offline, usando datos de entrenamiento'
            ];
        }
        
        return [
            'error' => 'Lo siento, no pude procesar tu consulta. Intenta de nuevo.',
            'method' => 'ai_error',
            'errno' => $curlErrno
        ];
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("AI Training - HTTP Error: {$httpCode}");
        
        // Fallback to training match (even low confidence is better than nothing)
        if ($trainingMatch) {
            return [
                'response' => $trainingMatch['answer'],
                'method' => 'training_fallback',
                'confidence' => $trainingMatch['confidence'],
                'category' => $trainingMatch['category'],
                'note' => 'AI error, usando datos de entrenamiento'
            ];
        }
        
        return [
            'error' => 'Lo siento, no pude procesar tu consulta. Intenta de nuevo.',
            'method' => 'ai_error',
            'http_code' => $httpCode
        ];
    }
    
    $result = json_decode($response, true);
    
    return [
        'response' => $result['response'] ?? 'Lo siento, no pude procesar eso.',
        'method' => 'ai_enhanced',
        'confidence' => $trainingMatch ? $trainingMatch['confidence'] : 0,
        'category' => $trainingMatch['category'] ?? 'general'
    ];
}

// Main execution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    error_log("AI Training - Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    error_log("AI Training - Decoded input: " . print_r($input, true));
    
    $userQuery = $input['query'] ?? '';
    
    // Enhanced logging
    error_log("AI Training - POST Request - Query: " . $userQuery);
    
    if (empty($userQuery)) {
        error_log("AI Training - ERROR: Empty query - Raw: {$rawInput}");
        echo json_encode([
            'error' => 'No query provided',
            'raw_input' => $rawInput,
            'decoded' => $input
        ]);
        exit;
    }
    
    error_log("AI Training - Processing query: {$userQuery}");
    $result = callTrainedAI($userQuery, $trainingData);
    error_log("AI Training - Result method: " . ($result['method'] ?? 'unknown'));
    
    // Log for analytics
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'query' => $userQuery,
        'response' => $result['response'] ?? '',
        'method' => $result['method'],
        'confidence' => $result['confidence'] ?? 0,
        'category' => $result['category'] ?? 'unknown'
    ];
    
    file_put_contents(
        __DIR__ . '/ai_training_log.txt',
        json_encode($logEntry) . "\n",
        FILE_APPEND
    );
    
    echo json_encode($result);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return training stats
    $stats = [
        'total_training_examples' => count($trainingData['training_dataset']),
        'categories' => array_unique(array_column($trainingData['training_dataset'], 'category')),
        'system_prompt' => $trainingData['system_prompt']
    ];
    
    echo json_encode($stats);
}
?>
