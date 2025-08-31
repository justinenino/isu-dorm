<?php
require_once '../config/config.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'])) {
    $complaint_id = sanitizeInput($_POST['complaint_id']);
    
    try {
        $pdo = getDBConnection();
        
        // Get complaint details for the logged-in student only
        $stmt = $pdo->prepare("
            SELECT * FROM complaints 
            WHERE id = ? AND student_id = ?
        ");
        
        $stmt->execute([$complaint_id, $_SESSION['user_id']]);
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
                                    <strong>Submitted:</strong> ' . date('F j, Y g:i A', strtotime($complaint['created_at'])) . '
                                </div>
                                <div class="col-md-6">
                                    <strong>Last Updated:</strong> ' . ($complaint['updated_at'] ? date('F j, Y g:i A', strtotime($complaint['updated_at'])) : 'Not updated') . '
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Contact Preference:</strong> ' . ucfirst($complaint['contact_preference']) . '
                                </div>
                            </div>
                            
                            ' . ($complaint['admin_response'] ? '
                            <div class="mb-3">
                                <strong>Admin Response:</strong>
                                <div class="alert alert-info">' . nl2br(htmlspecialchars($complaint['admin_response'])) . '</div>
                            </div>' : '
                            <div class="alert alert-warning">
                                <i class="fas fa-clock"></i> Your complaint is currently under review. We will respond as soon as possible.
                            </div>') . '
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Complaint Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Complaint ID:</strong> #' . $complaint['id'] . '</p>
                                    <p><strong>Priority:</strong> ' . ucfirst($complaint['priority']) . '</p>
                                    <p><strong>Status:</strong> ' . ucfirst(str_replace('_', ' ', $complaint['status'])) . '</p>
                                    <p><strong>Submitted:</strong> ' . date('M j, Y', strtotime($complaint['created_at'])) . '</p>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="mb-0">What Happens Next?</h6>
                                </div>
                                <div class="card-body">
                                    <small class="text-muted">
                                        <ul class="list-unstyled mb-0">
                                            <li><i class="fas fa-check text-success"></i> Your complaint is reviewed</li>
                                            <li><i class="fas fa-check text-success"></i> Admin will respond with updates</li>
                                            <li><i class="fas fa-check text-success"></i> Status will be updated accordingly</li>
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
            echo json_encode(['success' => false, 'message' => 'Complaint not found or access denied']);
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
