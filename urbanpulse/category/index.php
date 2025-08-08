<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit();
}

// Pagination settings
$perPage = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Search term
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        // Add new category
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $icon = trim($_POST['icon']);
        
        if (!empty($name)) {
            // Generate slug if empty
            if (empty($slug)) {
                $slug = strtolower(str_replace(' ', '-', $name));
                $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)");
                $stmt->execute([$name, $slug, $icon]);
                $_SESSION['success_message'] = "Category added successfully!";
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error adding category: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Category name is required!";
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['update_category'])) {
        // Update existing category
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $icon = trim($_POST['icon']);
        
        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, icon = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $icon, $id]);
                $_SESSION['success_message'] = "Category updated successfully!";
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error updating category: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Category name is required!";
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_message'] = "Category deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting category: " . $e->getMessage();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Build search query
$searchQuery = "";
$params = [];
if (!empty($searchTerm)) {
    $searchQuery = "WHERE name LIKE ? OR slug LIKE ? OR icon LIKE ?";
    $searchParam = "%$searchTerm%";
    $params = [$searchParam, $searchParam, $searchParam];
}

// Get total number of categories
$totalQuery = "SELECT COUNT(*) FROM categories $searchQuery";
$stmt = $pdo->prepare($totalQuery);
$stmt->execute($params);
$totalCategories = $stmt->fetchColumn();
$totalPages = ceil($totalCategories / $perPage);

// Get categories for current page
$query = "SELECT * FROM categories $searchQuery ORDER BY name LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);

// Bind search parameters if they exist
if (!empty($searchTerm)) {
    $stmt->bindValue(1, "%$searchTerm%", PDO::PARAM_STR);
    $stmt->bindValue(2, "%$searchTerm%", PDO::PARAM_STR);
    $stmt->bindValue(3, "%$searchTerm%", PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll();

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $edit_category = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories | UrbanPulse</title>
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
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --glow: 0 0 10px rgba(67, 97, 238, 0.3);
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
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .header h1 {
            font-size: 2rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-light);
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            font-size: 1.25rem;
            margin-bottom: 20px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Search Box Styles */
        .search-box {
            position: relative;
            margin-bottom: 25px;
        }
        
        .search-input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid var(--border-color);
            border-radius: 50px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
            box-shadow: var(--shadow);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: var(--glow);
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .search-clear {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            cursor: pointer;
            opacity: 0;
            transition: var(--transition);
        }
        
        .search-input:not(:placeholder-shown) + .search-icon + .search-clear {
            opacity: 1;
        }
        
        .search-clear:hover {
            color: var(--danger);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .table th, .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table th {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr {
            transition: var(--transition);
        }
        
        .table tr:hover {
            background-color: var(--light-gray);
            transform: translateX(5px);
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-primary {
            background-color: var(--primary-light);
            color: var(--primary);
        }
        
        .action-btn {
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            background: transparent;
            color: var(--gray);
            margin-right: 5px;
        }
        
        .action-btn:hover {
            background-color: var(--light-gray);
            color: var(--primary);
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--primary-light);
        }
        
        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 5px;
        }
        
        .page-item {
            list-style: none;
        }
        
        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid transparent;
        }
        
        .page-link:hover {
            background-color: var(--primary-light);
            color: var(--primary);
        }
        
        .page-item.active .page-link {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .page-item.disabled .page-link {
            color: var(--gray);
            pointer-events: none;
        }
        
        /* Floating action button */
        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
            transition: var(--transition);
            z-index: 100;
            border: none;
            cursor: pointer;
        }
        
        .fab:hover {
            background-color: var(--secondary);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.4);
        }
        
        /* Loading animation */
        .loader {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .loader i {
            font-size: 2rem;
            color: var(--primary);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Highlight matching text */
        .highlight {
            background-color: rgba(255, 255, 0, 0.3);
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
            
            .fab {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
         <a href="../admin/dashboard.php" class="btn btn-outline" style="margin-right: 10px;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <div class="header">
            <h1><i class="fas fa-tags"></i> Manage Categories</h1>
            <div>
                <span class="badge badge-primary" id="totalCount">
                    Total Categories: <?= number_format($totalCategories) ?>
                </span>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error_message'] ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <!-- Search Box -->
        <div class="search-box">
            <input type="text" id="liveSearch" class="search-input" placeholder="Search categories..." 
                   value="<?= htmlspecialchars($searchTerm) ?>" autocomplete="off">
            <i class="fas fa-search search-icon"></i>
            <i class="fas fa-times search-clear" onclick="clearSearch()"></i>
        </div>
        
        <!-- Categories Table -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-list"></i> Categories List</h2>
            
            <div id="loader" class="loader">
                <i class="fas fa-spinner"></i>
            </div>
            
            <div id="tableContainer">
                <?php if (count($categories) > 0): ?>
                    <div class="table-responsive">
                        <table class="table" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Icon</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?= $category['id'] ?></td>
                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                        <td><span class="badge badge-primary"><?= htmlspecialchars($category['slug']) ?></span></td>
                                        <td>
                                            <?php if (!empty($category['icon'])): ?>
                                                <i class="<?= htmlspecialchars($category['icon']) ?>"></i>
                                            <?php else: ?>
                                                <span class="text-muted">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?= $category['id'] ?>" class="action-btn" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?= $category['id'] ?>" class="action-btn text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <nav aria-label="Categories pagination">
                        <ul class="pagination">
                            <!-- Previous Page Link -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" aria-label="Previous">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            // Show first page if not in range
                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Show last page if not in range -->
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $totalPages ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Next Page Link -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" aria-label="Next">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    
                    <div class="text-center text-muted">
                        Showing <?= ($offset + 1) ?> to <?= min($offset + $perPage, $totalCategories) ?> of <?= number_format($totalCategories) ?> categories
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-tags"></i>
                        <h3>No Categories Found</h3>
                        <p><?= !empty($searchTerm) ? 'No results match your search' : 'Start by adding your first category' ?></p>
                        <button type="button" class="btn btn-primary" onclick="showAddForm()">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add/Edit Category Form -->
<div id="categoryForm" class="card" style="display: <?= isset($edit_category) ? 'block' : 'none' ?>;">
    <h2 class="card-title">
        <i class="fas <?= isset($edit_category) ? 'fa-edit' : 'fa-plus-circle' ?>"></i> 
        <?= isset($edit_category) ? 'Edit Category' : 'Add New Category' ?>
    </h2>
    <form method="POST" action="">
        <?php if (isset($edit_category)): ?>
            <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="name">Category Name *</label>
            <input type="text" id="name" name="name" class="form-control" 
                   value="<?= isset($edit_category['name']) ? htmlspecialchars($edit_category['name']) : '' ?>" required>
        </div>
        
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" class="form-control" 
                   value="<?= isset($edit_category['slug']) ? htmlspecialchars($edit_category['slug']) : '' ?>">
            <small class="text-muted">Leave empty to auto-generate from name</small>
        </div>
        
        <div class="form-group">
            <label for="icon">Icon (Font Awesome class)</label>
            <input type="text" id="icon" name="icon" class="form-control" 
                   value="<?= isset($edit_category['icon']) && $edit_category['icon'] !== null ? htmlspecialchars($edit_category['icon']) : '' ?>"
                   placeholder="e.g. fas fa-store">
        </div>
        
        <div class="form-group">
            <?php if (isset($edit_category)): ?>
                <button type="submit" name="update_category" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Category
                </button>
                <a href="?" class="btn btn-outline">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_category" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Category
                </button>
                <button type="button" onclick="hideAddForm()" class="btn btn-outline">Cancel</button>
            <?php endif; ?>
        </div>
    </form>
</div>

    <!-- Floating Action Button -->
    <button class="fab" onclick="showAddForm()" id="fabButton">
        <i class="fas fa-plus"></i>
    </button>

    <script>
        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            if (!document.getElementById('slug').value) {
                let slug = this.value.toLowerCase()
                    .replace(/ /g, '-')
                    .replace(/[^\w-]+/g, '');
                document.getElementById('slug').value = slug;
            }
        });
        
        // Show/hide add form
        function showAddForm() {
            document.getElementById('categoryForm').style.display = 'block';
            document.getElementById('fabButton').style.display = 'none';
            window.scrollTo({
                top: document.getElementById('categoryForm').offsetTop - 20,
                behavior: 'smooth'
            });
        }
        
        function hideAddForm() {
            document.getElementById('categoryForm').style.display = 'none';
            document.getElementById('fabButton').style.display = 'flex';
            // Clear form if not editing
            if (!<?= isset($edit_category) ? 'true' : 'false' ?>) {
                document.querySelector('#categoryForm form').reset();
            }
        }
        
        // Show form if coming from empty state or edit link
        if (window.location.hash === '#add' || <?= isset($edit_category) ? 'true' : 'false' ?>) {
            showAddForm();
        }
        
        // Clear search
        function clearSearch() {
            document.getElementById('liveSearch').value = '';
            document.getElementById('liveSearch').dispatchEvent(new Event('input'));
        }
        
        // Live search functionality
        let searchTimeout;
        document.getElementById('liveSearch').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            const searchTerm = this.value.trim();
            const loader = document.getElementById('loader');
            const tableContainer = document.getElementById('tableContainer');
            
            if (searchTerm.length === 0) {
                // If search is empty, reload the page to show all categories
                window.location.href = window.location.pathname;
                return;
            }
            
            // Show loader
            loader.style.display = 'block';
            tableContainer.style.opacity = '0.5';
            
            searchTimeout = setTimeout(() => {
                // Update URL without reloading
                const newUrl = window.location.pathname + '?search=' + encodeURIComponent(searchTerm);
                window.history.pushState({ path: newUrl }, '', newUrl);
                
                // Fetch search results
                fetch(newUrl + '&ajax=1')
                    .then(response => response.text())
                    .then(html => {
                        // Parse the HTML response
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newTableContainer = doc.getElementById('tableContainer');
                        
                        // Replace the content
                        if (newTableContainer) {
                            document.getElementById('tableContainer').innerHTML = newTableContainer.innerHTML;
                            
                            // Highlight matching text
                            highlightSearchTerms(searchTerm);
                            
                            // Update total count
                            const newTotalCount = doc.getElementById('totalCount');
                            if (newTotalCount) {
                                document.getElementById('totalCount').textContent = newTotalCount.textContent;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        loader.style.display = 'none';
                        tableContainer.style.opacity = '1';
                    });
            }, 500); // 500ms delay before searching
        });
        
        // Highlight search terms in the table
        function highlightSearchTerms(term) {
            if (!term) return;
            
            const table = document.getElementById('categoriesTable');
            if (!table) return;
            
            const regex = new RegExp(term, 'gi');
            const cells = table.querySelectorAll('td');
            
            cells.forEach(cell => {
                const originalText = cell.textContent;
                const highlightedText = originalText.replace(regex, match => 
                    `<span class="highlight">${match}</span>`
                );
                
                if (highlightedText !== originalText) {
                    cell.innerHTML = highlightedText;
                }
            });
        }
        
        // Initial highlight if there's a search term
        document.addEventListener('DOMContentLoaded', () => {
            const searchTerm = "<?= $searchTerm ?>";
            if (searchTerm) {
                highlightSearchTerms(searchTerm);
            }
        });
    </script>
</body>
</html>