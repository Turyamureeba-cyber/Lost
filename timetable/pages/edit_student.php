<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "work_job_db";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("UPDATE students SET name = :name WHERE id = :id");
        $stmt->bindParam(':name', $_POST['student_name']);
        $stmt->bindParam(':id', $_POST['id']);
        $stmt->execute();

        $_SESSION['message'] = "Student updated successfully";
        header("Location: ../index.php?page=add");
        exit();
    } catch(PDOException $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        header("Location: ../index.php?page=add");
        exit();
    }
}

// Handle GET request (showing the edit form)
if (isset($_GET['id'])) {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT * FROM students WHERE id = :id");
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $_SESSION['edit_student'] = $student;
            header("Location: ../index.php?page=add");
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
    }
}

header("Location: ../index.php?page=add");
exit();
?>