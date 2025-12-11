<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email_helper.php'; // Add this

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? '';

// Parse JSON request body
$json_data = [];
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $json_body = file_get_contents('php://input');
    $json_data = json_decode($json_body, true) ?? [];
}

switch ($action) {
    case 'get_paystack_config':
        getPaystackConfig();
        break;
    case 'verify_payment':
        verifyPayment($pdo, $json_data);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getPaystackConfig() {
    echo json_encode([
        'success' => true,
        'public_key' => PAYSTACK_PUBLIC_KEY
    ]);
}

function verifyPayment($pdo, $json_data) {
    $order_id = intval($json_data['order_id'] ?? 0);
    $reference = trim($json_data['reference'] ?? '');

    if (!$order_id || !$reference) {
        echo json_encode(['success' => false, 'message' => 'Missing order ID or payment reference']);
        return;
    }

    try {
        require_once __DIR__ . '/../includes/PaymentProcessor.php';
        $paymentProcessor = new PaymentProcessor('paystack');
        
        // Verify payment with Paystack
        $verification = $paymentProcessor->verifyPayment($reference);
        
        if ($verification['paid']) {
            // Update payment status - USE LOWERCASE
            $update_sql = "UPDATE payments SET 
                          payment_status = 'completed', 
                          provider_reference = ?,
                          updated_at = NOW()
                          WHERE order_id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$reference, $order_id]);

            // Update order status - USE LOWERCASE
            $order_sql = "UPDATE orders SET 
                         status = 'processing', 
                         payment_status = 'completed',
                         updated_at = NOW()
                         WHERE id = ?";
            $order_stmt = $pdo->prepare($order_sql);
            $order_stmt->execute([$order_id]);

            // Send order confirmation emails
            sendOrderConfirmationEmails($pdo, $order_id, $reference);

            echo json_encode([
                'success' => true,
                'message' => 'Payment verified successfully',
                'order_id' => $order_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Payment verification failed: ' . ($verification['message'] ?? 'Unknown error')
            ]);
        }

    } catch (Exception $e) {
        error_log("Payment verification error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Payment verification failed: ' . $e->getMessage()]);
    }
}

// New function to send confirmation emails after payment
function sendOrderConfirmationEmails($pdo, $order_id, $reference) {
    try {
        // Get order details
        $order_sql = "SELECT customer_email, customer_name, order_number FROM orders WHERE id = ?";
        $order_stmt = $pdo->prepare($order_sql);
        $order_stmt->execute([$order_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order || empty($order['customer_email'])) {
            error_log("PAYMENT VERIFIED: Could not find customer email for order $order_id");
            return;
        }
        
        // We need a Functions instance for email helper
        $database = new Database();
        $functions = new Functions();
        $functions->setDatabase($database);
        
        // Send confirmation to customer
        $customer_email_sent = EmailHelper::sendOrderConfirmation(
            $pdo,
            $functions,
            $order_id,
            $order['customer_email'],
            $order['customer_name']
        );
        
        if ($customer_email_sent) {
            error_log("PAYMENT VERIFIED: Order confirmation email sent to customer for order $order_id");
        } else {
            error_log("PAYMENT VERIFIED: Failed to send order confirmation email for order $order_id");
        }
        
        // Send notification to shop owner/admin
        $admin_email = $_ENV['ADMIN_EMAIL'] ?? $_ENV['SHOP_OWNER_EMAIL'] ?? $_ENV['MAIL_USER'] ?? null;
        if ($admin_email) {
            $owner_notification_sent = EmailHelper::sendNewOrderNotification(
                $pdo,
                $functions,
                $order_id,
                $admin_email
            );
            
            if ($owner_notification_sent) {
                error_log("PAYMENT VERIFIED: Shop owner notification sent for order $order_id");
            } else {
                error_log("PAYMENT VERIFIED: Failed to send shop owner notification for order $order_id");
            }
        } else {
            error_log("PAYMENT VERIFIED: No admin email configured for order $order_id");
        }
        
        // Clear cart if it exists in session
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
            error_log("PAYMENT VERIFIED: Cart cleared after successful payment for order $order_id");
        }
        
    } catch (Exception $e) {
        // Log email error but don't fail the payment verification
        error_log("PAYMENT VERIFIED: Failed to send order confirmation emails for order $order_id: " . $e->getMessage());
    }
}
?>