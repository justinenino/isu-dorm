<?php
/**
 * Get Students in Room - AJAX Endpoint
 * Returns students assigned to a specific room
 */

header('Content-Type: application/json');

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if room_id is provided
if (!isset($_GET['room_id']) || empty($_GET['room_id'])) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

$room_id = (int)$_GET['room_id'];

try {
    require_once '../config/database.php';
    $pdo = getConnection();
    
    // Get students in the specified room
    $stmt = $pdo->prepare("
        SELECT s.id, s.first_name, s.last_name, s.school_id, s.email, s.phone, 
               bs.bed_number, s.application_status, s.room_id
        FROM students s
        LEFT JOIN bed_spaces bs ON s.bed_space_id = bs.id
        WHERE s.room_id = ? 
        AND s.application_status = 'approved' 
        AND s.is_deleted = 0 
        AND s.is_active = 1
        ORDER BY bs.bed_number ASC, s.last_name ASC, s.first_name ASC
    ");
    $stmt->execute([$room_id]);
    $students = $stmt->fetchAll();
    
    // Get room information for context
    $stmt = $pdo->prepare("
        SELECT r.room_number, r.floor_number, b.name as building_name
        FROM rooms r
        JOIN buildings b ON r.building_id = b.id
        WHERE r.id = ?
    ");
    $stmt->execute([$room_id]);
    $room_info = $stmt->fetch();
    
    if (!$room_info) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }
    
    // Return success response with students data
    echo json_encode([
        'success' => true,
        'students' => $students,
        'room_info' => $room_info,
        'count' => count($students)
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching room students: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
