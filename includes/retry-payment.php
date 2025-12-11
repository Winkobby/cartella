<?php
require_once __DIR__ . '/config.php'; // Added __DIR__
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/PaymentProcessor.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'] ?? 0;
$payment_reference = $input['payment_reference'] ?? '';

try {
    if (empty($order_id) || empty($payment_reference)) {
        throw new Exception('Order ID and payment reference are required');
    }
    
    // Get payment details
    $db = new Database();
    $payment = $db->fetchSingle(
        "SELECT * FROM payments WHERE order_id = ? AND provider_reference = ?",
        [$order_id, $payment_reference]
    );
    
    if (!$payment) {
        throw new Exception('Payment not found');
    }
    
    // Retry payment with provider
    $payment_processor = new PaymentProcessor();
    if (defined('LOG_PATH')) {
        @file_put_contents(LOG_PATH . '/payments.log', date('Y-m-d H:i:s') . " | RETRY | REF $payment_reference | ORDER $order_id" . "\n", FILE_APPEND);
    }

    $result = $payment_processor->retryMobileMoneyPayment($payment_reference);
    
    if ($result['success']) {
        $new_ref = $result['payment_reference'] ?? null;
        if ($new_ref) {
            $db->execute(
                "UPDATE payments SET provider_reference = ? WHERE payment_id = ?",
                [$new_ref, $payment['payment_id']]
            );
        }
        if (defined('LOG_PATH')) {
            @file_put_contents(LOG_PATH . '/payments.log', date('Y-m-d H:i:s') . " | RETRY_SUCCESS | ORDER $order_id | NEW_REF " . ($new_ref ?? 'N/A') . " | PROVIDER_DATA " . json_encode($result['provider_data'] ?? []) . "\n", FILE_APPEND);
        }
        echo json_encode([
            'success' => true,
            'message' => $result['message'] ?? 'Payment request has been resent to your mobile number.',
            'payment_reference' => $new_ref
        ]);
    } else {
        if (defined('LOG_PATH')) {
            @file_put_contents(LOG_PATH . '/payments.log', date('Y-m-d H:i:s') . " | RETRY_FAILED | ORDER $order_id | MESSAGE " . ($result['message'] ?? 'Unknown') . "\n", FILE_APPEND);
        }
        throw new Exception($result['message'] ?? 'Failed to resend payment request');
    }
    
} catch (Exception $e) {
    error_log("Retry payment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error resending payment: ' . $e->getMessage()
    ]);
}
?>