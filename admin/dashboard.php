<?php
$page_title = 'Dashboard';
include 'includes/header.php';

// Get statistics with error handling
$pdo = getConnection();

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
    // Room occupancy statistics
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_rooms,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms,
        SUM(CASE WHEN status != 'maintenance' AND occupied = 0 THEN 1 ELSE 0 END) as available_rooms,
        SUM(CASE WHEN status != 'maintenance' AND occupied > 0 AND occupied < capacity THEN 1 ELSE 0 END) as partially_occupied_rooms,
        SUM(CASE WHEN status != 'maintenance' AND occupied >= capacity THEN 1 ELSE 0 END) as full_rooms
        FROM rooms");
    $room_stats = $stmt->fetch();
    $room_stats['occupied_rooms'] = $room_stats['partially_occupied_rooms'] + $room_stats['full_rooms'];

    // Bed space statistics
    $stmt = $pdo->query("SELECT 
        SUM(capacity) as total_beds,
        SUM(occupied) as occupied_beds,
        SUM(capacity - occupied) as available_beds
        FROM rooms WHERE status != 'maintenance'");
    $bed_stats = $stmt->fetch();

    // Student statistics (updated for access control)
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_students,
        SUM(CASE WHEN application_status = 'pending' AND is_deleted = 0 AND is_active = 1 THEN 1 ELSE 0 END) as pending_applications,
        SUM(CASE WHEN application_status = 'approved' AND is_deleted = 0 AND is_active = 1 THEN 1 ELSE 0 END) as approved_students,
        SUM(CASE WHEN application_status = 'rejected' AND is_deleted = 0 AND is_active = 1 THEN 1 ELSE 0 END) as rejected_applications,
        SUM(CASE WHEN is_deleted = 1 THEN 1 ELSE 0 END) as archived_students,
        SUM(CASE WHEN is_active = 0 AND is_deleted = 0 THEN 1 ELSE 0 END) as inactive_students,
        SUM(CASE WHEN gender = 'Male' AND is_deleted = 0 AND is_active = 1 THEN 1 ELSE 0 END) as male_students,
        SUM(CASE WHEN gender = 'Female' AND is_deleted = 0 AND is_active = 1 THEN 1 ELSE 0 END) as female_students
        FROM students");
    $student_stats = $stmt->fetch();

    // Pending items
    $stmt = $pdo->query("SELECT COUNT(*) as pending_offenses FROM offenses WHERE status = 'pending'");
    $pending_offenses = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as pending_maintenance FROM maintenance_requests WHERE status = 'pending'");
    $pending_maintenance = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as pending_room_requests FROM room_change_requests WHERE status = 'pending'");
    $pending_room_requests = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as pending_complaints FROM complaints WHERE status = 'pending'");
    $pending_complaints = $stmt->fetchColumn();

    // Additional statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total_visitors FROM visitor_logs WHERE time_out IS NULL");
    $current_visitors = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total_announcements FROM announcements WHERE status = 'published'");
    $total_announcements = $stmt->fetchColumn();

} catch (Exception $e) {
    // If there's an error, use default values
    error_log("Dashboard error: " . $e->getMessage());
    
    // Show error message to user
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Database Error:</strong> Some data may not be displayed correctly. Error: ' . htmlspecialchars($e->getMessage()) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}
?>

<!-- Welcome Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-gradient-primary text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">Welcome back, <?php echo $_SESSION['username']; ?>!</h2>
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

<!-- Primary Metrics Cards - Top Row -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-4">
        <a href="room_management.php" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-primary"><?php echo $room_stats['available_rooms']; ?></h3>
                            <p class="mb-0 text-muted">Available Rooms</p>
                            <small class="text-success">of <?php echo $room_stats['total_rooms']; ?> total</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-bed text-primary"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-primary" style="width: <?php echo $room_stats['total_rooms'] > 0 ? ($room_stats['available_rooms'] / $room_stats['total_rooms']) * 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <a href="room_management.php" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-success"><?php echo $bed_stats['available_beds']; ?></h3>
                            <p class="mb-0 text-muted">Available Beds</p>
                            <small class="text-info">of <?php echo $bed_stats['total_beds']; ?> total</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-bed text-success"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $bed_stats['total_beds'] > 0 ? ($bed_stats['available_beds'] / $bed_stats['total_beds']) * 100 : 0; ?>%"></div>
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
                            <h3 class="mb-1 text-success"><?php echo $student_stats['approved_students']; ?></h3>
                            <p class="mb-0 text-muted">Active Students</p>
                            <small class="text-warning"><?php echo $student_stats['pending_applications']; ?> pending</small>
                            <br><small class="text-muted"><?php echo $student_stats['archived_students']; ?> archived</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-user-graduate text-success"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $student_stats['total_students'] > 0 ? ($student_stats['approved_students'] / $student_stats['total_students']) * 100 : 0; ?>%"></div>
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
</div>

<!-- Secondary Metrics Row -->
<div class="row mb-4">
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
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-danger" style="width: 100%"></div>
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
        <a href="reservation_management.php?filter=archived" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-secondary"><?php echo $student_stats['archived_students']; ?></h3>
                            <p class="mb-0 text-muted">Archived Students</p>
                            <small class="text-muted">Access revoked</small>
                        </div>
                        <div class="stats-icon-modern">
                            <i class="fas fa-archive text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Pie Charts Section -->
<div class="row mb-4">
    <!-- Student Status Pie Chart -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Student Status Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="studentStatusChart" width="300" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Room Occupancy Pie Chart -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Room Occupancy Status</h5>
            </div>
            <div class="card-body">
                <canvas id="roomOccupancyChart" width="300" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Bed Availability Pie Chart -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Bed Availability Status</h5>
            </div>
            <div class="card-body">
                <canvas id="bedAvailabilityChart" width="300" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions and System Overview -->
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
    
    <!-- System Overview -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-server"></i> System Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="border rounded p-3">
                            <h4 class="text-success"><?php echo $room_stats['total_rooms']; ?></h4>
                            <p class="mb-0">Total Rooms</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="border rounded p-3">
                            <h4 class="text-info"><?php echo $bed_stats['total_beds']; ?></h4>
                            <p class="mb-0">Total Bed Spaces</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="border rounded p-3">
                            <h4 class="text-success"><?php echo $bed_stats['available_beds']; ?></h4>
                            <p class="mb-0">Available Beds</p>
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

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Student Status Pie Chart
const studentStatusCtx = document.getElementById('studentStatusChart').getContext('2d');
const studentStatusChart = new Chart(studentStatusCtx, {
    type: 'pie',
    data: {
        labels: ['Active Students', 'Pending Applications', 'Archived Students', 'Inactive Students'],
        datasets: [{
            data: [
                <?php echo $student_stats['approved_students']; ?>,
                <?php echo $student_stats['pending_applications']; ?>,
                <?php echo $student_stats['archived_students']; ?>,
                <?php echo $student_stats['inactive_students']; ?>
            ],
            backgroundColor: [
                '#28a745', // Green for active
                '#ffc107', // Yellow for pending
                '#6c757d', // Gray for archived
                '#dc3545'  // Red for inactive
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return `${label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Room Occupancy Pie Chart
const roomOccupancyCtx = document.getElementById('roomOccupancyChart').getContext('2d');
const roomOccupancyChart = new Chart(roomOccupancyCtx, {
    type: 'pie',
    data: {
        labels: ['Available Rooms', 'Occupied Rooms', 'Maintenance Rooms'],
        datasets: [{
            data: [
                <?php echo $room_stats['available_rooms']; ?>,
                <?php echo $room_stats['occupied_rooms']; ?>,
                <?php echo $room_stats['maintenance_rooms']; ?>
            ],
            backgroundColor: [
                '#28a745', // Green for available
                '#007bff', // Blue for occupied
                '#ffc107'  // Yellow for maintenance
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return `${label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Bed Availability Pie Chart
const bedAvailabilityCtx = document.getElementById('bedAvailabilityChart').getContext('2d');
const bedAvailabilityChart = new Chart(bedAvailabilityCtx, {
    type: 'pie',
    data: {
        labels: ['Available Beds', 'Occupied Beds'],
        datasets: [{
            data: [
                <?php echo $bed_stats['available_beds']; ?>,
                <?php echo $bed_stats['occupied_beds']; ?>
            ],
            backgroundColor: [
                '#28a745', // Green for available
                '#dc3545'  // Red for occupied
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return `${label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
