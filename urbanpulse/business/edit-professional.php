<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/../includes/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Prepare data
        $data = [
            'user_id' => $_SESSION['user_id'],
            'full_name' => $_POST['full_name'],
            'title' => $_POST['title'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'skills' => $_POST['skills'],
            'location' => $_POST['location'],
            'experience' => json_encode($_POST['experience'] ?? []),
            'testimonials' => json_encode($_POST['testimonials'] ?? [])
        ];

        // Handle file upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/profile/';
            $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $data['photo_url'] = $fileName;
            }
        }

        // Check if profile exists
        $stmt = $pdo->prepare("SELECT id FROM professional_profiles WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            // Update existing profile
            if (isset($data['photo_url'])) {
                $sql = "UPDATE professional_profiles SET 
                        full_name = :full_name, 
                        title = :title, 
                        email = :email, 
                        phone = :phone, 
                        skills = :skills, 
                        location = :location, 
                        experience = :experience, 
                        testimonials = :testimonials,
                        photo_url = :photo_url 
                        WHERE user_id = :user_id";
            } else {
                $sql = "UPDATE professional_profiles SET 
                        full_name = :full_name, 
                        title = :title, 
                        email = :email, 
                        phone = :phone, 
                        skills = :skills, 
                        location = :location, 
                        experience = :experience, 
                        testimonials = :testimonials 
                        WHERE user_id = :user_id";
            }
        } else {
            // Insert new profile
            $sql = "INSERT INTO professional_profiles (
                    user_id, full_name, title, email, phone, 
                    skills, location, experience, testimonials, photo_url
                ) VALUES (
                    :user_id, :full_name, :title, :email, :phone, 
                    :skills, :location, :experience, :testimonials, :photo_url
                )";
            
            // Set default photo if none uploaded
            if (!isset($data['photo_url'])) {
                $data['photo_url'] = $_SESSION['profile_photo'] ?? 'default.jpg';
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        
        $_SESSION['success_message'] = 'Profile updated successfully!';
        header('Location: professional.php');
        exit();
        
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Fetch existing profile data
try {
    $stmt = $pdo->prepare("SELECT * FROM professional_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        $profile = [
            'full_name' => $_SESSION['username'],
            'title' => '',
            'email' => $_SESSION['user_email'],
            'phone' => '',
            'skills' => '',
            'location' => '',
            'experience' => '[]',
            'testimonials' => '[]',
            'photo_url' => $_SESSION['profile_photo'] ?? 'default.jpg'
        ];
    }
    
    // Decode JSON fields
    $profile['experience'] = json_decode($profile['experience'], true) ?: [];
    $profile['testimonials'] = json_decode($profile['testimonials'], true) ?: [];
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = "Edit Professional Profile | UrbanPulse";
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
        
        .professional-header {
            background: linear-gradient(135deg, #3a0ca3, #4361ee);
            color: white;
            border-radius: 12px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(58, 12, 163, 0.2);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        
        .professional-header::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -50px;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid rgba(255, 255, 255, 0.2);
            margin-right: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .profile-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .profile-info h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        
        .profile-info .title {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 1rem;
            font-weight: 400;
        }
        
        .profile-info .location {
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }
        
        .profile-info .location i {
            margin-right: 0.5rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            position: relative;
        }
        
        .profile-card h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .profile-card h2 i {
            margin-right: 0.75rem;
            color: var(--accent);
        }
        
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--light-gray);
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .contact-item:hover {
            background: var(--primary-light);
            transform: translateY(-3px);
        }
        
        .contact-item i {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: var(--primary);
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(67, 97, 238, 0.1);
        }
        
        .contact-details h3 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }
        
        .contact-details p {
            font-weight: 500;
            color: var(--dark);
        }
        
        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .skill-tag {
            background: linear-gradient(135deg, var(--primary-light), white);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .skill-tag:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }
        
        .experience-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .experience-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .experience-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .experience-title {
            font-weight: 600;
            color: var(--dark);
        }
        
        .experience-date {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .experience-company {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .experience-description {
            color: var(--gray);
            line-height: 1.6;
        }
        
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .testimonial-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-top: 3px solid var(--primary);
            position: relative;
        }
        
        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: 10px;
            left: 15px;
            font-size: 4rem;
            color: rgba(67, 97, 238, 0.1);
            font-family: serif;
            line-height: 1;
        }
        
        .testimonial-text {
            font-style: italic;
            color: var(--dark);
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .testimonial-author img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0.75rem;
        }
        
        .author-info h4 {
            font-weight: 600;
            margin-bottom: 0.1rem;
        }
        
        .author-info p {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        .edit-profile-btn {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .edit-profile-btn:hover {
            background: white;
            color: var(--primary);
            transform: rotate(15deg);
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
            
            .professional-header {
                flex-direction: column;
                text-align: center;
                padding: 2rem 1.5rem;
            }
            
            .profile-photo {
                margin-right: 0;
                margin-bottom: 1.5rem;
                width: 120px;
                height: 120px;
            }
            
            .profile-info h1 {
                font-size: 1.8rem;
            }
            
            .contact-info {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .profile-name {
                display: none;
            }
            
            .experience-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .testimonials-grid {
                grid-template-columns: 1fr;
            }
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
        
        .photo-upload {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-light);
        }
        
        .upload-controls {
            flex: 1;
        }
        
        .btn-secondary {
            background-color: var(--light-gray);
            color: var(--dark);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
        }
        
        .dynamic-field {
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: var(--light-gray);
            border-radius: 8px;
            position: relative;
        }
        
        .remove-field {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            color: var(--danger);
            cursor: pointer;
            background: none;
            border: none;
            font-size: 1.2rem;
        }
        
        .add-field {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            color: var(--primary);
        }
        
        .add-field i {
            font-size: 1.2rem;
        }
        
        .error-message {
            color: var(--danger);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .success-message {
            color: var(--success);
            background: rgba(46, 204, 113, 0.1);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .success-message i {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar (same as professional.php) -->
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
            <a href="professional.php" class="menu-item active">
                <i class="fas fa-star" style="
                    background: linear-gradient(135deg, #FFD700, #FFA500);
                    -webkit-background-clip: text;
                    background-clip: text;
                    color: transparent;
                    text-shadow: 0 0 8px rgba(255, 215, 0, 0.3);
                "></i>
                <span style="
                    background: linear-gradient(135deg, #4361ee, #3f37c9);
                    -webkit-background-clip: text;
                    background-clip: text;
                    color: transparent;
                    font-weight: 600;
                ">Professional</span>
            </a>
            <a href="analytics.php" class="menu-item">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
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
    </div>
    
    <!-- Main Content -->
   <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search...">
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
                        <div class="dropdown-divider"></div>
                        <a href="../auth/logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        <nav class="top-nav">
            <!-- ... -->
        </nav>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="professional-header">
                <h1>Edit Professional Profile</h1>
                <p>Update your professional information to showcase your skills and experience.</p>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <span><?= $_SESSION['success_message'] ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>
            
            <form action="edit-professional.php" method="POST" enctype="multipart/form-data" class="profile-card">
                <div class="form-group">
                    <label for="photo" class="form-label">Profile Photo</label>
                    <div class="photo-upload">
                        <img src="../assets/uploads/profile/<?= htmlspecialchars($profile['photo_url']) ?>" 
                             alt="Current Photo" class="photo-preview" id="photo-preview">
                        <div class="upload-controls">
                            <input type="file" id="photo" name="photo" accept="image/*" class="form-control" 
                                   onchange="previewImage(this, 'photo-preview')">
                            <small class="text-muted">Recommended size: 500x500 pixels</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?= htmlspecialchars($profile['full_name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="title" class="form-label">Professional Title</label>
                    <input type="text" id="title" name="title" class="form-control" 
                           value="<?= htmlspecialchars($profile['title']) ?>" 
                           placeholder="e.g., Senior Web Developer, Marketing Specialist">
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($profile['email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?= htmlspecialchars($profile['phone']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" id="location" name="location" class="form-control" 
                           value="<?= htmlspecialchars($profile['location']) ?>" 
                           placeholder="City, Country">
                </div>
                
                <div class="form-group">
                    <label for="skills" class="form-label">Skills (comma separated)</label>
                    <textarea id="skills" name="skills" class="form-control" 
                              placeholder="e.g., Web Development, Graphic Design, Project Management"><?= htmlspecialchars($profile['skills']) ?></textarea>
                </div>
                
                <h2><i class="fas fa-briefcase"></i> Work Experience</h2>
                <div id="experience-container">
                    <?php foreach ($profile['experience'] as $index => $exp): ?>
                        <div class="dynamic-field">
                            <button type="button" class="remove-field" onclick="removeField(this)">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="form-group">
                                <label for="experience[<?= $index ?>][position]" class="form-label">Position</label>
                                <input type="text" name="experience[<?= $index ?>][position]" class="form-control" 
                                       value="<?= htmlspecialchars($exp['position'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="experience[<?= $index ?>][company]" class="form-label">Company</label>
                                <input type="text" name="experience[<?= $index ?>][company]" class="form-control" 
                                       value="<?= htmlspecialchars($exp['company'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="experience[<?= $index ?>][start_date]" class="form-label">Start Date</label>
                                <input type="text" name="experience[<?= $index ?>][start_date]" class="form-control" 
                                       value="<?= htmlspecialchars($exp['start_date'] ?? '') ?>" 
                                       placeholder="e.g., June 2018" required>
                            </div>
                            <div class="form-group">
                                <label for="experience[<?= $index ?>][end_date]" class="form-label">End Date</label>
                                <input type="text" name="experience[<?= $index ?>][end_date]" class="form-control" 
                                       value="<?= htmlspecialchars($exp['end_date'] ?? '') ?>" 
                                       placeholder="e.g., Present or May 2022">
                            </div>
                            <div class="form-group">
                                <label for="experience[<?= $index ?>][description]" class="form-label">Description</label>
                                <textarea name="experience[<?= $index ?>][description]" class="form-control"><?= htmlspecialchars($exp['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-field" onclick="addExperienceField()">
                    <i class="fas fa-plus"></i> Add Experience
                </button>
                
                <h2><i class="fas fa-quote-left"></i> Testimonials</h2>
                <div id="testimonials-container">
                    <?php foreach ($profile['testimonials'] as $index => $testimonial): ?>
                        <div class="dynamic-field">
                            <button type="button" class="remove-field" onclick="removeField(this)">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="form-group">
                                <label for="testimonials[<?= $index ?>][text]" class="form-label">Testimonial Text</label>
                                <textarea name="testimonials[<?= $index ?>][text]" class="form-control" required><?= htmlspecialchars($testimonial['text'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="testimonials[<?= $index ?>][author_name]" class="form-label">Author Name</label>
                                <input type="text" name="testimonials[<?= $index ?>][author_name]" class="form-control" 
                                       value="<?= htmlspecialchars($testimonial['author_name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="testimonials[<?= $index ?>][author_position]" class="form-label">Author Position</label>
                                <input type="text" name="testimonials[<?= $index ?>][author_position]" class="form-control" 
                                       value="<?= htmlspecialchars($testimonial['author_position'] ?? '') ?>" 
                                       placeholder="e.g., CEO at Company Inc.">
                            </div>
                            <div class="form-group">
                                <label for="testimonials[<?= $index ?>][author_photo]" class="form-label">Author Photo URL</label>
                                <input type="text" name="testimonials[<?= $index ?>][author_photo]" class="form-control" 
                                       value="<?= htmlspecialchars($testimonial['author_photo'] ?? '') ?>" 
                                       placeholder="Leave blank for default avatar">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-field" onclick="addTestimonialField()">
                    <i class="fas fa-plus"></i> Add Testimonial
                </button>
                
                <div class="form-group" style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Profile
                    </button>
                    <a href="professional.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            
            if (file) {
                reader.readAsDataURL(file);
            }
        }
        
        function addExperienceField() {
            const container = document.getElementById('experience-container');
            const index = container.children.length;
            
            const fieldHTML = `
                <div class="dynamic-field">
                    <button type="button" class="remove-field" onclick="removeField(this)">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="form-group">
                        <label for="experience[${index}][position]" class="form-label">Position</label>
                        <input type="text" name="experience[${index}][position]" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="experience[${index}][company]" class="form-label">Company</label>
                        <input type="text" name="experience[${index}][company]" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="experience[${index}][start_date]" class="form-label">Start Date</label>
                        <input type="text" name="experience[${index}][start_date]" class="form-control" 
                               placeholder="e.g., June 2018" required>
                    </div>
                    <div class="form-group">
                        <label for="experience[${index}][end_date]" class="form-label">End Date</label>
                        <input type="text" name="experience[${index}][end_date]" class="form-control" 
                               placeholder="e.g., Present or May 2022">
                    </div>
                    <div class="form-group">
                        <label for="experience[${index}][description]" class="form-label">Description</label>
                        <textarea name="experience[${index}][description]" class="form-control"></textarea>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', fieldHTML);
        }
        
        function addTestimonialField() {
            const container = document.getElementById('testimonials-container');
            const index = container.children.length;
            
            const fieldHTML = `
                <div class="dynamic-field">
                    <button type="button" class="remove-field" onclick="removeField(this)">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="form-group">
                        <label for="testimonials[${index}][text]" class="form-label">Testimonial Text</label>
                        <textarea name="testimonials[${index}][text]" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="testimonials[${index}][author_name]" class="form-label">Author Name</label>
                        <input type="text" name="testimonials[${index}][author_name]" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="testimonials[${index}][author_position]" class="form-label">Author Position</label>
                        <input type="text" name="testimonials[${index}][author_position]" class="form-control" 
                               placeholder="e.g., CEO at Company Inc.">
                    </div>
                    <div class="form-group">
                        <label for="testimonials[${index}][author_photo]" class="form-label">Author Photo URL</label>
                        <input type="text" name="testimonials[${index}][author_photo]" class="form-control" 
                               placeholder="Leave blank for default avatar">
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', fieldHTML);
        }
        
        function removeField(button) {
            if (confirm('Are you sure you want to remove this item?')) {
                button.closest('.dynamic-field').remove();
            }
        }
        
        // Initialize the page with at least one empty field if none exist
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('experience-container').children.length === 0) {
                addExperienceField();
            }
            
            if (document.getElementById('testimonials-container').children.length === 0) {
                addTestimonialField();
            }
        });
    </script>
</body>
</html>