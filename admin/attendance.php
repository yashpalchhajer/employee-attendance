<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$selected_employee = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

// Fetch employees
$employees = $pdo->query("SELECT id, full_name FROM users WHERE role='employee' AND status='active' ORDER BY full_name ASC")->fetchAll();

// Base query
$query = "
    SELECT 
        u.full_name,
        u.username,
        a.date,
        MIN(CASE WHEN a.type='check_in' THEN a.time END) AS checkin_time,
        MAX(CASE WHEN a.type='check_out' THEN a.time END) AS checkout_time,
        GROUP_CONCAT(a.location_address ORDER BY a.id SEPARATOR ', ') AS locations,
        MAX(a.latitude) AS latitude,
        MAX(a.longitude) AS longitude,
        MAX(a.distance_meters) AS distance_meters
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE a.date BETWEEN ? AND ?
";

// Add employee filter if selected
$params = [$from_date, $to_date];
if ($selected_employee > 0) {
    $query .= " AND u.id = ? ";
    $params[] = $selected_employee;
}

$query .= " GROUP BY a.user_id, a.date
            ORDER BY a.date DESC, u.full_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendanceRecords = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Attendance - Admin Dashboard</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.filter-section { margin-bottom: 20px; }
.filter-section form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.filter-section label { font-weight: bold; }
.filter-section input, .filter-section select { padding: 6px 8px; border: 1px solid #ccc; border-radius: 4px; }
.filter-section button { background: #007bff; color: white; border: none; padding: 8px 14px; border-radius: 4px; cursor: pointer; }
.filter-section button:hover { background: #0056b3; }
.export-btn {
    background-color: #17a2b8;
    color: #fff;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    border: none;
    cursor: pointer;
}
.fa-map-marker-alt {
    font-size: 18px;
    cursor: pointer;
}
.tooltip-text {
    white-space: pre-line;
}
</style>
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
<form method="GET">
    <label for="from_date">From:</label>
    <input type="date" id="from_date" name="from_date" value="<?php echo $from_date; ?>">
    <label for="to_date">To:</label>
    <input type="date" id="to_date" name="to_date" value="<?php echo $to_date; ?>">

    <label for="employee_id">Select Employee:</label>
    <select name="employee_id" id="employee_id">
        <option value="">-- All Employees --</option>
        <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>" <?= ($selected_employee == $emp['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($emp['full_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Filter</button>
</form>

<form method="POST" action="export_attendance_csv.php" style="margin-top:10px;">
    <input type="hidden" name="employee_id" value="<?= $selected_employee ?>">
    <button type="submit" class="export-btn">Export CSV</button>
</form>
</div>

<div class="attendance-details">
<h3>
    Attendance Records (<?php echo date('M d, Y', strtotime($from_date)); ?> to <?php echo date('M d, Y', strtotime($to_date)); ?>)
</h3>

<?php if ($attendanceRecords): ?>
<table class="attendance-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Employee Name</th>
            <th>Username</th>
            <th>Check-In / Check-Out</th>
            <th>Working Hours</th>
            <th>Location</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($attendanceRecords as $record): ?>
        <tr>
            <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
            <td><?php echo htmlspecialchars($record['full_name']); ?></td>
            <td><?php echo htmlspecialchars($record['username']); ?></td>
            <td>
                <?php
                $checkin = $record['checkin_time'] ? new DateTime($record['checkin_time'], new DateTimeZone('UTC')) : null;
                $checkout = $record['checkout_time'] ? new DateTime($record['checkout_time'], new DateTimeZone('UTC')) : null;
                if ($checkin) $checkin->setTimezone(new DateTimeZone('Asia/Kolkata'));
                if ($checkout) $checkout->setTimezone(new DateTimeZone('Asia/Kolkata'));
                echo $checkin ? $checkin->format('h:i:s A') : '---';
                echo ' - ';
                echo $checkout ? $checkout->format('h:i:s A') : '---';
                ?>
            </td>
            <td>
                <?php
                if ($checkin && $checkout) {
                    $interval = $checkin->diff($checkout);
                    echo $interval->format('%h hrs %i mins');
                } else {
                    echo '---';
                }
                ?>
            </td>
            <td style="text-align:center;">
                <?php
                $distance = isset($record['distance_meters']) ? round($record['distance_meters'], 2) : null;
                $isWithinRange = $distance !== null && $distance <= 100;
                $iconColor = $isWithinRange ? 'green' : 'red';
                $tooltip = "Address: " . htmlspecialchars($record['locations'] ?: 'N/A') .
                           "\nDistance: " . ($distance ? $distance . " m" : 'N/A');
                ?>
                <?php if (!empty($record['latitude']) && !empty($record['longitude'])): ?>
                    <i class="fas fa-map-marker-alt" 
                       style="color: <?= $iconColor ?>;" 
                       title="<?= $tooltip ?>"></i>
                <?php else: ?>
                    <i class="fas fa-map-marker-alt" style="color: gray;" title="No location data"></i>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p>No attendance records found for this selection.</p>
<?php endif; ?>
</div>

</div>
</div>
</body>
</html>