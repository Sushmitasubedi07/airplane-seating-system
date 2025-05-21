<?php
require_once '../classes/Admin.php';

session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

$admin = new Admin();
$valid = $admin->verifyPassword($password);

if ($valid) {
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_last_activity'] = time(); // Track activity time
}

echo json_encode(['valid' => $valid]);
?>