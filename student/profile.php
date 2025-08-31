<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'update_profile') {
            $first_name = sanitizeInput($_POST['first_name']);
            $middle_name = sanitizeInput($_POST['middle_name']);
            $last_name = sanitizeInput($_POST['last_name']);
            $mobile_number = sanitizeInput($_POST['mobile_number']);
            $email = sanitizeInput($_POST['email']);
            $facebook_link = sanitizeInput($_POST['facebook_link']);
            $emergency_contact_name = sanitizeInput($_POST['emergency_contact_name']);
            $emergency_contact_mobile = sanitizeInput($_POST['emergency_contact_mobile']);
            $emergency_contact_relationship = sanitizeInput($_POST['emergency_contact_relationship']);
            
            if (empty($first_name) || empty($last_name) || empty($mobile_number) || empty($email)) {
                $error = 'Required fields cannot be empty.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Update student profile
                    $stmt = $pdo->prepare("
                        UPDATE students 
                        SET first_name = ?, middle_name = ?, last_name = ?, mobile_number = ?, 
                            email = ?, facebook_link = ?, emergency_contact_name = ?, 
                            emergency_contact_mobile = ?, emergency_contact_relationship = ?, 
                            updated_at = NOW() 
                        WHERE user_id = ?
                    ");
                    $stmt->execute([
                        $first_name, $middle_name, $last_name, $mobile_number, $email,
                        $facebook_link, $emergency_contact_name, $emergency_contact_mobile,
                        $emergency_contact_relationship, $_SESSION['user_id']
                    ]);
                    
                    // Update user email
                    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                    $stmt->execute([$email, $_SESSION['user_id']]);
                    
                    $message = 'Profile updated successfully!';
                    logActivity($_SESSION['user_id'], 'Profile updated');
                    
                } catch (PDOException $e) {
                    $error = 'Error updating profile: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'All password fields are required.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New password and confirmation password do not match.';
            } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
                $error = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $current_hash = $stmt->fetchColumn();
                    
                    if (!password_verify($current_password, $current_hash)) {
                        $error = 'Current password is incorrect.';
                    } else {
                        // Update password
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
                        $stmt->execute([$new_hash, $_SESSION['user_id']]);
                        
                        $message = 'Password changed successfully!';
                        logActivity($_SESSION['user_id'], 'Password changed');
                    }
                } catch (PDOException $e) {
                    $error = 'Error changing password: ' . $e->getMessage();
                }
            }
        }
    }
}

// Fetch student profile
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.email, u.created_at, u.last_login
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        $error = 'Student profile not found.';
    }
} catch (PDOException $e) {
    $error = 'Error fetching profile: ' . $e->getMessage();
    $student = [];
}

$page_title = "My Profile";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-user me-2"></i>My Profile
                </h1>
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

            <?php if ($student): ?>
                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">First Name *</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                                       value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="middle_name" class="form-label">Middle Name</label>
                                                <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                                       value="<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">Last Name *</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                                       value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="mobile_number" class="form-label">Mobile Number *</label>
                                                <input type="tel" class="form-control" id="mobile_number" name="mobile_number" 
                                                       value="<?php echo htmlspecialchars($student['mobile_number']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email *</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($student['email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="facebook_link" class="form-label">Facebook Link</label>
                                        <input type="url" class="form-control" id="facebook_link" name="facebook_link" 
                                               value="<?php echo htmlspecialchars($student['facebook_link'] ?? ''); ?>"
                                               placeholder="https://facebook.com/username">
                                    </div>
                                    
                                    <hr>
                                    
                                    <h6 class="mb-3">Emergency Contact Information</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="emergency_contact_name" class="form-label">Contact Name</label>
                                                <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                                       value="<?php echo htmlspecialchars($student['emergency_contact_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="emergency_contact_mobile" class="form-label">Contact Mobile</label>
                                                <input type="tel" class="form-control" id="emergency_contact_mobile" name="emergency_contact_mobile" 
                                                       value="<?php echo htmlspecialchars($student['emergency_contact_mobile'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                                                <input type="text" class="form-control" id="emergency_contact_relationship" name="emergency_contact_relationship" 
                                                       value="<?php echo htmlspecialchars($student['emergency_contact_relationship'] ?? ''); ?>"
                                                       placeholder="e.g., Parent, Guardian, Sibling">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information & Change Password -->
                    <div class="col-lg-4">
                        <!-- Account Information -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-info-circle me-2"></i>Account Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Student ID:</strong></label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($student['student_id']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Username:</strong></label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($student['username']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Status:</strong></label>
                                    <p class="form-control-plaintext">
                                        <span class="badge bg-<?php echo $student['status'] === 'approved' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Account Created:</strong></label>
                                    <p class="form-control-plaintext"><?php echo formatDate($student['created_at'], 'M d, Y'); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Last Login:</strong></label>
                                    <p class="form-control-plaintext"><?php echo $student['last_login'] ? formatDate($student['last_login'], 'M d, Y h:i A') : 'Never'; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password *</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password *</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" 
                                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                        <small class="form-text text-muted">Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long.</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-save me-2"></i>Update Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Read-only Information -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-file-alt me-2"></i>Additional Information (Read-only)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label"><strong>Date of Birth:</strong></label>
                                            <p class="form-control-plaintext"><?php echo formatDate($student['date_of_birth'], 'M d, Y'); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label"><strong>Gender:</strong></label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($student['gender']); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label"><strong>School ID:</strong></label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($student['school_id_number']); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label"><strong>LRN:</strong></label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($student['lrn']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><strong>Address:</strong></label>
                                            <p class="form-control-plaintext">
                                                <?php 
                                                $address_parts = [];
                                                if ($student['province']) $address_parts[] = $student['province'];
                                                if ($student['municipality']) $address_parts[] = $student['municipality'];
                                                if ($student['barangay']) $address_parts[] = $student['barangay'];
                                                if ($student['street_purok']) $address_parts[] = $student['street_purok'];
                                                echo implode(', ', $address_parts);
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><strong>Course:</strong></label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($student['course'] ?? 'Not specified'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>
