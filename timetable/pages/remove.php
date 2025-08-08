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

// Get all distinct weeks from schedules
$weeks = [];
$stmt = $pdo->query("SELECT DISTINCT start_date, end_date FROM schedules ORDER BY start_date DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $weeks[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Weekly Data | Teen Challenge Uganda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            color: var(--dark-color);
        }

        .artistic-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .artistic-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, var(--primary-color) 0%, transparent 70%);
            opacity: 0.05;
            z-index: -1;
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .header-art {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .header-art h2 {
            font-size: 2.2rem;
            color: var(--dark-color);
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
        }

        .header-art h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .header-art p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .week-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .week-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .week-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .week-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, transparent 100%);
            z-index: 0;
        }

        .week-card h3 {
            margin-top: 0;
            color: var(--dark-color);
            position: relative;
        }

        .week-card p {
            color: #7f8c8d;
            margin: 10px 0;
            position: relative;
        }

        .week-card .week-dates {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .week-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            position: relative;
        }

        .delete-btn, .view-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }

        .delete-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .view-btn {
            background-color: var(--primary-color);
            color: white;
        }

        .view-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .confirmation-dialog {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .confirmation-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: scale(0.9);
            animation: scaleUp 0.3s ease forwards;
        }

        @keyframes scaleUp {
            to { transform: scale(1); }
        }

        .confirmation-box h3 {
            margin-top: 0;
            color: var(--dark-color);
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .confirmation-box p {
            color: #7f8c8d;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .confirm-btn, .cancel-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .confirm-btn {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c0392b 100%);
            color: white;
        }

        .confirm-btn:hover {
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

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .success {
            background: linear-gradient(135deg, var(--success-color) 0%, #27ae60 100%);
            color: white;
        }

        .error {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c0392b 100%);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .artistic-container {
                padding: 20px;
                margin: 20px;
            }
            
            .week-list {
                grid-template-columns: 1fr;
            }
            
            .confirmation-buttons {
                flex-direction: column;
            }
            
            .confirm-btn, .cancel-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="artistic-container">
        <div class="header-art">
            <h2>Manage Weekly Data</h2>
            <p>View and delete weekly schedule and assignment data</p>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> Data for week starting <?= htmlspecialchars($_GET['week_start']) ?> has been successfully deleted.
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> An error occurred while deleting the data. Please try again.
            </div>
        <?php endif; ?>
        
        <?php if (empty($weeks)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Weekly Data Found</h3>
                <p>There are no scheduled weeks in the database.</p>
            </div>
        <?php else: ?>
            <div class="week-list">
                <?php foreach ($weeks as $week): 
                    $start_date = new DateTime($week['start_date']);
                    $end_date = new DateTime($week['end_date']);
                ?>
                    <div class="week-card">
                        <h3>Week of <?= $start_date->format('F j, Y') ?></h3>
                        <p class="week-dates">
                            <?= $start_date->format('M j') ?> - <?= $end_date->format('M j, Y') ?>
                        </p>
                        <p>
                            <i class="fas fa-calendar-alt"></i> 
                            <?= $start_date->format('F Y') ?>
                        </p>
                        <div class="week-actions">
                            <button class="view-btn" onclick="window.location.href='pages/view_week.php?start_date=<?= $week['start_date'] ?>&end_date=<?= $week['end_date'] ?>'">
    <i class="fas fa-eye"></i> View
</button>
                            <button class="delete-btn" onclick="confirmDelete('<?= $week['start_date'] ?>', '<?= $week['end_date'] ?>')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Confirmation Dialog -->
    <div class="confirmation-dialog" id="confirmationDialog">
        <div class="confirmation-box">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <p id="confirmationText">You are about to permanently delete all schedule and history data for the selected week. This action cannot be undone.</p>
            <form id="deleteForm" method="POST" action="process_delete_week.php">
                <input type="hidden" name="week_start" id="deleteWeekStart">
                <div class="confirmation-buttons">
                    <button type="button" class="cancel-btn" id="cancelDelete">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="confirm-btn" id="confirmDelete">
                        <i class="fas fa-trash-alt"></i> Confirm Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmDelete(startDate, endDate) {
            document.getElementById('deleteWeekStart').value = startDate;
            document.getElementById('confirmationText').innerHTML = 
                `You are about to permanently delete all schedule and history data for the week of <strong>${formatDate(startDate)} to ${formatDate(endDate)}</strong>. This action cannot be undone.`;
            document.getElementById('confirmationDialog').style.display = 'flex';
        }

        function viewWeek(startDate, endDate) {
            // You can implement this to show detailed view of the week's data
            alert(`Viewing week: ${formatDate(startDate)} to ${formatDate(endDate)}\nThis would show detailed assignments for the week.`);
        }

        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        document.getElementById('cancelDelete').addEventListener('click', function() {
            document.getElementById('confirmationDialog').style.display = 'none';
        });
    </script>
</body>
</html>