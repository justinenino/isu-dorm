<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

$page_title = "Change Password";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-key me-2"></i>Change Password
                    </h6>
                </div>
                <div class="card-body">
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

                    <form method="POST">
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
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Password
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
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
