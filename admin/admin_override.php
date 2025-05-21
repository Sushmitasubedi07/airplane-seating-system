<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Passenger.php'; // Ensure this is included
require_once __DIR__ . '/../classes/SeatMap.php';
require_once __DIR__ . '/../classes/Admin.php';

session_start();
header('Content-Type: application/json');

// Enhanced authentication check
if (empty($_SESSION['admin_authenticated']) || 
    !isset($_SESSION['admin_last_activity']) || 
    (time() - $_SESSION['admin_last_activity'] > 1800)) { // 30-minute timeout
    
    session_unset();
    session_destroy();
    
    echo json_encode([
        'success' => false,
        'message' => 'Admin session expired or not authenticated'
    ]);
    exit;
}

// Update last activity time
$_SESSION['admin_last_activity'] = time();

$data = json_decode(file_get_contents('php://input'), true);
$seatId = $data['seatId'] ?? null;
$passengerName = $data['passengerName'] ?? null;

try {
    $database = new Database();
    $db = $database->getConnection();
    $seatMap = new SeatMap($db);
    $admin = new Admin();
    
    $result = $admin->overrideSeatAssignment($seatMap, $seatId, $passengerName);
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
?>