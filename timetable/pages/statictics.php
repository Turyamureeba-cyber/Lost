<?php
// Database connection
$db = new mysqli("localhost", "root", "", "work_job_db");
if ($db->connect_error) die("Connection failed: " . $db->connect_error);

// Fetch all students and their job history
$students = $db->query("SELECT id, name FROM students ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$jobs = $db->query("SELECT id, name FROM jobs ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get job history counts
$history_counts = [];
$history_result = $db->query("
    SELECT jh.student_id, st.name as student_name, 
           jh.job_id, j.name as job_name, 
           jh.assignment_count
    FROM job_history jh
    JOIN students st ON jh.student_id = st.id
    JOIN jobs j ON jh.job_id = j.id
    ORDER BY st.name, j.name
");

while ($row = $history_result->fetch_assoc()) {
    $studentId = $row['student_id'];
    $jobId = $row['job_id'];
    if (!isset($history_counts[$studentId])) {
        $history_counts[$studentId] = [
            'name' => $row['student_name'],
            'jobs' => []
        ];
    }
    $history_counts[$studentId]['jobs'][$jobId] = $row['assignment_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Statistics - Teen Challenge Uganda</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/statistics.css">
</head>
<body>
    <div class="container">
        <h1 class="statistics-header">Jobs Done per Student</h1>
        
        <div class="table-container">
            <table class="statistics-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <?php foreach ($jobs as $job): ?>
                            <th class="job-count"><?= htmlspecialchars($job['name']) ?></th>
                        <?php endforeach; ?>
                        <th class="total-col">Total Jobs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $serial = 1;
                    foreach ($students as $student): 
                        $studentId = $student['id'];
                        $totalJobs = 0;
                    ?>
                        <tr>
                            <td class="serial-no"><?= $serial++ ?></td>
                            <td class="student-name"><?= htmlspecialchars($student['name']) ?></td>
                            
                            <?php 
                            foreach ($jobs as $job): 
                                $jobId = $job['id'];
                                $count = $history_counts[$studentId]['jobs'][$jobId] ?? 0;
                                $totalJobs += $count;
                            ?>
                                <td class="job-count <?= $count > 0 ? 'highlight' : '' ?>">
                                    <?= $count > 0 ? $count : '-' ?>
                                </td>
                            <?php endforeach; ?>
                            
                            <td class="total-col"><?= $totalJobs ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align: right; font-weight: bold;">Job Totals:</td>
                        <?php 
                        $jobTotals = array_fill_keys(array_column($jobs, 'id'), 0);
                        foreach ($history_counts as $student) {
                            foreach ($student['jobs'] as $jobId => $count) {
                                $jobTotals[$jobId] += $count;
                            }
                        }
                        
                        foreach ($jobs as $job): 
                            $total = $jobTotals[$job['id']] ?? 0;
                        ?>
                            <td class="total-col"><?= $total ?></td>
                        <?php endforeach; ?>
                        <td class="total-col">
                            <?= array_sum($jobTotals) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</body>
</html>