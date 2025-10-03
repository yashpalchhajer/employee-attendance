<?php
require_once '../config.php';
requireLogin();
requireAdmin();

// Handle date filter
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get attendance records for the selected date
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name, u.username 
    FROM attendance a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.date = ? AND u.role = 'employee'
    ORDER BY a.time ASC
");
$stmt->execute([$date_filter]);
$attendanceRecords = $stmt->fetchAll();

// Get all active employees for comparison
$stmt = $pdo->prepare("SELECT id, username, full_name FROM users WHERE role = 'employee' AND status = 'active'");
$stmt->execute();
$activeEmployees = $stmt->fetchAll();

// Create array of employees who marked attendance
$presentEmployees = [];
foreach ($attendanceRecords as $record) {
    $presentEmployees[] = $record['user_id'];
}

// Find absent employees
$absentEmployees = [];
foreach ($activeEmployees as $employee) {
    if (!in_array($employee['id'], $presentEmployees)) {
        $absentEmployees[] = $employee;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>View Attendance</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> | 
                <a href="logout.php">Logout</a>
            </div>
        </header>
        
        <nav class="admin-nav">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="employees.php" class="nav-link">Manage Employees</a>
            <a href="attendance.php" class="nav-link active">View Attendance</a>
        </nav>
        
        <div class="main-content">
            <div class="filter-section">
                <form method="GET" class="date-filter">
                    <label for="date">Select Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>">
                    <button type="submit" class="btn-primary">Filter</button>
                </form>
            </div>
            
            <div class="attendance-summary">
                <div class="summary-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total Employees:</span>
                        <span class="stat-value"><?php echo count($activeEmployees); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Present:</span>
                        <span class="stat-value present"><?php echo count($attendanceRecords); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Absent:</span>
                        <span class="stat-value absent"><?php echo count($absentEmployees); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Attendance Rate:</span>
                        <span class="stat-value"><?php echo count($activeEmployees) > 0 ? round((count($attendanceRecords) / count($activeEmployees)) * 100) : 0; ?>%</span>
                    </div>
                </div>
            </div>
            
            <div class="attendance-details">
                <h3>Present Employees (<?php echo date('M d, Y', strtotime($date_filter)); ?>)</h3>
                <?php if ($attendanceRecords): ?>
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Username</th>
                                <th>Time</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['username']); ?></td>
                                    <td><?php echo date('h:i:s A', strtotime($record['time'])); ?></td>
                                    <td class="location-cell"><?php echo htmlspecialchars($record['location_address'] ?: 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No attendance records found for this date.</p>
                <?php endif; ?>
            </div>
            
            <?php if ($absentEmployees): ?>
            <div class="absent-employees">
                <h3>Absent Employees</h3>
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Username</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($absentEmployees as $employee): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($employee['username']); ?></td>
                                <td><span class="status-badge status-absent">Absent</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>