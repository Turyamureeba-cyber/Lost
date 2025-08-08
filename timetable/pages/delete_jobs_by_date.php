<?php
// delete_jobs_by_date.php

// Database connection
$db = new mysqli("localhost", "root", "", "work_job_db");
if ($db->connect_error) die("Connection failed: " . $db->connect_error);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_week'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Validate dates
    if (empty($start_date) || empty($end_date)) {
        $error_message = "Please select both start and end dates";
    } else {
        // Begin transaction
        $db->begin_transaction();
        try {
            // Delete assignments for the selected week
            $delete_stmt = $db->prepare("DELETE FROM schedules WHERE start_date = ? AND end_date = ?");
            $delete_stmt->bind_param("ss", $start_date, $end_date);
            $delete_stmt->execute();
            
            if ($delete_stmt->affected_rows > 0) {
                $success_message = "Successfully deleted all assignments for the week of " . 
                                   date('M j', strtotime($start_date)) . " - " . 
                                   date('M j, Y', strtotime($end_date));
            } else {
                $error_message = "No assignments found for the selected week";
            }
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            $error_message = "Error deleting assignments: " . $e->getMessage();
        }
    }
}

// Get all scheduled weeks
$scheduled_weeks = $db->query("
    SELECT DISTINCT start_date, end_date 
    FROM schedules 
    ORDER BY start_date DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Jobs by Date</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .alert-error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="date"], select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 200px;
        }
        .btn {
            padding: 10px 15px;
            background-color: #d9534f;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #c9302c;
        }
        .week-list {
            margin-top: 30px;
        }
        .week-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .week-item:hover {
            background-color: #f9f9f9;
        }
        .week-range {
            font-weight: bold;
        }
        .week-count {
            color: #777;
            font-size: 0.9em;
        }
        .delete-form {
            display: inline;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #337ab7;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Delete Jobs by Date Range</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?= $error_message ?></div>
        <?php endif; ?>
        
        <div class="form-group">
            <form method="post" onsubmit="return confirm('Are you sure you want to delete ALL assignments for the selected week? This cannot be undone.');">
                <label for="week_select">Select Week to Delete:</label>
                <select id="week_select" name="start_date" required>
                    <option value="">-- Select a Week --</option>
                    <?php foreach ($scheduled_weeks as $week): ?>
                        <option value="<?= $week['start_date'] ?>">
                            Week of <?= date('M j', strtotime($week['start_date'])) ?> - <?= date('M j, Y', strtotime($week['end_date'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="end_date" id="end_date">
                <button type="submit" name="delete_week" class="btn">Delete Week</button>
            </form>
        </div>
        
        <div class="week-list">
            <h2>All Scheduled Weeks</h2>
            <?php if (empty($scheduled_weeks)): ?>
                <p>No scheduled weeks found.</p>
            <?php else: ?>
                <?php foreach ($scheduled_weeks as $week): 
                    // Get assignment count for this week
                    $count_stmt = $db->prepare("SELECT COUNT(*) as count FROM schedules WHERE start_date = ? AND end_date = ?");
                    $count_stmt->bind_param("ss", $week['start_date'], $week['end_date']);
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $count = $count_result->fetch_assoc()['count'];
                ?>
                    <div class="week-item">
                        <div>
                            <span class="week-range">
                                Week of <?= date('M j', strtotime($week['start_date'])) ?> - <?= date('M j, Y', strtotime($week['end_date'])) ?>
                            </span>
                            <span class="week-count">(<?= $count ?> assignments)</span>
                        </div>
                        <form class="delete-form" method="post" onsubmit="return confirm('Are you sure you want to delete ALL assignments for this week? This cannot be undone.');">
                            <input type="hidden" name="start_date" value="<?= $week['start_date'] ?>">
                            <input type="hidden" name="end_date" value="<?= $week['end_date'] ?>">
                            <button type="submit" name="delete_week" class="btn">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <a href="assign.php" class="back-link">← Back to Scheduling</a>
    </div>

    <script>
        // When a week is selected from the dropdown, automatically set the end date
        document.getElementById('week_select').addEventListener('change', function() {
            if (this.value) {
                // Calculate end date (6 days after start date)
                const startDate = new Date(this.value);
                const endDate = new Date(startDate);
                endDate.setDate(endDate.getDate() + 6);
                
                // Format as YYYY-MM-DD
                const formattedEndDate = endDate.toISOString().split('T')[0];
                document.getElementById('end_date').value = formattedEndDate;
            }
        });
    </script>
</body>
</html>