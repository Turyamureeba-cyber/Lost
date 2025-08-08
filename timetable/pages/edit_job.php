<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "work_job_db";

// Get job ID from URL
$id = $_GET['id'];

// Create connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch job data
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if($job) {
        // Store job data in session to pre-fill the form
        session_start();
        $_SESSION['edit_job'] = $job;
        header("Location: ../index.php");
        exit();
    } else {
        $message = "Job not found";
    }
} catch(PDOException $e) {
    $message = "Error: " . $e->getMessage();
}

$conn = null;

// Redirect back with error message if needed
session_start();
$_SESSION['message'] = $message;
header("Location: ../index.php");
exit();
?>