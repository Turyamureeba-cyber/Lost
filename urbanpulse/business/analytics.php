<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Update session with latest user data
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['profile_photo'] = $user['avatar_url'] ?? 'default.jpg';
    
    // Get analytics data for the user's businesses
    $analyticsStmt = $pdo->prepare("
        SELECT 
            COUNT(b.id) as total_businesses,
            SUM(b.review_count) as total_reviews,
            AVG(b.rating) as avg_rating,
            SUM(CASE WHEN b.featured = 1 THEN 1 ELSE 0 END) as featured_businesses
        FROM businesses b
        WHERE b.owner_id = ?
    ");
    $analyticsStmt->execute([$_SESSION['user_id']]);
    $analyticsData = $analyticsStmt->fetch();
    
    // Get recent reviews
    $reviewsStmt = $pdo->prepare("
        SELECT r.*, b.name as business_name, u.username as reviewer_name
        FROM reviews r
        JOIN businesses b ON r.business_id = b.id
        JOIN users u ON r.user_id = u.id
        WHERE b.owner_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $reviewsStmt->execute([$_SESSION['user_id']]);
    $recentReviews = $reviewsStmt->fetchAll();
    
    // Get business categories distribution
    $categoriesStmt = $pdo->prepare("
        SELECT c.name, COUNT(b.id) as business_count
        FROM businesses b
        JOIN categories c ON b.category_id = c.id
        WHERE b.owner_id = ?
        GROUP BY c.name
        ORDER BY business_count DESC
    ");
    $categoriesStmt->execute([$_SESSION['user_id']]);
    $categoriesData = $categoriesStmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = "Analytics | UrbanPulse";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Maintain the same styling as your dashboard */
        :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #f1f3f5;
            --border-color: #e9ecef;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: var(--dark);
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles (same as dashboard) */
        .sidebar {
            width: 260px;
            background: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            height: 100vh;
            position: fixed;
            transition: all 0.3s;
            z-index: 100;
        }
        
        /* ... (include all your existing sidebar styles) ... */
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 260px;
            transition: all 0.3s;
        }
        
        /* Top Navigation (same as dashboard) */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        /* ... (include all your existing top-nav styles) ... */
        
        /* Content Wrapper */
        .content-wrapper {
            padding: 1.5rem;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-card h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .welcome-card p {
            opacity: 0.9;
            max-width: 600px;
        }
        
        /* Analytics Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .analytics-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .analytics-card h3 {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .analytics-value {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .analytics-change {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            color: var(--gray);
        }
        
        .analytics-change.positive {
            color: var(--success);
        }
        
        .analytics-change.negative {
            color: var(--danger);
        }
        
        /* Chart Containers */
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .chart-container h2 {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }
        
        /* Reviews List */
        .reviews-list {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .reviews-list h2 {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }
        
        .review-item {
            display: flex;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .review-rating {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .review-content {
            flex: 1;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .reviewer-name {
            font-weight: 500;
        }
        
        .review-business {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .review-time {
            color: var(--gray);
            font-size: 0.8rem;
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .analytics-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-card h1 {
                font-size: 1.5rem;
            }
        }

                :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #f1f3f5;
            --border-color: #e9ecef;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: var(--dark);
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            height: 100vh;
            position: fixed;
            transition: all 0.3s;
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-logo {
            height: 40px;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .menu-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--gray);
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--gray);
            text-decoration: none;
            transition: all 0.2s;
            margin: 0.25rem 0;
        }
        
        .menu-item:hover, .menu-item.active {
            color: var(--primary);
            background-color: var(--primary-light);
            border-left: 3px solid var(--primary);
        }
        
        .menu-item i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 260px;
            transition: all 0.3s;
        }
        
        /* Top Navigation */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .search-bar {
            position: relative;
            width: 400px;
        }
        
        .search-bar input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .search-bar i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .notification-icon {
            position: relative;
            margin-right: 1.5rem;
            color: var(--gray);
            cursor: pointer;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            font-weight: bold;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
        }
        
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0.75rem;
            border: 2px solid var(--primary-light);
        }
        
        .profile-name {
            font-weight: 500;
            margin-right: 0.5rem;
        }
        
        .profile-dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            width: 200px;
            padding: 0.5rem 0;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
            z-index: 100;
        }
        
        .user-profile:hover .profile-dropdown {
            opacity: 1;
            visibility: visible;
            top: 100%;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .dropdown-item i {
            margin-right: 0.75rem;
            color: var(--gray);
            width: 20px;
            text-align: center;
        }
        
        .dropdown-item:hover {
            background-color: var(--light-gray);
            color: var(--primary);
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 0.25rem 0;
        }
        
        /* Content Area */
        .content-wrapper {
            padding: 1.5rem;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .welcome-card h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .welcome-card p {
            opacity: 0.9;
            max-width: 600px;
        }
        
        /* Business Table Styles */
        .business-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .business-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .business-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .business-table th, 
        .business-table td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .business-table th {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .business-table tr:last-child td {
            border-bottom: none;
        }
        
        .business-table tr:hover {
            background-color: var(--light-gray);
        }
        
        .status {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }
        
        .status-inactive {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }
        
        .action-btn {
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            background: transparent;
            color: var(--gray);
        }
        
        .action-btn:hover {
            background-color: var(--light-gray);
            color: var(--primary);
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .search-bar {
                width: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .search-bar {
                display: none;
            }
            
            .business-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 576px) {
            .welcome-card h1 {
                font-size: 1.5rem;
            }
            
            .profile-name {
                display: none;
            }
            
            .business-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo-dark.png" alt="UrbanPulse" class="sidebar-logo">
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-title">Main</div>
            <a href="index.php" class="menu-item">
                <i class="fas fa-building"></i>
                <span>My Businesses</span>
            </a>
            <a href="analytics.php" class="menu-item active">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </a>
            <a href="calendar.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Calendar</span>
            </a>
            
            <div class="menu-title">Management</div>
            <a href="users.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search...">
            </div>
            
            <div class="user-menu">
                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                
                <div class="user-profile">
                    <img src="../assets/uploads/profile/<?= htmlspecialchars($_SESSION['profile_photo']) ?>" alt="Profile" class="profile-img">
                    <span class="profile-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <i class="fas fa-chevron-down"></i>
                    
                    <div class="profile-dropdown">
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <a href="messages.php" class="dropdown-item">
                            <i class="fas fa-envelope"></i>
                            <span>Messages</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h1>Business Analytics</h1>
                <p>Track performance and insights for your businesses.</p>
            </div>
            
            <!-- Analytics Summary Cards -->
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Total Businesses</h3>
                    <div class="analytics-value"><?= htmlspecialchars($analyticsData['total_businesses'] ?? 0) ?></div>
                    <div class="analytics-change positive">
                        <i class="fas fa-arrow-up"></i> 12% from last month
                    </div>
                </div>
                
                <div class="analytics-card">
                    <h3>Total Reviews</h3>
                    <div class="analytics-value"><?= htmlspecialchars($analyticsData['total_reviews'] ?? 0) ?></div>
                    <div class="analytics-change positive">
                        <i class="fas fa-arrow-up"></i> 8% from last month
                    </div>
                </div>
                
                <div class="analytics-card">
                    <h3>Average Rating</h3>
                    <div class="analytics-value"><?= number_format($analyticsData['avg_rating'] ?? 0, 1) ?></div>
                    <div class="analytics-change positive">
                        <i class="fas fa-arrow-up"></i> 0.3 from last month
                    </div>
                </div>
                
                <div class="analytics-card">
                    <h3>Featured Businesses</h3>
                    <div class="analytics-value"><?= htmlspecialchars($analyticsData['featured_businesses'] ?? 0) ?></div>
                    <div class="analytics-change positive">
                        <i class="fas fa-arrow-up"></i> 1 new this month
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="chart-container">
                <h2>Business Distribution by Category</h2>
                <canvas id="categoriesChart" height="300"></canvas>
            </div>
            
            <div class="chart-container">
                <h2>Monthly Reviews</h2>
                <canvas id="reviewsChart" height="300"></canvas>
            </div>
            
            <!-- Recent Reviews -->
            <div class="reviews-list">
                <h2>Recent Reviews</h2>
                
                <?php if (count($recentReviews) > 0): ?>
                    <?php foreach ($recentReviews as $review): ?>
                        <div class="review-item">
                            <div class="review-rating"><?= htmlspecialchars($review['rating']) ?></div>
                            <div class="review-content">
                                <div class="review-header">
                                    <span class="reviewer-name"><?= htmlspecialchars($review['reviewer_name']) ?></span>
                                    <span class="review-time"><?= date('M j, Y', strtotime($review['created_at'])) ?></span>
                                </div>
                                <div class="review-business"><?= htmlspecialchars($review['business_name']) ?></div>
                                <p><?= htmlspecialchars($review['comment']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No reviews yet for your businesses.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Profile dropdown toggle
            const profile = document.querySelector('.user-profile');
            profile.addEventListener('click', (e) => {
                e.stopPropagation();
                const dropdown = profile.querySelector('.profile-dropdown');
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', () => {
                const dropdowns = document.querySelectorAll('.profile-dropdown');
                dropdowns.forEach(dropdown => {
                    dropdown.style.display = 'none';
                });
            });
            
            // Categories Chart
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            const categoriesChart = new Chart(categoriesCtx, {
                type: 'doughnut',
                data: {
                    labels: [<?= implode(',', array_map(function($cat) { return "'" . htmlspecialchars($cat['name']) . "'"; }, $categoriesData)) ?>],
                    datasets: [{
                        data: [<?= implode(',', array_map(function($cat) { return $cat['business_count']; }, $categoriesData)) ?>],
                        backgroundColor: [
                            '#4361ee',
                            '#3f37c9',
                            '#4895ef',
                            '#4cc9f0',
                            '#f72585',
                            '#b5179e',
                            '#7209b7',
                            '#560bad'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
            
            // Reviews Chart (sample data - replace with your actual data)
            const reviewsCtx = document.getElementById('reviewsChart').getContext('2d');
            const reviewsChart = new Chart(reviewsCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Reviews',
                        data: [12, 19, 15, 20, 25, 30, 28],
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        borderColor: '#4361ee',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>