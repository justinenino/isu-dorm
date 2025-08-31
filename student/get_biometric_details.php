<?php
require_once '../config/config.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['biometric_id'])) {
    $biometric_id = sanitizeInput($_POST['biometric_id']);
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                u.username as uploaded_by_name,
                u.email as uploader_email
            FROM biometrics b
            LEFT JOIN users u ON b.uploaded_by = u.id
            WHERE b.id = ?
        ");
        
        $stmt->execute([$biometric_id]);
        $biometric = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($biometric) {
            $file_path = '../uploads/biometrics/' . $biometric['filename'];
            $file_exists = file_exists($file_path);
            
            // Calculate file age
            $upload_time = strtotime($biometric['uploaded_at']);
            $current_time = time();
            $age_seconds = $current_time - $upload_time;
            $age_days = floor($age_seconds / 86400);
            $age_hours = floor(($age_seconds % 86400) / 3600);
            
            $file_age = '';
            if ($age_days > 0) {
                $file_age = $age_days . ' day' . ($age_days > 1 ? 's' : '');
                if ($age_hours > 0) {
                    $file_age .= ', ' . $age_hours . ' hour' . ($age_hours > 1 ? 's' : '');
                }
            } else {
                $file_age = $age_hours . ' hour' . ($age_hours > 1 ? 's' : '');
            }
            $file_age .= ' ago';
            
            $response = [
                'success' => true,
                'data' => '
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="text-primary">' . htmlspecialchars($biometric['original_filename']) . '</h5>
                            <p class="text-muted mb-3">' . htmlspecialchars($biometric['description'] ?: 'No description provided') . '</p>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>File Size:</strong> ' . formatFileSize($biometric['file_size']) . '
                                </div>
                                <div class="col-md-6">
                                    <strong>File Type:</strong> ' . strtoupper(pathinfo($biometric['original_filename'], PATHINFO_EXTENSION)) . '
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Status:</strong> ' . ($file_exists ? '<span class="badge bg-success">Available</span>' : '<span class="badge bg-danger">File Missing</span>') . '
                                </div>
                                <div class="col-md-6">
                                    <strong>System Filename:</strong> <code>' . htmlspecialchars($biometric['filename']) . '</code>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Data Privacy Notice</h6>
                                <p class="mb-0">This file contains your personal biometric data. Please handle it securely and do not share it with unauthorized persons.</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Upload Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Upload Date:</strong><br>' . date('F j, Y', strtotime($biometric['uploaded_at'])) . '</p>
                                    <p><strong>Upload Time:</strong><br>' . date('g:i A', strtotime($biometric['uploaded_at'])) . '</p>
                                    <p><strong>Uploaded By:</strong><br>' . htmlspecialchars($biometric['uploaded_by_name']) . '</p>
                                    <p><strong>File Age:</strong><br>' . $file_age . '</p>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Download Options</h6>
                                </div>
                                <div class="card-body">
                                    ' . ($file_exists ? '
                                    <a href="../uploads/biometrics/' . htmlspecialchars($biometric['filename']) . '" 
                                       class="btn btn-primary btn-sm w-100 mb-2" download>
                                        <i class="fas fa-download"></i> Download File
                                    </a>
                                    <small class="text-muted d-block text-center">
                                        Right-click to save as different name
                                    </small>' : '
                                    <div class="alert alert-warning p-2">
                                        <small><i class="fas fa-exclamation-triangle"></i> File is currently unavailable</small>
                                    </div>') . '
                                </div>
                            </div>
                        </div>
                    </div>
                '
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Biometric file not found']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in get_biometric_details.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
