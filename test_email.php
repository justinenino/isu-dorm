<?php
/**
 * Email System Test for Hostinger
 * 
 * This file tests the email functionality after deployment
 * Delete this file after successful testing
 */

require_once 'config/hostinger_email.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Email System Test</title></head><body>";
echo "<h2>📧 Email System Test</h2>";

// Test email configuration
echo "<h3>Email Configuration Check</h3>";
echo "<p><strong>Email Enabled:</strong> " . (defined('EMAIL_ENABLED') && EMAIL_ENABLED ? '✅ Yes' : '❌ No') . "</p>";
echo "<p><strong>From Email:</strong> " . (defined('EMAIL_FROM_EMAIL') ? EMAIL_FROM_EMAIL : 'Not set') . "</p>";
echo "<p><strong>SMTP Host:</strong> " . (defined('EMAIL_SMTP_HOST') ? EMAIL_SMTP_HOST : 'Not set') . "</p>";
echo "<p><strong>SMTP Port:</strong> " . (defined('EMAIL_SMTP_PORT') ? EMAIL_SMTP_PORT : 'Not set') . "</p>";

echo "<hr>";

// Test basic email
echo "<h3>Basic Email Test</h3>";
$test_email = 'manueljustine291@gmail.com'; // Change this to your email
echo "<p>Testing email to: <strong>$test_email</strong></p>";

$result = testEmail($test_email);

if ($result) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✅ SUCCESS!</h3>";
    echo "<p>Email sent successfully!</p>";
    echo "<p>Check your Gmail inbox (and spam folder) for the test email.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ FAILED!</h3>";
    echo "<p>Email failed to send. This might be due to:</p>";
    echo "<ul>";
    echo "<li>Incorrect Gmail App Password</li>";
    echo "<li>2-Factor Authentication not enabled on Gmail</li>";
    echo "<li>Hostinger email restrictions</li>";
    echo "<li>SMTP configuration issues</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";

// Test approval email
echo "<h3>Student Approval Email Test</h3>";
$test_student = [
    'first_name' => 'Test',
    'last_name' => 'Student',
    'email' => $test_email
];

$test_room = [
    'building_name' => 'Test Building',
    'room_number' => '101',
    'bed_space_number' => 'A',
    'room_type' => 'Single'
];

$approval_result = sendStudentApprovalEmail($test_student, $test_room);

if ($approval_result) {
    echo "<p style='color: green;'>✅ Approval email sent successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Approval email failed to send</p>";
}

echo "<hr>";
echo "<p><strong>⚠️ Important:</strong> Delete this file after testing for security!</p>";
echo "<p><a href='admin/dashboard.php'>Go to Admin Dashboard</a> | <a href='index.php'>Go to Homepage</a></p>";
echo "</body></html>";
?>
