<?php
// Simple AI admin test
require_once 'db_connection.php';

// Simple authentication - temporarily disabled for testing
session_start();
$_SESSION['user_id'] = 1;

echo "<h1>AI Admin Test</h1>";
echo "<p>Database connection: " . ($conn ? "Connected" : "Failed") . "</p>";

try {
    require_once 'includes/ollama_ai.php';
    echo "<p>OllamaAI class loaded successfully</p>";
    
    $ai = new OllamaAI('localhost', 11434, 'gemma3:1b');
    echo "<p>OllamaAI instance created</p>";
    
    $testResponse = $ai->chat("Hello", null, ['context' => 'test']);
    echo "<p>AI Test Response: Success</p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='ai_admin.php'>Try Full AI Admin</a></p>";
?>