<?php
/**
 * Refresh Room Statuses - AJAX Endpoint
 * Updates all room statuses based on current occupancy
 */

header('Content-Type: application/json');

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    require_once '../config/database.php';
    $pdo = getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // First, ensure bed_spaces.is_occupied is accurate by syncing with students
    $stmt = $pdo->query("
        UPDATE bed_spaces bs 
        SET is_occupied = FALSE, student_id = NULL
        WHERE bs.is_occupied = TRUE
    ");
    $stmt->execute();
    
    // Now update bed_spaces based on actual students
    $stmt = $pdo->query("
        UPDATE bed_spaces bs
        JOIN students s ON bs.id = s.bed_space_id
        SET bs.is_occupied = TRUE, bs.student_id = s.id
        WHERE s.application_status = 'approved'
    ");
    $stmt->execute();
    
    // Update room occupancy based on bed spaces (which should now be accurate)
    $stmt = $pdo->query("
        UPDATE rooms r 
        SET occupied = (
            SELECT COUNT(*) 
            FROM bed_spaces bs 
            WHERE bs.room_id = r.id AND bs.is_occupied = TRUE
        )
    ");
    $stmt->execute();
    
    // Now update statuses based on capacity vs occupied columns
    $stmt = $pdo->query("
        UPDATE rooms 
        SET status = CASE 
            WHEN occupied >= capacity THEN 'full'
            WHEN occupied > 0 THEN 'available'
            ELSE 'available'
        END
    ");
    $stmt->execute();
    
    $updated_count = count($rooms);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Updated statuses for $updated_count rooms",
        'updated_count' => $updated_count
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error refreshing room statuses: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
