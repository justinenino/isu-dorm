<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('../admin/dashboard.php');
    } else {
        redirect('dashboard.php');
    }
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        // Sanitize and validate input
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $middle_name = sanitizeInput($_POST['middle_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $province = sanitizeInput($_POST['province'] ?? '');
        $municipality = sanitizeInput($_POST['municipality'] ?? '');
        $barangay = sanitizeInput($_POST['barangay'] ?? '');
        $street_purok = sanitizeInput($_POST['street_purok'] ?? '');
        $mobile_number = sanitizeInput($_POST['mobile_number'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $facebook_link = sanitizeInput($_POST['facebook_link'] ?? '');
        $school_id_number = sanitizeInput($_POST['school_id_number'] ?? '');
        $lrn = sanitizeInput($_POST['lrn'] ?? '');
        $emergency_contact_name = sanitizeInput($_POST['emergency_contact_name'] ?? '');
        $emergency_contact_mobile = sanitizeInput($_POST['emergency_contact_mobile'] ?? '');
        $emergency_contact_relationship = sanitizeInput($_POST['emergency_contact_relationship'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        $errors = [];

        if (empty($first_name)) $errors[] = 'First name is required';
        if (empty($last_name)) $errors[] = 'Last name is required';
        if (empty($date_of_birth)) $errors[] = 'Date of birth is required';
        if (empty($gender)) $errors[] = 'Gender is required';
        if (empty($province)) $errors[] = 'Province is required';
        if (empty($municipality)) $errors[] = 'Municipality is required';
        if (empty($barangay)) $errors[] = 'Barangay is required';
        if (empty($mobile_number)) $errors[] = 'Mobile number is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($school_id_number)) $errors[] = 'School ID number is required';
        if (empty($lrn)) $errors[] = 'LRN is required';
        if (empty($emergency_contact_name)) $errors[] = 'Emergency contact name is required';
        if (empty($emergency_contact_mobile)) $errors[] = 'Emergency contact mobile is required';
        if (empty($emergency_contact_relationship)) $errors[] = 'Emergency contact relationship is required';
        if (empty($password)) $errors[] = 'Password is required';
        if (strlen($password) < PASSWORD_MIN_LENGTH) $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        if ($password !== $confirm_password) $errors[] = 'Passwords do not match';

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Validate school ID number (6 digits)
        if (!preg_match('/^\d{6}$/', $school_id_number)) {
            $errors[] = 'School ID number must be exactly 6 digits';
        }

        // Validate LRN (12 digits)
        if (!preg_match('/^\d{12}$/', $lrn)) {
            $errors[] = 'LRN must be exactly 12 digits';
        }

        // Check if email already exists
        $existing_email = fetchOne("SELECT id FROM students WHERE email = ?", [$email]);
        if ($existing_email) {
            $errors[] = 'Email address is already registered';
        }

        // Check if LRN already exists
        $existing_lrn = fetchOne("SELECT id FROM students WHERE lrn = ?", [$lrn]);
        if ($existing_lrn) {
            $errors[] = 'LRN is already registered';
        }

        // Handle file upload
        $attachment_path = '';
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
                $errors[] = 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS);
            }
            
            if ($file['size'] > MAX_FILE_SIZE) {
                $errors[] = 'File size too large. Maximum: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
            }
            
            if (empty($errors)) {
                $upload_dir = '../' . UPLOAD_PATH . 'student_documents/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $filename = 'student_' . time() . '_' . $lrn . '.' . $file_extension;
                $attachment_path = UPLOAD_PATH . 'student_documents/' . $filename;
                
                if (!move_uploaded_file($file['tmp_name'], '../' . $attachment_path)) {
                    $errors[] = 'Failed to upload file';
                }
            }
        } else {
            $errors[] = 'Document attachment is required';
        }

        if (empty($errors)) {
            try {
                // Start transaction
                $pdo = getDBConnection();
                $pdo->beginTransaction();

                // Create user account
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $username = 'student_' . $lrn;
                
                $sql = "INSERT INTO users (username, password, role, status) VALUES (?, ?, 'student', 'active')";
                executeQuery($sql, [$username, $hashed_password]);
                $user_id = getLastInsertId();

                // Generate student ID
                $student_id = 'STU' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);

                // Create student record
                $sql = "INSERT INTO students (
                    user_id, student_id, first_name, middle_name, last_name, date_of_birth, 
                    gender, province, municipality, barangay, street_purok, mobile_number, 
                    email, facebook_link, school_id_number, lrn, attachment_path,
                    emergency_contact_name, emergency_contact_mobile, emergency_contact_relationship,
                    registration_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                
                executeQuery($sql, [
                    $user_id, $student_id, $first_name, $middle_name, $last_name, $date_of_birth,
                    $gender, $province, $municipality, $barangay, $street_purok, $mobile_number,
                    $email, $facebook_link, $school_id_number, $lrn, $attachment_path,
                    $emergency_contact_name, $emergency_contact_mobile, $emergency_contact_relationship
                ]);

                // Log activity
                logActivity($user_id, 'student_registration', 'Student registered successfully');

                $pdo->commit();

                $success_message = 'Registration submitted successfully! Your application is now pending approval. You will be notified via email once approved.';
                
                // Clear form data
                $_POST = [];
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = 'Registration failed. Please try again. Error: ' . $e->getMessage();
                
                // Delete uploaded file if registration failed
                if (!empty($attachment_path) && file_exists('../' . $attachment_path)) {
                    unlink('../' . $attachment_path);
                }
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Left Side - Information -->
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center" 
                 style="background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 50%, var(--primary-yellow) 100%);">
                <div class="text-center text-white p-5">
                    <div class="mb-4">
                        <i class="fas fa-user-graduate fa-5x mb-3"></i>
                        <h1 class="display-4 fw-bold">Student Registration</h1>
                        <p class="lead">Join our dormitory community</p>
                    </div>
                    <div class="row text-center">
                        <div class="col-md-6 mb-3">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h5>Easy Registration</h5>
                            <p>Simple and secure registration process</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <i class="fas fa-shield-alt fa-2x mb-2"></i>
                            <h5>Secure & Private</h5>
                            <p>Your information is protected and secure</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Registration Form -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center">
                <div class="w-100" style="max-width: 600px;">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold" style="color: var(--primary-green);">Student Registration</h2>
                        <p class="text-muted">Complete the form below to register for dormitory accommodation</p>
                    </div>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-4">
                            <form method="POST" action="" enctype="multipart/form-data" data-validate>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                
                                <!-- Personal Information -->
                                <h5 class="mb-3" style="color: var(--primary-green);">
                                    <i class="fas fa-user me-2"></i>Personal Information
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="first_name" class="form-label fw-semibold">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="middle_name" class="form-label fw-semibold">Middle Name</label>
                                        <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                               value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="last_name" class="form-label fw-semibold">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="date_of_birth" class="form-label fw-semibold">Date of Birth *</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                               value="<?php echo $_POST['date_of_birth'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="gender" class="form-label fw-semibold">Gender *</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo ($_POST['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Address Information -->
                                <h5 class="mb-3 mt-4" style="color: var(--primary-green);">
                                    <i class="fas fa-map-marker-alt me-2"></i>Address Information
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="province" class="form-label fw-semibold">Province *</label>
                                        <input type="text" class="form-control" id="province" name="province" 
                                               value="<?php echo htmlspecialchars($_POST['province'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="municipality" class="form-label fw-semibold">Municipality *</label>
                                        <input type="text" class="form-control" id="municipality" name="municipality" 
                                               value="<?php echo htmlspecialchars($_POST['municipality'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="barangay" class="form-label fw-semibold">Barangay *</label>
                                        <input type="text" class="form-control" id="barangay" name="barangay" 
                                               value="<?php echo htmlspecialchars($_POST['barangay'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="street_purok" class="form-label fw-semibold">Street/Purok</label>
                                    <input type="text" class="form-control" id="street_purok" name="street_purok" 
                                           value="<?php echo htmlspecialchars($_POST['street_purok'] ?? ''); ?>">
                                </div>
                                
                                <!-- Contact Information -->
                                <h5 class="mb-3 mt-4" style="color: var(--primary-green);">
                                    <i class="fas fa-phone me-2"></i>Contact Information
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="mobile_number" class="form-label fw-semibold">Mobile Number *</label>
                                        <input type="tel" class="form-control" id="mobile_number" name="mobile_number" 
                                               value="<?php echo htmlspecialchars($_POST['mobile_number'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label fw-semibold">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="facebook_link" class="form-label fw-semibold">Facebook Link (Optional)</label>
                                    <input type="url" class="form-control" id="facebook_link" name="facebook_link" 
                                           value="<?php echo htmlspecialchars($_POST['facebook_link'] ?? ''); ?>">
                                </div>
                                
                                <!-- Academic Information -->
                                <h5 class="mb-3 mt-4" style="color: var(--primary-green);">
                                    <i class="fas fa-graduation-cap me-2"></i>Academic Information
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="school_id_number" class="form-label fw-semibold">School ID Number (6 digits) *</label>
                                        <input type="text" class="form-control" id="school_id_number" name="school_id_number" 
                                               value="<?php echo htmlspecialchars($_POST['school_id_number'] ?? ''); ?>" 
                                               maxlength="6" pattern="\d{6}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="lrn" class="form-label fw-semibold">LRN (12 digits) *</label>
                                        <input type="text" class="form-control" id="lrn" name="lrn" 
                                               value="<?php echo htmlspecialchars($_POST['lrn'] ?? ''); ?>" 
                                               maxlength="12" pattern="\d{12}" required>
                                    </div>
                                </div>
                                
                                <!-- Document Upload -->
                                <h5 class="mb-3 mt-4" style="color: var(--primary-green);">
                                    <i class="fas fa-file-upload me-2"></i>Document Upload
                                </h5>
                                
                                <div class="mb-3">
                                    <label for="attachment" class="form-label fw-semibold">ID/Document (PDF, JPG, PNG) *</label>
                                    <input type="file" class="form-control" id="attachment" name="attachment" 
                                           accept=".pdf,.jpg,.jpeg,.png" required>
                                    <div class="form-text">Maximum file size: 5MB. Allowed formats: PDF, JPG, PNG</div>
                                </div>
                                
                                <!-- Emergency Contact -->
                                <h5 class="mb-3 mt-4" style="color: var(--primary-green);">
                                    <i class="fas fa-ambulance me-2"></i>Emergency Contact
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="emergency_contact_name" class="form-label fw-semibold">Contact Name *</label>
                                        <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                               value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="emergency_contact_mobile" class="form-label fw-semibold">Contact Mobile *</label>
                                        <input type="tel" class="form-control" id="emergency_contact_mobile" name="emergency_contact_mobile" 
                                               value="<?php echo htmlspecialchars($_POST['emergency_contact_mobile'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="emergency_contact_relationship" class="form-label fw-semibold">Relationship *</label>
                                    <select class="form-select" id="emergency_contact_relationship" name="emergency_contact_relationship" required>
                                        <option value="">Select Relationship</option>
                                        <option value="Parent" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Parent' ? 'selected' : ''; ?>>Parent</option>
                                        <option value="Guardian" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Guardian' ? 'selected' : ''; ?>>Guardian</option>
                                        <option value="Sibling" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Sibling' ? 'selected' : ''; ?>>Sibling</option>
                                        <option value="Relative" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Relative' ? 'selected' : ''; ?>>Relative</option>
                                        <option value="Other" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <!-- Account Security -->
                                <h5 class="mb-3 mt-4" style="color: var(--primary-green);">
                                    <i class="fas fa-lock me-2"></i>Account Security
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label fw-semibold">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                        <div class="form-text">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label fw-semibold">Confirm Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="../terms.php" target="_blank">Terms and Conditions</a> and 
                                            <a href="../privacy.php" target="_blank">Privacy Policy</a> *
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>Submit Registration
                                    </button>
                                </div>
                            </form>
                            
                            <hr class="my-4">
                            
                            <div class="text-center">
                                <p class="text-muted mb-0">Already have an account?</p>
                                <a href="../index.php" class="btn btn-outline-success">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login Here
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            All fields marked with * are required. Your registration will be reviewed by administrators.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // File size validation
        document.getElementById('attachment').addEventListener('change', function() {
            const file = this.files[0];
            const maxSize = <?php echo MAX_FILE_SIZE; ?>;
            
            if (file && file.size > maxSize) {
                alert('File size too large. Maximum size: ' + (maxSize / 1024 / 1024) + 'MB');
                this.value = '';
            }
        });
        
        // Form submission enhancement
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
