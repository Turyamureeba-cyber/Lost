<?php
// Database connection
$host = '127.0.0.1:3306';
$dbname = 'work_job_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get week data from URL parameters
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Validate dates
if (empty($start_date) || empty($end_date)) {
    header("Location: remove.php?error=invalid_date");
    exit();
}

// Get all jobs from the database
$jobs_stmt = $pdo->query("SELECT id, name FROM jobs ORDER BY name");
$all_jobs = $jobs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get schedules for this week
$stmt = $pdo->prepare("
    SELECT s.*, st.name as student_name, st.id as student_id, j.name as job_name 
    FROM schedules s
    JOIN students st ON s.student_id = st.id
    JOIN jobs j ON s.job_id = j.id
    WHERE s.start_date = ? AND s.end_date = ?
    ORDER BY j.name
");
$stmt->execute([$start_date, $end_date]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all available students
$students_stmt = $pdo->query("SELECT id, name FROM students ORDER BY name");
$all_students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Delete existing assignments for this week from schedules
        $delete_schedule_stmt = $pdo->prepare("DELETE FROM schedules WHERE start_date = ? AND end_date = ?");
        $delete_schedule_stmt->execute([$start_date, $end_date]);
        
        // Update job_history table for each assignment
        $update_history_stmt = $pdo->prepare("
            INSERT INTO job_history (student_id, job_id, assignment_count, last_assigned) 
            VALUES (?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE 
                assignment_count = assignment_count + 1,
                last_assigned = ?
        ");
        
        // Insert new assignments into schedules
        $insert_schedule_stmt = $pdo->prepare("INSERT INTO schedules (job_id, student_id, start_date, end_date) VALUES (?, ?, ?, ?)");
        
        foreach ($_POST['assignments'] as $job_id => $student_ids) {
            foreach ($student_ids as $student_id) {
                if (!empty($student_id)) {
                    // Insert into schedules table
                    $insert_schedule_stmt->execute([$job_id, $student_id, $start_date, $end_date]);
                    
                    // Update job_history table
                    $current_date = date('Y-m-d');
                    $update_history_stmt->execute([
                        $student_id, 
                        $job_id, 
                        $current_date,
                        $current_date
                    ]);
                }
            }
        }
        
        $pdo->commit();
        $success_message = "Assignments updated successfully in both schedules and history!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error updating assignments: " . $e->getMessage();
    }
}

// Format dates for display
$start_date_obj = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);

// Reorganize assignments by job_id for easier display
$assignments_by_job = [];
foreach ($assignments as $assignment) {
    $assignments_by_job[$assignment['job_id']][] = $assignment;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Week View | Teen Challenge Uganda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            color: var(--dark-color);
        }

        .artistic-view-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px;
            position: relative;
        }

        .week-header {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .week-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, transparent 100%);
            z-index: 0;
        }

        .week-title {
            font-size: 2rem;
            color: var(--dark-color);
            margin-bottom: 10px;
            position: relative;
        }

        .week-dates {
            font-size: 1.3rem;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: var(--shadow);
            z-index: 1;
        }

        .back-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .assignments-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .job-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .job-title {
            font-size: 1.3rem;
            color: var(--dark-color);
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .student-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .student-item {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-item:last-child {
            border-bottom: none;
        }

        .student-icon {
            color: var(--primary-color);
        }

        .no-assignments {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            grid-column: 1 / -1;
        }

        .no-assignments i {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        /* Form styles */
        form {
            margin-top: 20px;
        }

        .form-actions {
            margin-top: 30px;
            text-align: center;
        }

        .save-btn, .cancel-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow);
            margin: 0 10px;
        }

        .save-btn {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #27ae60 100%);
            color: white;
        }

        .save-btn:hover {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
            transform: translateY(-2px);
        }

        .cancel-btn {
            background: white;
            color: var(--dark-color);
            border: 2px solid #dfe6e9;
        }

        .cancel-btn:hover {
            background: #f5f5f5;
        }

        .student-select {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }

        .add-student-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .add-student-btn:hover {
            background: #2980b9;
        }

        .remove-student-btn {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 2px 6px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: var(--transition);
            margin-left: auto;
        }

        .remove-student-btn:hover {
            background: #c0392b;
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }

        .success {
            background: linear-gradient(135deg, var(--success-color) 0%, #27ae60 100%);
            color: white;
        }

        .error {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c0392b 100%);
            color: white;
        }

        /* Animation effects */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .job-card {
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }

        .job-card:nth-child(1) { animation-delay: 0.1s; }
        .job-card:nth-child(2) { animation-delay: 0.2s; }
        .job-card:nth-child(3) { animation-delay: 0.3s; }
        .job-card:nth-child(4) { animation-delay: 0.4s; }
        .job-card:nth-child(5) { animation-delay: 0.5s; }
        .job-card:nth-child(6) { animation-delay: 0.6s; }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .artistic-view-container {
                padding: 15px;
            }
            
            .assignments-container {
                grid-template-columns: 1fr;
            }
            
            .week-title {
                font-size: 1.5rem;
            }
            
            .week-dates {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="artistic-view-container">
        <button class="back-btn" onclick="window.location.href='../index.php?page=remove'">
            <i class="fas fa-arrow-left"></i> Back to Weeks
        </button>
        
        <div class="week-header">
            <h1 class="week-title">Weekly Assignments</h1>
            <div class="week-dates">
                <?= $start_date_obj->format('F j, Y') ?> - <?= $end_date_obj->format('F j, Y') ?>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?= $success_message ?>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="assignments-container">
                <?php foreach ($all_jobs as $job): ?>
                    <div class="job-card">
                        <h2 class="job-title"><?= htmlspecialchars($job['name']) ?></h2>
                        <div class="student-assignments" id="job-<?= $job['id'] ?>-assignments">
                            <?php 
                            $job_assignments = $assignments_by_job[$job['id']] ?? [];
                            $student_count = count($job_assignments);
                            ?>
                            
                            <?php for ($i = 0; $i < max(1, $student_count); $i++): ?>
                                <div class="student-assignment">
                                    <select name="assignments[<?= $job['id'] ?>][]" class="student-select">
                                        <option value="">-- Select Student --</option>
                                        <?php foreach ($all_students as $student): ?>
                                            <option value="<?= $student['id'] ?>" 
                                                <?= isset($job_assignments[$i]) && $job_assignments[$i]['student_id'] == $student['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($student['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($i == max(0, $student_count - 1)): ?>
                                        <button type="button" class="add-student-btn" onclick="addStudentField(<?= $job['id'] ?>)">
                                            <i class="fas fa-plus"></i> Add Student
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="remove-student-btn" onclick="removeStudentField(this)">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="save-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <button type="button" class="cancel-btn" onclick="window.location.href='../index.php?page=remove'">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>

    <script>
        function addStudentField(jobId) {
            const container = document.getElementById(`job-${jobId}-assignments`);
            const newField = document.createElement('div');
            newField.className = 'student-assignment';
            newField.innerHTML = `
                <select name="assignments[${jobId}][]" class="student-select">
                    <option value="">-- Select Student --</option>
                    <?php foreach ($all_students as $student): ?>
                        <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="remove-student-btn" onclick="removeStudentField(this)">
                    <i class="fas fa-times"></i> Remove
                </button>
            `;
            container.appendChild(newField);
        }

        function removeStudentField(button) {
            const assignmentDiv = button.closest('.student-assignment');
            if (assignmentDiv) {
                // Only remove if there's more than one student field
                if (document.querySelectorAll(`#${assignmentDiv.parentNode.id} .student-assignment`).length > 1) {
                    assignmentDiv.remove();
                } else {
                    // If it's the last one, just clear the selection
                    const select = assignmentDiv.querySelector('select');
                    if (select) select.value = '';
                }
            }
        }
    </script>
</body>
</html>