<?php
// Database connection
$host = '127.0.0.1:3306';
$dbname = 'work_job_db';
$username = 'root'; // Change to your username
$password = ''; // Change to your password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['week_start'])) {
    $week_start = $_POST['week_start'];
    $week_end = date('Y-m-d', strtotime($week_start . ' + 6 days'));
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete from schedules table
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE start_date = ? AND end_date = ?");
        $stmt->execute([$week_start, $week_end]);
        
        // Delete from job_history where last_assigned is within this week
        $stmt = $pdo->prepare("DELETE FROM job_history WHERE last_assigned BETWEEN ? AND ?");
        $stmt->execute([$week_start, $week_end]);
        
        // Commit transaction
        $pdo->commit();
        
        header("Location: remove.php?success=1&week_start=" . urlencode($week_start));
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: remove.php?error=1");
        exit();
    }
}

// Get all distinct weeks from schedules
$weeks = [];
$stmt = $pdo->query("SELECT DISTINCT start_date, end_date FROM schedules ORDER BY start_date DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $weeks[] = $row;
}
?>