<?php
require 'includes/auth.php';

$userId = $_SESSION['user_id'];
$user = $db->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        
        // Check if username is already taken by another user
        $check = $db->query("SELECT id FROM users WHERE username = '$username' AND id != $userId");
        if ($check->num_rows > 0) {
            $error = 'Username already taken';
        } else {
            $db->query("UPDATE users SET username = '$username', email = '$email' WHERE id = $userId");
            $_SESSION['username'] = $username;
            $success = 'Profile updated successfully';
        }
    } elseif (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password = '$hashedPassword' WHERE id = $userId");
            $success = 'Password changed successfully';
        }
    }
    
    // Refresh user data
    $user = $db->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();
}
?>

<?php 
$pageTitle = "My Profile";
include 'includes/header.php'; 
?>

<div class="dashboard">
    <div class="sidebar">
        <h3>Business Owner Dashboard</h3>
        <nav>
            <a href="my-businesses.php"><i class="fas fa-store"></i> My Businesses</a>
            <a href="add-business.php"><i class="fas fa-plus-circle"></i> Add New Business</a>
            <a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile</a>
            <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <h1>My Profile</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="profile-section">
            <h2>Profile Information</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo $user['username']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Account Type</label>
                    <input type="text" value="<?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Member Since</label>
                    <input type="text" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" disabled>
                </div>
                
                <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
            </form>
        </div>
        
        <div class="profile-section">
            <h2>Change Password</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="change_password" class="btn-primary">Change Password</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>