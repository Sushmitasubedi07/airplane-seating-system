<?php
require_once '../classes/Database.php';
require_once '../classes/SeatMap.php';

header('Content-Type: application/json');

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("DELETE FROM groups");
    $stmt = $db->prepare("DELETE FROM bookings");
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>