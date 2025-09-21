<?php
require_once '../config/database.php';

$success_message = '';
$error_message = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Please fill in all password fields.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'New password must be at least 6 characters long.';
        } else {
            $pdo = getConnection();
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($current_password, $admin['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET password = ?, updated_at = NOW() WHERE id = ?");
                
                if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    $success_message = 'Password updated successfully!';
                } else {
                    $error_message = 'Failed to update password. Please try again.';
                }
            } else {
                $error_message = 'Current password is incorrect.';
            }
        }
    } elseif ($action === 'change_username') {
        $current_password = $_POST['username_current_password'];
        $new_username = trim($_POST['new_username']);
        $confirm_username = trim($_POST['confirm_username']);
        
        if (empty($current_password) || empty($new_username) || empty($confirm_username)) {
            $error_message = 'Please fill in all username fields.';
        } elseif ($new_username !== $confirm_username) {
            $error_message = 'New usernames do not match.';
        } elseif (strlen($new_username) < 3) {
            $error_message = 'Username must be at least 3 characters long.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
            $error_message = 'Username can only contain letters, numbers, and underscores.';
        } else {
            $pdo = getConnection();
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $stmt->execute([$new_username, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $error_message = 'Username already exists. Please choose a different one.';
            } else {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($current_password, $admin['password'])) {
                    // Update username
                    $stmt = $pdo->prepare("UPDATE admins SET username = ?, updated_at = NOW() WHERE id = ?");
                    
                    if ($stmt->execute([$new_username, $_SESSION['user_id']])) {
                        // Update session
                        $_SESSION['username'] = $new_username;
                        $success_message = 'Username updated successfully! You will need to log in again with your new username.';
                    } else {
                        $error_message = 'Failed to update username. Please try again.';
                    }
                } else {
                    $error_message = 'Current password is incorrect.';
                }
            }
        }
    }
}

$page_title = 'Admin Settings';
include 'includes/header.php';

// Get admin info
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-cog"></i> Admin Settings</h5>
            </div>
            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Admin Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle"></i> Account Information</h6>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Username:</strong></td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Account Created:</strong></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($admin['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Last Updated:</strong></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($admin['updated_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-shield-alt"></i> Security Notice</h6>
                            <p class="mb-0">For security reasons, please use strong credentials that include:</p>
                            <ul class="mb-0 mt-2">
                                <li>At least 6 characters for passwords</li>
                                <li>Mix of letters, numbers, and special characters</li>
                                <li>Unique usernames that are easy to remember</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Settings Forms -->
                <div class="row">
                    <!-- Username Change Form -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-user-edit"></i> Change Username</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="change_username">
                                    
                                    <div class="mb-3">
                                        <label for="username_current_password" class="form-label">Current Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="username_current_password" name="username_current_password" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('username_current_password')">
                                                <i class="fas fa-eye" id="username_current_password_icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_username" class="form-label">New Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="new_username" name="new_username" minlength="3" pattern="[a-zA-Z0-9_]+" required>
                                        </div>
                                        <div class="form-text">Minimum 3 characters, letters, numbers, and underscores only</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_username" class="form-label">Confirm New Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="confirm_username" name="confirm_username" minlength="3" pattern="[a-zA-Z0-9_]+" required>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save"></i> Update Username
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Password Change Form -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-key"></i> Change Password</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                                <i class="fas fa-eye" id="current_password_icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                                <i class="fas fa-eye" id="new_password_icon"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Minimum 6 characters</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                                <i class="fas fa-eye" id="confirm_password_icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-server"></i> System Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>System Version:</strong></td>
                                <td>Dormitory Management v2.0</td>
                            </tr>
                            <tr>
                                <td><strong>PHP Version:</strong></td>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Server Time:</strong></td>
                                <td><?php echo date('Y-m-d H:i:s'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Important</h6>
                            <p class="mb-0">Default admin credentials should be changed immediately after first login for security purposes. Both username and password can be updated from this page.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength indicator
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strength = calculatePasswordStrength(password);
    updatePasswordStrengthIndicator(strength);
});

function calculatePasswordStrength(password) {
    let strength = 0;
    if (password.length >= 6) strength++;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    return strength;
}

function updatePasswordStrengthIndicator(strength) {
    const field = document.getElementById('new_password');
    field.classList.remove('is-valid', 'is-invalid');
    
    if (strength >= 4) {
        field.classList.add('is-valid');
    } else if (strength >= 2) {
        // Neutral - no class
    } else {
        field.classList.add('is-invalid');
    }
}

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    this.classList.remove('is-valid', 'is-invalid');
    
    if (confirmPassword && newPassword === confirmPassword) {
        this.classList.add('is-valid');
    } else if (confirmPassword) {
        this.classList.add('is-invalid');
    }
});

// Username validation
document.getElementById('new_username').addEventListener('input', function() {
    const username = this.value;
    this.classList.remove('is-valid', 'is-invalid');
    
    if (username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username)) {
        this.classList.add('is-valid');
    } else if (username.length > 0) {
        this.classList.add('is-invalid');
    }
});

// Confirm username validation
document.getElementById('confirm_username').addEventListener('input', function() {
    const newUsername = document.getElementById('new_username').value;
    const confirmUsername = this.value;
    
    this.classList.remove('is-valid', 'is-invalid');
    
    if (confirmUsername && newUsername === confirmUsername) {
        this.classList.add('is-valid');
    } else if (confirmUsername) {
        this.classList.add('is-invalid');
    }
});
</script>

<?php include 'includes/footer.php'; ?>