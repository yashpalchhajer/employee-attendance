<?php
require_once 'config.php';
requireLogin();

$today = date('Y-m-d');

$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id'], $today]);
$todayAttendance = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 10");
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

<style>
    .btn-checkin {
        background: linear-gradient(45deg, #28a745, #218838);
        color: #fff;
        font-weight: bold;
        padding: 10px 25px;
        border-radius: 8px;
        border: none;
        transition: 0.3s;
        margin: 5px;
    }
    .btn-checkin:hover {
        background: linear-gradient(45deg, #218838, #1e7e34);
    }

    .btn-checkout {
        background: linear-gradient(45deg, #dc3545, #c82333);
        color: #fff;
        font-weight: bold;
        padding: 10px 25px;
        border-radius: 8px;
        border: none;
        transition: 0.3s;
    }
    .btn-checkout:hover {
        background: linear-gradient(45deg, #c82333, #bd2130);
    }

    .current-time {
        font-weight: bold;
        margin-bottom: 15px;
        font-size: 1.2rem;
    }
</style>
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
            <h2>Attendance Actions</h2>
            <div class="current-time" id="currentTime"></div>

            <?php if ($todayAttendance): ?>
            <div class="attendance-marked">
                <h3>Last Action Today: <?php echo ucfirst(str_replace('_',' ',$todayAttendance['type'])); ?></h3>
                <p><strong>Time:</strong> <?php echo date('h:i:s A', strtotime($todayAttendance['time'])); ?></p>
                <p><strong>Location:</strong>
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
            <?php endif; ?>


            <div class="attendance-form mt-3">
                <form id="attendanceForm" method="POST">
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    <input type="hidden" name="location_address" id="location_address">

                    <button type="button" class="btn-checkin btn-large" data-action="check_in">Check-In</button>
                    <button type="button" class="btn-checkout btn-large" data-action="check_out">Check-Out</button>
                </form>
                <div id="locationStatus" class="location-status mt-2"></div>
                <div id="attendanceResult" class="result mt-2"></div>
            </div>
        </div>

        <div class="recent-attendance mt-4">
            <h3>Recent Attendance Records</h3>
            <?php if ($recentAttendance): ?>
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Time</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAttendance as $record): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                <td><?php echo ucfirst(str_replace('_',' ',$record['type'])); ?></td>
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

<script src="assets/js/dashboard.js"></script>


<script>
function updateCurrentTime() {
    const currentTimeElement = document.getElementById('currentTime');
    if (currentTimeElement) {
        const now = new Date();
        const options = { 
            weekday:'long', year:'numeric', month:'long', day:'numeric',
            hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:true
        };
        currentTimeElement.textContent = now.toLocaleString('en-US', options);
    }
}
setInterval(updateCurrentTime, 1000);
updateCurrentTime();
</script>

</body>
</html>
