<?php
// Usage: php tools\\send_test_email.php <order_id> [recipient_email]
// Sends the order confirmation email using EmailHelper for quick testing.

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email_helper.php';

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(2);
}

$order_id = isset($argv[1]) ? intval($argv[1]) : 0;
$override_to = isset($argv[2]) ? trim($argv[2]) : null;

if (!$order_id) {
    echo "Usage: php tools/send_test_email.php <order_id> [recipient_email]\n";
    exit(2);
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        echo "Failed to get DB connection.\n";
        exit(1);
    }

    // Build Functions helper
    $functions = new Functions();
    $functions->setDatabase($database);

    // Determine recipient if not provided
    if (empty($override_to)) {
        $stmt = $pdo->prepare('SELECT customer_email, customer_name FROM orders WHERE id = ?');
        $stmt->execute([$order_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['customer_email'])) {
            echo "Order not found or no customer email on order $order_id. Provide recipient explicitly.\n";
            exit(1);
        }
        $to = $row['customer_email'];
        $name = $row['customer_name'] ?? '';
    } else {
        $to = $override_to;
        // try to fetch name if exists
        $stmt = $pdo->prepare('SELECT customer_name FROM orders WHERE id = ?');
        $stmt->execute([$order_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $name = $row['customer_name'] ?? '';
    }

    echo "Sending test order confirmation for order $order_id to: $to\n";

    $result = EmailHelper::sendOrderConfirmation($pdo, $functions, $order_id, $to, $name);

    if ($result) {
        echo "EmailHelper reported success. Check Apache/PHP error log for PHPMailer debug lines.\n";
        exit(0);
    } else {
        echo "EmailHelper reported failure. Check Apache/PHP error log for detailed errors.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    exit(1);
}
