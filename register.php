<?php
require_once 'config/database.php';

$error_message = '';
$success_message = '';

if ($_POST) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $province = trim($_POST['province']);
    $municipality = trim($_POST['municipality']);
    $barangay = trim($_POST['barangay']);
    $street_purok = trim($_POST['street_purok']);
    $mobile_number = trim($_POST['mobile_number']);
    $email = trim($_POST['email']);
    $facebook_link = trim($_POST['facebook_link']);
    $school_id = trim($_POST['school_id']);
    $learner_reference_number = trim($_POST['learner_reference_number']);
    $guardian_name = trim($_POST['guardian_name']);
    $guardian_mobile = trim($_POST['guardian_mobile']);
    $guardian_relationship = trim($_POST['guardian_relationship']);
    
    // Validation
    $errors = [];
    
    if (empty($first_name) || empty($middle_name) || empty($last_name) || empty($date_of_birth) || empty($gender) ||
        empty($province) || empty($municipality) || empty($barangay) || empty($street_purok) ||
        empty($mobile_number) || empty($email) || empty($facebook_link) || empty($school_id) || empty($learner_reference_number) ||
        empty($guardian_name) || empty($guardian_mobile) || empty($guardian_relationship)) {
        $errors[] = 'Please fill in all required fields.';
    }
    
    if (strlen($school_id) != 6 || !is_numeric($school_id)) {
        $errors[] = 'School ID must be exactly 6 digits.';
    }
    
    if (strlen($learner_reference_number) != 12 || !is_numeric($learner_reference_number)) {
        $errors[] = 'Learner Reference Number must be exactly 12 digits.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Validate PDF file upload
    if (!isset($_FILES['attachment_file']) || $_FILES['attachment_file']['error'] != 0) {
        $errors[] = 'Please upload a PDF file.';
    } else {
        $file_extension = strtolower(pathinfo($_FILES['attachment_file']['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'pdf') {
            $errors[] = 'Only PDF files are allowed for attachment.';
        }
        if ($_FILES['attachment_file']['size'] > 5 * 1024 * 1024) { // 5MB limit
            $errors[] = 'PDF file size must not exceed 5MB.';
        }
    }
    
    if (empty($errors)) {
        $pdo = getConnection();
        
        // Check if school_id or LRN already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE school_id = ? OR learner_reference_number = ?");
        $stmt->execute([$school_id, $learner_reference_number]);
        $existing_count = $stmt->fetchColumn();
        
        if ($existing_count > 0) {
            $errors[] = 'School ID or Learner Reference Number already exists.';
        } else {
            // Handle file upload (PDF is now required)
            $upload_dir = 'uploads/student_documents/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['attachment_file']['name'], PATHINFO_EXTENSION);
            $new_filename = $school_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['attachment_file']['tmp_name'], $upload_path)) {
                $attachment_file = $upload_path;
            } else {
                $errors[] = 'Failed to upload PDF file. Please try again.';
            }
            
            // Insert student record
            $stmt = $pdo->prepare("INSERT INTO students (first_name, middle_name, last_name, date_of_birth, gender, 
                                   province, municipality, barangay, street_purok, mobile_number, email, facebook_link, 
                                   school_id, learner_reference_number, attachment_file, guardian_name, guardian_mobile, 
                                   guardian_relationship) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$first_name, $middle_name, $last_name, $date_of_birth, $gender, 
                               $province, $municipality, $barangay, $street_purok, $mobile_number, $email, 
                               $facebook_link, $school_id, $learner_reference_number, $attachment_file, 
                               $guardian_name, $guardian_mobile, $guardian_relationship])) {
                header('Location: registration_status.php');
                exit();
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Dormitory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .registration-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        .registration-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .registration-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .section-header {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0 15px 0;
            border-left: 4px solid #667eea;
        }
        .required {
            color: #dc3545;
        }
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-container">
            <div class="registration-header">
                <h2><i class="fas fa-user-plus"></i> Student Registration</h2>
                <p class="mb-0">Apply for Dormitory Accommodation</p>
            </div>
            <div class="registration-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- Personal Information -->
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-user"></i> Personal Information</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="required">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="middle_name" class="form-label">Middle Name <span class="required">*</span></label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="required">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth <span class="required">*</span></label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender <span class="required">*</span></label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Address Information</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="province" class="form-label">Province <span class="required">*</span></label>
                            <input type="text" class="form-control" id="province" name="province" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="municipality" class="form-label">Municipality <span class="required">*</span></label>
                            <input type="text" class="form-control" id="municipality" name="municipality" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="barangay" class="form-label">Barangay <span class="required">*</span></label>
                            <input type="text" class="form-control" id="barangay" name="barangay" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="street_purok" class="form-label">Street/Purok <span class="required">*</span></label>
                            <input type="text" class="form-control" id="street_purok" name="street_purok" required>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-phone"></i> Contact Information</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mobile_number" class="form-label">Mobile Number <span class="required">*</span></label>
                            <input type="tel" class="form-control" id="mobile_number" name="mobile_number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="facebook_link" class="form-label">Facebook Profile Link <span class="required">*</span></label>
                        <input type="url" class="form-control" id="facebook_link" name="facebook_link" placeholder="https://facebook.com/yourprofile" required>
                    </div>

                    <!-- School Information -->
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-graduation-cap"></i> School Information</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="school_id" class="form-label">School ID Number <span class="required">*</span></label>
                            <input type="text" class="form-control" id="school_id" name="school_id" maxlength="6" placeholder="6-digit School ID" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="learner_reference_number" class="form-label">Learner Reference Number <span class="required">*</span></label>
                            <input type="text" class="form-control" id="learner_reference_number" name="learner_reference_number" maxlength="12" placeholder="12-digit LRN" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="attachment_file" class="form-label">Attachment File (PDF) <span class="required">*</span></label>
                        <input type="file" class="form-control" id="attachment_file" name="attachment_file" accept=".pdf" required>
                        <div class="form-text">Upload any supporting documents (PDF format only)</div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-user-friends"></i> Emergency Contact</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="guardian_name" class="form-label">Guardian/Parent Name <span class="required">*</span></label>
                            <input type="text" class="form-control" id="guardian_name" name="guardian_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="guardian_mobile" class="form-label">Guardian Mobile Number <span class="required">*</span></label>
                            <input type="tel" class="form-control" id="guardian_mobile" name="guardian_mobile" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="guardian_relationship" class="form-label">Relationship <span class="required">*</span></label>
                        <select class="form-control" id="guardian_relationship" name="guardian_relationship" required>
                            <option value="">Select Relationship</option>
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Guardian">Guardian</option>
                            <option value="Sibling">Sibling</option>
                            <option value="Relative">Relative</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="login.php" class="btn btn-secondary me-md-2">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                        <button type="submit" class="btn btn-primary btn-register">
                            <i class="fas fa-paper-plane"></i> Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format School ID input
        document.getElementById('school_id').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 6) value = value.substring(0, 6);
            e.target.value = value;
        });

        // Format LRN input
        document.getElementById('learner_reference_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 12) value = value.substring(0, 12);
            e.target.value = value;
        });

        // Format mobile numbers
        document.getElementById('mobile_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
        });

        document.getElementById('guardian_mobile').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
        });

        // Form validation before submission
        document.querySelector('form').addEventListener('submit', function(e) {
            let isValid = true;
            let errorMessage = '';

            // Check all required fields
            const requiredFields = [
                'first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender',
                'province', 'municipality', 'barangay', 'street_purok',
                'mobile_number', 'email', 'facebook_link',
                'school_id', 'learner_reference_number', 'attachment_file',
                'guardian_name', 'guardian_mobile', 'guardian_relationship'
            ];

            requiredFields.forEach(function(fieldName) {
                const field = document.getElementById(fieldName);
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    errorMessage += '• ' + field.previousElementSibling.textContent.replace(' *', '') + ' is required\n';
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            // Validate PDF file
            const fileInput = document.getElementById('attachment_file');
            if (fileInput.files.length === 0) {
                isValid = false;
                fileInput.classList.add('is-invalid');
                errorMessage += '• PDF attachment file is required\n';
            } else {
                const file = fileInput.files[0];
                if (file.type !== 'application/pdf') {
                    isValid = false;
                    fileInput.classList.add('is-invalid');
                    errorMessage += '• Only PDF files are allowed\n';
                } else if (file.size > 5 * 1024 * 1024) {
                    isValid = false;
                    fileInput.classList.add('is-invalid');
                    errorMessage += '• PDF file size must not exceed 5MB\n';
                } else {
                    fileInput.classList.remove('is-invalid');
                }
            }

            // Validate Facebook URL format
            const facebookField = document.getElementById('facebook_link');
            if (facebookField.value && !facebookField.value.includes('facebook.com')) {
                isValid = false;
                facebookField.classList.add('is-invalid');
                errorMessage += '• Please enter a valid Facebook profile link\n';
            }

            if (!isValid) {
                e.preventDefault();
                alert('Please correct the following errors:\n\n' + errorMessage);
            }
        });
    </script>
</body>
</html>