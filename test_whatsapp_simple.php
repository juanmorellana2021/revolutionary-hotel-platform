<?php
/**
 * Simple WhatsApp AI Test - No Database Required
 */

echo "<h1 style='text-align:center;'>🤖 WhatsApp AI Speed Test</h1>";
echo "<style>
body{font-family:Arial;padding:20px;background:#f0f0f0;max-width:800px;margin:0 auto;}
.chat{background:white;padding:20px;border-radius:10px;margin:20px 0;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
.msg{margin:15px 0;padding:15px;border-radius:8px;}
.user{background:#e3f2fd;border-left:4px solid #2196f3;}
.ai{background:#e8f5e9;border-left:4px solid #4caf50;}
.error{background:#ffebee;border-left:4px solid #f44336;}
.time{font-size:0.85em;color:#666;font-weight:bold;}
.response{margin-top:5px;line-height:1.5;}
</style>";

// Test messages - short and simple
$testMessages = [
    "Hola, tienen habitaciones?",
    "Cuanto cuesta?",
    "Tienen WiFi?",
    "What time is check-in?",
    "Do you have parking?"
];

$totalTime = 0;
$successCount = 0;

echo "<div class='chat'>";

foreach ($testMessages as $msg) {
    echo "<div class='msg user'><strong>Customer:</strong> {$msg}</div>";
    
    $start = microtime(true);
    
    // Build VERY short prompt - detect language
    $isSpanish = (stripos($msg, 'hola') !== false || stripos($msg, 'tiene') !== false || stripos($msg, 'cuanto') !== false || stripos($msg, 'cuesta') !== false);
    $isGreeting = (stripos($msg, 'hola') !== false || stripos($msg, 'hello') !== false || stripos($msg, 'hi') !== false);
    
    if ($isSpanish) {
        if ($isGreeting) {
            $prompt = "Eres Valentina, recepcionista amable del hotel. Saluda y ofrece ayuda (máximo 12 palabras).\n\nCliente: {$msg}\nValentina:";
        } else {
            $prompt = "Eres Valentina del hotel. Responde en 1 frase corta (máximo 10 palabras). Usa 'tenemos/nuestro'.\n\nCliente: {$msg}\nValentina:";
        }
    } else {
        if ($isGreeting) {
            $prompt = "You're Valentina, friendly hotel receptionist. Greet and offer help (max 12 words).\n\nGuest: {$msg}\nValentina:";
        } else {
            $prompt = "You're Valentina from the hotel. Reply in 1 SHORT sentence (max 10 words). Use 'we/our'.\n\nGuest: {$msg}\nValentina:";
        }
    }
    
    $data = [
        'model' => 'qwen2.5:1.5b',    // Better at following instructions than TinyLlama
        'prompt' => $prompt,
        'stream' => false,
        'keep_alive' => '5m',
        'options' => [
            'num_predict' => 20,      // VERY short - force brevity
            'temperature' => 0.2,     // More focused/consistent
            'top_p' => 0.9,
            'stop' => ["\n", "?", "¿"]  // Stop at line break or question mark
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://72.60.1.16:11434/api/generate');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $elapsed = microtime(true) - $start;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $time = round($elapsed, 2);
    
    if ($httpCode === 200 && $response) {
        $decoded = json_decode($response, true);
        $answer = trim($decoded['response'] ?? 'No response');
        
        // Speed rating
        if ($time < 1.5) {
            $rating = "🥇 EXCELLENT";
            $color = "#4caf50";
        } elseif ($time < 3) {
            $rating = "🥈 GOOD";
            $color = "#2196f3";
        } else {
            $rating = "⚠️ SLOW";
            $color = "#ff9800";
        }
        
        echo "<div class='msg ai'>";
        echo "<span class='time' style='color:{$color};'>{$rating} - {$time}s</span>";
        echo "<div class='response'>{$answer}</div>";
        echo "</div>";
        
        $totalTime += $time;
        $successCount++;
    } else {
        echo "<div class='msg error'><strong>Error:</strong> Connection failed (HTTP {$httpCode})</div>";
    }
}

echo "</div>";

// Summary
$avgTime = $successCount > 0 ? round($totalTime / $successCount, 2) : 0;

echo "<div class='chat' style='background:#f5f5f5;text-align:center;'>";
echo "<h2>📊 Test Results</h2>";
echo "<p style='font-size:1.2em;'><strong>Average Response Time:</strong> {$avgTime} seconds</p>";
echo "<p style='font-size:1.2em;'><strong>Successful:</strong> {$successCount} / " . count($testMessages) . "</p>";

if ($avgTime < 2) {
    echo "<p style='color:#4caf50;font-size:1.3em;font-weight:bold;'>✅ PERFECT FOR WHATSAPP!</p>";
    echo "<p>Your AI responds fast enough for real-time chat.</p>";
} elseif ($avgTime < 3) {
    echo "<p style='color:#2196f3;font-size:1.3em;font-weight:bold;'>✅ GOOD FOR WHATSAPP!</p>";
    echo "<p>Acceptable speed for customer service.</p>";
} else {
    echo "<p style='color:#ff9800;font-size:1.3em;font-weight:bold;'>⚠️ A BIT SLOW</p>";
    echo "<p>Consider optimizing prompts or switching models.</p>";
}
echo "</div>";

echo "<div class='chat' style='background:#e3f2fd;'>";
echo "<h2>🚀 Next Steps to Connect WhatsApp:</h2>";
echo "<ol style='line-height:2;'>";
echo "<li>Go to <a href='https://developers.facebook.com/apps' target='_blank'>Facebook Developers</a></li>";
echo "<li>Select your WhatsApp Business app</li>";
echo "<li>Go to: WhatsApp → Configuration → Webhook</li>";
echo "<li>Set webhook URL to: <code style='background:#fff;padding:3px 8px;border-radius:3px;'>http://108.175.12.152/manage/whatsapp_webhook.php</code></li>";
echo "<li>Save and test by sending a message!</li>";
echo "</ol>";
echo "<p style='margin-top:20px;font-weight:bold;'>Your AI is ready! 🎉</p>";
echo "</div>";
?>
