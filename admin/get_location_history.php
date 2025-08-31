<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = sanitizeInput($_POST['student_id']);
    
    try {
        $pdo = getDBConnection();
        
        // Get student details
        $stmt = $pdo->prepare("
            SELECT s.*, r.room_number, b.building_name, bs.bedspace_number
            FROM students s
            LEFT JOIN bedspaces bs ON s.id = bs.student_id
            LEFT JOIN rooms r ON bs.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            WHERE s.id = ?
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            echo '<div class="alert alert-danger">Student not found.</div>';
            exit;
        }
        
        // Get location history (last 30 days)
        $stmt = $pdo->prepare("
            SELECT sl.*, u.username as created_by_name
            FROM student_locations sl
            LEFT JOIN users u ON sl.created_by = u.id
            WHERE sl.student_id = ? AND sl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY sl.created_at DESC
        ");
        $stmt->execute([$student_id]);
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="fw-bold">Student Information</h6>
                                <p class="mb-1">
                                    <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong><br>
                                    <small class="text-muted">ID: <?php echo htmlspecialchars($student['student_id']); ?></small>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h6 class="fw-bold">Current Assignment</h6>
                                <?php if ($student['room_number']): ?>
                                    <p class="mb-1">
                                        <?php echo htmlspecialchars($student['building_name'] . ' - Room ' . $student['room_number']); ?><br>
                                        <small class="text-muted">Bedspace <?php echo $student['bedspace_number']; ?></small>
                                    </p>
                                <?php else: ?>
                                    <p class="text-muted">No room assignment</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <h6 class="fw-bold mb-3">Location History (Last 30 Days)</h6>
        
        <?php if (empty($locations)): ?>
            <div class="text-center py-4">
                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No location history</h5>
                <p class="text-muted">No location updates found for this student in the last 30 days.</p>
            </div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($locations as $index => $location): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker <?php 
                            switch ($location['location']) {
                                case 'inside_dorm': echo 'bg-success'; break;
                                case 'outside_campus': echo 'bg-warning'; break;
                                case 'in_class': echo 'bg-info'; break;
                                default: echo 'bg-secondary';
                            }
                        ?>"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">
                                        <?php
                                        $location_icon = '';
                                        switch ($location['location']) {
                                            case 'inside_dorm': $location_icon = 'fas fa-home'; break;
                                            case 'outside_campus': $location_icon = 'fas fa-map-marker-alt'; break;
                                            case 'in_class': $location_icon = 'fas fa-chalkboard-teacher'; break;
                                            default: $location_icon = 'fas fa-question';
                                        }
                                        ?>
                                        <i class="<?php echo $location_icon; ?>"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $location['location'])); ?>
                                    </h6>
                                    <p class="mb-1 text-muted">
                                        <?php echo formatDate($location['created_at']); ?>
                                        <?php if ($location['created_by_name']): ?>
                                            by <?php echo htmlspecialchars($location['created_by_name']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($location['notes']): ?>
                                        <p class="mb-0">
                                            <small><?php echo nl2br(htmlspecialchars($location['notes'])); ?></small>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="badge bg-light text-dark">
                                        <?php echo $index === 0 ? 'Latest' : '#' . ($index + 1); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
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
        error_log("Database error in get_location_history.php: " . $e->getMessage());
        echo '<div class="alert alert-danger">Database error occurred while loading location history.</div>';
    }
} else {
    http_response_code(400);
    echo '<div class="alert alert-danger">Invalid request.</div>';
}
?>
