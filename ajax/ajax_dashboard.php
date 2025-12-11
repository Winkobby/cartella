<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Get the requested data type
$data_type = $_GET['type'] ?? 'all';

try {
    $response = [];
    
    switch($data_type) {
        case 'stats':
            // Order Statistics
            $order_stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
                COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                COUNT(CASE WHEN payment_status = 'completed' THEN 1 END) as completed_payments,
                COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_payments
            FROM orders";
            $order_stats_stmt = $database->getConnection()->prepare($order_stats_query);
            $order_stats_stmt->execute();
            $response['order_stats'] = $order_stats_stmt->fetch(PDO::FETCH_ASSOC);

            // Product Statistics
            $product_stats_query = "SELECT 
                COUNT(*) as total_products,
                SUM(stock_quantity) as total_stock,
                COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN is_new = 1 THEN 1 END) as new_products
            FROM products";
            $product_stats_stmt = $database->getConnection()->prepare($product_stats_query);
            $product_stats_stmt->execute();
            $response['product_stats'] = $product_stats_stmt->fetch(PDO::FETCH_ASSOC);

            // User Statistics
            $user_stats_query = "SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN role = 'Admin' THEN 1 END) as admin_users,
                COUNT(CASE WHEN role = 'Customer' THEN 1 END) as customer_users,
                COUNT(CASE WHEN DATE(date_joined) = CURDATE() THEN 1 END) as new_today
            FROM users";
            $user_stats_stmt = $database->getConnection()->prepare($user_stats_query);
            $user_stats_stmt->execute();
            $response['user_stats'] = $user_stats_stmt->fetch(PDO::FETCH_ASSOC);
            break;

        case 'recent_orders':
            // Recent Orders (last 7 days)
            $recent_orders_query = "SELECT 
                o.id, o.order_number, o.customer_name, o.total_amount, o.status, o.order_date,
                p.payment_status
            FROM orders o 
            LEFT JOIN payments p ON o.id = p.order_id 
            WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY o.order_date DESC 
            LIMIT 10";
            $recent_orders_stmt = $database->getConnection()->prepare($recent_orders_query);
            $recent_orders_stmt->execute();
            $response['recent_orders'] = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'top_products':
            // Top selling products
            $top_products_query = "SELECT 
                p.name, p.sku, p.main_image,
                SUM(oi.quantity) as total_sold,
                SUM(oi.quantity * oi.price) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.payment_status = 'completed'
            GROUP BY p.product_id, p.name, p.sku, p.main_image
            ORDER BY total_sold DESC 
            LIMIT 10";
            $top_products_stmt = $database->getConnection()->prepare($top_products_query);
            $top_products_stmt->execute();
            $response['top_products'] = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'revenue':
            // Revenue by day (last 7 days)
            $revenue_query = "SELECT 
                DATE(order_date) as date,
                COUNT(*) as order_count,
                SUM(total_amount) as daily_revenue
            FROM orders 
            WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                AND payment_status = 'completed'
            GROUP BY DATE(order_date) 
            ORDER BY date DESC";
            $revenue_stmt = $database->getConnection()->prepare($revenue_query);
            $revenue_stmt->execute();
            $response['revenue_data'] = $revenue_stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'all':
        default:
            // Load all data
            // Order Statistics
            $order_stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
                COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                COUNT(CASE WHEN payment_status = 'completed' THEN 1 END) as completed_payments,
                COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_payments
            FROM orders";
            $order_stats_stmt = $database->getConnection()->prepare($order_stats_query);
            $order_stats_stmt->execute();
            $response['order_stats'] = $order_stats_stmt->fetch(PDO::FETCH_ASSOC);

            // Product Statistics
            $product_stats_query = "SELECT 
                COUNT(*) as total_products,
                SUM(stock_quantity) as total_stock,
                COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN is_new = 1 THEN 1 END) as new_products
            FROM products";
            $product_stats_stmt = $database->getConnection()->prepare($product_stats_query);
            $product_stats_stmt->execute();
            $response['product_stats'] = $product_stats_stmt->fetch(PDO::FETCH_ASSOC);

            // User Statistics
            $user_stats_query = "SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN role = 'Admin' THEN 1 END) as admin_users,
                COUNT(CASE WHEN role = 'Customer' THEN 1 END) as customer_users,
                COUNT(CASE WHEN DATE(date_joined) = CURDATE() THEN 1 END) as new_today
            FROM users";
            $user_stats_stmt = $database->getConnection()->prepare($user_stats_query);
            $user_stats_stmt->execute();
            $response['user_stats'] = $user_stats_stmt->fetch(PDO::FETCH_ASSOC);

            // Recent Orders (last 7 days)
            $recent_orders_query = "SELECT 
                o.id, o.order_number, o.customer_name, o.total_amount, o.status, o.order_date,
                p.payment_status
            FROM orders o 
            LEFT JOIN payments p ON o.id = p.order_id 
            WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY o.order_date DESC 
            LIMIT 10";
            $recent_orders_stmt = $database->getConnection()->prepare($recent_orders_query);
            $recent_orders_stmt->execute();
            $response['recent_orders'] = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Revenue by day (last 7 days)
            $revenue_query = "SELECT 
                DATE(order_date) as date,
                COUNT(*) as order_count,
                SUM(total_amount) as daily_revenue
            FROM orders 
            WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                AND payment_status = 'completed'
            GROUP BY DATE(order_date) 
            ORDER BY date DESC";
            $revenue_stmt = $database->getConnection()->prepare($revenue_query);
            $revenue_stmt->execute();
            $response['revenue_data'] = $revenue_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Top selling products
            $top_products_query = "SELECT 
                p.name, p.sku, p.main_image,
                SUM(oi.quantity) as total_sold,
                SUM(oi.quantity * oi.price) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.payment_status = 'completed'
            GROUP BY p.product_id, p.name, p.sku, p.main_image
            ORDER BY total_sold DESC 
            LIMIT 10";
            $top_products_stmt = $database->getConnection()->prepare($top_products_query);
            $top_products_stmt->execute();
            $response['top_products'] = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }

    echo json_encode(['success' => true, 'data' => $response]);

} catch (PDOException $e) {
    error_log("Dashboard API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to load dashboard data']);
}
?>
