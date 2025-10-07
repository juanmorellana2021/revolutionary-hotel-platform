<?php
session_start();

// Check if user is logged in and is manager
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'manager') {
    header('Location: login.php');
    exit();
}

require_once 'includes/classes.php';

// Database connection
$database = new Database();
$connection = $database->getConnection();

// Function to clean up duplicate time clock entries
function cleanupDuplicateEntries($connection) {
    $cleaned = 0;
    $errors = [];
    
    try {
        // Find duplicate entries based on employee_id, date, and time
        $sql = "
            SELECT employee_id, DATE(clock_in) as date_only, TIME(clock_in) as time_only, COUNT(*) as count, GROUP_CONCAT(id) as ids
            FROM time_clock 
            WHERE clock_in IS NOT NULL 
            GROUP BY employee_id, DATE(clock_in), TIME(clock_in)
            HAVING COUNT(*) > 1
            ORDER BY employee_id, date_only, time_only
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate['ids']);
            // Sort by ID to keep the first entry (oldest)
            sort($ids);
            
            // Remove all but the first entry
            $idsToDelete = array_slice($ids, 1);
            
            if (!empty($idsToDelete)) {
                $placeholders = str_repeat('?,', count($idsToDelete) - 1) . '?';
                $deleteStmt = $connection->prepare("DELETE FROM time_clock WHERE id IN ($placeholders)");
                $deleteStmt->execute($idsToDelete);
                
                $cleaned += count($idsToDelete);
                
                echo "<div class='alert alert-success'>
                    Removed " . count($idsToDelete) . " duplicate entries for Employee ID {$duplicate['employee_id']} on {$duplicate['date_only']} at {$duplicate['time_only']}
                </div>";
            }
        }
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
    
    return ['cleaned' => $cleaned, 'errors' => $errors];
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cleanup'])) {
    $result = cleanupDuplicateEntries($connection);
    
    if ($result['cleaned'] > 0) {
        $message = "✅ Successfully cleaned up {$result['cleaned']} duplicate time clock entries!";
        $messageType = 'success';
    } else {
        $message = "ℹ️ No duplicate entries found to clean up.";
        $messageType = 'info';
    }
    
    if (!empty($result['errors'])) {
        $message .= "<br>❌ Errors: " . implode(', ', $result['errors']);
        $messageType = 'warning';
    }
}

// Get statistics about current entries
try {
    $statsStmt = $connection->prepare("
        SELECT 
            COUNT(*) as total_entries,
            COUNT(DISTINCT employee_id) as unique_employees,
            MIN(clock_in) as earliest_entry,
            MAX(clock_in) as latest_entry
        FROM time_clock 
        WHERE clock_in IS NOT NULL
    ");
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Check for potential duplicates
    $duplicateStmt = $connection->prepare("
        SELECT COUNT(*) as potential_duplicates
        FROM (
            SELECT employee_id, DATE(clock_in) as date_only, TIME(clock_in) as time_only, COUNT(*) as count
            FROM time_clock 
            WHERE clock_in IS NOT NULL 
            GROUP BY employee_id, DATE(clock_in), TIME(clock_in)
            HAVING COUNT(*) > 1
        ) as dup
    ");
    $duplicateStmt->execute();
    $duplicateInfo = $duplicateStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $stats = ['total_entries' => 0, 'unique_employees' => 0, 'earliest_entry' => null, 'latest_entry' => null];
    $duplicateInfo = ['potential_duplicates' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Clock Cleanup - Ghost Entry Removal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .stats-card {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .cleanup-card {
            background: linear-gradient(135deg, #ff6b6b 0%, #ffa500 100%);
            color: white;
        }
        .btn-cleanup {
            background: #ff4757;
            border: none;
            color: white;
            font-weight: bold;
            padding: 12px 24px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-cleanup:hover {
            background: #ff3742;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 71, 66, 0.4);
        }
        .stat-item {
            padding: 1rem;
            text-align: center;
            border-radius: 10px;
            margin: 0.5rem 0;
            background: rgba(255, 255, 255, 0.1);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-4">
                    <h1 class="text-white mb-3">
                        <i class="bi bi-tools me-3"></i>Time Clock Cleanup
                    </h1>
                    <p class="text-white-50">Remove duplicate "ghost" entries from the time clock system</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Card -->
                <div class="card stats-card mb-4">
                    <div class="card-header text-center">
                        <h3 class="mb-0"><i class="bi bi-graph-up me-2"></i>Time Clock Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo number_format($stats['total_entries']); ?></span>
                                    <span class="stat-label">Total Entries</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $stats['unique_employees']; ?></span>
                                    <span class="stat-label">Employees</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $duplicateInfo['potential_duplicates']; ?></span>
                                    <span class="stat-label">Potential Duplicates</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <span class="stat-number">
                                        <?php echo $stats['earliest_entry'] ? date('M j', strtotime($stats['earliest_entry'])) : 'N/A'; ?>
                                    </span>
                                    <span class="stat-label">Earliest Entry</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cleanup Card -->
                <div class="card cleanup-card mb-4">
                    <div class="card-header text-center">
                        <h3 class="mb-0"><i class="bi bi-trash3 me-2"></i>Duplicate Entry Cleanup</h3>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <i class="bi bi-exclamation-triangle" style="font-size: 3rem; opacity: 0.8;"></i>
                            <h4 class="mt-3">Remove Ghost Entries</h4>
                            <p class="mb-0">
                                This will find and remove duplicate time clock entries that occur when employees 
                                clock in multiple times due to form submission issues or browser refreshes.
                            </p>
                        </div>
                        
                        <?php if ($duplicateInfo['potential_duplicates'] > 0): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong><?php echo $duplicateInfo['potential_duplicates']; ?> potential duplicate entries found!</strong>
                            </div>
                            
                            <form method="POST" onsubmit="return confirm('Are you sure you want to clean up duplicate entries? This action cannot be undone.');">
                                <button type="submit" name="cleanup" class="btn btn-cleanup btn-lg">
                                    <i class="bi bi-trash3 me-2"></i>Clean Up Duplicates
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>No duplicate entries found!</strong> Your time clock system is clean.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="text-center">
                    <a href="time_clock.php" class="btn btn-light btn-lg me-3">
                        <i class="bi bi-arrow-left me-2"></i>Back to Time Clock
                    </a>
                    <a href="manager_dashboard.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-speedometer2 me-2"></i>Manager Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>