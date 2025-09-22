<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['room_id'])) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

$room_id = $_GET['room_id'];
$pdo = getConnection();

try {
    // Get available bed spaces for the selected room
    $stmt = $pdo->prepare("
        SELECT bs.id, bs.bed_number, bs.is_occupied
        FROM bed_spaces bs
        WHERE bs.room_id = ? AND bs.is_occupied = FALSE
        ORDER BY bs.bed_number
    ");
    $stmt->execute([$room_id]);
    $available_bed_spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get room information
    $stmt = $pdo->prepare("
        SELECT r.room_number, r.capacity, b.name as building_name
        FROM rooms r
        JOIN buildings b ON r.building_id = b.id
        WHERE r.id = ?
    ");
    $stmt->execute([$room_id]);
    $room_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get occupied bed spaces for reference
    $stmt = $pdo->prepare("
        SELECT bs.bed_number, s.first_name, s.last_name
        FROM bed_spaces bs
        LEFT JOIN students s ON bs.student_id = s.id
        WHERE bs.room_id = ? AND bs.is_occupied = TRUE
        ORDER BY bs.bed_number
    ");
    $stmt->execute([$room_id]);
    $occupied_bed_spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'room_info' => $room_info,
        'available_bed_spaces' => $available_bed_spaces,
        'occupied_bed_spaces' => $occupied_bed_spaces
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching bed spaces: ' . $e->getMessage()
    ]);
}
?>
