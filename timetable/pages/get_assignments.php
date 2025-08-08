<?php
// get_assignments.php
header('Content-Type: application/json');

$db = new mysqli("localhost", "root", "", "work_job_db");
if ($db->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

if (!$start_date || !$end_date) {
    echo json_encode(['error' => 'Missing date parameters']);
    exit;
}

try {
    $query = "SELECT s.job_id, s.student_id, st.name AS student_name
              FROM schedules s
              JOIN students st ON s.student_id = st.id
              WHERE s.start_date = ? AND s.end_date = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $assignments = [];
    
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    
    echo json_encode($assignments);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>