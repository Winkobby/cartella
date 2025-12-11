<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/settings_helper.php';

// Set proper JSON header first
header('Content-Type: application/json');

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize database and get connection
try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Check if database connection is successful
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Initialize functions
$functions = new Functions();
$functions->setDatabase($database);

// Get action from request
$action = $_GET['action'] ?? '';

// Parse JSON request body if content type is JSON
$json_data = [];
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $json_body = file_get_contents('php://input');
    $json_data = json_decode($json_body, true) ?? [];
} else {
    // Also try to get from raw input if not set in content-type
    $json_body = file_get_contents('php://input');
    if (!empty($json_body)) {
        $json_data = json_decode($json_body, true) ?? [];
    }
}

// Log the action for debugging
error_log("Orders API Action: " . $action);

try {
    switch ($action) {
        case 'create_order':
            createOrder($pdo, $functions, $json_data);
            break;
        case 'initiate_payment':
            initiatePayment($pdo, $json_data);
            break;
        case 'submit_otp':
            submitOtp($pdo, $json_data);
            break;
        case 'resend_otp':
            resendOtp($pdo, $json_data);
            break;
        case 'get_orders':
            getOrders($pdo, $functions);
            break;
        case 'get_order_details':
            getOrderDetails($pdo, $functions);
            break;
        case 'cancel_order':
            cancelOrder($pdo);
            break;
        case 'get_order_count':
            getOrderCount($pdo);
            break;
        case 'reorder':
            reorder($pdo, $functions, $json_data);
            break;
        case 'track_order_public':
            trackOrderPublic($pdo, $functions, $json_data);
            break;
        case 'delete_order':
            deleteOrder($pdo);
            break;
        case 'track_order':
            trackOrder($pdo, $functions);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    error_log("Orders API Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function createOrder($pdo, $functions, $json_data)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    // Global maintenance mode: prevent purchases by non-admin users
    if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE) {
        $user_role = $_SESSION['user_role'] ?? 'Customer';
        if (strtolower($user_role) !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'The store is temporarily closed for maintenance. Purchasing is disabled.']);
            return;
        }
    }

    // Validate cart
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        return;
    }

    // Get order data from JSON or POST
    $order_data = $json_data ?? $_POST;

    // Validate required fields
    $required_fields = ['customer', 'shipping', 'payment'];
    foreach ($required_fields as $field) {
           if (empty($order_data[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    // Validate customer fields
    $customer_fields = ['first_name', 'last_name', 'email', 'phone'];
    foreach ($customer_fields as $field) {
        if (empty($order_data['customer'][$field])) {
            echo json_encode(['success' => false, 'message' => "Missing customer $field"]);
            return;
        }
    }

    try {
        $pdo->beginTransaction();

        // Generate order number
        $order_number = 'ORD' . date('Ymd') . strtoupper(uniqid());

        // Calculate order totals
        $subtotal = $functions->getCartTotal();
        $tax_amount = 0; // No tax for now

        // Load shipping settings
        $shipping_cost = 0.0;
        try {
            SettingsHelper::init($pdo);
            $shipping_enabled = SettingsHelper::get($pdo, 'shipping_enabled', '1');
            $default_shipping = floatval(SettingsHelper::get($pdo, 'shipping_cost', 0));
            $free_threshold = floatval(SettingsHelper::get($pdo, 'free_shipping_threshold', 0));

            if ($shipping_enabled === '1' || $shipping_enabled === 1 || $shipping_enabled === true) {
                $shipping_cost = $default_shipping;

                // If applied coupon gives free shipping, override
                if (isset($_SESSION['applied_coupon']) && ($_SESSION['applied_coupon']['discount_type'] ?? '') === 'shipping') {
                    $shipping_cost = 0.0;
                }

                // Free if subtotal meets threshold
                if ($free_threshold > 0 && $subtotal >= $free_threshold) {
                    $shipping_cost = 0.0;
                }
            } else {
                $shipping_cost = 0.0;
            }
        } catch (Exception $e) {
            // fallback to 0 on error
            $shipping_cost = 0.0;
        }

        $discount_amount = 0;
        $coupon_id = null;
        $coupon_code = null;

        // Apply coupon discount if available
        if (isset($_SESSION['applied_coupon'])) {
            $coupon = $_SESSION['applied_coupon'];
            $discount_amount = $coupon['discount_amount'];
            $coupon_id = $coupon['coupon_id'] ?? null;
            $coupon_code = $coupon['code'] ?? null;
        }

        $total_amount = $subtotal - $discount_amount + $shipping_cost + $tax_amount;

        // Validate total amount
        if ($total_amount <= 0) {
            throw new Exception('Invalid order total');
        }

        $order_sql = "INSERT INTO orders (
            order_number, user_id, customer_name, customer_email, customer_phone,
            shipping_address, shipping_city, shipping_region, shipping_postal_code,
            payment_method, payment_status, 
            subtotal, shipping_cost, tax_amount, discount_amount, total_amount, status,
            coupon_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Extract customer information
        $customer_name = trim($order_data['customer']['first_name'] . ' ' . $order_data['customer']['last_name']);
        $customer_email = trim($order_data['customer']['email']);
        $customer_phone = trim($order_data['customer']['phone']);
        $user_id = $_SESSION['user_id'] ?? null;

        $order_stmt = $pdo->prepare($order_sql);
        if (!$order_stmt) {
            throw new Exception('Failed to prepare order statement: ' . implode(', ', $pdo->errorInfo()));
        }

        $result = $order_stmt->execute([
            $order_number,
            $user_id,
            $customer_name,
            $customer_email,
            $customer_phone,
            $order_data['shipping']['address'] ?? '',
            $order_data['shipping']['city'] ?? '',
            $order_data['shipping']['region'] ?? '',
            $order_data['shipping']['postal_code'] ?? '',
            $order_data['payment']['method'] ?? 'paystack_inline',
            'pending', // payment_status - lowercase
            $subtotal,
            $shipping_cost,
            $tax_amount,
            $discount_amount,
            $total_amount,
            'pending', // order status - lowercase
            $coupon_id
        ]);

        if (!$result) {
            throw new Exception('Failed to execute order insertion: ' . implode(', ', $order_stmt->errorInfo()));
        }

        $order_id = $pdo->lastInsertId();

        // Insert order items
        $order_items_sql = "INSERT INTO order_items (
            order_id, product_id, quantity, price, color, size
        ) VALUES (?, ?, ?, ?, ?, ?)";

        $order_items_stmt = $pdo->prepare($order_items_sql);
        if (!$order_items_stmt) {
            throw new Exception('Failed to prepare order items statement');
        }

        foreach ($_SESSION['cart'] as $item) {
            $result = $order_items_stmt->execute([
                $order_id,
                $item['product_id'] ?? 0,
                $item['quantity'] ?? 1,
                $item['price'] ?? 0,
                $item['color'] ?? null,
                $item['size'] ?? null
            ]);

            if (!$result) {
                throw new Exception('Failed to insert order item: ' . implode(', ', $order_items_stmt->errorInfo()));
            }
        }

        // Create a payment record
        $payment_sql = "INSERT INTO payments (
            order_id, amount, payment_status, payment_method, provider_reference
        ) VALUES (?, ?, ?, ?, ?)";

        $payment_stmt = $pdo->prepare($payment_sql);
        if (!$payment_stmt) {
            throw new Exception('Failed to prepare payment statement');
        }

        $placeholder_reference = 'PSK_' . time() . '_' . $order_id;
        $result = $payment_stmt->execute([
            $order_id,
            $total_amount,
            'pending',
            $order_data['payment']['method'] ?? 'paystack_inline',
            $placeholder_reference
        ]);

        if (!$result) {
            throw new Exception('Failed to insert payment record: ' . implode(', ', $payment_stmt->errorInfo()));
        }

        // RECORD COUPON USAGE - THIS IS THE MISSING PART
        if ($coupon_id && $user_id) {
            // Verify coupon is still valid
            $coupon_check_sql = "SELECT usage_limit, used_count, user_usage_limit 
                                FROM coupons 
                                WHERE coupon_id = ? AND is_active = 1 
                                AND start_date <= NOW() AND end_date >= NOW()";
            $coupon_check_stmt = $pdo->prepare($coupon_check_sql);
            $coupon_check_stmt->execute([$coupon_id]);
            $coupon = $coupon_check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($coupon) {
                // Check global usage limit
                if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
                    throw new Exception('This coupon has reached its usage limit');
                }

                // Check user-specific usage limit
                $user_usage_sql = "SELECT COUNT(*) as user_usage_count 
                                  FROM coupon_usage 
                                  WHERE coupon_id = ? AND user_id = ?";
                $user_usage_stmt = $pdo->prepare($user_usage_sql);
                $user_usage_stmt->execute([$coupon_id, $user_id]);
                $user_usage = $user_usage_stmt->fetch(PDO::FETCH_ASSOC);

                if ($user_usage && $user_usage['user_usage_count'] >= $coupon['user_usage_limit']) {
                    throw new Exception('You have reached the usage limit for this coupon');
                }

                // Record coupon usage
                $coupon_usage_sql = "INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)";
                $coupon_usage_stmt = $pdo->prepare($coupon_usage_sql);

                $result = $coupon_usage_stmt->execute([
                    $coupon_id,
                    $user_id,
                    $order_id,
                    $discount_amount
                ]);

                if (!$result) {
                    throw new Exception('Failed to record coupon usage: ' . implode(', ', $coupon_usage_stmt->errorInfo()));
                }

                // Update the used_count in coupons table
                $update_coupon_sql = "UPDATE coupons SET used_count = used_count + 1 WHERE coupon_id = ?";
                $update_coupon_stmt = $pdo->prepare($update_coupon_sql);
                $update_coupon_stmt->execute([$coupon_id]);
            }
        }

        // In the createOrder function, after successfully creating the order
                // Debug: log transaction state before commit to help diagnose intermittent "no active transaction" errors
                try {
                    if ($pdo && $pdo->inTransaction()) {
                        $pdo->commit();
                    }
                } catch (Exception $commitEx) {
                    error_log("Commit error during order creation: " . $commitEx->getMessage());
                    error_log("createOrder: inTransaction when commit threw = " . (($pdo && $pdo->inTransaction()) ? '1' : '0'));
                    error_log("createOrder: commit exception backtrace: " . $commitEx->getTraceAsString());
                    // Re-throw so outer catch can handle returning the error to client
                    throw $commitEx;
                }

        // Clear applied coupon after successful order creation
        if (isset($_SESSION['applied_coupon'])) {
            unset($_SESSION['applied_coupon']);
        }

        // Send order confirmation to customer
        try {
            require_once __DIR__ . '/../includes/email_helper.php';
            $email_sent = EmailHelper::sendOrderConfirmation($pdo, $functions, $order_id, $customer_email, $customer_name);

            if (!$email_sent) {
                error_log("Customer email notification failed for order $order_id but order was created");
            }
        } catch (Exception $e) {
            // Log error but don't fail order creation
            error_log("Failed to send order confirmation email: " . $e->getMessage());
        }

        // SEND NOTIFICATION TO SHOP OWNER - ADD THIS PART
        try {
            // Get admin email from environment or use shop owner email
            $admin_email = $_ENV['ADMIN_EMAIL'] ?? $_ENV['SHOP_OWNER_EMAIL'] ?? $_ENV['MAIL_USER'] ?? null;

            if ($admin_email) {
                $owner_notification_sent = EmailHelper::sendNewOrderNotification(
                    $pdo,
                    $functions,
                    $order_id,
                    $admin_email
                );

                if ($owner_notification_sent) {
                    error_log("Shop owner notification sent for order $order_id");
                } else {
                    error_log("Failed to send shop owner notification for order $order_id");
                }
            } else {
                error_log("No admin email configured for new order notifications");
            }
        } catch (Exception $e) {
            // Log error but don't fail order creation
            error_log("Failed to send shop owner notification: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully',
            'order_id' => $order_id,
            'order_number' => $order_number,
            'total_amount' => $total_amount,
            'formatted_total' => $functions->formatPrice($total_amount),
            'payment_placeholder_reference' => $placeholder_reference,
            'coupon_applied' => !empty($coupon_id),
            'discount_amount' => $discount_amount
        ]);
    } catch (Exception $e) {
        // Only rollback if a transaction is active to avoid "There is no active transaction" errors
        try {
                if ($pdo && $pdo->inTransaction()) { 
                    $pdo->rollBack(); 
                } 
            } catch (Exception $rollbackEx) { 
                // Log rollback error but continue to report the original error 
                error_log("Rollback error during order creation: " . $rollbackEx->getMessage()); 
            }

        error_log("Order creation error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to create order: ' . $e->getMessage()]);
    }
}

function getOrders($pdo, $functions)
{
    $user_id = $_SESSION['user_id'] ?? null;
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 10);
    $offset = ($page - 1) * $limit;
    $status_filter = $_GET['status'] ?? '';
    $date_range = $_GET['date_range'] ?? '';
    $sort_by = $_GET['sort'] ?? 'newest';
    $search = $_GET['search'] ?? '';

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }

    try {
        // Build base query with filters
        $where_conditions = ["o.user_id = ?"];
        $params = [$user_id];

        // Status filter
        if ($status_filter && $status_filter !== 'all') {
            $where_conditions[] = "o.status = ?";
            $params[] = $status_filter;
        }

        // Date range filter
        if ($date_range && $date_range !== 'all') {
            $date_condition = "o.order_date >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $where_conditions[] = $date_condition;
            $params[] = intval($date_range);
        }

        // Search filter
        if ($search) {
            $where_conditions[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR EXISTS (
                SELECT 1 FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = o.id AND p.name LIKE ?
            ))";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Get total count
        $count_sql = "SELECT COUNT(DISTINCT o.id) as total FROM orders o WHERE $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Build sort order
        $sort_order = "o.order_date DESC";
        switch ($sort_by) {
            case 'oldest':
                $sort_order = "o.order_date ASC";
                break;
            case 'total_high':
                $sort_order = "o.total_amount DESC";
                break;
            case 'total_low':
                $sort_order = "o.total_amount ASC";
                break;
            default:
                $sort_order = "o.order_date DESC";
        }

        // Get orders with item images
        $orders_sql = "SELECT 
                o.id, 
                o.order_number, 
                o.total_amount, 
                o.status, 
                o.payment_status,
                o.order_date as created_at, 
                COUNT(oi.order_item_id) as item_count,
                GROUP_CONCAT(DISTINCT p.main_image) as item_images,
                GROUP_CONCAT(DISTINCT p.name) as item_names
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.product_id
            WHERE $where_clause
            GROUP BY o.id
            ORDER BY $sort_order
            LIMIT ? OFFSET ?";

        // Add pagination parameters
        $params[] = $limit;
        $params[] = $offset;

        $orders_stmt = $pdo->prepare($orders_sql);
        $orders_stmt->execute($params);
        $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format orders
        $formatted_orders = array_map(function ($order) use ($functions) {
            // Process item images
            $item_images = [];
            if (!empty($order['item_images'])) {
                $images = explode(',', $order['item_images']);
                $item_images = array_slice(array_filter($images), 0, 4); // Get up to 4 unique images
            }

            // Process item names for search display
            $item_names = [];
            if (!empty($order['item_names'])) {
                $names = explode(',', $order['item_names']);
                $item_names = array_slice(array_unique(array_filter($names)), 0, 3); // Get up to 3 unique names
            }

            return [
                'id' => $order['id'],
                'order_number' => $order['order_number'],
                'total_amount' => floatval($order['total_amount']),
                'formatted_total' => $functions->formatPrice($order['total_amount']),
                'status' => $order['status'],
                'payment_status' => $order['payment_status'],
                'item_count' => $order['item_count'],
                'item_images' => $item_images,
                'item_names' => $item_names,
                'created_at' => $order['created_at'],
                'formatted_date' => date('M j, Y', strtotime($order['created_at']))
            ];
        }, $orders);

        echo json_encode([
            'success' => true,
            'orders' => $formatted_orders,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total_orders,
                'pages' => ceil($total_orders / $limit)
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get orders error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch orders: ' . $e->getMessage()]);
    }
}

function getOrderDetails($pdo, $functions)
{
    $order_id = intval($_GET['order_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        return;
    }

    try {
        // Get order basic info
        $order_sql = "SELECT 
                o.*, p.provider_reference as transaction_id
            FROM orders o
            LEFT JOIN payments p ON o.id = p.order_id
            WHERE o.id = ? AND (o.user_id = ? OR ? IS NULL)";

        $order_stmt = $pdo->prepare($order_sql);
        $order_stmt->execute([$order_id, $user_id, $user_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Get order items
        $items_sql = "SELECT oi.*, p.name as product_name, p.main_image as image
                     FROM order_items oi 
                     LEFT JOIN products p ON oi.product_id = p.product_id 
                     WHERE oi.order_id = ?";

        $items_stmt = $pdo->prepare($items_sql);
        if (!$items_stmt) {
            throw new Exception('Failed to prepare items query: ' . implode(', ', $pdo->errorInfo()));
        }

        $items_stmt->execute([$order_id]);
        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format items
        $formatted_items = array_map(function ($item) {
            $price = floatval($item['price']);
            $quantity = intval($item['quantity']);
            $total_price = $price * $quantity;

            return [
                'order_item_id' => $item['order_item_id'],
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_price' => $price,
                'quantity' => $quantity,
                'total_price' => $total_price,
                'formatted_price' => 'GHS ' . number_format($price, 2),
                'formatted_total' => 'GHS ' . number_format($total_price, 2),
                'color' => $item['color'],
                'size' => $item['size'],
                'image' => $item['image'] ?: 'assets/images/placeholder-product.jpg'
            ];
        }, $items);

        // Use the actual order number from the database
        $order_number = $order['order_number'];

        // Format order
        $formatted_order = [
            'id' => $order['id'],
            'order_number' => $order_number,
            'customer_name' => $order['customer_name'],
            'customer_email' => $order['customer_email'],
            'customer_phone' => $order['customer_phone'],
            'shipping_address' => [
                'address' => $order['shipping_address'],
                'city' => $order['shipping_city'],
                'region' => $order['shipping_region'],
                'postal_code' => $order['shipping_postal_code']
            ],
            'payment_info' => [
                'method' => $order['payment_method'],
                'momo_number' => $order['momo_phone'],
                'momo_network' => $order['momo_network'],
                'transaction_id' => $order['transaction_id']
            ],
            'totals' => [
                'subtotal' => floatval($order['subtotal']),
                'shipping_cost' => floatval($order['shipping_cost']),
                'tax_amount' => floatval($order['tax_amount']),
                'total_amount' => floatval($order['total_amount'])
            ],
            'formatted_totals' => [
                'subtotal' => $functions->formatPrice($order['subtotal']),
                'shipping_cost' => $functions->formatPrice($order['shipping_cost']),
                'tax_amount' => $functions->formatPrice($order['tax_amount']),
                'total_amount' => $functions->formatPrice($order['total_amount'])
            ],
            'status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'created_at' => $order['order_date'],
            'formatted_date' => date('F j, Y, g:i A', strtotime($order['order_date']))
        ];

        echo json_encode([
            'success' => true,
            'order' => $formatted_order,
            'items' => $formatted_items
        ]);
    } catch (Exception $e) {
        error_log("Get order details error: " . $e->getMessage());
        // Ensure we return proper JSON even on errors
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch order details: ' . $e->getMessage()
        ]);
    }
}

function cancelOrder($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    $order_id = intval($_POST['order_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        return;
    }

    try {
        // Check if order exists and belongs to user
        $check_sql = "SELECT id, status FROM orders WHERE id = ? AND user_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$order_id, $user_id]);
        $order = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Check if order can be cancelled
        if (!in_array($order['status'], ['processing', 'pending'])) {
            echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage']);
            return;
        }

        // Update order status
        $update_sql = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$order_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Order cancelled successfully'
        ]);
    } catch (Exception $e) {
        error_log("Cancel order error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
    }
}

function getOrderCount($pdo)
{
    $user_id = $_SESSION['user_id'] ?? null;
    $status = $_GET['status'] ?? '';
    $date_range = $_GET['date_range'] ?? '';
    $search = $_GET['search'] ?? '';

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }

    try {
        $where_conditions = ["user_id = ?"];
        $params = [$user_id];

        if ($status && $status !== 'all') {
            $where_conditions[] = "status = ?";
            $params[] = $status;
        }

        if ($date_range && $date_range !== 'all') {
            $where_conditions[] = "order_date >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params[] = intval($date_range);
        }

        if ($search) {
            $where_conditions[] = "(order_number LIKE ? OR customer_name LIKE ?)";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);
        $sql = "SELECT COUNT(*) as count FROM orders WHERE $where_clause";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'count' => intval($result['count'])
        ]);
    } catch (Exception $e) {
        error_log("Get order count error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to get order count']);
    }
}

/**
 * Reorder functionality - Add items from a previous order to cart
 */
function reorder($pdo, $functions, $json_data)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    $order_id = intval($json_data['order_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        return;
    }

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }

    try {
        // Verify order belongs to user
        $order_sql = "SELECT id FROM orders WHERE id = ? AND user_id = ?";
        $order_stmt = $pdo->prepare($order_sql);
        $order_stmt->execute([$order_id, $user_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Get order items with product details
        $items_sql = "SELECT oi.*, p.name as product_name, p.main_image, p.stock_quantity, p.price as original_price, p.discount
                     FROM order_items oi 
                     LEFT JOIN products p ON oi.product_id = p.product_id 
                     WHERE oi.order_id = ?";
        $items_stmt = $pdo->prepare($items_sql);
        $items_stmt->execute([$order_id]);
        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items found in this order']);
            return;
        }

        $added_items = [];
        $unavailable_items = [];

        foreach ($items as $item) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $color = $item['color'];
            $size = $item['size'];

            // Check product availability
            $stock_quantity = $item['stock_quantity'] ?? 0;

            if ($stock_quantity <= 0) {
                $unavailable_items[] = [
                    'product_id' => $product_id,
                    'product_name' => $item['product_name'],
                    'reason' => 'Out of stock'
                ];
                continue;
            }

            // Adjust quantity if exceeds available stock
            $adjusted_quantity = min($quantity, $stock_quantity);

            // This will handle the cart structure correctly
            $result = $functions->addToCart($product_id, $adjusted_quantity, $color, $size);

            if ($result) {
                $added_items[] = [
                    'product_id' => $product_id,
                    'product_name' => $item['product_name'],
                    'quantity' => $adjusted_quantity,
                    'price' => $price
                ];
            } else {
                $unavailable_items[] = [
                    'product_id' => $product_id,
                    'product_name' => $item['product_name'],
                    'reason' => 'Failed to add to cart'
                ];
            }
        }

        // Get updated cart count
        $cart_count = $functions->getCartItemCount();

        $response = [
            'success' => true,
            'message' => 'Items added to cart successfully',
            'added_items' => $added_items,
            'cart_count' => $cart_count
        ];

        if (!empty($unavailable_items)) {
            $response['unavailable_items'] = $unavailable_items;
            if (empty($added_items)) {
                $response['success'] = false;
                $response['message'] = 'No items could be added to cart';
            } else {
                $response['message'] = 'Some items were added to cart, but others are unavailable';
            }
        }

        echo json_encode($response);
    } catch (Exception $e) {
        error_log("Reorder error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to reorder: ' . $e->getMessage()]);
    }
}

// Helper function to get updated cart preview data
function getUpdatedCartPreview()
{
    $cartItems = $_SESSION['cart'] ?? [];
    $previewItems = array_slice($cartItems, 0, 3);
    $itemCount = count($cartItems);
    $totalAmount = 0;

    foreach ($cartItems as $item) {
        $totalAmount += $item['price'] * $item['quantity'];
    }

    // Format the preview items to match what cart.php expects
    $formattedPreview = [];
    foreach ($previewItems as $item) {
        $formattedPreview[] = [
            'key' => $item['cart_key'],
            'name' => $item['name'],
            'price' => floatval($item['price']),
            'quantity' => intval($item['quantity']),
            'image' => $item['image']
        ];
    }

    return [
        'items' => $formattedPreview,
        'item_count' => $itemCount,
        'total' => 'GHS ' . number_format($totalAmount, 2)
    ];
}

/**
 * Track order functionality - Get order status and tracking information
 */
function trackOrder($pdo, $functions)
{
    $order_id = intval($_GET['order_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        return;
    }

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }

    try {
        // Get order details by order_id (not order_number)
        $order_sql = "SELECT 
                o.*, 
                p.provider_reference as transaction_id,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
            FROM orders o
            LEFT JOIN payments p ON o.id = p.order_id
            WHERE o.id = ? AND o.user_id = ?";

        $order_stmt = $pdo->prepare($order_sql);
        $order_stmt->execute([$order_id, $user_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Get order items
        $items_sql = "SELECT oi.*, p.name as product_name, p.main_image as image
                     FROM order_items oi 
                     LEFT JOIN products p ON oi.product_id = p.product_id 
                     WHERE oi.order_id = ?";
        $items_stmt = $pdo->prepare($items_sql);
        $items_stmt->execute([$order_id]);
        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format items
        $formatted_items = array_map(function ($item) {
            $price = floatval($item['price']);
            $quantity = intval($item['quantity']);
            $total_price = $price * $quantity;

            return [
                'order_item_id' => $item['order_item_id'],
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_price' => $price,
                'quantity' => $quantity,
                'total_price' => $total_price,
                'formatted_price' => 'GHS ' . number_format($price, 2),
                'formatted_total' => 'GHS ' . number_format($total_price, 2),
                'color' => $item['color'],
                'size' => $item['size'],
                'image' => $item['image'] ?: 'assets/images/placeholder-product.jpg'
            ];
        }, $items);

        // Format order for tracking response
        $formatted_order = [
            'id' => $order['id'],
            'order_number' => $order['order_number'],
            'customer_name' => $order['customer_name'],
            'customer_email' => $order['customer_email'],
            'customer_phone' => $order['customer_phone'],
            'shipping_address' => [
                'address' => $order['shipping_address'],
                'city' => $order['shipping_city'],
                'region' => $order['shipping_region'],
                'postal_code' => $order['shipping_postal_code']
            ],
            'status' => $order['status'],
            'formatted_status' => ucfirst($order['status']),
            'payment_status' => $order['payment_status'],
            'formatted_payment_status' => ucfirst($order['payment_status']),
            'total_amount' => floatval($order['total_amount']),
            'formatted_total' => $functions->formatPrice($order['total_amount']),
            'item_count' => $order['item_count'],
            'created_at' => $order['order_date'],
            'formatted_date' => date('F j, Y', strtotime($order['order_date'])),
            'payment_info' => [
                'method' => $order['payment_method'],
                'transaction_id' => $order['transaction_id']
            ]
        ];

        echo json_encode([
            'success' => true,
            'order' => $formatted_order,
            'items' => $formatted_items
        ]);
    } catch (Exception $e) {
        error_log("Track order error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to track order: ' . $e->getMessage()]);
    }
}

/**
 * Public track order functionality - Get order status and tracking information without login
 */
function trackOrderPublic($pdo, $functions, $json_data)
{
    // Set proper content type header first
    header('Content-Type: application/json');

    $order_number = trim($json_data['order_number'] ?? '');
    $email = trim($json_data['email'] ?? '');

    if (empty($order_number) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Order number and email are required']);
        exit;
    }

    try {
        // Get order by order number and email (public access)
        // Use the correct column names from the orders table structure
        $sql = "SELECT o.* 
                FROM orders o 
                WHERE o.order_number = ? AND o.customer_email = ?";
        $order_stmt = $pdo->prepare($sql);
        $order_stmt->execute([$order_number, $email]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

        // If not found with exact match, try a LIKE query in case of truncation or encoding issues
        if (!$order) {
            $sql = "SELECT o.* 
                    FROM orders o 
                    WHERE o.order_number LIKE ? AND o.customer_email = ?";
            $order_stmt = $pdo->prepare($sql);
            $order_stmt->execute(['%' . $order_number . '%', $email]);
            $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found or email does not match']);
            exit;
        }

        // Get order items
        $sql = "SELECT oi.*, p.name as product_name, p.main_image as image
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = ?";
        $items_stmt = $pdo->prepare($sql);
        $items_stmt->execute([$order['id']]);
        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format items
        foreach ($items as &$item) {
            $item['formatted_price'] = $functions->formatPrice($item['price']);
            $item['formatted_total'] = $functions->formatPrice($item['price'] * $item['quantity']);
        }

        // Create timeline based on order status
        $timeline = [];
        $currentDate = new DateTime($order['order_date']);

        // Order placed
        $timeline[] = [
            'title' => 'Order Placed',
            'description' => 'Your order has been received and is being processed',
            'formatted_date' => $currentDate->format('M j, Y - g:i A'),
            'completed' => true,
            'notes' => null
        ];

        // Processing
        if (in_array($order['status'], ['processing', 'shipped', 'delivered'])) {
            $currentDate->add(new DateInterval('P1D'));
            $timeline[] = [
                'title' => 'Order Processing',
                'description' => 'Your order is being prepared for shipment',
                'formatted_date' => $currentDate->format('M j, Y - g:i A'),
                'completed' => true,
                'notes' => null
            ];
        }

        // Shipped
        if (in_array($order['status'], ['shipped', 'delivered'])) {
            $currentDate->add(new DateInterval('P2D'));
            $timeline[] = [
                'title' => 'Order Shipped',
                'description' => 'Your order has been shipped and is on its way',
                'formatted_date' => $currentDate->format('M j, Y - g:i A'),
                'completed' => true,
                'notes' => $order['tracking_number'] ?? null
            ];
        }

        // Delivered
        if ($order['status'] === 'delivered') {
            $currentDate->add(new DateInterval('P3D'));
            $timeline[] = [
                'title' => 'Order Delivered',
                'description' => 'Your order has been successfully delivered',
                'formatted_date' => $currentDate->format('M j, Y - g:i A'),
                'completed' => true,
                'notes' => null
            ];
        }

        // Prepare payment info
        $payment_info = [];
        if (!empty($order['payment_method'])) {
            $payment_info['method'] = $order['payment_method'];
            if ($order['payment_method'] === 'mtn_momo' && !empty($order['momo_phone'])) {
                $payment_info['momo_number'] = $order['momo_phone'];
            }
        }

        // Prepare shipping address
        $shipping_address = [
            'address' => $order['shipping_address'] ?? '',
            'city' => $order['shipping_city'] ?? '',
            'region' => $order['shipping_region'] ?? '',
            'postal_code' => $order['shipping_postal_code'] ?? ''
        ];

        // Prepare totals
        $totals = [
            'subtotal' => $functions->formatPrice($order['subtotal']),
            'shipping_cost' => $functions->formatPrice($order['shipping_cost']),
            'tax_amount' => $functions->formatPrice($order['tax_amount']),
            'total_amount' => $functions->formatPrice($order['total_amount'])
        ];

        echo json_encode([
            'success' => true,
            'order' => [
                'id' => $order['id'],
                'order_number' => $order['order_number'],
                'status' => $order['status'] ?? 'pending',
                'formatted_date' => date('M j, Y', strtotime($order['order_date'])),
                'customer_name' => $order['customer_name'],
                'customer_email' => $order['customer_email'],
                'customer_phone' => $order['customer_phone'],
                'payment_info' => $payment_info,
                'shipping_address' => $shipping_address,
                'formatted_totals' => $totals,
                'tracking_number' => $order['tracking_number'] ?? null
            ],
            'items' => $items,
            'timeline' => $timeline
        ]);
        exit;
    } catch (Exception $e) {
        error_log("Track order error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to track order: ' . $e->getMessage()
        ]);
        exit;
    }
}


/**
 * Finalize an order: insert order_items from session cart, decrement product stock,
 * update order and payment statuses, and clear the session cart.
 */
function finalizeOrder($pdo, $order_id, $functions = null, $provider_reference = null)
{
    if (!$order_id) {
        return ['success' => false, 'message' => 'Order ID required'];
    }

    try {
        $pdo->beginTransaction();

        // Check current order status with lock to prevent race conditions
        $check_sql = "SELECT id, status, payment_status FROM orders WHERE id = ? FOR UPDATE";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$order_id]);
        $order = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            try { if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); } } catch (Exception $rbEx) { error_log('Rollback error: ' . $rbEx->getMessage()); }
            return ['success' => false, 'message' => 'Order not found'];
        }

        // If order is already processing or completed, just return success
        if (in_array($order['status'], ['processing', 'completed', 'shipped', 'delivered'])) {
            try { if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); } } catch (Exception $rbEx) { error_log('Rollback error: ' . $rbEx->getMessage()); }
            return ['success' => true, 'message' => 'Order already processed'];
        }

        // Determine if order already has items (created at order creation time)
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM order_items WHERE order_id = ?");
        $count_stmt->execute([$order_id]);
        $count_row = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $items_exist = $count_row && intval($count_row['cnt']) > 0;

        $used_session_cart = false;

        // If items do not exist, try to insert them from session cart
        if (!$items_exist) {
            if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
                try { if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); } } catch (Exception $rbEx) { error_log('Rollback error: ' . $rbEx->getMessage()); }
                return ['success' => false, 'message' => 'No cart data available to insert order items'];
            }

            $order_items_sql = "INSERT INTO order_items (order_id, product_id, quantity, price, color, size) VALUES (?, ?, ?, ?, ?, ?)";
            $order_items_stmt = $pdo->prepare($order_items_sql);

            // Update product stock statement
            $stock_update_sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?";
            $stock_update_stmt = $pdo->prepare($stock_update_sql);

            foreach ($_SESSION['cart'] as $item) {
                // Insert order item
                $order_items_stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                    $item['color'] ?? null,
                    $item['size'] ?? null
                ]);

                // Decrement stock only if sufficient quantity exists
                $stock_update_stmt->execute([
                    $item['quantity'],
                    $item['product_id'],
                    $item['quantity']
                ]);
            }

            $used_session_cart = true;
        }

        // Use lowercase consistently and ensure status is never empty
        $update_order_sql = "UPDATE orders SET status = 'processing', payment_status = 'completed', updated_at = NOW() WHERE id = ?";
        $update_order_stmt = $pdo->prepare($update_order_sql);
        $result = $update_order_stmt->execute([$order_id]);

        if (!$result) {
            throw new Exception('Failed to update order status: ' . implode(', ', $update_order_stmt->errorInfo()));
        }

        // Update payments record
        $update_payment_sql = "UPDATE payments SET payment_status = 'completed', provider_reference = COALESCE(?, provider_reference), updated_at = NOW() WHERE order_id = ?";
        $update_payment_stmt = $pdo->prepare($update_payment_sql);
        $result = $update_payment_stmt->execute([$provider_reference, $order_id]);

        if (!$result) {
            throw new Exception('Failed to update payment record: ' . implode(', ', $update_payment_stmt->errorInfo()));
        }

        $pdo->commit();

        // Clear cart only if we used the session cart to insert items
        if ($used_session_cart) {
            $_SESSION['cart'] = [];
        }

        error_log("Order $order_id finalized successfully - status: processing, payment_status: completed");
        return ['success' => true, 'message' => 'Order finalized successfully'];
    } catch (Exception $e) {
            try { 
                if ($pdo && $pdo->inTransaction()) { 
                    $pdo->rollBack(); 
                } 
            } catch (Exception $rbEx) { 
                error_log('Rollback error during finalizeOrder: ' . $rbEx->getMessage()); 
            }
        error_log("Finalize order error for $order_id: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to finalize order: ' . $e->getMessage()];
    }
}

function deleteOrder($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    // Get JSON data
    $json_data = json_decode(file_get_contents('php://input'), true);
    $order_id = intval($json_data['order_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        return;
    }

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Check if order exists and belongs to user
        $check_sql = "SELECT id, status FROM orders WHERE id = ? AND user_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$order_id, $user_id]);
        $order = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            try { if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); } } catch (Exception $rbEx) { error_log('Rollback error: ' . $rbEx->getMessage()); }
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Only allow deletion of pending or cancelled orders for safety
        // You can remove this restriction if you want to allow deletion of any order
        if (!in_array($order['status'], ['pending', 'cancelled'])) {
            try { if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); } } catch (Exception $rbEx) { error_log('Rollback error: ' . $rbEx->getMessage()); }
            echo json_encode(['success' => false, 'message' => 'Only pending or cancelled orders can be deleted']);
            return;
        }

        // Delete order items first (due to foreign key constraints)
        $delete_items_sql = "DELETE FROM order_items WHERE order_id = ?";
        $delete_items_stmt = $pdo->prepare($delete_items_sql);
        $delete_items_stmt->execute([$order_id]);

        // Delete payment record
        $delete_payment_sql = "DELETE FROM payments WHERE order_id = ?";
        $delete_payment_stmt = $pdo->prepare($delete_payment_sql);
        $delete_payment_stmt->execute([$order_id]);

        // Delete the order
        $delete_order_sql = "DELETE FROM orders WHERE id = ?";
        $delete_order_stmt = $pdo->prepare($delete_order_sql);
        $delete_order_stmt->execute([$order_id]);

        try { if ($pdo && $pdo->inTransaction()) { $pdo->commit(); } } catch (Exception $commitEx) { error_log('Commit error: ' . $commitEx->getMessage()); throw $commitEx; }

        echo json_encode([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    } catch (Exception $e) {
        try { if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); } } catch (Exception $rbEx) { error_log('Rollback error during deleteOrder: ' . $rbEx->getMessage()); }
        error_log("Delete order error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete order: ' . $e->getMessage()]);
    }
}


function initiatePayment($pdo, $json_data)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    $order_id = intval($json_data['order_id'] ?? 0);
    $amount = floatval($json_data['amount'] ?? 0);
    $phone = trim($json_data['phone'] ?? '');
    $provider = trim($json_data['provider'] ?? 'mtn');

    error_log("Payment initiation - Order: $order_id, Amount: $amount, Phone: $phone, Provider: $provider");

    if (!$order_id || !$amount || !$phone) {
        echo json_encode(['success' => false, 'message' => 'Missing required payment data: order_id=' . $order_id . ', amount=' . $amount . ', phone=' . $phone]);
        return;
    }

    try {
        require_once __DIR__ . '/../includes/PaymentProcessor.php';

        // Fetch order details including customer email
        $order_sql = "SELECT customer_email, customer_name FROM orders WHERE id = ?";
        $order_stmt = $pdo->prepare($order_sql);
        $order_stmt->execute([$order_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        $customer_email = $order ? $order['customer_email'] : null;
        $customer_name = $order ? $order['customer_name'] : null;

        // Normalize phone server-side
        $formatted_phone = preg_replace('/\D/', '', $phone);
        if (strlen($formatted_phone) === 10 && substr($formatted_phone, 0, 1) === '0') {
            $formatted_phone = '233' . substr($formatted_phone, 1);
        } elseif (strlen($formatted_phone) === 9) {
            $formatted_phone = '233' . $formatted_phone;
        }

        error_log("Formatted phone to send to provider: $formatted_phone");
        error_log("Customer email: $customer_email");

        $paymentProcessor = new PaymentProcessor();
        $result = $paymentProcessor->initializeMobileMoneyPayment(
            $order_id,
            $amount,
            $formatted_phone,
            $provider,
            $customer_email
        );

        error_log("Payment initiation result: " . json_encode($result));

        // Extract provider reference
        $provider_ref = null;
        if (is_array($result)) {
            if (isset($result['data']['reference'])) $provider_ref = $result['data']['reference'];
            elseif (isset($result['data']['id'])) $provider_ref = $result['data']['id'];
            elseif (isset($result['reference'])) $provider_ref = $result['reference'];
            elseif (isset($result['payment_reference'])) $provider_ref = $result['payment_reference'];
        }

        // Determine payment state
        $dataStatus = null;
        if (is_array($result)) {
            if (isset($result['data']['status'])) $dataStatus = strtolower($result['data']['status']);
            elseif (isset($result['status'])) $dataStatus = is_bool($result['status']) ? ($result['status'] ? 'success' : 'failed') : strtolower($result['status']);
        }

        $payment_state = 'pending';
        $requires_otp = false;

        if (in_array($dataStatus, ['success', 'completed', 'paid'])) {
            $payment_state = 'completed';
        } elseif (in_array($dataStatus, ['send_otp', 'requires_otp']) || (isset($result['requires_otp']) && $result['requires_otp'])) {
            $payment_state = 'otp_required';
            $requires_otp = true;
        } elseif (in_array($dataStatus, ['failed', 'error'])) {
            $payment_state = 'failed';
        }

        // IMPORTANT: Update payments table with OTP requirement status
        try {
            $update_sql = "UPDATE payments SET provider_reference = ?, payment_status = ?, requires_otp = ?, updated_at = NOW() WHERE order_id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$provider_ref, $payment_state, $requires_otp ? 1 : 0, $order_id]);
        } catch (Exception $e) {
            error_log("Failed to update payments record for order $order_id: " . $e->getMessage());
        }

        $finalized = false;

        // If payment was immediately successful, update order status
        if ($payment_state === 'completed') {
            try {
                // Update order status
                $update_order_sql = "UPDATE orders SET status = 'processing', payment_status = 'completed', updated_at = NOW() WHERE id = ?";
                $update_order_stmt = $pdo->prepare($update_order_sql);
                $update_order_stmt->execute([$order_id]);
                
                // Send confirmation emails
                try {
                    require_once __DIR__ . '/../includes/email_helper.php';
                    
                    // Send to customer
                    $customer_email_sent = EmailHelper::sendOrderConfirmation(
                        $pdo,
                        $GLOBALS['functions'] ?? null,
                        $order_id,
                        $customer_email,
                        $customer_name
                    );
                    
                    if ($customer_email_sent) {
                        error_log("Immediate payment: Order confirmation email sent to customer for order $order_id");
                    }
                    
                    // Send to shop owner
                    $admin_email = $_ENV['ADMIN_EMAIL'] ?? $_ENV['SHOP_OWNER_EMAIL'] ?? $_ENV['MAIL_USER'] ?? null;
                    if ($admin_email) {
                        $owner_notification_sent = EmailHelper::sendNewOrderNotification(
                            $pdo,
                            $GLOBALS['functions'] ?? null,
                            $order_id,
                            $admin_email
                        );
                        
                        if ($owner_notification_sent) {
                            error_log("Immediate payment: Shop owner notification sent for order $order_id");
                        }
                    }
                } catch (Exception $e) {
                    error_log("Email sending error after immediate payment: " . $e->getMessage());
                }
                
                $finalized = true;
            } catch (Exception $e) {
                error_log("Error updating order $order_id after immediate payment: " . $e->getMessage());
            }
        }

        // Return comprehensive response including OTP requirement
        echo json_encode([
            'success' => true,
            'payment' => $result,
            'sent_phone' => $formatted_phone,
            'provider_reference' => $provider_ref,
            'requires_otp' => $requires_otp,
            'finalized' => $finalized,
            'payment_status' => $payment_state
        ]);
    } catch (Exception $e) {
        error_log("Payment initiation error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Payment Error: ' . $e->getMessage()]);
    }
}

function submitOtp($pdo, $json_data)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    $order_id = intval($json_data['order_id'] ?? 0);
    $reference = trim($json_data['reference'] ?? '');
    $otp = trim($json_data['otp'] ?? '');

    if (!$order_id || !$reference || !$otp) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields for OTP submission']);
        return;
    }

    try {
        require_once __DIR__ . '/../includes/PaymentProcessor.php';
        $pp = new PaymentProcessor();

        // Get payment record
        $db = new Database();
        $payment_sql = "SELECT * FROM payments WHERE order_id = ? LIMIT 1";
        $payment_stmt = $db->getConnection()->prepare($payment_sql);
        $payment_stmt->execute([$order_id]);
        $payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            throw new Exception('Payment record not found for this order');
        }

        $verification = $pp->submitOtpAndVerifyPayment($reference, $otp);
        error_log("OTP verification result: " . json_encode($verification));

        // Update payment status based on verification - use lowercase consistently
        $payment_status = 'pending';
        if (isset($verification['paid']) && $verification['paid'] === true) {
            $payment_status = 'completed';
            
            // Update payment record
            $pp->updatePaymentStatus($payment['payment_id'], $payment_status, ['reference' => $reference, 'status' => $verification['status'] ?? 'completed']);
            
            // CRITICAL FIX: Update order status to processing and payment_status to completed
            $update_order_sql = "UPDATE orders SET status = 'processing', payment_status = 'completed', updated_at = NOW() WHERE id = ?";
            $update_order_stmt = $pdo->prepare($update_order_sql);
            $update_order_stmt->execute([$order_id]);
            
            // Send order confirmation email to customer
            try {
                // Get order details
                $order_sql = "SELECT customer_email, customer_name, order_number FROM orders WHERE id = ?";
                $order_stmt = $pdo->prepare($order_sql);
                $order_stmt->execute([$order_id]);
                $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {
                    // Send to customer
                    require_once __DIR__ . '/../includes/email_helper.php';
                    $customer_email_sent = EmailHelper::sendOrderConfirmation(
                        $pdo,
                        $GLOBALS['functions'] ?? null,
                        $order_id,
                        $order['customer_email'],
                        $order['customer_name']
                    );
                    
                    if ($customer_email_sent) {
                        error_log("Order confirmation email sent to customer for order $order_id");
                    }
                    
                    // Send to shop owner
                    $admin_email = $_ENV['ADMIN_EMAIL'] ?? $_ENV['SHOP_OWNER_EMAIL'] ?? $_ENV['MAIL_USER'] ?? null;
                    if ($admin_email) {
                        $owner_notification_sent = EmailHelper::sendNewOrderNotification(
                            $pdo,
                            $GLOBALS['functions'] ?? null,
                            $order_id,
                            $admin_email
                        );
                        
                        if ($owner_notification_sent) {
                            error_log("Shop owner notification sent for order $order_id after payment");
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Email sending error after OTP payment: " . $e->getMessage());
                // Don't fail the payment if email fails
            }
            
            // Clear cart if it exists in session
            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
                error_log("Cart cleared after successful payment for order $order_id");
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Payment verified successfully and order updated to processing', 
                'paid' => true, 
                'order_id' => $order_id,
                'order_number' => $order['order_number'] ?? null
            ]);
        } else {
            // OTP submitted but payment still processing
            echo json_encode([
                'success' => true, 
                'message' => 'OTP submitted. Waiting for provider confirmation...', 
                'paid' => false, 
                'order_id' => $order_id, 
                'polling' => true
            ]);
        }
    } catch (Exception $e) {
        error_log('OTP submission error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'OTP submission failed: ' . $e->getMessage()]);
    }
}

function resendOtp($pdo, $json_data)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    $reference = trim($json_data['reference'] ?? '');
    if (!$reference) {
        echo json_encode(['success' => false, 'message' => 'Missing reference']);
        return;
    }

    try {
        require_once __DIR__ . '/../includes/PaymentProcessor.php';
        $pp = new PaymentProcessor();
        $res = $pp->resendOtp($reference);
        echo json_encode(['success' => true, 'message' => $res['message'] ?? 'OTP resent']);
    } catch (Exception $e) {
        error_log('Resend OTP error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to resend OTP: ' . $e->getMessage()]);
    }
}
// End of ajax/orders.php