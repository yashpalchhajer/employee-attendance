<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$latitude = $input['latitude'] ?? null;
$longitude = $input['longitude'] ?? null;
$action = $input['action'] ?? null;


$validActions = ['check_in', 'check_out'];
if (!$action || !in_array($action, $validActions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid attendance action']);
    exit();
}

$today = date('Y-m-d');
$currentTime = date('H:i:s');

try {
    
    $stmt = $pdo->prepare("SELECT type FROM attendance WHERE user_id = ? AND date = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $today]);
    $lastAction = strtolower($stmt->fetchColumn() ?? '');

    $type = '';
    switch($action) {
        case 'check_in':
    
            if ($lastAction && $lastAction !== 'check_out') {
                echo json_encode(['success' => false, 'message' => 'You can only Check-In after Check-Out']);
                exit();
            }
            $type = 'check_in';
            break;

        case 'check_out':
            if ($lastAction !== 'check_in') {
                echo json_encode(['success' => false, 'message' => 'You can only Check-Out after Check-In']);
                exit();
            }
            $type = 'check_out';
            break;
    }

    
    $locationAddress = null;
    if ($latitude && $longitude) {
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=18&addressdetails=1";
        $opts = ["http" => ["header" => "User-Agent: AttendanceApp/1.0 (your_email@example.com)\r\n"]];
        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (!empty($data['display_name'])) {
                $locationAddress = $data['display_name'];
            }
        }
    }

    
    $stmt = $pdo->prepare("INSERT INTO attendance
        (user_id, date, type, time, latitude, longitude, location_address, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $today,
        $type,
        $currentTime,
        $latitude,
        $longitude,
        $locationAddress
    ]);

    echo json_encode([
        'success' => true,
        'message' => ucfirst(str_replace('_', ' ', $type)) . ' successful',
        'action' => $type,
        'time' => date('h:i:s A', strtotime($currentTime)),
        'date' => date('M d, Y', strtotime($today)),
        'location' => $locationAddress ?? "Coordinates: $latitude, $longitude"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
