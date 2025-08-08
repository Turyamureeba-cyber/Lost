<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "work_job_db";

// Get student ID from URL
$id = $_GET['id'];

// Create connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Delete student
    $stmt = $conn->prepare("DELETE FROM students WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $message = "Student deleted successfully";
    } else {
        $message = "Student not found";
    }
} catch(PDOException $e) {
    $message = "Error: " . $e->getMessage();
}

$conn = null;

// Redirect back to the main page with message
session_start();
$_SESSION['message'] = $message;
header("Location: ../index.php");
exit();
?>