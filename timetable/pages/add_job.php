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

// Get form data
$job_name = $_POST['job_name'];
$student_id = $_POST['student_id'];

// Insert into database
$sql = "INSERT INTO jobs (name, student_id) VALUES ('$job_name', '$student_id')";

if ($conn->query($sql) === TRUE) {
    echo "New job added successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();

// Redirect back to the main page
header("Location: ../index.php");
?>