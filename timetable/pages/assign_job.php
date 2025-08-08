<?php
header('Content-Type: application/json');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "work_job_db";

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception("Invalid input data");
    }

    $student_id = $input['student_id'] ?? null;
    $job_id = $input['job_id'] ?? null;
    $start_date = $input['start_date'] ?? null;
    $end_date = $input['end_date'] ?? null;
    $notes = $input['notes'] ?? null;

    // Validate required fields
    if (!$student_id || !$job_id || !$start_date || !$end_date) {
        throw new Exception("All required fields must be filled");
    }

    // Validate dates
    if (strtotime($start_date) === false || strtotime($end_date) === false) {
        throw new Exception("Invalid date format");
    }

    if (strtotime($end_date) < strtotime($start_date)) {
        throw new Exception("End date must be after start date");
    }

    // Check if student exists
    $stmt = $conn->prepare("SELECT id FROM students WHERE id = :student_id");
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Student not found");
    }

    // Check if job exists
    $stmt = $conn->prepare("SELECT id FROM jobs WHERE id = :job_id");
    $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Job not found");
    }

    // Check for overlapping assignments
    $stmt = $conn->prepare("SELECT id FROM assignments 
                          WHERE student_id = :student_id 
                          AND (
                              (start_date BETWEEN :start_date AND :end_date)
                              OR (end_date BETWEEN :start_date AND :end_date)
                              OR (:start_date BETWEEN start_date AND end_date)
                              OR (:end_date BETWEEN start_date AND end_date)
                          )");
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        throw new Exception("This student already has an assignment during the selected period");
    }

    // Insert assignment
    $stmt = $conn->prepare("INSERT INTO assignments 
                          (student_id, job_id, start_date, end_date, notes) 
                          VALUES 
                          (:student_id, :job_id, :start_date, :end_date, :notes)");
    
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':notes', $notes);
    
    if ($stmt->execute()) {
        $assignment_id = $conn->lastInsertId();
        
        // Get assignment details for response
        $stmt = $conn->prepare("
            SELECT a.*, s.name as student_name, j.name as job_name 
            FROM assignments a
            JOIN students s ON a.student_id = s.id
            JOIN jobs j ON a.job_id = j.id
            WHERE a.id = :assignment_id
        ");
        $stmt->bindParam(':assignment_id', $assignment_id, PDO::PARAM_INT);
        $stmt->execute();
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Job assigned successfully',
            'assignment' => $assignment
        ]);
    } else {
        throw new Exception("Error saving assignment");
    }
    
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch(Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    $conn = null;
}
?>