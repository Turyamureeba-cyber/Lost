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
    
    // Get user's businesses with category names
    $businessStmt = $pdo->prepare("
        SELECT b.*, c.name as category_name 
        FROM businesses b
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.owner_id = ?
    ");
    $businessStmt->execute([$_SESSION['user_id']]);
    $businesses = $businessStmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = "My Businesses | UrbanPulse";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
        } @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    .menu-item:hover .fa-star {
        animation: none;
        transform: rotate(15deg);
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
             <a href="../index.php" class="menu-item">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <!-- deepseek add here menu called professional. make it artistic -->
<a href="professional.php" class="menu-item">
    <i class="fas fa-star" style="
        background: linear-gradient(135deg, #FFD700, #FFA500);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        text-shadow: 0 0 8px rgba(255, 215, 0, 0.3);
        animation: pulse 2s infinite;
    "></i>
    <span style="
        background: linear-gradient(135deg, #4361ee, #3f37c9);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-weight: 600;
        letter-spacing: 0.5px;
    ">Professional</span>
</a>
            <a href="index.php" class="menu-item active">
                <i class="fas fa-building"></i>
                <span>My Businesses</span>
            </a>
            <a href="analytics.php" class="menu-item">
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
                <input type="text" placeholder="Search businesses...">
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
                        <a href="../auth/logout.php" class="dropdown-item">
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
                <h1>Manage Your Businesses</h1>
                <p>View, edit, and manage all your business listings in one place.</p>
            </div>
            
            <!-- Business CRUD Section -->
            <div class="business-section">
                <div class="business-header">
                    <h2 class="section-title">My Business Listings</h2>
                    <a href="add-business.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add New Business
                    </a>
                </div>
                
                <div class="business-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Business Name</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Rating</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($businesses) > 0): ?>
                                <?php foreach ($businesses as $business): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($business['name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($business['category_name'] ?? 'Uncategorized') ?></td>
                                        <td><?= htmlspecialchars(($business['city'] ?? '') . ($business['address'] ? ', ' . $business['address'] : '')) ?></td>
                                        <td>
                                            <span class="status <?= ($business['featured'] ?? false) ? 'status-active' : 'status-inactive' ?>">
                                                <?= ($business['featured'] ?? false) ? 'Featured' : 'Standard' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($business['rating'] > 0): ?>
                                                <?= str_repeat('★', floor($business['rating'])) ?><?= str_repeat('☆', 5 - floor($business['rating'])) ?>
                                                (<?= $business['review_count'] ?? 0 ?>)
                                            <?php else: ?>
                                                No reviews yet
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit-business.php?id=<?= $business['id'] ?>" class="action-btn" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view-business.php?id=<?= $business['id'] ?>" class="action-btn" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="action-btn delete-business" title="Delete" data-id="<?= $business['id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem;">
                                        You don't have any businesses yet. <a href="add-business.php">Add your first business</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
            
            // Delete business functionality
            document.querySelectorAll('.delete-business').forEach(btn => {
                btn.addEventListener('click', function() {
                    const businessId = this.getAttribute('data-id');
                    const businessName = this.closest('tr').querySelector('td').textContent;
                    
                    if (confirm(`Are you sure you want to delete ${businessName}?`)) {
                        fetch(`delete-business.php?id=${businessId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.closest('tr').remove();
                            } else {
                                alert('Error deleting business: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the business');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>