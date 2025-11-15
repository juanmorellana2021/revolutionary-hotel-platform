<?php
session_start();
require_once 'includes/classes.php';

// Check if user is logged in and is manager/admin
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'manager' && $_SESSION['user_role'] !== 'admin')) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$connection = $database->getConnection();

// Get filter parameters
$filterBooking = $_GET['booking_id'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$filterDateTo = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Build query
$query = "
    SELECT r.*, 
           b.booking_reference, b.check_in_date, b.check_out_date,
           u.first_name, u.last_name, u.email as user_email
    FROM receipts r
    JOIN bookings b ON r.booking_id = b.id
    LEFT JOIN users u ON r.generated_by = u.id
    WHERE r.generated_at BETWEEN ? AND ?
";

$params = [$filterDateFrom . ' 00:00:00', $filterDateTo . ' 23:59:59'];

if ($filterBooking) {
    $query .= " AND r.booking_id = ?";
    $params[] = $filterBooking;
}

if ($filterType) {
    $query .= " AND r.receipt_type = ?";
    $params[] = $filterType;
}

$query .= " ORDER BY r.generated_at DESC LIMIT 500";

$stmt = $connection->prepare($query);
$stmt->execute($params);
$receipts = $stmt->fetchAll();

// Get statistics
$statsStmt = $connection->prepare("
    SELECT 
        COUNT(*) as total_receipts,
        SUM(CASE WHEN receipt_type = 'pdf' THEN 1 ELSE 0 END) as pdf_count,
        SUM(CASE WHEN receipt_type = 'print' THEN 1 ELSE 0 END) as print_count,
        SUM(CASE WHEN receipt_type = 'email' THEN 1 ELSE 0 END) as email_count,
        SUM(CASE WHEN receipt_type = 'view' THEN 1 ELSE 0 END) as view_count,
        SUM(total_amount) as total_amount_receipted
    FROM receipts
    WHERE generated_at BETWEEN ? AND ?
");
$statsStmt->execute([$filterDateFrom . ' 00:00:00', $filterDateTo . ' 23:59:59']);
$stats = $statsStmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipts History - AiNi Hotel</title>
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
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            font-size: 0.9em;
        }
        
        .filters {
            padding: 30px;
            background: #fff;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .filters form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .table-container {
            padding: 30px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-pdf {
            background: #dc3545;
            color: white;
        }
        
        .badge-print {
            background: #28a745;
            color: white;
        }
        
        .badge-email {
            background: #17a2b8;
            color: white;
        }
        
        .badge-view {
            background: #6c757d;
            color: white;
        }
        
        .badge-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-partial {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .actions a {
            color: #667eea;
            text-decoration: none;
            margin-right: 10px;
        }
        
        .actions a:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧾 Receipts History</h1>
            <p>Audit trail of all generated receipts</p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo number_format($stats['total_receipts']); ?></h3>
                <p>📊 Total Receipts</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($stats['pdf_count']); ?></h3>
                <p>📄 PDFs Generated</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($stats['print_count']); ?></h3>
                <p>🖨️ Printed</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($stats['email_count']); ?></h3>
                <p>📧 Emailed</p>
            </div>
            <div class="stat-card">
                <h3>$<?php echo number_format($stats['total_amount_receipted'], 2); ?></h3>
                <p>💰 Total Amount</p>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="form-group">
                    <label>📅 Date From:</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($filterDateFrom); ?>">
                </div>
                
                <div class="form-group">
                    <label>📅 Date To:</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($filterDateTo); ?>">
                </div>
                
                <div class="form-group">
                    <label>🆔 Booking ID:</label>
                    <input type="number" name="booking_id" value="<?php echo htmlspecialchars($filterBooking); ?>" placeholder="Optional">
                </div>
                
                <div class="form-group">
                    <label>📋 Type:</label>
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="pdf" <?php echo $filterType === 'pdf' ? 'selected' : ''; ?>>PDF</option>
                        <option value="print" <?php echo $filterType === 'print' ? 'selected' : ''; ?>>Print</option>
                        <option value="email" <?php echo $filterType === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="view" <?php echo $filterType === 'view' ? 'selected' : ''; ?>>View</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                </div>
                
                <div class="form-group">
                    <a href="receipts_history.php" class="btn btn-secondary">🔄 Reset</a>
                </div>
            </form>
        </div>
        
        <!-- Table -->
        <div class="table-container">
            <?php if (count($receipts) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Booking Ref</th>
                            <th>Guest</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                            <th>Generated By</th>
                            <th>Generated At</th>
                            <th>Email Info</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receipts as $receipt): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($receipt['receipt_number']); ?></strong></td>
                            <td>
                                <a href="calendar_view.php#booking-<?php echo $receipt['booking_id']; ?>">
                                    <?php echo htmlspecialchars($receipt['booking_reference']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($receipt['guest_name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $receipt['receipt_type']; ?>">
                                    <?php 
                                    $icons = ['pdf' => '📄', 'print' => '🖨️', 'email' => '📧', 'view' => '👁️'];
                                    echo $icons[$receipt['receipt_type']] . ' ' . strtoupper($receipt['receipt_type']); 
                                    ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($receipt['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $receipt['payment_status']; ?>">
                                    <?php echo ucfirst($receipt['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($receipt['first_name'] && $receipt['last_name']) {
                                    echo htmlspecialchars($receipt['first_name'] . ' ' . $receipt['last_name']);
                                } else {
                                    echo '<em>System</em>';
                                }
                                ?>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($receipt['generated_at'])); ?></td>
                            <td>
                                <?php if ($receipt['email_sent_to']): ?>
                                    ✅ <?php echo htmlspecialchars($receipt['email_sent_to']); ?><br>
                                    <small style="color: #999;">
                                        <?php echo date('M j, Y g:i A', strtotime($receipt['email_sent_at'])); ?>
                                    </small>
                                <?php else: ?>
                                    <em style="color: #ccc;">N/A</em>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="receipt_handler.php?booking_id=<?php echo $receipt['booking_id']; ?>" target="_blank">👁️ View</a>
                                <a href="receipt_handler.php?booking_id=<?php echo $receipt['booking_id']; ?>&action=pdf" target="_blank">📄 PDF</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3>No receipts found</h3>
                    <p>Try adjusting your filters or select a different date range</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
