<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "work_job_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect form data
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
    $assignments = isset($_POST['job_assignment']) ? $_POST['job_assignment'] : [];

    // Basic validation
    if (!$start_date || !$end_date) {
        die("Start date and end date are required.");
    }

    foreach ($assignments as $student_id => $job_id) {
        $student_id = (int)$student_id;
        $job_id_sql = !empty($job_id) ? (int)$job_id : "NULL";

        $sql = "INSERT INTO schedules (student_id, job_id, start_date, end_date)
                VALUES ($student_id, $job_id_sql, '$start_date', '$end_date')";

        if (!$conn->query($sql)) {
            echo "Error inserting schedule for student ID $student_id: " . $conn->error;
        }
    }

    $conn->close();
    header("Location: ../shedule.php");
    exit;
} else {
    echo "Invalid request.";
}
?>
