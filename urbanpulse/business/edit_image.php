<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Initialize variables
$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_business_image'])) {
    $image_id = $_POST['image_id'];
    $business_id = $_POST['business_id'];
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    
    try {
        // Verify user owns this business image
        $checkStmt = $pdo->prepare("
            SELECT bi.id, bi.image_url 
            FROM business_images bi
            JOIN businesses b ON bi.business_id = b.id
            WHERE bi.id = ? AND b.owner_id = ?
        ");
        $checkStmt->execute([$image_id, $_SESSION['user_id']]);
        $imageData = $checkStmt->fetch();
        
        if (!$imageData) {
            throw new Exception("You don't have permission to edit this image");
        }

        $updateData = [];
        $updateFields = [];
        $currentImage = $imageData['image_url'];
        
        // Handle file upload if a new image was provided
        if (!empty($_FILES['new_image']['name']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/business/';
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $_FILES['new_image']['tmp_name']);
            finfo_close($fileInfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception("Only JPG, PNG, and GIF files are allowed");
            }
            
            // Generate new filename
            $fileExt = pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'business_' . $business_id . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['new_image']['tmp_name'], $targetPath)) {
                // Delete old image file if it exists
                if ($currentImage && file_exists($uploadDir . $currentImage)) {
                    unlink($uploadDir . $currentImage);
                }
                
                $updateFields[] = 'image_url = ?';
                $updateData[] = $fileName;
            } else {
                throw new Exception("Error uploading new image");
            }
        }
        
        // Handle primary image setting
        if ($is_primary) {
            // Unset any existing primary images for this business
            $pdo->prepare("UPDATE business_images SET is_primary = 0 WHERE business_id = ?")->execute([$business_id]);
            
            $updateFields[] = 'is_primary = ?';
            $updateData[] = 1;
        }
        
        // Only proceed with update if there are fields to update
        if (!empty($updateFields)) {
            $updateData[] = $image_id;
            
            $sql = "UPDATE business_images SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute($updateData);
            
            $success_message = "Business image updated successfully!";
        } else {
            $success_message = "No changes were made to the image.";
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
    
    // Store messages in session to display after redirect
    if ($success_message) {
        $_SESSION['success_message'] = $success_message;
    }
    if ($error_message) {
        $_SESSION['error_message'] = $error_message;
    }
}

header('Location: settings.php');
exit();