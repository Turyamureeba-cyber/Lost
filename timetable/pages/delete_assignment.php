<?php
// delete_assignment.php
header('Content-Type: application/json');

$db = new mysqli("localhost", "root", "", "work_job_db");
if ($db->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Get assignment ID from URL parameter
$assignmentId = $_GET['id'] ?? null;
if (!$assignmentId || !is_numeric($assignmentId)) {
    echo json_encode(['error' => 'Invalid assignment ID']);
    exit;
}

try {
    // First get the assignment details before deleting
    $query = "SELECT student_id, job_id FROM schedules WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $assignmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Assignment not found']);
        exit;
    }
    
    $assignment = $result->fetch_assoc();
    $studentId = $assignment['student_id'];
    $jobId = $assignment['job_id'];
    
    // Begin transaction
    $db->begin_transaction();
    
    // Delete the assignment
    $deleteStmt = $db->prepare("DELETE FROM schedules WHERE id = ?");
    $deleteStmt->bind_param("i", $assignmentId);
    $deleteStmt->execute();
    
    // Decrement the count in job_history
    $updateStmt = $db->prepare("
        UPDATE job_history 
        SET assignment_count = GREATEST(0, assignment_count - 1),
            last_assigned = CASE 
                WHEN assignment_count - 1 <= 0 THEN NULL 
                ELSE last_assigned 
            END
        WHERE student_id = ? AND job_id = ?
    ");
    $updateStmt->bind_param("ii", $studentId, $jobId);
    $updateStmt->execute();
    
    // Commit transaction
    $db->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?>