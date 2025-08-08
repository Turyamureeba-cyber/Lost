<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_photo'] = $user['avatar_url'] ?? 'default.jpg';
        }
    } catch (PDOException $e) {
        // Continue without user data if there's an error
    }
}

// Initialize default values
$search = '';
$locationSearch = '';
$sort = '';
$page = 1;
$perPage = 10;
$isFiltered = false;
$locationEnabled = false;
$userLatitude = null;
$userLongitude = null;

// Check if this is a filtered request
if (isset($_GET['search']) || isset($_GET['location']) || isset($_GET['sort']) || isset($_GET['page'])) {
    $isFiltered = true;
    $search = $_GET['search'] ?? '';
    $locationSearch = $_GET['location'] ?? '';
    $sort = $_GET['sort'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
}

// Check if location is enabled (from GPS)
if (isset($_GET['lat']) && isset($_GET['lng'])) {
    $locationEnabled = true;
    $userLatitude = $_GET['lat'];
    $userLongitude = $_GET['lng'];
}

// Validate sort options if provided
$validSorts = ['newest', 'oldest', 'rating', 'name_asc', 'name_desc', 'distance'];
if (!empty($sort) && !in_array($sort, $validSorts)) {
    $sort = '';
}

// Build ORDER BY clause
$orderBy = 'b.created_at DESC'; // Default sort
if (!empty($sort)) {
    $orderBy = match($sort) {
        'newest' => 'b.created_at DESC',
        'oldest' => 'b.created_at ASC',
        'rating' => 'b.rating DESC, b.review_count DESC',
        'name_asc' => 'b.name ASC',
        'name_desc' => 'b.name DESC',
        'distance' => $locationEnabled ? 
            "(6371 * acos(cos(radians($userLatitude)) * cos(radians(b.latitude)) * cos(radians(b.longitude) - radians($userLongitude)) + sin(radians($userLatitude)) * sin(radians(b.latitude))))" : 
            'b.created_at DESC',
        default => 'b.created_at DESC'
    };
}

// Build WHERE conditions
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(b.name LIKE ? OR b.description LIKE ?)";
    $params = array_merge($params, array_fill(0, 2, "%$search%"));
}

if (!empty($locationSearch)) {
    $whereConditions[] = "(r.region_name LIKE ? OR d.district_name LIKE ? OR c.county_name LIKE ? OR b.city LIKE ? OR b.address LIKE ?)";
    $params = array_merge($params, array_fill(0, 5, "%$locationSearch%"));
}

// For logged-in users on dashboard
if ($isLoggedIn && strpos($_SERVER['REQUEST_URI'], '/business/') !== false) {
    $whereConditions[] = "b.owner_id = ?";
    $params[] = $_SESSION['user_id'];
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM businesses b
                  LEFT JOIN region r ON b.region_id = r.region_id
                  LEFT JOIN district d ON b.district_id = d.district_id
                  LEFT JOIN county c ON b.county_id = c.county_id
                  $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalBusinesses = $countStmt->fetchColumn();
    $totalPages = ceil($totalBusinesses / $perPage);

    // Calculate offset
    $offset = ($page - 1) * $perPage;

    // Get paginated businesses with location names
    $query = "
        SELECT b.*, 
               r.region_name, 
               d.district_name, 
               c.county_name";
    
    if ($locationEnabled) {
        $query .= ", (6371 * acos(cos(radians($userLatitude)) * cos(radians(b.latitude)) * 
                  cos(radians(b.longitude) - radians($userLongitude)) + 
                  sin(radians($userLatitude)) * sin(radians(b.latitude)))) as distance";
    }
    
    $query .= "
        FROM businesses b
        LEFT JOIN region r ON b.region_id = r.region_id
        LEFT JOIN district d ON b.district_id = d.district_id
        LEFT JOIN county c ON b.county_id = c.county_id
        $whereClause
        ORDER BY $orderBy
        LIMIT $perPage OFFSET $offset
    ";
    
    $businessStmt = $pdo->prepare($query);
    $businessStmt->execute($params);
    $businesses = $businessStmt->fetchAll();

    // Get all images for the displayed businesses
    $businessIds = array_column($businesses, 'id');
    $businessImages = [];
    
    if (!empty($businessIds)) {
        $placeholders = implode(',', array_fill(0, count($businessIds), '?'));
        $imageQuery = "SELECT * FROM business_images WHERE business_id IN ($placeholders) ORDER BY is_primary DESC";
        $imageStmt = $pdo->prepare($imageQuery);
        $imageStmt->execute($businessIds);
        
        while ($image = $imageStmt->fetch()) {
            $businessImages[$image['business_id']][] = $image;
        }
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = $isLoggedIn ? "My Businesses | UrbanPulse" : "Business Directory | UrbanPulse";
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
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 1.8rem;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: var(--primary);
        }
        
        .hero {
            padding: 80px 0;
            text-align: center;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/1.avif');
            background-size: cover;
            background-position: center;
            color: white;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .search-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            transform: translateY(-50px);
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .location-toggle {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .location-toggle label {
            margin-left: 8px;
            cursor: pointer;
        }
        
        .sort-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .sort-options h2 {
            font-size: 1.5rem;
            color: var(--dark);
        }
        
        .sort-options select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .business-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .business-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .business-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .business-image {
            height: 200px;
            background-color: var(--light-gray);
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .business-rating {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .business-rating i {
            color: var(--warning);
            margin-right: 5px;
        }
        
        .distance-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: var(--primary);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .distance-badge i {
            margin-right: 5px;
        }
        
        .business-details {
            padding: 20px;
        }
        
        .business-category {
            display: inline-block;
            background-color: var(--primary-light);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            margin-bottom: 10px;
        }
        
        .business-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .business-location {
            color: var(--gray);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .business-location i {
            margin-right: 5px;
        }
        
        .business-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .business-meta span {
            display: flex;
            align-items: center;
        }
        
        .business-meta i {
            margin-right: 5px;
        }
        
        footer {
            background-color: var(--dark);
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .footer-section h3 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: white;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 10px;
        }
        
        .footer-section ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section ul li a:hover {
            color: white;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            color: white;
            font-size: 1.2rem;
            transition: color 0.3s;
        }
        
        .social-links a:hover {
            color: var(--primary);
        }
        
        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #ccc;
            font-size: 0.9rem;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
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
        
        .loading-spinner {
            display: none;
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            margin: 0 auto;
            border: 4px solid var(--primary-light);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 30px 0;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        /* Autocomplete styles */
        .autocomplete {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .autocomplete-items {
            position: absolute;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            border-top: none;
            z-index: 99;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .autocomplete-items div {
            padding: 10px;
            cursor: pointer;
            background-color: #fff;
            border-bottom: 1px solid #d4d4d4;
        }
        
        .autocomplete-items div:hover {
            background-color: #e9e9e9;
        }
        
        .autocomplete-active {
            background-color: var(--primary) !important;
            color: #ffffff;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 20px;
                justify-content: center;
            }
            
            nav ul li {
                margin: 0 10px;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .sort-options {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .sort-options h2 {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">
                <i class="fas fa-building"></i>
                <span>UrbanPulse</span>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="browse.php">Browse</a></li>
                    <li><a href="about.php">About</a></li>
                    <?php if ($isLoggedIn): ?>
                        <li><a href="business/index.php" class="btn btn-primary">Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="auth/login.php" class="btn btn-primary">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        
        <div class="container hero">
            <h1>Discover Local Businesses Near You</h1>
            <p>Find the best shops, services, and professionals in your area</p>
        </div>
    </header>
    
    <main class="container">
        <div class="search-container">
            <form class="search-form" id="searchForm" onsubmit="return false;">
                <div class="form-group">
                    <label for="search">Business Name or Description</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           placeholder="e.g. Restaurant, Hotel, Clinic..." value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="form-group autocomplete">
                    <label for="location">Location (Region, District, or County)</label>
                    <input type="text" id="location" name="location" class="form-control" 
                           placeholder="e.g. Kampala, Central, Nakawa..." value="<?= htmlspecialchars($locationSearch) ?>">
                </div>
                
                <div class="form-group" style="align-self: flex-end;">
                    <button type="button" class="btn btn-primary" style="width: 100%;" onclick="performLiveSearch()">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <div class="location-toggle">
                        <input type="checkbox" id="useLocation" <?= $locationEnabled ? 'checked' : '' ?>>
                        <label for="useLocation">Use my location</label>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="sort-options">
            <h2><?= $isFiltered ? 'Search Results' : 'Featured Businesses' ?></h2>
            <div>
                <label for="sort">Sort by:</label>
                <select id="sort" name="sort">
                    <option value="">Default</option>
                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                    <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                    <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                    <?php if ($locationEnabled): ?>
                        <option value="distance" <?= $sort == 'distance' ? 'selected' : '' ?>>Distance</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <p>Loading results...</p>
        </div>
        
        <div class="business-grid" id="businessGrid">
            <?php if (count($businesses) > 0): ?>
                <?php foreach ($businesses as $business): ?>
                    <?php 
                    $imageUrl = 'assets/images/1.avif';
                    if (!empty($businessImages[$business['id']])) {
                        $imageUrl = 'assets/uploads/business/' . $businessImages[$business['id']][0]['image_url'];
                    }
                    ?>
                    <div class="business-card">
                        <div class="business-image" style="background-image: url('<?= htmlspecialchars($imageUrl) ?>');">
                            <?php if ($business['rating'] > 0): ?>
                                <span class="business-rating">
                                    <i class="fas fa-star"></i> <?= number_format($business['rating'], 1) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($locationEnabled && isset($business['distance'])): ?>
                                <span class="distance-badge">
                                    <i class="fas fa-map-marker-alt"></i> <?= number_format($business['distance'], 2) ?> km
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="business-details">
                            <span class="business-category">
                                <?= htmlspecialchars($business['county_name'] ?? $business['district_name'] ?? $business['region_name'] ?? 'Location not specified') ?>
                            </span>
                            <h3 class="business-title"><?= htmlspecialchars($business['name']) ?></h3>
                            <p class="business-location">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?= htmlspecialchars($business['city'] . ', ' . $business['address']) ?>
                            </p>
                            <div class="business-meta">
                                <span><i class="fas fa-clock"></i> <?= htmlspecialchars($business['opening_hours'] ?? 'Hours not specified') ?></span>
                                <span><i class="fas fa-phone"></i> <?= htmlspecialchars($business['phone']) ?></span>
                            </div>
                            <a href="business/view-business.php?id=<?= $business['id'] ?>" class="btn btn-primary" style="width: 100%; margin-top: 15px; text-align: center;">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: white; border-radius: 8px;">
                    <h3>No businesses found</h3>
                    <p><?= $isFiltered ? 'Try adjusting your search filters' : 'There are currently no businesses listed' ?></p>
                    <?php if ($isLoggedIn): ?>
                        <a href="add-business.php" class="btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Add Your First Business
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="pagination" id="paginationContainer">
            <?php if ($totalPages > 1): ?>
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="btn">
                        <i class="fas fa-angle-double-left"></i> First
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn">
                        <i class="fas fa-angle-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <span style="margin: 0 15px;">Page <?= $page ?> of <?= $totalPages ?></span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn">
                        Next <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="btn">
                        Last <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <div class="container footer-content">
            <div class="footer-section">
                <h3>About UrbanPulse</h3>
                <p>Connecting communities with local businesses since 2023. Our mission is to help people discover the best local services and help businesses thrive.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="browse.php">Browse Businesses</a></li>
                    <li><a href="add-business.php">Add Your Business</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="container copyright">
            <p>&copy; <?= date('Y') ?> UrbanPulse. All rights reserved.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('search');
        const locationInput = document.getElementById('location');
        const sortSelect = document.getElementById('sort');
        const useLocationCheckbox = document.getElementById('useLocation');
        const businessGrid = document.getElementById('businessGrid');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const paginationContainer = document.getElementById('paginationContainer');
        
        // Autocomplete for location search
        function autocomplete(inp) {
            let currentFocus;
            
            inp.addEventListener("input", function(e) {
                const val = this.value;
                closeAllLists();
                if (!val) return false;
                currentFocus = -1;
                
                // Create autocomplete items container
                const a = document.createElement("DIV");
                a.setAttribute("id", this.id + "autocomplete-list");
                a.setAttribute("class", "autocomplete-items");
                this.parentNode.appendChild(a);
                
                // Fetch suggestions from server
                fetch(`ajax/get_locations.php?term=${encodeURIComponent(val)}`)
                    .then(response => response.json())
                    .then(data => {
                        // Clear previous results
                        a.innerHTML = '';
                        
                        // Add new suggestions
                        data.forEach(item => {
                            const b = document.createElement("DIV");
                            b.innerHTML = `<strong>${item.label}</strong>`;
                            b.innerHTML += `<input type="hidden" value="${item.value}">`;
                            b.addEventListener("click", function() {
                                inp.value = item.label;
                                closeAllLists();
                                performLiveSearch();
                            });
                            a.appendChild(b);
                        });
                    });
            });
            
            // Keyboard navigation
            inp.addEventListener("keydown", function(e) {
                let x = document.getElementById(this.id + "autocomplete-list");
                if (x) x = x.getElementsByTagName("div");
                if (e.keyCode == 40) { // Down arrow
                    currentFocus++;
                    addActive(x);
                } else if (e.keyCode == 38) { // Up arrow
                    currentFocus--;
                    addActive(x);
                } else if (e.keyCode == 13) { // Enter
                    e.preventDefault();
                    if (currentFocus > -1) {
                        if (x) x[currentFocus].click();
                    }
                    performLiveSearch();
                }
            });
            
            function addActive(x) {
                if (!x) return false;
                removeActive(x);
                if (currentFocus >= x.length) currentFocus = 0;
                if (currentFocus < 0) currentFocus = (x.length - 1);
                x[currentFocus].classList.add("autocomplete-active");
            }
            
            function removeActive(x) {
                for (let i = 0; i < x.length; i++) {
                    x[i].classList.remove("autocomplete-active");
                }
            }
            
            function closeAllLists(elmnt) {
                const x = document.getElementsByClassName("autocomplete-items");
                for (let i = 0; i < x.length; i++) {
                    if (elmnt != x[i] && elmnt != inp) {
                        x[i].parentNode.removeChild(x[i]);
                    }
                }
            }
            
            document.addEventListener("click", function(e) {
                closeAllLists(e.target);
            });
        }
        
        // Initialize autocomplete
        autocomplete(locationInput);
        
        // Function to get user's location
        function getLocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject('Geolocation is not supported by your browser');
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        resolve({
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        });
                    },
                    (error) => {
                        reject('Unable to retrieve your location: ' + error.message);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    }
                );
            });
        }
        
        // Function to perform live search
        async function performLiveSearch() {
            const searchTerm = searchInput.value;
            const locationTerm = locationInput.value;
            const sortValue = sortSelect.value;
            const useLocation = useLocationCheckbox.checked;
            
            // Show loading spinner and hide current results
            loadingSpinner.style.display = 'block';
            businessGrid.style.display = 'none';
            paginationContainer.style.display = 'none';
            
            try {
                let locationData = {};
                if (useLocation) {
                    try {
                        locationData = await getLocation();
                    } catch (error) {
                        alert(error);
                        useLocationCheckbox.checked = false;
                        locationData = {};
                    }
                }
                
                // Build query string for GET request
                const params = new URLSearchParams();
                params.append('search', searchTerm);
                params.append('location', locationTerm);
                params.append('sort', sortValue);
                
                if (useLocation && locationData.lat && locationData.lng) {
                    params.append('lat', locationData.lat);
                    params.append('lng', locationData.lng);
                }
                
                // Send AJAX request
                const response = await fetch('live-search.php?' + params.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const html = await response.text();
                businessGrid.innerHTML = html;
                loadingSpinner.style.display = 'none';
                businessGrid.style.display = 'grid';
                paginationContainer.style.display = 'none'; // Hide pagination for live search
                
                // Update URL without reloading the page
                const newUrl = window.location.pathname + '?' + params.toString();
                window.history.pushState({ path: newUrl }, '', newUrl);
                
                // Update sort options to include distance if location is enabled
                if (useLocation && locationData.lat && locationData.lng) {
                    const distanceOption = document.querySelector('#sort option[value="distance"]');
                    if (!distanceOption) {
                        const option = document.createElement('option');
                        option.value = 'distance';
                        option.textContent = 'Distance';
                        sortSelect.appendChild(option);
                    }
                } else {
                    const distanceOption = document.querySelector('#sort option[value="distance"]');
                    if (distanceOption) {
                        distanceOption.remove();
                    }
                }
                
            } catch (error) {
                console.error('Error:', error);
                businessGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: white; border-radius: 8px;"><h3>Error loading results</h3><p>Please try again later</p></div>';
                loadingSpinner.style.display = 'none';
                businessGrid.style.display = 'grid';
                paginationContainer.style.display = 'none';
            }
        }
        
        // Event listeners for live search
        searchInput.addEventListener('input', function() {
            if (this.value.length > 0 || this.value.length === 0) {
                performLiveSearch();
            }
        });
        
        locationInput.addEventListener('input', function() {
            if (this.value.length > 0 || this.value.length === 0) {
                performLiveSearch();
            }
        });
        
        // Add debounce to prevent too many requests
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                performLiveSearch();
            }, 300);
        });
        
        locationInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                performLiveSearch();
            }, 300);
        });
        
        // Also trigger search when sort or location changes
        sortSelect.addEventListener('change', performLiveSearch);
        useLocationCheckbox.addEventListener('change', performLiveSearch);
        
        // Initial load if there are search parameters
        if (searchInput.value || locationInput.value || sortSelect.value || <?= $locationEnabled ? 'true' : 'false' ?>) {
            performLiveSearch();
        }
    });
    </script>
</body>
</html>