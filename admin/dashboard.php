<?php
require_once '../config.php';
requireLogin();
requireAdmin();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee'");
$totalEmployees = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee' AND status = 'active'");
$activeEmployees = $stmt->fetchColumn();

$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.user_id) FROM attendance a JOIN users u ON a.user_id = u.id WHERE a.date = ? AND u.role = 'employee'");
$stmt->execute([$today]);
$todayAttendance = $stmt->fetchColumn();

// Get recent attendance with employee names
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name, u.username 
    FROM attendance a 
    JOIN users u ON a.user_id = u.id 
    WHERE u.role = 'employee' 
    ORDER BY a.date DESC, a.time DESC 
    LIMIT 20
");
$stmt->execute();
$recentAttendance = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendance System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Admin Dashboard</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> | 
                <a href="../logout.php">Logout</a>
            </div>
        </header>
        
        <nav class="admin-nav">
            <a href="dashboard.php" class="nav-link active">Dashboard</a>
            <a href="employees.php" class="nav-link">Manage Employees</a>
            <a href="attendance.php" class="nav-link">View Attendance</a>
        </nav>
        
        <div class="main-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Employees</h3>
                    <div class="stat-number"><?php echo $totalEmployees; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Active Employees</h3>
                    <div class="stat-number"><?php echo $activeEmployees; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Today's Attendance</h3>
                    <div class="stat-number"><?php echo $todayAttendance; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Attendance Rate</h3>
                    <div class="stat-number"><?php echo $activeEmployees > 0 ? round(($todayAttendance / $activeEmployees) * 100) : 0; ?>%</div>
                </div>
            </div>
            
            <div class="recent-attendance">
                <h3>Recent Attendance Records</h3>
                <?php if ($recentAttendance): ?>
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Username</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAttendance as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['username']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo date('h:i:s A', strtotime($record['time'])); ?></td>
                                    <td class="location-cell"><?php echo htmlspecialchars($record['location_address'] ?: 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No attendance records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>