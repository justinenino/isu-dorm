<?php
require_once '../config/database.php';
requireAdmin();

header('Content-Type: application/json');

if (!isset($_GET['room_id'])) {
    echo json_encode([]);
    exit;
}

$room_id = $_GET['room_id'];
$pdo = getConnection();

$stmt = $pdo->prepare("SELECT id, bed_number FROM bed_spaces WHERE room_id = ? AND is_occupied = FALSE ORDER BY bed_number");
$stmt->execute([$room_id]);
$bed_spaces = $stmt->fetchAll();

echo json_encode($bed_spaces);
?>