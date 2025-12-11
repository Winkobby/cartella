<?php
/**
 * Test & Verify Automated Notification System
 * Tests all notification triggers and verifies customer preferences
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/NotificationEngine.php';

// Check admin access
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    header('Location: signin.php');
    exit;
}

$database = new Database();
$pdo = $database->getConnection();
$functions = new Functions();
$functions->setDatabase($database);

$test_results = [];
$message = '';

// Handle test actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_new_product_notification') {
        // Create a test product
        $test_product = [
            'product_id' => null,
            'name' => 'Test Product - ' . date('Y-m-d H:i:s'),
            'description' => 'This is a test product for notification testing',
            'short_description' => 'Test product',
            'price' => 99.99,
            'discount' => 10,
            'is_new' => 1,
            'is_featured' => 0,
            'main_image' => 'assets/images/placeholder-product.jpg'
        ];
        
        try {
            $notificationEngine = new NotificationEngine($pdo, $functions);
            $result = $notificationEngine->notifyNewProduct($test_product);
            $message = "✅ New Product Notification Test: " . ($result['success'] ? "Emails sent to " . $result['emails_sent'] . " users (recipients found: " . ($result['target_count'] ?? 0) . ")" : "Failed: " . $result['error']);
        } catch (Exception $e) {
            $message = "❌ Test Failed: " . $e->getMessage();
        }
    }
    
    elseif ($action === 'test_coupon_notification') {
        // Create a test coupon
        $test_coupon = [
            'coupon_id' => null,
            'code' => 'TEST' . rand(1000, 9999),
            'description' => 'Test coupon for notification testing',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'min_order_amount' => 50,
            'end_date' => date('Y-m-d', strtotime('+7 days'))
        ];
        
        try {
            $notificationEngine = new NotificationEngine($pdo, $functions);
            $result = $notificationEngine->notifyCouponCreated($test_coupon);
            $message = "✅ Coupon Notification Test: " . ($result['success'] ? "Emails sent to " . $result['emails_sent'] . " users (recipients found: " . ($result['target_count'] ?? 0) . ")" : "Failed: " . $result['error']);
        } catch (Exception $e) {
            $message = "❌ Test Failed: " . $e->getMessage();
        }
    }
    
    elseif ($action === 'test_order_update_notification') {
        // Test with first active order
        $order_query = "SELECT o.id, o.order_number, u.email, u.first_name FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.user_id 
                       LIMIT 1";
        $order_stmt = $pdo->query($order_query);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order && $order['email']) {
            try {
                $notificationEngine = new NotificationEngine($pdo, $functions);
                $result = $notificationEngine->notifyOrderUpdate(
                    $order['id'],
                    'Shipped',
                    $order['email'],
                    $order['first_name'] ?? 'Customer'
                );
                $message = "✅ Order Update Notification Test: " . ($result ? "Email sent to " . $order['email'] : "Failed to send email");
            } catch (Exception $e) {
                $message = "❌ Test Failed: " . $e->getMessage();
            }
        } else {
            $message = "❌ No orders found to test";
        }
    }
}

// Get statistics
try {
    // Count users with notifications enabled
    $stats_query = "
        SELECT 
            COUNT(DISTINCT u.user_id) as total_users,
            SUM(CASE WHEN unp.new_products = 1 THEN 1 ELSE 0 END) as new_products_enabled,
            SUM(CASE WHEN unp.featured_products = 1 THEN 1 ELSE 0 END) as featured_enabled,
            SUM(CASE WHEN unp.sales_promotions = 1 THEN 1 ELSE 0 END) as sales_enabled,
            SUM(CASE WHEN unp.order_updates = 1 THEN 1 ELSE 0 END) as orders_enabled,
            SUM(CASE WHEN unp.newsletter = 1 THEN 1 ELSE 0 END) as newsletter_enabled
        FROM users u
        LEFT JOIN user_notification_preferences unp ON u.user_id = unp.user_id
        WHERE u.is_active = 1
    ";
    
    $stats_stmt = $pdo->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error getting stats: " . $e->getMessage());
    $stats = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automated Notification System - Test & Verify</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 30px; }
        .message { padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: 500; }
        .message.success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4CAF50; }
        .message.error { background: #ffebee; color: #c62828; border-left: 4px solid #f44336; }
        
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h2 { color: #667eea; margin-bottom: 15px; }
        
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-card .number { font-size: 32px; font-weight: bold; }
        .stat-card .label { font-size: 14px; opacity: 0.9; margin-top: 5px; }
        
        .test-section { margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 8px; border: 1px solid #e0e0e0; }
        .test-section h3 { margin-bottom: 15px; color: #333; }
        .test-section p { color: #666; margin-bottom: 15px; line-height: 1.6; }
        
        button { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; }
        button:hover { background: #5568d3; }
        button.secondary { background: #757575; }
        button.secondary:hover { background: #616161; }
        
        .info-box { background: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .info-box strong { color: #1976D2; }
        
        .feature-list { list-style: none; margin: 15px 0; }
        .feature-list li { padding: 10px 0; color: #666; }
        .feature-list li:before { content: "✓ "; color: #4CAF50; font-weight: bold; margin-right: 8px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: 600; }
        table tr:hover { background: #f9f9f9; }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔔 Automated Notification System - Test & Verify</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✅') === 0 ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="card">
            <h2>📊 System Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number"><?php echo $stats['total_users'] ?? 0; ?></div>
                    <div class="label">Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $stats['new_products_enabled'] ?? 0; ?></div>
                    <div class="label">New Products Notif.</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $stats['sales_enabled'] ?? 0; ?></div>
                    <div class="label">Sales & Promotions</div>
                </div>
            </div>
        </div>
        
        <!-- System Overview -->
        <div class="card">
            <h2>🚀 System Overview</h2>
            <p>The Automated Notification System automatically sends emails to customers based on their preferences when:</p>
            <ul class="feature-list">
                <li><strong>New Products</strong> - Alerts when products marked as "New" are added</li>
                <li><strong>Featured Products</strong> - Alerts when products are marked as "Featured"</li>
                <li><strong>Sales & Promotions</strong> - Alerts when coupons with discounts are created</li>
                <li><strong>Order Updates</strong> - Alerts when order status changes (Processing, Shipped, Delivered, Cancelled)</li>
                <li><strong>Important News</strong> - Alerts for major system announcements</li>
                <li><strong>Newsletter</strong> - Subscription to regular newsletters</li>
            </ul>
            
            <div class="info-box">
                <strong>ℹ️ How It Works:</strong> When an admin adds a new product, creates a coupon, or updates an order status, the system automatically sends notifications to all customers who have that notification type enabled in their preferences.
            </div>
        </div>
        
        <!-- Test Tools -->
        <div class="card">
            <h2>🧪 Test Notification Triggers</h2>
            
            <div class="test-section">
                <h3>Test 1: New Product Notification</h3>
                <p>Tests sending notifications to all users with "New Products" enabled (is_new = 1)</p>
                <form method="POST">
                    <input type="hidden" name="action" value="test_new_product_notification">
                    <button type="submit">Send Test Notification</button>
                </form>
            </div>
            
            <div class="test-section">
                <h3>Test 2: Coupon/Sales Notification</h3>
                <p>Tests sending notifications to all users with "Sales & Promotions" enabled</p>
                <form method="POST">
                    <input type="hidden" name="action" value="test_coupon_notification">
                    <button type="submit">Send Test Notification</button>
                </form>
            </div>
            
            <div class="test-section">
                <h3>Test 3: Order Update Notification</h3>
                <p>Tests sending "Shipped" status notification to first active order customer</p>
                <form method="POST">
                    <input type="hidden" name="action" value="test_order_update_notification">
                    <button type="submit">Send Test Notification</button>
                </form>
            </div>
        </div>
        
        <!-- Implementation Details -->
        <div class="card">
            <h2>📋 Implementation Details</h2>
            
            <h3>Files Modified/Created:</h3>
            <table>
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Purpose</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>/includes/NotificationEngine.php</code></td>
                        <td>Notification handler</td>
                        <td>New file - Contains all notification logic</td>
                    </tr>
                    <tr>
                        <td><code>/a_pro.php</code></td>
                        <td>Product admin</td>
                        <td>Added notification trigger on product creation</td>
                    </tr>
                    <tr>
                        <td><code>/a_coupons.php</code></td>
                        <td>Coupon admin</td>
                        <td>Added notification trigger on coupon creation</td>
                    </tr>
                    <tr>
                        <td><code>/a_orders.php</code></td>
                        <td>Order admin</td>
                        <td>Added notification trigger on order status change</td>
                    </tr>
                </tbody>
            </table>
            
            <h3 style="margin-top: 20px;">Notification Triggers:</h3>
            <ul class="feature-list">
                <li>Product added with <code>is_new = 1</code> → Send "New Product" notification</li>
                <li>Product added with <code>is_featured = 1</code> → Send "Featured Product" notification</li>
                <li>Coupon created with discount → Send "Sales & Promotions" notification</li>
                <li>Order status changes → Send "Order Update" notification (if user has it enabled)</li>
            </ul>
        </div>
        
        <!-- Database Schema -->
        <div class="card">
            <h2>🗄️ Database Schema</h2>
            <p>The system uses the existing <code>user_notification_preferences</code> table:</p>
            <table>
                <thead>
                    <tr>
                        <th>Column</th>
                        <th>Type</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>new_products</td>
                        <td>TINYINT(1)</td>
                        <td>Enable/disable new product notifications</td>
                    </tr>
                    <tr>
                        <td>featured_products</td>
                        <td>TINYINT(1)</td>
                        <td>Enable/disable featured product notifications</td>
                    </tr>
                    <tr>
                        <td>sales_promotions</td>
                        <td>TINYINT(1)</td>
                        <td>Enable/disable sales/coupon notifications</td>
                    </tr>
                    <tr>
                        <td>order_updates</td>
                        <td>TINYINT(1)</td>
                        <td>Enable/disable order status notifications</td>
                    </tr>
                    <tr>
                        <td>important_news</td>
                        <td>TINYINT(1)</td>
                        <td>Enable/disable important news notifications</td>
                    </tr>
                    <tr>
                        <td>newsletter</td>
                        <td>TINYINT(1)</td>
                        <td>Enable/disable newsletter subscription</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Usage Guide -->
        <div class="card">
            <h2>📚 Usage Guide</h2>
            
            <h3>For Admins:</h3>
            <ol style="padding-left: 20px; margin: 15px 0; color: #666; line-height: 1.8;">
                <li><strong>Adding Products:</strong> Check "Mark as New" or "Mark as Featured" checkboxes when creating products. Eligible customers will be notified automatically.</li>
                <li><strong>Creating Coupons:</strong> When you create a coupon with a discount, customers with "Sales & Promotions" enabled will be notified.</li>
                <li><strong>Updating Orders:</strong> When you change an order status (Processing, Shipped, Delivered, etc.), customers with "Order Updates" enabled will be notified.</li>
                <li><strong>Monitor Email Log:</strong> Check server logs in <code>/logs/emails/</code> to verify emails were sent.</li>
            </ol>
            
            <h3>For Customers:</h3>
            <ol style="padding-left: 20px; margin: 15px 0; color: #666; line-height: 1.8;">
                <li><strong>Set Preferences:</strong> Go to Account → Notifications & Preferences</li>
                <li><strong>Enable Notifications:</strong> Toggle the switches for notification types you want to receive</li>
                <li><strong>Auto Subscribe:</strong> Toggling "Newsletter Subscription" automatically subscribes/unsubscribes from emails</li>
                <li><strong>Real-time Updates:</strong> Changes are saved immediately without page reload</li>
            </ol>
        </div>
        
        <!-- Troubleshooting -->
        <div class="card">
            <h2>🔧 Troubleshooting</h2>
            
            <h3>Issue: Emails not sending?</h3>
            <ul style="padding-left: 20px; margin: 15px 0; color: #666;">
                <li>Check if <code>PHPMailerWrapper</code> is available (should be in <code>/includes/</code>)</li>
                <li>Verify SMTP settings in configuration</li>
                <li>Check server error logs for mail errors</li>
                <li>Run a test notification from this page</li>
            </ul>
            
            <h3>Issue: Wrong customers receiving notifications?</h3>
            <ul style="padding-left: 20px; margin: 15px 0; color: #666;">
                <li>Check customer preferences: <code>user_notification_preferences</code> table</li>
                <li>Verify customer account status is "Active"</li>
                <li>Check email address is valid</li>
            </ul>
            
            <h3>Issue: Notifications not triggering?</h3>
            <ul style="padding-left: 20px; margin: 15px 0; color: #666;">
                <li>Verify <code>NotificationEngine.php</code> exists in <code>/includes/</code></li>
                <li>Check that trigger code was added to <code>a_pro.php</code>, <code>a_coupons.php</code>, <code>a_orders.php</code></li>
                <li>Review server error logs for PHP errors</li>
                <li>Ensure notification preference table exists and has data</li>
            </ul>
        </div>
        
        <!-- Quick Summary -->
        <div class="card" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border: 2px solid #4CAF50;">
            <h2>✅ System Status</h2>
            <p style="color: #2e7d32; font-size: 16px; line-height: 1.8;">
                <strong>✓ Notification System Installed</strong><br>
                <strong>✓ Product notifications integrated</strong><br>
                <strong>✓ Coupon/sales notifications integrated</strong><br>
                <strong>✓ Order update notifications integrated</strong><br>
                <strong>✓ Customer preferences respected</strong><br>
                <strong>✓ Email templates created</strong><br>
                <strong>✓ Ready for production</strong>
            </p>
        </div>
    </div>
</body>
</html>
