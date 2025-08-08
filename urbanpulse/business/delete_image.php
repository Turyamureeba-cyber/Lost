<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Check if image ID and business ID are provided via POST
if (!isset($_POST['image_id']) || !isset($_POST['business_id'])) {
    $_SESSION['error_message'] = "Invalid request";
    header('Location: settings.php');
    exit();
}

$imageId = $_POST['image_id'];
$businessId = $_POST['business_id'];

try {
    // Verify the business belongs to the current user
    $stmt = $pdo->prepare("SELECT owner_id FROM businesses WHERE id = ?");
    $stmt->execute([$businessId]);
    $business = $stmt->fetch();
    
    if (!$business || $business['owner_id'] != $_SESSION['user_id']) {
        $_SESSION['error_message'] = "You don't have permission to delete this image";
        header('Location: settings.php');
        exit();
    }

    // Get image info before deleting
    $stmt = $pdo->prepare("SELECT image_url FROM business_images WHERE id = ? AND business_id = ?");
    $stmt->execute([$imageId, $businessId]);
    $image = $stmt->fetch();
    
    if (!$image) {
        $_SESSION['error_message'] = "Image not found";
        header('Location: settings.php');
        exit();
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM business_images WHERE id = ? AND business_id = ?");
    $stmt->execute([$imageId, $businessId]);
    
    // Delete the actual file
    $filePath = __DIR__ . '/../assets/uploads/business/' . $image['image_url'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $_SESSION['success_message'] = "Image deleted successfully";
    header('Location: settings.php');
    exit();

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error deleting image: " . $e->getMessage();
    header('Location: settings.php');
    exit();
}