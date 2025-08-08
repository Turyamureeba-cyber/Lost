<?php
require 'includes/auth.php';

if (!isBusinessOwner()) {
    redirect('index.php');
}

$businessId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];

// Verify the business belongs to the user
$business = $db->query("SELECT * FROM businesses WHERE id = $businessId AND user_id = $userId")->fetch_assoc();

if (!$business) {
    redirect('my-businesses.php');
}

$categories = getCategories();

// Decode JSON fields
$business['images'] = json_decode($business['images'], true) ?: [];
$business['features'] = json_decode($business['features'], true) ?: [];
$business['opening_hours'] = json_decode($business['opening_hours'], true) ?: [];
$business['social_media'] = json_decode($business['social_media'], true) ?: [];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission (similar to add-business.php)
    // Include validation and update logic here
    
    $success = 'Business updated successfully!';
}
?>

<?php 
$pageTitle = "Edit Business";
include 'includes/header.php'; 
?>

<div class="dashboard">
    <div class="sidebar">
        <h3>Business Owner Dashboard</h3>
        <nav>
            <a href="my-businesses.php"><i class="fas fa-store"></i> My Businesses</a>
            <a href="add-business.php"><i class="fas fa-plus-circle"></i> Add New Business</a>
            <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
            <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <h1>Edit Business: <?php echo $business['name']; ?></h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="business-form">
            <!-- Similar form structure to add-business.php but with existing values -->
            <!-- Include all fields pre-populated with $business data -->
            
            <div class="form-section">
                <h2>Basic Information</h2>
                
                <div class="form-group">
                    <label>Business Name*</label>
                    <input type="text" name="name" value="<?php echo $business['name']; ?>" required>
                </div>
                
                <!-- Other form fields -->
                
            </div>
            
            <button type="submit" class="btn-primary">Update Business</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>