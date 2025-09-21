<?php
/**
 * Gmail Email Test Script
 * 
 * This script tests the Gmail email functionality
 * Run this to verify Gmail SMTP configuration is working
 */

require_once 'config/gmail_email.php';

// Test email configuration
echo "<h2>Gmail Email Configuration Test</h2>";

// Check if email is enabled
if (!EMAIL_ENABLED) {
    echo "<p style='color: red;'>❌ Email is disabled. Please enable it in config/gmail_email.php</p>";
    exit;
}

echo "<p style='color: green;'>✅ Email is enabled</p>";

// Check Gmail configuration
if (EMAIL_SMTP_USERNAME === 'your-email@gmail.com') {
    echo "<p style='color: orange;'>⚠️ Gmail configuration not set up. Please run <a href='setup_gmail.php'>setup_gmail.php</a> first.</p>";
    exit;
}

echo "<p style='color: green;'>✅ Gmail configuration found</p>";

// Check PHP mail function
if (!function_exists('mail')) {
    echo "<p style='color: red;'>❌ PHP mail() function is not available. Check your server configuration.</p>";
    exit;
}

echo "<p style='color: green;'>✅ PHP mail() function is available</p>";

// Test email configuration
$test_email = 'dormitoryisue2025@gmail.com'; // Your Gmail for testing
$subject = 'Dormitory System - Gmail Test';
$body = '<h1>Gmail Email Test Successful!</h1><p>This is a test email from the Dormitory Management System using Gmail SMTP.</p>';

echo "<h3>Testing Gmail Email Send Function...</h3>";

if (isset($_GET['send_test'])) {
    $result = sendEmail($test_email, $subject, $body, 'Gmail Email Test Successful! This is a test email from the Dormitory Management System using Gmail SMTP.');
    
    if ($result) {
        echo "<p style='color: green;'>✅ Test email sent successfully to $test_email via Gmail SMTP</p>";
        echo "<p style='color: blue;'>📧 Check your email inbox (and spam folder) for the test message.</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to send test email. Check server logs and XAMPP configuration.</p>";
        echo "<p style='color: orange;'>💡 Make sure you've configured XAMPP's sendmail settings as shown in setup_gmail.php</p>";
    }
} else {
    echo "<p><a href='?send_test=1' class='btn btn-primary'>Send Gmail Test Email</a></p>";
}

// Display current configuration
echo "<h3>Current Gmail Configuration:</h3>";
echo "<ul>";
echo "<li><strong>SMTP Host:</strong> " . EMAIL_SMTP_HOST . "</li>";
echo "<li><strong>SMTP Port:</strong> " . EMAIL_SMTP_PORT . "</li>";
echo "<li><strong>SMTP Username:</strong> " . EMAIL_SMTP_USERNAME . "</li>";
echo "<li><strong>SMTP Encryption:</strong> " . EMAIL_SMTP_ENCRYPTION . "</li>";
echo "<li><strong>From Email:</strong> " . EMAIL_FROM_EMAIL . "</li>";
echo "<li><strong>From Name:</strong> " . EMAIL_FROM_NAME . "</li>";
echo "<li><strong>Reply To:</strong> " . EMAIL_REPLY_TO . "</li>";
echo "</ul>";

echo "<h3>Setup Instructions:</h3>";
echo "<ol>";
echo "<li>Run <a href='setup_gmail.php'>setup_gmail.php</a> to configure Gmail credentials</li>";
echo "<li>Configure XAMPP's sendmail settings as shown in the setup script</li>";
echo "<li>Restart XAMPP (Apache and MySQL)</li>";
echo "<li>Test the email functionality using this script</li>";
echo "<li>Change the test email address in this script to your actual email</li>";
echo "</ol>";

echo "<h3>Troubleshooting:</h3>";
echo "<ul>";
echo "<li><strong>Emails not sending:</strong> Check XAMPP error logs and sendmail configuration</li>";
echo "<li><strong>Authentication failed:</strong> Verify Gmail app password is correct</li>";
echo "<li><strong>Connection timeout:</strong> Check firewall and network settings</li>";
echo "<li><strong>Emails in spam:</strong> This is normal for test emails, check spam folder</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> Change the test email address in this script to your actual email for testing.</p>";
echo "<p><strong>Gmail Users:</strong> Make sure you've enabled 2-Step Verification and generated an app password.</p>";
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
