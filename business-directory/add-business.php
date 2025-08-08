<?php
require 'includes/auth.php';
require 'includes/functions.php';

if (!isBusinessOwner()) {
    redirect('index.php');
}

$categories = getCategories();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and process form
    $name = sanitize($_POST['name']);
    $categoryId = (int)$_POST['category_id'];
    $description = sanitize($_POST['description']);
    $location = sanitize($_POST['location']);
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $website = sanitize($_POST['website']);
    
    // Process features
    $features = [];
    if (!empty($_POST['features'])) {
        foreach ($_POST['features'] as $feature) {
            $features[] = sanitize($feature);
        }
    }
    
    // Process opening hours
    $openingHours = [];
    foreach ($_POST['opening_day'] as $index => $day) {
        if (!empty($day)) {
            $openingHours[$day] = [
                'open' => sanitize($_POST['opening_time'][$index]),
                'close' => sanitize($_POST['closing_time'][$index])
            ];
        }
    }
    
    // Process social media
    $socialMedia = [];
    if (!empty($_POST['social_platform'])) {
        foreach ($_POST['social_platform'] as $index => $platform) {
            if (!empty($platform) {
                $socialMedia[$platform] = sanitize($_POST['social_url'][$index]);
            }
        }
    }
    
    // Upload logo
    $logoPath = '';
    if (!empty($_FILES['logo']['name'])) {
        $logoPath = uploadFile($_FILES['logo'], 'logo_');
        if (!$logoPath) {
            $error = 'Failed to upload logo';
        }
    }
    
    // Upload images
    $imagePaths = [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['images']['name'][$index],
                    'type' => $_FILES['images']['type'][$index],
                    'tmp_name' => $tmpName,
                    'error' => $_FILES['images']['error'][$index],
                    'size' => $_FILES['images']['size'][$index]
                ];
                $path = uploadFile($file, 'biz_');
                if ($path) {
                    $imagePaths[] = $path;
                }
            }
        }
    }
    
    if (empty($error)) {
        // Insert business
        $userId = $_SESSION['user_id'];
        $featuresJson = json_encode($features);
        $openingHoursJson = json_encode($openingHours);
        $socialMediaJson = json_encode($socialMedia);
        $imagesJson = json_encode($imagePaths);
        
        $sql = "INSERT INTO businesses (user_id, category_id, name, description, location, address, 
                phone, email, website, logo, images, features, opening_hours, social_media) 
                VALUES ($userId, $categoryId, '$name', '$description', '$location', '$address', 
                '$phone', '$email', '$website', '$logoPath', '$imagesJson', '$featuresJson', 
                '$openingHoursJson', '$socialMediaJson')";
        
        if ($db->query($sql)) {
            $success = 'Business submitted successfully! It will be visible after admin approval.';
            // Reset form or redirect
        } else {
            $error = 'Error saving business: ' . $db->error();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Business - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <h1>Add Your Business</h1>
        
        <?php if ($error): ?>
            <?php echo displayError($error); ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <?php echo displaySuccess($success); ?>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="business-form">
            <div class="form-section">
                <h2>Basic Information</h2>
                
                <div class="form-group">
                    <label>Business Name*</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Category*</label>
                    <select name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description*</label>
                    <textarea name="description" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Location (City/Town)*</label>
                    <input type="text" name="location" required>
                </div>
                
                <div class="form-group">
                    <label>Full Address*</label>
                    <textarea name="address" rows="3" required></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h2>Contact Information</h2>
                
                <div class="form-group">
                    <label>Phone Number*</label>
                    <input type="tel" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label>Email*</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Website</label>
                    <input type="url" name="website">
                </div>
            </div>
            
            <div class="form-section">
                <h2>Media</h2>
                
                <div class="form-group">
                    <label>Logo</label>
                    <input type="file" name="logo" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label>Business Images (Up to 5)</label>
                    <input type="file" name="images[]" multiple accept="image/*">
                </div>
            </div>
            
            <div class="form-section">
                <h2>Features & Amenities</h2>
                
                <div class="features-list">
                    <div class="feature-item">
                        <input type="text" name="features[]" placeholder="Feature (e.g., Free WiFi, Parking)">
                    </div>
                </div>
                <button type="button" class="add-feature">+ Add Another Feature</button>
            </div>
            
            <div class="form-section">
                <h2>Opening Hours</h2>
                
                <div class="opening-hours">
                    <div class="day-hours">
                        <select name="opening_day[]">
                            <option value="">Select day</option>
                            <option value="monday">Monday</option>
                            <option value="tuesday">Tuesday</option>
                            <option value="wednesday">Wednesday</option>
                            <option value="thursday">Thursday</option>
                            <option value="friday">Friday</option>
                            <option value="saturday">Saturday</option>
                            <option value="sunday">Sunday</option>
                        </select>
                        <input type="time" name="opening_time[]">
                        <span>to</span>
                        <input type="time" name="closing_time[]">
                    </div>
                </div>
                <button type="button" class="add-hours">+ Add More Days</button>
            </div>
            
            <div class="form-section">
                <h2>Social Media</h2>
                
                <div class="social-media">
                    <div class="social-item">
                        <select name="social_platform[]">
                            <option value="">Select platform</option>
                            <option value="facebook">Facebook</option>
                            <option value="twitter">Twitter</option>
                            <option value="instagram">Instagram</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="youtube">YouTube</option>
                        </select>
                        <input type="url" name="social_url[]" placeholder="Profile URL">
                    </div>
                </div>
                <button type="button" class="add-social">+ Add Another Platform</button>
            </div>
            
            <button type="submit" class="submit-btn">Submit Business</button>
        </form>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        // Dynamic form field additions
        document.querySelector('.add-feature').addEventListener('click', function() {
            const featureItem = document.createElement('div');
            featureItem.className = 'feature-item';
            featureItem.innerHTML = `
                <input type="text" name="features[]" placeholder="Feature (e.g., Free WiFi, Parking)">
                <button type="button" class="remove-field">×</button>
            `;
            document.querySelector('.features-list').appendChild(featureItem);
        });
        
        document.querySelector('.add-hours').addEventListener('click', function() {
            const dayHours = document.createElement('div');
            dayHours.className = 'day-hours';
            dayHours.innerHTML = `
                <select name="opening_day[]">
                    <option value="">Select day</option>
                    <option value="monday">Monday</option>
                    <option value="tuesday">Tuesday</option>
                    <option value="wednesday">Wednesday</option>
                    <option value="thursday">Thursday</option>
                    <option value="friday">Friday</option>
                    <option value="saturday">Saturday</option>
                    <option value="sunday">Sunday</option>
                </select>
                <input type="time" name="opening_time[]">
                <span>to</span>
                <input type="time" name="closing_time[]">
                <button type="button" class="remove-field">×</button>
            `;
            document.querySelector('.opening-hours').appendChild(dayHours);
        });
        
        document.querySelector('.add-social').addEventListener('click', function() {
            const socialItem = document.createElement('div');
            socialItem.className = 'social-item';
            socialItem.innerHTML = `
                <select name="social_platform[]">
                    <option value="">Select platform</option>
                    <option value="facebook">Facebook</option>
                    <option value="twitter">Twitter</option>
                    <option value="instagram">Instagram</option>
                    <option value="linkedin">LinkedIn</option>
                    <option value="youtube">YouTube</option>
                </select>
                <input type="url" name="social_url[]" placeholder="Profile URL">
                <button type="button" class="remove-field">×</button>
            `;
            document.querySelector('.social-media').appendChild(socialItem);
        });
        
        // Remove field functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-field')) {
                e.target.parentElement.remove();
            }
        });
    </script>
</body>
</html>