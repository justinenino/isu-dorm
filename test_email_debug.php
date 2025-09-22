<?php
/**
 * Email Debug Test
 * Check why emails are not sending
 */

echo "<h2>🔍 Email Debug Test</h2>";
echo "<p><strong>⚠️ Important:</strong> This test shows why emails don't work on local XAMPP but will work on Hostinger!</p>";

// Test 1: Check if email config loads
echo "<h3>1. Testing Email Configuration</h3>";
try {
    require_once 'config/hostinger_email.php';
    echo "✅ Email configuration loaded successfully<br>";
    
    // Check constants
    echo "EMAIL_ENABLED: " . (defined('EMAIL_ENABLED') ? (EMAIL_ENABLED ? 'true' : 'false') : 'not defined') . "<br>";
    echo "EMAIL_FROM_EMAIL: " . (defined('EMAIL_FROM_EMAIL') ? EMAIL_FROM_EMAIL : 'not defined') . "<br>";
    echo "EMAIL_SMTP_HOST: " . (defined('EMAIL_SMTP_HOST') ? EMAIL_SMTP_HOST : 'not defined') . "<br>";
    echo "EMAIL_SMTP_PORT: " . (defined('EMAIL_SMTP_PORT') ? EMAIL_SMTP_PORT : 'not defined') . "<br>";
    echo "EMAIL_SMTP_USERNAME: " . (defined('EMAIL_SMTP_USERNAME') ? EMAIL_SMTP_USERNAME : 'not defined') . "<br>";
    echo "EMAIL_SMTP_PASSWORD: " . (defined('EMAIL_SMTP_PASSWORD') ? '***hidden***' : 'not defined') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error loading email config: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 2: Check PHP mail function
echo "<h3>2. Testing PHP Mail Function</h3>";
if (function_exists('mail')) {
    echo "✅ PHP mail() function is available<br>";
} else {
    echo "❌ PHP mail() function is NOT available<br>";
}

// Check mail configuration
echo "SMTP: " . ini_get('SMTP') . "<br>";
echo "smtp_port: " . ini_get('smtp_port') . "<br>";
echo "sendmail_from: " . ini_get('sendmail_from') . "<br>";

echo "<hr>";

// Test 3: Test basic email sending
echo "<h3>3. Testing Basic Email Sending</h3>";
$test_email = 'manueljustine291@gmail.com';
echo "Testing email to: <strong>$test_email</strong><br>";

if (function_exists('sendEmail')) {
    echo "✅ sendEmail function is available<br>";
    
    $result = sendEmail(
        $test_email,
        'Test Email - Debug',
        'This is a test email to check if the system is working.',
        'This is a test email to check if the system is working.'
    );
    
    if ($result) {
        echo "✅ Email sent successfully!<br>";
        echo "Check your Gmail inbox and spam folder.<br>";
    } else {
        echo "❌ Email failed to send<br>";
        echo "Check the error logs for more details.<br>";
    }
} else {
    echo "❌ sendEmail function is NOT available<br>";
}

echo "<hr>";

// Test 4: Check error logs
echo "<h3>4. Recent Error Logs</h3>";
$error_log = error_get_last();
if ($error_log) {
    echo "Last error: " . $error_log['message'] . "<br>";
    echo "File: " . $error_log['file'] . "<br>";
    echo "Line: " . $error_log['line'] . "<br>";
} else {
    echo "No recent PHP errors<br>";
}

echo "<hr>";

// Test 5: Check server environment
echo "<h3>5. Server Environment</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown') . "<br>";
echo "OS: " . php_uname() . "<br>";
echo "Current working directory: " . getcwd() . "<br>";

echo "<hr>";

// Test 6: Check if we're on Hostinger or local
echo "<h3>6. Environment Detection</h3>";
$is_hostinger = (isset($_SERVER['HTTP_HOST']) && 
                (strpos($_SERVER['HTTP_HOST'], 'hostinger') !== false || 
                 strpos($_SERVER['HTTP_HOST'], '.com') !== false));

if ($is_hostinger) {
    echo "✅ <strong>HOSTINGER DETECTED</strong> - Emails will be sent via Gmail SMTP<br>";
    echo "This is the production environment where emails will work!<br>";
} else {
    echo "⚠️ <strong>LOCAL XAMPP DETECTED</strong> - Emails will be simulated only<br>";
    echo "Gmail SMTP requires STARTTLS which XAMPP doesn't support<br>";
    echo "Emails will work when deployed to Hostinger!<br>";
}

echo "<hr>";

// Test 7: Test student approval email
echo "<h3>7. Testing Student Approval Email</h3>";
if (function_exists('sendStudentApprovalEmail')) {
    echo "✅ sendStudentApprovalEmail function is available<br>";
    
    $test_student = [
        'first_name' => 'Manuel',
        'last_name' => 'Justine',
        'email' => $test_email
    ];
    
    $test_room = [
        'building_name' => 'ISU Dormitory Building A',
        'room_number' => '205',
        'bed_space_number' => 'B',
        'room_type' => 'Double'
    ];
    
    $approval_result = sendStudentApprovalEmail($test_student, $test_room);
    
    if ($approval_result) {
        echo "✅ Student approval email sent successfully!<br>";
    } else {
        echo "❌ Student approval email failed to send<br>";
    }
} else {
    echo "❌ sendStudentApprovalEmail function is NOT available<br>";
}

echo "<hr>";
echo "<p><strong>⚠️ Important:</strong> Delete this file after testing for security!</p>";
echo "<p><a href='admin/dashboard.php'>Go to Admin Dashboard</a> | <a href='index.php'>Go to Homepage</a></p>";
?>
