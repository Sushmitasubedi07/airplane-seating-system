<?php
require_once '../classes/Admin.php';

session_start();
$admin = new Admin();
$admin->logout();

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>