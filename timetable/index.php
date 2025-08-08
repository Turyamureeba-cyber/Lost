<?php
// Start session at the VERY beginning
ob_start();
session_start();

// Handle form submissions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['print_week'])) {
    $week_start = $_POST['print_week_start'];
    header("Location: pages/print_week.php?week_start=$week_start");
    exit();
}

// Initialize page variable first
$page = isset($_GET['page']) ? $_GET['page'] : 'manage';
$allowed_pages = ['manage', 'assign', 'add', 'statictics', 'remove'];

// Validate page parameter
if (!in_array($page, $allowed_pages)) {
    $page = 'manage';
}

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

// Calculate the date 9 weeks ago from today
$nineWeeksAgo = date('Y-m-d', strtotime('-9 weeks'));

try {
    // Delete old schedule data (older than 9 weeks)
    $deleteSchedules = $pdo->prepare("DELETE FROM schedules WHERE end_date < ?");
    $deleteSchedules->execute([$nineWeeksAgo]);
    
} catch (Exception $e) {
    error_log("Error cleaning old data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teen Challenge Uganda Work Job Schedule Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/new_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="images/logo.png" type="image/png">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        body {
            display: flex;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            color: white;
            height: 100vh;
            position: fixed;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            padding-top: 20px;
        }
        
        .logo-container {
            text-align: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .sidebar-title {
            font-size: 18px;
            margin: 0;
            color: var(--light-color);
        }
        
        .nav-menu {
            margin-top: 30px;
        }
        
        .nav-item {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid transparent;
            color: white;
            text-decoration: none;
        }
        
        .nav-item:hover {
            background-color: rgba(255,255,255,0.1);
            border-left: 4px solid var(--secondary-color);
        }
        
        .nav-item.active {
            background-color: rgba(255,255,255,0.1);
            border-left: 4px solid var(--accent-color);
        }
        
        .nav-icon {
            margin-right: 15px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .nav-text {
            font-size: 16px;
        }
        
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }
        
        .header-bar {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .page-title {
            color: var(--dark-color);
            margin-left: 20px;
        }
        
        footer {
            margin-top: 30px;
            text-align: center;
            padding: 15px;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .content-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="images/logo.png" alt="Teen Challenge Uganda Logo" class="logo">
            <h2 class="sidebar-title">Work Job Scheduler</h2>
        </div>
        
        <div class="nav-menu">
            <a href="index.php?page=manage" class="nav-item <?php echo ($page == 'manage') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-home"></i></div>
                <div class="nav-text">Dashboard</div>
            </a>
            
            <a href="index.php?page=assign" class="nav-item <?php echo ($page == 'assign') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="nav-text">Schedule Jobs</div>
            </a>
            
            <a href="index.php?page=add" class="nav-item <?php echo ($page == 'add') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-user-plus"></i></div>
                <div class="nav-text">Add New</div>
            </a>
            
            <a href="index.php?page=statictics" class="nav-item <?php echo ($page == 'statictics') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="nav-text">Statistics</div>
            </a>
            
            <a href="index.php?page=remove" class="nav-item <?php echo ($page == 'remove') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-tasks"></i></div>
                <div class="nav-text">Manage Jobs</div>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header-bar">
            <h1 class="page-title">
                <?php 
                    $titles = [
                        'manage' => 'Dashboard Overview',
                        'assign' => 'Job Scheduling',
                        'add' => 'Add New Students/Jobs',
                        'statictics' => 'Work Statistics',
                        'remove' => 'Manage Assignments'
                    ];
                    echo $titles[$page] ?? 'Work Job Management System';
                ?>
            </h1>
        </div>
        
        <div class="content-card">
            <?php
                if (file_exists("pages/{$page}.php")) {
                    include "pages/{$page}.php";
                } else {
                    echo "<h2>Page Error</h2><p>The requested page file does not exist.</p>";
                }
            ?>
        </div>
        
        <footer>
            <p>&copy; 2025 Teen Challenge Uganda, Kiwatule, Kampala. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>