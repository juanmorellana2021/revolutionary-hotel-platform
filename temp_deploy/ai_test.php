<?php
/**
 * AI Test Endpoint - Test Ollama Connection
 */

require_once 'db_connection.php';
require_once 'includes/ollama_ai.php';

header('Content-Type: application/json');

try {
    // Use your Gemma3:1b model (faster and less RAM)
    $ai = new OllamaAI('localhost', 11434, 'gemma3:1b');
    
    $testMessage = "Hello! I'm testing the AI system for a revolutionary hotel booking platform. Please respond briefly.";
    
    $response = $ai->chat($testMessage, null, ['context' => 'test']);
    
    echo json_encode([
        'success' => true,
        'message' => 'AI system is working with Gemma3:4b!',
        'ai_response' => $response,
        'model_info' => 'Connected to Ollama with Gemma3:4b model'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => 'Make sure Ollama is running and accessible'
    ]);
}
?>