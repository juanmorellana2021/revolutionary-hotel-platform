<?php
/**
 * Save Training Data
 * Saves updated training data to JSON file
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['training_dataset'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Validate structure
if (!is_array($input['training_dataset'])) {
    http_response_code(400);
    echo json_encode(['error' => 'training_dataset must be an array']);
    exit;
}

// Ensure system_prompt and response_templates exist
if (!isset($input['system_prompt'])) {
    $input['system_prompt'] = "Eres Vicky, una agente de viajes experta en Perú. MÁXIMO 15 PALABRAS por respuesta. SOLO hablas de hoteles, tours, retiros y viajes. Eres amable, concisa y profesional. Si no sabes algo, deriva al equipo de soporte.";
}

if (!isset($input['response_templates'])) {
    $input['response_templates'] = [
        'search' => 'Buscaré {what} en {where}.',
        'clarify' => '¿Podrías especificar {detail}?',
        'confirm' => 'Perfecto, te muestro opciones de {category}.',
        'fallback' => 'Déjame buscar eso para ti.'
    ];
}

// Save to file with backup
$filename = __DIR__ . '/ai_training_data.json';
$backupFilename = __DIR__ . '/ai_training_data_backup_' . date('Y-m-d_His') . '.json';

// Create backup
if (file_exists($filename)) {
    copy($filename, $backupFilename);
    
    // Keep only last 5 backups
    $backups = glob(__DIR__ . '/ai_training_data_backup_*.json');
    if (count($backups) > 5) {
        usort($backups, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        foreach (array_slice($backups, 0, -5) as $oldBackup) {
            unlink($oldBackup);
        }
    }
}

// Save new data
$json = json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$success = file_put_contents($filename, $json);

if ($success === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

echo json_encode([
    'success' => true,
    'total_questions' => count($input['training_dataset']),
    'backup_created' => basename($backupFilename),
    'message' => 'Training data saved successfully'
]);
?>
