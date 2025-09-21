<?php
/**
 * Email Test Script
 * 
 * This script tests the email functionality
 * Run this to verify email configuration is working
 */

require_once 'config/simple_email.php';

// Test email configuration
echo "<h2>Email Configuration Test</h2>";

// Check if email is enabled
if (!EMAIL_ENABLED) {
    echo "<p style='color: red;'>❌ Email is disabled. Please enable it in config/email.php</p>";
    exit;
}

echo "<p style='color: green;'>✅ Email is enabled</p>";

// Check PHP mail function
if (!function_exists('mail')) {
    echo "<p style='color: red;'>❌ PHP mail() function is not available. Check your server configuration.</p>";
    exit;
}

echo "<p style='color: green;'>✅ PHP mail() function is available</p>";

// Test email configuration
$test_email = 'test@example.com'; // Change this to your email for testing
$subject = 'Dormitory System - Email Test';
$body = '<h1>Email Test Successful!</h1><p>This is a test email from the Dormitory Management System.</p>';

echo "<h3>Testing Email Send Function...</h3>";

if (isset($_GET['send_test'])) {
    $result = sendEmail($test_email, $subject, $body, 'Email Test Successful! This is a test email from the Dormitory Management System.');
    
    if ($result) {
        echo "<p style='color: green;'>✅ Test email sent successfully to $test_email</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to send test email. Check server logs for details.</p>";
    }
} else {
    echo "<p><a href='?send_test=1' class='btn btn-primary'>Send Test Email</a></p>";
}

// Display current configuration
echo "<h3>Current Email Configuration:</h3>";
echo "<ul>";
echo "<li><strong>From Email:</strong> " . EMAIL_FROM_EMAIL . "</li>";
echo "<li><strong>From Name:</strong> " . EMAIL_FROM_NAME . "</li>";
echo "<li><strong>Reply To:</strong> " . EMAIL_REPLY_TO . "</li>";
echo "<li><strong>Method:</strong> PHP mail() function</li>";
echo "</ul>";

echo "<h3>Setup Instructions:</h3>";
echo "<ol>";
echo "<li>Configure your server's mail settings (SMTP server, etc.)</li>";
echo "<li>Update EMAIL_FROM_EMAIL and EMAIL_FROM_NAME in config/simple_email.php if needed</li>";
echo "<li>Test the email functionality using this script</li>";
echo "<li>For production, consider using a proper SMTP service</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> Change the test email address in this script to your actual email for testing.</p>";
echo "<p><strong>XAMPP Users:</strong> You may need to configure SMTP settings in php.ini for emails to work properly.</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.btn { 
    background: #007bff; 
    color: white; 
    padding: 10px 20px; 
    text-decoration: none; 
    border-radius: 5px; 
    display: inline-block; 
}
.btn:hover { background: #0056b3; }
</style>
