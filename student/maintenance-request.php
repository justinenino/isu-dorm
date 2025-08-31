<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['submit_request'])) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $priority = sanitizeInput($_POST['priority']);
    $room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : null;
    $contact_preference = sanitizeInput($_POST['contact_preference']);
    
    if (empty($title) || empty($description)) {
        $error = 'Title and description are required.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Handle file upload if provided
            $photo_filename = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_extension, $allowed_types)) {
                    $error = 'Only JPG, PNG, and GIF images are allowed.';
                } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) { // 5MB limit
                    $error = 'Photo size must be less than 5MB.';
                } else {
                    $photo_filename = 'maintenance_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $file_extension;
                    $upload_path = '../uploads/maintenance/' . $photo_filename;
                    
                    // Create directory if it doesn't exist
                    if (!is_dir('../uploads/maintenance/')) {
                        mkdir('../uploads/maintenance/', 0755, true);
                    }
                    
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                        $error = 'Error uploading photo. Please try again.';
                    }
                }
            }
            
            if (!$error) {
                $stmt = $pdo->prepare("
                    INSERT INTO maintenance_requests (
                        student_id, title, description, priority, room_id, 
                        contact_preference, photo_filename, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $title,
                    $description,
                    $priority,
                    $room_id,
                    $contact_preference,
                    $photo_filename
                ]);
                
                $message = 'Maintenance request submitted successfully! We will review and respond to your request soon.';
                logActivity($_SESSION['user_id'], "Submitted maintenance request: $title");
                
                // Clear form data
                $title = $description = $priority = $room_id = $contact_preference = '';
            }
            
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch student's current room assignment
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT r.room_id, r.room_number, b.building_name
        FROM reservations res
        JOIN bedspaces bs ON res.bedspace_id = bs.bedspace_id
        JOIN rooms r ON bs.room_id = r.room_id
        JOIN buildings b ON r.building_id = b.building_id
        WHERE res.student_id = ? AND res.status = 'occupied'
        ORDER BY res.created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $current_room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch available rooms for selection
    $rooms_stmt = $pdo->query("
        SELECT r.room_id, r.room_number, b.building_name
        FROM rooms r
        JOIN buildings b ON r.building_id = b.building_id
        ORDER BY b.building_name, r.room_number
    ");
    $available_rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $current_room = null;
    $available_rooms = [];
}

$page_title = "Submit Maintenance Request";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-tools me-2"></i>Submit Maintenance Request
                </h1>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Maintenance Request Form -->
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-edit me-2"></i>Request Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="submit_request" value="1">
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">Request Title *</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" 
                                                   required maxlength="100" placeholder="Brief description of the issue...">
                                            <div class="form-text">Keep it concise and descriptive</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="priority" class="form-label">Priority Level *</label>
                                            <select class="form-select" id="priority" name="priority" required>
                                                <option value="">Select Priority</option>
                                                <option value="low" <?php echo (isset($priority) && $priority === 'low') ? 'selected' : ''; ?>>Low - Minor issue, not urgent</option>
                                                <option value="medium" <?php echo (isset($priority) && $priority === 'medium') ? 'selected' : ''; ?>>Medium - Moderate issue, needs attention</option>
                                                <option value="high" <?php echo (isset($priority) && $priority === 'high') ? 'selected' : ''; ?>>High - Urgent issue, affects safety/functionality</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Detailed Description *</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" 
                                              required maxlength="1000" placeholder="Please provide a detailed description of the maintenance issue..."><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                                    <div class="form-text">
                                        <span id="charCount">0</span>/1000 characters. Include specific details about the problem, when it started, and any relevant information.
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="room_id" class="form-label">Room Location</label>
                                            <select class="form-select" id="room_id" name="room_id">
                                                <option value="">Select Room (Optional)</option>
                                                <?php if ($current_room): ?>
                                                    <option value="<?php echo $current_room['room_id']; ?>" selected>
                                                        <?php echo htmlspecialchars($current_room['room_number'] . ' - ' . $current_room['building_name']); ?> (Current Room)
                                                    </option>
                                                <?php endif; ?>
                                                <?php foreach ($available_rooms as $room): ?>
                                                    <?php if (!$current_room || $room['room_id'] !== $current_room['room_id']): ?>
                                                        <option value="<?php echo $room['room_id']; ?>">
                                                            <?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['building_name']); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Select the room where the issue is located</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="contact_preference" class="form-label">Preferred Contact Method</label>
                                            <select class="form-select" id="contact_preference" name="contact_preference">
                                                <option value="email" <?php echo (isset($contact_preference) && $contact_preference === 'email') ? 'selected' : ''; ?>>Email</option>
                                                <option value="phone" <?php echo (isset($contact_preference) && $contact_preference === 'phone') ? 'selected' : ''; ?>>Phone Call</option>
                                                <option value="sms" <?php echo (isset($contact_preference) && $contact_preference === 'sms') ? 'selected' : ''; ?>>SMS/Text</option>
                                                <option value="in_person" <?php echo (isset($contact_preference) && $contact_preference === 'in_person') ? 'selected' : ''; ?>>In Person</option>
                                            </select>
                                            <div class="form-text">How would you prefer to be contacted about updates?</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Photo Attachment (Optional)</label>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                    <div class="form-text">
                                        Upload a photo of the issue if possible. This helps maintenance staff understand the problem better.
                                        <br>Accepted formats: JPG, PNG, GIF. Max size: 5MB.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="confirm_accurate" required>
                                        <label class="form-check-label" for="confirm_accurate">
                                            I confirm that the information provided is accurate and complete *
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="reset" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-undo me-2"></i>Reset Form
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Information Panel -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-info-circle me-2"></i>Guidelines
                            </h6>
                        </div>
                        <div class="card-body">
                            <h6 class="text-primary">Before Submitting:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Ensure the issue is maintenance-related</li>
                                <li><i class="fas fa-check text-success me-2"></i>Provide clear, detailed descriptions</li>
                                <li><i class="fas fa-check text-success me-2"></i>Include photos if possible</li>
                                <li><i class="fas fa-check text-success me-2"></i>Select appropriate priority level</li>
                            </ul>
                            
                            <hr>
                            
                            <h6 class="text-primary">Response Time:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-clock text-warning me-2"></i><strong>High Priority:</strong> 24-48 hours</li>
                                <li><i class="fas fa-clock text-warning me-2"></i><strong>Medium Priority:</strong> 3-5 days</li>
                                <li><i class="fas fa-clock text-warning me-2"></i><strong>Low Priority:</strong> 1-2 weeks</li>
                            </ul>
                            
                            <hr>
                            
                            <h6 class="text-primary">What Happens Next?</h6>
                            <ol class="small">
                                <li>Your request is reviewed by maintenance staff</li>
                                <li>You'll receive updates on the status</li>
                                <li>Maintenance staff may contact you for more details</li>
                                <li>You'll be notified when the issue is resolved</li>
                            </ol>
                        </div>
                    </div>
                    
                    <!-- Current Room Info -->
                    <?php if ($current_room): ?>
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-home me-2"></i>Your Current Room
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center">
                                    <i class="fas fa-building fa-3x text-primary mb-3"></i>
                                    <h5><?php echo htmlspecialchars($current_room['building_name']); ?></h5>
                                    <h4 class="text-primary">Room <?php echo htmlspecialchars($current_room['room_number']); ?></h4>
                                    <small class="text-muted">This room is pre-selected for your convenience</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Character counter for description
document.getElementById('description').addEventListener('input', function() {
    const maxLength = 1000;
    const currentLength = this.value.length;
    const charCount = document.getElementById('charCount');
    
    charCount.textContent = currentLength;
    
    if (currentLength > maxLength * 0.9) {
        charCount.className = 'text-warning';
    } else {
        charCount.className = '';
    }
    
    if (currentLength > maxLength) {
        charCount.className = 'text-danger';
    }
});

// File size validation
document.getElementById('photo').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            alert('File size must be less than 5MB. Please select a smaller file.');
            this.value = '';
        }
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    const priority = document.getElementById('priority').value;
    
    if (!title || !description || !priority) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
    if (title.length < 5) {
        e.preventDefault();
        alert('Title must be at least 5 characters long.');
        return false;
    }
    
    if (description.length < 20) {
        e.preventDefault();
        alert('Description must be at least 20 characters long.');
        return false;
    }
    
    return true;
});
</script>

<?php include 'includes/footer.php'; ?>
