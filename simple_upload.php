<?php
session_start();

// Check if user is logged in and is manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: login.php');
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'hotel_booking';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if (!$room_id) {
    die("Room ID required");
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photos'])) {
    $uploadDir = 'uploads/rooms/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    // Handle multiple files
    $files = $_FILES['photos'];
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = $files['name'][$i];
            $tmpName = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileType = $files['type'][$i];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "File $fileName: Invalid file type. Only JPG, PNG, GIF allowed.";
                continue;
            }
            
            // Validate file size (5MB max)
            if ($fileSize > 5 * 1024 * 1024) {
                $errors[] = "File $fileName: File too large. Maximum 5MB allowed.";
                continue;
            }
            
            // Generate unique filename
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = 'room_' . $room_id . '_' . time() . '_' . $i . '.' . $extension;
            $filePath = $uploadDir . $newFileName;
            
            // Move uploaded file
            if (move_uploaded_file($tmpName, $filePath)) {
                // Save to database
                try {
                    $stmt = $pdo->prepare("INSERT INTO room_photos (room_id, photo_url, is_primary) VALUES (?, ?, 0)");
                    $stmt->execute([$room_id, $filePath]);
                    $uploadedFiles[] = $fileName;
                } catch (PDOException $e) {
                    $errors[] = "Database error for $fileName: " . $e->getMessage();
                    unlink($filePath); // Delete file if database insert fails
                }
            } else {
                $errors[] = "Failed to upload $fileName";
            }
        } else {
            $errors[] = "Upload error for file " . $files['name'][$i] . ": " . $files['error'][$i];
        }
    }
    
    // Set success/error messages
    if (!empty($uploadedFiles)) {
        $_SESSION['upload_success'] = count($uploadedFiles) . " file(s) uploaded successfully: " . implode(', ', $uploadedFiles);
    }
    if (!empty($errors)) {
        $_SESSION['upload_errors'] = implode('<br>', $errors);
    }
    
    // Redirect back to room management
    header("Location: room_management.php?upload_complete=1");
    exit();
}

// Get room info
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    die("Room not found");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Photos - Room <?php echo htmlspecialchars($room['room_number']); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .upload-area {
            border: 3px dashed #007bff;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #0056b3;
            background: #e3f2fd;
        }
        .upload-area.dragover {
            border-color: #28a745;
            background: #d4edda;
        }
        input[type="file"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            margin: 10px 0;
            cursor: pointer;
        }
        input[type="file"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .file-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            border-radius: 10px;
            background: #e9ecef;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
            width: 0%;
        }
        .room-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 6px 6px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📸 Upload Photos</h1>
            <p>Simple & Reliable Upload - No Freezing Issues!</p>
        </div>
        
        <div class="content">
            <div class="room-info">
                <h3>🏨 Room Information</h3>
                <p><strong>Room Number:</strong> <?php echo htmlspecialchars($room['room_number']); ?></p>
                <p><strong>Room Type:</strong> <?php echo htmlspecialchars($room['room_type']); ?></p>
                <p><strong>Price:</strong> $<?php echo number_format($room['price_usd'], 2); ?> USD / S/<?php echo number_format($room['price_pen'], 2); ?> PEN</p>
            </div>

            <?php if (isset($_SESSION['upload_success'])): ?>
                <div class="alert alert-success">
                    ✅ <?php echo $_SESSION['upload_success']; unset($_SESSION['upload_success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['upload_errors'])): ?>
                <div class="alert alert-danger">
                    ❌ <?php echo $_SESSION['upload_errors']; unset($_SESSION['upload_errors']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" id="uploadArea">
                    <div style="font-size: 48px; margin-bottom: 15px;">📷</div>
                    <h3>Select Photos to Upload</h3>
                    <p>Choose multiple image files (JPG, PNG, GIF)</p>
                    <p>Maximum file size: 5MB per file</p>
                </div>

                <div class="file-info">
                    <h4>📋 Upload Methods:</h4>
                    <p><strong>Method 1:</strong> Use the file input below (most reliable)</p>
                    <p><strong>Method 2:</strong> Or drag & drop files onto the area above</p>
                </div>

                <label for="photos" style="display: block; font-weight: bold; margin-bottom: 10px;">
                    📁 Select Files (Multiple selection supported):
                </label>
                <input type="file" 
                       id="photos" 
                       name="photos[]" 
                       multiple 
                       accept="image/*"
                       onchange="showFileInfo(this)"
                       required>

                <div id="fileInfo" style="display: none;" class="file-info">
                    <h4>📋 Selected Files:</h4>
                    <div id="fileList"></div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                        📤 Upload Photos
                    </button>
                    <a href="room_management.php" class="btn btn-secondary">
                        ❌ Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show file information when files are selected
        function showFileInfo(input) {
            const fileInfo = document.getElementById('fileInfo');
            const fileList = document.getElementById('fileList');
            const uploadBtn = document.getElementById('uploadBtn');
            
            if (input.files && input.files.length > 0) {
                let html = '';
                let totalSize = 0;
                
                for (let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    const size = (file.size / (1024 * 1024)).toFixed(2);
                    totalSize += file.size;
                    
                    html += `<div style="padding: 5px 0; border-bottom: 1px solid #eee;">
                        <strong>${file.name}</strong> - ${size} MB
                        <span style="color: #666;">(${file.type})</span>
                    </div>`;
                }
                
                const totalSizeMB = (totalSize / (1024 * 1024)).toFixed(2);
                html += `<div style="margin-top: 10px; font-weight: bold; color: #007bff;">
                    Total: ${input.files.length} files, ${totalSizeMB} MB
                </div>`;
                
                fileList.innerHTML = html;
                fileInfo.style.display = 'block';
                
                // Enable upload button and make it prominent
                uploadBtn.style.background = '#28a745';
                uploadBtn.style.transform = 'scale(1.05)';
                uploadBtn.innerHTML = `📤 Upload ${input.files.length} Photo(s)`;
                
            } else {
                fileInfo.style.display = 'none';
                uploadBtn.style.background = '#007bff';
                uploadBtn.style.transform = 'scale(1)';
                uploadBtn.innerHTML = '📤 Upload Photos';
            }
        }

        // Drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('photos');

        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                showFileInfo(fileInput);
            }
        });

        // Click to select files
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        // Form submission with progress indication
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const uploadBtn = document.getElementById('uploadBtn');
            uploadBtn.innerHTML = '⏳ Uploading...';
            uploadBtn.disabled = true;
            uploadBtn.style.background = '#ffc107';
        });

        console.log('✅ Simple Upload Page Loaded - No JavaScript file dialog issues!');
    </script>
</body>
</html>