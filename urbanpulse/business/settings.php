<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Initialize variables
$success_message = '';
$error_message = '';
$user = [];
$businesses = [];

// Check for success/error messages from other operations (like delete)
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

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
    
    // Get user's businesses
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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    try {
        // Check if username or email already exists (excluding current user)
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $checkStmt->execute([$username, $email, $_SESSION['user_id']]);
        $existingUser = $checkStmt->fetch();
        
        if ($existingUser) {
            $error_message = "Username or email already exists";
        } else {
            // Handle file upload
            $avatar_url = $user['avatar_url'];
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/uploads/profile/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $fileName = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExt;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                    // Delete old avatar if it exists and isn't the default
                    if ($avatar_url && $avatar_url !== 'default.jpg' && file_exists($uploadDir . $avatar_url)) {
                        unlink($uploadDir . $avatar_url);
                    }
                    $avatar_url = $fileName;
                }
            }
            
            // Update user in database
            $updateStmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, avatar_url = ? WHERE id = ?");
            $updateStmt->execute([$username, $email, $avatar_url, $_SESSION['user_id']]);
            
            // Update session
            $_SESSION['username'] = $username;
            $_SESSION['user_email'] = $email;
            $_SESSION['profile_photo'] = $avatar_url;
            
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    } catch (PDOException $e) {
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}

// Handle business image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_business_image'])) {
    $business_id = $_POST['business_id'];
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    
    try {
        // Verify user owns this business
        $checkStmt = $pdo->prepare("SELECT id FROM businesses WHERE id = ? AND owner_id = ?");
        $checkStmt->execute([$business_id, $_SESSION['user_id']]);
        
        if ($checkStmt->fetch()) {
            if (isset($_FILES['business_image']) && $_FILES['business_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/uploads/business/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileExt = pathinfo($_FILES['business_image']['name'], PATHINFO_EXTENSION);
                $fileName = 'business_' . $business_id . '_' . time() . '.' . $fileExt;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['business_image']['tmp_name'], $targetPath)) {
                    // If this is marked as primary, unset any existing primary images
                    if ($is_primary) {
                        $pdo->prepare("UPDATE business_images SET is_primary = 0 WHERE business_id = ?")->execute([$business_id]);
                    }
                    
                    // Insert new image
                    $insertStmt = $pdo->prepare("INSERT INTO business_images (business_id, image_url, is_primary) VALUES (?, ?, ?)");
                    $insertStmt->execute([$business_id, $fileName, $is_primary]);
                    
                    $success_message = "Business image uploaded successfully!";
                } else {
                    $error_message = "Error uploading business image";
                }
            } else {
                $error_message = "No image selected or upload error occurred";
            }
        } else {
            $error_message = "You don't have permission to add images to this business";
        }
    } catch (PDOException $e) {
        $error_message = "Error uploading business image: " . $e->getMessage();
    }
}

// Handle business image edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_business_image'])) {
    $image_id = $_POST['image_id'];
    $business_id = $_POST['business_id'];
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    
    try {
        // Verify user owns this business image
        $checkStmt = $pdo->prepare("
            SELECT bi.id 
            FROM business_images bi
            JOIN businesses b ON bi.business_id = b.id
            WHERE bi.id = ? AND b.owner_id = ?
        ");
        $checkStmt->execute([$image_id, $_SESSION['user_id']]);
        
        if ($checkStmt->fetch()) {
            $updateData = [];
            $updateFields = [];
            
            // Handle file upload if a new image was provided
            if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/uploads/business/';
                
                // Get current image info
                $currentImageStmt = $pdo->prepare("SELECT image_url FROM business_images WHERE id = ?");
                $currentImageStmt->execute([$image_id]);
                $currentImage = $currentImageStmt->fetchColumn();
                
                // Generate new filename
                $fileExt = pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION);
                $fileName = 'business_' . $business_id . '_' . time() . '.' . $fileExt;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['new_image']['tmp_name'], $targetPath)) {
                    // Delete old image file
                    if ($currentImage && file_exists($uploadDir . $currentImage)) {
                        unlink($uploadDir . $currentImage);
                    }
                    
                    $updateFields[] = 'image_url = ?';
                    $updateData[] = $fileName;
                } else {
                    $error_message = "Error uploading new image";
                }
            }
            
            // Handle primary image setting
            if ($is_primary) {
                // Unset any existing primary images for this business
                $pdo->prepare("UPDATE business_images SET is_primary = 0 WHERE business_id = ?")->execute([$business_id]);
                
                $updateFields[] = 'is_primary = ?';
                $updateData[] = 1;
            }
            
            // Only proceed with update if there are fields to update
            if (!empty($updateFields)) {
                $updateData[] = $image_id;
                
                $sql = "UPDATE business_images SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $updateStmt = $pdo->prepare($sql);
                $updateStmt->execute($updateData);
                
                $success_message = "Business image updated successfully!";
            } else {
                $success_message = "No changes were made to the image.";
            }
        } else {
            $error_message = "You don't have permission to edit this image";
        }
    } catch (PDOException $e) {
        $error_message = "Error updating business image: " . $e->getMessage();
    }
}

// Get business images for display
$businessImages = [];
if (!empty($businesses)) {
    $businessIds = array_column($businesses, 'id');
    $placeholders = implode(',', array_fill(0, count($businessIds), '?'));
    
    $imageStmt = $pdo->prepare("
        SELECT bi.*, b.name as business_name 
        FROM business_images bi
        JOIN businesses b ON bi.business_id = b.id
        WHERE bi.business_id IN ($placeholders)
    ");
    $imageStmt->execute($businessIds);
    $businessImages = $imageStmt->fetchAll();
}

$pageTitle = "Settings | UrbanPulse";
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
        
        /* Settings Card Styles */
        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .settings-card h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            position: relative;
            padding-bottom: 0.75rem;
        }
        
        .settings-card h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
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
        
        .profile-picture-container {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-light);
            margin-right: 1.5rem;
        }
        
        .file-upload {
            display: flex;
            flex-direction: column;
        }
        
        .file-upload-label {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        
        .file-upload-label:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .file-upload-input {
            display: none;
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
            background-color: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-light);
        }
        
        /* Business Images Section */
        .business-images-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .business-image-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            border: 1px solid var(--border-color);
        }
        
        .business-image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .business-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .business-image-info {
            padding: 1rem;
        }
        
        .business-image-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .business-image-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .business-image-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }
        
        .image-action-btn {
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            background: transparent;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .image-action-btn:hover {
            background-color: var(--light-gray);
            color: var(--primary);
        }
        
        .image-action-btn.delete {
            color: var(--danger);
        }
        
        .image-action-btn.delete:hover {
            background-color: rgba(231, 76, 60, 0.1);
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
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
            margin-right: 0.75rem;
            font-size: 1.2rem;
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
            
            .profile-picture-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .profile-picture {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .welcome-card h1 {
                font-size: 1.5rem;
            }
            
            .profile-name {
                display: none;
            }
            
            .settings-card {
                padding: 1.5rem;
            }
            
            .business-images-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .close-modal {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        .close-modal:hover {
            color: var(--dark);
        }

        /* Edit button style */
        .image-action-btn.edit {
            color: var(--accent);
        }

        .image-action-btn.edit:hover {
            background-color: rgba(72, 149, 239, 0.1);
            color: var(--secondary);
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
            <a href="index.php" class="menu-item">
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
            <a href="settings.php" class="menu-item active">
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
                <h1>Account Settings</h1>
                <p>Manage your profile information and business images in one place.</p>
            </div>
            
            <!-- Alert Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($success_message) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Profile Settings Card -->
            <div class="settings-card">
                <h2>Profile Information</h2>
                
                <form action="settings.php" method="POST" enctype="multipart/form-data">
                    <div class="profile-picture-container">
                        <img src="../assets/uploads/profile/<?= htmlspecialchars($user['avatar_url'] ?? 'default.jpg') ?>" alt="Profile Picture" class="profile-picture" id="profile-picture-preview">
                        
                        <div class="file-upload">
                            <label for="avatar-upload" class="file-upload-label">
                                <i class="fas fa-camera"></i> Change Photo
                            </label>
                            <input type="file" id="avatar-upload" name="avatar" class="file-upload-input" accept="image/*">
                            <small>JPG, PNG or GIF (Max 5MB)</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
            
            <!-- Business Images Card -->
            <div class="settings-card">
                <h2>Business Images</h2>
                
                <?php if (!empty($businesses)): ?>
                    <form action="settings.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="business_id" class="form-label">Select Business</label>
                            <select id="business_id" name="business_id" class="form-control" required>
                                <option value="">-- Select a Business --</option>
                                <?php foreach ($businesses as $business): ?>
                                    <option value="<?= $business['id'] ?>"><?= htmlspecialchars($business['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_image" class="form-label">Upload Image</label>
                            <input type="file" id="business_image" name="business_image" class="form-control" accept="image/*" required>
                            <small>JPG, PNG or GIF (Max 5MB)</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="is_primary"> Set as primary image for this business
                            </label>
                        </div>
                        
                        <button type="submit" name="upload_business_image" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Image
                        </button>
                    </form>
                    
                    <?php if (!empty($businessImages)): ?>
                        <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Your Uploaded Images</h3>
                        <div class="business-images-container">
                            <?php foreach ($businessImages as $image): ?>
                                <div class="business-image-card">
                                    <img src="../assets/uploads/business/<?= htmlspecialchars($image['image_url']) ?>" alt="Business Image" class="business-image">
                                    <div class="business-image-info">
                                        <h4 class="business-image-title"><?= htmlspecialchars($image['business_name']) ?></h4>
                                        <?php if ($image['is_primary']): ?>
                                            <span class="business-image-status">Primary Image</span>
                                        <?php endif; ?>
                                        
                                        <div class="business-image-actions">
                                            <form action="set_primary_image.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                                                <input type="hidden" name="business_id" value="<?= $image['business_id'] ?>">
                                                <button type="submit" class="image-action-btn" <?= $image['is_primary'] ? 'disabled' : '' ?>>
                                                    <i class="fas fa-star"></i> Set Primary
                                                </button>
                                            </form>
                                            
                                            <button class="image-action-btn edit" onclick="openEditModal(<?= $image['id'] ?>, '<?= htmlspecialchars($image['image_url']) ?>', <?= $image['business_id'] ?>, <?= $image['is_primary'] ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            
                                            <form action="delete_image.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                                                <input type="hidden" name="business_id" value="<?= $image['business_id'] ?>">
                                                <button type="submit" class="image-action-btn delete" onclick="return confirm('Are you sure you want to delete this image?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="margin-top: 1.5rem; color: var(--gray);">You haven't uploaded any business images yet.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>You don't have any businesses yet. <a href="add-business.php">Add your first business</a> to upload images.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Image Modal -->
    <div id="editImageModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Edit Business Image</h2>
            
            <form id="editImageForm" action="settings.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="image_id" id="edit_image_id">
                <input type="hidden" name="business_id" id="edit_business_id">
                <input type="hidden" name="edit_business_image" value="1">
                
                <div class="form-group">
                    <label class="form-label">Current Image</label>
                    <img id="current_image_preview" src="" style="max-width: 100%; height: auto; border-radius: 8px; margin-bottom: 1rem;">
                </div>
                
                <div class="form-group">
                    <label for="new_image" class="form-label">Upload New Image</label>
                    <input type="file" id="new_image" name="new_image" class="form-control" accept="image/*">
                    <small>JPG, PNG or GIF (Max 5MB)</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="is_primary" id="edit_is_primary"> Set as primary image
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
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
            
            // Preview profile picture before upload
            const avatarUpload = document.getElementById('avatar-upload');
            const profilePreview = document.getElementById('profile-picture-preview');
            
            if (avatarUpload && profilePreview) {
                avatarUpload.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            profilePreview.src = event.target.result;
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }
            
            // Edit Image Modal Functions
            function openEditModal(imageId, imageUrl, businessId, isPrimary) {
                const modal = document.getElementById('editImageModal');
                const currentImagePreview = document.getElementById('current_image_preview');
                
                // Set the form values
                document.getElementById('edit_image_id').value = imageId;
                document.getElementById('edit_business_id').value = businessId;
                document.getElementById('edit_is_primary').checked = isPrimary;
                currentImagePreview.src = '../assets/uploads/business/' + imageUrl;
                
                // Show the modal
                modal.style.display = 'block';
            }

            function closeEditModal() {
                document.getElementById('editImageModal').style.display = 'none';
            }

            // Close modal when clicking X or outside
            document.querySelector('.close-modal').addEventListener('click', closeEditModal);
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('editImageModal');
                if (event.target === modal) {
                    closeEditModal();
                }
            });

            // Preview new image before upload
            document.getElementById('new_image').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        document.getElementById('current_image_preview').src = event.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>