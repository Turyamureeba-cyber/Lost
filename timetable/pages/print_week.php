<?php
// Database connection
$db = new mysqli('127.0.0.1:3306', 'root', '', 'work_job_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get week parameter
$week_start = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

// Get assignments for the week
$assignments = $db->query("
    SELECT s.name as student_name, j.name as job_name
    FROM schedules sch
    JOIN students s ON sch.student_id = s.id
    JOIN jobs j ON sch.job_id = j.id
    WHERE sch.start_date = '$week_start'
    ORDER BY student_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Schedule Print View</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            color: var(--dark);
            background-color: white;
        }
        
        @page {
            size: A4;
            margin: 15mm;
        }
        
        .print-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--primary);
        }
        
        h1 {
            color: var(--primary);
            margin: 0;
            font-size: 28px;
        }
        
        .week-range {
            color: #666;
            font-size: 18px;
            margin-top: 5px;
        }
        
        .logo {
            height: 60px;
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }
        
        th {
            background-color: var(--primary);
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        
        .no-print {
            display: none;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            table {
                page-break-inside: avoid;
            }
        }
        
        @media screen {
            .screen-only {
                display: block;
                text-align: center;
                margin: 20px 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Header with logo and dates -->
        <div class="header">
            <!-- Replace with your actual logo or school name -->
            <div class="logo-placeholder" style="text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: var(--primary);">WORK JOB SCHEDULE</div>
                <div style="font-size: 14px; color: #666;">Student Assignments</div>
            </div>
            
            <h1>Weekly Work Schedule</h1>
            <div class="week-range">
                <?php 
                echo date('F j', strtotime($week_start)) . ' - ' . date('F j, Y', strtotime($week_end));
                ?>
            </div>
        </div>
        
        <!-- Assignments Table -->
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Job Assignment</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($assignments->num_rows > 0) {
                    while ($row = $assignments->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['student_name']}</td>
                            <td>{$row['job_name']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr>
                        <td colspan='2' style='text-align: center;'>No assignments scheduled for this week</td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
        
        <!-- Footer -->
        <div class="footer">
            <p>Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
            <p>Work Job Management System</p>
        </div>
    </div>
    
    <!-- Print Controls (only visible on screen) -->
    <div class="no-print screen-only">
        <button onclick="window.print()" style="
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
        ">
            Print Schedule
        </button>
        <button onclick="window.history.back()" style="
            background-color: #666;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            margin-left: 10px;
        ">
            Go Back
        </button>
    </div>
    
    <script>
        // Auto-print when page loads (optional)
        window.addEventListener('DOMContentLoaded', (event) => {
            // Uncomment to enable auto-printing
            // window.print();
        });
    </script>
</body>
</html>