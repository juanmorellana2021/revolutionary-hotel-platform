<?php
/**
 * api_partners.php
 * 
 * API endpoint to return partner businesses with location data
 * for the Discover map in the social app
 * 
 * @author AI Assistant
 * @date November 29, 2025
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
require_once 'db_connection_pdo.php';

try {
    // Fetch active partners with location data
    $stmt = $pdo->prepare("
        SELECT 
            id,
            business_name,
            business_type,
            city,
            address,
            latitude,
            longitude,
            phone,
            is_verified,
            reward_percentage
        FROM aini_partner_businesses 
        WHERE is_active = 1 
        AND latitude IS NOT NULL 
        AND longitude IS NOT NULL
        ORDER BY is_verified DESC, business_name ASC
    ");
    
    $stmt->execute();
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response
    $response = [
        'success' => true,
        'count' => count($partners),
        'partners' => $partners
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
