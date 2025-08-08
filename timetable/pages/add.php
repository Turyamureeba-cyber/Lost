<?php


// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "work_job_db";

// Check for edit student ID from URL
if (isset($_GET['edit_student_id'])) {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT * FROM students WHERE id = :id");
        $stmt->bindParam(':id', $_GET['edit_student_id']);
        $stmt->execute();
        $_SESSION['edit_student'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

// Check for edit job ID from URL
if (isset($_GET['edit_job_id'])) {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = :id");
        $stmt->bindParam(':id', $_GET['edit_job_id']);
        $stmt->execute();
        $_SESSION['edit_job'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

// Check for edit student data
$edit_student = isset($_SESSION['edit_student']) ? $_SESSION['edit_student'] : null;
unset($_SESSION['edit_student']);

// Check for edit job data
$edit_job = isset($_SESSION['edit_job']) ? $_SESSION['edit_job'] : null;
unset($_SESSION['edit_job']);

// Check for messages
$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Job Time Table</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 0;
        }
        
        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #2c3e50;
            color: white;
            border-radius: 5px 5px 0 0;
        }
        
        .logo {
            width: 150px;
            height: auto;
        }
        
        .title {
            flex-grow: 1;
            text-align: center;
            margin: 0;
        }
        
        /* Form Section */
        .form-section {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            padding: 20px;
            background-color: #ecf0f1;
            border-radius: 5px;
        }
        
        .form-container {
            width: 48%;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .form-container h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #34495e;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .submit-btn:hover {
            background-color: #2980b9;
        }
        
        .cancel-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-left: 10px;
        }
        
        .cancel-btn:hover {
            background-color: #c0392b;
        }
        
        /* Table Section */
        .table-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .table-container {
            width: 48%;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .table-container h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .students-table th {
            background-color: #3498db;
            color: white;
        }
        
        .students-table tr:nth-child(even) {
            background-color: #e3f2fd;
        }
        
        .students-table tr:nth-child(odd) {
            background-color: #bbdefb;
        }
        
        .jobs-table th {
            background-color: #e74c3c;
            color: white;
        }
        
        .jobs-table tr:nth-child(even) {
            background-color: #ffebee;
        }
        
        .jobs-table tr:nth-child(odd) {
            background-color: #ffcdd2;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            font-weight: bold;
        }
        
        .action-link {
            color: #3498db;
            text-decoration: none;
            margin: 0 5px;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .delete-link {
            color: #e74c3c;
        }
        
        .message {
            padding: 10px; 
            background: #dff0d8; 
            color: #3c763d; 
            border: 1px solid #d6e9c6; 
            border-radius: 4px; 
            margin: 10px 0;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section with Logo -->
        <div class="header">
            <img src="images/logo.png" alt="Company Logo" class="logo">
            <h1 class="title">Work Job Time Table System</h1>
        </div>
        
        <!-- Display messages -->
        <?php if($message): ?>
        <div class="message">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <!-- Form Section -->
        <div class="form-section">
            <!-- Student Form Container -->
            <div class="form-container">
                <!-- Add Student Form (shown by default) -->
                <div id="add-student-form" <?php echo $edit_student ? 'class="hidden"' : ''; ?>>
                    <h2>Add Student</h2>
                    <form action="pages/add_student.php" method="POST">
                        <div class="form-group">
                            <label for="student_name">Student Name:</label>
                            <input type="text" id="student_name" name="student_name" required>
                        </div>
                        <button type="submit" class="submit-btn">Add Student</button>
                    </form>
                </div>
                
                <!-- Edit Student Form (shown when editing) -->
                <div id="edit-student-form" <?php echo !$edit_student ? 'class="hidden"' : ''; ?>>
                    <h2>Edit Student</h2>
                    <form action="pages/edit_student.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $edit_student ? $edit_student['id'] : ''; ?>">
                        <div class="form-group">
                            <label for="edit_student_name">Student Name:</label>
                            <input type="text" id="edit_student_name" name="student_name" 
                                   value="<?php echo $edit_student ? htmlspecialchars($edit_student['name']) : ''; ?>" required>
                        </div>
                        <button type="submit" class="submit-btn">Update Student</button>
                        <button type="button" class="cancel-btn" onclick="window.location.href='index.php'">Cancel</button>
                    </form>
                </div>
            </div>
            
            <!-- Job Form Container -->
            <div class="form-container">
                <!-- Add Job Form (shown by default) -->
                <div id="add-job-form" <?php echo $edit_job ? 'class="hidden"' : ''; ?>>
                    <h2>Add Work Job</h2>
                    <form action="pages/add_job.php" method="POST">
                        <div class="form-group">
                            <label for="job_name">Job Name:</label>
                            <input type="text" id="job_name" name="job_name" required>
                        </div>
                        <button type="submit" class="submit-btn">Add Job</button>
                    </form>
                </div>
                
                <!-- Edit Job Form (shown when editing) -->
                <div id="edit-job-form" <?php echo !$edit_job ? 'class="hidden"' : ''; ?>>
                    <h2>Edit Work Job</h2>
                    <form action="pages/edit_job.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $edit_job ? $edit_job['id'] : ''; ?>">
                        <div class="form-group">
                            <label for="edit_job_name">Job Name:</label>
                            <input type="text" id="edit_job_name" name="job_name" 
                                   value="<?php echo $edit_job ? htmlspecialchars($edit_job['name']) : ''; ?>" required>
                        </div>
                        <button type="submit" class="submit-btn">Update Job</button>
                        <button type="button" class="cancel-btn" onclick="window.location.href='index.php'">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Table Section -->
        <div class="table-section">
            <!-- Students Table -->
            <div class="table-container">
                <h2>Students List</h2>
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            
                            // Fetch students from database
                            $stmt = $conn->prepare("SELECT * FROM students");
                            $stmt->execute();
                            
                            // Set the resulting array to associative
                            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if(count($students) > 0) {
                                foreach($students as $student) {
                                    echo "<tr>
                                            <td>{$student['id']}</td>
                                            <td>{$student['name']}</td>
                                            <td>
                                                <a href='index.php?page=add&edit_student_id={$student['id']}' class='action-link'>Edit</a> | 
                                                <a href='pages/delete_student.php?id={$student['id']}' class='action-link delete-link'>Delete</a>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3'>No students found</td></tr>";
                            }
                        } catch(PDOException $e) {
                            echo "<tr><td colspan='3'>Error: " . $e->getMessage() . "</td></tr>";
                        }
                        $conn = null;
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Jobs Table -->
            <div class="table-container">
                <h2>Work Jobs</h2>
                <table class="jobs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Job Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            
                            // Fetch jobs from database
                            $stmt = $conn->prepare("SELECT * FROM jobs");
                            $stmt->execute();
                            
                            // Set the resulting array to associative
                            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if(count($jobs) > 0) {
                                foreach($jobs as $job) {
                                    echo "<tr>
                                            <td>{$job['id']}</td>
                                            <td>{$job['name']}</td>
                                            <td>
                                                <a href='index.php?page=add&edit_job_id={$job['id']}' class='action-link'>Edit</a> | 
                                                <a href='pages/delete_job.php?id={$job['id']}' class='action-link delete-link'>Delete</a>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3'>No jobs found</td></tr>";
                            }
                        } catch(PDOException $e) {
                            echo "<tr><td colspan='3'>Error: " . $e->getMessage() . "</td></tr>";
                        }
                        $conn = null;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>