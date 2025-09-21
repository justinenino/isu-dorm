<?php
session_start(); // Always start session at the top

require_once 'config/database.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } else {
        $pdo = getConnection();
        $login_successful = false;

        // First, try admin login
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Admin login successful
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['username'] = $admin['username'];
            header('Location: admin/dashboard.php');
            exit();
        } else {
            // Try student login
            $stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ? AND learner_reference_number = ? AND application_status = 'approved' AND is_active = 1 AND is_deleted = 0");
            $stmt->execute([$username, $password]);
            $student = $stmt->fetch();

            if ($student) {
                // Student login successful
                $_SESSION['user_id'] = $student['id'];
                $_SESSION['user_type'] = 'student';
                $_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
                $_SESSION['school_id'] = $student['school_id'];
                header('Location: student/dashboard.php');
                exit();
            } else {
                // Check different scenarios for better error messages
                $stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ? AND learner_reference_number = ?");
                $stmt->execute([$username, $password]);
                $check_student = $stmt->fetch();
                
                if ($check_student) {
                    if ($check_student['application_status'] !== 'approved') {
                        $error_message = 'Your application is still pending approval. Please wait for admin approval.';
                    } elseif ($check_student['is_active'] == 0) {
                        $error_message = 'Your account has been deactivated. Please contact the dormitory administration for assistance.';
                    } elseif ($check_student['is_deleted'] == 1) {
                        $error_message = 'Your account has been archived. Please contact the dormitory administration for assistance.';
                    } else {
                        $error_message = 'Your account is not accessible at this time. Please contact the dormitory administration.';
                    }
                } else {
                    $error_message = 'Invalid credentials. Please check your School ID/Username and LRN/Password.';
                }
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
    <title>Login - Dormitory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --info-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            --light-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 15px 35px rgba(0, 0, 0, 0.15);
            --shadow-strong: 0 20px 40px rgba(0, 0, 0, 0.2);
            --border-radius: 15px;
            --border-radius-lg: 25px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-strong);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-header {
            background: var(--primary-gradient);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .login-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
            padding: 45px 35px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
            display: block;
            font-size: 0.95rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            padding: 15px 20px;
            transition: var(--transition);
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.3rem rgba(102, 126, 234, 0.15);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: #6c757d;
            font-weight: 400;
        }
        
        .btn-login {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--border-radius);
            padding: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: var(--transition);
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            position: relative;
        }

        .register-link a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 50%;
            background: var(--primary-gradient);
            transition: var(--transition);
        }

        .register-link a:hover::after {
            width: 100%;
            left: 0;
        }
        
        .register-link a:hover {
            color: #5a6fd8;
        }
        
        .alert {
            border-radius: var(--border-radius);
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(13, 202, 240, 0.1) 0%, rgba(13, 202, 240, 0.05) 100%);
            border-left: 4px solid #0dcaf0;
            color: #0c5460;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(220, 53, 69, 0.05) 100%);
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.1) 0%, rgba(25, 135, 84, 0.05) 100%);
            border-left: 4px solid #198754;
            color: #0f5132;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                max-width: none;
            }
            
            .login-body {
                padding: 30px 25px;
            }
            
            .login-header {
                padding: 30px 25px;
            }
        }

        /* Animation for form elements */
        .form-group {
            animation: slideInUp 0.6s ease-out;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h3><i class="fas fa-building"></i> Dormitory Management</h3>
            <p>Unified Login - Admin & Student Portal</p>
        </div>
        
        <div class="login-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> School ID / Username
                    </label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter School ID or Username" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> LRN / Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter LRN or Password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="register-link">
                <div class="alert alert-info" style="font-size: 0.9em; margin-bottom: 15px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Login Instructions:</strong><br>
                    • <strong>Students:</strong> Use School ID + LRN<br>
                    • <strong>Admins:</strong> Use Username + Password
                </div>
                <p>New student? <a href="register.php">Register here</a></p>
                <p><a href="registration_status.php">Check Application Status</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
