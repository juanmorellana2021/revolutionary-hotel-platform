<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Please log in first.'); window.close();</script>";
    exit;
}

$userManager = new UserManager();
if (!$userManager->isManager($_SESSION['user']['id'])) {
    echo "<script>alert('Access denied.'); window.close();</script>";
    exit;
}

$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
if (!$roomId) {
    echo "<script>alert('Invalid room ID.'); window.close();</script>";
    exit;
}

// Get database connection for photo operations
$database = new Database();
$connection = $database->getConnection();

$message = '';
$success = false;

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (isset($_FILES['room_photo']) && $_FILES['room_photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileName = uniqid() . '_' . basename($_FILES['room_photo']['name']);
        $targetDir = 'uploads/room_photos/';
        $targetPath = $targetDir . $fileName;
        
        // Create directory if it doesn't exist
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                $message = "Error: Unable to create upload directory.";
            }
        }
        
        if (empty($message) && in_array($_FILES['room_photo']['type'], $allowedTypes)) {
            if ($_FILES['room_photo']['size'] <= 5 * 1024 * 1024) {
                if (move_uploaded_file($_FILES['room_photo']['tmp_name'], $targetPath)) {
                    // If this is set as primary, unset other primary photos for this room
                    if (isset($_POST['is_primary'])) {
                        $stmt = $connection->prepare("UPDATE room_photos SET is_primary = 0 WHERE room_id = ?");
                        $stmt->execute([$roomId]);
                    }
                    
                    // Insert photo record
                    $stmt = $connection->prepare("INSERT INTO room_photos (room_id, photo_path, photo_name, is_primary) VALUES (?, ?, ?, ?)");
                    $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
                    $stmt->execute([$roomId, $targetPath, $fileName, $isPrimary]);
                    
                    $message = "Photo uploaded successfully!";
                    $success = true;
                } else {
                    $message = "Error uploading photo.";
                }
            } else {
                $message = "File size must be less than 5MB.";
            }
        } else {
            $message = "Invalid file type. Please select JPG, PNG, or GIF.";
        }
    } else {
        $message = "Please select a photo to upload.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Room Photo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f8f9fa;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 15px 0;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .info {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📷 Upload Room Photo</h2>
        <p>Room ID: <?php echo $roomId; ?></p>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <script>
                // Auto-close after successful upload
                setTimeout(function() {
                    if (confirm('Photo uploaded successfully! Close this window?')) {
                        window.close();
                    }
                }, 2000);
            </script>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="room_photo">📸 Select Photo:</label>
                <input type="file" id="room_photo" name="room_photo" accept="image/jpeg,image/jpg,image/png,image/gif" required>
                <div class="info">Supported formats: JPG, PNG, GIF (Max 5MB)</div>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="is_primary" name="is_primary">
                <label for="is_primary">⭐ Set as primary photo</label>
            </div>
            
            <button type="submit" name="upload_photo" class="btn">📤 Upload Photo</button>
            <button type="button" onclick="window.close()" class="btn btn-secondary">❌ Cancel</button>
        </form>
    </div>
</body>
</html>