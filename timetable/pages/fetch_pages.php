<?php
$conn = new mysqli("localhost", "root", "", "work_job_db");

$student_id = $_GET['student_id'] ?? 0;
$job_id = $_GET['job_id'] ?? 0;
$search = $_GET['search'] ?? '';

$where = "WHERE 1=1";
if ($student_id) $where .= " AND schedules.student_id = $student_id";
if ($job_id) $where .= " AND schedules.job_id = $job_id";
if ($search) $where .= " AND students.name LIKE '%" . $conn->real_escape_string($search) . "%'";

$sql = "SELECT DISTINCT start_date, end_date FROM schedules 
        LEFT JOIN students ON schedules.student_id = students.id 
        $where";

$result = $conn->query($sql);
echo json_encode(["total" => $result->num_rows]);
$conn->close();
