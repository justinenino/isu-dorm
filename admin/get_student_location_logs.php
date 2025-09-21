<?php
require_once '../config/database.php';
requireAdmin();

if (!isset($_GET['student_id'])) {
    echo '<div class="alert alert-danger">Student ID is required.</div>';
    exit;
}

$student_id = $_GET['student_id'];
$pdo = getConnection();

// Get student information (only if student is active)
$stmt = $pdo->prepare("SELECT 
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.school_id,
    r.room_number,
    b.name as building_name,
    s.is_deleted,
    s.is_active
    FROM students s
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE s.id = ? AND s.is_deleted = 0 AND s.is_active = 1");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    echo '<div class="alert alert-danger">Student not found or is inactive/archived.</div>';
    exit;
}

// Get student's location logs
$stmt = $pdo->prepare("SELECT * FROM student_location_logs WHERE student_id = ? ORDER BY timestamp DESC LIMIT 50");
$stmt->execute([$student_id]);
$logs = $stmt->fetchAll();
?>

<div class="p-4 bg-light border-bottom">
    <div class="row">
        <div class="col-md-6">
            <div class="d-flex align-items-center mb-3">
                <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                    <i class="fas fa-user fa-2x"></i>
                </div>
                <div>
                    <h5 class="mb-1 text-dark"><?php echo htmlspecialchars($student['student_name']); ?></h5>
                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($student['school_id']); ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <h6 class="text-primary mb-3">
                <i class="fas fa-home me-2"></i>Room Assignment
            </h6>
            <?php if ($student['room_number']): ?>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-building text-success me-2"></i>
                    <span class="text-dark"><?php echo htmlspecialchars($student['building_name']); ?></span>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-door-open text-success me-2"></i>
                    <span class="text-dark">Room <?php echo htmlspecialchars($student['room_number']); ?></span>
                </div>
            <?php else: ?>
                <div class="d-flex align-items-center">
                    <i class="fas fa-times-circle text-muted me-2"></i>
                    <span class="text-muted">No room assigned</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (empty($logs)): ?>
    <div class="p-4 text-center">
        <div class="alert alert-info border-0 shadow-sm">
            <i class="fas fa-info-circle fa-2x mb-3 text-info"></i>
            <h6 class="mb-0">No location logs found for this student.</h6>
        </div>
    </div>
<?php else: ?>
    <div class="p-4">
        <h6 class="text-primary mb-3">
            <i class="fas fa-history me-2"></i>Location History (Last 50 entries)
        </h6>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="border-0">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>Location
                        </th>
                        <th class="border-0">
                            <i class="fas fa-clock me-2 text-primary"></i>Timestamp
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr class="align-middle">
                            <td>
                                <?php
                                $status_class = '';
                                $status_icon = '';
                                switch ($log['location_status']) {
                                    case 'inside_dormitory':
                                        $status_class = 'badge bg-success';
                                        $status_icon = 'fas fa-home';
                                        break;
                                    case 'in_class':
                                        $status_class = 'badge bg-info';
                                        $status_icon = 'fas fa-graduation-cap';
                                        break;
                                    case 'outside_campus':
                                        $status_class = 'badge bg-danger';
                                        $status_icon = 'fas fa-external-link-alt';
                                        break;
                                }
                                ?>
                                <span class="<?php echo $status_class; ?> px-3 py-2 rounded-pill">
                                    <i class="<?php echo $status_icon; ?> me-1"></i>
                                    <?php echo ucwords(str_replace('_', ' ', $log['location_status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-muted small">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('M j, Y g:i A', strtotime($log['timestamp'])); ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
