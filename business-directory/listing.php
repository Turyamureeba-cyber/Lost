<?php
require 'includes/auth.php';
require 'includes/functions.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$businessId = (int)$_GET['id'];
$business = getBusinessById($businessId);

if (!$business) {
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $business['name']; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="business-detail">
            <div class="business-header">
                <?php if (!empty($business['logo'])): ?>
                    <img src="<?php echo $business['logo']; ?>" alt="<?php echo $business['name']; ?>" class="business-logo-large">
                <?php endif; ?>
                
                <div class="business-title">
                    <h1><?php echo $business['name']; ?></h1>
                    <p class="category"><?php echo $business['category_name']; ?></p>
                    <p class="location"><?php echo $business['location']; ?></p>
                    
                    <div class="business-actions">
                        <a href="<?php echo $business['website']; ?>" target="_blank" class="action-btn">Visit Website</a>
                        <a href="tel:<?php echo $business['phone']; ?>" class="action-btn">Call Now</a>
                    </div>
                </div>
            </div>
            
            <div class="business-content">
                <div class="business-description">
                    <h2>About</h2>
                    <p><?php echo nl2br($business['description']); ?></p>
                    
                    <h2>Address</h2>
                    <p><?php echo nl2br($business['address']); ?></p>
                </div>
                
                <div class="business-sidebar">
                    <div class="contact-card">
                        <h3>Contact Information</h3>
                        <p><strong>Phone:</strong> <?php echo $business['phone']; ?></p>
                        <p><strong>Email:</strong> <?php echo $business['email']; ?></p>
                        
                        <?php if (!empty($business['social_media'])): ?>
                            <div class="social-links">
                                <h4>Social Media</h4>
                                <?php foreach ($business['social_media'] as $platform => $url): ?>
                                    <a href="<?php echo $url; ?>" target="_blank" class="social-icon">
                                        <?php echo ucfirst($platform); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($business['opening_hours'])): ?>
                        <div class="hours-card">
                            <h3>Opening Hours</h3>
                            <table>
                                <?php foreach ($business['opening_hours'] as $day => $hours): ?>
                                    <tr>
                                        <td><?php echo ucfirst($day); ?></td>
                                        <td><?php echo $hours['open'] . ' - ' . $hours['close']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($business['images'])): ?>
                <div class="business-gallery">
                    <h2>Gallery</h2>
                    <div class="gallery-grid">
                        <?php foreach ($business['images'] as $image): ?>
                            <div class="gallery-item">
                                <img src="<?php echo $image; ?>" alt="<?php echo $business['name']; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($business['features'])): ?>
                <div class="business-features">
                    <h2>Features & Amenities</h2>
                    <ul class="features-list">
                        <?php foreach ($business['features'] as $feature): ?>
                            <li><?php echo $feature; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>