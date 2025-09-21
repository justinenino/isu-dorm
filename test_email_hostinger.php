<?php
// Test Email Functionality on Hostinger
// Run this script to test if email notifications work

// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Hostinger Email Test</h1>";
echo "<p>Testing email functionality for student approval notifications...</p>";

// Include email configuration
include 'config/email_hostinger.php';

// Test 1: Check if email is enabled
echo "<h2>1. Email Configuration Test</h2>";
if (defined('EMAIL_ENABLED') && EMAIL_ENABLED) {
    echo "<p style='color: green;'>✅ Email is enabled</p>";
} else {
    echo "<p style='color: red;'>❌ Email is disabled</p>";
}

// Test 2: Check SMTP configuration
echo "<h2>2. SMTP Configuration Test</h2>";
echo "<p>SMTP Host: " . (defined('EMAIL_SMTP_HOST') ? EMAIL_SMTP_HOST : 'Not defined') . "</p>";
echo "<p>SMTP Port: " . (defined('EMAIL_SMTP_PORT') ? EMAIL_SMTP_PORT : 'Not defined') . "</p>";
echo "<p>SMTP Username: " . (defined('EMAIL_SMTP_USERNAME') ? EMAIL_SMTP_USERNAME : 'Not defined') . "</p>";
echo "<p>SMTP Password: " . (defined('EMAIL_SMTP_PASSWORD') ? '***hidden***' : 'Not defined') . "</p>";

// Test 3: Check PHP mail function
echo "<h2>3. PHP Mail Function Test</h2>";
if (function_exists('mail')) {
    echo "<p style='color: green;'>✅ mail() function is available</p>";
} else {
    echo "<p style='color: red;'>❌ mail() function is not available</p>";
}

// Test 4: Check PHPMailer availability
echo "<h2>4. PHPMailer Test</h2>";
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<p style='color: green;'>✅ PHPMailer is available</p>";
} else {
    echo "<p style='color: orange;'>⚠️ PHPMailer is not available (will use mail() function)</p>";
}

// Test 5: Test email sending
echo "<h2>5. Email Sending Test</h2>";

if (isset($_POST['test_email'])) {
    $test_email = $_POST['test_email'];
    
    if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        echo "<p>Attempting to send test email to: $test_email</p>";
        
        $result = testEmailHostinger($test_email);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Test email sent successfully!</p>";
            echo "<p>Please check your inbox (and spam folder) for the test email.</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to send test email</p>";
            echo "<p>Check the error logs for more details.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid email address</p>";
    }
}

// Test 6: Test student approval email
echo "<h2>6. Student Approval Email Test</h2>";

if (isset($_POST['test_approval_email'])) {
    $test_email = $_POST['test_approval_email'];
    
    if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        echo "<p>Testing student approval email to: $test_email</p>";
        
        // Create test data
        $test_student = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $test_email,
            'school_id' => 'TEST123'
        ];
        
        $test_room = [
            'building_name' => 'Building A',
            'room_number' => '101',
            'bed_space_number' => '1',
            'room_type' => 'Double'
        ];
        
        $result = sendStudentApprovalEmailHostinger($test_student, $test_room);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Student approval email sent successfully!</p>";
            echo "<p>Please check your inbox for the approval email.</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to send student approval email</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid email address</p>";
    }
}

// Test forms
echo "<h2>Test Email Forms</h2>";

echo "<form method='POST' style='margin: 20px 0; padding: 20px; border: 1px solid #ccc; border-radius: 5px;'>";
echo "<h3>Send Test Email</h3>";
echo "<p>Enter an email address to send a test email:</p>";
echo "<input type='email' name='test_email' placeholder='test@example.com' required style='padding: 8px; width: 300px; margin-right: 10px;'>";
echo "<button type='submit' style='padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 3px;'>Send Test Email</button>";
echo "</form>";

echo "<form method='POST' style='margin: 20px 0; padding: 20px; border: 1px solid #ccc; border-radius: 5px;'>";
echo "<h3>Send Student Approval Email Test</h3>";
echo "<p>Enter an email address to test the student approval email template:</p>";
echo "<input type='email' name='test_approval_email' placeholder='student@example.com' required style='padding: 8px; width: 300px; margin-right: 10px;'>";
echo "<button type='submit' style='padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 3px;'>Send Approval Email Test</button>";
echo "</form>";

// Test 7: Check server configuration
echo "<h2>7. Server Configuration</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Operating System: " . php_uname() . "</p>";

// Check sendmail configuration
$sendmail_path = ini_get('sendmail_path');
echo "<p>Sendmail Path: " . ($sendmail_path ? $sendmail_path : 'Not configured') . "</p>";

// Check SMTP configuration
$smtp = ini_get('SMTP');
$smtp_port = ini_get('smtp_port');
echo "<p>SMTP: " . ($smtp ? $smtp : 'Not configured') . "</p>";
echo "<p>SMTP Port: " . ($smtp_port ? $smtp_port : 'Not configured') . "</p>";

// Test 8: Error log check
echo "<h2>8. Recent Error Logs</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $recent_errors = file_get_contents($error_log);
    $error_lines = explode("\n", $recent_errors);
    $recent_lines = array_slice($error_lines, -10); // Last 10 lines
    
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px; font-size: 12px;'>";
    foreach ($recent_lines as $line) {
        if (!empty(trim($line))) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>Error log not found or not configured</p>";
}

echo "<h2>Test Complete</h2>";
echo "<p>If you see any red ❌ errors above, please fix them before using email notifications.</p>";
echo "<p>If all tests pass, email notifications should work on Hostinger!</p>";

// Instructions
echo "<h2>Instructions for Hostinger</h2>";
echo "<ol>";
echo "<li><strong>Update Gmail Credentials:</strong> Edit config/email_hostinger.php and update EMAIL_SMTP_USERNAME and EMAIL_SMTP_PASSWORD with your Gmail credentials</li>";
echo "<li><strong>Enable Gmail App Password:</strong> Go to Google Account Security → App passwords → Generate password for Mail</li>";
echo "<li><strong>Test Email:</strong> Use the forms above to test email functionality</li>";
echo "<li><strong>Update Admin Code:</strong> Replace sendStudentApprovalEmail() calls with sendStudentApprovalEmailHostinger()</li>";
echo "</ol>";
?>
