<?php
require_once '../config.php';
requireLogin();
requireAdmin();


$employee_value = $_REQUEST['employee_id'] ?? '';
$from_date = $_REQUEST['from_date'] ?? date('Y-m-01');
$to_date   = $_REQUEST['to_date'] ?? date('Y-m-d');


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
    WHERE a.date BETWEEN ? AND ?
";

$params = [$from_date, $to_date];


if (!empty($employee_value)) {
    if (is_numeric($employee_value)) {
        $query .= " AND a.user_id = ?";
    } else {
        $query .= " AND u.username = ?";
    }
    $params[] = $employee_value;
}

$query .= " GROUP BY a.user_id, a.date ORDER BY a.date DESC, u.full_name ASC";


$stmt = $pdo->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll();

if (!$records) {
    die('No attendance records found for the selected filters.');
}


header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendance_export.csv"');

$output = fopen('php://output', 'w');


fputcsv($output, ['Date', 'Employee Name', 'Username', 'Check-In', 'Check-Out', 'Working Hours', 'Location']);

foreach ($records as $record) {
    $checkin = $record['checkin_time']
        ? (new DateTime($record['checkin_time'], new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('Asia/Kolkata'))
            ->format('h:i:s A')
        : '';
    $checkout = $record['checkout_time']
        ? (new DateTime($record['checkout_time'], new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('Asia/Kolkata'))
            ->format('h:i:s A')
        : '';

    if ($checkin && $checkout) {
        $interval = (new DateTime($record['checkin_time'], new DateTimeZone('UTC')))
            ->diff(new DateTime($record['checkout_time'], new DateTimeZone('UTC')));
        $workingHours = $interval->format('%h hrs %i mins');
    } else {
        $workingHours = '';
    }

    fputcsv($output, [
        date('M d, Y', strtotime($record['date'])),
        $record['full_name'],
        $record['username'],
        $checkin,
        $checkout,
        $workingHours,
        $record['locations'] ?: '',
    ]);
}
fclose($output);
exit;