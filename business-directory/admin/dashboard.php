<?php
// dashboard.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required files
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

// Check admin status
if (!isAdmin()) {
    $_SESSION['error'] = "Admin privileges required";
    header('Location: ../index.php');
    exit();
}

// Get dashboard statistics
try {
    $totalBusinesses = $db->query("SELECT COUNT(*) as total FROM businesses")->fetch_assoc()['total'];
    $totalUsers = $db->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
    $pendingApprovals = $db->query("SELECT COUNT(*) as total FROM businesses WHERE verified = 0")->fetch_assoc()['total'];
    $featuredBusinesses = $db->query("SELECT COUNT(*) as total FROM businesses WHERE featured = 1")->fetch_assoc()['total'];

    // Recent businesses
    $recentBusinesses = $db->query("SELECT b.*, u.username as owner 
                                   FROM businesses b
                                   JOIN users u ON b.user_id = u.id
                                   ORDER BY b.created_at DESC
                                   LIMIT 5");

    // Recent users
    $recentUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading dashboard data";
    header('Location: ../index.php');
    exit();
}

// Set page title
$pageTitle = "Admin Dashboard";
include __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-container">
    <h1>Admin Dashboard</h1>
    
    <!-- Display any error messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-store"></i>
            </div>
            <div class="stat-info">
                <h3>Total Businesses</h3>
                <p><?php echo htmlspecialchars($totalBusinesses); ?></p>
            </div>
            <a href="businesses.php" class="stat-link">View All</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3>Total Users</h3>
                <p><?php echo htmlspecialchars($totalUsers); ?></p>
            </div>
            <a href="users.php" class="stat-link">View All</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3>Pending Approvals</h3>
                <p><?php echo htmlspecialchars($pendingApprovals); ?></p>
            </div>
            <a href="businesses.php?status=pending" class="stat-link">Review</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-info">
                <h3>Featured Businesses</h3>
                <p><?php echo htmlspecialchars($featuredBusinesses); ?></p>
            </div>
            <a href="businesses.php?status=featured" class="stat-link">Manage</a>
        </div>
    </div>
    
    <div class="dashboard-sections">
        <div class="dashboard-section">
            <h2>Recent Businesses</h2>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentBusinesses && $recentBusinesses->num_rows > 0): ?>
                            <?php while ($business = $recentBusinesses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($business['name']); ?></td>
                                    <td><?php echo htmlspecialchars($business['owner']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $business['verified'] ? 'approved' : 'pending'; ?>">
                                            <?php echo $business['verified'] ? 'Approved' : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($business['created_at'])); ?></td>
                                    <td>
                                        <a href="../listing.php?id=<?php echo (int)$business['id']; ?>" class="btn-small" target="_blank">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No businesses found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="dashboard-section">
            <h2>Recent Users</h2>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Date Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentUsers && $recentUsers->num_rows > 0): ?>
                            <?php while ($user = $recentUsers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>