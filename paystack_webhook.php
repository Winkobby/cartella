<?php
// paystack_webhook.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Log the incoming webhook
$input = @file_get_contents("php://input");
error_log("Paystack Webhook Received: " . $input);

// Verify it's from Paystack
$secret = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
if (empty($secret)) {
    http_response_code(403);
    error_log("Paystack secret key not configured");
    exit;
}

if (isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'])) {
    $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'];
    $computedSignature = hash_hmac('sha512', $input, $secret);
    
    if ($signature !== $computedSignature) {
        http_response_code(403);
        error_log("Invalid webhook signature");
        exit;
    }
}

// Parse the webhook data
$event = json_decode($input, true);

if ($event['event'] === 'charge.success') {
    $reference = $event['data']['reference'];
    $amount = $event['data']['amount'] / 100; // Convert from kobo
    $status = $event['data']['status'];
    $metadata = $event['data']['metadata'] ?? [];
    
    error_log("Processing successful charge for reference: $reference, amount: $amount, status: $status");
    
    // Find the order by reference
    $database = new Database();
    $pdo = $database->getConnection();
    
    try {
        // Find payment by provider_reference
        $payment_sql = "SELECT p.*, o.customer_email, o.customer_name, o.order_number 
                       FROM payments p 
                       JOIN orders o ON p.order_id = o.id 
                       WHERE p.provider_reference = ?";
        $payment_stmt = $pdo->prepare($payment_sql);
        $payment_stmt->execute([$reference]);
        $payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            $order_id = $payment['order_id'];
            
            // Update payment status
            $update_payment_sql = "UPDATE payments SET payment_status = 'completed', updated_at = NOW() WHERE provider_reference = ?";
            $update_payment_stmt = $pdo->prepare($update_payment_sql);
            $update_payment_stmt->execute([$reference]);
            
            // Update order status (if still pending)
            if ($payment['payment_status'] === 'pending') {
                $update_order_sql = "UPDATE orders SET status = 'processing', payment_status = 'completed', updated_at = NOW() WHERE id = ?";
                $update_order_stmt = $pdo->prepare($update_order_sql);
                $update_order_stmt->execute([$order_id]);
                
                error_log("Webhook: Order $order_id updated to processing via webhook");
                
                // Send emails
                try {
                    require_once __DIR__ . '/includes/email_helper.php';
                    $functions = new Functions();
                    
                    // Send confirmation to customer
                    $customer_email_sent = EmailHelper::sendOrderConfirmation(
                        $pdo,
                        $functions,
                        $order_id,
                        $payment['customer_email'],
                        $payment['customer_name']
                    );
                    
                    if ($customer_email_sent) {
                        error_log("Webhook: Order confirmation email sent to customer for order $order_id");
                    }
                    
                    // Send notification to shop owner
                    $admin_email = $_ENV['ADMIN_EMAIL'] ?? $_ENV['MAIL_USER'] ?? null;
                    if ($admin_email) {
                        $owner_notification_sent = EmailHelper::sendNewOrderNotification(
                            $pdo,
                            $functions,
                            $order_id,
                            $admin_email
                        );
                        
                        if ($owner_notification_sent) {
                            error_log("Webhook: Shop owner notification sent for order $order_id");
                        }
                    }
                } catch (Exception $e) {
                    error_log("Webhook email error: " . $e->getMessage());
                }
            } else {
                error_log("Webhook: Order $order_id already has payment status: " . $payment['payment_status']);
            }
        } else {
            error_log("Webhook: No payment found for reference: $reference");
            
            // Try to find by order_id in metadata
            if (isset($metadata['order_id'])) {
                $order_id = $metadata['order_id'];
                
                $order_sql = "SELECT * FROM orders WHERE id = ?";
                $order_stmt = $pdo->prepare($order_sql);
                $order_stmt->execute([$order_id]);
                $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {
                    // Update order and payment status
                    $update_order_sql = "UPDATE orders SET status = 'processing', payment_status = 'completed', updated_at = NOW() WHERE id = ?";
                    $update_order_stmt = $pdo->prepare($update_order_sql);
                    $update_order_stmt->execute([$order_id]);
                    
                    // Update or create payment record
                    $payment_sql = "UPDATE payments SET payment_status = 'completed', provider_reference = ?, updated_at = NOW() WHERE order_id = ?";
                    $payment_stmt = $pdo->prepare($payment_sql);
                    $payment_stmt->execute([$reference, $order_id]);
                    
                    error_log("Webhook: Updated order $order_id using metadata order_id");
                }
            }
        }
    } catch (Exception $e) {
        error_log("Webhook processing error: " . $e->getMessage());
    }
}

// Always return 200 to acknowledge receipt
http_response_code(200);
echo json_encode(['status' => 'received']);
?>