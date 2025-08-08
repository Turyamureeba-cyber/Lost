<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Initialize variables
$errors = [];
$formData = [
    'name' => '',
    'description' => '',
    'category_id' => '',
    'address' => '',
    'city' => '',
    'phone' => '',
    'email' => '',
    'website' => '',
    'opening_hours' => ''
];

// Get categories for dropdown
try {
    $categoriesStmt = $pdo->query("SELECT * FROM categories");
    $categories = $categoriesStmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['description'] = trim($_POST['description'] ?? '');
    $formData['category_id'] = intval($_POST['category_id'] ?? 0);
    $formData['address'] = trim($_POST['address'] ?? '');
    $formData['city'] = trim($_POST['city'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['email'] = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $formData['website'] = filter_var($_POST['website'] ?? '', FILTER_SANITIZE_URL);
    $formData['opening_hours'] = trim($_POST['opening_hours'] ?? '');
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;

    // Validate required fields
    if (empty($formData['name'])) {
        $errors['name'] = 'Business name is required';
    }
    
    if (empty($formData['category_id'])) {
        $errors['category_id'] = 'Category is required';
    }
    
    if (empty($formData['phone'])) {
        $errors['phone'] = 'Phone number is required';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO businesses (
                    name, description, category_id, address, city, phone, 
                    email, website, latitude, longitude, opening_hours, owner_id
                ) VALUES (
                    :name, :description, :category_id, :address, :city, :phone, 
                    :email, :website, :latitude, :longitude, :opening_hours, :owner_id
                )
            ");
            
            $stmt->execute([
                ':name' => $formData['name'],
                ':description' => $formData['description'],
                ':category_id' => $formData['category_id'],
                ':address' => $formData['address'],
                ':city' => $formData['city'],
                ':phone' => $formData['phone'],
                ':email' => $formData['email'],
                ':website' => $formData['website'],
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':opening_hours' => $formData['opening_hours'],
                ':owner_id' => $_SESSION['user_id']
            ]);
            
            $businessId = $pdo->lastInsertId();
            
            // Redirect to business page or dashboard
            $_SESSION['success_message'] = 'Business added successfully!';
            header("Location: index.php");
            exit();
            
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Add Business | UrbanPulse";
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
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .back-btn:hover {
            color: var(--secondary);
        }
        
        .page-title {
            font-size: 1.8rem;
            color: var(--dark);
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .required:after {
            content: " *";
            color: var(--danger);
        }
        
        input[type="text"],
        input[type="tel"],
        input[type="email"],
        input[type="url"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="tel"]:focus,
        input[type="email"]:focus,
        input[type="url"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .error-message {
            color: var(--danger);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .location-wrapper {
            display: flex;
            gap: 1rem;
        }
        
        .location-input {
            flex: 1;
        }
        
        .get-location-btn {
            background-color: var(--primary-light);
            color: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 0 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .get-location-btn:hover {
            background-color: rgba(67, 97, 238, 0.2);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
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
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--gray);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--light-gray);
        }
        
        .map-container {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 1rem;
            border: 1px solid var(--border-color);
            display: none;
        }
        
        .map-container.active {
            display: block;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="../index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <h1 class="page-title">Add New Business</h1>
        </div>
        
        <div class="form-card">
            <form id="business-form" method="POST" action="add-business.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name" class="required">Business Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($formData['name']) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <span class="error-message"><?= htmlspecialchars($errors['name']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id" class="required">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $category['id'] == $formData['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category_id'])): ?>
                            <span class="error-message"><?= htmlspecialchars($errors['category_id']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?= htmlspecialchars($formData['description']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <div class="location-wrapper">
                            <input type="text" id="address" name="address" class="location-input" 
                                   value="<?= htmlspecialchars($formData['address']) ?>">
                            <button type="button" id="get-location" class="get-location-btn">
                                <i class="fas fa-location-arrow"></i>
                            </button>
                        </div>
                        <input type="hidden" id="latitude" name="latitude">
                        <input type="hidden" id="longitude" name="longitude">
                    </div>
                    
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?= htmlspecialchars($formData['city']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="required">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($formData['phone']) ?>" required>
                        <?php if (isset($errors['phone'])): ?>
                            <span class="error-message"><?= htmlspecialchars($errors['phone']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" value="<?= htmlspecialchars($formData['website']) ?>">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="opening_hours">Opening Hours</label>
                        <textarea id="opening_hours" name="opening_hours"><?= htmlspecialchars($formData['opening_hours']) ?></textarea>
                    </div>
                </div>
                
                <div id="map" class="map-container"></div>
                
                <div class="form-actions">
                    <a href="../index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Business
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const getLocationBtn = document.getElementById('get-location');
            const addressInput = document.getElementById('address');
            const cityInput = document.getElementById('city');
            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');
            const mapContainer = document.getElementById('map');
            let map;
            let marker;
            
            // Get current location
            getLocationBtn.addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const latitude = position.coords.latitude;
                            const longitude = position.coords.longitude;
                            
                            // Update hidden inputs
                            latitudeInput.value = latitude;
                            longitudeInput.value = longitude;
                            
                            // Reverse geocode to get address
                            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.address) {
                                        const addressParts = [];
                                        if (data.address.road) addressParts.push(data.address.road);
                                        if (data.address.house_number) addressParts.push(data.address.house_number);
                                        addressInput.value = addressParts.join(' ');
                                        
                                        if (data.address.city) {
                                            cityInput.value = data.address.city;
                                        } else if (data.address.town) {
                                            cityInput.value = data.address.town;
                                        } else if (data.address.village) {
                                            cityInput.value = data.address.village;
                                        }
                                    }
                                });
                            
                            // Show map with marker
                            if (!map) {
                                mapContainer.classList.add('active');
                                map = L.map('map').setView([latitude, longitude], 15);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                }).addTo(map);
                            } else {
                                map.setView([latitude, longitude], 15);
                            }
                            
                            if (marker) {
                                marker.setLatLng([latitude, longitude]);
                            } else {
                                marker = L.marker([latitude, longitude]).addTo(map);
                            }
                        },
                        function(error) {
                            alert('Error getting location: ' + error.message);
                        }
                    );
                } else {
                    alert('Geolocation is not supported by your browser');
                }
            });
            
            // Initialize Leaflet map if coordinates are already set
            if (latitudeInput.value && longitudeInput.value) {
                mapContainer.classList.add('active');
                map = L.map('map').setView([latitudeInput.value, longitudeInput.value], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                marker = L.marker([latitudeInput.value, longitudeInput.value]).addTo(map);
            }
        });
    </script>
    
    <!-- Include Leaflet JS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
</body>
</html>