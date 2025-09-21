<?php
/**
 * XAMPP Gmail Configuration Helper
 * 
 * This script helps configure XAMPP to use Gmail SMTP
 */

echo "<h1>XAMPP Gmail Configuration Helper</h1>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>";
echo "<h3>⚠️ XAMPP Configuration Required</h3>";
echo "<p>XAMPP needs to be configured to use Gmail SMTP. Follow these steps:</p>";
echo "</div>";

echo "<h2>Step 1: Configure php.ini</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p><strong>File:</strong> <code>C:\\xampp\\php\\php.ini</code></p>";
echo "<p><strong>Find the [mail function] section and update:</strong></p>";
echo "<pre style='background: #e9ecef; padding: 15px; border-radius: 4px;'>";
echo "[mail function]\n";
echo "SMTP = smtp.gmail.com\n";
echo "smtp_port = 587\n";
echo "sendmail_from = dormitoryisue2025@gmail.com\n";
echo "sendmail_path = \"\\\"C:\\xampp\\sendmail\\sendmail.exe\\\" -t\"\n";
echo "</pre>";
echo "</div>";

echo "<h2>Step 2: Configure sendmail.ini</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p><strong>File:</strong> <code>C:\\xampp\\sendmail\\sendmail.ini</code></p>";
echo "<p><strong>Update the configuration:</strong></p>";
echo "<pre style='background: #e9ecef; padding: 15px; border-radius: 4px;'>";
echo "[sendmail]\n";
echo "smtp_server=smtp.gmail.com\n";
echo "smtp_port=587\n";
echo "auth_username=dormitoryisue2025@gmail.com\n";
echo "auth_password=wwtw ovek dzbt yawj\n";
echo "force_sender=dormitoryisue2025@gmail.com\n";
echo "</pre>";
echo "</div>";

echo "<h2>Step 3: Restart XAMPP</h2>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>";
echo "<h3>✅ Important Steps:</h3>";
echo "<ol>";
echo "<li>Stop Apache and MySQL in XAMPP Control Panel</li>";
echo "<li>Start Apache and MySQL again</li>";
echo "<li>Test the email functionality</li>";
echo "</ol>";
echo "</div>";

echo "<h2>Step 4: Test Email</h2>";
echo "<div style='background: #cce5ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p><strong>After configuring XAMPP, test your email:</strong></p>";
echo "<p><a href='send_test_email.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Send Test Email</a></p>";
echo "</div>";

echo "<h2>Alternative: Use Simple Email (No SMTP)</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p>If you prefer not to configure XAMPP SMTP, you can use the simple email configuration:</p>";
echo "<p><a href='test_email.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📧 Use Simple Email</a></p>";
echo "<p><small>Note: Simple email may have delivery limitations but works without SMTP configuration.</small></p>";
echo "</div>";

echo "<h2>Troubleshooting</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Common Issues:</h3>";
echo "<ul>";
echo "<li><strong>Permission denied:</strong> Run XAMPP as Administrator</li>";
echo "<li><strong>File not found:</strong> Check file paths are correct</li>";
echo "<li><strong>Authentication failed:</strong> Verify Gmail app password</li>";
echo "<li><strong>Connection timeout:</strong> Check firewall settings</li>";
echo "</ul>";
echo "</div>";

echo "<h2>Quick Configuration Files</h2>";
echo "<div style='background: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p><strong>I can create the configuration files for you:</strong></p>";
echo "<p><a href='?create_config=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📝 Create Config Files</a></p>";
echo "</div>";

if (isset($_GET['create_config'])) {
    echo "<h2>Configuration Files Created</h2>";
    
    // Create php.ini configuration
    $php_ini_config = "
; Gmail SMTP Configuration for Dormitory Management System
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = dormitoryisue2025@gmail.com
sendmail_path = \"\\\"C:\\xampp\\sendmail\\sendmail.exe\\\" -t\"
";
    
    // Create sendmail.ini configuration
    $sendmail_ini_config = "
; Gmail SMTP Configuration for Dormitory Management System
[sendmail]
smtp_server=smtp.gmail.com
smtp_port=587
auth_username=dormitoryisue2025@gmail.com
auth_password=wwtw ovek dzbt yawj
force_sender=dormitoryisue2025@gmail.com
";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ Configuration Files Ready</h3>";
    echo "<p>Copy these configurations to your XAMPP files:</p>";
    echo "</div>";
    
    echo "<h3>php.ini Configuration:</h3>";
    echo "<pre style='background: #e9ecef; padding: 15px; border-radius: 4px; overflow-x: auto;'>";
    echo htmlspecialchars($php_ini_config);
    echo "</pre>";
    
    echo "<h3>sendmail.ini Configuration:</h3>";
    echo "<pre style='background: #e9ecef; padding: 15px; border-radius: 4px; overflow-x: auto;'>";
    echo htmlspecialchars($sendmail_ini_config);
    echo "</pre>";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>⚠️ Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Copy the php.ini configuration to <code>C:\\xampp\\php\\php.ini</code></li>";
    echo "<li>Copy the sendmail.ini configuration to <code>C:\\xampp\\sendmail\\sendmail.ini</code></li>";
    echo "<li>Restart XAMPP (Apache and MySQL)</li>";
    echo "<li>Test the email functionality</li>";
    echo "</ol>";
    echo "</div>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
code { background: #e9ecef; padding: 2px 4px; border-radius: 3px; }
pre { background: #e9ecef; padding: 15px; border-radius: 4px; overflow-x: auto; }
</style>
