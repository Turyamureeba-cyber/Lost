<?php
require '../includes/auth.php';

if (!isAdmin()) {
    redirect('../index.php');
}

// Handle category actions
if (isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    $db->query("INSERT INTO categories (name, description) VALUES ('$name', '$description')");
    $_SESSION['success'] = 'Category added successfully';
    redirect('categories.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if category is in use
    $check = $db->query("SELECT id FROM businesses WHERE category_id = $id");
    if ($check->num_rows > 0) {
        $_SESSION['error'] = 'Cannot delete category - it is being used by businesses';
    } else {
        $db->query("DELETE FROM categories WHERE id = $id");
        $_SESSION['success'] = 'Category deleted successfully';
    }
    
    redirect('categories.php');
}

$categories = $db->query("SELECT * FROM categories ORDER BY name");
?>

<?php 
$pageTitle = "Manage Categories";
include 'includes/admin-header.php'; 
?>

<div class="admin-container">
    <h1>Manage Categories</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <div class="admin-actions">
        <button class="btn-primary" id="addCategoryBtn">
            <i class="fas fa-plus"></i> Add New Category
        </button>
    </div>
    
    <div class="admin-form" id="addCategoryForm" style="display: none;">
        <h2>Add New Category</h2>
        <form method="POST">
            <div class="form-group">
                <label>Category Name*</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            
            <button type="submit" name="add_category" class="btn-primary">Add Category</button>
            <button type="button" id="cancelAddCategory" class="btn-secondary">Cancel</button>
        </form>
    </div>
    
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categories->num_rows > 0): ?>
                    <?php while ($category = $categories->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td><?php echo $category['name']; ?></td>
                            <td><?php echo $category['description']; ?></td>
                            <td>
                                <a href="edit-category.php?id=<?php echo $category['id']; ?>" class="btn-small">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="categories.php?delete=<?php echo $category['id']; ?>" class="btn-small delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No categories found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.getElementById('addCategoryBtn').addEventListener('click', function() {
        document.getElementById('addCategoryForm').style.display = 'block';
    });
    
    document.getElementById('cancelAddCategory').addEventListener('click', function() {
        document.getElementById('addCategoryForm').style.display = 'none';
    });
    
    // Confirm before deleting
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this category?')) {
                e.preventDefault();
            }
        });
    });
</script>

<?php include 'includes/admin-footer.php'; ?>