<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_id'])) {
    $transfer_id = sanitizeInput($_POST['transfer_id']);
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                rt.*,
                s.first_name, s.last_name, s.middle_name, s.student_id, s.mobile_number, s.email,
                s.facebook_link, s.emergency_contact_name, s.emergency_contact_mobile,
                cr.room_number as current_room, cb.building_name as current_building,
                tr.room_number as target_room, tb.building_name as target_building,
                cbs.bedspace_number as current_bedspace, tbs.bedspace_number as target_bedspace,
                u.username as processed_by_name
            FROM room_transfers rt
            LEFT JOIN students s ON rt.student_id = s.id
            LEFT JOIN bedspaces cbs ON rt.current_bedspace_id = cbs.id
            LEFT JOIN rooms cr ON cbs.room_id = cr.id
            LEFT JOIN buildings cb ON cr.building_id = cb.id
            LEFT JOIN bedspaces tbs ON rt.target_bedspace_id = tbs.id
            LEFT JOIN rooms tr ON tbs.room_id = tr.id
            LEFT JOIN buildings tb ON tr.building_id = tb.id
            LEFT JOIN users u ON rt.processed_by = u.id
            WHERE rt.id = ?
        ");
        $stmt->execute([$transfer_id]);
        $transfer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transfer) {
            echo '<div class="alert alert-danger">Transfer request not found.</div>';
            exit;
        }
        
        // Check current bedspace availability
        $stmt = $pdo->prepare("SELECT status FROM bedspaces WHERE id = ?");
        $stmt->execute([$transfer['target_bedspace_id']]);
        $target_bedspace = $stmt->fetch(PDO::FETCH_ASSOC);
        $target_available = $target_bedspace && $target_bedspace['status'] === 'available';
        
        ?>
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold mb-3">Transfer Request Information</h6>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Request ID:</label>
                    <p class="mb-1"><?php echo htmlspecialchars($transfer['id']); ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Status:</label>
                    <p class="mb-1">
                        <?php
                        $status_class = '';
                        switch ($transfer['status']) {
                            case 'pending':
                                $status_class = 'bg-warning';
                                break;
                            case 'approved':
                                $status_class = 'bg-success';
                                break;
                            case 'rejected':
                                $status_class = 'bg-danger';
                                break;
                        }
                        ?>
                        <span class="badge <?php echo $status_class; ?>">
                            <?php echo ucfirst($transfer['status']); ?>
                        </span>
                        <?php if (!$target_available && $transfer['status'] === 'pending'): ?>
                            <span class="badge bg-warning ms-2">Target Unavailable</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Current Room:</label>
                    <p class="mb-1">
                        <?php echo htmlspecialchars($transfer['current_building'] . ' - Room ' . $transfer['current_room']); ?>
                        <br>
                        <small class="text-muted">Bedspace <?php echo $transfer['current_bedspace']; ?></small>
                    </p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Requested Room:</label>
                    <p class="mb-1">
                        <?php echo htmlspecialchars($transfer['target_building'] . ' - Room ' . $transfer['target_room']); ?>
                        <br>
                        <small class="text-muted">Bedspace <?php echo $transfer['target_bedspace']; ?></small>
                    </p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Reason for Transfer:</label>
                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($transfer['reason'])); ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Contact Preference:</label>
                    <p class="mb-1">
                        <span class="badge bg-info"><?php echo ucfirst($transfer['contact_preference']); ?></span>
                    </p>
                </div>
            </div>
            
            <div class="col-md-6">
                <h6 class="fw-bold mb-3">Student Information</h6>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Name:</label>
                    <p class="mb-1">
                        <?php 
                        $full_name = trim($transfer['first_name'] . ' ' . $transfer['middle_name'] . ' ' . $transfer['last_name']);
                        echo htmlspecialchars($full_name); 
                        ?>
                    </p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Student ID:</label>
                    <p class="mb-1"><?php echo htmlspecialchars($transfer['student_id']); ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Contact Information:</label>
                    <p class="mb-1">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($transfer['mobile_number']); ?><br>
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($transfer['email']); ?>
                        <?php if ($transfer['facebook_link']): ?>
                            <br><i class="fab fa-facebook"></i> 
                            <a href="<?php echo htmlspecialchars($transfer['facebook_link']); ?>" target="_blank">Facebook</a>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Emergency Contact:</label>
                    <p class="mb-1">
                        <?php echo htmlspecialchars($transfer['emergency_contact_name']); ?><br>
                        <small class="text-muted"><?php echo htmlspecialchars($transfer['emergency_contact_mobile']); ?></small>
                    </p>
                </div>
                
                <?php if ($transfer['admin_notes']): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Admin Notes:</label>
                        <div class="alert alert-info">
                            <?php echo nl2br(htmlspecialchars($transfer['admin_notes'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($transfer['processed_by_name']): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Processed By:</label>
                        <p class="mb-1"><?php echo htmlspecialchars($transfer['processed_by_name']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h6 class="fw-bold mb-3">Timeline</h6>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Transfer Request Submitted</h6>
                            <p class="mb-0 text-muted"><?php echo formatDate($transfer['created_at']); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($transfer['processed_at']): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker <?php echo $transfer['status'] === 'approved' ? 'bg-success' : 'bg-danger'; ?>"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Request <?php echo ucfirst($transfer['status']); ?></h6>
                                <p class="mb-0 text-muted">
                                    <?php echo formatDate($transfer['processed_at']); ?>
                                    <?php if ($transfer['processed_by_name']): ?>
                                        by <?php echo htmlspecialchars($transfer['processed_by_name']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-marker {
            position: absolute;
            left: -22px;
            top: 0;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #e9ecef;
        }
        
        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #007bff;
        }
        </style>
        
        <?php
        
    } catch (PDOException $e) {
        error_log("Database error in get_transfer_details.php: " . $e->getMessage());
        echo '<div class="alert alert-danger">Database error occurred while loading transfer details.</div>';
    }
} else {
    http_response_code(400);
    echo '<div class="alert alert-danger">Invalid request.</div>';
}
?>
