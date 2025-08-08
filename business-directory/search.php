<?php
require 'includes/auth.php';
require 'includes/functions.php';

$searchTerm = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$location = isset($_GET['location']) ? sanitize($_GET['location']) : '';

$businesses = getBusinesses(true, false, $categoryId, $searchTerm);
$categories = getCategories();

// Filter by location if specified
if ($location) {
    $businesses = array_filter($businesses, function($business) use ($location) {
        return stripos($business['location'], $location) !== false;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="search-container">
            <form method="GET" action="search.php" class="search-form">
                <div class="form-group">
                    <input type="text" name="q" placeholder="Search businesses..." value="<?php echo $searchTerm; ?>">
                </div>
                
                <div class="form-group">
                    <input type="text" name="location" placeholder="Location (city/town)" value="<?php echo $location; ?>">
                </div>
                
                <div class="form-group">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit">Search</button>
            </form>
        </div>
        
        <div class="search-results">
            <h2>Search Results</h2>
            
            <?php if (empty($businesses)): ?>
                <p>No businesses found matching your criteria.</p>
            <?php else: ?>
                <div class="business-grid">
                    <?php foreach ($businesses as $business): ?>
                        <div class="business-card">
                            <?php if (!empty($business['logo'])): ?>
                                <img src="<?php echo $business['logo']; ?>" alt="<?php echo $business['name']; ?>" class="business-logo">
                            <?php endif; ?>
                            
                            <div class="business-info">
                                <h3><?php echo $business['name']; ?></h3>
                                <p class="category"><?php echo $business['category_name']; ?></p>
                                <p class="location"><?php echo $business['location']; ?></p>
                                
                                <?php if (!empty($business['features'])): ?>
                                    <div class="features">
                                        <?php foreach (array_slice($business['features'], 0, 3) as $feature): ?>
                                            <span class="feature-tag"><?php echo $feature; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="listing.php?id=<?php echo $business['id']; ?>" class="view-btn">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>