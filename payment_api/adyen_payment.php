<?php
// Adyen payment integration (sandbox)
class AdyenPayment {
    // Replace with your Adyen sandbox API key and merchant account
    private static $apiKey = 'YOUR_ADYEN_SANDBOX_API_KEY';
    private static $merchantAccount = 'YOUR_MERCHANT_ACCOUNT';
    private static $endpoint = 'https://checkout-test.adyen.com/v70/payments';

    public static function createPayment($data) {
        // Validate input
        if (!isset($data['amount']) || !isset($data['paymentMethod'])) {
            return [
                'status' => 400,
                'error' => 'Missing required fields: amount, paymentMethod'
            ];
        }

        $payload = [
            'amount' => [
                'value' => intval($data['amount'] * 100), // e.g. 10.00 -> 1000
                'currency' => $data['currency'] ?? 'USD',
            ],
            'reference' => $data['reference'] ?? 'AINI_' . time(),
            'paymentMethod' => $data['paymentMethod'],
            'merchantAccount' => self::$merchantAccount,
            'returnUrl' => $data['returnUrl'] ?? 'https://aini.com/payment_return',
        ];

        $ch = curl_init(self::$endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-API-Key: ' . self::$apiKey,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'response' => json_decode($response, true)
        ];
    }

    public static function handleWebhook($data) {
        // For demo: just log and return received data
        // In production, verify HMAC signature and process event
        
        // Log webhook for debugging
        error_log('Adyen Webhook: ' . json_encode($data));
        
        return [
            'received' => true,
            'message' => 'Webhook received (sandbox demo)',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public static function getConfig() {
        return [
            'endpoint' => self::$endpoint,
            'merchantAccount' => self::$merchantAccount,
            'hasApiKey' => !empty(self::$apiKey) && self::$apiKey !== 'YOUR_ADYEN_SANDBOX_API_KEY'
        ];
    }
}
