<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$latitude = $input['latitude'] ?? null;
$longitude = $input['longitude'] ?? null;

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

    // Reverse geocoding on server-side if lat/lng available
    $locationAddress = null;
    if ($latitude && $longitude) {
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=18&addressdetails=1";
        
        $opts = [
            "http" => [
                "header" => "User-Agent: AttendanceApp/1.0 (your_email@example.com)\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (!empty($data['display_name'])) {
                $locationAddress = $data['display_name'];
            }
        }
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
            'date' => date('M d, Y', strtotime($today)),
            'location' => $locationAddress ?? "Coordinates: $latitude, $longitude"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark attendance']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
