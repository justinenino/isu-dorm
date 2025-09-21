<?php
$page_title = 'Dashboard';
include 'includes/header.php';

// Get statistics
$pdo = getConnection();

// Room occupancy statistics - Based on room management system logic
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_rooms,
    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms,
    SUM(CASE WHEN status != 'maintenance' AND occupied = 0 THEN 1 ELSE 0 END) as available_rooms,
    SUM(CASE WHEN status != 'maintenance' AND occupied > 0 AND occupied < capacity THEN 1 ELSE 0 END) as partially_occupied_rooms,
    SUM(CASE WHEN status != 'maintenance' AND occupied >= capacity THEN 1 ELSE 0 END) as full_rooms
    FROM rooms");
$room_stats = $stmt->fetch();

// Calculate total occupied rooms (partially + full)
$room_stats['occupied_rooms'] = $room_stats['partially_occupied_rooms'] + $room_stats['full_rooms'];

// Bed space statistics - Use room occupancy data instead of bed_spaces table
$stmt = $pdo->query("SELECT 
    SUM(capacity) as total_beds,
    SUM(occupied) as occupied_beds,
    SUM(capacity - occupied) as available_beds
    FROM rooms WHERE status != 'maintenance'");
$bed_stats = $stmt->fetch();

// Student statistics - More comprehensive
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_students,
    SUM(CASE WHEN application_status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
    SUM(CASE WHEN application_status = 'approved' THEN 1 ELSE 0 END) as approved_students,
    SUM(CASE WHEN application_status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications,
    SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male_students,
    SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_students
    FROM students");
$student_stats = $stmt->fetch();

// Pending items - More comprehensive
$stmt = $pdo->query("SELECT COUNT(*) as pending_offenses FROM offense_logs WHERE status = 'pending'");
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

// Weekly applicant data for graph - Fixed query with better date handling
$stmt = $pdo->query("SELECT 
    DATE(created_at) as date,
    COUNT(*) as count
    FROM students 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
    GROUP BY DATE(created_at)
    ORDER BY date");
$weekly_applicants_raw = $stmt->fetchAll();

// Fill in missing weeks with 0 values for better chart display
$weekly_applicants = [];
for ($i = 7; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i weeks"));
    $found = false;
    foreach ($weekly_applicants_raw as $item) {
        if ($item['date'] === $date) {
            $weekly_applicants[] = $item;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $weekly_applicants[] = ['date' => $date, 'count' => 0];
    }
}

// Monthly statistics for better charts
$stmt = $pdo->query("SELECT 
    MONTH(created_at) as month,
    COUNT(*) as count
    FROM students 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY MONTH(created_at)
    ORDER BY month");
$monthly_applicants = $stmt->fetchAll();

// Offense statistics by severity
$stmt = $pdo->query("SELECT 
    severity,
    COUNT(*) as count
    FROM offense_logs 
    WHERE reported_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY severity");
$offense_by_severity = $stmt->fetchAll();

// Maintenance requests by priority
$stmt = $pdo->query("SELECT 
    priority,
    COUNT(*) as count
    FROM maintenance_requests 
    WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY priority");
$maintenance_by_priority = $stmt->fetchAll();

// Recent activities - Enhanced
$stmt = $pdo->query("SELECT 
    'maintenance' as type, title as description, submitted_at as created_at, priority
    FROM maintenance_requests 
    WHERE status = 'pending' 
    ORDER BY submitted_at DESC 
    LIMIT 5");
$recent_maintenance = $stmt->fetchAll();

$stmt = $pdo->query("SELECT 
    'offense' as type, CONCAT(offense_type, ' - ', description) as description, reported_at as created_at, severity
    FROM offense_logs 
    WHERE status = 'pending' 
    ORDER BY reported_at DESC 
    LIMIT 5");
$recent_offenses = $stmt->fetchAll();

$stmt = $pdo->query("SELECT 
    'complaint' as type, subject as description, submitted_at as created_at, status
    FROM complaints 
    WHERE status IN ('pending', 'investigating')
    ORDER BY submitted_at DESC 
    LIMIT 5");
$recent_complaints = $stmt->fetchAll();

$recent_activities = array_merge($recent_maintenance, $recent_offenses, $recent_complaints);
usort($recent_activities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_activities = array_slice($recent_activities, 0, 10);

// Building occupancy statistics
$stmt = $pdo->query("SELECT 
    b.name as building_name,
    COUNT(r.id) as total_rooms,
    SUM(CASE WHEN r.status = 'full' THEN 1 ELSE 0 END) as occupied_rooms,
    SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_rooms
    FROM buildings b
    LEFT JOIN rooms r ON b.id = r.building_id
    GROUP BY b.id, b.name
    ORDER BY b.name");
$building_stats = $stmt->fetchAll();
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

<!-- Key Metrics Cards -->
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
        <a href="reservation_management.php" class="text-decoration-none">
            <div class="card stats-card-modern clickable-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-success"><?php echo $student_stats['approved_students']; ?></h3>
                            <p class="mb-0 text-muted">Active Students</p>
                            <small class="text-warning"><?php echo $student_stats['pending_applications']; ?> pending</small>
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
                            <h3 class="mb-1 text-secondary"><?php echo $bed_stats['occupied_beds']; ?></h3>
                            <p class="mb-0 text-muted">Occupied Beds</p>
                            <small class="text-muted">of <?php echo $bed_stats['total_beds']; ?> total</small>
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

<!-- Charts Section -->
<div class="row mb-4">
    <!-- Room Occupancy Pie Chart -->
    <div class="col-lg-3 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie text-primary"></i> Room Occupancy</h5>
            </div>
            <div class="card-body">
                <canvas id="roomOccupancyChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Student Gender Distribution -->
    <div class="col-lg-3 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users text-success"></i> Student Gender</h5>
            </div>
            <div class="card-body">
                <canvas id="studentGenderChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Offense Severity Distribution -->
    <div class="col-lg-3 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle text-warning"></i> Offense Severity</h5>
            </div>
            <div class="card-body">
                <canvas id="offenseSeverityChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Bed Availability Pie Chart -->
    <div class="col-lg-3 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bed text-info"></i> Bed Availability</h5>
            </div>
            <div class="card-body">
                <canvas id="bedAvailabilityChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Additional Charts Row -->
<div class="row mb-4">
    <!-- Bed Space Capacity -->
    <div class="col-lg-6 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bed text-info"></i> Bed Space Capacity</h5>
            </div>
            <div class="card-body">
                <canvas id="bedCapacityChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Maintenance Priority -->
    <div class="col-lg-6 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-tools text-danger"></i> Maintenance Priority</h5>
            </div>
            <div class="card-body">
                <canvas id="maintenancePriorityChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Building Occupancy Chart -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card chart-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-building text-primary"></i> Building Occupancy Overview</h5>
            </div>
            <div class="card-body">
                <canvas id="buildingOccupancyChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Weekly Applicants Graph -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card chart-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line text-success"></i> Weekly Applicant Trends</h5>
            </div>
            <div class="card-body">
                <canvas id="weeklyApplicantsChart" height="200"></canvas>
            </div>
        </div>
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
    
    <!-- Recent Activities -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Activities</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_activities)): ?>
                    <p class="text-muted">No recent activities to display.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-<?php echo $activity['type'] == 'maintenance' ? 'tools' : 'exclamation-triangle'; ?> text-<?php echo $activity['type'] == 'maintenance' ? 'primary' : 'warning'; ?>"></i>
                                    <span class="ms-2"><?php echo htmlspecialchars($activity['description']); ?></span>
                                </div>
                                <small class="text-muted"><?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- System Status -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-server"></i> System Status</h5>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart.js configuration
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.color = '#6c757d';

// Room Occupancy Pie Chart - Based on room management system
const roomCtx = document.getElementById('roomOccupancyChart').getContext('2d');
new Chart(roomCtx, {
    type: 'doughnut',
    data: {
        labels: ['Available', 'Occupied', 'Maintenance'],
        datasets: [{
            data: [
                <?php echo $room_stats['available_rooms']; ?>,
                <?php echo $room_stats['occupied_rooms']; ?>,
                <?php echo $room_stats['maintenance_rooms']; ?>
            ],
            backgroundColor: [
                '#28a745',
                '#007bff',
                '#ffc107'
            ],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 10
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
                        return context.label + ': ' + context.parsed + ' rooms';
                    }
                }
            }
        }
    }
});

// Student Gender Distribution Chart
const genderCtx = document.getElementById('studentGenderChart').getContext('2d');
new Chart(genderCtx, {
    type: 'doughnut',
    data: {
        labels: ['Male', 'Female'],
        datasets: [{
            data: [
                <?php echo $student_stats['male_students']; ?>,
                <?php echo $student_stats['female_students']; ?>
            ],
            backgroundColor: [
                '#17a2b8',
                '#e83e8c'
            ],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 10
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
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Offense Severity Chart
const offenseCtx = document.getElementById('offenseSeverityChart').getContext('2d');
const offenseData = {
    minor: <?php echo isset(array_column($offense_by_severity, 'count', 'severity')['minor']) ? array_column($offense_by_severity, 'count', 'severity')['minor'] : 0; ?>,
    major: <?php echo isset(array_column($offense_by_severity, 'count', 'severity')['major']) ? array_column($offense_by_severity, 'count', 'severity')['major'] : 0; ?>,
    critical: <?php echo isset(array_column($offense_by_severity, 'count', 'severity')['critical']) ? array_column($offense_by_severity, 'count', 'severity')['critical'] : 0; ?>
};

new Chart(offenseCtx, {
    type: 'doughnut',
    data: {
        labels: ['Minor', 'Major', 'Critical'],
        datasets: [{
            data: [offenseData.minor, offenseData.major, offenseData.critical],
            backgroundColor: [
                '#28a745',
                '#ffc107',
                '#dc3545'
            ],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 10
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
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Bed Capacity Chart - Enhanced
const bedCtx = document.getElementById('bedCapacityChart').getContext('2d');
new Chart(bedCtx, {
    type: 'bar',
    data: {
        labels: ['Available Beds', 'Occupied Beds'],
        datasets: [{
            label: 'Bed Spaces',
            data: [
                <?php echo $bed_stats['available_beds']; ?>,
                <?php echo $bed_stats['occupied_beds']; ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(0, 123, 255, 0.8)'
            ],
            borderColor: [
                '#28a745',
                '#007bff'
            ],
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + ' beds';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Maintenance Priority Chart
const maintenanceCtx = document.getElementById('maintenancePriorityChart').getContext('2d');
const maintenanceData = {
    low: <?php echo isset(array_column($maintenance_by_priority, 'count', 'priority')['low']) ? array_column($maintenance_by_priority, 'count', 'priority')['low'] : 0; ?>,
    medium: <?php echo isset(array_column($maintenance_by_priority, 'count', 'priority')['medium']) ? array_column($maintenance_by_priority, 'count', 'priority')['medium'] : 0; ?>,
    high: <?php echo isset(array_column($maintenance_by_priority, 'count', 'priority')['high']) ? array_column($maintenance_by_priority, 'count', 'priority')['high'] : 0; ?>,
    urgent: <?php echo isset(array_column($maintenance_by_priority, 'count', 'priority')['urgent']) ? array_column($maintenance_by_priority, 'count', 'priority')['urgent'] : 0; ?>
};

new Chart(maintenanceCtx, {
    type: 'bar',
    data: {
        labels: ['Low', 'Medium', 'High', 'Urgent'],
        datasets: [{
            label: 'Maintenance Requests',
            data: [maintenanceData.low, maintenanceData.medium, maintenanceData.high, maintenanceData.urgent],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(255, 87, 34, 0.8)',
                'rgba(220, 53, 69, 0.8)'
            ],
            borderColor: [
                '#28a745',
                '#ffc107',
                '#ff5722',
                '#dc3545'
            ],
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Building Occupancy Chart
const buildingCtx = document.getElementById('buildingOccupancyChart').getContext('2d');
const buildingData = <?php echo json_encode($building_stats); ?>;

new Chart(buildingCtx, {
    type: 'bar',
    data: {
        labels: buildingData.map(b => b.building_name),
        datasets: [{
            label: 'Available Rooms',
            data: buildingData.map(b => b.available_rooms),
            backgroundColor: 'rgba(40, 167, 69, 0.8)',
            borderColor: '#28a745',
            borderWidth: 2
        }, {
            label: 'Occupied Rooms',
            data: buildingData.map(b => b.occupied_rooms),
            backgroundColor: 'rgba(0, 123, 255, 0.8)',
            borderColor: '#007bff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            x: {
                stacked: true
            },
            y: {
                stacked: true,
                beginAtZero: true
            }
        }
    }
});

// Weekly Applicants Line Chart - Enhanced
const weeklyCtx = document.getElementById('weeklyApplicantsChart').getContext('2d');
const weeklyData = <?php echo json_encode($weekly_applicants); ?>;

// Process the data from PHP (already filled with missing weeks)
const weeklyLabels = weeklyData.map(item => {
    const d = new Date(item.date);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});

const weeklyCounts = weeklyData.map(item => parseInt(item.count));

new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: weeklyLabels,
        datasets: [{
            label: 'New Applicants',
            data: weeklyCounts,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#28a745',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    title: function(context) {
                        const date = new Date(weeklyData[context[0].dataIndex].date);
                        return date.toLocaleDateString('en-US', { 
                            weekday: 'short', 
                            month: 'short', 
                            day: 'numeric' 
                        });
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Bed Availability Pie Chart
const bedAvailabilityCtx = document.getElementById('bedAvailabilityChart').getContext('2d');
const bedAvailabilityData = {
    available: <?php echo $bed_stats['available_beds']; ?>,
    occupied: <?php echo $bed_stats['occupied_beds']; ?>
};

new Chart(bedAvailabilityCtx, {
    type: 'doughnut',
    data: {
        labels: ['Available Beds', 'Occupied Beds'],
        datasets: [{
            data: [bedAvailabilityData.available, bedAvailabilityData.occupied],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(220, 53, 69, 0.8)'
            ],
            borderColor: [
                '#28a745',
                '#dc3545'
            ],
            borderWidth: 2,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    usePointStyle: true,
                    font: {
                        size: 12,
                        weight: 'bold'
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = bedAvailabilityData.available + bedAvailabilityData.occupied;
                        const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        },
        cutout: '50%',
        animation: {
            animateRotate: true,
            animateScale: true,
            duration: 1500
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>