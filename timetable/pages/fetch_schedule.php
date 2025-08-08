<?php
$conn = new mysqli("localhost", "root", "", "work_job_db");

$page = (int)($_GET['page'] ?? 1);
$student_id = (int)($_GET['student_id'] ?? 0);
$job_id = (int)($_GET['job_id'] ?? 0);
$search = $_GET['search'] ?? "";

$where = "WHERE 1=1";
if ($student_id) $where .= " AND schedules.student_id = $student_id";
if ($job_id) $where .= " AND schedules.job_id = $job_id";
if ($search) $where .= " AND students.name LIKE '%" . $conn->real_escape_string($search) . "%'";

$range_sql = "SELECT DISTINCT start_date, end_date FROM schedules 
              LEFT JOIN students ON schedules.student_id = students.id 
              $where ORDER BY start_date DESC";
$range_result = $conn->query($range_sql);
$date_ranges = [];
while ($r = $range_result->fetch_assoc()) $date_ranges[] = $r;

$current = $date_ranges[$page - 1] ?? null;
if (!$current) {
    echo "<p>No data available.</p>";
    exit;
}

$start = $conn->real_escape_string($current['start_date']);
$end = $conn->real_escape_string($current['end_date']);

$query = "
    SELECT schedules.id, students.name AS student_name, jobs.name AS job_name, schedules.start_date, schedules.end_date
    FROM schedules
    LEFT JOIN students ON schedules.student_id = students.id
    LEFT JOIN jobs ON schedules.job_id = jobs.id
    WHERE schedules.start_date = '$start' AND schedules.end_date = '$end'
";

if ($student_id) $query .= " AND schedules.student_id = $student_id";
if ($job_id) $query .= " AND schedules.job_id = $job_id";
if ($search) $query .= " AND students.name LIKE '%" . $conn->real_escape_string($search) . "%'";
$query .= " ORDER BY students.name ASC";

$result = $conn->query($query);

echo "<h2>Schedule from <strong>$start</strong> to <strong>$end</strong></h2>";
if ($result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>Student Name</th>
                <th>Job Name</th>
                
            </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['student_name']) . "</td>
                <td>" . htmlspecialchars($row['job_name'] ?? '—') . "</td>
                
            </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No matching schedules found.</p>";
}
$conn->close();
