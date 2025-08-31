<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

// Handle AJAX requests for generating reports
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDBConnection();
        
        if (isset($_POST['action']) && $_POST['action'] === 'generate_report') {
            $report_type = sanitizeInput($_POST['report_type']);
            $date_from = sanitizeInput($_POST['date_from']);
            $date_to = sanitizeInput($_POST['date_to']);
            $building_id = sanitizeInput($_POST['building_id'] ?? '');
            
            // Generate report based on type
            $report_data = [];
            
            switch ($report_type) {
                case 'occupancy':
                    $where_building = $building_id ? "AND b.id = ?" : "";
                    $params = $building_id ? [$building_id] : [];
                    
                    $stmt = $pdo->prepare("
                        SELECT 
                            b.building_name,
                            COUNT(r.id) as total_rooms,
                            COUNT(bs.id) as total_bedspaces,
                            SUM(CASE WHEN bs.status = 'occupied' THEN 1 ELSE 0 END) as occupied_bedspaces,
                            SUM(CASE WHEN bs.status = 'available' THEN 1 ELSE 0 END) as available_bedspaces,
                            ROUND((SUM(CASE WHEN bs.status = 'occupied' THEN 1 ELSE 0 END) / COUNT(bs.id)) * 100, 2) as occupancy_rate
                        FROM buildings b
                        LEFT JOIN rooms r ON b.id = r.building_id
                        LEFT JOIN bedspaces bs ON r.id = bs.room_id
                        WHERE 1=1 $where_building
                        GROUP BY b.id, b.building_name
                        ORDER BY b.building_name
                    ");
                    $stmt->execute($params);
                    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'reservations':
                    $where_conditions = ["r.created_at BETWEEN ? AND ?"];
                    $params = [$date_from, $date_to];
                    
                    if ($building_id) {
                        $where_conditions[] = "b.id = ?";
                        $params[] = $building_id;
                    }
                    
                    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
                    
                    $stmt = $pdo->prepare("
                        SELECT 
                            DATE(r.created_at) as date,
                            COUNT(*) as total_reservations,
                            SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved,
                            SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                            SUM(CASE WHEN r.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                        FROM reservations r
                        LEFT JOIN bedspaces bs ON r.bedspace_id = bs.id
                        LEFT JOIN rooms rm ON bs.room_id = rm.id
                        LEFT JOIN buildings b ON rm.building_id = b.id
                        $where_clause
                        GROUP BY DATE(r.created_at)
                        ORDER BY DATE(r.created_at) DESC
                    ");
                    $stmt->execute($params);
                    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'visitors':
                    $where_conditions = ["v.time_in BETWEEN ? AND ?"];
                    $params = [$date_from, $date_to];
                    
                    if ($building_id) {
                        $where_conditions[] = "b.id = ?";
                        $params[] = $building_id;
                    }
                    
                    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
                    
                    $stmt = $pdo->prepare("
                        SELECT 
                            DATE(v.time_in) as date,
                            COUNT(*) as total_visitors,
                            SUM(CASE WHEN v.time_out IS NOT NULL THEN 1 ELSE 0 END) as checked_out,
                            SUM(CASE WHEN v.time_out IS NULL THEN 1 ELSE 0 END) as still_inside,
                            b.building_name
                        FROM visitors v
                        LEFT JOIN students s ON v.student_id = s.id
                        LEFT JOIN bedspaces bs ON s.id = bs.student_id
                        LEFT JOIN rooms r ON bs.room_id = r.id
                        LEFT JOIN buildings b ON r.building_id = b.id
                        $where_clause
                        GROUP BY DATE(v.time_in), b.building_name
                        ORDER BY DATE(v.time_in) DESC, b.building_name
                    ");
                    $stmt->execute($params);
                    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'maintenance':
                    $where_conditions = ["mr.created_at BETWEEN ? AND ?"];
                    $params = [$date_from, $date_to];
                    
                    if ($building_id) {
                        $where_conditions[] = "b.id = ?";
                        $params[] = $building_id;
                    }
                    
                    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
                    
                    $stmt = $pdo->prepare("
                        SELECT 
                            mr.priority,
                            mr.status,
                            COUNT(*) as count,
                            AVG(CASE WHEN mr.status = 'resolved' THEN DATEDIFF(mr.updated_at, mr.created_at) END) as avg_resolution_days
                        FROM maintenance_requests mr
                        LEFT JOIN rooms r ON mr.room_id = r.id
                        LEFT JOIN buildings b ON r.building_id = b.id
                        $where_clause
                        GROUP BY mr.priority, mr.status
                        ORDER BY mr.priority, mr.status
                    ");
                    $stmt->execute($params);
                    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
            }
            
            echo json_encode(['success' => true, 'data' => $report_data]);
        }
    } catch (Exception $e) {
        error_log("Error in reports.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get summary statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM students WHERE status = 'approved') as total_students,
            (SELECT COUNT(*) FROM buildings) as total_buildings,
            (SELECT COUNT(*) FROM rooms) as total_rooms,
            (SELECT COUNT(*) FROM bedspaces WHERE status = 'occupied') as occupied_bedspaces,
            (SELECT COUNT(*) FROM bedspaces) as total_bedspaces,
            (SELECT COUNT(*) FROM reservations WHERE status = 'pending') as pending_reservations,
            (SELECT COUNT(*) FROM maintenance_requests WHERE status != 'resolved') as pending_maintenance,
            (SELECT COUNT(*) FROM visitors WHERE time_out IS NULL) as current_visitors
    ");
    $stats_stmt->execute();
    $summary_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get buildings for filter
    $buildings_stmt = $pdo->prepare("SELECT * FROM buildings ORDER BY building_name");
    $buildings_stmt->execute();
    $buildings = $buildings_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activity
    $activity_stmt = $pdo->prepare("
        SELECT al.*, u.username 
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $activity_stmt->execute();
    $recent_activities = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error in reports.php: " . $e->getMessage());
    $summary_stats = [];
    $buildings = [];
    $recent_activities = [];
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Reports & Analytics</h1>
                <p class="text-muted">Generate comprehensive reports and analytics</p>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $summary_stats['total_students'] ?? 0; ?></h4>
                                <p class="mb-0">Total Students</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $summary_stats['occupied_bedspaces'] ?? 0; ?>/<?php echo $summary_stats['total_bedspaces'] ?? 0; ?></h4>
                                <p class="mb-0">Occupancy</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-bed fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $summary_stats['pending_reservations'] ?? 0; ?></h4>
                                <p class="mb-0">Pending Reservations</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $summary_stats['current_visitors'] ?? 0; ?></h4>
                                <p class="mb-0">Current Visitors</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-user-friends fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Generation -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Generate Reports</h5>
                    </div>
                    <div class="card-body">
                        <form id="reportForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="reportType" class="form-label">Report Type *</label>
                                        <select class="form-select" id="reportType" name="report_type" required>
                                            <option value="">Select report type...</option>
                                            <option value="occupancy">Occupancy Report</option>
                                            <option value="reservations">Reservations Report</option>
                                            <option value="visitors">Visitors Report</option>
                                            <option value="maintenance">Maintenance Report</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="buildingFilter" class="form-label">Building (Optional)</label>
                                        <select class="form-select" id="buildingFilter" name="building_id">
                                            <option value="">All Buildings</option>
                                            <?php foreach ($buildings as $building): ?>
                                                <option value="<?php echo $building['id']; ?>">
                                                    <?php echo htmlspecialchars($building['building_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="dateFrom" class="form-label">Date From</label>
                                        <input type="date" class="form-control" id="dateFrom" name="date_from" 
                                               value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="dateTo" class="form-label">Date To</label>
                                        <input type="date" class="form-control" id="dateTo" name="date_to" 
                                               value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-chart-line"></i> Generate Report
                                </button>
                                <button type="button" class="btn btn-success" onclick="exportCurrentReport()">
                                    <i class="fas fa-download"></i> Export to CSV
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Report Results -->
                <div class="card mt-4" id="reportResults" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0" id="reportTitle">Report Results</h5>
                    </div>
                    <div class="card-body">
                        <div id="reportContent">
                            <!-- Report content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Quick Export Options -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-download"></i> Quick Exports</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="export_students.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-graduate"></i> All Students
                            </a>
                            <a href="export_reservations.php" class="btn btn-outline-primary">
                                <i class="fas fa-calendar-check"></i> All Reservations
                            </a>
                            <a href="export_visitors.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-friends"></i> All Visitors
                            </a>
                            <a href="export_maintenance.php" class="btn btn-outline-primary">
                                <i class="fas fa-tools"></i> All Maintenance
                            </a>
                            <a href="export_complaints.php" class="btn btn-outline-primary">
                                <i class="fas fa-clipboard-list"></i> All Complaints
                            </a>
                            <a href="export_room_transfers.php" class="btn btn-outline-primary">
                                <i class="fas fa-exchange-alt"></i> Room Transfers
                            </a>
                            <a href="export_student_locations.php" class="btn btn-outline-primary">
                                <i class="fas fa-map-marker-alt"></i> Student Locations
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activities)): ?>
                            <p class="text-muted">No recent activity</p>
                        <?php else: ?>
                            <div class="timeline-sm">
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="timeline-item-sm">
                                        <div class="timeline-content-sm">
                                            <div class="fw-bold"><?php echo htmlspecialchars($activity['username']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($activity['action']); ?></small>
                                            <br>
                                            <small class="text-muted"><?php echo formatDate($activity['created_at']); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentReportData = null;

// Handle report form submission
$('#reportForm').on('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Generating...').prop('disabled', true);
    
    $.post('reports.php', $(this).serialize() + '&action=generate_report', function(response) {
        if (response.success) {
            currentReportData = response.data;
            displayReport($('#reportType').val(), response.data);
            $('#reportResults').show();
        } else {
            showAlert(response.message, 'danger');
        }
    }, 'json').fail(function() {
        showAlert('Error generating report', 'danger');
    }).always(function() {
        submitBtn.html(originalText).prop('disabled', false);
    });
});

// Display report based on type
function displayReport(reportType, data) {
    let title = '';
    let content = '';
    
    switch (reportType) {
        case 'occupancy':
            title = 'Occupancy Report';
            content = generateOccupancyReport(data);
            break;
        case 'reservations':
            title = 'Reservations Report';
            content = generateReservationsReport(data);
            break;
        case 'visitors':
            title = 'Visitors Report';
            content = generateVisitorsReport(data);
            break;
        case 'maintenance':
            title = 'Maintenance Report';
            content = generateMaintenanceReport(data);
            break;
    }
    
    $('#reportTitle').text(title);
    $('#reportContent').html(content);
}

// Generate occupancy report HTML
function generateOccupancyReport(data) {
    if (data.length === 0) {
        return '<div class="alert alert-info">No occupancy data found.</div>';
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped">';
    html += '<thead><tr><th>Building</th><th>Total Rooms</th><th>Total Bedspaces</th><th>Occupied</th><th>Available</th><th>Occupancy Rate</th></tr></thead>';
    html += '<tbody>';
    
    data.forEach(function(row) {
        html += '<tr>';
        html += '<td>' + row.building_name + '</td>';
        html += '<td>' + row.total_rooms + '</td>';
        html += '<td>' + row.total_bedspaces + '</td>';
        html += '<td>' + row.occupied_bedspaces + '</td>';
        html += '<td>' + row.available_bedspaces + '</td>';
        html += '<td><span class="badge bg-primary">' + row.occupancy_rate + '%</span></td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    return html;
}

// Generate reservations report HTML
function generateReservationsReport(data) {
    if (data.length === 0) {
        return '<div class="alert alert-info">No reservation data found for the selected period.</div>';
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped">';
    html += '<thead><tr><th>Date</th><th>Total</th><th>Pending</th><th>Approved</th><th>Rejected</th><th>Cancelled</th></tr></thead>';
    html += '<tbody>';
    
    data.forEach(function(row) {
        html += '<tr>';
        html += '<td>' + row.date + '</td>';
        html += '<td>' + row.total_reservations + '</td>';
        html += '<td><span class="badge bg-warning">' + row.pending + '</span></td>';
        html += '<td><span class="badge bg-success">' + row.approved + '</span></td>';
        html += '<td><span class="badge bg-danger">' + row.rejected + '</span></td>';
        html += '<td><span class="badge bg-secondary">' + row.cancelled + '</span></td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    return html;
}

// Generate visitors report HTML
function generateVisitorsReport(data) {
    if (data.length === 0) {
        return '<div class="alert alert-info">No visitor data found for the selected period.</div>';
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped">';
    html += '<thead><tr><th>Date</th><th>Building</th><th>Total Visitors</th><th>Checked Out</th><th>Still Inside</th></tr></thead>';
    html += '<tbody>';
    
    data.forEach(function(row) {
        html += '<tr>';
        html += '<td>' + row.date + '</td>';
        html += '<td>' + (row.building_name || 'N/A') + '</td>';
        html += '<td>' + row.total_visitors + '</td>';
        html += '<td><span class="badge bg-success">' + row.checked_out + '</span></td>';
        html += '<td><span class="badge bg-warning">' + row.still_inside + '</span></td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    return html;
}

// Generate maintenance report HTML
function generateMaintenanceReport(data) {
    if (data.length === 0) {
        return '<div class="alert alert-info">No maintenance data found for the selected period.</div>';
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped">';
    html += '<thead><tr><th>Priority</th><th>Status</th><th>Count</th><th>Avg Resolution (Days)</th></tr></thead>';
    html += '<tbody>';
    
    data.forEach(function(row) {
        let priorityClass = row.priority === 'high' ? 'bg-danger' : (row.priority === 'medium' ? 'bg-warning' : 'bg-info');
        let statusClass = row.status === 'resolved' ? 'bg-success' : (row.status === 'pending' ? 'bg-warning' : 'bg-info');
        
        html += '<tr>';
        html += '<td><span class="badge ' + priorityClass + '">' + row.priority.charAt(0).toUpperCase() + row.priority.slice(1) + '</span></td>';
        html += '<td><span class="badge ' + statusClass + '">' + row.status.charAt(0).toUpperCase() + row.status.slice(1) + '</span></td>';
        html += '<td>' + row.count + '</td>';
        html += '<td>' + (row.avg_resolution_days ? Math.round(row.avg_resolution_days * 10) / 10 : 'N/A') + '</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    return html;
}

// Export current report
function exportCurrentReport() {
    if (!currentReportData) {
        showAlert('Please generate a report first', 'warning');
        return;
    }
    
    const reportType = $('#reportType').val();
    const dateFrom = $('#dateFrom').val();
    const dateTo = $('#dateTo').val();
    const buildingId = $('#buildingFilter').val();
    
    const params = new URLSearchParams({
        type: reportType,
        date_from: dateFrom,
        date_to: dateTo,
        building_id: buildingId
    });
    
    window.location.href = 'export_report.php?' + params.toString();
}

// Alert function
function showAlert(message, type) {
    const alertDiv = $(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('.main-content .container-fluid').prepend(alertDiv);
    
    setTimeout(() => {
        alertDiv.alert('close');
    }, 5000);
}
</script>

<style>
.timeline-sm {
    position: relative;
}

.timeline-item-sm {
    position: relative;
    margin-bottom: 15px;
    padding-left: 15px;
    border-left: 2px solid #e9ecef;
}

.timeline-content-sm {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}
</style>

<?php include 'includes/footer.php'; ?>
