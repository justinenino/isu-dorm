<?php
/**
 * Gmail SMTP Setup Script for XAMPP
 * 
 * This script helps configure XAMPP to use Gmail SMTP
 * Run this script to set up Gmail email functionality
 */

echo "<h1>Gmail SMTP Setup for Dormitory Management System</h1>";

// Check if we're running on XAMPP
$is_xampp = (strpos($_SERVER['DOCUMENT_ROOT'], 'xampp') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false);

if (!$is_xampp) {
    echo "<p style='color: orange;'>⚠️ This script is designed for XAMPP. You may need to adjust paths for other environments.</p>";
}

echo "<h2>Step 1: Gmail App Password Setup</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔐 Create Gmail App Password</h3>";
echo "<ol>";
echo "<li>Go to your <a href='https://myaccount.google.com/' target='_blank'>Google Account</a></li>";
echo "<li>Navigate to <strong>Security</strong> → <strong>2-Step Verification</strong></li>";
echo "<li>Enable 2-Step Verification if not already enabled</li>";
echo "<li>Go to <strong>Security</strong> → <strong>App passwords</strong></li>";
echo "<li>Select <strong>'Mail'</strong> as the app</li>";
echo "<li>Generate a 16-character app password</li>";
echo "<li>Copy the generated password (you'll need it below)</li>";
echo "</ol>";
echo "</div>";

echo "<h2>Step 2: Configure Email Settings</h2>";

if (isset($_POST['configure'])) {
    $gmail_email = $_POST['gmail_email'];
    $app_password = $_POST['app_password'];
    
    if (empty($gmail_email) || empty($app_password)) {
        echo "<p style='color: red;'>❌ Please fill in both Gmail address and app password.</p>";
    } else {
        // Update the Gmail email configuration
        $config_content = file_get_contents('config/gmail_email.php');
        $config_content = str_replace('your-email@gmail.com', $gmail_email, $config_content);
        $config_content = str_replace('your-app-password', $app_password, $config_content);
        
        if (file_put_contents('config/gmail_email.php', $config_content)) {
            echo "<p style='color: green;'>✅ Gmail configuration updated successfully!</p>";
            
            // Update reservation management to use Gmail config
            $reservation_file = 'admin/reservation_management.php';
            $reservation_content = file_get_contents($reservation_file);
            $reservation_content = str_replace('require_once \'../config/simple_email.php\';', 'require_once \'../config/gmail_email.php\';', $reservation_content);
            file_put_contents($reservation_file, $reservation_content);
            
            echo "<p style='color: green;'>✅ System updated to use Gmail SMTP!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update configuration. Please check file permissions.</p>";
        }
    }
}

?>

<form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3>📧 Gmail Configuration</h3>
    <div style="margin: 15px 0;">
        <label for="gmail_email" style="display: block; margin-bottom: 5px; font-weight: bold;">Gmail Address:</label>
        <input type="email" id="gmail_email" name="gmail_email" placeholder="your-email@gmail.com" 
               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
    </div>
    
    <div style="margin: 15px 0;">
        <label for="app_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Gmail App Password:</label>
        <input type="password" id="app_password" name="app_password" placeholder="16-character app password" 
               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
        <small style="color: #666;">The 16-character password you generated in Step 1</small>
    </div>
    
    <button type="submit" name="configure" style="background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
        🔧 Configure Gmail SMTP
    </button>
</form>

<?php if (isset($_POST['configure']) && !empty($_POST['gmail_email']) && !empty($_POST['app_password'])): ?>
    <h2>Step 3: Configure XAMPP SMTP Settings</h2>
    <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;">
        <h3>⚠️ Manual Configuration Required</h3>
        <p>You need to manually configure XAMPP's SMTP settings for Gmail to work properly.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3>📝 XAMPP Configuration Steps:</h3>
        <ol>
            <li><strong>Open php.ini file:</strong> <code>C:\xampp\php\php.ini</code></li>
            <li><strong>Find the [mail function] section</strong> and update:</li>
        </ol>
        
        <div style="background: #e9ecef; padding: 15px; border-radius: 4px; margin: 10px 0;">
            <pre style="margin: 0; font-family: monospace;">
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = <?php echo $_POST['gmail_email']; ?>
sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
            </pre>
        </div>
        
        <ol start="3">
            <li><strong>Open sendmail.ini file:</strong> <code>C:\xampp\sendmail\sendmail.ini</code></li>
            <li><strong>Update the configuration:</strong></li>
        </ol>
        
        <div style="background: #e9ecef; padding: 15px; border-radius: 4px; margin: 10px 0;">
            <pre style="margin: 0; font-family: monospace;">
[sendmail]
smtp_server=smtp.gmail.com
smtp_port=587
auth_username=<?php echo $_POST['gmail_email']; ?>
auth_password=<?php echo $_POST['app_password']; ?>
force_sender=<?php echo $_POST['gmail_email']; ?>
            </pre>
        </div>
        
        <ol start="5">
            <li><strong>Restart XAMPP</strong> (Apache and MySQL)</li>
            <li><strong>Test the email system</strong> using the test script</li>
        </ol>
    </div>
    
    <h2>Step 4: Test Email Functionality</h2>
    <div style="background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;">
        <h3>✅ Ready to Test!</h3>
        <p>After completing the XAMPP configuration above, test your email system:</p>
        <p><a href="test_email.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">🧪 Test Email System</a></p>
    </div>
<?php endif; ?>

<h2>Step 5: Alternative - Use PHPMailer (Advanced)</h2>
<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <p>For more advanced email features, you can install PHPMailer:</p>
    <ol>
        <li>Download PHPMailer from <a href="https://github.com/PHPMailer/PHPMailer" target="_blank">GitHub</a></li>
        <li>Extract to <code>vendor/phpmailer/phpmailer/</code> directory</li>
        <li>Use the original <code>config/email.php</code> configuration</li>
    </ol>
</div>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
code { background: #e9ecef; padding: 2px 4px; border-radius: 3px; }
pre { background: #e9ecef; padding: 15px; border-radius: 4px; overflow-x: auto; }
</style>
