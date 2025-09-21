<?php
$page_title = 'Dashboard';
include 'includes/header.php';

// Get student information and statistics using optimized queries
$pdo = getConnection();

try {
    // Get student dashboard data using stored procedure
    $stmt = $pdo->prepare("CALL GetStudentDashboardData(?)");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    // Get recent announcements with optimized query
    $stmt = $pdo->prepare("SELECT * FROM announcements 
        WHERE status = 'published' 
        AND (is_archived IS NULL OR is_archived = 0)
        AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY COALESCE(is_pinned, 0) DESC, COALESCE(published_at, created_at) DESC 
        LIMIT 3");
    $stmt->execute();
    $recent_announcements = $stmt->fetchAll();
    
    // Get student statistics using stored procedure
    $stmt = $pdo->prepare("CALL GetStudentStatistics(?)");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch();
    
    $pending_maintenance = $stats['pending_maintenance_requests'] ?? 0;
    $pending_room_requests = $stats['pending_room_requests'] ?? 0;
    $pending_complaints = $stats['pending_complaints'] ?? 0;
    $total_offenses = $stats['total_offenses'] ?? 0;
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    // Fallback to basic queries if stored procedures fail
    $stmt = $pdo->prepare("SELECT s.*, 
        CONCAT(s.first_name, ' ', IFNULL(s.middle_name, ''), ' ', s.last_name) as full_name,
        r.room_number, r.floor_number, b.name as building_name, bs.bed_number
        FROM students s
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN bed_spaces bs ON s.bed_space_id = bs.id
        WHERE s.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
    $recent_announcements = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_maintenance FROM maintenance_requests WHERE student_id = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id']]);
    $pending_maintenance = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_room_requests FROM room_change_requests WHERE student_id = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id']]);
    $pending_room_requests = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_complaints FROM complaints WHERE student_id = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id']]);
    $pending_complaints = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_offenses FROM offenses WHERE student_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_offenses = $stmt->fetchColumn();
}
?>

<!-- Welcome Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-home"></i> Welcome, <?php echo htmlspecialchars($student['first_name']); ?>!</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6>Personal Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                                <p><strong>School ID:</strong> <?php echo htmlspecialchars($student['school_id']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Mobile:</strong> <?php echo htmlspecialchars($student['mobile_number']); ?></p>
                                <p><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></p>
                                <p><strong>Date of Birth:</strong> <?php echo date('M j, Y', strtotime($student['date_of_birth'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6>Room Assignment</h6>
                        <?php if ($student['room_id']): ?>
                            <div class="alert alert-success">
                                <h6><i class="fas fa-bed"></i> Room Details</h6>
                                <p class="mb-1"><strong><?php echo htmlspecialchars($student['building_name']); ?></strong></p>
                                <p class="mb-1">Room <?php echo htmlspecialchars($student['room_number']); ?></p>
                                <p class="mb-0">Bed <?php echo htmlspecialchars($student['bed_number']); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <p class="mb-0">No room assigned yet. Please wait for admin approval.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card text-center">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?php echo $pending_maintenance; ?></h3>
                    <p class="mb-0">Pending Maintenance</p>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-tools"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?php echo $pending_room_requests; ?></h3>
                    <p class="mb-0">Room Requests</p>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?php echo $pending_complaints; ?></h3>
                    <p class="mb-0">Pending Complaints</p>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-comment-alt"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #dc3545 0%, #e91e63 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?php echo $total_offenses; ?></h3>
                    <p class="mb-0">Total Offenses</p>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions and Recent Announcements -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="maintenance_requests.php" class="btn btn-primary w-100">
                            <i class="fas fa-tools"></i><br>
                            Request Maintenance<br>
                            <small>Report issues</small>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="room_requests.php" class="btn btn-success w-100">
                            <i class="fas fa-exchange-alt"></i><br>
                            Room Change<br>
                            <small>Request transfer</small>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="visitor_logs.php" class="btn btn-info w-100">
                            <i class="fas fa-users"></i><br>
                            Register Visitor<br>
                            <small>Log guests</small>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="complaints.php" class="btn btn-warning w-100">
                            <i class="fas fa-comment-alt"></i><br>
                            Submit Complaint<br>
                            <small>Report concerns</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bullhorn"></i> Recent Announcements</h5>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($recent_announcements)): ?>
                    <p class="text-muted text-center">No announcements available</p>
                <?php else: ?>
                    <?php foreach ($recent_announcements as $announcement): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <h6 class="mb-2"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                            <p class="mb-1 text-truncate"><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)) . '...'; ?></p>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($announcement['created_at'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                    <div class="text-center">
                        <a href="announcements.php" class="btn btn-outline-primary btn-sm">View All Announcements</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Important Links -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-link"></i> Important Links</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="dorm_policies.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-file-alt"></i> Dormitory Policies
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="biometric_logs.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-fingerprint"></i> Biometric Logs
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="offense_records.php" class="btn btn-outline-warning w-100">
                            <i class="fas fa-exclamation-triangle"></i> My Offense Records
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="announcements.php" class="btn btn-outline-success w-100">
                            <i class="fas fa-bullhorn"></i> All Announcements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>