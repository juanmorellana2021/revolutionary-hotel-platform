<?php
/**
 * Shared HTML Header for Hotel Management System
 * Includes Bootstrap 5, Icons, and Custom CSS
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Hotel Management System'; ?> - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'AiNi Hotel'); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Hotel System Styles -->
    <style>
        :root {
            --hotel-primary: #667eea;
            --hotel-secondary: #764ba2;
            --hotel-success: #28a745;
            --hotel-danger: #dc3545;
            --hotel-warning: #ffc107;
            --hotel-info: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--hotel-primary) 0%, var(--hotel-secondary) 100%);
            min-height: 100vh;
        }
        
        .main-content {
            background: white;
            border-radius: 15px;
            margin: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .navbar-hotel {
            background: rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .navbar-hotel .navbar-brand {
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .navbar-hotel .nav-link {
            color: rgba(255,255,255,0.9);
            transition: color 0.3s ease;
        }
        
        .navbar-hotel .nav-link:hover,
        .navbar-hotel .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
        }
        
        .btn-hotel-primary {
            background: linear-gradient(45deg, var(--hotel-primary), var(--hotel-secondary));
            border: none;
            color: white;
        }
        
        .btn-hotel-primary:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
            color: white;
        }
        
        .card-hotel {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-hotel:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .table-hotel {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .badge-working {
            background: var(--hotel-success);
        }
        
        .badge-completed {
            background: var(--hotel-secondary);
        }
        
        .currency-pen {
            color: var(--hotel-info);
            font-weight: 600;
        }
        
        .currency-usd {
            color: var(--hotel-success);
            font-weight: 600;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin: 10px;
                padding: 20px;
            }
        }
    </style>
    
    <!-- Custom page styles -->
    <?php if (isset($customStyles)): ?>
        <style><?php echo $customStyles; ?></style>
    <?php endif; ?>
</head>
<body>