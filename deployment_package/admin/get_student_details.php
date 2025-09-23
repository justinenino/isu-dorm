<?php
require_once '../config/database.php';
requireAdmin();

if (!isset($_GET['id'])) {
    exit('Invalid request');
}

$student_id = $_GET['id'];
$pdo = getConnection();

$stmt = $pdo->prepare("SELECT s.*, 
    CONCAT(s.first_name, ' ', IFNULL(s.middle_name, ''), ' ', s.last_name) as full_name,
    r.room_number, b.name as building_name
    FROM students s
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    exit('Student not found');
}
?>

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-user"></i> Personal Information</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td><strong>Full Name:</strong></td>
                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
            </tr>
            <tr>
                <td><strong>Date of Birth:</strong></td>
                <td><?php echo date('M j, Y', strtotime($student['date_of_birth'])); ?></td>
            </tr>
            <tr>
                <td><strong>Gender:</strong></td>
                <td><?php echo htmlspecialchars($student['gender']); ?></td>
            </tr>
            <tr>
                <td><strong>School ID:</strong></td>
                <td><?php echo htmlspecialchars($student['school_id']); ?></td>
            </tr>
            <tr>
                <td><strong>LRN:</strong></td>
                <td><?php echo htmlspecialchars($student['learner_reference_number']); ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6><i class="fas fa-map-marker-alt"></i> Address</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td><strong>Province:</strong></td>
                <td><?php echo htmlspecialchars($student['province']); ?></td>
            </tr>
            <tr>
                <td><strong>Municipality:</strong></td>
                <td><?php echo htmlspecialchars($student['municipality']); ?></td>
            </tr>
            <tr>
                <td><strong>Barangay:</strong></td>
                <td><?php echo htmlspecialchars($student['barangay']); ?></td>
            </tr>
            <tr>
                <td><strong>Street/Purok:</strong></td>
                <td><?php echo htmlspecialchars($student['street_purok']); ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-phone"></i> Contact Information</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td><strong>Mobile:</strong></td>
                <td><?php echo htmlspecialchars($student['mobile_number']); ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td><?php echo htmlspecialchars($student['email']); ?></td>
            </tr>
            <tr>
                <td><strong>Facebook:</strong></td>
                <td>
                    <?php if ($student['facebook_link']): ?>
                        <a href="<?php echo htmlspecialchars($student['facebook_link']); ?>" target="_blank">View Profile</a>
                    <?php else: ?>
                        Not provided
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6><i class="fas fa-user-friends"></i> Emergency Contact</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td><strong>Guardian Name:</strong></td>
                <td><?php echo htmlspecialchars($student['guardian_name']); ?></td>
            </tr>
            <tr>
                <td><strong>Relationship:</strong></td>
                <td><?php echo htmlspecialchars($student['guardian_relationship']); ?></td>
            </tr>
            <tr>
                <td><strong>Mobile:</strong></td>
                <td><?php echo htmlspecialchars($student['guardian_mobile']); ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <h6><i class="fas fa-info-circle"></i> Application Status</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <?php if ($student['application_status'] == 'pending'): ?>
                        <span class="badge bg-warning">Pending</span>
                    <?php elseif ($student['application_status'] == 'approved'): ?>
                        <span class="badge bg-success">Approved</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Rejected</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Applied Date:</strong></td>
                <td><?php echo date('M j, Y g:i A', strtotime($student['created_at'])); ?></td>
            </tr>
            <?php if ($student['room_number']): ?>
            <tr>
                <td><strong>Room Assignment:</strong></td>
                <td><?php echo htmlspecialchars($student['building_name'] . ' - Room ' . $student['room_number']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php if ($student['attachment_file']): ?>
<div class="row">
    <div class="col-12">
        <h6><i class="fas fa-paperclip"></i> Attachment</h6>
        <?php 
        $attachment_path = '../' . $student['attachment_file'];
        $full_path = dirname(__DIR__) . '/' . $student['attachment_file'];
        if (file_exists($full_path)): ?>
            <a href="<?php echo htmlspecialchars($attachment_path); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-file-pdf"></i> View Attachment
            </a>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>File Not Found:</strong> The attachment file "<?php echo htmlspecialchars(basename($student['attachment_file'])); ?>" could not be located.
                <br><small class="text-muted">Expected path: <?php echo htmlspecialchars($student['attachment_file']); ?></small>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>