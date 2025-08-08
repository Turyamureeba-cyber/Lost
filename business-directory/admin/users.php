<?php
require '../includes/auth.php';

if (!isAdmin()) {
    redirect('../index.php');
}

// Handle user actions
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Prevent deleting admin account
    if ($id === 1) {
        $_SESSION['error'] = 'Cannot delete primary admin account';
    } else {
        // Check if user has businesses
        $check = $db->query("SELECT id FROM businesses WHERE user_id = $id");
        if ($check->num_rows > 0) {
            $_SESSION['error'] = 'Cannot delete user - they have businesses registered';
        } else {
            $db->query("DELETE FROM users WHERE id = $id");
            $_SESSION['success'] = 'User deleted successfully';
        }
    }
    
    redirect('users.php');
}

if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    
    // Prevent disabling admin account
    if ($id === 1) {
        $_SESSION['error'] = 'Cannot disable primary admin account';
    } else {
        $db->query("UPDATE users SET active = NOT active WHERE id = $id");
        $_SESSION['success'] = 'User status updated';
    }
    
    redirect('users.php');
}

$users = $db->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<?php 
$pageTitle = "Manage Users";
include 'includes/admin-header.php'; 
?>

<div class="admin-container">
    <h1>Manage Users</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users->num_rows > 0): ?>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo ucfirst($user['role']); ?></td>
                            <td>
                                <?php if ($user['id'] === 1): ?>
                                    <span class="status-badge active">Active</span>
                                <?php else: ?>
                                    <a href="users.php?toggle_status=<?php echo $user['id']; ?>" class="status-badge <?php echo $user['active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $user['active'] ? 'Active' : 'Inactive'; ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['id'] !== 1): ?>
                                    <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn-small">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn-small delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Confirm before deleting
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this user?')) {
                e.preventDefault();
            }
        });
    });
</script>

<?php include 'includes/admin-footer.php'; ?>