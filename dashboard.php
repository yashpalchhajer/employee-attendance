<?php
require_once 'config.php';
requireLogin();

// Check if attendance already marked for today
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
$stmt->execute([$_SESSION['user_id'], $today]);
$todayAttendance = $stmt->fetch();

// Recent attendance records
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY date DESC, time DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$recentAttendance = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Attendance System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Employee Dashboard</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> | 
                <a href="logout.php">Logout</a>
            </div>
        </header>
        
        <div class="main-content">
            <div class="attendance-section">
                <h2>Mark Attendance</h2>
                <div class="current-time" id="currentTime"></div>
                
                <?php if ($todayAttendance): ?>
                    <div class="attendance-marked">
                        <h3> Attendance Already Marked for Today</h3>
                        <p><strong>Time:</strong> <?php echo date('h:i:s A', strtotime($todayAttendance['time'])); ?></p>
                        <p>
                            <strong>Location:</strong> 
                            <?php 
                                if ($todayAttendance['location_address']) {
                                    echo htmlspecialchars($todayAttendance['location_address']);
                                } elseif ($todayAttendance['latitude'] && $todayAttendance['longitude']) {
                                    echo "Coordinates: {$todayAttendance['latitude']}, {$todayAttendance['longitude']}";
                                } else {
                                    echo 'Location not available';
                                }
                            ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="attendance-form">
                        <form id="attendanceForm" method="POST">
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="hidden" name="longitude" id="longitude">
                            <input type="hidden" name="location_address" id="location_address">
                            <button type="button" id="markAttendanceBtn" class="btn-primary btn-large">Mark Attendance</button>
                        </form>
                        <div id="locationStatus" class="location-status"></div>
                        <div id="attendanceResult" class="result"></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="recent-attendance">
                <h3>Recent Attendance Records</h3>
                <?php if ($recentAttendance): ?>
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAttendance as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo date('h:i:s A', strtotime($record['time'])); ?></td>
                                    <td>
                                        <?php 
                                            if ($record['location_address']) {
                                                echo htmlspecialchars($record['location_address']);
                                            } elseif ($record['latitude'] && $record['longitude']) {
                                                echo "Coordinates: {$record['latitude']}, {$record['longitude']}";
                                            } else {
                                                echo 'N/A';
                                            }
                                        ?>
                                    </td>
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
    
    <!-- JS file separate -->
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
