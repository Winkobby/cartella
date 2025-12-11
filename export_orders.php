<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied';
    exit;
}

$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Collect same filters as a_orders
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "SELECT 
            o.id,
            o.order_number,
            o.customer_name,
            o.customer_email,
            o.customer_phone,
            o.shipping_address,
            o.shipping_city,
            o.shipping_region,
            o.shipping_postal_code,
            o.payment_method,
            COALESCE(p.payment_status, '') as payment_status,
            o.subtotal,
            o.shipping_cost,
            o.tax_amount,
            o.discount_amount,
            o.total_amount,
            o.status,
            o.order_date,
            COUNT(oi.order_item_id) as item_count
          FROM orders o
          LEFT JOIN payments p ON o.id = p.order_id
          LEFT JOIN order_items oi ON o.id = oi.order_id
          WHERE 1=1";

$params = [];
if (!empty($search)) {
    $query .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
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

$query .= " GROUP BY o.id ORDER BY o.order_date DESC";

try {
    $stmt = $database->getConnection()->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send CSV headers
    $filename = 'orders_export_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Output UTF-8 BOM for Excel compatibility
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    // Header row
    fputcsv($out, [
        'Order ID', 'Order Number', 'Order Date', 'Customer Name', 'Customer Email', 'Customer Phone',
        'Shipping Address', 'Shipping City', 'Shipping Region', 'Shipping Postal Code',
        'Payment Method', 'Payment Status', 'Item Count', 'Subtotal', 'Shipping Cost', 'Tax', 'Discount', 'Total', 'Status'
    ]);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['order_number'],
            $r['order_date'],
            $r['customer_name'],
            $r['customer_email'],
            $r['customer_phone'],
            $r['shipping_address'],
            $r['shipping_city'],
            $r['shipping_region'],
            $r['shipping_postal_code'],
            $r['payment_method'],
            $r['payment_status'],
            $r['item_count'],
            $r['subtotal'],
            $r['shipping_cost'],
            $r['tax_amount'],
            $r['discount_amount'],
            $r['total_amount'],
            $r['status']
        ]);
    }

    fclose($out);
    exit;
} catch (PDOException $e) {
    error_log('Export error: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Export failed';
    exit;
}

?>
