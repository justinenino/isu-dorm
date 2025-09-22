<?php
/**
 * Secure Email Test for Hostinger
 * This tests email with enhanced security headers
 */

require_once 'config/hostinger_email.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Secure Email Test</title></head><body>";
echo "<h2>🔒 Testing Secure Email with Enhanced Security</h2>";

// Test basic email with security headers
$test_email = 'manueljustine291@gmail.com';
echo "<p>Testing secure email to: <strong>$test_email</strong></p>";
echo "<p>Using enhanced security headers and authentication</p>";

echo "<hr>";

$result = testEmail($test_email);

if ($result) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✅ SUCCESS!</h3>";
    echo "<p>Secure email sent successfully!</p>";
    echo "<p><strong>Enhanced Security Features:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Message-ID authentication</li>";
    echo "<li>✅ X-Originating-IP tracking</li>";
    echo "<li>✅ X-Sender verification</li>";
    echo "<li>✅ X-Authentication-Results header</li>";
    echo "<li>✅ X-Report-Abuse contact</li>";
    echo "<li>✅ Security notice in email content</li>";
    echo "</ul>";
    echo "<p><strong>Check your Gmail inbox and spam folder for the secure test email.</strong></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ FAILED!</h3>";
    echo "<p>Secure email failed to send. Possible issues:</p>";
    echo "<ul>";
    echo "<li>Gmail App Password incorrect</li>";
    echo "<li>2-Factor Authentication not enabled</li>";
    echo "<li>Hostinger email restrictions</li>";
    echo "<li>SMTP configuration issues</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";

// Test student approval email with security
echo "<h3>Testing Secure Student Approval Email</h3>";
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
    echo "<p style='color: green;'>✅ Secure approval email sent successfully!</p>";
    echo "<p>This email includes:</p>";
    echo "<ul>";
    echo "<li>Security notice and verification</li>";
    echo "<li>Official university branding</li>";
    echo "<li>Enhanced authentication headers</li>";
    echo "<li>Abuse reporting contact</li>";
    echo "</ul>";
    echo "<p>Check your Gmail for the secure dormitory approval email.</p>";
} else {
    echo "<p style='color: red;'>❌ Secure approval email failed to send</p>";
}

echo "<hr>";
echo "<h3>🔒 Security Features Added:</h3>";
echo "<ul>";
echo "<li><strong>Message-ID:</strong> Unique identifier for each email</li>";
echo "<li><strong>X-Originating-IP:</strong> Server IP tracking</li>";
echo "<li><strong>X-Sender:</strong> Verified sender information</li>";
echo "<li><strong>X-Authentication-Results:</strong> Authentication status</li>";
echo "<li><strong>X-Report-Abuse:</strong> Abuse reporting contact</li>";
echo "<li><strong>Security Notice:</strong> Official verification in email content</li>";
echo "<li><strong>List-Unsubscribe:</strong> Proper unsubscribe mechanism</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>⚠️ Important:</strong> Delete this file after testing for security!</p>";
echo "<p><a href='admin/dashboard.php'>Go to Admin Dashboard</a> | <a href='index.php'>Go to Homepage</a></p>";
echo "</body></html>";
?>
