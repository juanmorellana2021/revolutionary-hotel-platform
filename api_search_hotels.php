<?php
/**
 * AI Hotel Search API
 * Receives search parameters from Vicky and queries the database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
require_once 'db_connection.php';

// Get search parameters
$input = file_get_contents('php://input');
$params = json_decode($input, true);

// Extract search criteria
$destination = $params['destination'] ?? '';
$minPrice = $params['minPrice'] ?? 0;
$maxPrice = $params['maxPrice'] ?? 99999;
$features = $params['features'] ?? [];
$category = $params['category'] ?? '';
$minRating = $params['minRating'] ?? 0;
$limit = $params['limit'] ?? 10;

// Build SQL query
$sql = "SELECT * FROM hotels WHERE is_active = 1";
$sqlParams = [];

// Filter by destination/location
if (!empty($destination)) {
    $sql .= " AND (location LIKE ? OR name LIKE ?)";
    $searchTerm = "%{$destination}%";
    $sqlParams[] = $searchTerm;
    $sqlParams[] = $searchTerm;
}

// Filter by price range
$sql .= " AND price BETWEEN ? AND ?";
$sqlParams[] = $minPrice;
$sqlParams[] = $maxPrice;

// Filter by category
if (!empty($category)) {
    $sql .= " AND category LIKE ?";
    $sqlParams[] = "%{$category}%";
}

// Filter by minimum rating
if ($minRating > 0) {
    $sql .= " AND rating >= ?";
    $sqlParams[] = $minRating;
}

// Order by rating and price
$sql .= " ORDER BY rating DESC, price ASC LIMIT ?";
$sqlParams[] = (int)$limit;

try {
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }
    
    // Bind parameters dynamically
    if (!empty($sqlParams)) {
        $types = str_repeat('s', count($sqlParams) - 1) . 'i'; // Last param is int (limit)
        $stmt->bind_param($types, ...$sqlParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $hotels = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON fields
        $row['features'] = json_decode($row['features'], true);
        $row['images'] = json_decode($row['images'], true);
        
        // Filter by features if requested
        if (!empty($features)) {
            $hasAllFeatures = true;
            foreach ($features as $feature) {
                $featureFound = false;
                foreach ($row['features'] as $hotelFeature) {
                    if (stripos($hotelFeature, $feature) !== false) {
                        $featureFound = true;
                        break;
                    }
                }
                if (!$featureFound) {
                    $hasAllFeatures = false;
                    break;
                }
            }
            if (!$hasAllFeatures) {
                continue; // Skip this hotel
            }
        }
        
        $hotels[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($hotels),
        'hotels' => $hotels,
        'query' => [
            'destination' => $destination,
            'priceRange' => [$minPrice, $maxPrice],
            'features' => $features,
            'category' => $category,
            'minRating' => $minRating
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
