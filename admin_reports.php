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

// Date range filters
$date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('Y-m-01');
$date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('Y-m-d');

// Overview Stats
$stats = [];

// Total Revenue
try {
    $revenue_sql = "SELECT 
        SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as total_revenue,
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN booking_status = 'confirmed' THEN 1 END) as confirmed_bookings,
        COUNT(CASE WHEN booking_status = 'cancelled' THEN 1 END) as cancelled_bookings
        FROM aini_experience_bookings 
        WHERE created_at BETWEEN ? AND ?";
    $stmt = $pdo->prepare($revenue_sql);
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $stats['bookings'] = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats['bookings'] = ['total_revenue' => 0, 'total_bookings' => 0, 'confirmed_bookings' => 0, 'cancelled_bookings' => 0];
}

// User Stats
try {
    $user_sql = "SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_users
        FROM ainitravel_users";
    $stmt = $pdo->prepare($user_sql);
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats['users'] = ['total_users' => 0, 'new_users' => 0];
}

// Experience Stats
try {
    $exp_sql = "SELECT 
        COUNT(*) as total_experiences,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as active_experiences,
        AVG(CASE WHEN status = 'approved' THEN price_usd END) as avg_price
        FROM aini_experiences";
    $stats['experiences'] = $pdo->query($exp_sql)->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats['experiences'] = ['total_experiences' => 0, 'active_experiences' => 0, 'avg_price' => 0];
}

// Partner Stats
try {
    $partner_sql = "SELECT 
        COUNT(*) as total_partners,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_partners
        FROM aini_experience_partners";
    $stats['partners'] = $pdo->query($partner_sql)->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats['partners'] = ['total_partners' => 0, 'active_partners' => 0];
}

// Revenue by Category
try {
    $category_revenue_sql = "SELECT 
        e.category,
        COUNT(b.id) as booking_count,
        SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as revenue
        FROM aini_experiences e
        LEFT JOIN aini_experience_bookings b ON e.id = b.experience_id 
            AND b.created_at BETWEEN ? AND ?
        GROUP BY e.category
        ORDER BY revenue DESC
        LIMIT 10";
    $stmt = $pdo->prepare($category_revenue_sql);
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $category_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $category_revenue = [];
}

// Top Experiences
try {
    $top_exp_sql = "SELECT 
        e.name,
        e.category,
        COUNT(b.id) as booking_count,
        SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as revenue,
        AVG(r.rating) as avg_rating
        FROM aini_experiences e
        LEFT JOIN aini_experience_bookings b ON e.id = b.experience_id 
            AND b.created_at BETWEEN ? AND ?
        LEFT JOIN aini_experience_reviews r ON e.id = r.experience_id AND r.status = 'approved'
        GROUP BY e.id
        ORDER BY revenue DESC
        LIMIT 10";
    $stmt = $pdo->prepare($top_exp_sql);
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $top_experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_experiences = [];
}

// Top Partners
try {
    $top_partners_sql = "SELECT 
        p.business_name,
        COUNT(DISTINCT e.id) as experience_count,
        COUNT(b.id) as booking_count,
        SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as revenue
        FROM aini_experience_partners p
        LEFT JOIN aini_experiences e ON p.id = e.partner_id
        LEFT JOIN aini_experience_bookings b ON e.id = b.experience_id 
            AND b.created_at BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY revenue DESC
        LIMIT 10";
    $stmt = $pdo->prepare($top_partners_sql);
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $top_partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_partners = [];
}

// Daily Revenue (for chart)
try {
    $daily_revenue_sql = "SELECT 
        DATE(created_at) as date,
        COUNT(*) as bookings,
        SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as revenue
        FROM aini_experience_bookings
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date";
    $stmt = $pdo->prepare($daily_revenue_sql);
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $daily_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $daily_revenue = [];
}

// Recent Reviews
try {
    $recent_reviews_sql = "SELECT 
        r.rating,
        r.review_text,
        r.created_at,
        u.name as reviewer_name,
        e.name as experience_name
        FROM aini_experience_reviews r
        JOIN ainitravel_users u ON r.user_id = u.id
        JOIN aini_experiences e ON r.experience_id = e.id
        WHERE r.created_at BETWEEN ? AND ?
        ORDER BY r.created_at DESC
        LIMIT 10";
    $stmt = $pdo->prepare($recent_reviews_sql);
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $recent_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_reviews = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - AiniTravel Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            align-items: center;
        }

        .back-btn, .export-btn {
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

        .back-btn:hover, .export-btn:hover {
            transform: translateY(-2px);
        }

        .export-btn {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .date-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            align-items: center;
        }

        .date-filter input {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .date-filter button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 36px;
            margin-bottom: 8px;
        }

        .stat-card p {
            opacity: 0.9;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .stat-card small {
            opacity: 0.7;
            font-size: 12px;
        }

        .stat-card.revenue {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .stat-card.bookings {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.users {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .stat-card.experiences {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
        }

        .chart-card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .table-section {
            margin-bottom: 30px;
        }

        .table-section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            font-size: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-bottom: 20px;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 16px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
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

            .date-filter {
                flex-direction: column;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Reports & Analytics</h1>
            <div class="header-actions">
                <button class="export-btn" onclick="window.print()">📥 Export Report</button>
                <a href="admin_dashboard.php" class="back-btn">← Dashboard</a>
            </div>
        </div>

        <form method="GET" class="date-filter">
            <label style="font-weight: 600; color: #333;">Date Range:</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" required>
            <span style="color: #666;">to</span>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" required>
            <button type="submit">📅 Update Report</button>
            <button type="button" onclick="setToday()" style="background: #757575;">Today</button>
            <button type="button" onclick="setThisMonth()" style="background: #757575;">This Month</button>
        </form>

        <div class="stats-grid">
            <div class="stat-card revenue">
                <h3>$<?php echo number_format($stats['bookings']['total_revenue'] ?? 0, 2); ?></h3>
                <p>Total Revenue</p>
                <small><?php echo $stats['bookings']['total_bookings']; ?> bookings</small>
            </div>
            
            <div class="stat-card bookings">
                <h3><?php echo $stats['bookings']['confirmed_bookings']; ?></h3>
                <p>Confirmed Bookings</p>
                <small><?php echo $stats['bookings']['cancelled_bookings']; ?> cancelled</small>
            </div>
            
            <div class="stat-card users">
                <h3><?php echo $stats['users']['total_users']; ?></h3>
                <p>Total Users</p>
                <small><?php echo $stats['users']['new_users']; ?> new in period</small>
            </div>
            
            <div class="stat-card experiences">
                <h3><?php echo $stats['experiences']['active_experiences']; ?></h3>
                <p>Active Experiences</p>
                <small>Avg price: $<?php echo number_format($stats['experiences']['avg_price'] ?? 0, 2); ?></small>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h2>📈 Revenue Over Time</h2>
                <canvas id="revenueChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h2>📊 Revenue by Category</h2>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <div class="table-section">
            <h2>🏆 Top Performing Experiences</h2>
            <table>
                <thead>
                    <tr>
                        <th>Experience</th>
                        <th>Category</th>
                        <th>Bookings</th>
                        <th>Revenue</th>
                        <th>Avg Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_experiences)): ?>
                        <tr><td colspan="5" class="no-data">No data available for this period</td></tr>
                    <?php else: ?>
                        <?php foreach ($top_experiences as $exp): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($exp['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($exp['category']); ?></td>
                                <td><?php echo $exp['booking_count']; ?></td>
                                <td><strong>$<?php echo number_format($exp['revenue'] ?? 0, 2); ?></strong></td>
                                <td>
                                    <?php if ($exp['avg_rating']): ?>
                                        <span class="rating-stars">
                                            <?php echo str_repeat('⭐', round($exp['avg_rating'])); ?>
                                        </span>
                                        <?php echo number_format($exp['avg_rating'], 1); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">No reviews</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-section">
            <h2>🤝 Top Partners</h2>
            <table>
                <thead>
                    <tr>
                        <th>Partner</th>
                        <th>Experiences</th>
                        <th>Bookings</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_partners)): ?>
                        <tr><td colspan="4" class="no-data">No data available for this period</td></tr>
                    <?php else: ?>
                        <?php foreach ($top_partners as $partner): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($partner['business_name']); ?></strong></td>
                                <td><?php echo $partner['experience_count']; ?></td>
                                <td><?php echo $partner['booking_count']; ?></td>
                                <td><strong>$<?php echo number_format($partner['revenue'] ?? 0, 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-section">
            <h2>⭐ Recent Reviews</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reviewer</th>
                        <th>Experience</th>
                        <th>Rating</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_reviews)): ?>
                        <tr><td colspan="5" class="no-data">No reviews in this period</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_reviews as $review): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($review['reviewer_name']); ?></td>
                                <td><?php echo htmlspecialchars($review['experience_name']); ?></td>
                                <td>
                                    <span class="rating-stars">
                                        <?php echo str_repeat('⭐', $review['rating']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($review['review_text'], 0, 100)); ?>...</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Revenue Over Time Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const dailyData = <?php echo json_encode($daily_revenue); ?>;
        
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: dailyData.map(d => d.date),
                datasets: [{
                    label: 'Revenue ($)',
                    data: dailyData.map(d => parseFloat(d.revenue || 0)),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Bookings',
                    data: dailyData.map(d => parseInt(d.bookings)),
                    borderColor: '#43e97b',
                    backgroundColor: 'rgba(67, 233, 123, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Bookings'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });

        // Category Revenue Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($category_revenue); ?>;
        
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(c => c.category || 'Unknown'),
                datasets: [{
                    data: categoryData.map(c => parseFloat(c.revenue || 0)),
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#43e97b',
                        '#38f9d7',
                        '#fa709a',
                        '#fee140',
                        '#4facfe',
                        '#00f2fe',
                        '#f093fb',
                        '#f5576c'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        function setToday() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="date_from"]').value = today;
            document.querySelector('input[name="date_to"]').value = today;
        }

        function setThisMonth() {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
            const today = now.toISOString().split('T')[0];
            document.querySelector('input[name="date_from"]').value = firstDay;
            document.querySelector('input[name="date_to"]').value = today;
        }
    </script>
</body>
</html>
