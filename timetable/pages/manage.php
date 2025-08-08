<?php
// Database connection
$db = new mysqli('127.0.0.1:3306', 'root', '', 'work_job_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['print_week'])) {
        $week_start = $_POST['print_week_start'];
        header("Location: pages/print_week.php?week_start=$week_start");
        exit();
    }
}

// Get current week start (Monday)
$current_week_start = date('Y-m-d', strtotime('monday this week'));

// Pagination logic
$weeks_per_page = 1;
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($page - 1) * $weeks_per_page;

// Get total number of weeks (current and upcoming)
$total_weeks = $db->query("
    SELECT COUNT(DISTINCT start_date) as total 
    FROM schedules 
    WHERE start_date >= '$current_week_start'
")->fetch_assoc()['total'];

$total_pages = ceil($total_weeks / $weeks_per_page);

// Get the week to display
$week_result = $db->query("
    SELECT DISTINCT start_date, end_date
    FROM schedules
    WHERE start_date >= '$current_week_start'
    ORDER BY start_date
    LIMIT $offset, $weeks_per_page
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Job Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: var(--dark);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 0;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        h1, h2, h3 {
            margin: 0;
            font-weight: 600;
        }
        
        h1 {
            font-size: 2.2rem;
            text-align: center;
        }
        
        h2 {
            font-size: 1.5rem;
            color: var(--secondary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent);
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background-color: var(--light);
            font-weight: 500;
            color: var(--secondary);
        }
        
        tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .week-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }
        
        .week-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .week-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .week-title {
            font-weight: 500;
            color: var(--secondary);
        }
        
        .week-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
        }
        
        .btn-print {
            background-color: var(--accent);
            color: white;
        }
        
        .btn-print:hover {
            background-color: #3a7bc8;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input[type="date"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            color: var(--dark);
            background-color: var(--light);
            border: 1px solid #ddd;
        }
        
        .pagination a:hover {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination .current {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .week-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .actions {
                margin-top: 10px;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Work Job Management</h1>
        </div>
    </header>
    
    <div class="container">
        <!-- Week Schedule -->
        <div class="card">
            <h2>Work Schedule</h2>
            <?php
            if ($week_result->num_rows > 0) {
                $week = $week_result->fetch_assoc();
                echo "<div class='week-card'>
                    <div class='week-header'>
                        <div>
                            <span class='week-title'>Week of " . date('F j', strtotime($week['start_date'])) . " - " . date('F j, Y', strtotime($week['end_date'])) . "</span>
                            <span class='week-date'>" . date('M d', strtotime($week['start_date'])) . " - " . date('M d', strtotime($week['end_date'])) . "</span>
                        </div>
                        <div class='actions'>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='print_week_start' value='{$week['start_date']}'>
                                <button type='submit' name='print_week' class='btn btn-print'>
                                    <i class='fas fa-print'></i> Print
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <table>
                        <tr>
                            <th>Student</th>
                            <th>Job Assignment</th>
                        </tr>";
                
                $assignments = $db->query("
                    SELECT s.name as student_name, j.name as job_name
                    FROM schedules sch
                    JOIN students s ON sch.student_id = s.id
                    JOIN jobs j ON sch.job_id = j.id
                    WHERE sch.start_date = '{$week['start_date']}'
                    ORDER BY student_name
                ");
                
                while ($assignment = $assignments->fetch_assoc()) {
                    echo "<tr>
                        <td>{$assignment['student_name']}</td>
                        <td>{$assignment['job_name']}</td>
                    </tr>";
                }
                
                echo "</table>
                </div>";
                
                // Pagination controls
echo "<div class='pagination'>";
if ($page > 1) {
    echo "<a href='?page=manage&p=" . ($page - 1) . "'><i class='fas fa-chevron-left'></i> Previous</a>";
}

if ($total_weeks > 0) {
    echo "<span>Page $page of $total_pages</span>";
}

if ($page < $total_pages) {
    echo "<a href='?page=manage&p=" . ($page + 1) . "'>Next <i class='fas fa-chevron-right'></i></a>";
}
echo "</div>";
            } else {
                echo "<div class='empty-state'>
                    <i class='fas fa-calendar-times'></i>
                    <h3>No schedules found</h3>
                    <p>There are no scheduled jobs for any week.</p>
                </div>";
            }
            ?>
        </div>
        
        <!-- Print Week Form -->
        <div class="card">
            <h2>Print Schedule</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="print_week_start">Select Week to Print:</label>
                    <input type="date" id="print_week_start" name="print_week_start" required 
                           value="<?php echo $current_week_start; ?>">
                </div>
                <button type="submit" name="print_week" class="btn btn-print">
                    <i class='fas fa-print'></i> Print Schedule
                </button>
            </form>
        </div>
    </div>
    
    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>