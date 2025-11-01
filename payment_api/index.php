<?php
// Payment API entry point
header('Content-Type: application/json');

require_once 'adyen_payment.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

switch ($endpoint) {
    case 'create-payment':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $result = AdyenPayment::createPayment($data);
            echo json_encode($result);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
        }
        break;
    case 'webhook':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $result = AdyenPayment::handleWebhook($data);
            echo json_encode($result);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
        }
        break;
    case 'status':
        echo json_encode([
            'status' => 'OK',
            'message' => 'Payment API is running',
            'version' => '1.0',
            'environment' => 'sandbox'
        ]);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint Not Found']);
}
