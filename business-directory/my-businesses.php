<?php
require 'includes/auth.php';

if (!isBusinessOwner()) {
    redirect('index.php');
}

$userId = $_SESSION['user_id'];
$businesses = $db->query("SELECT b.*, c.name as category_name 
                         FROM businesses b
                         JOIN categories c ON b.category_id = c.id
                         WHERE b.user_id = $userId
                         ORDER BY b.created_at DESC");
?>

<?php 
$pageTitle = "My Businesses";
include 'includes/header.php'; 
?>

<div class="dashboard">
    <div class="sidebar">
        <h3>Business Owner Dashboard</h3>
        <nav>
            <a href="my-businesses.php" class="active"><i class="fas fa-store"></i> My Businesses</a>
            <a href="add-business.php"><i class="fas fa-plus-circle"></i> Add New Business</a>
            <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
            <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <h1>My Businesses</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <a href="add-business.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Business</a>
        
        <div class="business-list">
            <?php if ($businesses->num_rows > 0): ?>
                <?php while ($business = $businesses->fetch_assoc()): ?>
                    <div class="business-item">
                        <div class="business-image">
                            <?php if (!empty($business['logo'])): ?>
                                <img src="<?php echo $business['logo']; ?>" alt="<?php echo $business['name']; ?>">
                            <?php else: ?>
                                <div class="no-image"><i class="fas fa-store"></i></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="business-details">
                            <h3><?php echo $business['name']; ?></h3>
                            <p class="category"><?php echo $business['category_name']; ?></p>
                            <p class="location"><?php echo $business['location']; ?></p>
                            
                            <div class="business-status">
                                <span class="status-badge <?php echo $business['verified'] ? 'approved' : 'pending'; ?>">
                                    <?php echo $business['verified'] ? 'Approved' : 'Pending Approval'; ?>
                                </span>
                                
                                <?php if ($business['featured']): ?>
                                    <span class="status-badge featured">Featured</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="business-actions">
                            <a href="listing.php?id=<?php echo $business['id']; ?>" class="btn-secondary" target="_blank">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="edit-business.php?id=<?php echo $business['id']; ?>" class="btn-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-store-alt"></i>
                    <h3>No Businesses Found</h3>
                    <p>You haven't added any businesses yet. Get started by adding your first business.</p>
                    <a href="add-business.php" class="btn-primary">Add Your First Business</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>