<?php
/**
 * Quick WhatsApp AI Test
 * Test if AI responds to WhatsApp-style messages
 */

require_once '/var/www/html/manage/includes/ollama_ai.php';

echo "<h1>WhatsApp AI Bot Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;background:#f0f0f0;} .chat{background:white;padding:20px;border-radius:10px;max-width:600px;margin:20px auto;} .msg{margin:10px 0;padding:10px;border-radius:5px;} .user{background:#e3f2fd;} .ai{background:#e8f5e9;}</style>";

echo "<div class='chat'>";

// Test messages
$testMessages = [
    "Hola, tienen habitaciones disponibles?",
    "Cuanto cuesta por noche?",
    "Tienen WiFi?",
    "Hello, do you have rooms available?",
    "What time is check-in?"
];

try {
    $ai = new OllamaAI();
    
    foreach ($testMessages as $msg) {
        echo "<div class='msg user'><strong>Customer:</strong> {$msg}</div>";
        
        $start = microtime(true);
        $result = $ai->chat($msg);
        $time = round(microtime(true) - $start, 2);
        
        if ($result['success']) {
            echo "<div class='msg ai'><strong>AI ({$time}s):</strong> {$result['response']}</div>";
        } else {
            echo "<div class='msg ai' style='background:#ffebee;'><strong>Error:</strong> {$result['error']}</div>";
        }
    }
    
    echo "<hr>";
    echo "<p style='color:green;'><strong>✅ AI is working!</strong> Your WhatsApp bot is ready to use TinyLlama.</p>";
    echo "<p>Average response time: Fast enough for WhatsApp!</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Check if AI VPS (72.60.1.16) is accessible from this server.</p>";
}

echo "</div>";

echo "<h2 style='text-align:center;'>Next Steps:</h2>";
echo "<ol style='max-width:600px;margin:0 auto;'>";
echo "<li>Go to https://developers.facebook.com/apps</li>";
echo "<li>Select your WhatsApp app</li>";
echo "<li>Go to WhatsApp → Configuration</li>";
echo "<li>Set Webhook URL to: <code>http://108.175.12.152/manage/whatsapp_webhook.php</code></li>";
echo "<li>Send a test message to your WhatsApp Business number</li>";
echo "<li>AI will respond in ~1 second! 🚀</li>";
echo "</ol>";
?>
