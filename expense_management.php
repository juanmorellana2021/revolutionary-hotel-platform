<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/accounting_classes.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$userManager = new UserManager();
if (!$userManager->isManager($_SESSION['user']['id'])) {
    header('Location: dashboard.php');
    exit;
}

$expenseManager = new ExpenseManager();
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_expense'])) {
        $result = $expenseManager->addExpense([
            'expense_category' => $_POST['expense_category'],
            'description' => $_POST['description'],
            'amount' => (float)$_POST['amount'],
            'currency' => $_POST['currency'] ?? 'PEN',
            'payment_method' => $_POST['payment_method'],
            'vendor_name' => $_POST['vendor_name'],
            'vendor_contact' => $_POST['vendor_contact'],
            'expense_date' => $_POST['expense_date'],
            'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
            'recurring_frequency' => $_POST['recurring_frequency'] ?? null,
            'tax_deductible' => isset($_POST['tax_deductible']) ? 1 : 0,
            'paid_by' => $_SESSION['user']['id'],
            'notes' => $_POST['notes'] ?? ''
        ]);
        
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['update_expense'])) {
        $result = $expenseManager->updateExpense((int)$_POST['expense_id'], [
            'expense_category' => $_POST['expense_category'],
            'description' => $_POST['description'],
            'amount' => (float)$_POST['amount'],
            'currency' => $_POST['currency'] ?? 'PEN',
            'payment_method' => $_POST['payment_method'],
            'vendor_name' => $_POST['vendor_name'],
            'vendor_contact' => $_POST['vendor_contact'],
            'expense_date' => $_POST['expense_date'],
            'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
            'recurring_frequency' => $_POST['recurring_frequency'] ?? null,
            'tax_deductible' => isset($_POST['tax_deductible']) ? 1 : 0,
            'notes' => $_POST['notes'] ?? ''
        ]);
        
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['delete_expense'])) {
        $result = $expenseManager->deleteExpense((int)$_POST['expense_id']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

// Get filters
$filters = [];
if (!empty($_GET['expense_category'])) {
    $filters['expense_category'] = $_GET['expense_category'];
}
if (!empty($_GET['payment_method'])) {
    $filters['payment_method'] = $_GET['payment_method'];
}
if (!empty($_GET['start_date'])) {
    $filters['start_date'] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
    $filters['end_date'] = $_GET['end_date'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (isset($_GET['is_recurring']) && $_GET['is_recurring'] !== '') {
    $filters['is_recurring'] = (int)$_GET['is_recurring'];
}
if (isset($_GET['tax_deductible']) && $_GET['tax_deductible'] !== '') {
    $filters['tax_deductible'] = (int)$_GET['tax_deductible'];
}

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$filters['limit'] = $perPage;
$filters['offset'] = ($page - 1) * $perPage;

// Get expense records
$expenseRecords = $expenseManager->getExpenses($filters);
$totalFilters = $filters;
unset($totalFilters['limit'], $totalFilters['offset']);
$totalExpenses = $expenseManager->getExpenses($totalFilters);
$totalPages = ceil(count($totalExpenses) / $perPage);

// Calculate totals
$totalAmount = array_sum(array_column($totalExpenses, 'amount'));

// Get category summary with same filters as the main query
$categorySummaryFilters = [];
if (!empty($_GET['start_date'])) {
    $categorySummaryFilters['start_date'] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
    $categorySummaryFilters['end_date'] = $_GET['end_date'];
}
$categorySummary = $expenseManager->getCategorySummary($categorySummaryFilters);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
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
        }

        .navbar {
            background: rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .total-display {
            background: #dc3545;
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 15px;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .category-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .category-amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
        }

        .category-name {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group textarea {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .table-container {
            overflow-x: auto;
        }

        .expense-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .expense-table th,
        .expense-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .expense-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .expense-table tbody tr:hover {
            background: #f8f9fa;
        }

        .amount {
            font-weight: bold;
            color: #dc3545;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-recurring {
            background: #ffc107;
            color: #212529;
        }

        .badge-deductible {
            background: #28a745;
            color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            text-decoration: none;
            color: #495057;
        }

        .pagination a:hover {
            background: #f8f9fa;
        }

        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 2% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .filters {
                grid-template-columns: 1fr;
            }
            
            .category-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-container">
            <div class="logo">💸 Expense Management</div>
            <div class="nav-links">
                <a href="manager_dashboard.php">Dashboard</a>
                <a href="room_management.php">Rooms</a>
                <a href="calendar_view.php">Calendar</a>
                <a href="accounting_dashboard.php">Accounting</a>
                <a href="income_management.php">Income</a>
                <a href="expense_management.php" class="active">Expenses</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>💸 Expense Management</h1>
            <p>Track and manage all hotel expenses and operational costs</p>
            <div class="total-display">
                Total Expenses: $<?php echo number_format($totalAmount, 2); ?> 
                (<?php echo count($totalExpenses); ?> entries)
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Category Summary -->
        <div class="section">
            <h2>📊 Expense Categories Summary</h2>
            <div class="category-grid">
                <?php foreach ($categorySummary as $category): ?>
                    <div class="category-card">
                        <div class="category-amount">$<?php echo number_format($category['total'], 2); ?></div>
                        <div class="category-name"><?php echo ucfirst(str_replace('_', ' ', $category['expense_category'])); ?></div>
                        <small><?php echo $category['count']; ?> entries</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="section">
            <h2>🔍 Filter Expense Records</h2>
            <form method="GET">
                <div class="filters">
                    <div class="form-group">
                        <label for="expense_category">Expense Category</label>
                        <select id="expense_category" name="expense_category">
                            <option value="">All Categories</option>
                            <option value="utilities" <?php echo ($_GET['expense_category'] ?? '') === 'utilities' ? 'selected' : ''; ?>>Utilities</option>
                            <option value="rent" <?php echo ($_GET['expense_category'] ?? '') === 'rent' ? 'selected' : ''; ?>>Rent</option>
                            <option value="maintenance" <?php echo ($_GET['expense_category'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="supplies" <?php echo ($_GET['expense_category'] ?? '') === 'supplies' ? 'selected' : ''; ?>>Supplies</option>
                            <option value="food_beverage" <?php echo ($_GET['expense_category'] ?? '') === 'food_beverage' ? 'selected' : ''; ?>>Food & Beverage</option>
                            <option value="staff_salary" <?php echo ($_GET['expense_category'] ?? '') === 'staff_salary' ? 'selected' : ''; ?>>Staff Salary</option>
                            <option value="marketing" <?php echo ($_GET['expense_category'] ?? '') === 'marketing' ? 'selected' : ''; ?>>Marketing</option>
                            <option value="insurance" <?php echo ($_GET['expense_category'] ?? '') === 'insurance' ? 'selected' : ''; ?>>Insurance</option>
                            <option value="taxes" <?php echo ($_GET['expense_category'] ?? '') === 'taxes' ? 'selected' : ''; ?>>Taxes</option>
                            <option value="cleaning" <?php echo ($_GET['expense_category'] ?? '') === 'cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                            <option value="laundry" <?php echo ($_GET['expense_category'] ?? '') === 'laundry' ? 'selected' : ''; ?>>Laundry</option>
                            <option value="internet" <?php echo ($_GET['expense_category'] ?? '') === 'internet' ? 'selected' : ''; ?>>Internet</option>
                            <option value="telephone" <?php echo ($_GET['expense_category'] ?? '') === 'telephone' ? 'selected' : ''; ?>>Telephone</option>
                            <option value="repairs" <?php echo ($_GET['expense_category'] ?? '') === 'repairs' ? 'selected' : ''; ?>>Repairs</option>
                            <option value="equipment" <?php echo ($_GET['expense_category'] ?? '') === 'equipment' ? 'selected' : ''; ?>>Equipment</option>
                            <option value="office_supplies" <?php echo ($_GET['expense_category'] ?? '') === 'office_supplies' ? 'selected' : ''; ?>>Office Supplies</option>
                            <option value="transportation" <?php echo ($_GET['expense_category'] ?? '') === 'transportation' ? 'selected' : ''; ?>>Transportation</option>
                            <option value="other" <?php echo ($_GET['expense_category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method">
                            <option value="">All Methods</option>
                            <option value="cash" <?php echo ($_GET['payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="credit_card" <?php echo ($_GET['payment_method'] ?? '') === 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                            <option value="debit_card" <?php echo ($_GET['payment_method'] ?? '') === 'debit_card' ? 'selected' : ''; ?>>Debit Card</option>
                            <option value="bank_transfer" <?php echo ($_GET['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="check" <?php echo ($_GET['payment_method'] ?? '') === 'check' ? 'selected' : ''; ?>>Check</option>
                            <option value="other" <?php echo ($_GET['payment_method'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $_GET['start_date'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $_GET['end_date'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Description, vendor..." value="<?php echo $_GET['search'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="is_recurring">Recurring</label>
                        <select id="is_recurring" name="is_recurring">
                            <option value="">All</option>
                            <option value="1" <?php echo ($_GET['is_recurring'] ?? '') === '1' ? 'selected' : ''; ?>>Recurring Only</option>
                            <option value="0" <?php echo ($_GET['is_recurring'] ?? '') === '0' ? 'selected' : ''; ?>>One-time Only</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tax_deductible">Tax Deductible</label>
                        <select id="tax_deductible" name="tax_deductible">
                            <option value="">All</option>
                            <option value="1" <?php echo ($_GET['tax_deductible'] ?? '') === '1' ? 'selected' : ''; ?>>Deductible Only</option>
                            <option value="0" <?php echo ($_GET['tax_deductible'] ?? '') === '0' ? 'selected' : ''; ?>>Non-deductible</option>
                        </select>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">🔍 Apply Filters</button>
                    <a href="expense_management.php" class="btn btn-warning">🔄 Clear Filters</a>
                    <button type="button" class="btn btn-danger" onclick="openAddModal()">➕ Add Expense</button>
                </div>
            </form>
        </div>

        <!-- Expense Records Table -->
        <div class="section">
            <div class="table-container">
                <table class="expense-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Vendor</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenseRecords)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                    No expense records found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenseRecords as $expense): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $expense['expense_category'])); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td>
                                        <?php if (isset($expense['vendor_name']) && !empty($expense['vendor_name'])): ?>
                                            <strong><?php echo htmlspecialchars($expense['vendor_name']); ?></strong>
                                            <?php if (isset($expense['vendor_contact']) && !empty($expense['vendor_contact'])): ?>
                                                <br><small><?php echo htmlspecialchars($expense['vendor_contact']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <em>No vendor</em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="amount">
                                        <?php 
                                        $currency = $expense['currency'] ?? 'PEN';
                                        $symbol = $currency === 'PEN' ? 'S/.' : '$';
                                        echo $symbol . ' ' . number_format($expense['amount'], 2);
                                        
                                        // Show conversion if different from primary currency
                                        if ($currency === 'USD') {
                                            $penAmount = $expense['amount'] * 3.50;
                                            echo '<br><small class="text-muted">≈ S/. ' . number_format($penAmount, 2) . '</small>';
                                        } else if ($currency === 'PEN') {
                                            $usdAmount = $expense['amount'] / 3.50;
                                            echo '<br><small class="text-muted">≈ $' . number_format($usdAmount, 2) . '</small>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $expense['payment_method'])); ?></td>
                                    <td>
                                        <?php if (isset($expense['is_recurring']) && $expense['is_recurring']): ?>
                                            <span class="badge badge-recurring">Recurring</span>
                                        <?php endif; ?>
                                        <?php if (isset($expense['tax_deductible']) && $expense['tax_deductible']): ?>
                                            <span class="badge badge-deductible">Tax Deductible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="editExpense(<?php echo htmlspecialchars(json_encode($expense)); ?>)" class="btn btn-primary btn-sm">✏️ Edit</button>
                                        <button onclick="deleteExpense(<?php echo $expense['id']; ?>)" class="btn btn-danger btn-sm">🗑️ Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">« Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Expense Modal -->
    <div id="expenseModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Expense</h2>
            <form id="expenseForm" method="POST">
                <input type="hidden" id="expense_id" name="expense_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_expense_category">Expense Category *</label>
                        <select id="modal_expense_category" name="expense_category" required>
                            <option value="utilities">Utilities</option>
                            <option value="rent">Rent</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="supplies">Supplies</option>
                            <option value="food_beverage">Food & Beverage</option>
                            <option value="staff_salary">Staff Salary</option>
                            <option value="marketing">Marketing</option>
                            <option value="insurance">Insurance</option>
                            <option value="taxes">Taxes</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="laundry">Laundry</option>
                            <option value="internet">Internet</option>
                            <option value="telephone">Telephone</option>
                            <option value="repairs">Repairs</option>
                            <option value="equipment">Equipment</option>
                            <option value="office_supplies">Office Supplies</option>
                            <option value="transportation">Transportation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modal_payment_method">Payment Method *</label>
                        <select id="modal_payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="modal_description">Description *</label>
                    <input type="text" id="modal_description" name="description" required>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="modal_amount">Amount *</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="number" id="modal_amount" name="amount" step="0.01" min="0" required style="flex: 1;" placeholder="Enter amount">
                            <select id="modal_currency" name="currency" style="width: 80px;">
                                <option value="PEN">PEN</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                        <div id="currency_conversion" style="margin-top: 5px; font-size: 0.85rem; color: #666;"></div>
                    </div>
                    <div class="form-group">
                        <label for="modal_expense_date">Expense Date *</label>
                        <input type="date" id="modal_expense_date" name="expense_date" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_vendor_name">Vendor Name</label>
                        <input type="text" id="modal_vendor_name" name="vendor_name">
                    </div>
                    <div class="form-group">
                        <label for="modal_vendor_contact">Vendor Contact</label>
                        <input type="text" id="modal_vendor_contact" name="vendor_contact" placeholder="Phone or email">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_recurring_frequency">Recurring Frequency</label>
                        <select id="modal_recurring_frequency" name="recurring_frequency">
                            <option value="">Not Recurring</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="modal_is_recurring" name="is_recurring">
                            <label for="modal_is_recurring">This is a recurring expense</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="modal_tax_deductible" name="tax_deductible">
                            <label for="modal_tax_deductible">Tax deductible</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="modal_notes">Notes</label>
                    <textarea id="modal_notes" name="notes" rows="3"></textarea>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" id="submitBtn" name="add_expense" class="btn btn-danger">💸 Add Expense</button>
                    <button type="button" onclick="closeModal()" class="btn btn-warning">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Expense';
            document.getElementById('expenseForm').reset();
            document.getElementById('expense_id').value = '';
            document.getElementById('modal_expense_date').value = new Date().toISOString().split('T')[0];
            document.getElementById('submitBtn').textContent = '💸 Add Expense';
            document.getElementById('submitBtn').name = 'add_expense';
            document.getElementById('expenseModal').style.display = 'block';
        }

        function editExpense(expense) {
            document.getElementById('modalTitle').textContent = 'Edit Expense';
            document.getElementById('expense_id').value = expense.id;
            document.getElementById('modal_expense_category').value = expense.expense_category;
            document.getElementById('modal_description').value = expense.description;
            document.getElementById('modal_amount').value = expense.amount;
            document.getElementById('modal_currency').value = expense.currency || 'PEN';
            document.getElementById('modal_payment_method').value = expense.payment_method;
            document.getElementById('modal_expense_date').value = expense.expense_date;
            document.getElementById('modal_vendor_name').value = expense.vendor_name || '';
            document.getElementById('modal_vendor_contact').value = expense.vendor_contact || '';
            document.getElementById('modal_recurring_frequency').value = expense.recurring_frequency || '';
            document.getElementById('modal_is_recurring').checked = expense.is_recurring == 1;
            document.getElementById('modal_tax_deductible').checked = expense.tax_deductible == 1;
            document.getElementById('modal_notes').value = expense.notes || '';
            document.getElementById('submitBtn').textContent = '✏️ Update Expense';
            document.getElementById('submitBtn').name = 'update_expense';
            document.getElementById('expenseModal').style.display = 'block';
            
            // Update currency conversion display
            setTimeout(updateCurrencyConversion, 100);
        }

        function deleteExpense(expenseId) {
            if (confirm('Are you sure you want to delete this expense record? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="expense_id" value="${expenseId}">
                    <input type="hidden" name="delete_expense" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal() {
            document.getElementById('expenseModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('expenseModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Handle recurring checkbox
        document.getElementById('modal_is_recurring').addEventListener('change', function() {
            const frequencySelect = document.getElementById('modal_recurring_frequency');
            if (this.checked) {
                frequencySelect.required = true;
                if (!frequencySelect.value) {
                    frequencySelect.value = 'monthly';
                }
            } else {
                frequencySelect.required = false;
                frequencySelect.value = '';
            }
        });

        // Handle currency conversion display
        function updateCurrencyConversion() {
            const amountInput = document.getElementById('modal_amount');
            const currencySelect = document.getElementById('modal_currency');
            const conversionDisplay = document.getElementById('currency_conversion');
            
            const amount = parseFloat(amountInput.value) || 0;
            const currency = currencySelect.value;
            const exchangeRate = 3.50; // PEN to USD rate
            
            if (amount > 0) {
                if (currency === 'PEN') {
                    const usdAmount = (amount / exchangeRate).toFixed(2);
                    conversionDisplay.innerHTML = `<small class="text-muted">≈ $${usdAmount} USD</small>`;
                } else {
                    const penAmount = (amount * exchangeRate).toFixed(2);
                    conversionDisplay.innerHTML = `<small class="text-muted">≈ S/. ${penAmount} PEN</small>`;
                }
                conversionDisplay.style.display = 'block';
            } else {
                conversionDisplay.style.display = 'none';
            }
        }

        // Add event listeners for currency conversion
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('modal_amount');
            const currencySelect = document.getElementById('modal_currency');
            
            if (amountInput && currencySelect) {
                amountInput.addEventListener('input', updateCurrencyConversion);
                currencySelect.addEventListener('change', updateCurrencyConversion);
            }
        });
    </script>
</body>
</html>