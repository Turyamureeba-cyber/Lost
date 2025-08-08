<?php
$conn = new mysqli("localhost", "root", "", "work_job_db");

$students = $conn->query("SELECT id, name FROM students ORDER BY name");
$jobs = $conn->query("SELECT id, name FROM jobs ORDER BY name");

$student_arr = [];
$job_arr = [];

while ($row = $students->fetch_assoc()) $student_arr[] = $row;
while ($row = $jobs->fetch_assoc()) $job_arr[] = $row;

echo json_encode([
    "students" => $student_arr,
    "jobs" => $job_arr
]);
$conn->close();
