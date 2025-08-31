<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

// Get student information
$student_id = $_SESSION['student_id'];
$student = fetchOne("
    SELECT s.*, u.username 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.student_id = ?
", [$student_id]);

// Get student's current reservation/room assignment
$current_assignment = fetchOne("
    SELECT r.*, b.label as bedspace_label, rm.room_number, bld.name as building_name
    FROM reservations r 
    JOIN bedspaces b ON r.bedspace_id = b.id 
    JOIN rooms rm ON b.room_id = rm.id 
    JOIN buildings bld ON rm.building_id = bld.id 
    WHERE r.student_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC 
    LIMIT 1
", [$student['id']]);

// Get recent announcements
$announcements = fetchAll("
    SELECT * FROM announcements 
    WHERE status = 'published' 
    AND (publish_at IS NULL OR publish_at <= NOW())
    AND (unpublish_at IS NULL OR unpublish_at > NOW())
    ORDER BY created_at DESC 
    LIMIT 5
");

// Get student's maintenance requests
$maintenance_requests = fetchAll("
    SELECT * FROM maintenance_requests 
    WHERE student_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$student['id']]);

// Get student's complaints
$complaints = fetchAll("
    SELECT * FROM complaints 
    WHERE student_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$student['id']]);

// Get student's recent visitors
$recent_visitors = fetchAll("
    SELECT * FROM visitors 
    WHERE student_id = ? 
    ORDER BY time_in DESC 
    LIMIT 5
", [$student['id']]);

$page_title = "Student Dashboard";
include 'includes/header.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">
            <i class="fas fa-user-graduate me-2"></i>Welcome, <?php echo $student['first_name']; ?>!
        </h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
            <a href="profile.php" class="btn btn-success">
                <i class="fas fa-user-edit me-2"></i>Edit Profile
            </a>
        </div>
    </div>

    <!-- Student Status Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-lg border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="card-title mb-2">
                                <i class="fas fa-id-card me-2"></i>Student Information
                            </h5>
                            <p class="card-text mb-1">
                                <strong>Student ID:</strong> <?php echo $student['student_id']; ?> | 
                                <strong>Name:</strong> <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                            </p>
                            <p class="card-text mb-1">
                                <strong>Email:</strong> <?php echo $student['email']; ?> | 
                                <strong>Mobile:</strong> <?php echo $student['mobile_number']; ?>
                            </p>
                            <p class="card-text mb-0">
                                <strong>Status:</strong> 
                                <span class="badge bg-<?php echo $student['registration_status'] === 'approved' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($student['registration_status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="avatar-placeholder bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Current Assignment -->
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Current Assignment</h5>
                <div class="card-icon occupancy">
                    <i class="fas fa-bed"></i>
                </div>
            </div>
            <?php if ($current_assignment): ?>
                <div class="card-value"><?php echo $current_assignment['building_name']; ?></div>
                <p class="card-description">
                    Room <?php echo $current_assignment['room_number']; ?> - 
                    <?php echo $current_assignment['bedspace_label']; ?>
                </p>
                <small class="text-muted">
                    Assigned: <?php echo formatDate($current_assignment['approved_at'], 'M j, Y'); ?>
                </small>
            <?php else: ?>
                <div class="card-value text-muted">No Assignment</div>
                <p class="card-description">You are not currently assigned to a room</p>
                <a href="reserve-room.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Reserve Room
                </a>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Quick Actions</h5>
                <div class="card-icon applicants">
                    <i class="fas fa-bolt"></i>
                </div>
            </div>
            <div class="d-grid gap-2">
                <a href="reserve-room.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-bed me-1"></i>Reserve Room
                </a>
                <a href="maintenance-request.php" class="btn btn-sm btn-outline-warning">
                    <i class="fas fa-tools me-1"></i>Maintenance Request
                </a>
                <a href="complaint.php" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-comment-dots me-1"></i>Submit Complaint
                </a>
                <a href="visitor-log.php" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-user-friends me-1"></i>Log Visitor
                </a>
            </div>
        </div>

        <!-- Notifications -->
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Notifications</h5>
                <div class="card-icon offenses">
                    <i class="fas fa-bell"></i>
                </div>
            </div>
            <div class="card-value"><?php echo count($announcements); ?></div>
            <p class="card-description">New announcements available</p>
            <a href="announcements.php" class="btn btn-sm btn-outline-success">
                <i class="fas fa-eye me-1"></i>View All
            </a>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Recent Activity</h5>
                <div class="card-icon maintenance">
                    <i class="fas fa-history"></i>
                </div>
            </div>
            <div class="card-value"><?php echo count($maintenance_requests) + count($complaints); ?></div>
            <p class="card-description">Recent requests and complaints</p>
            <a href="activity.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-list me-1"></i>View Details
            </a>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="row">
        <!-- Announcements -->
        <div class="col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bullhorn me-2"></i>Recent Announcements
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($announcements)): ?>
                        <p class="text-muted text-center">No announcements at this time</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($announcements as $announcement): ?>
                            <div class="list-group-item">
                                <h6 class="mb-1"><?php echo $announcement['title']; ?></h6>
                                <p class="mb-1 text-muted"><?php echo substr($announcement['content'], 0, 100) . '...'; ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo formatDate($announcement['created_at'], 'M j, Y'); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="announcements.php" class="btn btn-primary">
                                View All Announcements
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Maintenance Requests -->
        <div class="col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tools me-2"></i>Maintenance Requests
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($maintenance_requests)): ?>
                        <p class="text-muted text-center">No maintenance requests</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($maintenance_requests as $request): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $request['title']; ?></h6>
                                    <small class="text-muted">
                                        Status: 
                                        <span class="badge bg-<?php 
                                            echo $request['status'] === 'resolved' ? 'success' : 
                                                ($request['status'] === 'in_progress' ? 'info' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                        </span>
                                    </small>
                                </div>
                                <small class="text-muted">
                                    <?php echo formatDate($request['created_at'], 'M j'); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="maintenance-requests.php" class="btn btn-warning">
                                View All Requests
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Sections -->
    <div class="row mt-4">
        <!-- Complaints -->
        <div class="col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comment-dots me-2"></i>Recent Complaints
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($complaints)): ?>
                        <p class="text-muted text-center">No complaints submitted</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($complaints as $complaint): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $complaint['subject']; ?></h6>
                                    <small class="text-muted">
                                        Status: 
                                        <span class="badge bg-<?php 
                                            echo $complaint['status'] === 'resolved' ? 'success' : 
                                                ($complaint['status'] === 'under_review' ? 'info' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                        </span>
                                    </small>
                                </div>
                                <small class="text-muted">
                                    <?php echo formatDate($complaint['created_at'], 'M j'); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="complaints.php" class="btn btn-danger">
                                View All Complaints
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Visitors -->
        <div class="col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-friends me-2"></i>Recent Visitors
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_visitors)): ?>
                        <p class="text-muted text-center">No recent visitors</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_visitors as $visitor): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $visitor['visitor_name']; ?></h6>
                                    <small class="text-muted">
                                        Status: 
                                        <span class="badge bg-<?php echo $visitor['status'] === 'inside' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($visitor['status']); ?>
                                        </span>
                                    </small>
                                </div>
                                <small class="text-muted">
                                    <?php echo formatDate($visitor['time_in'], 'M j, g:i A'); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="visitors.php" class="btn btn-info">
                                View All Visitors
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshDashboard() {
    location.reload();
}

// Auto-refresh every 10 minutes
setInterval(function() {
    // Only refresh if user is active
    if (!document.hidden) {
        refreshDashboard();
    }
}, 600000);
</script>

<?php include 'includes/footer.php'; ?>
