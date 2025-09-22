<?php
/**
 * Test Real Email Button
 * This script sends a real email to test the button functionality
 */

echo "<h2>📧 Real Email Button Test</h2>";
echo "<p>This will send a real email to test the button functionality.</p>";
echo "<hr>";

// Load email configuration
require_once 'config/hostinger_email.php';

// Test email address - CHANGE THIS TO YOUR EMAIL
$test_email = 'manueljustine291@gmail.com'; // Replace with your test email

echo "<h3>1. Email Configuration Check</h3>";
echo "EMAIL_ENABLED: " . (defined('EMAIL_ENABLED') && EMAIL_ENABLED ? 'true' : 'false') . "<br>";
echo "EMAIL_FROM_EMAIL: " . (defined('EMAIL_FROM_EMAIL') ? EMAIL_FROM_EMAIL : 'N/A') . "<br>";
echo "EMAIL_SMTP_HOST: " . (defined('EMAIL_SMTP_HOST') ? EMAIL_SMTP_HOST : 'N/A') . "<br>";
echo "Test email address: <strong>" . htmlspecialchars($test_email) . "</strong><br>";
echo "<hr>";

echo "<h3>2. Environment Detection</h3>";
$is_hostinger = (isset($_SERVER['HTTP_HOST']) && 
                (strpos($_SERVER['HTTP_HOST'], 'hostinger') !== false || 
                 strpos($_SERVER['HTTP_HOST'], '.com') !== false));

if ($is_hostinger) {
    echo "✅ <strong>HOSTINGER DETECTED</strong> - Will send real email<br>";
} else {
    echo "⚠️ <strong>LOCAL XAMPP DETECTED</strong> - Will simulate email<br>";
    echo "For real email testing, run this on Hostinger<br>";
}
echo "<hr>";

echo "<h3>3. Sending Test Email</h3>";
echo "Attempting to send email to: <strong>" . htmlspecialchars($test_email) . "</strong><br>";

// Test data
$test_student = [
    'first_name' => 'Test',
    'last_name' => 'Student',
    'email' => $test_email
];
$test_room = [
    'building_name' => 'ISU Dormitory Building A',
    'room_number' => '101',
    'bed_space_number' => 'Bed 1',
    'room_type' => 'Standard'
];

// Send approval email
if (function_exists('sendStudentApprovalEmail')) {
    echo "✅ sendStudentApprovalEmail function found<br>";
    
    $result = sendStudentApprovalEmail($test_student, $test_room);
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ <strong>Email sent successfully!</strong></p>";
        echo "<p>Please check your email inbox and spam folder for the test email.</p>";
        echo "<p><strong>What to look for:</strong></p>";
        echo "<ul>";
        echo "<li>Blue button that says 'Access Student Dashboard'</li>";
        echo "<li>Fallback text link below the button</li>";
        echo "<li>Button should link to: https://skyblue-ibis-464501.hostingersite.com/login.php</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ <strong>Email failed to send</strong></p>";
        echo "<p>Check the error logs for more details.</p>";
    }
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ sendStudentApprovalEmail function not found</p>";
}
echo "<hr>";

echo "<h3>4. Email Preview (HTML)</h3>";
if (function_exists('getApprovalEmailTemplate')) {
    $html_template = getApprovalEmailTemplate($test_student, $test_room);
    echo "<div style='border: 2px solid #007bff; padding: 20px; background: white; max-width: 600px; margin: 0 auto;'>";
    echo $html_template;
    echo "</div>";
}
echo "<hr>";

echo "<h3>5. Instructions for Testing</h3>";
echo "<p><strong>After receiving the email:</strong></p>";
echo "<ol>";
echo "<li>Check your inbox for an email from 'ISU Dormitory Management'</li>";
echo "<li>If not in inbox, check spam folder</li>";
echo "<li>Look for the blue 'Access Student Dashboard' button</li>";
echo "<li>Click the button to test if it works</li>";
echo "<li>If button doesn't work, try the fallback text link</li>";
echo "<li>Report back if the button works or not</li>";
echo "</ol>";
echo "<hr>";

echo "<p><strong>✅ Real email test completed!</strong></p>";
echo "<p><strong>⚠️ Important:</strong> Delete this file after testing for security!</p>";
echo "<p><a href='admin/dashboard.php'>Go to Admin Dashboard</a> | <a href='index.php'>Go to Homepage</a></p>";
?>
