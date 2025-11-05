<?php
// Test business-specific questions
$url = 'http://localhost/manage/ai_trained_search.php';

$businessQueries = [
    'qué es samay wasi',
    'dónde está samay wasi',
    'cuánto cuesta samay wasi',
    'samay wasi tiene desayuno',
    'cómo hago una reserva',
    'cuál es la política de cancelación',
    'tienen traslado del aeropuerto',
    'a qué hora es el check-in',
    'tienen estacionamiento',
    'qué tours recomiendan'
];

foreach ($businessQueries as $query) {
    echo "\n========================================\n";
    echo "💬 Pregunta: $query\n";
    echo "========================================\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    
    if ($result['response']) {
        $emoji = $result['method'] === 'training_direct' ? '🎓' : '🤖';
        $confidence = round($result['confidence']);
        echo "{$emoji} Vicky: {$result['response']}\n";
        echo "📊 Confidence: {$confidence}% | Method: {$result['method']}\n";
    }
    
    curl_close($ch);
}
?>
