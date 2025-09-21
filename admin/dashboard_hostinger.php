<?php
// Hostinger-specific admin dashboard
// Handles common Hostinger hosting differences

$page_title = 'Dashboard';
include 'includes/header.php';

// Enhanced error handling for Hostinger
ini_set('display_errors', 0); // Hide errors from users
ini_set('log_errors', 1); // Log errors to file
error_reporting(E_ALL);

// Get statistics with comprehensive error handling
$pdo = null;
$db_error = false;
$error_message = '';

// Initialize default values
$room_stats = [
    'total_rooms' => 0,
    'available_rooms' => 0,
    'occupied_rooms' => 0,
    'maintenance_rooms' => 0
];

$bed_stats = [
    'total_beds' => 0,
    'occupied_beds' => 0,
    'available_beds' => 0
];

$student_stats = [
    'total_students' => 0,
    'pending_applications' => 0,
    'approved_students' => 0,
    'rejected_applications' => 0,
    'male_students' => 0,
    'female_students' => 0
];

$pending_offenses = 0;
$pending_maintenance = 0;
$pending_room_requests = 0;
$pending_complaints = 0;
$current_visitors = 0;
$total_announcements = 0;

try {
    // Test database connection first
    $pdo = getConnection();
    
    // Test basic query
    $pdo->query("SELECT 1");
    
    // Room occupancy statistics
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_rooms,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms,
        SUM(CASE WHEN status != 'maintenance' AND occupied = 0 THEN 1 ELSE 0 END) as available_rooms,
        SUM(CASE WHEN status != 'maintenance' AND occupied > 0 AND occupied < capacity THEN 1 ELSE 0 END) as partially_occupied_rooms,
        SUM(CASE WHEN status != 'maintenance' AND occupied >= capacity THEN 1 ELSE 0 END) as full_rooms
        FROM rooms");
    $room_data = $stmt->fetch();
    if ($room_data) {
        $room_stats = $room_data;
        $room_stats['occupied_rooms'] = ($room_stats['partially_occupied_rooms'] ?? 0) + ($room_stats['full_rooms'] ?? 0);
    }

    // Bed space statistics
    $stmt = $pdo->query("SELECT 
        COALESCE(SUM(capacity), 0) as total_beds,
        COALESCE(SUM(occupied), 0) as occupied_beds,
        COALESCE(SUM(capacity - occupied), 0) as available_beds
        FROM rooms WHERE status != 'maintenance'");
    $bed_data = $stmt->fetch();
    if ($bed_data) {
        $bed_stats = $bed_data;
    }

    // Student statistics
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_students,
        SUM(CASE WHEN application_status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
        SUM(CASE WHEN application_status = 'approved' THEN 1 ELSE 0 END) as approved_students,
        SUM(CASE WHEN application_status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications,
        SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as male_students,
        SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female_students
        FROM students");
    $student_data = $stmt->fetch();
    if ($student_data) {
        $student_stats = $student_data;
    }

    // Pending items - with individual error handling
    $pending_queries = [
        'offenses' => "SELECT COUNT(*) FROM offenses WHERE status = 'pending'",
        'maintenance_requests' => "SELECT COUNT(*) FROM maintenance_requests WHERE status = 'pending'",
        'room_change_requests' => "SELECT COUNT(*) FROM room_change_requests WHERE status = 'pending'",
        'complaints' => "SELECT COUNT(*) FROM complaints WHERE status = 'pending'",
        'visitor_logs' => "SELECT COUNT(*) FROM visitor_logs WHERE time_out IS NULL",
        'announcements' => "SELECT COUNT(*) FROM announcements WHERE status = 'published'"
    ];

    foreach ($pending_queries as $key => $query) {
        try {
            $stmt = $pdo->query($query);
            $count = $stmt->fetchColumn();
            
            switch ($key) {
                case 'offenses':
                    $pending_offenses = $count;
                    break;
                case 'maintenance_requests':
                    $pending_maintenance = $count;
                    break;
                case 'room_change_requests':
                    $pending_room_requests = $count;
                    break;
                case 'complaints':
                    $pending_complaints = $count;
                    break;
                case 'visitor_logs':
                    $current_visitors = $count;
                    break;
                case 'announcements':
                    $total_announcements = $count;
                    break;
            }
        } catch (Exception $e) {
            error_log("Query failed for $key: " . $e->getMessage());
        }
    }

} catch (Exception $e) {
    $db_error = true;
    $error_message = $e->getMessage();
    error_log("Dashboard database error: " . $e->getMessage());
}

// Get admin username safely
$admin_username = $_SESSION['username'] ?? 'Admin';
?>

<!-- Error Display -->
<?php if ($db_error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Database Error:</strong> <?php echo htmlspecialchars($error_message); ?>
    <br><small>Please check your database configuration and ensure all tables exist.</small>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Welcome Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-gradient-primary text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">Welcome back, <?php echo htmlspecialchars($admin_username); ?>!</h2>
                        <p class="mb-0">Here's what's happening in your dormitory management system today.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <i class="fas fa-building fa-4x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Key Metrics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-4">
        <a href="room_management.php" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-primary"><?php echo $room_stats['available_rooms'] ?? 0; ?></h3>
                            <p class="mb-0 text-muted">Available Rooms</p>
                            <small class="text-success">of <?php echo $room_stats['total_rooms'] ?? 0; ?> total</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-bed text-primary"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-primary" style="width: <?php echo ($room_stats['total_rooms'] ?? 0) > 0 ? (($room_stats['available_rooms'] ?? 0) / ($room_stats['total_rooms'] ?? 1)) * 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <a href="reservation_management.php" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-success"><?php echo $student_stats['approved_students'] ?? 0; ?></h3>
                            <p class="mb-0 text-muted">Active Students</p>
                            <small class="text-warning"><?php echo $student_stats['pending_applications'] ?? 0; ?> pending</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-user-graduate text-success"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: <?php echo ($student_stats['total_students'] ?? 0) > 0 ? (($student_stats['approved_students'] ?? 0) / ($student_stats['total_students'] ?? 1)) * 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <a href="offense_logs.php" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-warning"><?php echo $pending_offenses; ?></h3>
                            <p class="mb-0 text-muted">Pending Offenses</p>
                            <small class="text-danger">Need attention</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-warning" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <a href="maintenance_requests.php" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-info"><?php echo $pending_maintenance; ?></h3>
                            <p class="mb-0 text-muted">Pending Maintenance</p>
                            <small class="text-info">Requests to process</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-tools text-info"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-info" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Additional Metrics Row -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-4">
        <a href="room_management.php" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-secondary"><?php echo $bed_stats['occupied_beds'] ?? 0; ?></h3>
                            <p class="mb-0 text-muted">Occupied Beds</p>
                            <small class="text-muted">of <?php echo $bed_stats['total_beds'] ?? 0; ?> total</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-bed text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <a href="visitor_logs.php" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-primary"><?php echo $current_visitors; ?></h3>
                            <p class="mb-0 text-muted">Current Visitors</p>
                            <small class="text-info">In dormitory now</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-users text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <a href="complaints_management.php" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-danger"><?php echo $pending_complaints; ?></h3>
                            <p class="mb-0 text-muted">Pending Complaints</p>
                            <small class="text-danger">Awaiting review</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-comment-alt text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <a href="announcements.php" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-success"><?php echo $total_announcements; ?></h3>
                            <p class="mb-0 text-muted">Published Announcements</p>
                            <small class="text-success">Active posts</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-bullhorn text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Quick Actions and Recent Activities -->
<div class="row">
    <!-- Quick Actions -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="maintenance_requests.php" class="btn btn-outline-primary">
                        <i class="fas fa-tools"></i> View Maintenance Requests
                    </a>
                    <a href="offense_logs.php" class="btn btn-outline-warning">
                        <i class="fas fa-exclamation-triangle"></i> Review Offenses
                    </a>
                    <a href="room_requests.php" class="btn btn-outline-info">
                        <i class="fas fa-exchange-alt"></i> Process Room Requests
                    </a>
                    <a href="complaints_management.php" class="btn btn-outline-secondary">
                        <i class="fas fa-comment-alt"></i> Handle Complaints
                    </a>
                    <a href="reservation_management.php" class="btn btn-outline-success">
                        <i class="fas fa-calendar-check"></i> Manage Reservations
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Status -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-server"></i> System Status</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="border rounded p-3">
                            <h4 class="text-success"><?php echo $room_stats['total_rooms'] ?? 0; ?></h4>
                            <p class="mb-0">Total Rooms</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="border rounded p-3">
                            <h4 class="text-info"><?php echo $bed_stats['total_beds'] ?? 0; ?></h4>
                            <p class="mb-0">Total Bed Spaces</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="border rounded p-3">
                            <h4 class="text-warning"><?php echo $pending_room_requests; ?></h4>
                            <p class="mb-0">Pending Room Requests</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="border rounded p-3">
                            <h4 class="text-danger"><?php echo $pending_complaints; ?></h4>
                            <p class="mb-0">Pending Complaints</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
