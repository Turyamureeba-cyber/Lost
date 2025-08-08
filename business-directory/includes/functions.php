<?php
require 'db.php';

// General helper functions
function sanitize($data) {
    global $db;
    return $db->escape(htmlspecialchars(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function displayError($message) {
    return '<div class="alert error">' . $message . '</div>';
}

function displaySuccess($message) {
    return '<div class="alert success">' . $message . '</div>';
}

// Hotel-specific functions
function getHotels($featured = false) {
    global $db;
    
    $sql = "SELECT * FROM hotels WHERE approved = 1";
    if ($featured) {
        $sql .= " AND featured = 1";
    }
    $sql .= " ORDER BY created_at DESC";
    
    $result = $db->query($sql);
    $hotels = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $hotels[] = $row;
        }
    }
    
    return $hotels;
}

function getHotelById($id) {
    global $db;
    $id = (int)$id;
    $sql = "SELECT * FROM hotels WHERE id = $id";
    $result = $db->query($sql);
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// includes/functions.php
function getCategories() {
    global $db;
    
    $sql = "SELECT * FROM categories ORDER BY name";
    $result = $db->query($sql);
    $categories = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}
?>