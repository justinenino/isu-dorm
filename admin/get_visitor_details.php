<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

$visitor_id = isset($_GET['visitor_id']) ? sanitizeInput($_GET['visitor_id']) : '';

if (!$visitor_id) {
    echo '<div class="alert alert-danger">Invalid visitor ID.</div>';
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT v.*, s.first_name, s.last_name, s.student_id as host_student_id,
               s.mobile_number as host_mobile, s.email as host_email,
               r.room_number, r.floor, b.building_name, b.address as building_address
        FROM visitors v
        JOIN students s ON v.student_id = s.user_id
        JOIN rooms r ON v.room_id = r.room_id
        JOIN buildings b ON r.building_id = b.building_id
        WHERE v.visitor_id = ?
    ");
    
    $stmt->execute([$visitor_id]);
    $visitor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$visitor) {
        echo '<div class="alert alert-danger">Visitor not found.</div>';
        exit;
    }
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error fetching visitor details: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-primary mb-3">
            <i class="fas fa-user me-2"></i>Visitor Information
        </h6>
        
        <table class="table table-borderless">
            <tr>
                <td><strong>Name:</strong></td>
                <td><?php echo htmlspecialchars($visitor['visitor_name']); ?></td>
            </tr>
            <tr>
                <td><strong>Age:</strong></td>
                <td><?php echo $visitor['age']; ?> years old</td>
            </tr>
            <tr>
                <td><strong>Address:</strong></td>
                <td><?php echo htmlspecialchars($visitor['address']); ?></td>
            </tr>
            <tr>
                <td><strong>Contact Number:</strong></td>
                <td><?php echo htmlspecialchars($visitor['contact_number']); ?></td>
            </tr>
            <tr>
                <td><strong>Purpose:</strong></td>
                <td><?php echo htmlspecialchars($visitor['purpose'] ?? 'Not specified'); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary mb-3">
            <i class="fas fa-home me-2"></i>Location Information
        </h6>
        
        <table class="table table-borderless">
            <tr>
                <td><strong>Building:</strong></td>
                <td><?php echo htmlspecialchars($visitor['building_name']); ?></td>
            </tr>
            <tr>
                <td><strong>Room Number:</strong></td>
                <td><?php echo htmlspecialchars($visitor['room_number']); ?></td>
            </tr>
            <tr>
                <td><strong>Floor:</strong></td>
                <td><?php echo $visitor['floor']; ?></td>
            </tr>
            <tr>
                <td><strong>Building Address:</strong></td>
                <td><?php echo htmlspecialchars($visitor['building_address']); ?></td>
            </tr>
        </table>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-primary mb-3">
            <i class="fas fa-user-graduate me-2"></i>Host Student Information
        </h6>
        
        <table class="table table-borderless">
            <tr>
                <td><strong>Name:</strong></td>
                <td><?php echo htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']); ?></td>
            </tr>
            <tr>
                <td><strong>Student ID:</strong></td>
                <td><?php echo htmlspecialchars($visitor['host_student_id']); ?></td>
            </tr>
            <tr>
                <td><strong>Mobile:</strong></td>
                <td><?php echo htmlspecialchars($visitor['host_mobile']); ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td><?php echo htmlspecialchars($visitor['host_email']); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary mb-3">
            <i class="fas fa-clock me-2"></i>Visit Timing
        </h6>
        
        <table class="table table-borderless">
            <tr>
                <td><strong>Time In:</strong></td>
                <td>
                    <span class="badge bg-success">
                        <?php echo formatDate($visitor['time_in'], 'M d, Y h:i A'); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Time Out:</strong></td>
                <td>
                    <?php if ($visitor['time_out']): ?>
                        <span class="badge bg-info">
                            <?php echo formatDate($visitor['time_out'], 'M d, Y h:i A'); ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning">Currently Inside</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Duration:</strong></td>
                <td>
                    <?php 
                    if ($visitor['time_out']) {
                        $duration = strtotime($visitor['time_out']) - strtotime($visitor['time_in']);
                        $hours = floor($duration / 3600);
                        $minutes = floor(($duration % 3600) / 60);
                        echo $hours . 'h ' . $minutes . 'm';
                    } else {
                        $duration = time() - strtotime($visitor['time_in']);
                        $hours = floor($duration / 3600);
                        $minutes = floor(($duration % 3600) / 60);
                        echo '<span class="text-warning">' . $hours . 'h ' . $minutes . 'm (ongoing)</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td><strong>Visit Date:</strong></td>
                <td><?php echo formatDate($visitor['time_in'], 'l, F d, Y'); ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if ($visitor['notes']): ?>
    <hr>
    <div class="row">
        <div class="col-12">
            <h6 class="text-primary mb-3">
                <i class="fas fa-sticky-note me-2"></i>Additional Notes
            </h6>
            <div class="alert alert-info">
                <?php echo nl2br(htmlspecialchars($visitor['notes'])); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-between">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Record created: <?php echo formatDate($visitor['created_at'], 'M d, Y h:i A'); ?>
            </small>
            
            <?php if ($visitor['updated_at'] && $visitor['updated_at'] !== $visitor['created_at']): ?>
                <small class="text-muted">
                    <i class="fas fa-edit me-1"></i>
                    Last updated: <?php echo formatDate($visitor['updated_at'], 'M d, Y h:i A'); ?>
                </small>
            <?php endif; ?>
        </div>
    </div>
</div>
