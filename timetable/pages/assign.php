<?php
// Database connection
$db = new mysqli("localhost", "root", "", "work_job_db");
if ($db->connect_error) die("Connection failed: " . $db->connect_error);

// Create job_history table if not exists
$db->query("
    CREATE TABLE IF NOT EXISTS job_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        job_id INT NOT NULL,
        assignment_count INT DEFAULT 0,
        last_assigned DATE,
        FOREIGN KEY (student_id) REFERENCES students(id),
        FOREIGN KEY (job_id) REFERENCES jobs(id)
    )
");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_schedule'])) {
        // Save the schedule to database
        $db->begin_transaction();
        try {
            // Clear existing assignments for this week if needed
            if (isset($_POST['overwrite_existing'])) {
                $clear_stmt = $db->prepare("DELETE FROM schedules WHERE start_date = ? AND end_date = ?");
                $clear_stmt->bind_param("ss", $_POST['start_date'], $_POST['end_date']);
                $clear_stmt->execute();
            }
            
            // Insert new assignments
            $insert_stmt = $db->prepare("INSERT INTO schedules (student_id, job_id, start_date, end_date) VALUES (?, ?, ?, ?)");
            $history_stmt = $db->prepare("
                INSERT INTO job_history (student_id, job_id, assignment_count, last_assigned) 
                VALUES (?, ?, 1, CURDATE())
                ON DUPLICATE KEY UPDATE 
                    assignment_count = assignment_count + 1,
                    last_assigned = CURDATE()
            ");
            
            foreach ($_POST['assignments'] as $assignment) {
                $insert_stmt->bind_param(
                    "iiss",
                    $assignment['student_id'],
                    $assignment['job_id'],
                    $_POST['start_date'],
                    $_POST['end_date']
                );
                $insert_stmt->execute();
                
                // Update job history
                $history_stmt->bind_param("ii", $assignment['student_id'], $assignment['job_id']);
                $history_stmt->execute();
            }
            
            $db->commit();
            $success_message = "Weekly schedule saved successfully!";
        } catch (Exception $e) {
            $db->rollback();
            $error_message = "Error saving schedule: " . $e->getMessage();
        }
    }
}

// Fetch data for forms
$students = $db->query("SELECT id, name FROM students ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$jobs = $db->query("SELECT id, name, share FROM jobs ORDER BY share DESC, name")->fetch_all(MYSQLI_ASSOC);

// Find the latest assigned week in the database
$latest_assigned = $db->query("SELECT MAX(end_date) as latest FROM schedules")->fetch_assoc();
$latest_end_date = $latest_assigned['latest'] ? $latest_assigned['latest'] : '2025-06-08';

// Calculate the first unassigned week (next week after latest assigned)
$first_unassigned_monday = date('Y-m-d', strtotime($latest_end_date . ' +1 day'));
$first_unassigned_sunday = date('Y-m-d', strtotime($first_unassigned_monday . ' +6 days'));

// Handle week offset for pagination
$weekOffset = isset($_GET['week_offset']) ? (int)$_GET['week_offset'] : 0;

if ($weekOffset === 0) {
    // Default to first unassigned week
    $monday = $first_unassigned_monday;
    $sunday = $first_unassigned_sunday;
} else {
    // Calculate based on offset from first unassigned week
    $monday = date('Y-m-d', strtotime($first_unassigned_monday . ($weekOffset > 0 ? " +$weekOffset weeks" : " $weekOffset weeks")));
    $sunday = date('Y-m-d', strtotime($monday . ' +6 days'));
}

$existing_assignments = $db->query("
    SELECT s.*, st.name as student_name, j.name as job_name, j.share as job_share 
    FROM schedules s
    JOIN students st ON s.student_id = st.id
    JOIN jobs j ON s.job_id = j.id
    WHERE s.start_date = '$monday' AND s.end_date = '$sunday'
    ORDER BY st.name
")->fetch_all(MYSQLI_ASSOC);

// Group existing assignments by job for display
$jobs_with_assignments = [];
foreach ($existing_assignments as $assignment) {
    if (!isset($jobs_with_assignments[$assignment['job_id']])) {
        $jobs_with_assignments[$assignment['job_id']] = [
            'name' => $assignment['job_name'],
            'share' => $assignment['job_share'],
            'students' => []
        ];
    }
    $jobs_with_assignments[$assignment['job_id']]['students'][] = $assignment['student_name'];
}

// Precompute student job history
$studentHistory = [];
$history_result = $db->query("
    SELECT jh.student_id, jh.job_id, jh.assignment_count
    FROM job_history jh
");
while ($row = $history_result->fetch_assoc()) {
    $studentId = $row['student_id'];
    if (!isset($studentHistory[$studentId])) {
        $studentHistory[$studentId] = [];
    }
    $studentHistory[$studentId][$row['job_id']] = $row['assignment_count'];
}

// Include students with no history
foreach ($students as $student) {
    if (!isset($studentHistory[$student['id']])) {
        $studentHistory[$student['id']] = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Work Job Scheduling</title>
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e1e5eb;
        }
        
        .title {
            color: #2c3e50;
            font-size: 2.2rem;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .schedule-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .week-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5eb;
        }
        
        .week-display {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .edit-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .capacity-warning {
            background-color: #fff3cd;
            padding: 15px;
            border-left: 5px solid #ffc107;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .job-grid-container {
            margin-bottom: 30px;
        }
        
        .job-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .job-card {
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            padding: 15px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .priority-high {
            border-left: 5px solid #e74c3c;
        }
        
        .priority-medium {
            border-left: 5px solid #f39c12;
        }
        
        .job-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .share-info {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .capacity-info {
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .capacity-full {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .capacity-available {
            color: #27ae60;
        }
        
        .assigned-students {
            margin-bottom: 15px;
        }
        
        .assigned-student {
            display: block;
            margin: 5px 0;
            padding: 8px;
            background-color: #eaf7ff;
            border-radius: 4px;
            font-size: 0.9rem;
            position: relative;
        }
        
        .remove-student {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #e74c3c;
            font-weight: bold;
        }
        
        .history-count {
            float: right;
            background-color: #3498db;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
        
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .job-controls {
            display: flex;
            justify-content: flex-end;
        }
        
        .debug-console {
            display: none;
            background-color: #f8f9fa;
            border: 1px solid #e1e5eb;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
            max-height: 200px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        @media (max-width: 992px) {
            .job-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .job-grid {
                grid-template-columns: 1fr;
            }
            
            .edit-controls {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <h1 class="title">Weekly Work Job Scheduling</h1>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?= $error_message ?></div>
        <?php endif; ?>
        
        <!-- Weekly Schedule Form -->
        <div class="schedule-form">
            <div class="week-navigation">
                <button class="btn btn-secondary" onclick="changeWeek(-1)">
                    <i class="fas fa-chevron-left"></i> Previous Week
                </button>
                <div class="week-display" id="weekDisplay">
                    Week of <?= date('F j, Y', strtotime($monday)) ?> to <?= date('F j, Y', strtotime($sunday)) ?>
                </div>
                <button class="btn btn-secondary" onclick="changeWeek(1)">
                    Next Week <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <form id="scheduleForm" method="post">
                <input type="hidden" id="start_date" name="start_date" value="<?= $monday ?>">
                <input type="hidden" id="end_date" name="end_date" value="<?= $sunday ?>">
                
                <div class="form-group">
                    <input type="checkbox" id="overwrite_existing" name="overwrite_existing">
                    <label for="overwrite_existing" style="display: inline;">Overwrite existing assignments for this week</label>
                </div>
                
                <div class="edit-controls">
                    <button type="button" class="btn btn-secondary" onclick="autoAssignJobs()">
                        <i class="fas fa-magic"></i> Auto-Assign Jobs
                    </button>
                    <button type="button" class="btn btn-warning" onclick="rotateJobs()">
                        <i class="fas fa-sync-alt"></i> Rotate Jobs
                    </button>
                    <button type="button" class="btn btn-danger" onclick="clearAllAssignments()">
                        <i class="fas fa-trash-alt"></i> Clear All
                    </button>
                    <button type="button" class="btn btn-primary" onclick="toggleDebugConsole()">
                        <i class="fas fa-terminal"></i> Debug Console
                    </button>
                </div>
                
                <!-- Capacity warning -->
                <div id="capacityWarning" class="capacity-warning" style="display: none;">
                    <strong>Warning:</strong> There are more unassigned students than available job slots. 
                    Some students cannot be assigned. Consider adding more jobs or increasing job capacities.
                </div>
                
                <div class="job-grid-container">
                    <div class="job-grid" id="jobsContainer">
                        <?php foreach ($jobs as $job): 
                            $current_count = isset($jobs_with_assignments[$job['id']]) ? 
                                count($jobs_with_assignments[$job['id']]['students']) : 0;
                            $max_capacity = $job['share'] > 0 ? $job['share'] : 1;
                            $capacity_class = ($current_count >= $max_capacity) ? 'capacity-full' : 'capacity-available';
                        ?>
                            <div class="job-card <?= $job['share'] > 1 ? 'priority-high' : 'priority-medium' ?>" data-job-id="<?= $job['id'] ?>">
                                <h3><?= htmlspecialchars($job['name']) ?></h3>
                                <div class="share-info">
                                    <?php if ($job['share'] > 0): ?>
                                        Can be shared by <?= $job['share'] ?> students
                                    <?php else: ?>
                                        Individual assignment
                                    <?php endif; ?>
                                </div>
                                
                                <div class="capacity-info">
                                    Current: <span id="count-<?= $job['id'] ?>" class="<?= $capacity_class ?>"><?= $current_count ?></span>
                                    / Max: <?= $max_capacity ?>
                                </div>
                                
                                <div class="assigned-students" id="students-<?= $job['id'] ?>">
                                    <?php if (isset($jobs_with_assignments[$job['id']])): ?>
                                        <?php foreach ($existing_assignments as $assignment): ?>
                                            <?php if ($assignment['job_id'] == $job['id']): ?>
                                                <span class="assigned-student">
                                                    <?= htmlspecialchars($assignment['student_name']) ?>
                                                    <span class="remove-student" onclick="removeStudentAssignment(<?= $job['id'] ?>, <?= $assignment['student_id'] ?>)">×</span>
                                                    <?php if (isset($studentHistory[$assignment['student_id']][$job['id']])): ?>
                                                        <span class="history-count"><?= $studentHistory[$assignment['student_id']][$job['id']] ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="student-select-<?= $job['id'] ?>">Assign Student:</label>
                                    <select id="student-select-<?= $job['id'] ?>" class="student-select">
                                        <option value="">Select Student</option>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="job-controls">
                                        <button type="button" class="btn btn-secondary" onclick="assignStudentToJob(<?= $job['id'] ?>)">
                                            <i class="fas fa-user-plus"></i> Assign
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="debug-console" id="debugConsole"></div>
                
                <input type="hidden" name="save_schedule" value="1">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    <i class="fas fa-save"></i> Save Weekly Schedule
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Global variables
        let currentWeekStart = new Date('<?= $monday ?>');
        let assignments = {};
        let jobData = <?= json_encode($jobs) ?>;
        let phpStudents = <?= json_encode($students) ?>;
        let studentHistory = <?= json_encode($studentHistory) ?>;
        
        // Initialize assignments from existing data
        <?php foreach ($existing_assignments as $assignment): ?>
            if (!assignments[<?= $assignment['job_id'] ?>]) {
                assignments[<?= $assignment['job_id'] ?>] = [];
            }
            assignments[<?= $assignment['job_id'] ?>].push({
                studentId: <?= $assignment['student_id'] ?>,
                studentName: '<?= addslashes($assignment['student_name']) ?>'
            });
        <?php endforeach; ?>
        
        // Debug console functions
        function logToDebug(message) {
            const debugConsole = document.getElementById('debugConsole');
            if (debugConsole) {
                const entry = document.createElement('div');
                entry.textContent = message;
                debugConsole.appendChild(entry);
                debugConsole.scrollTop = debugConsole.scrollHeight;
            }
            console.log(message);
        }
        
        function toggleDebugConsole() {
            const debugConsole = document.getElementById('debugConsole');
            debugConsole.style.display = debugConsole.style.display === 'block' ? 'none' : 'block';
        }
        
        // Change week navigation for schedule editing
        function changeWeek(weeks) {
            currentWeekStart.setDate(currentWeekStart.getDate() + (weeks * 7));
            updateWeekDisplay();
            loadWeekAssignments();
        }
        
        function updateWeekDisplay() {
            const weekEnd = new Date(currentWeekStart);
            weekEnd.setDate(weekEnd.getDate() + 6);
            
            document.getElementById('weekDisplay').innerHTML = `
                Week of ${currentWeekStart.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })} 
                to ${weekEnd.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}
            `;
            
            // Update hidden form fields
            document.getElementById('start_date').value = formatDate(currentWeekStart);
            document.getElementById('end_date').value = formatDate(weekEnd);
        }
        
        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }
        
        // Load assignments for the current week
        function loadWeekAssignments() {
            const weekStart = formatDate(currentWeekStart);
            const weekEnd = new Date(currentWeekStart);
            weekEnd.setDate(weekEnd.getDate() + 6);
            
            fetch(`get_assignments.php?start_date=${weekStart}&end_date=${formatDate(weekEnd)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        
                        assignments = {};
                        data.forEach(assignment => {
                            if (!assignments[assignment.job_id]) {
                                assignments[assignment.job_id] = [];
                            }
                            assignments[assignment.job_id].push({
                                studentId: assignment.student_id,
                                studentName: assignment.student_name
                            });
                        });
                        
                        updateAssignmentsDisplay();
                    } catch (e) {
                        logToDebug(`Error parsing response: ${e.message}\nResponse: ${text.substring(0, 100)}`);
                    }
                })
                .catch(error => {
                    logToDebug(`Error loading assignments: ${error}`);
                });
        }
        
        // Assign student to job
        function assignStudentToJob(jobId) {
            const select = document.querySelector(`#student-select-${jobId}`);
            const studentId = select.value;
            const studentName = select.options[select.selectedIndex].text;
            
            if (!studentId) {
                alert('Please select a student first');
                return;
            }
            
            // Check if student is already assigned to another job
            for (const [job, jobAssignments] of Object.entries(assignments)) {
                if (jobAssignments.some(a => a.studentId == studentId)) {
                    alert('This student is already assigned to another job!');
                    return;
                }
            }
            
            // Initialize job assignments if not exists
            if (!assignments[jobId]) {
                assignments[jobId] = [];
            }
            
            // Check if job can accept more assignments
            const job = jobData.find(j => j.id == jobId);
            const currentCount = assignments[jobId].length;
            const maxCapacity = job.share > 0 ? job.share : 1;
            
            if (currentCount >= maxCapacity) {
                alert(`This job can only be assigned to ${maxCapacity} student(s)!`);
                return;
            }
            
            assignments[jobId].push({
                studentId: studentId,
                studentName: studentName
            });
            
            updateAssignmentsDisplay();
            logToDebug(`Assigned ${studentName} to ${job.name}`);
        }
        
        // Remove a specific student assignment from a job
        function removeStudentAssignment(jobId, studentId) {
            if (!confirm('Are you sure you want to remove this assignment?')) {
                return;
            }
            
            // Find and remove the assignment
            if (assignments[jobId]) {
                assignments[jobId] = assignments[jobId].filter(a => a.studentId != studentId);
            }
            
            updateAssignmentsDisplay();
            logToDebug(`Removed student ID ${studentId} from job ID ${jobId}`);
        }
        
        // Update the display of assignments
        function updateAssignmentsDisplay() {
            // Clear all student selects
            document.querySelectorAll('.student-select').forEach(select => {
                select.value = '';
            });
            
            // Update each job card
            jobData.forEach(job => {
                const container = document.querySelector(`#students-${job.id}`);
                const countElement = document.querySelector(`#count-${job.id}`);
                
                if (container && countElement) {
                    // Clear existing assignments display
                    container.innerHTML = '';
                    
                    // Get current assignments for this job
                    const jobAssignments = assignments[job.id] || [];
                    const maxCapacity = job.share > 0 ? job.share : 1;
                    
                    // Update capacity class
                    countElement.textContent = jobAssignments.length;
                    countElement.className = (jobAssignments.length >= maxCapacity) ? 'capacity-full' : 'capacity-available';
                    
                    // Update assignments display
                    jobAssignments.forEach(assignment => {
                        const span = document.createElement('span');
                        span.className = 'assigned-student';
                        span.innerHTML = assignment.studentName;
                        
                        // Add remove button
                        const removeBtn = document.createElement('span');
                        removeBtn.className = 'remove-student';
                        removeBtn.innerHTML = ' ×';
                        removeBtn.onclick = () => removeStudentAssignment(job.id, assignment.studentId);
                        span.appendChild(removeBtn);
                        
                        // Show assignment count if available
                        const history = studentHistory[assignment.studentId] || {};
                        if (history[job.id]) {
                            const countBadge = document.createElement('span');
                            countBadge.className = 'history-count';
                            countBadge.textContent = history[job.id];
                            span.appendChild(countBadge);
                        }
                        
                        container.appendChild(span);
                    });
                }
            });
        }
        
        // Clear all assignments
        function clearAllAssignments() {
            if (confirm('Are you sure you want to clear all assignments for this week?')) {
                assignments = {};
                updateAssignmentsDisplay();
                logToDebug('Cleared all assignments');
            }
        }
        
        // Before form submission, collect all assignments
        document.getElementById('scheduleForm').onsubmit = function(e) {
            // Create hidden input for each assignment
            let assignmentIndex = 0;
            for (const [jobId, jobAssignments] of Object.entries(assignments)) {
                jobAssignments.forEach(assignment => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `assignments[${assignmentIndex}][student_id]`;
                    input.value = assignment.studentId;
                    this.appendChild(input);
                    
                    const jobInput = document.createElement('input');
                    jobInput.type = 'hidden';
                    jobInput.name = `assignments[${assignmentIndex}][job_id]`;
                    jobInput.value = jobId;
                    this.appendChild(jobInput);
                    
                    assignmentIndex++;
                });
            }
            
            return true;
        };

        // IMPROVED AUTO-ASSIGN FUNCTION WITH FAIR DISTRIBUTION
        function autoAssignJobs() {
            logToDebug('Starting auto-assign process...');
            
            if (!confirm('This will automatically assign jobs to all unassigned students. Continue?')) {
                return;
            }

            // 1. Get list of already assigned students
            const assignedStudentIds = new Set();
            for (const jobId in assignments) {
                assignments[jobId].forEach(assignment => {
                    assignedStudentIds.add(assignment.studentId);
                });
            }
            logToDebug(`Already assigned students: ${Array.from(assignedStudentIds).join(', ')}`);

            // 2. Create list of unassigned students
            let unassignedStudents = phpStudents.filter(student => 
                !assignedStudentIds.has(student.id)
            );
            
            const initialUnassignedCount = unassignedStudents.length;
            logToDebug(`Found ${initialUnassignedCount} unassigned students: ${unassignedStudents.map(s => s.name).join(', ')}`);

            if (initialUnassignedCount === 0) {
                alert('All students are already assigned!');
                return;
            }

            // 3. Calculate current job capacity
            const jobCapacity = {};
            let totalAvailableSlots = 0;
            
            jobData.forEach(job => {
                const currentCount = assignments[job.id] ? assignments[job.id].length : 0;
                jobCapacity[job.id] = Math.max(
                    (job.share > 0 ? job.share : 1) - currentCount,
                    0
                );
                totalAvailableSlots += jobCapacity[job.id];
                logToDebug(`Job ${job.name} (ID:${job.id}) capacity: ${jobCapacity[job.id]} (Current: ${currentCount}, Max: ${job.share > 0 ? job.share : 1})`);
            });
            
            logToDebug(`Total available slots: ${totalAvailableSlots}, Unassigned students: ${initialUnassignedCount}`);

            // Show/hide capacity warning
            const warningElement = document.getElementById('capacityWarning');
            if (initialUnassignedCount > totalAvailableSlots) {
                warningElement.style.display = 'block';
            } else {
                warningElement.style.display = 'none';
            }

            if (totalAvailableSlots === 0) {
                alert('No available job slots! All jobs are fully assigned.');
                return;
            }

            // 4. Sort students by total assignments (fairness)
            unassignedStudents.sort((a, b) => {
                const aAssignments = Object.values(studentHistory[a.id] || {}).reduce((sum, count) => sum + count, 0);
                const bAssignments = Object.values(studentHistory[b.id] || {}).reduce((sum, count) => sum + count, 0);
                return aAssignments - bAssignments;
            });

            // 5. Track newly assigned students
            const newlyAssignedStudents = new Set();

            // 6. Assign students to jobs
            unassignedStudents.forEach(student => {
                let assigned = false;
                
                // Try to find a job with available capacity
                for (const job of jobData) {
                    if (jobCapacity[job.id] <= 0) continue;
                    
                    // Check if job can still accept assignments
                    if (!assignments[job.id]) assignments[job.id] = [];
                    const currentCount = assignments[job.id].length;
                    const maxCapacity = job.share > 0 ? job.share : 1;
                    
                    if (currentCount < maxCapacity) {
                        assignments[job.id].push({
                            studentId: student.id,
                            studentName: student.name
                        });
                        jobCapacity[job.id]--;
                        newlyAssignedStudents.add(student.id);
                        assigned = true;
                        logToDebug(`Assigned ${student.name} to ${job.name}`);
                        break;
                    }
                }
            });

            // 7. Calculate how many students were actually assigned
            const assignedCount = newlyAssignedStudents.size;
            const remainingUnassigned = initialUnassignedCount - assignedCount;
            
            if (remainingUnassigned > 0) {
                logToDebug(`Could not assign ${remainingUnassigned} students`);
                alert(
                    `Assigned ${assignedCount} students. ` +
                    `${remainingUnassigned} could not be assigned due to insufficient job slots.\n\n` +
                    `Available job slots: ${totalAvailableSlots}\n` +
                    `Unassigned students: ${initialUnassignedCount}`
                );
            } else {
                logToDebug(`Successfully assigned all ${assignedCount} students`);
                alert(`Successfully assigned all ${assignedCount} students to available jobs!`);
            }

            // 8. Update the display
            updateAssignmentsDisplay();
            logToDebug('Auto-assign completed successfully!');
        }
        
        // Job rotation function
        function rotateJobs() {
            logToDebug('Starting job rotation...');
            
            if (!confirm('This will rotate jobs to give all students new assignments. Continue?')) {
                return;
            }
            
            // 1. Clear all current assignments
            assignments = {};
            
            // 2. Get all students
            const allStudents = [...phpStudents];
            
            // 3. Calculate job capacity
            const jobCapacity = {};
            jobData.forEach(job => {
                jobCapacity[job.id] = job.share > 0 ? job.share : 1;
            });
            
            // 4. Sort students by total assignments (fairness)
            allStudents.sort((a, b) => {
                const aAssignments = Object.values(studentHistory[a.id] || {}).reduce((sum, count) => sum + count, 0);
                const bAssignments = Object.values(studentHistory[b.id] || {}).reduce((sum, count) => sum + count, 0);
                return aAssignments - bAssignments;
            });
            
            // 5. Track assignments
            const assignmentsThisRound = [];
            
            // 6. Assign each student to a job they haven't done recently
            allStudents.forEach(student => {
                const history = studentHistory[student.id] || {};
                let assigned = false;
                
                // Try to find a job the student hasn't done
                const availableJobs = jobData.filter(job => 
                    jobCapacity[job.id] > 0 && 
                    (!history[job.id] || history[job.id] < 2) // Not done or done less than twice
                );
                
                // If no new jobs, try any available job
                const fallbackJobs = jobData.filter(job => jobCapacity[job.id] > 0);
                
                const jobsToTry = availableJobs.length > 0 ? availableJobs : fallbackJobs;
                
                for (const job of jobsToTry) {
                    if (jobCapacity[job.id] > 0) {
                        if (!assignments[job.id]) assignments[job.id] = [];
                        assignments[job.id].push({
                            studentId: student.id,
                            studentName: student.name
                        });
                        jobCapacity[job.id]--;
                        assigned = true;
                        assignmentsThisRound.push({
                            studentId: student.id,
                            jobId: job.id
                        });
                        logToDebug(`Assigned ${student.name} to ${job.name}`);
                        break;
                    }
                }
                
                if (!assigned) {
                    logToDebug(`Could not assign ${student.name} to any job`);
                }
            });
            
            // 7. Update the display
            updateAssignmentsDisplay();
            
            // 8. Show results
            const unassignedCount = allStudents.length - assignmentsThisRound.length;
            if (unassignedCount > 0) {
                alert(`Job rotation completed. ${unassignedCount} students could not be assigned.`);
            } else {
                alert('Job rotation completed successfully! All students have new assignments.');
            }
            
            logToDebug('Job rotation completed');
        }
    </script>
</body>
</html>