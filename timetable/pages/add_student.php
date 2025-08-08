<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "work_job_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get student name from form
$student_name = $_POST['student_name'];

// Insert into database
$sql = "INSERT INTO students (name) VALUES ('$student_name')";

if ($conn->query($sql) === TRUE) {
    echo "New student added successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();

// Redirect back to the main page
header("Location: ../index.php");
?>