<?php
// Include database configuration first
require_once '../config/database.php';

$pdo = getConnection();

/**
 * Generate complaint subject based on room/building, complaint type, and target
 */
function generateComplaintSubject($student, $complaint_type, $target_type = '', $target_value = '', $target_building = '') {
    $location = '';
    
    if ($student['room_number'] && $student['building_name']) {
        $location = $student['building_name'] . ' - Room ' . $student['room_number'];
    } elseif ($student['building_name']) {
        $location = $student['building_name'];
    } else {
        $location = 'Dormitory';
    }
    
    $type_labels = [
        'noise' => 'Noise Complaint',
        'maintenance' => 'Maintenance Issue',
        'cleanliness' => 'Cleanliness Issue',
        'security' => 'Security Concern',
        'roommate' => 'Roommate Issue',
        'facility' => 'Facility Problem',
        'policy' => 'Policy Violation',
        'other' => 'Other Issue'
    ];
    
    $type_label = $type_labels[$complaint_type] ?? 'General Complaint';
    
    // Add target information if specified
    $target_info = '';
    if ($target_type && $target_value) {
        if ($target_type === 'room') {
            if (!empty($target_building)) {
                $target_info = ' (Regarding ' . $target_building . ' - Room ' . $target_value . ')';
            } else {
                $target_info = ' (Regarding Room ' . $target_value . ')';
            }
        } elseif ($target_type === 'person') {
            $target_info = ' (Regarding ' . $target_value . ')';
        }
    }
    
    return $location . ' - ' . $type_label . $target_info;
}

// Handle form submissions BEFORE including header to avoid "headers already sent" error
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'submit_complaint':
                $complaint_type = $_POST['complaint_type'];
                $description = $_POST['description'];
                $target_type = $_POST['target_type'] ?? '';
                $target_value = $_POST['target_value'] ?? '';
                $target_building = $_POST['target_building'] ?? '';
                
                // Get student's room information for auto-generating subjects
                $stmt = $pdo->prepare("SELECT s.*, r.id as room_id, r.room_number, b.name as building_name
                    FROM students s
                    LEFT JOIN rooms r ON s.room_id = r.id
                    LEFT JOIN buildings b ON r.building_id = b.id
                    WHERE s.id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $student = $stmt->fetch();
                
                // Auto-generate subject based on room/building, complaint type, and target
                $subject = generateComplaintSubject($student, $complaint_type, $target_type, $target_value, $target_building);
                
                $stmt = $pdo->prepare("INSERT INTO complaints (student_id, subject, description) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $subject, $description]);
                
                $_SESSION['success'] = "Complaint submitted successfully.";
                header("Location: complaints.php");
                exit;
                break;
        }
    }
}

// Include header and other files after form processing
$page_title = 'Submit Complaints';
include 'includes/header.php';

// Get student's room information for auto-generating subjects
$stmt = $pdo->prepare("SELECT s.*, r.id as room_id, r.room_number, b.name as building_name
    FROM students s
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE s.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Get list of buildings and rooms for target selection
$buildings = $pdo->query("SELECT id, name FROM buildings ORDER BY name")->fetchAll();
$building_rooms = [];
foreach ($buildings as $b) {
    $stmt = $pdo->prepare("SELECT id, room_number FROM rooms WHERE building_id = ? ORDER BY room_number");
    $stmt->execute([$b['id']]);
    $building_rooms[$b['id']] = $stmt->fetchAll();
}

// Get list of students in the same building for target selection
$students_in_building = [];
if ($student['building_name']) {
    $stmt = $pdo->prepare("SELECT s.first_name, s.last_name, s.id, s.school_id, r.room_number
        FROM students s
        JOIN rooms r ON s.room_id = r.id
        JOIN buildings b ON r.building_id = b.id
        WHERE b.name = ? AND s.id != ?
        ORDER BY s.first_name, s.last_name");
    $stmt->execute([$student['building_name'], $_SESSION['user_id']]);
    $students_in_building = $stmt->fetchAll();
}

// Get student's complaints
$stmt = $pdo->prepare("SELECT * FROM complaints WHERE student_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$complaints = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-comment-alt"></i> Submit Complaints</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#submitComplaintModal">
        <i class="fas fa-plus"></i> Submit New Complaint
    </button>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count(array_filter($complaints, function($c) { return $c['status'] == 'pending'; })); ?></h3>
            <p>Pending</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
            <h3><?php echo count(array_filter($complaints, function($c) { return $c['status'] == 'investigating'; })); ?></h3>
            <p>Investigating</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo count(array_filter($complaints, function($c) { return $c['status'] == 'resolved'; })); ?></h3>
            <p>Resolved</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
            <h3><?php echo count(array_filter($complaints, function($c) { return $c['status'] == 'closed'; })); ?></h3>
            <p>Closed</p>
        </div>
    </div>
</div>

<!-- Information Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> How to Submit a Complaint</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Before Submitting:</h6>
                <ul>
                    <li>Select the appropriate complaint type</li>
                    <li>Optionally specify who/what the complaint is about</li>
                    <li>Be specific about the issue you're reporting</li>
                    <li>Include relevant details and dates</li>
                    <li>Provide any supporting information</li>
                    <li>Use respectful and professional language</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>What Happens Next:</h6>
                <ul>
                    <li>Your complaint will be reviewed by admin</li>
                    <li>You'll receive updates on the status</li>
                    <li>Admin may contact you for more information</li>
                    <li>Resolution will be communicated to you</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Complaints Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Your Complaints</h5>
    </div>
    <div class="card-body">
        <?php if (empty($complaints)): ?>
            <div class="text-center py-4">
                <i class="fas fa-comment-alt fa-3x text-muted mb-3"></i>
                <h5>No complaints submitted yet</h5>
                <p class="text-muted">Submit your first complaint to get started.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped" id="complaintsTable">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Resolved</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $complaint): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($complaint['subject']); ?></strong></td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($complaint['description']); ?>">
                                        <?php echo htmlspecialchars($complaint['description']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch ($complaint['status']) {
                                        case 'pending': $status_class = 'badge bg-warning'; break;
                                        case 'investigating': $status_class = 'badge bg-info'; break;
                                        case 'resolved': $status_class = 'badge bg-success'; break;
                                        case 'closed': $status_class = 'badge bg-secondary'; break;
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>"><?php echo ucfirst($complaint['status']); ?></span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($complaint['created_at'])); ?></td>
                                <td>
                                    <?php if ($complaint['resolved_at']): ?>
                                        <?php echo date('M j, Y g:i A', strtotime($complaint['resolved_at'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not resolved</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewComplaintModal" 
                                            data-complaint='<?php echo json_encode($complaint); ?>'>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Submit Complaint Modal -->
<div class="modal fade" id="submitComplaintModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit New Complaint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="submit_complaint">
                <div class="modal-body">
                    <!-- Current Room Information -->
                    <?php if ($student['room_number'] && $student['building_name']): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Your Location:</strong> <?php echo htmlspecialchars($student['building_name'] . ' - Room ' . $student['room_number']); ?>
                        <br><small>The complaint subject will be automatically generated based on your room, complaint type, and any specific target you select.</small>
                    </div>
                    <?php elseif ($student['building_name']): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Your Location:</strong> <?php echo htmlspecialchars($student['building_name']); ?>
                        <br><small>The complaint subject will be automatically generated based on your building, complaint type, and any specific target you select.</small>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Note:</strong> You don't have a room assigned yet. The complaint subject will be generated as "Dormitory - [Complaint Type]".
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Complaint Type</label>
                        <select name="complaint_type" class="form-select" required>
                            <option value="">Select Complaint Type</option>
                            <option value="noise">Noise Complaint</option>
                            <option value="maintenance">Maintenance Issue</option>
                            <option value="cleanliness">Cleanliness Issue</option>
                            <option value="security">Security Concern</option>
                            <option value="roommate">Roommate Issue</option>
                            <option value="facility">Facility Problem</option>
                            <option value="policy">Policy Violation</option>
                            <option value="other">Other Issue</option>
                        </select>
                        <small class="form-text text-muted">Select the type of complaint that best describes your issue.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Who/What is this complaint about? (Optional)</label>
                        <div class="row">
                            <div class="col-md-6">
                                <select name="target_type" class="form-select" id="targetType">
                                    <option value="">Select Target Type</option>
                                    <option value="room">Specific Room</option>
                                    <option value="person">Specific Person</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div id="roomTargetGroup" style="display:none;">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <select name="target_building" class="form-select" id="targetBuilding" disabled>
                                                <option value="">Select Building</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <select name="target_value" class="form-select" id="targetValue" disabled>
                                                <option value="">Select Room</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="personTargetGroup" style="display:none;">
                                    <select name="target_value_person" class="form-select" id="targetValuePerson" disabled>
                                        <option value="">Select Person</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <small class="form-text text-muted">If your complaint is about a specific room or person, select them here. Leave blank for general complaints. <strong>Note:</strong> You cannot report yourself - your name will not appear in the person list.</small>
                    </div>
                    
                    <!-- Subject Preview -->
                    <div class="mb-3" id="subjectPreview" style="display: none;">
                        <div class="alert alert-light border">
                            <i class="fas fa-eye"></i>
                            <span id="subjectPreviewText"></span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Detailed Description</label>
                        <textarea name="description" class="form-control" rows="6" required placeholder="Please provide a detailed description of your complaint..."></textarea>
                        <small class="form-text text-muted">Include specific details, dates, times, and any relevant information that will help us understand and address your concern.</small>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> All complaints are reviewed by the dormitory administration. Please ensure your complaint is accurate and constructive.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Complaint</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Complaint Modal -->
<div class="modal fade" id="viewComplaintModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complaint Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="complaintDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#complaintsTable').DataTable({
        order: [[3, 'desc']],
        pageLength: 10
    });
    
    // Auto-generate subject preview
    var studentLocation = '<?php echo $student['room_number'] && $student['building_name'] ? $student['building_name'] . ' - Room ' . $student['room_number'] : ($student['building_name'] ? $student['building_name'] : 'Dormitory'); ?>';
    
    // Building and room options for target selection
    var buildings = <?php echo json_encode($buildings); ?>;
    var buildingRooms = <?php echo json_encode($building_rooms); ?>;
    // Student options for target selection (all students with room and building)
    var studentOptions = <?php echo json_encode($students_in_building); ?>;
    
    function updateSubjectPreview() {
        var complaintType = $('select[name="complaint_type"]').val();
        var targetType = $('select[name="target_type"]').val();
        var targetValue = $('select[name="target_value"]').val();
        
        var typeLabels = {
            'noise': 'Noise Complaint',
            'maintenance': 'Maintenance Issue',
            'cleanliness': 'Cleanliness Issue',
            'security': 'Security Concern',
            'roommate': 'Roommate Issue',
            'facility': 'Facility Problem',
            'policy': 'Policy Violation',
            'other': 'Other Issue'
        };
        
        if (complaintType && typeLabels[complaintType]) {
            var generatedSubject = studentLocation + ' - ' + typeLabels[complaintType];
            
            // Add target information if specified
            if (targetType) {
                if (targetType === 'room') {
                    var bName = $('#targetBuilding option:selected').text();
                    if (bName && targetValue) {
                        generatedSubject += ' (Regarding ' + bName + ' - Room ' + targetValue + ')';
                    }
                } else if (targetType === 'person') {
                    generatedSubject += ' (Regarding ' + targetValue + ')';
                }
            }
            
            $('#subjectPreviewText').html('<strong>Generated Subject:</strong> ' + generatedSubject);
            $('#subjectPreview').show();
        } else {
            $('#subjectPreview').hide();
        }
    }
    
    $('select[name="complaint_type"]').on('change', updateSubjectPreview);
    
    // Handle target type selection
    $('#targetType').on('change', function() {
        var targetType = $(this).val();
        var targetBuilding = $('#targetBuilding');
        var targetRoom = $('#targetValue');
        var targetPerson = $('#targetValuePerson');
        
        // Reset all
        $('#roomTargetGroup').hide();
        $('#personTargetGroup').hide();
        targetBuilding.prop('disabled', true).empty().append('<option value="">Select Building</option>');
        targetRoom.prop('disabled', true).empty().append('<option value="">Select Room</option>');
        targetPerson.prop('disabled', true).empty().append('<option value="">Select Person</option>');
        
        if (targetType === 'room') {
            $('#roomTargetGroup').show();
            targetBuilding.prop('disabled', false);
            buildings.forEach(function(b) {
                targetBuilding.append('<option value="' + b.id + '">' + b.name + '</option>');
            });
        } else if (targetType === 'person') {
            $('#personTargetGroup').show();
            targetPerson.prop('disabled', false);
            studentOptions.forEach(function(student) {
                var displayName = student.first_name + ' ' + student.last_name;
                var identifier = ' (' + student.school_id + ' - Room ' + student.room_number + ')';
                targetPerson.append('<option value="' + displayName + '">' + displayName + identifier + '</option>');
            });
        }
        
        updateSubjectPreview();
    });
    
    // Populate rooms when building changes
    $('#targetBuilding').on('change', function() {
        var buildingId = $(this).val();
        var targetRoom = $('#targetValue');
        targetRoom.prop('disabled', true).empty().append('<option value="">Select Room</option>');
        if (buildingId && buildingRooms[buildingId]) {
            targetRoom.prop('disabled', false);
            buildingRooms[buildingId].forEach(function(r) {
                targetRoom.append('<option value="' + r.room_number + '">Room ' + r.room_number + '</option>');
            });
        }
        updateSubjectPreview();
    });
    
    // Handle target value selection
    $('#targetValue, #targetValuePerson').on('change', function(){
        if ($('#targetType').val() === 'person') {
            $('select[name="target_value"]').val($('#targetValuePerson').val());
        }
        updateSubjectPreview();
    });
    
    // Handle view complaint modal
    $('#viewComplaintModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var complaint = button.data('complaint');
        var modal = $(this);
        
        var statusClass = '';
        switch (complaint.status) {
            case 'pending': statusClass = 'badge bg-warning'; break;
            case 'investigating': statusClass = 'badge bg-info'; break;
            case 'resolved': statusClass = 'badge bg-success'; break;
            case 'closed': statusClass = 'badge bg-secondary'; break;
        }
        
        var content = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Complaint Information</h6>
                    <p><strong>Subject:</strong> ${complaint.subject}</p>
                    <p><strong>Status:</strong> <span class="${statusClass}">${complaint.status}</span></p>
                    <p><strong>Submitted:</strong> ${new Date(complaint.created_at).toLocaleString()}</p>
                </div>
                <div class="col-md-6">
                    <h6>Processing</h6>
                    ${complaint.resolved_at ? `<p><strong>Resolved:</strong> ${new Date(complaint.resolved_at).toLocaleString()}</p>` : '<p><strong>Resolved:</strong> Not yet resolved</p>'}
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Description</h6>
                    <p>${complaint.description}</p>
                </div>
            </div>
            ${complaint.admin_response ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Admin Response</h6>
                    <p>${complaint.admin_response}</p>
                </div>
            </div>
            ` : ''}
        `;
        
        modal.find('#complaintDetails').html(content);
    });
});
</script>

<?php include 'includes/footer.php'; ?> 