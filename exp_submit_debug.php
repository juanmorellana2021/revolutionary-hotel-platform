<?php
/**
 * experience_submit.php
 * 
 * Controller - Processes experience registration form submission
 * 
 * SECURITY:
 * - CSRF token validation
 * - SQL injection prevention (PDO prepared statements)
 * - XSS prevention (input sanitization)
 * - File upload validation (size, type, rename)
 * 
 * @author AI Assistant + juanmorellana2021
 * @date November 10, 2025
 */

session_start();
header('Content-Type: application/json');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1); // TEMPORARILY ENABLED FOR DEBUG
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/experience_submit_errors.log');

try {
    // ========================================
    // 1. CSRF TOKEN VALIDATION
    // ========================================
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid CSRF token. Please refresh the page and try again.');
    }
    
    // ========================================
    // 2. VALIDATE REQUEST METHOD
    // ========================================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }
    
    // ========================================
    // 3. LOAD DEPENDENCIES
    // ========================================
    require_once 'db_connection_pdo.php'; // Provides $pdo
    require_once 'classes/ExperienceManager.php';
    
    $experienceManager = new ExperienceManager($pdo);
    
    // ========================================
    // 4. VALIDATE & SANITIZE PARTNER DATA
    // ========================================
    $partnerData = [
        'business_name' => filter_input(INPUT_POST, 'business_name', FILTER_SANITIZE_STRING),
        'business_type' => 'experience', // Fixed type for this form
        'owner_name' => filter_input(INPUT_POST, 'owner_name', FILTER_SANITIZE_STRING),
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'phone' => filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING),
        'country' => filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING),
        'city' => filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING),
        'address' => filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING),
        'reward_percentage' => 5.00 // Default 5% commission
    ];
    
    // Validate required fields
    $requiredPartnerFields = ['business_name', 'owner_name', 'email', 'phone', 'country', 'city'];
    foreach ($requiredPartnerFields as $field) {
        if (empty($partnerData[$field])) {
            throw new Exception("Missing required partner field: $field");
        }
    }
    
    // Validate email format
    if (!filter_var($partnerData['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }
    
    // ========================================
    // 5. CREATE OR GET PARTNER
    // ========================================
    $partnerId = $experienceManager->createOrGetPartner($partnerData);
    
    // ========================================
    // 6. VALIDATE & SANITIZE EXPERIENCE DATA
    // ========================================
    $experienceData = [
        'partner_id' => $partnerId,
        'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
        'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
        'short_description' => filter_input(INPUT_POST, 'short_description', FILTER_SANITIZE_STRING),
        'price_usd' => filter_input(INPUT_POST, 'price_usd', FILTER_VALIDATE_FLOAT),
        'price_aini_rewards' => filter_input(INPUT_POST, 'price_aini_rewards', FILTER_VALIDATE_INT),
        'price_aini_crypto' => filter_input(INPUT_POST, 'price_aini_crypto', FILTER_VALIDATE_FLOAT),
        'discount_percentage' => filter_input(INPUT_POST, 'discount_percentage', FILTER_VALIDATE_FLOAT) ?: 0,
        'duration_hours' => filter_input(INPUT_POST, 'duration_hours', FILTER_VALIDATE_INT),
        'duration_days' => filter_input(INPUT_POST, 'duration_days', FILTER_VALIDATE_INT) ?: 1,
        'max_participants' => filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT) ?: 10,
        'min_participants' => filter_input(INPUT_POST, 'min_participants', FILTER_VALIDATE_INT) ?: 1,
        'difficulty_level' => filter_input(INPUT_POST, 'difficulty_level', FILTER_SANITIZE_STRING) ?: 'moderate',
        'category' => filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING),
        'country' => $partnerData['country'],
        'city' => $partnerData['city'],
        'meeting_point' => filter_input(INPUT_POST, 'meeting_point', FILTER_SANITIZE_STRING),
        'latitude' => filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT),
        'longitude' => filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT),
        'start_date' => filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING),
        'end_date' => filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING),
        'status' => 'pending_review' // All submissions go to moderation
    ];
    
    // Validate required experience fields
    $requiredExperienceFields = ['title', 'description', 'price_usd', 'category'];
    foreach ($requiredExperienceFields as $field) {
        if (empty($experienceData[$field])) {
            throw new Exception("Missing required experience field: $field");
        }
    }
    
    // Validate category
    $validCategories = ['adventure', 'cultural', 'food', 'nature', 'wellness', 'water_sports', 'city_tour', 'multi_day'];
    if (!in_array($experienceData['category'], $validCategories)) {
        throw new Exception("Invalid category selected.");
    }
    
    // Validate difficulty
    $validDifficulty = ['easy', 'moderate', 'hard', 'expert'];
    if (!in_array($experienceData['difficulty_level'], $validDifficulty)) {
        throw new Exception("Invalid difficulty level.");
    }
    
    // Validate price range
    if ($experienceData['price_usd'] < 1 || $experienceData['price_usd'] > 10000) {
        throw new Exception("Price must be between 1 and 10,000 USD.");
    }
    
    // ========================================
    // 7. PROCESS TAGS (CSV to JSON Array)
    // ========================================
    if (!empty($_POST['tags'])) {
        $tags = array_map('trim', explode(',', $_POST['tags']));
        $tags = array_filter($tags); // Remove empty elements
        $experienceData['tags'] = $tags;
    } else {
        $experienceData['tags'] = [];
    }
    
    // ========================================
    // 8. PROCESS AVAILABLE DAYS
    // ========================================
    if (!empty($_POST['available_days']) && is_array($_POST['available_days'])) {
        $experienceData['available_days'] = $_POST['available_days'];
    } else {
        $experienceData['available_days'] = [];
    }
    
    // ========================================
    // 9. HANDLE FILE UPLOADS
    // ========================================
    $uploadDir = __DIR__ . '/uploads/experiences/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Upload cover photo
    if (!empty($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
        $coverPath = uploadPhoto($_FILES['cover_photo'], $uploadDir, 'cover');
        $experienceData['cover_photo'] = $coverPath;
    }
    
    // Upload gallery photos
    $galleryPaths = [];
    if (!empty($_FILES['gallery_photos']['name'][0])) {
        foreach ($_FILES['gallery_photos']['name'] as $key => $name) {
            if ($_FILES['gallery_photos']['error'][$key] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['gallery_photos']['tmp_name'][$key];
                $size = $_FILES['gallery_photos']['size'][$key];
                $type = $_FILES['gallery_photos']['type'][$key];
                
                $fileData = [
                    'name' => $name,
                    'tmp_name' => $tmpName,
                    'size' => $size,
                    'type' => $type,
                    'error' => UPLOAD_ERR_OK
                ];
                
                $galleryPath = uploadPhoto($fileData, $uploadDir, 'gallery');
                $galleryPaths[] = $galleryPath;
                
                if (count($galleryPaths) >= 10) {
                    break; // Max 10 photos
                }
            }
        }
    }
    $experienceData['photo_gallery'] = $galleryPaths;
    
    // ========================================
    // 10. CREATE EXPERIENCE IN DATABASE
    // ========================================
    $experienceId = $experienceManager->createExperience($experienceData);
    
    // ========================================
    // 11. SEND EMAIL NOTIFICATION TO ADMIN (Optional)
    // ========================================
    // TODO: Implement email notification
    // sendAdminNotification($experienceId, $experienceData);
    
    // ========================================
    // 12. RETURN SUCCESS RESPONSE
    // ========================================
    echo json_encode([
        'success' => true,
        'message' => 'Experience registered successfully!',
        'experience_id' => $experienceId,
        'status' => 'pending_review'
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log('Experience Submit Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// ========================================
// HELPER FUNCTIONS
// ========================================

/**
 * Upload and validate photo file
 * @param array $file $_FILES array element
 * @param string $uploadDir Upload directory
 * @param string $prefix Filename prefix (cover/gallery)
 * @return string Relative path to uploaded file
 * @throws Exception If validation fails
 */
function uploadPhoto($file, $uploadDir, $prefix) {
    // Validate file exists
    if (empty($file['tmp_name'])) {
        throw new Exception("No file uploaded.");
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception("File too large. Maximum size is 5MB.");
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception("Invalid file type. Only JPG, PNG, and WEBP allowed.");
    }
    
    // Get file extension
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp'])) {
        $extension = 'jpg'; // Default to jpg
    }
    
    // Generate unique filename
    $uniqueId = uniqid($prefix . '_', true);
    $filename = $uniqueId . '.' . strtolower($extension);
    $filePath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception("Failed to upload file.");
    }
    
    // Return relative path (for database storage)
    return '/uploads/experiences/' . $filename;
}

/**
 * Send email notification to admin about new submission
 * @param int $experienceId Experience ID
 * @param array $data Experience data
 */
function sendAdminNotification($experienceId, $data) {
    // TODO: Implement email sending
    // Use PHPMailer or similar
    
    $to = 'admin@ainitravel.com';
    $subject = 'New Experience Submission: ' . $data['title'];
    $message = "
        A new experience has been submitted for review.
        
        Experience ID: $experienceId
        Title: {$data['title']}
        Partner: {$data['partner_id']}
        Category: {$data['category']}
        Price: \${$data['price_usd']} USD
        
        Review at: https://ainitravel.com/admin/experience_moderate.php
    ";
    
    // mail($to, $subject, $message); // Basic PHP mail
    // Or use PHPMailer for production
}
