<?php
require_once '../config.php';
requireLogin();
requireAdmin();


$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$employee_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : 'all';


$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role = 'employee' AND status = 'active'");
$stmt->execute();
$employees = $stmt->fetchAll();


$query = "
    SELECT 
        u.full_name,
        u.username,
        a.date,
        MIN(CASE WHEN a.type='check_in' THEN a.time END) AS checkin_time,
        MAX(CASE WHEN a.type='check_out' THEN a.time END) AS checkout_time,
        GROUP_CONCAT(a.location_address ORDER BY a.id SEPARATOR ', ') AS locations
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE a.date BETWEEN ? AND ? AND u.role='employee'
";

$params = [$from_date, $to_date];

if ($employee_id !== 'all') {
    $query .= " AND a.user_id=?";
    $params[] = $employee_id;
}

$query .= " GROUP BY a.user_id, a.date ORDER BY a.date DESC, u.full_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendanceRecords = $stmt->fetchAll();


$stmt = $pdo->prepare("SELECT id, full_name, username FROM users WHERE role='employee' AND status='active'");
$stmt->execute();
$activeEmployees = $stmt->fetchAll();


$presentEmployeeIds = array_unique(array_column($attendanceRecords, 'username'));
$absentEmployees = [];
if ($employee_id === 'all') {
    foreach ($activeEmployees as $emp) {
        if (!in_array($emp['username'], $presentEmployeeIds)) {
            $absentEmployees[] = $emp;
        }
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
<style>
.filter-section { margin-bottom:20px; }
.filter-section form { display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
.filter-section label { font-weight:bold; }
.filter-section input, .filter-section select { padding:6px 8px; border:1px solid #ccc; border-radius:4px; }
.filter-section button { background:#007bff; color:white; border:none; padding:8px 14px; border-radius:4px; cursor:pointer; }
.filter-section button:hover { background:#0056b3; }
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

    <label for="employee_id">Employee:</label>
    <select name="employee_id" id="employee_id">
        <option value="all" <?php echo $employee_id === 'all' ? 'selected' : ''; ?>>All Employees</option>
        <?php foreach ($employees as $emp): ?>
            <option value="<?php echo $emp['id']; ?>" <?php echo $employee_id == $emp['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($emp['full_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Filter</button>
</form>
</div>

<div class="attendance-details">
<h3>Attendance Records (<?php echo date('M d, Y', strtotime($from_date)); ?> to <?php echo date('M d, Y', strtotime($to_date)); ?>)</h3>

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
            <td><?php echo htmlspecialchars($record['locations'] ?: 'N/A'); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p>No attendance records found for this selection.</p>
<?php endif; ?>
</div>

<?php if ($employee_id === 'all' && $absentEmployees): ?>
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
<?php foreach ($absentEmployees as $emp): ?>
 
<tr>
<td><?php echo htmlspecialchars($emp['full_name']); ?></td>
<td><?php echo htmlspecialchars($emp['username']); ?></td>
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

