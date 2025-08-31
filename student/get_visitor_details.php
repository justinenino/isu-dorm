<?php
require_once '../config/config.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visitor_id'])) {
    $visitor_id = sanitizeInput($_POST['visitor_id']);
    
    try {
        $pdo = getDBConnection();
        
        // Get visitor details for the logged-in student only
        $stmt = $pdo->prepare("
            SELECT * FROM visitors 
            WHERE id = ? AND student_id = ?
        ");
        
        $stmt->execute([$visitor_id, $_SESSION['user_id']]);
        $visitor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($visitor) {
            $status_badge = '';
            switch ($visitor['status']) {
                case 'pending':
                    $status_badge = '<span class="badge bg-warning">Pending Approval</span>';
                    break;
                case 'approved':
                    $status_badge = '<span class="badge bg-success">Approved</span>';
                    break;
                case 'checked_in':
                    $status_badge = '<span class="badge bg-info">Checked In</span>';
                    break;
                case 'checked_out':
                    $status_badge = '<span class="badge bg-secondary">Checked Out</span>';
                    break;
                case 'rejected':
                    $status_badge = '<span class="badge bg-danger">Rejected</span>';
                    break;
            }
            
            $response = [
                'success' => true,
                'data' => '
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="text-primary">' . htmlspecialchars($visitor['visitor_name']) . '</h5>
                            <p class="text-muted mb-3">Age: ' . $visitor['visitor_age'] . ' years old</p>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Status:</strong> ' . $status_badge . '
                                </div>
                                <div class="col-md-6">
                                    <strong>Room:</strong> ' . htmlspecialchars($visitor['room_number']) . '
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Contact:</strong> ' . htmlspecialchars($visitor['visitor_contact']) . '
                                </div>
                                <div class="col-md-6">
                                    <strong>Relationship:</strong> ' . htmlspecialchars($visitor['relationship'] ?: 'Not specified') . '
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <strong>Address:</strong> ' . htmlspecialchars($visitor['visitor_address'] ?: 'Not provided') . '
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <strong>Purpose:</strong> ' . htmlspecialchars($visitor['purpose'] ?: 'Not specified') . '
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Expected Duration:</strong> ' . htmlspecialchars($visitor['expected_duration']) . '
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Visit Timeline</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Registered:</strong><br>' . date('F j, Y g:i A', strtotime($visitor['created_at'])) . '</p>
                                    
                                    ' . ($visitor['time_in'] ? '<p><strong>Checked In:</strong><br>' . date('F j, Y g:i A', strtotime($visitor['time_in'])) . '</p>' : '') . '
                                    
                                    ' . ($visitor['time_out'] ? '<p><strong>Checked Out:</strong><br>' . date('F j, Y g:i A', strtotime($visitor['time_out'])) . '</p>' : '') . '
                                    
                                    ' . (!$visitor['time_out'] && $visitor['status'] === 'checked_in' ? '<div class="alert alert-warning p-2"><small><i class="fas fa-clock"></i> Currently visiting</small></div>' : '') . '
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Important Notes</h6>
                                </div>
                                <div class="card-body">
                                    <small class="text-muted">
                                        <ul class="list-unstyled mb-0">
                                            <li><i class="fas fa-check text-success"></i> Must check in at front desk</li>
                                            <li><i class="fas fa-check text-success"></i> Follow dormitory rules</li>
                                            <li><i class="fas fa-check text-success"></i> Check out before closing time</li>
                                        </ul>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                '
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Visitor not found or access denied']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in get_visitor_details.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
