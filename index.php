<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Check if user is already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('student/dashboard.php');
    }
}

// Handle login form submission
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        // Check if it's admin login
        if ($username === 'Dorm_admin') {
            $sql = "SELECT u.*, a.full_name FROM users u 
                    JOIN admin_accounts a ON u.id = a.user_id 
                    WHERE u.username = ? AND u.role = 'admin' AND u.status = 'active'";
            $user = fetchOne($sql, [$username]);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                logActivity($user['id'], 'admin_login', 'Admin logged in successfully');
                redirect('admin/dashboard.php');
            } else {
                $error_message = 'Invalid admin credentials.';
            }
        } else {
            // Check if it's student login
            $sql = "SELECT u.*, s.student_id, s.first_name, s.last_name, s.registration_status 
                    FROM users u 
                    JOIN students s ON u.id = s.user_id 
                    WHERE u.username = ? AND u.role = 'student' AND u.status = 'active'";
            $user = fetchOne($sql, [$username]);
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['registration_status'] === 'approved') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['student_id'] = $user['student_id'];
                    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    
                    logActivity($user['id'], 'student_login', 'Student logged in successfully');
                    redirect('student/dashboard.php');
                } else {
                    $error_message = 'Your registration is still pending approval.';
                }
            } else {
                $error_message = 'Invalid student credentials.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Left Side - Welcome Section -->
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center" 
                 style="background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 50%, var(--primary-yellow) 100%);">
                <div class="text-center text-white p-5">
                    <div class="mb-4">
                        <i class="fas fa-building fa-5x mb-3"></i>
                        <h1 class="display-4 fw-bold">Welcome to ISU Dormitory</h1>
                        <p class="lead">Manage your dormitory experience with ease</p>
                    </div>
                    <div class="row text-center">
                        <div class="col-md-6 mb-3">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h5>Student Portal</h5>
                            <p>Reserve rooms, submit requests, and stay updated</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <i class="fas fa-shield-alt fa-2x mb-2"></i>
                            <h5>Admin Portal</h5>
                            <p>Manage buildings, approve requests, and monitor activities</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Login Form -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center">
                <div class="w-100" style="max-width: 400px;">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold" style="color: var(--primary-green);">Login</h2>
                        <p class="text-muted">Access your dormitory management portal</p>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="username" class="form-label fw-semibold">
                                        <i class="fas fa-user me-2"></i>Username
                                    </label>
                                    <input type="text" class="form-control form-control-lg" 
                                           id="username" name="username" 
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                           placeholder="Enter your username" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label fw-semibold">
                                        <i class="fas fa-lock me-2"></i>Password
                                    </label>
                                    <input type="password" class="form-control form-control-lg" 
                                           id="password" name="password" 
                                           placeholder="Enter your password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </form>
                            
                            <hr class="my-4">
                            
                            <div class="text-center">
                                <p class="text-muted mb-2">New student?</p>
                                <a href="student/register.php" class="btn btn-outline-success">
                                    <i class="fas fa-user-plus me-2"></i>Register Here
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Default admin: Dorm_admin / Dorm_admin
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
            
            // Add loading state to form submission
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
                submitBtn.disabled = true;
            });
        });
    </script>
</body>
</html>
