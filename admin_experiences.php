<?php
session_start();
require_once 'db_connection_pdo.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'] ?? 'Admin';

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'delete') {
            $exp_id = filter_input(INPUT_POST, 'experience_id', FILTER_VALIDATE_INT);
            
            // Check for existing bookings
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM aini_experience_bookings WHERE experience_id = ?");
            $stmt->execute([$exp_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete experience with existing bookings');
            }
            
            // Delete reviews first
            $stmt = $pdo->prepare("DELETE FROM aini_experience_reviews WHERE experience_id = ?");
            $stmt->execute([$exp_id]);
            
            // Delete experience
            $stmt = $pdo->prepare("DELETE FROM aini_experiences WHERE id = ?");
            $stmt->execute([$exp_id]);
            
            echo json_encode(['success' => true, 'message' => 'Experience deleted']);
            exit;
        }
        
        if ($_POST['action'] === 'toggle_status') {
            $exp_id = filter_input(INPUT_POST, 'experience_id', FILTER_VALIDATE_INT);
            $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if (!in_array($new_status, ['pending_review', 'approved', 'rejected', 'inactive'])) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $pdo->prepare("UPDATE aini_experiences SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $exp_id]);
            
            echo json_encode(['success' => true, 'message' => 'Status updated']);
            exit;
        }
        
        if ($_POST['action'] === 'bulk_delete') {
            $ids = json_decode($_POST['ids'], true);
            
            if (empty($ids) || !is_array($ids)) {
                throw new Exception('No experiences selected');
            }
            
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            // Check for bookings
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM aini_experience_bookings WHERE experience_id IN ($placeholders)");
            $stmt->execute($ids);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete experiences with existing bookings');
            }
            
            // Delete reviews
            $stmt = $pdo->prepare("DELETE FROM aini_experience_reviews WHERE experience_id IN ($placeholders)");
            $stmt->execute($ids);
            
            // Delete experiences
            $stmt = $pdo->prepare("DELETE FROM aini_experiences WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            
            echo json_encode(['success' => true, 'message' => count($ids) . ' experiences deleted']);
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Pagination
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$category_filter = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$partner_filter = filter_input(INPUT_GET, 'partner', FILTER_VALIDATE_INT) ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(e.name LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where[] = "e.status = ?";
    $params[] = $status_filter;
}

if ($category_filter) {
    $where[] = "e.category = ?";
    $params[] = $category_filter;
}

if ($partner_filter) {
    $where[] = "e.partner_id = ?";
    $params[] = $partner_filter;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
try {
    $count_sql = "SELECT COUNT(*) FROM aini_experiences e $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_experiences = $count_stmt->fetchColumn();
    $total_pages = ceil($total_experiences / $per_page);
} catch (Exception $e) {
    $total_experiences = 0;
    $total_pages = 0;
}

// Get experiences
try {
    $sql = "SELECT e.*, 
            p.business_name as partner_name,
            COUNT(DISTINCT b.id) as total_bookings,
            AVG(r.rating) as avg_rating,
            COUNT(DISTINCT r.id) as review_count
            FROM aini_experiences e
            LEFT JOIN aini_experience_partners p ON e.partner_id = p.id
            LEFT JOIN aini_experience_bookings b ON e.id = b.experience_id
            LEFT JOIN aini_experience_reviews r ON e.id = r.experience_id AND r.status = 'approved'
            $where_clause
            GROUP BY e.id
            ORDER BY e.created_at DESC 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $experiences = [];
}

// Get categories and partners for filters
try {
    $categories_stmt = $pdo->query("SELECT DISTINCT category FROM aini_experiences WHERE category IS NOT NULL ORDER BY category");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = [];
}

try {
    $partners_stmt = $pdo->query("SELECT id, business_name FROM aini_experience_partners ORDER BY business_name");
    $partners = $partners_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $partners = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experience Management - AiniTravel Admin</title>
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
            max-width: 1800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .header .back-btn, .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
            display: inline-block;
            cursor: pointer;
        }

        .header .back-btn:hover, .btn-primary:hover {
            transform: translateY(-2px);
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .filters input,
        .filters select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            width: 100%;
        }

        .filters button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: opacity 0.2s;
        }

        .filters button:hover {
            opacity: 0.9;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card.pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card.approved {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.inactive {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .stat-card p {
            opacity: 0.9;
            font-size: 14px;
        }

        .bulk-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
            align-items: center;
            display: none;
        }

        .bulk-actions.active {
            display: flex;
        }

        .bulk-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: opacity 0.2s;
        }

        .bulk-actions .btn-delete-bulk {
            background: #f44336;
            color: white;
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #f8f9fa;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            white-space: nowrap;
            font-size: 13px;
        }

        td {
            padding: 15px 10px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
            font-size: 13px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        .badge.pending_review {
            background: #fff3cd;
            color: #856404;
        }

        .badge.approved {
            background: #d4edda;
            color: #155724;
        }

        .badge.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .badge.inactive {
            background: #d6d8d9;
            color: #383d41;
        }

        .experience-info {
            display: flex;
            gap: 15px;
            align-items: start;
        }

        .experience-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .experience-details {
            flex: 1;
        }

        .experience-details strong {
            font-size: 14px;
            color: #333;
            display: block;
            margin-bottom: 5px;
        }

        .experience-details small {
            color: #666;
            font-size: 12px;
            display: block;
            margin-bottom: 3px;
        }

        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: opacity 0.2s;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .btn-edit {
            background: #2196f3;
            color: white;
        }

        .btn-view {
            background: #00bcd4;
            color: white;
        }

        .btn-activate {
            background: #4caf50;
            color: white;
        }

        .btn-deactivate {
            background: #ff9800;
            color: white;
        }

        .btn-delete {
            background: #f44336;
            color: white;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination .active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .alert.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .table-container {
                font-size: 12px;
            }

            th, td {
                padding: 8px;
            }

            .experience-info {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Experience Management</h1>
            <div class="header-actions">
                <a href="experience_register.php" class="btn-primary">➕ Add New Experience</a>
                <a href="admin_dashboard.php" class="back-btn">← Dashboard</a>
            </div>
        </div>

        <div id="alert" class="alert"></div>

        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $total_experiences; ?></h3>
                <p>Total Experiences</p>
            </div>
            <div class="stat-card pending">
                <h3><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM aini_experiences WHERE status = 'pending_review'");
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) {
                        echo 0;
                    }
                ?></h3>
                <p>Pending Review</p>
            </div>
            <div class="stat-card approved">
                <h3><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM aini_experiences WHERE status = 'approved'");
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) {
                        echo 0;
                    }
                ?></h3>
                <p>Active</p>
            </div>
            <div class="stat-card inactive">
                <h3><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM aini_experiences WHERE status = 'inactive'");
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) {
                        echo 0;
                    }
                ?></h3>
                <p>Inactive</p>
            </div>
        </div>

        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Search experiences..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="status">
                <option value="">All Status</option>
                <option value="pending_review" <?php echo $status_filter === 'pending_review' ? 'selected' : ''; ?>>Pending Review</option>
                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                            <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="partner">
                <option value="">All Partners</option>
                <?php foreach ($partners as $partner): ?>
                    <option value="<?php echo $partner['id']; ?>" 
                            <?php echo $partner_filter == $partner['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($partner['business_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">🔍 Filter</button>
        </form>

        <div id="bulkActions" class="bulk-actions">
            <span><strong id="selectedCount">0</strong> selected</span>
            <button class="btn-delete-bulk" onclick="bulkDelete()">🗑️ Delete Selected</button>
            <button onclick="clearSelection()" style="background: #757575; color: white;">✕ Clear</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th>Experience</th>
                        <th>Category</th>
                        <th>Partner</th>
                        <th>Price</th>
                        <th>Rating</th>
                        <th>Bookings</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($experiences)): ?>
                        <tr>
                            <td colspan="10" class="empty-state">
                                <h3>No experiences found</h3>
                                <p>Try adjusting your filters or create a new experience.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($experiences as $exp): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="exp-checkbox" value="<?php echo $exp['id']; ?>" onchange="updateBulkActions()">
                                </td>
                                <td>
                                    <div class="experience-info">
                                        <?php 
                                        $images = !empty($exp['images']) ? json_decode($exp['images'], true) : [];
                                        $firstImage = !empty($images) ? $images[0] : 'https://via.placeholder.com/80';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($firstImage); ?>" 
                                             alt="<?php echo htmlspecialchars($exp['name']); ?>" 
                                             class="experience-image"
                                             onerror="this.src='https://via.placeholder.com/80'">
                                        <div class="experience-details">
                                            <strong><?php echo htmlspecialchars($exp['name']); ?></strong>
                                            <small>📍 <?php echo htmlspecialchars($exp['location']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($exp['category']); ?></td>
                                <td><?php echo htmlspecialchars($exp['partner_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <strong>$<?php echo number_format($exp['price_usd'], 2); ?></strong><br>
                                    <small style="color: #999;"><?php echo $exp['price_aini_rewards'] ?? 0; ?> 🪙</small>
                                </td>
                                <td>
                                    <?php if ($exp['review_count'] > 0): ?>
                                        <div class="rating-display">
                                            <span style="color: #ffc107;">⭐</span>
                                            <strong><?php echo number_format($exp['avg_rating'], 1); ?></strong>
                                            <small style="color: #999;">(<?php echo $exp['review_count']; ?>)</small>
                                        </div>
                                    <?php else: ?>
                                        <small style="color: #999;">No reviews</small>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $exp['total_bookings']; ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $exp['status']; ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($exp['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($exp['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="experience_details.php?id=<?php echo $exp['id']; ?>" class="btn btn-view" target="_blank">
                                            👁️ View
                                        </a>
                                        
                                        <?php if ($exp['status'] === 'approved'): ?>
                                            <button class="btn btn-deactivate" onclick="toggleStatus(<?php echo $exp['id']; ?>, 'inactive')">
                                                🔒 Deactivate
                                            </button>
                                        <?php elseif ($exp['status'] === 'inactive'): ?>
                                            <button class="btn btn-activate" onclick="toggleStatus(<?php echo $exp['id']; ?>, 'approved')">
                                                ✅ Activate
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($exp['total_bookings'] == 0): ?>
                                            <button class="btn btn-delete" onclick="deleteExperience(<?php echo $exp['id']; ?>, '<?php echo htmlspecialchars($exp['name']); ?>')">
                                                🗑️ Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&partner=<?php echo urlencode($partner_filter); ?>">
                        ← Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= min(10, $total_pages); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&partner=<?php echo urlencode($partner_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&partner=<?php echo urlencode($partner_filter); ?>">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showAlert(message, type = 'success') {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = `alert ${type}`;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.exp-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkActions();
        }

        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.exp-checkbox:checked');
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            
            selectedCount.textContent = checkboxes.length;
            
            if (checkboxes.length > 0) {
                bulkActions.classList.add('active');
            } else {
                bulkActions.classList.remove('active');
            }
        }

        function clearSelection() {
            document.getElementById('selectAll').checked = false;
            document.querySelectorAll('.exp-checkbox').forEach(cb => cb.checked = false);
            updateBulkActions();
        }

        async function toggleStatus(expId, newStatus) {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('experience_id', expId);
            formData.append('status', newStatus);
            
            try {
                const response = await fetch('admin_experiences.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        }

        async function deleteExperience(expId, expName) {
            if (!confirm(`Are you sure you want to DELETE "${expName}"? This will also delete all reviews. This action cannot be undone!`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('experience_id', expId);
            
            try {
                const response = await fetch('admin_experiences.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        }

        async function bulkDelete() {
            const checkboxes = document.querySelectorAll('.exp-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            
            if (ids.length === 0) {
                showAlert('No experiences selected', 'error');
                return;
            }
            
            if (!confirm(`Are you sure you want to DELETE ${ids.length} experience(s)? This will also delete all related reviews. This action cannot be undone!`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'bulk_delete');
            formData.append('ids', JSON.stringify(ids));
            
            try {
                const response = await fetch('admin_experiences.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        }
    </script>
</body>
</html>
