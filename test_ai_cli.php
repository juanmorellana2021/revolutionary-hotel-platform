<?php
// Quick test of AI training search
$url = 'http://localhost/manage/ai_trained_search.php';

$queries = [
    'hotel con piscina',
    'tour machu picchu',
    'hotel barato',
    'hola'
];

foreach ($queries as $query) {
    echo "\n========================================\n";
    echo "Testing: $query\n";
    echo "========================================\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response:\n";
    print_r(json_decode($response, true));
    echo "\n";
    
    curl_close($ch);
}
?>
