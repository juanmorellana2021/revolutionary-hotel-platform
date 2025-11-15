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
        if ($_POST['action'] === 'update_status') {
            $review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
            $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if (!in_array($new_status, ['pending', 'approved', 'rejected', 'flagged'])) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $pdo->prepare("UPDATE aini_experience_reviews SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $admin_id, $review_id]);
            
            echo json_encode(['success' => true, 'message' => 'Review status updated']);
            exit;
        }
        
        if ($_POST['action'] === 'delete_review') {
            $review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
            
            $stmt = $pdo->prepare("DELETE FROM aini_experience_reviews WHERE id = ?");
            $stmt->execute([$review_id]);
            
            echo json_encode(['success' => true, 'message' => 'Review deleted']);
            exit;
        }
        
        if ($_POST['action'] === 'add_response') {
            $review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
            $response = filter_input(INPUT_POST, 'response', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            $stmt = $pdo->prepare("UPDATE aini_experience_reviews SET admin_response = ?, response_by = ?, response_at = NOW() WHERE id = ?");
            $stmt->execute([$response, $admin_id, $review_id]);
            
            echo json_encode(['success' => true, 'message' => 'Response added']);
            exit;
        }
        
        if ($_POST['action'] === 'mark_helpful') {
            $review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
            
            $stmt = $pdo->prepare("UPDATE aini_experience_reviews SET helpful_count = helpful_count + 1 WHERE id = ?");
            $stmt->execute([$review_id]);
            
            echo json_encode(['success' => true, 'message' => 'Marked as helpful']);
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
$rating_filter = filter_input(INPUT_GET, 'rating', FILTER_VALIDATE_INT) ?? '';
$experience_filter = filter_input(INPUT_GET, 'experience', FILTER_VALIDATE_INT) ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(r.review_text LIKE ? OR u.name LIKE ? OR e.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where[] = "r.status = ?";
    $params[] = $status_filter;
}

if ($rating_filter) {
    $where[] = "r.rating = ?";
    $params[] = $rating_filter;
}

if ($experience_filter) {
    $where[] = "r.experience_id = ?";
    $params[] = $experience_filter;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
try {
    $count_sql = "SELECT COUNT(*) FROM aini_experience_reviews r
                  JOIN aini_experiences e ON r.experience_id = e.id
                  JOIN ainitravel_users u ON r.user_id = u.id
                  $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_reviews = $count_stmt->fetchColumn();
    $total_pages = ceil($total_reviews / $per_page);
} catch (Exception $e) {
    $total_reviews = 0;
    $total_pages = 0;
}

// Get reviews
try {
    $sql = "SELECT r.*, 
            e.name as experience_name,
            e.category,
            u.name as reviewer_name,
            u.email as reviewer_email,
            admin.name as admin_name
            FROM aini_experience_reviews r
            JOIN aini_experiences e ON r.experience_id = e.id
            JOIN ainitravel_users u ON r.user_id = u.id
            LEFT JOIN ainitravel_users admin ON r.reviewed_by = admin.id
            $where_clause
            ORDER BY r.created_at DESC 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reviews = [];
}

// Get experiences for filter
try {
    $experiences_stmt = $pdo->query("SELECT id, name FROM aini_experiences ORDER BY name");
    $experiences = $experiences_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $experiences = [];
}

// Calculate average rating
try {
    $avg_rating_stmt = $pdo->query("SELECT AVG(rating) FROM aini_experience_reviews WHERE status = 'approved'");
    $avg_rating = round($avg_rating_stmt->fetchColumn(), 1);
} catch (Exception $e) {
    $avg_rating = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Moderation - AiniTravel Admin</title>
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
            max-width: 1600px;
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

        .header .back-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
            display: inline-block;
        }

        .header .back-btn:hover {
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

        .stat-card.rating {
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

        .reviews-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 20px;
        }

        .review-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: box-shadow 0.2s;
        }

        .review-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .reviewer-info {
            flex: 1;
        }

        .reviewer-info h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .reviewer-info small {
            color: #666;
            font-size: 13px;
        }

        .rating-stars {
            display: flex;
            gap: 3px;
            font-size: 18px;
            color: #ffc107;
        }

        .review-meta {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
        }

        .experience-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .review-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            line-height: 1.6;
            color: #333;
        }

        .review-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge.pending {
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

        .badge.flagged {
            background: #f8d7da;
            color: #721c24;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: opacity 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .btn-approve {
            background: #4caf50;
            color: white;
        }

        .btn-reject {
            background: #f44336;
            color: white;
        }

        .btn-flag {
            background: #ff9800;
            color: white;
        }

        .btn-respond {
            background: #2196f3;
            color: white;
        }

        .btn-delete {
            background: #9e9e9e;
            color: white;
        }

        .admin-response {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 12px 15px;
            margin-top: 15px;
            border-radius: 6px;
        }

        .admin-response strong {
            color: #2e7d32;
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .helpful-count {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 13px;
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

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            min-height: 120px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        .btn-save {
            background: #4caf50;
            color: white;
            padding: 12px 24px;
        }

        .btn-cancel {
            background: #757575;
            color: white;
            padding: 12px 24px;
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

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
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

            .review-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⭐ Review Moderation</h1>
            <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div id="alert" class="alert"></div>

        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $total_reviews; ?></h3>
                <p>Total Reviews</p>
            </div>
            <div class="stat-card pending">
                <h3><?php 
                    $stmt = $pdo->query("SELECT COUNT(*) FROM aini_experience_reviews WHERE status = 'pending'");
                    echo $stmt->fetchColumn();
                ?></h3>
                <p>Pending Review</p>
            </div>
            <div class="stat-card approved">
                <h3><?php 
                    $stmt = $pdo->query("SELECT COUNT(*) FROM aini_experience_reviews WHERE status = 'approved'");
                    echo $stmt->fetchColumn();
                ?></h3>
                <p>Approved</p>
            </div>
            <div class="stat-card rating">
                <h3><?php echo $avg_rating; ?> ⭐</h3>
                <p>Average Rating</p>
            </div>
        </div>

        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Search reviews, reviewer, or experience..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="status">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="flagged" <?php echo $status_filter === 'flagged' ? 'selected' : ''; ?>>Flagged</option>
            </select>
            
            <select name="rating">
                <option value="">All Ratings</option>
                <option value="5" <?php echo $rating_filter === 5 ? 'selected' : ''; ?>>5 Stars</option>
                <option value="4" <?php echo $rating_filter === 4 ? 'selected' : ''; ?>>4 Stars</option>
                <option value="3" <?php echo $rating_filter === 3 ? 'selected' : ''; ?>>3 Stars</option>
                <option value="2" <?php echo $rating_filter === 2 ? 'selected' : ''; ?>>2 Stars</option>
                <option value="1" <?php echo $rating_filter === 1 ? 'selected' : ''; ?>>1 Star</option>
            </select>
            
            <select name="experience">
                <option value="">All Experiences</option>
                <?php foreach ($experiences as $exp): ?>
                    <option value="<?php echo $exp['id']; ?>" 
                            <?php echo $experience_filter == $exp['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($exp['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">🔍 Filter</button>
        </form>

        <div class="reviews-grid">
            <?php if (empty($reviews)): ?>
                <div class="empty-state">
                    <h3>No reviews found</h3>
                    <p>There are no reviews matching your filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <h3><?php echo htmlspecialchars($review['reviewer_name']); ?></h3>
                                <small><?php echo htmlspecialchars($review['reviewer_email']); ?></small>
                            </div>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php echo $i <= $review['rating'] ? '⭐' : '☆'; ?>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="review-meta">
                            <span class="experience-tag"><?php echo htmlspecialchars($review['experience_name']); ?></span>
                            <span class="badge <?php echo $review['status']; ?>">
                                <?php echo ucfirst($review['status']); ?>
                            </span>
                            <small style="color: #999;">
                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                            </small>
                        </div>

                        <div class="review-content">
                            <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                        </div>

                        <?php if ($review['admin_response']): ?>
                            <div class="admin-response">
                                <strong>Admin Response by <?php echo htmlspecialchars($review['admin_name']); ?>:</strong>
                                <?php echo nl2br(htmlspecialchars($review['admin_response'])); ?>
                            </div>
                        <?php endif; ?>

                        <div class="review-footer">
                            <div class="helpful-count">
                                👍 <?php echo $review['helpful_count'] ?? 0; ?> found this helpful
                            </div>
                            
                            <div class="actions">
                                <?php if ($review['status'] !== 'approved'): ?>
                                    <button class="btn btn-approve" onclick="updateStatus(<?php echo $review['id']; ?>, 'approved')">
                                        ✅ Approve
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($review['status'] !== 'rejected'): ?>
                                    <button class="btn btn-reject" onclick="updateStatus(<?php echo $review['id']; ?>, 'rejected')">
                                        ❌ Reject
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($review['status'] !== 'flagged'): ?>
                                    <button class="btn btn-flag" onclick="updateStatus(<?php echo $review['id']; ?>, 'flagged')">
                                        🚩 Flag
                                    </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-respond" onclick="openResponseModal(<?php echo $review['id']; ?>, '<?php echo htmlspecialchars($review['admin_response'] ?? ''); ?>')">
                                    💬 Respond
                                </button>
                                
                                <button class="btn btn-delete" onclick="deleteReview(<?php echo $review['id']; ?>, '<?php echo htmlspecialchars($review['reviewer_name']); ?>')">
                                    🗑️ Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&rating=<?php echo urlencode($rating_filter); ?>&experience=<?php echo urlencode($experience_filter); ?>">
                        ← Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= min(10, $total_pages); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&rating=<?php echo urlencode($rating_filter); ?>&experience=<?php echo urlencode($experience_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&rating=<?php echo urlencode($rating_filter); ?>&experience=<?php echo urlencode($experience_filter); ?>">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Response Modal -->
    <div id="responseModal" class="modal">
        <div class="modal-content">
            <h2>Add/Edit Admin Response</h2>
            <form id="responseForm">
                <input type="hidden" id="review_id">
                
                <div class="form-group">
                    <label>Your Response</label>
                    <textarea id="admin_response" placeholder="Write your response to this review..." required></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-save">Save Response</button>
                </div>
            </form>
        </div>
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

        async function updateStatus(reviewId, newStatus) {
            const actions = {
                approved: 'approve',
                rejected: 'reject',
                flagged: 'flag'
            };
            
            if (!confirm(`Are you sure you want to ${actions[newStatus]} this review?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('review_id', reviewId);
            formData.append('status', newStatus);
            
            try {
                const response = await fetch('admin_reviews.php', {
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

        async function deleteReview(reviewId, reviewerName) {
            if (!confirm(`Are you sure you want to DELETE the review by "${reviewerName}"? This action cannot be undone!`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_review');
            formData.append('review_id', reviewId);
            
            try {
                const response = await fetch('admin_reviews.php', {
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

        function openResponseModal(reviewId, currentResponse) {
            document.getElementById('review_id').value = reviewId;
            document.getElementById('admin_response').value = currentResponse;
            document.getElementById('responseModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('responseModal').classList.remove('active');
        }

        document.getElementById('responseForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'add_response');
            formData.append('review_id', document.getElementById('review_id').value);
            formData.append('response', document.getElementById('admin_response').value);
            
            try {
                const response = await fetch('admin_reviews.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        });

        // Close modal when clicking outside
        document.getElementById('responseModal').addEventListener('click', (e) => {
            if (e.target.id === 'responseModal') {
                closeModal();
            }
        });
    </script>
</body>
</html>
