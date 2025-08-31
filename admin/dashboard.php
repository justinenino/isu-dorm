<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

// Get dashboard statistics
$stats = [];

// Total students
$stats['total_students'] = fetchOne("SELECT COUNT(*) as count FROM students")['count'];
$stats['pending_students'] = fetchOne("SELECT COUNT(*) as count FROM students WHERE registration_status = 'pending'")['count'];
$stats['approved_students'] = fetchOne("SELECT COUNT(*) as count FROM students WHERE registration_status = 'approved'")['count'];

// Building and room statistics
$stats['total_buildings'] = fetchOne("SELECT COUNT(*) as count FROM buildings WHERE status = 'active'")['count'];
$stats['total_rooms'] = fetchOne("SELECT COUNT(*) as count FROM rooms WHERE status = 'active'")['count'];
$stats['total_bedspaces'] = fetchOne("SELECT COUNT(*) as count FROM bedspaces")['count'];
$stats['occupied_bedspaces'] = fetchOne("SELECT COUNT(*) as count FROM bedspaces WHERE status = 'occupied'")['count'];
$stats['available_bedspaces'] = fetchOne("SELECT COUNT(*) as count FROM bedspaces WHERE status = 'available'")['count'];

// Reservation statistics
$stats['pending_reservations'] = fetchOne("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'")['count'];
$stats['total_reservations'] = fetchOne("SELECT COUNT(*) as count FROM reservations")['count'];

// Maintenance and complaints
$stats['pending_maintenance'] = fetchOne("SELECT COUNT(*) as count FROM maintenance_requests WHERE status = 'pending'")['count'];
$stats['pending_complaints'] = fetchOne("SELECT COUNT(*) as count FROM complaints WHERE status = 'pending'")['count'];
$stats['pending_offenses'] = fetchOne("SELECT COUNT(*) as count FROM offenses WHERE status = 'pending'")['count'];

// Recent activities
$recent_activities = fetchAll("
    SELECT al.*, u.username, u.role 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");

// Recent pending students
$recent_pending_students = fetchAll("
    SELECT s.*, u.username 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.registration_status = 'pending' 
    ORDER BY s.created_at DESC 
    LIMIT 5
");

// Recent reservations
$recent_reservations = fetchAll("
    SELECT r.*, s.first_name, s.last_name, s.student_id, b.label as bedspace_label
    FROM reservations r 
    JOIN students s ON r.student_id = s.id 
    JOIN bedspaces b ON r.bedspace_id = b.id 
    WHERE r.status = 'pending' 
    ORDER BY r.created_at DESC 
    LIMIT 5
");

$page_title = "Admin Dashboard";
include 'includes/header.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="refreshStats()">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
            <a href="backup/create.php" class="btn btn-success">
                <i class="fas fa-download me-2"></i>Create Backup
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="dashboard-grid">
        <!-- Occupancy Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Occupancy Rate</h5>
                <div class="card-icon occupancy">
                    <i class="fas fa-bed"></i>
                </div>
            </div>
            <div class="card-value">
                <?php 
                $occupancy_rate = $stats['total_bedspaces'] > 0 ? 
                    round(($stats['occupied_bedspaces'] / $stats['total_bedspaces']) * 100) : 0;
                echo $occupancy_rate . '%';
                ?>
            </div>
            <p class="card-description">
                <?php echo $stats['occupied_bedspaces']; ?> of <?php echo $stats['total_bedspaces']; ?> bedspaces occupied
            </p>
        </div>

        <!-- Students Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Total Students</h5>
                <div class="card-icon applicants">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="card-value"><?php echo $stats['total_students']; ?></div>
            <p class="card-description">
                <?php echo $stats['pending_students']; ?> pending approval
            </p>
        </div>

        <!-- Offenses Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Pending Offenses</h5>
                <div class="card-icon offenses">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="card-value"><?php echo $stats['pending_offenses']; ?></div>
            <p class="card-description">
                Require immediate attention
            </p>
        </div>

        <!-- Maintenance Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Maintenance Requests</h5>
                <div class="card-icon maintenance">
                    <i class="fas fa-tools"></i>
                </div>
            </div>
            <div class="card-value"><?php echo $stats['pending_maintenance']; ?></div>
            <p class="card-description">
                Pending maintenance tickets
            </p>
        </div>
    </div>

    <!-- Quick Actions and Recent Activities -->
    <div class="row">
        <!-- Quick Actions -->
        <div class="col-lg-4">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="students/pending.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-check me-2"></i>
                            Review Pending Students (<?php echo $stats['pending_students']; ?>)
                        </a>
                        <a href="reservations/pending.php" class="btn btn-outline-success">
                            <i class="fas fa-calendar-check me-2"></i>
                            Process Reservations (<?php echo $stats['pending_reservations']; ?>)
                        </a>
                        <a href="maintenance/pending.php" class="btn btn-outline-warning">
                            <i class="fas fa-tools me-2"></i>
                            Handle Maintenance (<?php echo $stats['pending_maintenance']; ?>)
                        </a>
                        <a href="offenses/pending.php" class="btn btn-outline-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Review Offenses (<?php echo $stats['pending_offenses']; ?>)
                        </a>
                        <a href="announcements/create.php" class="btn btn-outline-info">
                            <i class="fas fa-bullhorn me-2"></i>
                            Create Announcement
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-lg-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Recent Activities
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activities as $activity): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php echo $activity['role'] === 'admin' ? 'primary' : 'success'; ?>">
                                            <?php echo $activity['username'] ?? 'System'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?></td>
                                    <td><?php echo $activity['details']; ?></td>
                                    <td><?php echo formatDate($activity['created_at'], 'M j, g:i A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Pending Items -->
    <div class="row mt-4">
        <!-- Pending Students -->
        <div class="col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-clock me-2"></i>Recent Pending Students
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_pending_students)): ?>
                        <p class="text-muted text-center">No pending student registrations</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_pending_students as $student): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h6>
                                    <small class="text-muted">ID: <?php echo $student['student_id']; ?></small>
                                </div>
                                <a href="students/view.php?id=<?php echo $student['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>Review
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="students/pending.php" class="btn btn-warning">
                                View All Pending Students
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pending Reservations -->
        <div class="col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-clock me-2"></i>Recent Reservations
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_reservations)): ?>
                        <p class="text-muted text-center">No pending reservations</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_reservations as $reservation): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $reservation['first_name'] . ' ' . $reservation['last_name']; ?></h6>
                                    <small class="text-muted">Bed: <?php echo $reservation['bedspace_label']; ?></small>
                                </div>
                                <a href="reservations/view.php?id=<?php echo $reservation['id']; ?>" 
                                   class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye me-1"></i>Review
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="reservations/pending.php" class="btn btn-info">
                                View All Pending Reservations
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshStats() {
    location.reload();
}

// Auto-refresh every 5 minutes
setInterval(function() {
    // Only refresh if user is active
    if (!document.hidden) {
        refreshStats();
    }
}, 300000);
</script>

<?php include 'includes/footer.php'; ?>
