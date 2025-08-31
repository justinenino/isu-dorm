<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'])) {
    $complaint_id = sanitizeInput($_POST['complaint_id']);
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                s.first_name,
                s.last_name,
                s.middle_name,
                s.student_id,
                s.mobile_number,
                s.email
            FROM complaints c
            LEFT JOIN students s ON c.student_id = s.id
            WHERE c.id = ?
        ");
        
        $stmt->execute([$complaint_id]);
        $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($complaint) {
            $status_badge = '';
            switch ($complaint['status']) {
                case 'pending':
                    $status_badge = '<span class="badge bg-warning">Pending</span>';
                    break;
                case 'in_progress':
                    $status_badge = '<span class="badge bg-info">In Progress</span>';
                    break;
                case 'resolved':
                    $status_badge = '<span class="badge bg-success">Resolved</span>';
                    break;
                case 'closed':
                    $status_badge = '<span class="badge bg-secondary">Closed</span>';
                    break;
            }
            
            $priority_badge = '';
            switch ($complaint['priority']) {
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
            
            $student_name = $complaint['first_name'] . ' ' . $complaint['last_name'];
            if ($complaint['middle_name']) {
                $student_name = $complaint['first_name'] . ' ' . $complaint['middle_name'] . ' ' . $complaint['last_name'];
            }
            
            $response = [
                'success' => true,
                'data' => '
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="text-primary">' . htmlspecialchars($complaint['title']) . '</h5>
                            <p class="text-muted mb-3">' . nl2br(htmlspecialchars($complaint['description'])) . '</p>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Status:</strong> ' . $status_badge . '
                                </div>
                                <div class="col-md-6">
                                    <strong>Priority:</strong> ' . $priority_badge . '
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Created:</strong> ' . date('F j, Y g:i A', strtotime($complaint['created_at'])) . '
                                </div>
                                <div class="col-md-6">
                                    <strong>Updated:</strong> ' . ($complaint['updated_at'] ? date('F j, Y g:i A', strtotime($complaint['updated_at'])) : 'Not updated') . '
                                </div>
                            </div>
                            
                            ' . ($complaint['admin_response'] ? '
                            <div class="mb-3">
                                <strong>Admin Response:</strong>
                                <div class="alert alert-info">' . nl2br(htmlspecialchars($complaint['admin_response'])) . '</div>
                            </div>' : '') . '
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Student Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Name:</strong> ' . htmlspecialchars($student_name) . '</p>
                                    <p><strong>Student ID:</strong> ' . $complaint['student_id'] . '</p>
                                    <p><strong>Mobile:</strong> ' . ($complaint['mobile_number'] ?: 'Not provided') . '</p>
                                    <p><strong>Email:</strong> ' . ($complaint['email'] ?: 'Not provided') . '</p>
                                </div>
                            </div>
                        </div>
                    </div>
                '
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Complaint not found']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in get_complaint_details.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
