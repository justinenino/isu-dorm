<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maintenance_id'])) {
    $maintenance_id = sanitizeInput($_POST['maintenance_id']);
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                mr.*,
                s.first_name,
                s.last_name,
                s.middle_name,
                s.student_id,
                s.mobile_number,
                s.email,
                r.room_number,
                b.building_name,
                u.username as assigned_to_name
            FROM maintenance_requests mr
            LEFT JOIN students s ON mr.student_id = s.id
            LEFT JOIN rooms r ON mr.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            LEFT JOIN users u ON mr.assigned_to = u.id
            WHERE mr.id = ?
        ");
        
        $stmt->execute([$maintenance_id]);
        $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($maintenance) {
            $status_badge = '';
            switch ($maintenance['status']) {
                case 'pending':
                    $status_badge = '<span class="badge bg-warning">Pending</span>';
                    break;
                case 'assigned':
                    $status_badge = '<span class="badge bg-info">Assigned</span>';
                    break;
                case 'in_progress':
                    $status_badge = '<span class="badge bg-primary">In Progress</span>';
                    break;
                case 'resolved':
                    $status_badge = '<span class="badge bg-success">Resolved</span>';
                    break;
                case 'cancelled':
                    $status_badge = '<span class="badge bg-secondary">Cancelled</span>';
                    break;
            }
            
            $priority_badge = '';
            switch ($maintenance['priority']) {
                case 'low':
                    $priority_badge = '<span class="badge bg-success">Low</span>';
                    break;
                case 'medium':
                    $priority_badge = '<span class="badge bg-warning">Medium</span>';
                    break;
                case 'high':
                    $priority_badge = '<span class="badge bg-danger">High</span>';
                    break;
            }
            
            $student_name = $maintenance['first_name'] . ' ' . $maintenance['last_name'];
            if ($maintenance['middle_name']) {
                $student_name = $maintenance['first_name'] . ' ' . $maintenance['middle_name'] . ' ' . $maintenance['last_name'];
            }
            
            $room_info = $maintenance['building_name'] . ' - Room ' . $maintenance['room_number'];
            
            $photo_html = '';
            if ($maintenance['photo_path']) {
                $photo_html = '<img src="../uploads/maintenance/' . htmlspecialchars($maintenance['photo_path']) . '" class="img-fluid rounded" alt="Maintenance Photo">';
            }
            
            $response = [
                'success' => true,
                'data' => [
                    'id' => $maintenance['id'],
                    'title' => htmlspecialchars($maintenance['title']),
                    'description' => nl2br(htmlspecialchars($maintenance['description'])),
                    'status' => $maintenance['status'],
                    'status_badge' => $status_badge,
                    'priority' => $maintenance['priority'],
                    'priority_badge' => $priority_badge,
                    'student_name' => $student_name,
                    'student_id' => $maintenance['student_id'],
                    'mobile_number' => $maintenance['mobile_number'],
                    'email' => $maintenance['email'],
                    'room_info' => $room_info,
                    'assigned_to' => $maintenance['assigned_to_name'] ?: 'Not Assigned',
                    'created_at' => date('F j, Y g:i A', strtotime($maintenance['created_at'])),
                    'updated_at' => $maintenance['updated_at'] ? date('F j, Y g:i A', strtotime($maintenance['updated_at'])) : 'Not updated',
                    'photo' => $photo_html,
                    'contact_preference' => $maintenance['contact_preference']
                ]
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Maintenance request not found']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in get_maintenance_details.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
