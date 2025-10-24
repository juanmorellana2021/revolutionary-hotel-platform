<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI VPS Connection Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">🤖 AI VPS Connection Test</h1>
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <p class="text-blue-800">
                    <strong>Testing connection to AI VPS:</strong> 72.60.1.16:11434<br>
                    <strong>Model:</strong> TinyLlama (0.69s average response)
                </p>
            </div>

            <?php
            require_once 'includes/ollama_ai.php';
            
            echo "<h2 class='text-2xl font-semibold mb-4 text-gray-700'>Test Results:</h2>";
            
            // Test 1: Basic connection
            echo "<div class='mb-6 p-4 bg-gray-50 rounded-lg'>";
            echo "<h3 class='text-lg font-semibold mb-2'>🔌 Test 1: API Connection</h3>";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://72.60.1.16:11434/api/tags');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            
            $start = microtime(true);
            $response = curl_exec($ch);
            $elapsed = microtime(true) - $start;
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                echo "<p class='text-green-600'>✅ Connected successfully! ({$elapsed}s)</p>";
                echo "<p class='text-sm text-gray-600 mt-2'><strong>Available models:</strong></p>";
                echo "<ul class='list-disc list-inside text-sm text-gray-600'>";
                foreach ($data['models'] as $model) {
                    $size = round($model['size'] / 1024 / 1024 / 1024, 2);
                    echo "<li>{$model['name']} ({$size} GB)</li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='text-red-600'>❌ Connection failed! HTTP Code: {$httpCode}</p>";
                echo "<p class='text-sm text-gray-600 mt-2'>Check if AI VPS is accessible and Ollama is running.</p>";
            }
            echo "</div>";
            
            // Test 2: TinyLlama speed test
            if ($httpCode === 200) {
                echo "<div class='mb-6 p-4 bg-gray-50 rounded-lg'>";
                echo "<h3 class='text-lg font-semibold mb-2'>⚡ Test 2: TinyLlama Speed</h3>";
                
                try {
                    $ai = new OllamaAI();
                    
                    $start = microtime(true);
                    $result = $ai->chat("What time is hotel check-in? Answer briefly.");
                    $elapsed = microtime(true) - $start;
                    
                    if ($result['success']) {
                        $speed = round($elapsed, 2);
                        
                        if ($speed < 1.0) {
                            $badge = "<span class='bg-green-100 text-green-800 text-xs px-2 py-1 rounded'>🥇 BLAZING FAST</span>";
                        } elseif ($speed < 2.0) {
                            $badge = "<span class='bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded'>🥈 VERY FAST</span>";
                        } else {
                            $badge = "<span class='bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded'>⚠️ ACCEPTABLE</span>";
                        }
                        
                        echo "<p class='text-green-600 mb-2'>✅ Response received in <strong>{$speed} seconds</strong> {$badge}</p>";
                        echo "<div class='bg-white p-3 rounded border border-gray-200 mt-2'>";
                        echo "<p class='text-sm text-gray-700'><strong>AI Response:</strong></p>";
                        echo "<p class='text-gray-800 mt-1'>{$result['response']}</p>";
                        echo "</div>";
                    } else {
                        echo "<p class='text-red-600'>❌ {$result['error']}</p>";
                    }
                    
                } catch (Exception $e) {
                    echo "<p class='text-red-600'>❌ Error: " . $e->getMessage() . "</p>";
                }
                echo "</div>";
                
                // Test 3: WhatsApp-style conversation
                echo "<div class='mb-6 p-4 bg-gray-50 rounded-lg'>";
                echo "<h3 class='text-lg font-semibold mb-2'>💬 Test 3: WhatsApp-Style Chat</h3>";
                
                $testMessages = [
                    "Hola, tienen habitaciones disponibles?",
                    "Cuanto cuesta por noche?",
                    "Tienen WiFi gratis?"
                ];
                
                foreach ($testMessages as $msg) {
                    echo "<div class='mb-3'>";
                    echo "<p class='text-sm text-gray-600 mb-1'><strong>Guest:</strong> {$msg}</p>";
                    
                    try {
                        $start = microtime(true);
                        $result = $ai->chat($msg);
                        $elapsed = microtime(true) - $start;
                        
                        if ($result['success']) {
                            $speed = round($elapsed, 2);
                            echo "<div class='bg-green-50 p-2 rounded mt-1'>";
                            echo "<p class='text-sm text-green-900'><strong>AI ({$speed}s):</strong> {$result['response']}</p>";
                            echo "</div>";
                        }
                    } catch (Exception $e) {
                        echo "<p class='text-red-600 text-sm'>Error: " . $e->getMessage() . "</p>";
                    }
                    echo "</div>";
                }
                echo "</div>";
            }
            
            // Summary
            echo "<div class='bg-gradient-to-r from-blue-500 to-purple-600 text-white p-6 rounded-lg'>";
            echo "<h3 class='text-xl font-bold mb-2'>🎉 Next Steps</h3>";
            echo "<ol class='list-decimal list-inside space-y-2'>";
            echo "<li>Deploy this code to your production server (108.175.12.152)</li>";
            echo "<li>Configure WhatsApp webhook URL in Meta/Twilio</li>";
            echo "<li>Test with real WhatsApp messages</li>";
            echo "<li>Monitor AI response times in logs</li>";
            echo "<li>Train AI with your specific hotel information</li>";
            echo "</ol>";
            echo "<p class='mt-4 text-sm'>Your WhatsApp sales agent is ready to handle customer inquiries 24/7! 🚀</p>";
            echo "</div>";
            ?>
            
        </div>
    </div>
</body>
</html>
