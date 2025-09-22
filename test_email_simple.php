<?php
/**
 * Simple Email Test for Hostinger
 * This will test if emails are actually being sent
 */

require_once 'config/hostinger_email.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Email Test</title></head><body>";
echo "<h2>📧 Testing Email with New App Password</h2>";

// Test basic email
$test_email = 'manueljustine291@gmail.com'; // Your email
echo "<p>Testing email to: <strong>$test_email</strong></p>";
echo "<p>Using Gmail: <strong>dormitoryisue2025@gmail.com</strong></p>";
echo "<p>App Password: <strong>dxpm ekie zguc yliy</strong></p>";

echo "<hr>";

$result = testEmail($test_email);

if ($result) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✅ SUCCESS!</h3>";
    echo "<p>Email sent successfully!</p>";
    echo "<p><strong>Check your Gmail inbox and spam folder for the test email.</strong></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ FAILED!</h3>";
    echo "<p>Email failed to send. Possible issues:</p>";
    echo "<ul>";
    echo "<li>Gmail App Password incorrect</li>";
    echo "<li>2-Factor Authentication not enabled</li>";
    echo "<li>Hostinger email restrictions</li>";
    echo "<li>SMTP configuration issues</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";

// Test student approval email
echo "<h3>Testing Student Approval Email</h3>";
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
    echo "<p style='color: green;'>✅ Approval email sent successfully!</p>";
    echo "<p>Check your Gmail for the dormitory approval email.</p>";
} else {
    echo "<p style='color: red;'>❌ Approval email failed to send</p>";
}

echo "<hr>";
echo "<p><strong>⚠️ Important:</strong> Delete this file after testing for security!</p>";
echo "<p><a href='admin/dashboard.php'>Go to Admin Dashboard</a> | <a href='index.php'>Go to Homepage</a></p>";
echo "</body></html>";
?>
