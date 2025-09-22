<?php
/**
 * Debug Email Configuration
 * This will show detailed information about email setup
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Email Debug</title></head><body>";
echo "<h2>🔍 Email Configuration Debug</h2>";

// Check if email config file exists
if (file_exists('config/hostinger_email.php')) {
    echo "<p style='color: green;'>✅ Email config file exists</p>";
    require_once 'config/hostinger_email.php';
} else {
    echo "<p style='color: red;'>❌ Email config file not found</p>";
    exit;
}

// Check email constants
echo "<h3>Email Configuration:</h3>";
echo "<p><strong>EMAIL_ENABLED:</strong> " . (defined('EMAIL_ENABLED') ? (EMAIL_ENABLED ? 'Yes' : 'No') : 'Not defined') . "</p>";
echo "<p><strong>EMAIL_FROM_EMAIL:</strong> " . (defined('EMAIL_FROM_EMAIL') ? EMAIL_FROM_EMAIL : 'Not defined') . "</p>";
echo "<p><strong>EMAIL_SMTP_HOST:</strong> " . (defined('EMAIL_SMTP_HOST') ? EMAIL_SMTP_HOST : 'Not defined') . "</p>";
echo "<p><strong>EMAIL_SMTP_PORT:</strong> " . (defined('EMAIL_SMTP_PORT') ? EMAIL_SMTP_PORT : 'Not defined') . "</p>";
echo "<p><strong>EMAIL_SMTP_USERNAME:</strong> " . (defined('EMAIL_SMTP_USERNAME') ? EMAIL_SMTP_USERNAME : 'Not defined') . "</p>";
echo "<p><strong>EMAIL_SMTP_PASSWORD:</strong> " . (defined('EMAIL_SMTP_PASSWORD') ? '***' . substr(EMAIL_SMTP_PASSWORD, -4) : 'Not defined') . "</p>";

echo "<hr>";

// Test mail function
echo "<h3>Mail Function Test:</h3>";
if (function_exists('mail')) {
    echo "<p style='color: green;'>✅ mail() function is available</p>";
} else {
    echo "<p style='color: red;'>❌ mail() function is not available</p>";
}

// Test SMTP settings
echo "<h3>SMTP Settings Test:</h3>";
ini_set('SMTP', 'smtp.gmail.com');
ini_set('smtp_port', '587');
ini_set('sendmail_from', 'dormitoryisue2025@gmail.com');

echo "<p><strong>SMTP Host:</strong> " . ini_get('SMTP') . "</p>";
echo "<p><strong>SMTP Port:</strong> " . ini_get('smtp_port') . "</p>";
echo "<p><strong>Sendmail From:</strong> " . ini_get('sendmail_from') . "</p>";

echo "<hr>";

// Test simple email
echo "<h3>Simple Email Test:</h3>";
$to = 'manueljustine291@gmail.com';
$subject = 'Test Email from ISU Dormitory';
$message = 'This is a test email to verify the email system is working.';
$headers = 'From: dormitoryisue2025@gmail.com' . "\r\n" .
           'Reply-To: dormitoryisue2025@gmail.com' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

echo "<p>Sending test email to: <strong>$to</strong></p>";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "<p style='color: green;'>✅ Basic mail() function returned TRUE</p>";
    echo "<p>Check your Gmail inbox (and spam folder) for the test email.</p>";
} else {
    echo "<p style='color: red;'>❌ Basic mail() function returned FALSE</p>";
    echo "<p>This might be due to server configuration issues.</p>";
}

echo "<hr>";
echo "<p><strong>⚠️ Important:</strong> Delete this file after testing for security!</p>";
echo "</body></html>";
?>
