<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Work Job Schedule</title>
<style>
    body {
        margin: 0; padding: 0;
        background-color: #f5f5f5;
        font-family: Arial, sans-serif;
    }
    .container {
        width: 90%; max-width: 1200px;
        margin: 0 auto;
        padding: 20px 0;
    }
    .header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 20px;
        background-color: #2c3e50;
        color: white;
        border-radius: 5px 5px 0 0;
    }
    .logo {
        width: 150px; height: auto;
    }
    .title {
        flex-grow: 1;
        text-align: center;
        margin: 0;
        font-size: 24px;
        font-weight: bold;
    }
    h2 {
        margin-top: 30px;
        color: #2c3e50;
    }
    form {
        margin-top: 20px;
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    select, input[type="text"] {
        padding: 8px;
        font-size: 16px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: white;
    }
    th, td {
        padding: 12px;
        border: 1px solid #ddd;
    }
    th {
        background-color: #34495e;
        color: white;
    }
    .btn {
        padding: 8px 12px;
        background-color: #27ae60;
        border: none;
        color: white;
        cursor: pointer;
        border-radius: 5px;
        text-decoration: none;
    }
    .btn:hover {
        background-color: #219150;
    }
    .btn-danger {
        background-color: #e74c3c;
    }
    .btn-danger:hover {
        background-color: #c0392b;
    }
    .btn-edit {
        background-color: #2980b9;
    }
    .btn-edit:hover {
        background-color: #2471a3;
    }
    .pagination {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        gap: 15px;
        align-items: center;
    }
    .pagination button {
        background: #2980b9;
        border: none;
        color: white;
        padding: 8px 16px;
        cursor: pointer;
        border-radius: 5px;
        font-weight: bold;
    }
    .pagination button:disabled {
        background: #bbb;
        cursor: not-allowed;
    }
    #printBtn {
        margin-top: 20px;
        float: right;
        background-color: #34495e;
    }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="../images/logo.png" alt="Logo" class="logo" />
        <h1 class="title">Teen Challenge Uganda Work Job Schedule Management system</h1>
    </div>

    <form id="filterForm">
        <label>Filter by Student:
            <select name="student_id" id="studentFilter">
                <option value="0">All</option>
                <?php
                $conn = new mysqli("localhost", "root", "", "work_job_db");
                if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
                $students = $conn->query("SELECT id, name FROM students ORDER BY name ASC");
                while ($s = $students->fetch_assoc()) {
                    echo '<option value="'.(int)$s['id'].'">'.htmlspecialchars($s['name']).'</option>';
                }
                ?>
            </select>
        </label>
        <label>Filter by Job:
            <select name="job_id" id="jobFilter">
                <option value="0">All</option>
                <?php
                $jobs = $conn->query("SELECT id, name FROM jobs ORDER BY name ASC");
                while ($j = $jobs->fetch_assoc()) {
                    echo '<option value="'.(int)$j['id'].'">'.htmlspecialchars($j['name']).'</option>';
                }
                $conn->close();
                ?>
            </select>
        </label>
        <label>Live Search:
            <input type="text" id="liveSearch" placeholder="Search student or job..." />
        </label>
        <button type="submit" class="btn">Apply Filters</button>
    </form>

    <button id="printBtn" class="btn">Print / Export</button>

    <div id="scheduleContainer">
        <!-- AJAX-loaded schedules appear here -->
        <p>Loading schedules...</p>
    </div>

    <div class="pagination">
        <button id="prevBtn" disabled>&laquo; Previous</button>
        <span id="pageInfo"></span>
        <button id="nextBtn" disabled>Next &raquo;</button>
    </div>
</div>

<script>
(() => {
    let currentPage = 1;
    let totalPages = 1;
    let currentStudent = 0;
    let currentJob = 0;
    let liveSearchTerm = '';

    const scheduleContainer = document.getElementById('scheduleContainer');
    const studentFilter = document.getElementById('studentFilter');
    const jobFilter = document.getElementById('jobFilter');
    const liveSearchInput = document.getElementById('liveSearch');
    const filterForm = document.getElementById('filterForm');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const pageInfo = document.getElementById('pageInfo');
    const printBtn = document.getElementById('printBtn');

    function fetchSchedules() {
        scheduleContainer.innerHTML = '<p>Loading schedules...</p>';
        let params = new URLSearchParams({
            page: currentPage,
            student_id: currentStudent,
            job_id: currentJob,
        });

        fetch('pages/ajax_schedule.php?' + params.toString())
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    scheduleContainer.innerHTML = `<p style="color:red;">${data.error}</p>`;
                    pageInfo.textContent = '';
                    prevBtn.disabled = true;
                    nextBtn.disabled = true;
                    return;
                }

                // Insert HTML returned from server
                scheduleContainer.innerHTML = data.html;

                // Apply live search filter after loading HTML
                if (liveSearchTerm.trim().length > 0) {
                    filterTableRows(liveSearchTerm);
                }

                totalPages = data.total_pages;
                pageInfo.textContent = `Page ${data.page} of ${totalPages}`;
                prevBtn.disabled = (data.page <= 1);
                nextBtn.disabled = (data.page >= totalPages);
            })
            .catch(() => {
                scheduleContainer.innerHTML = '<p style="color:red;">Error loading schedules.</p>';
            });
    }

    function filterTableRows(searchTerm) {
        const rows = scheduleContainer.querySelectorAll('table tbody tr, table tr');
        const lowerTerm = searchTerm.toLowerCase();

        // If no table, skip
        if (!rows.length) return;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            // Show header row always
            if (row.querySelector('th')) {
                row.style.display = '';
                return;
            }
            row.style.display = text.includes(lowerTerm) ? '' : 'none';
        });
    }

    filterForm.addEventListener('submit', e => {
        e.preventDefault();
        currentStudent = parseInt(studentFilter.value, 10) || 0;
        currentJob = parseInt(jobFilter.value, 10) || 0;
        currentPage = 1; // reset page to 1 on filter change
        liveSearchTerm = liveSearchInput.value.trim();
        fetchSchedules();
    });

    liveSearchInput.addEventListener('input', e => {
        liveSearchTerm = e.target.value.trim();
        filterTableRows(liveSearchTerm);
    });

    prevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            fetchSchedules();
        }
    });

    nextBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            fetchSchedules();
        }
    });

    printBtn.addEventListener('click', () => {
        if (!scheduleContainer.innerHTML.trim()) return alert('Nothing to print.');

        // Create a new window for print
        const printWindow = window.open('', '', 'width=900,height=600');
        printWindow.document.write('<html><head><title>Print Schedule</title>');
        printWindow.document.write('<style>body{font-family:Arial,sans-serif;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#34495e;color:#fff;}</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(scheduleContainer.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    });

    // Initialize - load schedules on page load
    fetchSchedules();
})();
</script>
</body>
</html>
