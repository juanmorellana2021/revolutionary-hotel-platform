<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

// Get database connection
$database = new Database();
$connection = $database->getConnection();

// Check if user is logged in and is manager
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$userManager = new UserManager();
if (!$userManager->isManager($_SESSION['user']['id'])) {
    header('Location: dashboard.php');
    exit;
}

$roomManager = new Room();
$rooms = $roomManager->getAllRooms();

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    $roomId = (int)$_POST['room_id'];
    $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
    
    if (isset($_FILES['room_photo']) && $_FILES['room_photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/rooms/';
        $fileName = uniqid() . '_' . basename($_FILES['room_photo']['name']);
        $targetPath = $uploadDir . $fileName;
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['room_photo']['type'], $allowedTypes)) {
            // Validate file size (max 5MB)
            if ($_FILES['room_photo']['size'] <= 5 * 1024 * 1024) {
                if (move_uploaded_file($_FILES['room_photo']['tmp_name'], $targetPath)) {
                    // If this is set as primary, unset other primary photos for this room
                    if ($isPrimary) {
                        $stmt = $connection->prepare("UPDATE room_photos SET is_primary = 0 WHERE room_id = ?");
                        $stmt->execute([$roomId]);
                    }
                    
                    // Insert photo record
                    $stmt = $connection->prepare("INSERT INTO room_photos (room_id, photo_path, photo_name, is_primary) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$roomId, $targetPath, $fileName, $isPrimary]);
                    
                    $message = "Foto subida exitosamente";
                    $messageType = "success";
                } else {
                    $message = "Error al subir la foto";
                    $messageType = "error";
                }
            } else {
                $message = "El archivo es demasiado grande (máximo 5MB)";
                $messageType = "error";
            }
        } else {
            $message = "Tipo de archivo no permitido. Use JPG, PNG, GIF o WebP";
            $messageType = "error";
        }
    }
}

// Handle photo deletion
if (isset($_GET['delete_photo'])) {
    $photoId = (int)$_GET['delete_photo'];
    
    // Get photo info
    $stmt = $connection->prepare("SELECT photo_path FROM room_photos WHERE id = ?");
    $stmt->execute([$photoId]);
    $photo = $stmt->fetch();
    
    if ($photo) {
        // Delete file
        if (file_exists($photo['photo_path'])) {
            unlink($photo['photo_path']);
        }
        
        // Delete database record
        $stmt = $connection->prepare("DELETE FROM room_photos WHERE id = ?");
        $stmt->execute([$photoId]);
        
        $message = "Foto eliminada exitosamente";
        $messageType = "success";
    }
}

// Handle set primary photo
if (isset($_GET['set_primary'])) {
    $photoId = (int)$_GET['set_primary'];
    
    // Get room_id for this photo
    $stmt = $connection->prepare("SELECT room_id FROM room_photos WHERE id = ?");
    $stmt->execute([$photoId]);
    $photo = $stmt->fetch();
    
    if ($photo) {
        // Unset all primary photos for this room
        $stmt = $connection->prepare("UPDATE room_photos SET is_primary = 0 WHERE room_id = ?");
        $stmt->execute([$photo['room_id']]);
        
        // Set this photo as primary
        $stmt = $connection->prepare("UPDATE room_photos SET is_primary = 1 WHERE id = ?");
        $stmt->execute([$photoId]);
        
        $message = "Foto principal establecida";
        $messageType = "success";
    }
}

// Get all photos with room info
$stmt = $connection->prepare("
    SELECT rp.*, r.room_number, r.room_type 
    FROM room_photos rp 
    JOIN rooms r ON rp.room_id = r.id 
    ORDER BY r.room_number, rp.is_primary DESC, rp.upload_date DESC
");
$stmt->execute();
$photos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Fotos - Hotel Booking System</title>
    
    <!-- AINI Innovations Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="favicon.png">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .nav-menu {
            background: #34495e;
            padding: 0;
        }

        .nav-menu ul {
            list-style: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }

        .nav-menu li {
            position: relative;
        }

        .nav-menu a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }

        .nav-menu a:hover {
            background: #2c3e50;
        }

        .nav-menu .dropdown {
            position: relative;
        }

        .nav-menu .dropdown-content {
            display: none;
            position: absolute;
            background: #2c3e50;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            top: 100%;
        }

        .nav-menu .dropdown:hover .dropdown-content {
            display: block;
        }

        .content {
            padding: 30px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .upload-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .btn-danger:hover {
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .btn-warning:hover {
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.4);
        }

        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .photo-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }

        .photo-card:hover {
            transform: translateY(-5px);
        }

        .photo-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .photo-info {
            padding: 15px;
        }

        .room-info {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .primary-badge {
            background: #27ae60;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .photo-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .photo-actions .btn {
            padding: 8px 12px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .photos-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-menu ul {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📸 Gestión de Fotos de Habitaciones</h1>
            <p>Administra las fotos de tus habitaciones para mostrar a los huéspedes</p>
        </div>

        <!-- Navigation Menu -->
        <nav class="nav-menu">
            <ul>
                <li class="dropdown">
                    <a href="#" class="dropbtn">🏨 Hotel</a>
                    <div class="dropdown-content">
                        <a href="calendar_view.php">📅 Calendar View</a>
                        <a href="room_management.php">🏠 Room Management</a>
                        <a href="room_photos.php">📸 Room Photos</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">👥 Staff</a>
                    <div class="dropdown-content">
                        <a href="employee_management.php">👤 Employee Management</a>
                        <a href="time_clock.php">⏰ Time Clock</a>
                        <a href="payroll_management.php">💰 Payroll</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">💼 Finance</a>
                    <div class="dropdown-content">
                        <a href="income_management.php">💵 Income Management</a>
                        <a href="expense_management.php">💳 Expense Management</a>
                        <a href="accounting_dashboard.php">📊 Accounting Dashboard</a>
                    </div>
                </li>
                <li><a href="manager_dashboard.php">🏠 Dashboard</a></li>
            </ul>
        </nav>

        <div class="content">
            <?php if (isset($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Upload Form -->
            <div class="upload-section">
                <h2>📤 Subir Nueva Foto</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="room_id">🏨 Habitación</label>
                            <select id="room_id" name="room_id" required>
                                <option value="">Seleccionar habitación</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>">
                                        Habitación <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="room_photo">📸 Foto</label>
                            <input type="file" id="room_photo" name="room_photo" accept="image/*" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_primary" name="is_primary">
                            <label for="is_primary">⭐ Establecer como foto principal</label>
                        </div>
                    </div>
                    <button type="submit" name="upload_photo" class="btn">📤 Subir Foto</button>
                </form>
            </div>

            <!-- Photos Grid -->
            <h2>🖼️ Fotos de Habitaciones</h2>
            <?php if (empty($photos)): ?>
                <p style="text-align: center; color: #666; margin: 40px 0;">
                    No hay fotos subidas aún. ¡Sube la primera foto para comenzar!
                </p>
            <?php else: ?>
                <div class="photos-grid">
                    <?php foreach ($photos as $photo): ?>
                        <div class="photo-card">
                            <img src="<?php echo $photo['photo_path']; ?>" alt="Habitación <?php echo $photo['room_number']; ?>">
                            <div class="photo-info">
                                <div class="room-info">
                                    🏨 Habitación <?php echo $photo['room_number']; ?> - <?php echo $photo['room_type']; ?>
                                    <?php if ($photo['is_primary']): ?>
                                        <span class="primary-badge">⭐ Principal</span>
                                    <?php endif; ?>
                                </div>
                                <p style="color: #666; font-size: 14px;">
                                    📅 Subida: <?php echo date('d/m/Y H:i', strtotime($photo['upload_date'])); ?>
                                </p>
                                <div class="photo-actions">
                                    <?php if (!$photo['is_primary']): ?>
                                        <a href="?set_primary=<?php echo $photo['id']; ?>" class="btn btn-warning">⭐ Hacer Principal</a>
                                    <?php endif; ?>
                                    <a href="?delete_photo=<?php echo $photo['id']; ?>" class="btn btn-danger" 
                                       onclick="return confirm('¿Estás seguro de eliminar esta foto?')">🗑️ Eliminar</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>