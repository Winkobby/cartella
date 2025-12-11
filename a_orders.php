<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
// Add this at the top of a_orders.php
error_log("Current directory: " . __DIR__);
error_log("Email helper path 1: " . __DIR__ . '/includes/email_helper.php');
error_log("Email helper path 2: " . __DIR__ . '/../includes/email_helper.php');

// Check if user is admin - FIXED: using user_role instead of role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    error_log("Admin access denied - Redirecting to signin. User role: " . ($_SESSION['user_role'] ?? 'not set'));
    header('Location: signin.php');
    exit;
}

error_log("Admin access granted to: " . $_SESSION['user_email']);

$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Add this function to a_orders.php
function logStatusChange($pdo, $order_id, $old_status, $new_status, $notes = '')
{
    try {
        // Create status_logs table if it doesn't exist
        $create_table_sql = "CREATE TABLE IF NOT EXISTS status_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            old_status VARCHAR(50),
            new_status VARCHAR(50) NOT NULL,
            notes TEXT,
            changed_by VARCHAR(255),
            changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )";
        $pdo->exec($create_table_sql);

        $changed_by = $_SESSION['user_email'] ?? 'system';

        $sql = "INSERT INTO status_logs (order_id, old_status, new_status, notes, changed_by) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id, $old_status, $new_status, $notes, $changed_by]);

        return true;
    } catch (Exception $e) {
        error_log("Error logging status change: " . $e->getMessage());
        return false;
    }
}
// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['ajax_action']) {

            case 'update_order_status':
                $order_id = intval($_POST['order_id'] ?? 0);
                $status = $_POST['status'] ?? '';
                $notes = $_POST['notes'] ?? '';

                if (!$order_id || empty($status)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid order ID or status.']);
                    exit;
                }

                // Validate status
                $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                if (!in_array($status, $valid_statuses)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid status.']);
                    exit;
                }

                // Get current status before update
                $current_sql = "SELECT status, customer_email FROM orders WHERE id = ?";
                $current_stmt = $database->getConnection()->prepare($current_sql);
                $current_stmt->execute([$order_id]);
                $current_order = $current_stmt->fetch(PDO::FETCH_ASSOC);

                $old_status = $current_order['status'] ?? '';

                // Update order status
                $query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$status, $order_id]);

                // If status is cancelled, restore product stock
                if ($status === 'cancelled') {
                    restoreStock($database->getConnection(), $order_id);
                }

                // Get customer info for notification
                $customer_sql = "SELECT u.user_id, u.first_name, u.email FROM orders o 
                                LEFT JOIN users u ON o.user_id = u.user_id 
                                WHERE o.id = ?";
                $customer_stmt = $database->getConnection()->prepare($customer_sql);
                $customer_stmt->execute([$order_id]);
                $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);

                // Send preference-based notification
                if ($customer && $customer['email']) {
                    try {
                        if (file_exists(__DIR__ . '/includes/NotificationEngine.php')) {
                            require_once __DIR__ . '/includes/NotificationEngine.php';
                            $notificationEngine = new NotificationEngine($database->getConnection(), $functions);
                            
                            // Convert status to title case
                            $status_display = ucfirst($status);
                            
                            // Send notification (respects user preferences)
                            $notify_result = $notificationEngine->notifyOrderUpdate(
                                $order_id,
                                $status_display,
                                $customer['email'],
                                $customer['first_name'] ?? 'Customer'
                            );
                            error_log("Order notification sent: " . json_encode(['result' => $notify_result, 'status' => $status_display]));
                        }
                    } catch (Exception $e) {
                        error_log("Error sending order notification: " . $e->getMessage());
                    }
                }

                // Send status update email (only for certain status changes)
                $email_statuses = ['processing', 'shipped', 'delivered', 'cancelled'];
                if (in_array($status, $email_statuses) && $status !== $old_status) {
                    error_log("Attempting to send status update email for order $order_id");
                    error_log("Old status: $old_status, New status: $status");
                    error_log("Customer email: " . ($current_order['customer_email'] ?? 'not found'));

                    try {
                        // Check if EmailHelper class exists
                        if (!class_exists('EmailHelper')) {
                            error_log("EmailHelper class not found. Trying to include...");
                            $email_helper_path = __DIR__ . '/includes/email_helper.php';
                            if (file_exists($email_helper_path)) {
                                require_once $email_helper_path;
                                error_log("EmailHelper included from: $email_helper_path");
                            } else {
                                error_log("Email helper file not found at: $email_helper_path");
                            }
                        }

                        if (class_exists('EmailHelper')) {
                            error_log("EmailHelper class exists. Calling sendOrderStatusUpdate...");
                            $email_sent = EmailHelper::sendOrderStatusUpdate(
                                $database->getConnection(),
                                $functions,
                                $order_id,
                                $status,
                                $notes
                            );

                            if ($email_sent) {
                                error_log("SUCCESS: Status update email sent for order $order_id");
                            } else {
                                error_log("FAILED: Status update email not sent for order $order_id");
                            }
                        } else {
                            error_log("ERROR: EmailHelper class still not found after attempting to include");
                        }
                    } catch (Exception $e) {
                        error_log("Exception sending status update email: " . $e->getMessage());
                        error_log("Exception trace: " . $e->getTraceAsString());
                    }
                }
                // Log the status change
                logStatusChange($database->getConnection(), $order_id, $old_status, $status, $notes);

                echo json_encode(['success' => true, 'message' => 'Order status updated successfully.']);
                break;

            case 'update_payment_status':
                $order_id = intval($_POST['order_id'] ?? 0);
                $payment_status = $_POST['payment_status'] ?? '';

                if (!$order_id || empty($payment_status)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid order ID or status.']);
                    exit;
                }

                // Validate payment status
                $valid_statuses = ['pending', 'completed', 'failed'];
                if (!in_array($payment_status, $valid_statuses)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid payment status.']);
                    exit;
                }

                // Update payment status in payments table
                $query = "UPDATE payments SET payment_status = ? WHERE order_id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$payment_status, $order_id]);

                // Update order payment status
                $query = "UPDATE orders SET payment_status = ? WHERE id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$payment_status, $order_id]);

                echo json_encode(['success' => true, 'message' => 'Payment status updated successfully.']);
                break;

            case 'bulk_update_status':
                $bulk_status = $_POST['bulk_status'] ?? '';
                $selected_orders = $_POST['selected_orders'] ?? [];

                if (empty($selected_orders)) {
                    echo json_encode(['success' => false, 'message' => 'Please select orders to perform bulk action.']);
                    exit;
                }

                if (empty($bulk_status)) {
                    echo json_encode(['success' => false, 'message' => 'Please select a status.']);
                    exit;
                }

                $order_ids = array_map('intval', $selected_orders);
                $placeholders = implode(',', array_fill(0, count($order_ids), '?'));

                // Update order status
                $query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute(array_merge([$bulk_status], $order_ids));

                // If status is cancelled, restore product stock
                if ($bulk_status === 'cancelled') {
                    foreach ($order_ids as $order_id) {
                        restoreStock($database->getConnection(), $order_id);
                    }
                }

                echo json_encode(['success' => true, 'message' => count($order_ids) . " order(s) updated successfully."]);
                break;

            case 'get_order_details':
                $order_id = intval($_POST['order_id'] ?? 0);

                if (!$order_id) {
                    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
                    exit;
                }

                // Get order details
                $query = "SELECT o.*, u.email as user_email, c.code as coupon_code 
                          FROM orders o 
                          LEFT JOIN users u ON o.user_id = u.user_id 
                          LEFT JOIN coupons c ON o.coupon_id = c.coupon_id 
                          WHERE o.id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$order_id]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$order) {
                    echo json_encode(['success' => false, 'message' => 'Order not found.']);
                    exit;
                }

                // Get order items
                $query = "SELECT oi.*, p.name as product_name, p.main_image 
                          FROM order_items oi 
                          LEFT JOIN products p ON oi.product_id = p.product_id 
                          WHERE oi.order_id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$order_id]);
                $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Get payment details
                $query = "SELECT * FROM payments WHERE order_id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$order_id]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'order' => $order,
                    'order_items' => $order_items,
                    'payment' => $payment
                ]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid AJAX action.']);
                break;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Helper function to restore stock when order is cancelled
function restoreStock($pdo, $order_id)
{
    try {
        // Get order items
        $query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Update product stock
        foreach ($order_items as $item) {
            $query = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$item['quantity'], $item['product_id']]);

            // Log inventory change
            $query = "INSERT INTO inventory_log (product_id, action, quantity_changed) VALUES (?, 'Returned', ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$item['product_id'], $item['quantity']]);
        }
    } catch (PDOException $e) {
        error_log("Error restoring stock: " . $e->getMessage());
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;

// Build query with filters - FIXED: Explicitly select columns to avoid duplicate names
$query = "SELECT 
            o.id,
            o.order_number,
            o.user_id,
            o.customer_name,
            o.customer_email,
            o.customer_phone,
            o.shipping_address,
            o.shipping_city,
            o.shipping_region,
            o.shipping_postal_code,
            o.payment_method,
            o.payment_status as order_payment_status,
            o.momo_phone,
            o.momo_network,
            o.subtotal,
            o.shipping_cost,
            o.tax_amount,
            o.discount_amount,
            o.total_amount,
            o.status,
            o.coupon_id,
            o.order_date,
            o.updated_at,
            u.email as user_email,
            p.payment_status as payment_status,
            COUNT(oi.order_item_id) as item_count
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.user_id 
          LEFT JOIN payments p ON o.id = p.order_id 
          LEFT JOIN order_items oi ON o.id = oi.order_id 
          WHERE 1=1";

$params = [];

// Apply filters
if (!empty($search)) {
    $query .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($status)) {
    $query .= " AND o.status = ?";
    $params[] = $status;
}

if (!empty($payment_status)) {
    $query .= " AND p.payment_status = ?";
    $params[] = $payment_status;
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.order_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.order_date) <= ?";
    $params[] = $date_to;
}

// Group by order
$query .= " GROUP BY o.id";

// Apply sorting
switch ($sort) {
    case 'order_asc':
        $query .= " ORDER BY o.order_number ASC";
        break;
    case 'order_desc':
        $query .= " ORDER BY o.order_number DESC";
        break;
    case 'amount_asc':
        $query .= " ORDER BY o.total_amount ASC";
        break;
    case 'amount_desc':
        $query .= " ORDER BY o.total_amount DESC";
        break;
    case 'date_asc':
        $query .= " ORDER BY o.order_date ASC";
        break;
    case 'date_desc':
        $query .= " ORDER BY o.order_date DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY o.order_date DESC";
        break;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM ($query) as filtered_orders";
$count_stmt = $database->getConnection()->prepare($count_query);
$count_stmt->execute($params);
$total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pagination
$total_pages = ceil($total_orders / $per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $per_page;

$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Execute main query
$stmt = $database->getConnection()->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$stats_query = "SELECT 
                 COUNT(*) as total_orders,
                 SUM(total_amount) as total_revenue,
                 COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                 COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
                 COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
                 COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                 COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
               FROM orders";
$stats_stmt = $database->getConnection()->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'Manage Orders';
$meta_description = 'View and manage customer orders';
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-4 lg:py-8">
    <div class="container mx-auto px-3 lg:px-4 max-w-7xl">
        <!-- Header Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-6 lg:mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Manage Orders</h1>
                    <p class="text-gray-600 mt-2 text-sm lg:text-base">View, process, and manage customer orders</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <a href="export_orders.php"
                        class="inline-flex items-center justify-center px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm lg:text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export Orders
                    </a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div id="ajax-messages"></div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Orders</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_orders']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                        <p class="text-2xl font-semibold text-gray-900">₵<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pending</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['pending_orders']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Processing</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['processing_orders']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Delivered</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['delivered_orders']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search Section -->
        <!-- Filters and Search Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-6">
    <form method="GET" class="space-y-4" id="filter-form">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm lg:text-sm"
                    placeholder="Order number, customer...">
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Order Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm lg:text-sm">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <!-- Payment Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                <select name="payment_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm lg:text-sm">
                    <option value="">All Payment Statuses</option>
                    <option value="pending" <?php echo $payment_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $payment_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="failed" <?php echo $payment_status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm lg:text-sm">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm lg:text-sm">
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="text-sm text-gray-600">
                <span id="orders-count"><?php echo $total_orders; ?></span> order(s) found
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <button type="submit"
                    class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm lg:text-sm w-full sm:w-auto flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Apply Filters
                </button>
                <a href="a_orders.php"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm lg:text-sm w-full sm:w-auto flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear
                </a>
            </div>
        </div>
    </form>
</div>

<!-- View Toggle (Mobile only) -->
<div class="md:hidden flex items-center justify-between mb-3">
    <div class="text-sm font-medium text-gray-700">View:</div>
    <div class="flex bg-gray-100 rounded-lg p-1">
        <button id="tableViewBtn" class="px-3 py-1.5 rounded-md text-sm font-medium bg-white shadow-sm text-gray-700">
            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
            </svg>
            Table
        </button>
        <button id="cardViewBtn" class="px-3 py-1.5 rounded-md text-sm font-medium text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            Cards
        </button>
    </div>
</div>

<!-- Orders Container -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Bulk Actions -->
    <div id="bulk-action-form" class="hidden md:block">
        <div class="border-b border-gray-200 px-4 lg:px-6 py-3 lg:py-4 bg-gray-50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <select name="bulk_status" id="bulk_status" class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                    <option value="">Bulk Actions</option>
                    <option value="pending">Mark as Pending</option>
                    <option value="processing">Mark as Processing</option>
                    <option value="shipped">Mark as Shipped</option>
                    <option value="delivered">Mark as Delivered</option>
                    <option value="cancelled">Cancel Orders</option>
                </select>
                <button type="button" onclick="confirmBulkStatusUpdate()"
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium text-sm w-full sm:w-auto">
                    Apply
                </button>
            </div>
            <div class="text-sm text-gray-600 whitespace-nowrap">
                Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Bulk Actions -->
    <div id="mobile-bulk-actions" class="md:hidden p-3 bg-gray-50 border-b border-gray-200">
        <div class="flex items-center gap-2">
            <input type="checkbox" id="mobile-select-all" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
            <label for="mobile-select-all" class="text-sm text-gray-700">Select All</label>
            <select name="mobile_bulk_status" id="mobile_bulk_status" class="ml-auto px-2 py-1 border border-gray-300 rounded text-sm">
                <option value="">Actions</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancel</option>
            </select>
        </div>
    </div>

    <!-- Desktop Table (hidden on mobile) -->
    <div id="desktop-table" class="overflow-x-auto hidden md:block">
        <table class="w-full min-w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                    </th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Customer</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Payment</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Items</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="orders-table-body">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="px-4 lg:px-6 py-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p class="text-lg font-medium">No orders found</p>
                            <p class="text-sm mt-1">Try adjusting your search or filter criteria</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50 transition-colors" id="order-<?php echo $order['id']; ?>">
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="selected_orders[]" value="<?php echo $order['id']; ?>"
                                    class="order-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-700">
                                    <?php echo htmlspecialchars($order['order_number']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    ID: <?php echo $order['id']; ?>
                                </div>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo htmlspecialchars($order['customer_email']); ?>
                                </div>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                                <div class="text-xs text-gray-500">
                                    <?php echo date('g:i A', strtotime($order['order_date'])); ?>
                                </div>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="font-medium">₵<?php echo number_format($order['total_amount'], 2); ?></div>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <div class="text-xs text-red-600">
                                        -₵<?php echo number_format($order['discount_amount'], 2); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php
                                    echo ($order['payment_status'] ?? 'pending') === 'completed' ? 'bg-green-100 text-green-800' : (($order['payment_status'] ?? 'pending') === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                        'bg-red-100 text-red-800');
                                    ?>">
                                    <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                                </span>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden lg:table-cell">
                                <?php echo $order['item_count']; ?>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php
                                    echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : ($order['status'] === 'shipped' ? 'bg-blue-100 text-blue-800' : ($order['status'] === 'processing' ? 'bg-yellow-100 text-yellow-800' : ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-800' :
                                        'bg-gray-100 text-gray-800')));
                                    ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <!-- View Details Button -->
                                    <a href="a_order_details.php?id=<?php echo $order['id']; ?>"
                                        class="inline-flex items-center justify-center border border-blue-300 text-blue-600 p-2 rounded-xl hover:bg-blue-50 transition-all duration-300 hover:scale-105 group"
                                        title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>

                                    <!-- Update Status Button -->
                                    <button onclick="updateOrderStatus(<?php echo $order['id']; ?>)"
                                        class="inline-flex items-center justify-center border border-green-300 text-green-600 p-2 rounded-xl hover:bg-green-50 transition-all duration-300 hover:scale-105 group"
                                        title="Update Status">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>

                                    <!-- Update Payment Button -->
                                    <button onclick="updatePaymentStatus(<?php echo $order['id']; ?>)"
                                        class="inline-flex items-center justify-center border border-yellow-300 text-yellow-600 p-2 rounded-xl hover:bg-yellow-50 transition-all duration-300 hover:scale-105 group"
                                        title="Update Payment">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards (visible on mobile) -->
    <div id="mobile-cards" class="md:hidden">
        <?php if (empty($orders)): ?>
            <div class="p-8 text-center text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="text-lg font-medium">No orders found</p>
                <p class="text-sm mt-1">Try adjusting your search or filter criteria</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-3 p-3">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-sm transition-all duration-200 mobile-card" id="mobile-order-<?php echo $order['id']; ?>">
                        <!-- Top row: Checkbox and Order Info -->
                        <div class="flex items-center justify-between mb-3">
                            <!-- Checkbox -->
                            <input type="checkbox" name="mobile_selected_orders[]" value="<?php echo $order['id']; ?>"
                                class="mobile-order-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            
                            <!-- Order Number and ID -->
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($order['order_number']); ?></div>
                                <div class="text-xs text-gray-500">ID: <?php echo $order['id']; ?></div>
                            </div>
                        </div>
                        
                        <!-- Status Badges - prominent display -->
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium 
                                <?php
                                echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 
                                ($order['status'] === 'shipped' ? 'bg-blue-100 text-blue-800' : 
                                ($order['status'] === 'processing' ? 'bg-yellow-100 text-yellow-800' : 
                                ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 
                                'bg-gray-100 text-gray-800')));
                                ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                            
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium 
                                <?php
                                echo ($order['payment_status'] ?? 'pending') === 'completed' ? 'bg-green-100 text-green-800' : 
                                (($order['payment_status'] ?? 'pending') === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                'bg-red-100 text-red-800');
                                ?>">
                                <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                            </span>
                        </div>
                        
                        <!-- Customer Information -->
                        <div class="mb-3">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <div class="text-sm font-medium text-gray-900 truncate">
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 truncate pl-6">
                                <?php echo htmlspecialchars($order['customer_email']); ?>
                            </div>
                            <?php if (!empty($order['customer_phone'])): ?>
                                <div class="text-xs text-gray-500 truncate pl-6">
                                    📞 <?php echo htmlspecialchars($order['customer_phone']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Date and Time -->
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <div class="text-sm text-gray-700">
                                <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo date('g:i A', strtotime($order['order_date'])); ?>
                            </div>
                        </div>
                        
                        <!-- Order Summary Grid -->
                        <div class="grid grid-cols-2 gap-3 mb-3 p-3 bg-gray-50 rounded-lg">
                            <!-- Amount -->
                            <div>
                                <div class="text-xs text-gray-500 mb-1">Amount</div>
                                <div class="flex items-baseline">
                                    <span class="text-base font-bold text-gray-900">₵<?php echo number_format($order['total_amount'], 2); ?></span>
                                    <?php if ($order['discount_amount'] > 0): ?>
                                        <span class="text-xs text-red-600 ml-2 bg-red-50 px-1.5 py-0.5 rounded">-₵<?php echo number_format($order['discount_amount'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Items -->
                            <div>
                                <div class="text-xs text-gray-500 mb-1">Items</div>
                                <div class="text-sm font-semibold text-gray-900">
                                    <?php echo $order['item_count']; ?> items
                                </div>
                            </div>
                            
                            <!-- Payment Method -->
                            <div>
                                <div class="text-xs text-gray-500 mb-1">Payment</div>
                                <div class="text-sm text-gray-700">
                                    <?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            
                            <!-- Shipping -->
                            <div>
                                <div class="text-xs text-gray-500 mb-1">Shipping</div>
                                <div class="text-sm text-gray-700">
                                    ₵<?php echo number_format($order['shipping_cost'], 2); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                            <!-- Shipping Address (Truncated) -->
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-500 truncate" title="<?php echo htmlspecialchars($order['shipping_address']); ?>">
                                    <svg class="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <?php echo strlen($order['shipping_address']) > 25 ? substr($order['shipping_address'], 0, 25) . '...' : $order['shipping_address']; ?>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex gap-1 ml-2">
                                <!-- View Details -->
                                <a href="a_order_details.php?id=<?php echo $order['id']; ?>"
                                    class="inline-flex items-center justify-center w-10 h-10 border border-blue-300 text-blue-600 rounded-lg hover:bg-blue-50 transition-all duration-200"
                                    title="View Details">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                
                                <!-- Update Status -->
                                <button onclick="updateOrderStatus(<?php echo $order['id']; ?>)"
                                    class="inline-flex items-center justify-center w-10 h-10 border border-green-300 text-green-600 rounded-lg hover:bg-green-50 transition-all duration-200"
                                    title="Update Status">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                
                                <!-- Update Payment -->
                                <button onclick="updatePaymentStatus(<?php echo $order['id']; ?>)"
                                    class="inline-flex items-center justify-center w-10 h-10 border border-yellow-300 text-yellow-600 rounded-lg hover:bg-yellow-50 transition-all duration-200"
                                    title="Update Payment">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="border-t border-gray-200 px-4 lg:px-6 py-4 bg-gray-50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-700 text-center sm:text-left">
                            Showing <?php echo (($current_page - 1) * $per_page) + 1; ?> to
                            <?php echo min($current_page * $per_page, $total_orders); ?> of
                            <?php echo $total_orders; ?> results
                        </div>
                        <div class="flex flex-wrap justify-center gap-2">
                            <?php if ($current_page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>"
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $current_page - 2 && $i <= $current_page + 2)): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                        class="px-3 py-2 border rounded-lg transition-colors text-sm
                                              <?php echo $i == $current_page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php elseif ($i == $current_page - 3 || $i == $current_page + 3): ?>
                                    <span class="px-3 py-2 text-gray-500 text-sm">...</span>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>"
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity duration-300">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto transform transition-transform duration-300 scale-95" id="orderDetailsContent">
            <!-- Order details content will be loaded here via AJAX -->
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="updateStatusModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity duration-300">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full transform transition-transform duration-300 scale-95" id="updateStatusContent">
            <!-- Update status content will be loaded here via AJAX -->
        </div>
    </div>
</div>

<script>
    // Select all checkboxes
    // document.getElementById('select-all').addEventListener('change', function() {
    //     const checkboxes = document.querySelectorAll('.order-checkbox');
    //     checkboxes.forEach(checkbox => {
    //         checkbox.checked = this.checked;
    //     });
    // });

    // AJAX Functions
    async function performAjaxAction(action, data) {
        try {
            const formData = new FormData();
            formData.append('ajax_action', action);

            for (const key in data) {
                if (Array.isArray(data[key])) {
                    data[key].forEach(value => {
                        formData.append(key + '[]', value);
                    });
                } else {
                    formData.append(key, data[key]);
                }
            }

            const response = await fetch('a_orders.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showNotification(result.message, 'success');
                // Reload page to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(result.message, 'error');
            }

            return result;
        } catch (error) {
            console.error('AJAX error:', error);
            showNotification('An error occurred. Please try again.', 'error');
            return {
                success: false,
                message: 'Network error'
            };
        }
    }

    // View Order Details
    async function viewOrderDetails(orderId) {
        try {
            const result = await performAjaxAction('get_order_details', {
                order_id: orderId
            });

            if (result.success) {
                const modal = document.getElementById('orderDetailsModal');
                const content = document.getElementById('orderDetailsContent');

                content.innerHTML = `
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">Order Details</h2>
                        <button onclick="closeOrderDetailsModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Order Information</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Order Number:</span>
                                    <span class="font-medium">${result.order.order_number}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Order Date:</span>
                                    <span class="font-medium">${new Date(result.order.order_date).toLocaleDateString()}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status:</span>
                                    <span class="font-medium">${result.order.status}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Payment Status:</span>
                                    <span class="font-medium">${result.payment ? result.payment.payment_status : 'N/A'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Payment Method:</span>
                                    <span class="font-medium">${result.order.payment_method}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Customer Information</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Name:</span>
                                    <span class="font-medium">${result.order.customer_name}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Email:</span>
                                    <span class="font-medium">${result.order.customer_email}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Phone:</span>
                                    <span class="font-medium">${result.order.customer_phone}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Address:</span>
                                    <span class="font-medium">${result.order.shipping_address}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Order Items</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${result.order_items.map(item => `
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-lg overflow-hidden mr-3">
                                                        ${item.main_image ? 
                                                            `<img class="h-10 w-10 object-cover" src="${item.main_image}" alt="${item.product_name}">` : 
                                                            `<div class="h-10 w-10 flex items-center justify-center bg-gray-100 text-gray-400">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                </svg>
                                                            </div>`
                                                        }
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">${item.product_name || 'Product #' + item.product_id}</div>
                                                        ${item.color ? `<div class="text-xs text-gray-500">Color: ${item.color}</div>` : ''}
                                                        ${item.size ? `<div class="text-xs text-gray-500">Size: ${item.size}</div>` : ''}
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">₵${parseFloat(item.price).toFixed(2)}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">${item.quantity}</td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">₵${(parseFloat(item.price) * item.quantity).toFixed(2)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Order Summary</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-medium">₵${parseFloat(result.order.subtotal).toFixed(2)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shipping:</span>
                                    <span class="font-medium">₵${parseFloat(result.order.shipping_cost).toFixed(2)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tax:</span>
                                    <span class="font-medium">₵${parseFloat(result.order.tax_amount).toFixed(2)}</span>
                                </div>
                                ${result.order.discount_amount > 0 ? 
                                    `<div class="flex justify-between">
                                        <span class="text-gray-600">Discount:</span>
                                        <span class="font-medium text-red-600">-₵${parseFloat(result.order.discount_amount).toFixed(2)}</span>
                                    </div>` : ''
                                }
                                <div class="flex justify-between pt-2 border-t">
                                    <span class="text-gray-900 font-medium">Total:</span>
                                    <span class="font-medium text-gray-900">₵${parseFloat(result.order.total_amount).toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Payment Information</h3>
                            ${result.payment ? `
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Payment Method:</span>
                                        <span class="font-medium">${result.order.payment_method}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Payment Status:</span>
                                        <span class="font-medium">${result.payment.payment_status}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Payment Date:</span>
                                        <span class="font-medium">${new Date(result.payment.payment_date).toLocaleDateString()}</span>
                                    </div>
                                    ${result.payment.transaction_id ? 
                                        `<div class="flex justify-between">
                                            <span class="text-gray-600">Transaction ID:</span>
                                            <span class="font-medium">${result.payment.transaction_id}</span>
                                        </div>` : ''
                                    }
                                </div>
                            ` : '<p class="text-gray-500">No payment information available</p>'}
                        </div>
                    </div>
                </div>
            `;

                modal.classList.remove('hidden');
                setTimeout(() => {
                    content.classList.remove('scale-95');
                    content.classList.add('scale-100');
                }, 50);
            }
        } catch (error) {
            console.error('Error loading order details:', error);
            showNotification('Error loading order details', 'error');
        }
    }

    // Close Order Details Modal
    function closeOrderDetailsModal() {
        const modal = document.getElementById('orderDetailsModal');
        const content = document.getElementById('orderDetailsContent');

        content.classList.remove('scale-100');
        content.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Update Order Status
    async function updateOrderStatus(orderId) {
        const modal = document.getElementById('updateStatusModal');
        const content = document.getElementById('updateStatusContent');

        // Fetch current order status
        try {
            const formData = new FormData();
            formData.append('ajax_action', 'get_order_details');
            formData.append('order_id', orderId);

            const response = await fetch('a_orders.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            const currentStatus = result.success ? result.order.status : 'pending';

            content.innerHTML = `
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Update Order Status</h2>
                <button onclick="closeUpdateStatusModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form onsubmit="submitStatusUpdate(event, ${orderId})">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Status: <span class="text-purple-600 font-semibold capitalize">${currentStatus}</span></label>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
                    <select id="order-status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="pending" ${currentStatus === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="processing" ${currentStatus === 'processing' ? 'selected' : ''}>Processing</option>
                        <option value="shipped" ${currentStatus === 'shipped' ? 'selected' : ''}>Shipped</option>
                        <option value="delivered" ${currentStatus === 'delivered' ? 'selected' : ''}>Delivered</option>
                        <option value="cancelled" ${currentStatus === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                    <textarea id="status-notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Add any notes about this status update..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeUpdateStatusModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="submit-status-btn" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center space-x-2">
                        <span id="btn-text">Update Status</span>
                        <svg id="btn-spinner" class="hidden w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    `;
        } catch (error) {
            console.error('Error loading current status:', error);
            showNotification('Error loading order details', 'error');
            return;
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 50);
    }

    // Submit Status Update
    async function submitStatusUpdate(event, orderId) {
        event.preventDefault();

        const status = document.getElementById('order-status').value;
        const notes = document.getElementById('status-notes').value;
        const submitBtn = document.getElementById('submit-status-btn');
        const btnText = document.getElementById('btn-text');
        const btnSpinner = document.getElementById('btn-spinner');

        // Show spinner and disable button
        submitBtn.disabled = true;
        btnText.textContent = 'Updating';
        btnSpinner.classList.remove('hidden');

        try {
            const result = await performAjaxAction('update_order_status', {
                order_id: orderId,
                status: status,
                notes: notes
            });

            if (result.success) {
                closeUpdateStatusModal();
            } else {
                // Reset button on error
                submitBtn.disabled = false;
                btnText.textContent = 'Update Status';
                btnSpinner.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error updating order status:', error);
            showNotification('Error updating order status', 'error');
            // Reset button on error
            submitBtn.disabled = false;
            btnText.textContent = 'Update Status';
            btnSpinner.classList.add('hidden');
        }
    }

    // Close Update Status Modal
    function closeUpdateStatusModal() {
        const modal = document.getElementById('updateStatusModal');
        const content = document.getElementById('updateStatusContent');

        content.classList.remove('scale-100');
        content.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Update Payment Status
    function updatePaymentStatus(orderId) {
        // Get payment details first
        fetch('a_orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax_action=get_order_details&order_id=${orderId}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.payment) {
                    const modal = document.getElementById('updateStatusModal');
                    const content = document.getElementById('updateStatusContent');

                    content.innerHTML = `
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">Update Payment Status</h2>
                        <button onclick="closeUpdateStatusModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form onsubmit="submitPaymentStatusUpdate(event, ${orderId})">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                            <select id="payment-status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="pending" ${result.payment.payment_status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="completed" ${result.payment.payment_status === 'completed' ? 'selected' : ''}>Completed</option>
                                <option value="failed" ${result.payment.payment_status === 'failed' ? 'selected' : ''}>Failed</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                            <textarea id="payment-notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Add any notes about this payment status update..."></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeUpdateStatusModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                Update Payment Status
                            </button>
                        </div>
                    </form>
                </div>
            `;

                    modal.classList.remove('hidden');
                    setTimeout(() => {
                        content.classList.remove('scale-95');
                        content.classList.add('scale-100');
                    }, 50);
                } else {
                    showNotification('No payment information available for this order', 'error');
                }
            })
            .catch(error => {
                console.error('Error fetching payment details:', error);
                showNotification('Error fetching payment details', 'error');
            });
    }

    // Submit Payment Status Update
    async function submitPaymentStatusUpdate(event, orderId) {
        event.preventDefault();

        const status = document.getElementById('payment-status').value;
        const notes = document.getElementById('payment-notes').value;

        try {
            const result = await performAjaxAction('update_payment_status', {
                order_id: orderId,
                payment_status: status,
                notes: notes
            });

            if (result.success) {
                closeUpdateStatusModal();
            }
        } catch (error) {
            console.error('Error updating payment status:', error);
            showNotification('Error updating payment status', 'error');
        }
    }

    // Bulk Status Update
    function confirmBulkStatusUpdate() {
        const selectedOrders = document.querySelectorAll('.order-checkbox:checked');
        const bulkStatus = document.getElementById('bulk_status').value;

        if (selectedOrders.length === 0) {
            showNotification('Please select orders to perform bulk action.', 'warning');
            return;
        }

        if (!bulkStatus) {
            showNotification('Please select a status.', 'warning');
            return;
        }

        const orderIds = Array.from(selectedOrders).map(cb => cb.value);

        let message = `Are you sure you want to update ${selectedOrders.length} order(s) to "${bulkStatus}"?`;
        let type = 'warning';
        let confirmText = 'Update';

        if (bulkStatus === 'cancelled') {
            message = `Are you sure you want to cancel ${selectedOrders.length} order(s)? This action cannot be undone.`;
            type = 'error';
            confirmText = 'Cancel Orders';
        }

        showConfirmationModal(
            'Confirm Bulk Status Update',
            message,
            () => {
                performAjaxAction('bulk_update_status', {
                    bulk_status: bulkStatus,
                    selected_orders: orderIds
                });
            }, {
                type: type,
                confirmText: confirmText,
                cancelText: 'Cancel'
            }
        );
    }

    // Quick search functionality
    let searchTimeout;
    document.querySelector('input[name="search"]').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filter-form').submit();
        }, 500);
    });

    // Show notification function (uses global toast system)
    function showNotification(message, type = 'info') {
        try {
            if (window.toast && typeof window.toast[type] === 'function') {
                window.toast[type](message);
                return;
            }

            if (window.toast && typeof window.toast.show === 'function') {
                window.toast.show(message, type);
                return;
            }
        } catch (e) {
            console.error('Toast error:', e);
        }

        // Fallback simple notification
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    // Confirmation modal function
    function showConfirmationModal(title, message, onConfirm, options = {}) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';

        const modalContent = document.createElement('div');
        modalContent.className = 'bg-white rounded-lg p-6 max-w-md w-full mx-4';

        const modalTitle = document.createElement('h3');
        modalTitle.className = 'text-lg font-medium text-gray-900 mb-4';
        modalTitle.textContent = title;

        const modalMessage = document.createElement('p');
        modalMessage.className = 'text-gray-600 mb-6';
        modalMessage.textContent = message;

        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'flex justify-end space-x-3';

        const cancelButton = document.createElement('button');
        cancelButton.className = 'px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors';
        cancelButton.textContent = options.cancelText || 'Cancel';
        cancelButton.onclick = () => document.body.removeChild(modal);

        const confirmButton = document.createElement('button');
        confirmButton.className = `px-4 py-2 rounded-lg transition-colors ${
        options.type === 'error' ? 'bg-red-600 hover:bg-red-700 text-white' :
        options.type === 'warning' ? 'bg-yellow-600 hover:bg-yellow-700 text-white' :
        'bg-blue-600 hover:bg-blue-700 text-white'
    }`;
        confirmButton.textContent = options.confirmText || 'Confirm';
        confirmButton.onclick = () => {
            onConfirm();
            document.body.removeChild(modal);
        };

        buttonContainer.appendChild(cancelButton);
        buttonContainer.appendChild(confirmButton);

        modalContent.appendChild(modalTitle);
        modalContent.appendChild(modalMessage);
        modalContent.appendChild(buttonContainer);

        modal.appendChild(modalContent);
        document.body.appendChild(modal);
    }

 // View toggle for mobile - Fixed version
document.addEventListener('DOMContentLoaded', function() {
    const tableViewBtn = document.getElementById('tableViewBtn');
    const cardViewBtn = document.getElementById('cardViewBtn');
    const desktopTable = document.getElementById('desktop-table');
    const mobileCards = document.getElementById('mobile-cards');
    
    // Function to set view based on screen size
    function setInitialView() {
        const isMobile = window.innerWidth < 768;
        
        if (isMobile) {
            // Check for saved preference
            const savedView = localStorage.getItem('adminOrdersViewPreference');
            
            if (savedView === 'table') {
                // Show table view
                desktopTable.classList.remove('hidden');
                mobileCards.classList.add('hidden');
                tableViewBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-700');
                tableViewBtn.classList.remove('text-gray-500');
                cardViewBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-700');
                cardViewBtn.classList.add('text-gray-500');
            } else {
                // Default to cards view (mobile preferred)
                desktopTable.classList.add('hidden');
                mobileCards.classList.remove('hidden');
                cardViewBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-700');
                cardViewBtn.classList.remove('text-gray-500');
                tableViewBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-700');
                tableViewBtn.classList.add('text-gray-500');
                // Save preference
                localStorage.setItem('adminOrdersViewPreference', 'cards');
            }
        } else {
            // Desktop always shows table
            desktopTable.classList.remove('hidden');
            mobileCards.classList.add('hidden');
        }
    }
    
    // Set initial view
    setInitialView();
    
    // Add event listeners for toggle buttons
    tableViewBtn.addEventListener('click', function() {
        if (window.innerWidth < 768) {
            desktopTable.classList.remove('hidden');
            mobileCards.classList.add('hidden');
            tableViewBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-700');
            tableViewBtn.classList.remove('text-gray-500');
            cardViewBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-700');
            cardViewBtn.classList.add('text-gray-500');
            localStorage.setItem('adminOrdersViewPreference', 'table');
        }
    });
    
    cardViewBtn.addEventListener('click', function() {
        if (window.innerWidth < 768) {
            desktopTable.classList.add('hidden');
            mobileCards.classList.remove('hidden');
            cardViewBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-700');
            cardViewBtn.classList.remove('text-gray-500');
            tableViewBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-700');
            tableViewBtn.classList.add('text-gray-500');
            localStorage.setItem('adminOrdersViewPreference', 'cards');
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', setInitialView);
});

// Mobile bulk actions
document.getElementById('mobile_bulk_status').addEventListener('change', function() {
    if (this.value) {
        confirmMobileBulkStatusUpdate(this.value);
        this.value = ''; // Reset dropdown
    }
});

function confirmMobileBulkStatusUpdate(action) {
    const selectedOrders = document.querySelectorAll('.mobile-order-checkbox:checked');
    
    if (selectedOrders.length === 0) {
        showNotification('Please select orders to perform bulk action.', 'warning');
        return;
    }

    const orderIds = Array.from(selectedOrders).map(cb => cb.value);

    let message = `Are you sure you want to update ${selectedOrders.length} order(s) to "${action}"?`;
    let type = 'warning';
    let confirmText = 'Update';

    if (action === 'cancelled') {
        message = `Are you sure you want to cancel ${selectedOrders.length} order(s)? This action cannot be undone.`;
        type = 'error';
        confirmText = 'Cancel Orders';
    }

    showConfirmationModal(
        'Confirm Bulk Status Update',
        message,
        () => {
            performAjaxAction('bulk_update_status', {
                bulk_status: action,
                selected_orders: orderIds
            });
        }, {
            type: type,
            confirmText: confirmText,
            cancelText: 'Cancel'
        }
    );
}

// Select all checkboxes for mobile
document.getElementById('mobile-select-all').addEventListener('change', function() {
    const mobileCheckboxes = document.querySelectorAll('.mobile-order-checkbox');
    mobileCheckboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    // Also update desktop checkboxes
    const desktopCheckboxes = document.querySelectorAll('.order-checkbox');
    desktopCheckboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Also update the existing select-all to sync with mobile
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    // Also update mobile checkboxes
    const mobileCheckboxes = document.querySelectorAll('.mobile-order-checkbox');
    mobileCheckboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});
</script>

<style>
/* Fix checkbox sizing */
input[type="checkbox"].order-checkbox,
input[type="checkbox"].mobile-order-checkbox {
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    min-height: 18px !important;
    cursor: pointer;
}

/* Mobile-specific checkbox adjustments */
@media (max-width: 767px) {
    .mobile-order-checkbox {
        width: 20px !important;
        height: 20px !important;
        transform: scale(1.1);
    }
    
    /* Ensure mobile view is properly displayed */
    #mobile-cards {
        display: grid !important;
    }
    
    #desktop-table {
        display: none !important;
    }
    
    /* Mobile toggle button active state */
    #cardViewBtn.active {
        background-color: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        color: #374151;
    }
    
    #tableViewBtn.active {
        background-color: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        color: #374151;
    }
}

    /* Mobile card styling */
@media (max-width: 767px) {
    .mobile-card {
        transition: all 0.3s ease;
        position: relative;
    }
    
    .mobile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    /* Status badge colors */
    .bg-green-100 { background-color: #d1fae5; }
    .text-green-800 { color: #065f46; }
    .bg-blue-100 { background-color: #dbeafe; }
    .text-blue-800 { color: #1e40af; }
    .bg-yellow-100 { background-color: #fef3c7; }
    .text-yellow-800 { color: #92400e; }
    .bg-red-100 { background-color: #fee2e2; }
    .text-red-800 { color: #991b1b; }
    .bg-gray-100 { background-color: #f3f4f6; }
    .text-gray-800 { color: #374151; }
    
    /* Ensure text doesn't overflow */
    .truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Better spacing for mobile */
    .mobile-card > * + * {
        margin-top: 12px;
    }
}

/* Touch-friendly buttons on mobile */
@media (max-width: 767px) {
    button, 
    .mobile-order-checkbox {
        min-height: 44px;
        min-width: 44px;
    }
    
    .mobile-order-checkbox {
        width: 24px;
        height: 24px;
    }
    
    /* Action buttons */
    .mobile-card button[title] {
        position: relative;
    }
    
    .mobile-card button[title]:hover::after {
        content: attr(title);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 10;
        margin-bottom: 5px;
    }
}

/* Swipe actions for future enhancement */
.mobile-card-swipe {
    position: relative;
    overflow: hidden;
}

.mobile-card-swipe-actions {
    position: absolute;
    top: 0;
    right: -100%;
    bottom: 0;
    display: flex;
    align-items: center;
    background: linear-gradient(to right, transparent, rgba(139, 92, 246, 0.1));
    padding: 0 16px;
    transition: right 0.3s ease;
}

.mobile-card-swipe:hover .mobile-card-swipe-actions {
    right: 0;
}


    .animate-fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Custom scrollbar for table */
    .overflow-x-auto::-webkit-scrollbar {
        height: 6px;
    }

    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Mobile optimizations */
    @media (max-width: 640px) {
        .container {
            padding-left: 12px;
            padding-right: 12px;
        }
    }

    /* Loading state for buttons */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-right-color: transparent;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>