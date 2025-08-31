<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offense_id'])) {
    $offense_id = sanitizeInput($_POST['offense_id']);
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                o.*,
                s.first_name,
                s.last_name,
                s.middle_name,
                s.student_id,
                s.mobile_number,
                s.email,
                u.username as created_by_name
            FROM offenses o
            LEFT JOIN students s ON o.student_id = s.id
            LEFT JOIN users u ON o.created_by = u.id
            WHERE o.id = ?
        ");
        
        $stmt->execute([$offense_id]);
        $offense = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($offense) {
            $status_badge = '';
            switch ($offense['status']) {
                case 'active':
                    $status_badge = '<span class="badge bg-warning">Active</span>';
                    break;
                case 'resolved':
                    $status_badge = '<span class="badge bg-success">Resolved</span>';
                    break;
            }
            
            $severity_badge = '';
            switch ($offense['severity']) {
                case 'low':
                    $severity_badge = '<span class="badge bg-success">Low</span>';
                    break;
                case 'medium':
                    $severity_badge = '<span class="badge bg-warning">Medium</span>';
                    break;
                case 'high':
                    $severity_badge = '<span class="badge bg-danger">High</span>';
                    break;
            }
            
            $student_name = $offense['first_name'] . ' ' . $offense['last_name'];
            if ($offense['middle_name']) {
                $student_name = $offense['first_name'] . ' ' . $offense['middle_name'] . ' ' . $offense['last_name'];
            }
            
            $response = [
                'success' => true,
                'data' => [
                    'id' => $offense['id'],
                    'offense_type' => htmlspecialchars($offense['offense_type']),
                    'description' => nl2br(htmlspecialchars($offense['description'])),
                    'severity' => $offense['severity'],
                    'severity_badge' => $severity_badge,
                    'status' => $offense['status'],
                    'status_badge' => $status_badge,
                    'student_name' => $student_name,
                    'student_id' => $offense['student_id'],
                    'mobile_number' => $offense['mobile_number'],
                    'email' => $offense['email'],
                    'action_taken' => nl2br(htmlspecialchars($offense['action_taken'])),
                    'created_by' => $offense['created_by_name'],
                    'date_occurred' => date('F j, Y', strtotime($offense['date_occurred'])),
                    'created_at' => date('F j, Y g:i A', strtotime($offense['created_at'])),
                    'updated_at' => $offense['updated_at'] ? date('F j, Y g:i A', strtotime($offense['updated_at'])) : 'Not updated'
                ]
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Offense record not found']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in get_offense_details.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
