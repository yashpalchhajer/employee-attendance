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
$locationAddress = $input['location_address'] ?? null;

$today = date('Y-m-d');
$currentTime = date('H:i:s');

try {
    // Check if attendance already marked for today
    $stmt = $pdo->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->execute([$_SESSION['user_id'], $today]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Attendance already marked for today']);
        exit();
    }
    
    // Insert attendance record
    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, time, latitude, longitude, location_address) VALUES (?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $_SESSION['user_id'],
        $today,
        $currentTime,
        $latitude,
        $longitude,
        $locationAddress
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Attendance marked successfully!',
            'time' => date('h:i:s A', strtotime($currentTime)),
            'date' => date('M d, Y', strtotime($today))
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark attendance']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>