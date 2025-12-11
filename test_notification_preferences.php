<?php
/**
 * Notification Preferences Feature - Test & Verification Script
 * 
 * This script verifies that all components of the notification preferences feature are working correctly.
 * Access it at: http://localhost/cartmate/test_notification_preferences.php
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';

$test_results = [];
$database = new Database();
$conn = $database->getConnection();

// ============================================
// TEST 1: Database Table Exists
// ============================================
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'user_notification_preferences'");
    if ($stmt->rowCount() > 0) {
        $test_results['database_table'] = ['status' => 'PASS', 'message' => 'user_notification_preferences table exists'];
    } else {
        $test_results['database_table'] = ['status' => 'FAIL', 'message' => 'user_notification_preferences table does NOT exist'];
    }
} catch (Exception $e) {
    $test_results['database_table'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
}

// ============================================
// TEST 2: Table Structure
// ============================================
try {
    $stmt = $conn->query("DESC user_notification_preferences");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $required_columns = [
        'preference_id', 'user_id', 'new_products', 'featured_products', 
        'sales_promotions', 'important_news', 'order_updates', 'newsletter', 'product_reviews'
    ];
    
    $column_names = array_map(function($col) { return $col['Field']; }, $columns);
    $missing = array_diff($required_columns, $column_names);
    
    if (empty($missing)) {
        $test_results['table_structure'] = ['status' => 'PASS', 'message' => 'All required columns exist'];
    } else {
        $test_results['table_structure'] = ['status' => 'FAIL', 'message' => 'Missing columns: ' . implode(', ', $missing)];
    }
} catch (Exception $e) {
    $test_results['table_structure'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
}

// ============================================
// TEST 3: Files Exist
// ============================================
$files_to_check = [
    'ajax/preferences.php' => 'AJAX Preferences Handler',
    'database/init_notification_preferences.php' => 'Database Initialization Script',
    'database/notification_preferences_table.sql' => 'SQL Schema File',
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        $test_results['file_' . basename($file)] = ['status' => 'PASS', 'message' => "$description exists"];
    } else {
        $test_results['file_' . basename($file)] = ['status' => 'FAIL', 'message' => "$description NOT FOUND at $file"];
    }
}

// ============================================
// TEST 4: Newsletter Subscribers Table Exists
// ============================================
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'newsletter_subscribers'");
    if ($stmt->rowCount() > 0) {
        $test_results['newsletter_table'] = ['status' => 'PASS', 'message' => 'newsletter_subscribers table exists'];
    } else {
        $test_results['newsletter_table'] = ['status' => 'FAIL', 'message' => 'newsletter_subscribers table NOT FOUND (required for newsletter sync)'];
    }
} catch (Exception $e) {
    $test_results['newsletter_table'] = ['status' => 'WARN', 'message' => 'Could not verify newsletter_subscribers table: ' . $e->getMessage()];
}

// ============================================
// TEST 5: Sample Data (if user logged in)
// ============================================
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_notification_preferences WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $test_results['sample_data'] = ['status' => 'PASS', 'message' => "Preferences exist for user ID $user_id"];
        } else {
            $test_results['sample_data'] = ['status' => 'INFO', 'message' => "No preferences found for user ID $user_id (will be created on first load)"];
        }
    } catch (Exception $e) {
        $test_results['sample_data'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
    }
} else {
    $test_results['sample_data'] = ['status' => 'INFO', 'message' => 'Please log in to test user preferences'];
}

// ============================================
// TEST 6: AJAX Endpoint Accessibility
// ============================================
// This is a basic check - real test would be done in browser
if (file_exists('ajax/preferences.php')) {
    $content = file_get_contents('ajax/preferences.php');
    if (strpos($content, 'get_preferences') !== false) {
        $test_results['ajax_endpoint'] = ['status' => 'PASS', 'message' => 'AJAX endpoint contains get_preferences action'];
    } else {
        $test_results['ajax_endpoint'] = ['status' => 'FAIL', 'message' => 'AJAX endpoint missing get_preferences action'];
    }
} else {
    $test_results['ajax_endpoint'] = ['status' => 'FAIL', 'message' => 'ajax/preferences.php not found'];
}

// ============================================
// Determine Overall Status
// ============================================
$pass_count = 0;
$fail_count = 0;
$warn_count = 0;
$info_count = 0;

foreach ($test_results as $test) {
    switch ($test['status']) {
        case 'PASS': $pass_count++; break;
        case 'FAIL': $fail_count++; break;
        case 'WARN': $warn_count++; break;
        case 'INFO': $info_count++; break;
    }
}

$overall_status = ($fail_count > 0) ? 'SETUP INCOMPLETE' : (($warn_count > 0) ? 'SETUP WITH WARNINGS' : 'SETUP COMPLETE ✓');
$overall_color = ($fail_count > 0) ? '#dc2626' : (($warn_count > 0) ? '#ea580c' : '#059669');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Preferences - Verification Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            margin-top: 15px;
            color: white;
        }
        
        .status-badge.complete {
            background-color: #059669;
        }
        
        .status-badge.incomplete {
            background-color: #dc2626;
        }
        
        .status-badge.warnings {
            background-color: #ea580c;
        }
        
        .results {
            background: white;
            padding: 30px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .test-group {
            margin-bottom: 25px;
        }
        
        .test-group h2 {
            font-size: 18px;
            color: #333;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .test-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            background-color: #f9fafb;
            border-left: 4px solid #e5e7eb;
        }
        
        .test-item.pass {
            border-left-color: #10b981;
            background-color: #f0fdf4;
        }
        
        .test-item.fail {
            border-left-color: #ef4444;
            background-color: #fef2f2;
        }
        
        .test-item.warn {
            border-left-color: #f59e0b;
            background-color: #fffbeb;
        }
        
        .test-item.info {
            border-left-color: #3b82f6;
            background-color: #eff6ff;
        }
        
        .icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .icon.pass { background-color: #10b981; }
        .icon.fail { background-color: #ef4444; }
        .icon.warn { background-color: #f59e0b; }
        .icon.info { background-color: #3b82f6; }
        
        .test-content {
            flex: 1;
        }
        
        .test-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        
        .test-message {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .stat {
            text-align: center;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat.pass .stat-number { color: #10b981; }
        .stat.fail .stat-number { color: #ef4444; }
        .stat.warn .stat-number { color: #f59e0b; }
        .stat.info .stat-number { color: #3b82f6; }
        
        .next-steps {
            background-color: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .next-steps h3 {
            color: #059669;
            margin-bottom: 12px;
            font-size: 16px;
        }
        
        .next-steps ol {
            margin-left: 20px;
            color: #333;
            line-height: 1.8;
        }
        
        .next-steps li {
            margin-bottom: 8px;
        }
        
        .code {
            background-color: #1f2937;
            color: #e5e7eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Notification Preferences Feature - Verification Test</h1>
            <p>Testing the setup of the notification preferences and newsletter subscription feature</p>
            <div class="status-badge <?php 
                if ($fail_count > 0) {
                    echo 'incomplete';
                } elseif ($warn_count > 0) {
                    echo 'warnings';
                } else {
                    echo 'complete';
                }
            ?>">
                <?php echo $overall_status; ?>
            </div>
        </div>
        
        <div class="results">
            <!-- Statistics -->
            <div class="stats">
                <div class="stat pass">
                    <div class="stat-number"><?php echo $pass_count; ?></div>
                    <div class="stat-label">Passed</div>
                </div>
                <div class="stat fail">
                    <div class="stat-number"><?php echo $fail_count; ?></div>
                    <div class="stat-label">Failed</div>
                </div>
                <div class="stat warn">
                    <div class="stat-number"><?php echo $warn_count; ?></div>
                    <div class="stat-label">Warnings</div>
                </div>
                <div class="stat info">
                    <div class="stat-number"><?php echo $info_count; ?></div>
                    <div class="stat-label">Info</div>
                </div>
            </div>
            
            <!-- Database Tests -->
            <div class="test-group">
                <h2>Database Tests</h2>
                <?php foreach (['database_table', 'table_structure', 'newsletter_table'] as $key): ?>
                    <?php if (isset($test_results[$key])): 
                        $test = $test_results[$key];
                        $status_class = strtolower($test['status']);
                        ?>
                        <div class="test-item <?php echo $status_class; ?>">
                            <div class="icon <?php echo $status_class; ?>">
                                <?php 
                                    if ($status_class === 'pass') echo '✓';
                                    elseif ($status_class === 'fail') echo '✗';
                                    elseif ($status_class === 'warn') echo '⚠';
                                    else echo 'ℹ';
                                ?>
                            </div>
                            <div class="test-content">
                                <div class="test-title"><?php echo str_replace('_', ' ', ucfirst($key)); ?></div>
                                <div class="test-message"><?php echo htmlspecialchars($test['message']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- File Tests -->
            <div class="test-group">
                <h2>File Tests</h2>
                <?php foreach ($test_results as $key => $test): 
                    if (strpos($key, 'file_') === 0):
                        $status_class = strtolower($test['status']);
                        ?>
                        <div class="test-item <?php echo $status_class; ?>">
                            <div class="icon <?php echo $status_class; ?>">
                                <?php 
                                    if ($status_class === 'pass') echo '✓';
                                    elseif ($status_class === 'fail') echo '✗';
                                    elseif ($status_class === 'warn') echo '⚠';
                                    else echo 'ℹ';
                                ?>
                            </div>
                            <div class="test-content">
                                <div class="test-title"><?php echo str_replace('_', ' ', ucfirst(str_replace('file_', '', $key))); ?></div>
                                <div class="test-message"><?php echo htmlspecialchars($test['message']); ?></div>
                            </div>
                        </div>
                    <?php 
                    endif;
                endforeach; ?>
            </div>
            
            <!-- Integration Tests -->
            <div class="test-group">
                <h2>Integration Tests</h2>
                <?php foreach (['sample_data', 'ajax_endpoint'] as $key): ?>
                    <?php if (isset($test_results[$key])): 
                        $test = $test_results[$key];
                        $status_class = strtolower($test['status']);
                        ?>
                        <div class="test-item <?php echo $status_class; ?>">
                            <div class="icon <?php echo $status_class; ?>">
                                <?php 
                                    if ($status_class === 'pass') echo '✓';
                                    elseif ($status_class === 'fail') echo '✗';
                                    elseif ($status_class === 'warn') echo '⚠';
                                    else echo 'ℹ';
                                ?>
                            </div>
                            <div class="test-content">
                                <div class="test-title"><?php echo str_replace('_', ' ', ucfirst($key)); ?></div>
                                <div class="test-message"><?php echo htmlspecialchars($test['message']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Next Steps -->
            <?php if ($fail_count === 0): ?>
                <div class="next-steps">
                    <h3>✓ All Tests Passed! Next Steps:</h3>
                    <ol>
                        <li>Log in to your account at <span class="code">/account.php</span></li>
                        <li>Click on <strong>"Notifications & Preferences"</strong> in the left sidebar</li>
                        <li>Toggle any notification preference to test the feature</li>
                        <li>Check the database to verify changes were saved:
                            <pre style="background: #f3f4f6; padding: 10px; margin: 10px 0; border-radius: 4px; overflow-x: auto;">SELECT * FROM user_notification_preferences WHERE user_id = YOUR_USER_ID;</pre>
                        </li>
                        <li>Test newsletter subscription sync by enabling the newsletter toggle and checking <span class="code">newsletter_subscribers</span> table</li>
                    </ol>
                </div>
            <?php else: ?>
                <div class="next-steps" style="background-color: #fef2f2; border-color: #ef4444;">
                    <h3 style="color: #dc2626;">⚠ Setup Incomplete - Please Fix These Issues:</h3>
                    <ol>
                        <?php foreach ($test_results as $test): 
                            if ($test['status'] === 'FAIL'):
                                ?>
                                <li><?php echo htmlspecialchars($test['message']); ?></li>
                            <?php 
                            endif;
                        endforeach; ?>
                    </ol>
                    <p style="margin-top: 15px; color: #666; font-size: 13px;">
                        Visit <span class="code">/database/init_notification_preferences.php</span> to create the database table, or run the SQL manually in phpMyAdmin.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
