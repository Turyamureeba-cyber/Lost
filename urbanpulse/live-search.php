<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// Get search parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? '';
$userLatitude = $_GET['lat'] ?? null;
$userLongitude = $_GET['lng'] ?? null;
$locationEnabled = !empty($userLatitude) && !empty($userLongitude);

// Validate and sanitize inputs
$category = is_numeric($category) ? (int)$category : '';
$sort = in_array($sort, ['newest', 'oldest', 'rating', 'name_asc', 'name_desc', 'distance']) ? $sort : '';

// Build the query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(b.name LIKE ? OR b.address LIKE ? OR b.city LIKE ? OR c.name LIKE ? OR c.slug LIKE ?)";
    $params = array_merge($params, array_fill(0, 5, "%$search%"));
}

if (!empty($category)) {
    $whereConditions[] = "b.category_id = ?";
    $params[] = $category;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Order by
$orderBy = 'b.created_at DESC';
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

try {
    // Get businesses
    $query = "
        SELECT b.*, c.name as category_name, c.slug as category_slug";
    
    if ($locationEnabled) {
        $query .= ", (6371 * acos(cos(radians($userLatitude)) * cos(radians(b.latitude)) * 
                  cos(radians(b.longitude) - radians($userLongitude)) + 
                  sin(radians($userLatitude)) * sin(radians(b.latitude)))) as distance";
    }
    
    $query .= "
        FROM businesses b
        LEFT JOIN categories c ON b.category_id = c.id
        $whereClause
        ORDER BY $orderBy
        LIMIT 20
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $businesses = $stmt->fetchAll();

    // Get categories for reference
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

    // Get images
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
    
    // Output the business grid
    if (count($businesses) > 0): ?>
        <?php foreach ($businesses as $business): ?>
            <?php 
            $imageUrl = 'assets/images/default-business.jpg';
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
                    <span class="business-category"><?= htmlspecialchars($business['category_name'] ?? 'Uncategorized') ?></span>
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
            <p>Try adjusting your search filters</p>
        </div>
    <?php endif;
    
} catch (PDOException $e) {
    echo '<div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: white; border-radius: 8px;">
            <h3>Database Error</h3>
            <p>Please try again later</p>
          </div>';
}
?>