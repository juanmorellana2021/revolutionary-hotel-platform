<?php
/**
 * admin_experience_moderate.php
 * 
 * Admin panel to moderate (approve/reject) pending experience submissions
 * 
 * @author AI Assistant
 * @date November 11, 2025
 */

session_start();
require_once 'db_connection_pdo.php';

// Simple admin check - you can enhance this later
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['experience_id'])) {
    $experienceId = filter_input(INPUT_POST, 'experience_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];
    
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE aini_experiences SET status = 'active', approved_at = NOW(), approved_by = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $experienceId]);
            $message = "Experience approved successfully!";
        } elseif ($action === 'reject') {
            $reason = filter_input(INPUT_POST, 'rejection_reason', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $stmt = $pdo->prepare("UPDATE aini_experiences SET status = 'rejected', rejection_reason = ? WHERE id = ?");
            $stmt->execute([$reason, $experienceId]);
            $message = "Experience rejected.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch pending experiences
try {
    $stmt = $pdo->query("
        SELECT e.*, p.business_name, p.owner_name, p.email, p.phone 
        FROM aini_experiences e
        LEFT JOIN aini_experience_partners p ON e.partner_id = p.id
        WHERE e.status = 'pending_review'
        ORDER BY e.created_at DESC
    ");
    $pendingExperiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pendingExperiences = [];
}

// Fetch recently approved/rejected
try {
    $stmt = $pdo->query("
        SELECT e.*, p.business_name 
        FROM aini_experiences e
        LEFT JOIN aini_experience_partners p ON e.partner_id = p.id
        WHERE e.status IN ('active', 'rejected')
        ORDER BY e.updated_at DESC
        LIMIT 10
    ");
    $recentExperiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentExperiences = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experience Moderation - AiniTravel Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #667eea;
            font-size: 28px;
        }
        .header .stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        .stat-card {
            background: #f7f7f7;
            padding: 15px 20px;
            border-radius: 8px;
            flex: 1;
        }
        .stat-card h3 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 5px;
        }
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        .message {
            background: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .experience-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .experience-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .experience-title {
            flex: 1;
        }
        .experience-title h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 8px;
        }
        .experience-title .meta {
            color: #666;
            font-size: 14px;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-active { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .experience-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .info-section h3 {
            color: #667eea;
            font-size: 16px;
            margin-bottom: 12px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .info-row strong {
            min-width: 140px;
            color: #555;
        }
        .info-row span {
            color: #333;
        }
        .description-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-approve {
            background: #4CAF50;
            color: white;
        }
        .btn-approve:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        .btn-reject {
            background: #f44336;
            color: white;
        }
        .btn-reject:hover {
            background: #da190b;
            transform: translateY(-2px);
        }
        .btn-back {
            background: #667eea;
            color: white;
            text-decoration: none;
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
        }
        .rejection-form {
            display: none;
            margin-top: 15px;
        }
        .rejection-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            resize: vertical;
        }
        .section-title {
            color: white;
            font-size: 22px;
            margin: 30px 0 15px 0;
        }
        .no-pending {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 10px;
            color: #666;
        }
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .images-grid img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Experience Moderation Panel</h1>
            <div class="stats">
                <div class="stat-card">
                    <h3><?php echo count($pendingExperiences); ?></h3>
                    <p>Pending Review</p>
                </div>
                <div class="stat-card">
                    <h3><?php 
                        $activeCount = $pdo->query("SELECT COUNT(*) FROM aini_experiences WHERE status = 'active'")->fetchColumn();
                        echo $activeCount;
                    ?></h3>
                    <p>Active Experiences</p>
                </div>
            </div>
            <a href="admin_dashboard.php" class="btn-back" style="margin-top: 15px;">← Back to Dashboard</a>
        </div>

        <?php if (isset($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2 class="section-title">📋 Pending Experiences</h2>
        
        <?php if (empty($pendingExperiences)): ?>
            <div class="no-pending">
                <h2>✅ All caught up!</h2>
                <p>No pending experiences to review.</p>
            </div>
        <?php else: ?>
            <?php foreach ($pendingExperiences as $exp): ?>
                <div class="experience-card">
                    <div class="experience-header">
                        <div class="experience-title">
                            <h2><?php echo htmlspecialchars($exp['title']); ?></h2>
                            <div class="meta">
                                Submitted: <?php echo date('M d, Y g:i A', strtotime($exp['created_at'])); ?>
                                | Partner: <?php echo htmlspecialchars($exp['business_name']); ?>
                            </div>
                        </div>
                        <span class="status-badge status-pending">PENDING REVIEW</span>
                    </div>

                    <div class="experience-body">
                        <div>
                            <div class="info-section">
                                <h3>📍 Basic Information</h3>
                                <div class="info-row">
                                    <strong>Category:</strong>
                                    <span><?php echo htmlspecialchars($exp['category']); ?></span>
                                </div>
                                <div class="info-row">
                                    <strong>Location:</strong>
                                    <span><?php echo htmlspecialchars($exp['city'] . ', ' . $exp['country']); ?></span>
                                </div>
                                <div class="info-row">
                                    <strong>Meeting Point:</strong>
                                    <span><?php echo htmlspecialchars($exp['meeting_point']); ?></span>
                                </div>
                                <div class="info-row">
                                    <strong>Coordinates:</strong>
                                    <span><?php echo $exp['latitude'] ? "{$exp['latitude']}, {$exp['longitude']}" : 'Not provided'; ?></span>
                                </div>
                                <div class="info-row">
                                    <strong>Duration:</strong>
                                    <span><?php echo $exp['duration_hours'] . ' hours, ' . $exp['duration_days'] . ' days'; ?></span>
                                </div>
                                <div class="info-row">
                                    <strong>Difficulty:</strong>
                                    <span><?php echo ucfirst($exp['difficulty_level']); ?></span>
                                </div>
                                <div class="info-row">
                                    <strong>Participants:</strong>
                                    <span><?php echo $exp['min_participants'] . ' - ' . $exp['max_participants']; ?></span>
                                </div>
                            </div>

                            <div class="info-section" style="margin-top: 20px;">
                                <h3>💰 Pricing</h3>
                                <div class="info-row">
                                    <strong>USD:</strong>
                                    <span>$<?php echo number_format($exp['price_usd'], 2); ?></span>
                                </div>
                                <div class="info-row">
                                    <strong>AINI Rewards:</strong>
                                    <span><?php echo number_format($exp['price_aini_rewards']); ?> coins</span>
                                </div>
                                <div class="info-row">
                                    <strong>AINI Crypto:</strong>
                                    <span><?php echo number_format($exp['price_aini_crypto'], 4); ?> AINI</span>
                                </div>
                                <?php if ($exp['discount_percentage'] > 0): ?>
                                <div class="info-row">
                                    <strong>Discount:</strong>
                                    <span><?php echo $exp['discount_percentage']; ?>% OFF</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <div class="info-section">
                                <h3>👤 Partner Information</h3>
                                <div class="info-row">
                                    <strong>Business:</strong>
                                    <span><?php echo htmlspecialchars($exp['business_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <strong>Owner:</strong>
                                    <span><?php echo htmlspecialchars($exp['owner_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <strong>Email:</strong>
                                    <span><?php echo htmlspecialchars($exp['email']); ?></span>
                                </div>
                                <div class="info-row">
                                    <strong>Phone:</strong>
                                    <span><?php echo htmlspecialchars($exp['phone']); ?></span>
                                </div>
                            </div>

                            <div class="info-section" style="margin-top: 20px;">
                                <h3>📝 Description</h3>
                                <div class="description-box">
                                    <strong>Short:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($exp['short_description'])); ?></p>
                                </div>
                                <div class="description-box" style="margin-top: 10px;">
                                    <strong>Full Description:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($exp['description'])); ?></p>
                                </div>
                            </div>

                            <?php if ($exp['images']): ?>
                            <div class="info-section" style="margin-top: 20px;">
                                <h3>📷 Images</h3>
                                <div class="images-grid">
                                    <?php 
                                    $images = explode(',', $exp['images']);
                                    foreach ($images as $image): 
                                    ?>
                                        <img src="uploads/experiences/<?php echo trim($image); ?>" alt="Experience image">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="experience_id" value="<?php echo $exp['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this experience?')">
                                ✅ Approve & Publish
                            </button>
                        </form>
                        
                        <button class="btn btn-reject" onclick="showRejectForm(<?php echo $exp['id']; ?>)">
                            ❌ Reject
                        </button>
                        
                        <div id="reject-form-<?php echo $exp['id']; ?>" class="rejection-form">
                            <form method="POST">
                                <input type="hidden" name="experience_id" value="<?php echo $exp['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <textarea name="rejection_reason" rows="3" placeholder="Enter rejection reason..." required></textarea>
                                <button type="submit" class="btn btn-reject" style="margin-top: 10px;">Confirm Rejection</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($recentExperiences)): ?>
        <h2 class="section-title">📊 Recently Processed</h2>
        <?php foreach ($recentExperiences as $exp): ?>
            <div class="experience-card">
                <div class="experience-header">
                    <div class="experience-title">
                        <h2><?php echo htmlspecialchars($exp['title']); ?></h2>
                        <div class="meta">
                            <?php echo htmlspecialchars($exp['business_name']); ?> | 
                            Updated: <?php echo date('M d, Y g:i A', strtotime($exp['updated_at'])); ?>
                        </div>
                    </div>
                    <span class="status-badge status-<?php echo $exp['status']; ?>">
                        <?php echo strtoupper($exp['status']); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function showRejectForm(experienceId) {
            const form = document.getElementById('reject-form-' + experienceId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
