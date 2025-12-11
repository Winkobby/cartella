<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';


if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    error_log("Admin access denied - Redirecting to signin. User role: " . ($_SESSION['user_role'] ?? 'not set'));
    header('Location: signin.php');
    exit;
}

$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    header('Location: a_orders.php');
    exit;
}

// Get order details
try {
    // Get main order details
    $query = "SELECT o.*, u.email as user_email, u.phone as user_phone, c.code as coupon_code, c.discount_type, c.discount_value
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.user_id 
              LEFT JOIN coupons c ON o.coupon_id = c.coupon_id 
              WHERE o.id = ?";
    $stmt = $database->getConnection()->prepare($query);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: a_orders.php');
        exit;
    }

    // Get order items
    $query = "SELECT oi.*, p.name as product_name, p.main_image, p.sku, p.brand
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

    // Get shipping details if available
    $query = "SELECT * FROM shipping WHERE order_id = ?";
    $stmt = $database->getConnection()->prepare($query);
    $stmt->execute([$order_id]);
    $shipping = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: a_orders.php');
    exit;
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $new_status = $_POST['status'] ?? '';
        $status_notes = $_POST['status_notes'] ?? '';

        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        if (in_array($new_status, $valid_statuses)) {
            try {
                // Update order status
                $query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$new_status, $order_id]);

                // If status is cancelled, restore product stock
                if ($new_status === 'cancelled') {
                    restoreStock($database->getConnection(), $order_id);
                }

                // Log status change
                $query = "INSERT INTO order_status_log (order_id, status, notes, updated_by) VALUES (?, ?, ?, ?)";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$order_id, $new_status, $status_notes, $_SESSION['user_id']]);

                $_SESSION['success_message'] = 'Order status updated successfully.';
                header("Location: a_order_details.php?id=$order_id");
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'Error updating order status: ' . $e->getMessage();
            }
        }
    }

    if (isset($_POST['update_payment_status'])) {
        $new_payment_status = $_POST['payment_status'] ?? '';
        $payment_notes = $_POST['payment_notes'] ?? '';

        $valid_statuses = ['pending', 'completed', 'failed'];

        if (in_array($new_payment_status, $valid_statuses)) {
            try {
                // Update payment status in payments table
                $query = "UPDATE payments SET payment_status = ? WHERE order_id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$new_payment_status, $order_id]);

                // Update order payment status
                $query = "UPDATE orders SET payment_status = ? WHERE id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$new_payment_status, $order_id]);

                $_SESSION['success_message'] = 'Payment status updated successfully.';
                header("Location: a_order_details.php?id=$order_id");
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'Error updating payment status: ' . $e->getMessage();
            }
        }
    }
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

$page_title = "Order #{$order['order_number']} - Details";
$meta_description = "View and manage order details";
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-4 lg:py-8">
    <div class="container mx-auto px-3 lg:px-4 max-w-7xl">
        <!-- Header Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-6 lg:mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="a_orders.php" class="text-blue-600 hover:text-blue-800 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <h1 class="text-lg lg:text-lg font-bold text-gray-800 truncate max-w-[250px]">
                            Order #<?php echo htmlspecialchars($order['order_number']); ?>
                        </h1>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?>
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <?php echo htmlspecialchars($order['customer_name']); ?>
                        </span>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <a href="a_orders.php"
                        class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm lg:text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Orders
                    </a>
                    <button onclick="window.print()"
                        class="inline-flex items-center justify-center px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm lg:text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-green-800"><?php echo $_SESSION['success_message'];
                                                    unset($_SESSION['success_message']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-red-800"><?php echo $_SESSION['error_message'];
                                                unset($_SESSION['error_message']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6 lg:space-y-8">
                <!-- Order Items -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 px-4 lg:px-6 py-4 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Order Items</h2>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-full">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($order_items as $item): ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12 bg-gray-200 rounded-lg overflow-hidden mr-4">
                            <?php if (!empty($item['main_image'])): ?>
                                <img class="h-12 w-12 object-cover" src="<?php echo htmlspecialchars($item['main_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            <?php else: ?>
                                <div class="h-12 w-12 flex items-center justify-center bg-gray-100 text-gray-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <div class="text-base text-gray-900 truncate">
                                <?php echo htmlspecialchars($item['product_name'] ?: 'Product #' . $item['product_id']); ?>
                            </div>
                            <?php if (!empty($item['brand'])): ?>
                                <div class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($item['brand']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['color']) || !empty($item['size'])): ?>
                                <div class="text-xs text-gray-500 truncate">
                                    <?php
                                    $variants = [];
                                    if (!empty($item['color'])) $variants[] = 'Color: ' . $item['color'];
                                    if (!empty($item['size'])) $variants[] = 'Size: ' . $item['size'];
                                    echo implode(', ', $variants);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    ₵<?php echo number_format($item['price'], 2); ?>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    <?php echo $item['quantity']; ?>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ₵<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 px-4 lg:px-6 py-4 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Order Summary</h2>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">₵<?php echo number_format($order['subtotal'], 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping:</span>
                                <span class="font-medium">₵<?php echo number_format($order['shipping_cost'], 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax:</span>
                                <span class="font-medium">₵<?php echo number_format($order['tax_amount'], 2); ?></span>
                            </div>
                            <?php if ($order['discount_amount'] > 0): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Discount:</span>
                                    <span class="font-medium text-red-600">-₵<?php echo number_format($order['discount_amount'], 2); ?></span>
                                    <?php if (!empty($order['coupon_code'])): ?>
                                        <span class="text-xs text-gray-500">(<?php echo htmlspecialchars($order['coupon_code']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="flex justify-between pt-3 border-t border-gray-200">
                                <span class="text-lg font-semibold text-gray-900">Total:</span>
                                <span class="text-lg font-semibold text-gray-900">₵<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6 lg:space-y-8">
                <!-- Order Status -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 px-4 lg:px-6 py-4 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Order Status</h2>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="mb-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                <?php
                                echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : ($order['status'] === 'shipped' ? 'bg-blue-100 text-blue-800' : ($order['status'] === 'processing' ? 'bg-yellow-100 text-yellow-800' : ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-800' :
                                                'bg-gray-100 text-gray-800')));
                                ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <form method="POST" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Update Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                                <textarea name="status_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" placeholder="Add status update notes..."></textarea>
                            </div>
                            <button type="submit" name="update_status" class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm">
                                Update Status
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 px-4 lg:px-6 py-4 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Payment Information</h2>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                    <?php
                                    echo ($order['payment_status'] ?? 'pending') === 'completed' ? 'bg-green-100 text-green-800' : (($order['payment_status'] ?? 'pending') === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-red-100 text-red-800');
                                    ?>">
                                    <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Method:</span>
                                <span class="font-medium"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                            </div>
                            <?php if (!empty($payment['transaction_id'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Transaction ID:</span>
                                    <span class="font-medium text-sm"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($order['momo_phone'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Mobile Money:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($order['momo_phone']); ?> (<?php echo htmlspecialchars($order['momo_network']); ?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <form method="POST" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Update Payment Status</label>
                                <select name="payment_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="pending" <?php echo ($order['payment_status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo ($order['payment_status'] ?? 'pending') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="failed" <?php echo ($order['payment_status'] ?? 'pending') === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                                <textarea name="payment_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" placeholder="Add payment notes..."></textarea>
                            </div>
                            <button type="submit" name="update_payment_status" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium text-sm">
                                Update Payment Status
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 px-4 lg:px-6 py-4 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Customer Information</h2>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Name</span>
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Email</span>
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Phone</span>
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                            </div>
                            <?php if (!empty($order['user_email'])): ?>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Account</span>
                                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($order['user_email']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 px-4 lg:px-6 py-4 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Shipping Information</h2>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Address</span>
                                <p class="text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">City</span>
                                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($order['shipping_city']); ?></p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Region</span>
                                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($order['shipping_region']); ?></p>
                                </div>
                            </div>
                            <?php if (!empty($order['shipping_postal_code'])): ?>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Postal Code</span>
                                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($order['shipping_postal_code']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white !important;
        }

        .bg-gray-50 {
            background: white !important;
        }

        .shadow-sm,
        .shadow-lg {
            box-shadow: none !important;
        }

        .border {
            border: 1px solid #e5e7eb !important;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>