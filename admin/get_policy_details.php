<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['policy_id'])) {
    $policy_id = sanitizeInput($_POST['policy_id']);
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                u.username as created_by_name
            FROM policies p
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.id = ?
        ");
        
        $stmt->execute([$policy_id]);
        $policy = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($policy) {
            $status_badge = '';
            switch ($policy['status']) {
                case 'active':
                    $status_badge = '<span class="badge bg-success">Active</span>';
                    break;
                case 'draft':
                    $status_badge = '<span class="badge bg-warning">Draft</span>';
                    break;
                case 'archived':
                    $status_badge = '<span class="badge bg-secondary">Archived</span>';
                    break;
            }
            
            $type_badge = '';
            switch ($policy['policy_type']) {
                case 'general':
                    $type_badge = '<span class="badge bg-primary">General Rules</span>';
                    break;
                case 'conduct':
                    $type_badge = '<span class="badge bg-info">Code of Conduct</span>';
                    break;
                case 'safety':
                    $type_badge = '<span class="badge bg-danger">Safety & Security</span>';
                    break;
                case 'visitors':
                    $type_badge = '<span class="badge bg-success">Visitor Policies</span>';
                    break;
                case 'maintenance':
                    $type_badge = '<span class="badge bg-warning">Maintenance</span>';
                    break;
                case 'offenses':
                    $type_badge = '<span class="badge bg-dark">Offenses & Penalties</span>';
                    break;
            }
            
            $response = [
                'success' => true,
                'data' => '
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="text-primary">' . htmlspecialchars($policy['title']) . '</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Type:</strong> ' . $type_badge . '
                                </div>
                                <div class="col-md-6">
                                    <strong>Status:</strong> ' . $status_badge . '
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Created:</strong> ' . date('F j, Y g:i A', strtotime($policy['created_at'])) . '
                                </div>
                                <div class="col-md-6">
                                    <strong>Updated:</strong> ' . ($policy['updated_at'] ? date('F j, Y g:i A', strtotime($policy['updated_at'])) : 'Not updated') . '
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Content:</strong>
                                <div class="border rounded p-3 mt-2" style="max-height: 300px; overflow-y: auto;">
                                    ' . nl2br(htmlspecialchars($policy['content'])) . '
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Policy Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Policy ID:</strong> #' . $policy['id'] . '</p>
                                    <p><strong>Type:</strong> ' . ucfirst($policy['policy_type']) . '</p>
                                    <p><strong>Status:</strong> ' . ucfirst($policy['status']) . '</p>
                                    <p><strong>Created By:</strong> ' . htmlspecialchars($policy['created_by_name']) . '</p>
                                    
                                    ' . ($policy['file_path'] ? '
                                    <div class="mt-3">
                                        <strong>Attached File:</strong><br>
                                        <a href="../uploads/policies/' . htmlspecialchars($policy['file_path']) . '" 
                                           class="btn btn-sm btn-primary" download>
                                            <i class="fas fa-download"></i> Download File
                                        </a>
                                    </div>' : '') . '
                                </div>
                            </div>
                        </div>
                    </div>
                ',
                'edit_data' => [
                    'id' => $policy['id'],
                    'title' => $policy['title'],
                    'content' => $policy['content'],
                    'policy_type' => $policy['policy_type'],
                    'status' => $policy['status']
                ]
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Policy not found']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in get_policy_details.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
