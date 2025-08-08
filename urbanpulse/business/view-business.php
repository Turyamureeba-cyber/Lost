<?php
session_start();

// Check if business ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../index.php');
    exit();
}

$businessId = $_GET['id'];

// Include database connection
require_once __DIR__ . '/../includes/db.php';

try {
    // Get business details with category name
    $businessStmt = $pdo->prepare("
        SELECT b.*, c.name as category_name 
        FROM businesses b
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.id = ?
    ");
    $businessStmt->execute([$businessId]);
    $business = $businessStmt->fetch();
    
    if (!$business) {
        header('Location: ../index.php');
        exit();
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = htmlspecialchars($business['name'] ?? 'Business') . " | UrbanPulse";

// Generate shareable content
$shareContent = urlencode("Check out " . $business['name'] . " at " . $business['address'] . ", " . $business['city'] . "\n\n" . 
                 ($business['description'] ? substr(strip_tags($business['description']), 0, 100) . "..." : ""));
$shareUrl = urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
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
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .back-link:hover {
            color: var(--secondary);
        }
        
        .back-link i {
            margin-right: 0.5rem;
        }
        
        /* Business Card Styles */
        .business-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .business-header {
            position: relative;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .business-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .business-title {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .business-category {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .business-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-left: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .status-featured {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success);
        }
        
        .status-standard {
            background-color: rgba(108, 117, 125, 0.2);
            color: var(--gray);
        }
        
        .business-rating {
            display: flex;
            align-items: center;
            margin-top: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }
        
        .rating-count {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .business-body {
            padding: 2rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 0.75rem;
            color: var(--primary);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: var(--light);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }
        
        .info-label {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .info-value a {
            color: var(--primary);
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .info-value a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
        
        .description-card {
            background: var(--light);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .map-container {
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 2rem;
            background: var(--light);
        }
        
        .map-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .hours-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        
        .hours-table th, 
        .hours-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .hours-table th {
            font-weight: 500;
            color: var(--gray);
        }
        
        .hours-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .business-title {
                font-size: 1.5rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .map-container {
                height: 300px;
            }
        }
        
        /* Add these new styles */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
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
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        @media print {
            .back-link, .action-buttons {
                display: none;
            }
            
            body {
                background-color: white;
                color: black;
            }
            
            .business-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Link -->
        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>
        
        <!-- Business Card -->
        <div class="business-card">
            <div class="business-header">
                <h1 class="business-title"><?= htmlspecialchars($business['name']) ?></h1>
                <span class="business-category"><?= htmlspecialchars($business['category_name'] ?? 'Uncategorized') ?></span>
                <span class="business-status <?= $business['featured'] ? 'status-featured' : 'status-standard' ?>">
                    <?= $business['featured'] ? 'Featured' : 'Standard' ?>
                </span>
                
                <?php if ($business['rating'] > 0): ?>
                    <div class="business-rating">
                        <div class="rating-stars">
                            <?= str_repeat('★', floor($business['rating'])) ?><?= str_repeat('☆', 5 - floor($business['rating'])) ?>
                        </div>
                        <span class="rating-count">(<?= $business['review_count'] ?> reviews)</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="business-body">
                <!-- Basic Information Grid -->
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?= htmlspecialchars($business['address']) ?></div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label">City</div>
                        <div class="info-value"><?= htmlspecialchars($business['city']) ?></div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label">Phone</div>
                        <div class="info-value">
                            <a href="tel:<?= htmlspecialchars($business['phone']) ?>"><?= htmlspecialchars($business['phone']) ?></a>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <?php if ($business['email']): ?>
                                <a href="mailto:<?= htmlspecialchars($business['email']) ?>"><?= htmlspecialchars($business['email']) ?></a>
                            <?php else: ?>
                                Not provided
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label">Website</div>
                        <div class="info-value">
                            <?php if ($business['website']): ?>
                                <a href="<?= htmlspecialchars($business['website']) ?>" target="_blank"><?= htmlspecialchars($business['website']) ?></a>
                            <?php else: ?>
                                Not provided
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Description Section -->
                <h2 class="section-title"><i class="fas fa-align-left"></i> Description</h2>
                <div class="description-card">
                    <?php if ($business['description']): ?>
                        <?= nl2br(htmlspecialchars($business['description'])) ?>
                    <?php else: ?>
                        <p style="color: var(--gray); font-style: italic;">No description provided for this business.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Location Map Section -->
                <h2 class="section-title"><i class="fas fa-map-marked-alt"></i> Location</h2>
                <div class="map-container">
                    <?php if ($business['latitude'] && $business['longitude']): ?>
                        <iframe class="map-iframe" 
                            src="https://www.openstreetmap.org/export/embed.html?bbox=<?= $business['longitude']-0.01 ?>%2C<?= $business['latitude']-0.01 ?>%2C<?= $business['longitude']+0.01 ?>%2C<?= $business['latitude']+0.01 ?>&amp;layer=mapnik&amp;marker=<?= $business['latitude'] ?>%2C<?= $business['longitude'] ?>"
                            title="<?= htmlspecialchars($business['name']) ?> Location">
                        </iframe>
                        <br/>
                        <small>
                            <a href="https://www.openstreetmap.org/?mlat=<?= $business['latitude'] ?>&amp;mlon=<?= $business['longitude'] ?>#map=16/<?= $business['latitude'] ?>/<?= $business['longitude'] ?>" target="_blank">
                                View Larger Map
                            </a>
                        </small>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                            <i class="fas fa-map-marker-alt" style="font-size: 3rem; color: var(--gray); margin-bottom: 1rem;"></i>
                            <p style="color: var(--gray);">No location coordinates provided for this business</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Opening Hours Section -->
                <h2 class="section-title"><i class="fas fa-clock"></i> Opening Hours</h2>
                <?php if ($business['opening_hours']): ?>
                    <?php 
                        $hours = json_decode($business['opening_hours'], true);
                        if ($hours && is_array($hours)):
                    ?>
                        <table class="hours-table">
                            <tbody>
                                <?php foreach ($hours as $day => $time): ?>
                                    <tr>
                                        <th><?= htmlspecialchars(ucfirst($day)) ?></th>
                                        <td><?= $time ? htmlspecialchars($time) : 'Closed' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: var(--gray); font-style: italic;">Opening hours information is not properly formatted.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="color: var(--gray); font-style: italic;">No opening hours information provided.</p>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="mailto:?subject=Check%20out%20<?= urlencode($business['name']) ?>&body=<?= $shareContent ?>%0A%0A<?= $shareUrl ?>" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> Email This
                    </a>
                    
                    <a href="https://wa.me/?text=<?= $shareContent ?>%20<?= $shareUrl ?>" target="_blank" class="btn btn-success">
                        <i class="fab fa-whatsapp"></i> Share on WhatsApp
                    </a>
                    
                    <button onclick="window.print()" class="btn btn-outline">
                        <i class="fas fa-print"></i> Print Details
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Print functionality is handled by the browser's native print dialog
        // No additional JavaScript needed for basic printing
        
        // For better print formatting, we've added @media print styles in the CSS
    </script>
</body>
</html>