<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$current_lat = isset($input['latitude']) ? floatval($input['latitude']) : null;
$current_lon = isset($input['longitude']) ? floatval($input['longitude']) : null;
$action = $input['action'] ?? null;

$validActions = ['check_in','check_out'];
if (!$action || !in_array($action, $validActions)) {
    echo json_encode(['success'=>false,'message'=>'Invalid attendance action']);
    exit();
}

if ($current_lat === null || $current_lon === null) {
    echo json_encode(['success'=>false,'message'=>'Employee current location required']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT latitude, longitude FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee || !$employee['latitude'] || !$employee['longitude']) {
        echo json_encode(['success'=>false,'message'=>'Your registered location is not set. Contact admin.']);
        exit();
    }

    $reg_lat = floatval($employee['latitude']);
    $reg_lon = floatval($employee['longitude']);

    
    $earth_radius = 6371000; 
    $dLat = deg2rad($current_lat - $reg_lat);
    $dLon = deg2rad($current_lon - $reg_lon);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($reg_lat)) * cos(deg2rad($current_lat)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earth_radius * $c;

    if ($distance > 100) {
        echo json_encode(['success' => false, 'message' => "You are outside the 100-meter radius from your assigned location. Distance: " . round($distance, 2) . " meters."]);
        exit();
    }

    
    $stmt = $pdo->prepare("SELECT type FROM attendance WHERE user_id=? AND date=CURDATE() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $lastAction = strtolower($stmt->fetchColumn() ?? '');

    if ($action == 'check_in' && $lastAction && $lastAction != 'check_out') {
        echo json_encode(['success'=>false,'message'=>'You can only Check-In after Check-Out']);
        exit();
    }
    if ($action == 'check_out' && $lastAction != 'check_in') {
        echo json_encode(['success'=>false,'message'=>'You can only Check-Out after Check-In']);
        exit();
    }

    
    $locationAddress = null;
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$current_lat}&lon={$current_lon}&zoom=18&addressdetails=1";
    $opts = ["http"=>["header"=>"User-Agent: AttendanceApp/1.0 (your_email@example.com)\r\n"]];
    $context = stream_context_create($opts);
    $resp = @file_get_contents($url,false,$context);
    if($resp){
        $data = json_decode($resp,true);
        if(!empty($data['display_name'])) $locationAddress=$data['display_name'];
    }
    
    $utcNow = new DateTime('now', new DateTimeZone('UTC'));
    $todayUTC = $utcNow->format('Y-m-d');
    $timeUTC = $utcNow->format('H:i:s');

    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, type, time, latitude, longitude, location_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $todayUTC, $action, $timeUTC, $current_lat, $current_lon, $locationAddress]);

    echo json_encode([
        'success' => true,
        'message' => ucfirst(str_replace('_',' ',$action)) . ' successful',
        'distance' => round($distance, 2),
        'location' => $locationAddress ?? "$current_lat,$current_lon"
    ]);

} catch(Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
?>