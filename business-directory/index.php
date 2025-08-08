<?php
require 'includes/auth.php';
require 'includes/db.php';

// Function to get businesses
function getBusinesses($featured = false) {
    global $db;
    
    $sql = "SELECT b.*, u.username as owner 
            FROM businesses b
            JOIN users u ON b.user_id = u.id
            WHERE b.verified = 1";
    
    if ($featured) {
        $sql .= " AND b.featured = 1";
    }
    
    $sql .= " ORDER BY b.created_at DESC";
    
    // Use the Database class's preparedQuery method
    $stmt = $db->preparedQuery($sql);
    
    if ($stmt) {
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("Database error: " . $db->error());
        return [];
    }
}

$featuredBusinesses = getBusinesses(true); // Get featured businesses
$recentBusinesses = getBusinesses(); // Get all verified businesses
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Directory - Find Local Businesses</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>Discover Local Businesses</h1>
                <p>Find and connect with businesses in your area</p>
                
                <!-- Search Form -->
                <form action="search.php" method="GET" class="hero-search">
                    <div class="search-group">
                        <label for="location"><i class="fas fa-map-marker-alt"></i></label>
                        <input type="text" id="location" name="location" placeholder="Search by location">
                    </div>
                    
                    <div class="search-group">
                        <label for="category"><i class="fas fa-tag"></i></label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <option value="restaurant">Restaurants</option>
                            <option value="retail">Retail</option>
                            <option value="service">Services</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>
        </section>

        <!-- Featured Businesses -->
        <section class="section">
            <h2 class="section-title">Featured Businesses</h2>
            <div class="business-grid">
                <?php if (!empty($featuredBusinesses)): ?>
                    <?php foreach ($featuredBusinesses as $business): ?>
                        <div class="business-card">
                            <div class="business-image">
                                <img src="<?php echo htmlspecialchars($business['logo'] ?? 'assets/images/business-placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($business['name']); ?>">
                                <span class="featured-badge">Featured</span>
                            </div>
                            <div class="business-info">
                                <h3><?php echo htmlspecialchars($business['name']); ?></h3>
                                <p class="location">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($business['location']); ?>
                                </p>
                                <div class="contact">
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($business['phone']); ?></p>
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($business['email']); ?></p>
                                </div>
                                <a href="listing.php?id=<?php echo (int)$business['id']; ?>" class="view-btn">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-results">No featured businesses available at the moment.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Recent Businesses -->
        <section class="section">
            <h2 class="section-title">Recently Added</h2>
            <div class="business-grid">
                <?php if (!empty($recentBusinesses)): ?>
                    <?php foreach ($recentBusinesses as $business): ?>
                        <div class="business-card">
                            <div class="business-image">
                                <img src="<?php echo htmlspecialchars($business['logo'] ?? 'assets/images/business-placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($business['name']); ?>">
                            </div>
                            <div class="business-info">
                                <h3><?php echo htmlspecialchars($business['name']); ?></h3>
                                <p class="location">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($business['location']); ?>
                                </p>
                                <div class="contact">
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($business['phone']); ?></p>
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($business['email']); ?></p>
                                </div>
                                <a href="listing.php?id=<?php echo (int)$business['id']; ?>" class="view-btn">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-results">No businesses available at the moment.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="cta">
            <div class="cta-content">
                <h2>Own a Business?</h2>
                <p>List your business with us and reach more customers</p>
                <?php if (isLoggedIn()): ?>
                    <a href="add-business.php" class="cta-btn">Add Your Business</a>
                <?php else: ?>
                    <a href="register.php" class="cta-btn">Register Now</a>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>