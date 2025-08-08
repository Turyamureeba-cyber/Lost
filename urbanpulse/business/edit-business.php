<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if business ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$businessId = $_GET['id'];

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: ../auth/login.php');
        exit();
    }
    
    // Update session with latest user data
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['profile_photo'] = $user['avatar_url'] ?? 'default.jpg';
    
    // Get business data
    $businessStmt = $pdo->prepare("
        SELECT b.*, c.name as category_name 
        FROM businesses b
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.id = ? AND b.owner_id = ?
    ");
    $businessStmt->execute([$businessId, $_SESSION['user_id']]);
    $business = $businessStmt->fetch();
    
    if (!$business) {
        $_SESSION['error_message'] = "Business not found or you don't have permission to edit it";
        header('Location: index.php');
        exit();
    }
    
    // Get all categories for dropdown
    $categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $categoriesStmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $categoryId = $_POST['category_id'];
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $website = trim($_POST['website']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validate required fields
    if (empty($name)) {
        $_SESSION['error_message'] = "Business name is required";
        header("Location: edit-business.php?id=$businessId");
        exit();
    }
    
    try {
        // Update business
        $updateStmt = $pdo->prepare("
            UPDATE businesses SET 
                name = ?, 
                category_id = ?, 
                description = ?, 
                address = ?, 
                city = ?, 
                phone = ?, 
                email = ?, 
                website = ?, 
                featured = ?,
                updated_at = NOW()
            WHERE id = ? AND owner_id = ?
        ");
        
        $updateStmt->execute([
            $name,
            $categoryId,
            $description,
            $address,
            $city,
            $phone,
            $email,
            $website,
            $featured,
            $businessId,
            $_SESSION['user_id']
        ]);
        
        $_SESSION['success_message'] = "Business updated successfully!";
        header("Location: edit-business.php?id=$businessId");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating business: " . $e->getMessage();
        header("Location: edit-business.php?id=$businessId");
        exit();
    }
}

$pageTitle = "Edit Business | UrbanPulse";
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
        
        /* Form Styles */
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-title {
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
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-light);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-col {
            flex: 1;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .form-check-label {
            font-weight: 500;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
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
        
        .alert i {
            font-size: 1.2rem;
        }
        
        /* Image Upload Styles */
        .image-upload {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .preview-item {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .preview-item .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 25px;
            height: 25px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
        }
        
        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            border-color: var(--primary);
            background-color: var(--primary-light);
        }
        
        .upload-area i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .upload-area p {
            color: var(--gray);
            margin-bottom: 0;
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
            
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .search-bar {
                display: none;
            }
            
            .profile-name {
                display: none;
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
            <a href="../index.php" class="menu-item">
                <i class="fas fa-home"></i>
                <span>Home</span>
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
                <h1>Edit Business</h1>
                <p>Update your business information and settings</p>
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
            
            <!-- Edit Business Form -->
            <div class="form-card">
                <div class="form-header">
                    <h2 class="form-title">Business Information</h2>
                    <div>
                        <a href="index.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="name" class="form-label">Business Name *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($business['name']) ?>" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="category_id" class="form-label">Category *</label>
                                <select id="category_id" name="category_id" class="form-control" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                            <?= $category['id'] == $business['category_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control"><?= htmlspecialchars($business['description']) ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" id="address" name="address" class="form-control" 
                                       value="<?= htmlspecialchars($business['address']) ?>">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="city" class="form-label">City</label>
                                <input type="text" id="city" name="city" class="form-control" 
                                       value="<?= htmlspecialchars($business['city']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($business['phone']) ?>">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($business['email']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" id="website" name="website" class="form-control" 
                               value="<?= htmlspecialchars($business['website']) ?>">
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="featured" name="featured" class="form-check-input" 
                               <?= $business['featured'] ? 'checked' : '' ?>>
                        <label for="featured" class="form-check-label">Featured Business</label>
                    </div>
                    
                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="index.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
            
            <!-- Business Images Section -->
            <div class="form-card">
                <div class="form-header">
                    <h2 class="form-title">Business Images</h2>
                </div>
                
                <div class="image-upload">
                    <div class="upload-area">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop images here or click to browse</p>
                        <input type="file" id="imageUpload" multiple style="display: none;">
                    </div>
                    
                    <div class="image-preview">
                        <!-- Existing images would be displayed here -->
                        <div class="preview-item">
                            <img src="../assets/uploads/businesses/<?= $business['id'] ?>/image1.jpg" alt="Business Image">
                            <button class="remove-btn" type="button">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
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
            
            // Image upload functionality
            const uploadArea = document.querySelector('.upload-area');
            const imageUpload = document.getElementById('imageUpload');
            
            uploadArea.addEventListener('click', () => {
                imageUpload.click();
            });
            
            imageUpload.addEventListener('change', function(e) {
                const files = e.target.files;
                const previewContainer = document.querySelector('.image-preview');
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (!file.type.match('image.*')) continue;
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'remove-btn';
                        removeBtn.type = 'button';
                        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                        removeBtn.addEventListener('click', function() {
                            previewItem.remove();
                        });
                        
                        previewItem.appendChild(img);
                        previewItem.appendChild(removeBtn);
                        previewContainer.appendChild(previewItem);
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Drag and drop functionality
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--primary)';
                this.style.backgroundColor = 'var(--primary-light)';
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--border-color)';
                this.style.backgroundColor = 'transparent';
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--border-color)';
                this.style.backgroundColor = 'transparent';
                
                imageUpload.files = e.dataTransfer.files;
                const event = new Event('change');
                imageUpload.dispatchEvent(event);
            });
            
            // Remove existing images
            document.querySelectorAll('.preview-item .remove-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this image?')) {
                        // Here you would send an AJAX request to delete the image from server
                        this.closest('.preview-item').remove();
                    }
                });
            });
        });
    </script>
</body>
</html>