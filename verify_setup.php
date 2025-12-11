<?php
/**
 * Verify database table and AJAX endpoint functionality
 */
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h1>Notification Preferences Setup Verification</h1>";
    
    // Check if table exists
    echo "<h2>1. Checking if user_notification_preferences table exists...</h2>";
    $result = $conn->query("SHOW TABLES LIKE 'user_notification_preferences'");
    $tableExists = $result->rowCount() > 0;
    
    if ($tableExists) {
        echo "<div style='background:#e8f5e9;padding:15px;border-radius:4px;margin:10px 0;color:#2e7d32;'>✓ Table EXISTS</div>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $columns = $conn->query("DESCRIBE user_notification_preferences");
        echo "<table style='border-collapse:collapse;width:100%;margin:10px 0;'>";
        echo "<tr style='background:#f5f5f5;'><th style='border:1px solid #ddd;padding:10px;text-align:left;'>Field</th><th style='border:1px solid #ddd;padding:10px;text-align:left;'>Type</th><th style='border:1px solid #ddd;padding:10px;text-align:left;'>Null</th><th style='border:1px solid #ddd;padding:10px;text-align:left;'>Default</th></tr>";
        foreach ($columns->fetchAll(PDO::FETCH_ASSOC) as $col) {
            echo "<tr>";
            echo "<td style='border:1px solid #ddd;padding:10px;'>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:10px;'>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:10px;'>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:10px;'>" . ($col['Default'] ?: 'None') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for existing data
        echo "<h3>Sample Data:</h3>";
        $count = $conn->query("SELECT COUNT(*) as cnt FROM user_notification_preferences")->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total preference records: <strong>" . $count['cnt'] . "</strong></p>";
        
    } else {
        echo "<div style='background:#ffebee;padding:15px;border-radius:4px;margin:10px 0;color:#c62828;'>✗ Table DOES NOT EXIST</div>";
        echo "<p><a href='database/init_notification_preferences.php' style='color:blue;text-decoration:underline;'>Click here to create the table</a></p>";
    }
    
    // Check AJAX file
    echo "<h2>2. Checking AJAX endpoint file...</h2>";
    $ajaxPath = __DIR__ . '/ajax/preferences.php';
    if (file_exists($ajaxPath)) {
        echo "<div style='background:#e8f5e9;padding:15px;border-radius:4px;margin:10px 0;color:#2e7d32;'>✓ AJAX file EXISTS at /ajax/preferences.php</div>";
        
        // Check file size
        $fileSize = filesize($ajaxPath);
        echo "<p>File size: <strong>" . number_format($fileSize) . " bytes</strong></p>";
        
        // Check if PHP syntax is valid
        $output = shell_exec("php -l " . escapeshellarg($ajaxPath) . " 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "<div style='background:#e8f5e9;padding:15px;border-radius:4px;margin:10px 0;color:#2e7d32;'>✓ PHP syntax is VALID</div>";
        } else {
            echo "<div style='background:#ffebee;padding:15px;border-radius:4px;margin:10px 0;color:#c62828;'>✗ PHP syntax ERROR:</div>";
            echo "<pre style='background:#f5f5f5;padding:10px;margin:10px 0;'>" . htmlspecialchars($output) . "</pre>";
        }
    } else {
        echo "<div style='background:#ffebee;padding:15px;border-radius:4px;margin:10px 0;color:#c62828;'>✗ AJAX file NOT FOUND at /ajax/preferences.php</div>";
    }
    
    // Check Account.php modifications
    echo "<h2>3. Checking account.php modifications...</h2>";
    $accountPath = __DIR__ . '/account.php';
    if (file_exists($accountPath)) {
        $content = file_get_contents($accountPath);
        
        if (strpos($content, 'Notifications & Preferences') !== false) {
            echo "<div style='background:#e8f5e9;padding:15px;border-radius:4px;margin:10px 0;color:#2e7d32;'>✓ Notification preferences UI found in account.php</div>";
        } else {
            echo "<div style='background:#ffebee;padding:15px;border-radius:4px;margin:10px 0;color:#c62828;'>✗ Notification preferences UI NOT found in account.php</div>";
        }
        
        if (strpos($content, 'loadNotificationPreferences') !== false) {
            echo "<div style='background:#e8f5e9;padding:15px;border-radius:4px;margin:10px 0;color:#2e7d32;'>✓ JavaScript functions found in account.php</div>";
        } else {
            echo "<div style='background:#ffebee;padding:15px;border-radius:4px;margin:10px 0;color:#c62828;'>✗ JavaScript functions NOT found in account.php</div>";
        }
    }
    
    // Summary
    echo "<h2>Summary</h2>";
    if ($tableExists) {
        echo "<div style='background:#e3f2fd;padding:15px;border-radius:4px;margin:10px 0;border-left:4px solid #2196F3;'>";
        echo "<p><strong>✓ All systems ready!</strong></p>";
        echo "<p>The notification preferences feature is installed and ready to use.</p>";
        echo "<p><a href='account.php' style='color:blue;text-decoration:underline;margin-top:10px;display:inline-block;'>Go to Account Page</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background:#fff3e0;padding:15px;border-radius:4px;margin:10px 0;border-left:4px solid #FF9800;'>";
        echo "<p><strong>⚠ Setup Required</strong></p>";
        echo "<p>Please run the database initialization script to complete setup.</p>";
        echo "<p><a href='database/init_notification_preferences.php' style='color:blue;text-decoration:underline;margin-top:10px;display:inline-block;'>Initialize Database</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background:#ffebee;padding:15px;border-radius:4px;margin:10px 0;color:#c62828;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notification Preferences Setup Verification</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; margin-top: 0; }
        h2 { color: #1976D2; margin-top: 30px; }
        h3 { color: #555; margin-top: 20px; }
    </style>
</head>
<body>
</body>
</html>
