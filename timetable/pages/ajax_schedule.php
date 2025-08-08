<?php
// ajax_schedule.php

header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "work_job_db");
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get parameters safely
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$student_filter = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$job_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

// Get unique date ranges
$ranges_result = $conn->query("SELECT DISTINCT start_date, end_date FROM schedules ORDER BY start_date DESC");
$date_ranges = [];
while ($r = $ranges_result->fetch_assoc()) {
    $date_ranges[] = $r;
}

$total_pages = count($date_ranges);
if ($total_pages === 0) {
    echo json_encode([
        'error' => 'No schedule groups found',
        'total_pages' => 0,
        'page' => 0,
        'html' => '<p>No schedule groups found.</p>'
    ]);
    exit;
}

if ($page > $total_pages) $page = $total_pages;

$current_range = $date_ranges[$page - 1];
$start = $conn->real_escape_string($current_range['start_date']);
$end = $conn->real_escape_string($current_range['end_date']);

// Build SQL with filters
$where = "WHERE schedules.start_date = '$start' AND schedules.end_date = '$end'";
if ($student_filter > 0) $where .= " AND schedules.student_id = $student_filter";
if ($job_filter > 0) $where .= " AND schedules.job_id = $job_filter";

$sql = "
    SELECT schedules.id, students.name AS student_name, jobs.name AS job_name, schedules.start_date, schedules.end_date
    FROM schedules
    LEFT JOIN students ON schedules.student_id = students.id
    LEFT JOIN jobs ON schedules.job_id = jobs.id
    $where
    ORDER BY students.name ASC
";

$result = $conn->query($sql);

// Generate HTML for schedules table
ob_start();

echo "<h2>Schedule from <strong>$start</strong> to <strong>$end</strong></h2>";

if ($result && $result->num_rows > 0) {
    echo '<table>
            <tr>
                <th>Student Name</th>
                <th>Job Name</th>
                
            </tr>';
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['student_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['job_name'] ?? '—') . '</td>';
        
    }
    echo '</table>';
} else {
    echo '<p>No schedules found for selected filters in this range.</p>';
}

$html = ob_get_clean();

echo json_encode([
    'html' => $html,
    'total_pages' => $total_pages,
    'page' => $page,
]);

$conn->close();
