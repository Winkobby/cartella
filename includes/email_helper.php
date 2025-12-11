<?php
class EmailHelper {
    private static $errorBuffer = '';
    
    public static function sendOrderConfirmation($pdo, $functions, $order_id, $customer_email, $customer_name) {
        // Capture all errors to buffer
        self::$errorBuffer = '';
        set_error_handler(function($severity, $message, $file, $line) {
            self::$errorBuffer .= "Error: $message in $file on line $line\n";
            return true; // Suppress error display
        });
        
        try {
            // Check if PHPMailerWrapper is available
            if (!class_exists('PHPMailerWrapper')) {
                // Try to include it directly
                $phpmailerPath = __DIR__ . '/PHPMailerWrapper.php';
                if (file_exists($phpmailerPath)) {
                    require_once $phpmailerPath;
                } else {
                    error_log("PHPMailerWrapper file not found at: $phpmailerPath");
                    restore_error_handler();
                    return false;
                }
            }
            
            // Get order details
            $order_sql = "SELECT o.*, p.provider_reference as transaction_id
                        FROM orders o
                        LEFT JOIN payments p ON o.id = p.order_id
                        WHERE o.id = ?";
            
            $order_stmt = $pdo->prepare($order_sql);
            $order_stmt->execute([$order_id]);
            $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                error_log("Order $order_id not found for email");
                restore_error_handler();
                return false;
            }
            
            // Get order items
            $items_sql = "SELECT oi.*, p.name as product_name, p.main_image as image
                         FROM order_items oi 
                         LEFT JOIN products p ON oi.product_id = p.product_id 
                         WHERE oi.order_id = ?";
            
            $items_stmt = $pdo->prepare($items_sql);
            $items_stmt->execute([$order_id]);
            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($items)) {
                error_log("No items found for order $order_id");
                restore_error_handler();
                return false;
            }
            
            // Format items for email
            $formatted_items = [];
            foreach ($items as $item) {
                $price = floatval($item['price']);
                $quantity = intval($item['quantity']);
                $total_price = $price * $quantity;
                
                $formatted_items[] = [
                    'name' => $item['product_name'] ?? 'Unknown Product',
                    'price' => $price,
                    'formatted_price' => $functions->formatPrice($price),
                    'quantity' => $quantity,
                    'total_price' => $total_price,
                    'formatted_total' => $functions->formatPrice($total_price),
                    'color' => $item['color'] ?? '',
                    'size' => $item['size'] ?? '',
                    'image' => $item['image'] ?? 'assets/images/placeholder-product.jpg'
                ];
            }
            
            // Generate email HTML
            $email_html = self::createSimpleOrderEmail($order, $formatted_items, $functions);
            
            // Try to send email
            $email_sent = false;
            try {
                $mailer = new PHPMailerWrapper();
                $subject = "Order Confirmation - " . $order['order_number'];
                $email_sent = $mailer->sendHtml($customer_email, $subject, $email_html);
                
                if ($email_sent) {
                    error_log("Email sent successfully to $customer_email for order $order_id");
                } else {
                    error_log("Failed to send email to $customer_email for order $order_id");
                }
            } catch (Exception $e) {
                self::$errorBuffer .= "Mailer error: " . $e->getMessage() . "\n";
                $email_sent = false;
            }
            
            restore_error_handler();
            
            if (!empty(self::$errorBuffer)) {
                error_log("Email helper errors for order $order_id: " . self::$errorBuffer);
            }
            
            return $email_sent;
        } catch (Exception $e) {
            restore_error_handler();
            error_log("Email helper exception for order $order_id: " . $e->getMessage());
            return false;
        }
    }
    
    private static function createSimpleOrderEmail($order, $items, $functions) {
        $order_number = htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8');
        $customer_name = htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8');
        $order_date = date('F j, Y', strtotime($order['order_date']));
        
        $items_html = '';
        $subtotal = 0;
        foreach ($items as $item) {
            $name = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
            $items_html .= "<tr><td>{$name}</td><td>{$item['quantity']}</td><td>{$item['formatted_total']}</td></tr>";
            $subtotal += $item['total_price'];
        }
        
        $shipping_cost = floatval($order['shipping_cost'] ?? 0);
        $tax_amount = floatval($order['tax_amount'] ?? 0);
        $discount_amount = floatval($order['discount_amount'] ?? 0);
        $total = $subtotal + $shipping_cost + $tax_amount - $discount_amount;
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Order Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 5px; }
                h1 { color: #333; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background: #f0f0f0; padding: 10px; text-align: left; }
                td { padding: 10px; border-bottom: 1px solid #ddd; }
                .total { font-size: 18px; font-weight: bold; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>Order Confirmation</h1>
                <p>Thank you for your order, {$customer_name}!</p>
                <p><strong>Order Number:</strong> {$order_number}</p>
                <p><strong>Order Date:</strong> {$order_date}</p>
                
                <h2>Order Items</h2>
                <table>
                    <tr><th>Product</th><th>Quantity</th><th>Total</th></tr>
                    {$items_html}
                </table>
                
                <div class='total'>
                    <p><strong>Total Amount:</strong> {$functions->formatPrice($total)}</p>
                </div>
                
                <hr>
                <p><small>This is an automated message. Please do not reply.</small></p>
            </div>
        </body>
        </html>";
    }

    // Add this method to your existing EmailHelper class in email_helper.php
  public static function sendOrderStatusUpdate($pdo, $functions, $order_id, $new_status, $notes = '') {
        // Capture all errors to buffer
        self::$errorBuffer = '';
        set_error_handler(function($severity, $message, $file, $line) {
            self::$errorBuffer .= "Error: $message in $file on line $line\n";
            return true;
        });
        
        try {
            // Check if PHPMailerWrapper is available
            if (!class_exists('PHPMailerWrapper')) {
                // Try to include it directly
                $phpmailerPath = __DIR__ . '/PHPMailerWrapper.php';
                if (file_exists($phpmailerPath)) {
                    require_once $phpmailerPath;
                } else {
                    error_log("PHPMailerWrapper file not found at: $phpmailerPath");
                    restore_error_handler();
                    return false;
                }
            }
            
            // Get order details
            $order_sql = "SELECT o.*, p.provider_reference as transaction_id
                        FROM orders o
                        LEFT JOIN payments p ON o.id = p.order_id
                        WHERE o.id = ?";
            
            $order_stmt = $pdo->prepare($order_sql);
            $order_stmt->execute([$order_id]);
            $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                error_log("Order $order_id not found for status update email");
                restore_error_handler();
                return false;
            }
            
            // Get order items
            $items_sql = "SELECT oi.*, p.name as product_name, p.main_image as image
                         FROM order_items oi 
                         LEFT JOIN products p ON oi.product_id = p.product_id 
                         WHERE oi.order_id = ?";
            
            $items_stmt = $pdo->prepare($items_sql);
            $items_stmt->execute([$order_id]);
            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($items)) {
                error_log("No items found for order $order_id");
                restore_error_handler();
                return false;
            }
            
            // Format items for email
            $formatted_items = [];
            foreach ($items as $item) {
                $price = floatval($item['price']);
                $quantity = intval($item['quantity']);
                $total_price = $price * $quantity;
                
                $formatted_items[] = [
                    'name' => $item['product_name'] ?? 'Unknown Product',
                    'price' => $price,
                    'formatted_price' => $functions->formatPrice($price),
                    'quantity' => $quantity,
                    'total_price' => $total_price,
                    'formatted_total' => $functions->formatPrice($total_price),
                    'color' => $item['color'] ?? '',
                    'size' => $item['size'] ?? '',
                    'image' => $item['image'] ?? 'assets/images/placeholder-product.jpg'
                ];
            }
            
            // Get status-specific template details
            $status_templates = [
                'processing' => [
                    'subject' => 'Your Order is Now Being Processed',
                    'title' => 'Order Processing Started',
                    'message' => 'Great news! We\'ve started processing your order.',
                    'timeline' => 'Your items are being prepared for shipment.'
                ],
                'shipped' => [
                    'subject' => 'Your Order Has Been Shipped!',
                    'title' => 'Order Shipped',
                    'message' => 'Your order is on its way to you!',
                    'timeline' => 'Your package has left our warehouse.'
                ],
                'delivered' => [
                    'subject' => 'Your Order Has Been Delivered',
                    'title' => 'Order Delivered',
                    'message' => 'Your order has been successfully delivered.',
                    'timeline' => 'Your package has arrived at its destination.'
                ],
                'cancelled' => [
                    'subject' => 'Order Cancellation Update',
                    'title' => 'Order Cancelled',
                    'message' => 'Your order has been cancelled as requested.',
                    'timeline' => 'This order has been cancelled.'
                ]
            ];
            
            $template = $status_templates[$new_status] ?? [
                'subject' => 'Order Status Update',
                'title' => 'Order Status Updated',
                'message' => 'The status of your order has been updated.',
                'timeline' => 'Status updated to ' . ucfirst($new_status)
            ];
            
            // Generate email HTML
            $email_html = self::createStatusUpdateEmail($order, $formatted_items, $functions, $new_status, $template, $notes);
            
            // Try to send email
            $email_sent = false;
            try {
                $mailer = new PHPMailerWrapper();
                $subject = $template['subject'] . " - " . $order['order_number'];
                $email_sent = $mailer->sendHtml($order['customer_email'], $subject, $email_html);
                
                if ($email_sent) {
                    error_log("Status update email sent to {$order['customer_email']} for order $order_id (status: $new_status)");
                } else {
                    error_log("Failed to send status update email for order $order_id (status: $new_status)");
                }
            } catch (Exception $e) {
                self::$errorBuffer .= "Mailer error: " . $e->getMessage() . "\n";
                $email_sent = false;
            }
            
            restore_error_handler();
            
            if (!empty(self::$errorBuffer)) {
                error_log("Email helper errors for order $order_id status update: " . self::$errorBuffer);
            }
            
            return $email_sent;
        } catch (Exception $e) {
            restore_error_handler();
            error_log("Email helper exception for order $order_id status update: " . $e->getMessage());
            return false;
        }
    }
    
    private static function createStatusUpdateEmail($order, $items, $functions, $new_status, $template, $notes = '') {
        $order_number = htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8');
        $customer_name = htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8');
        $order_date = date('F j, Y', strtotime($order['order_date']));
        $new_status_display = ucfirst($new_status);
        
        // Format items for email
        $items_html = '';
        $subtotal = 0;
        foreach ($items as $item) {
            $name = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
            $items_html .= "<tr><td>{$name}</td><td>{$item['quantity']}</td><td>{$item['formatted_total']}</td></tr>";
            $subtotal += $item['total_price'];
        }
        
        $shipping_cost = floatval($order['shipping_cost'] ?? 0);
        $tax_amount = floatval($order['tax_amount'] ?? 0);
        $discount_amount = floatval($order['discount_amount'] ?? 0);
        $total = $subtotal + $shipping_cost + $tax_amount - $discount_amount;
        
        // Status color mapping
        $status_colors = [
            'processing' => '#3B82F6', // Blue
            'shipped' => '#8B5CF6',    // Purple
            'delivered' => '#10B981',  // Green
            'cancelled' => '#EF4444'   // Red
        ];
        
        $status_color = $status_colors[$new_status] ?? '#6B7280'; // Gray default
        
        // Additional notes section
        $notes_html = '';
        if (!empty($notes)) {
            $notes = htmlspecialchars($notes, ENT_QUOTES, 'UTF-8');
            $notes_html = "
            <div style='background-color: #F3F4F6; border-left: 4px solid {$status_color}; padding: 12px; margin: 20px 0; border-radius: 4px;'>
                <p style='margin: 0; color: #374151; font-weight: 600;'>Admin Note:</p>
                <p style='margin: 4px 0 0 0; color: #6B7280; font-size: 14px;'>{$notes}</p>
            </div>";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>{$template['subject']}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #e5e7eb; }
                .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #f3f4f6; margin-bottom: 30px; }
                .status-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-weight: bold; color: white; margin: 10px 0; }
                .item-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .item-table th { background: #f9fafb; text-align: left; padding: 12px; border-bottom: 2px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase; }
                .item-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; text-align: center; }
                .timeline { background: #f0f9ff; border-left: 4px solid {$status_color}; padding: 15px; margin: 20px 0; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0; color: #111827;'>{$template['title']}</h1>
                    <div class='status-badge' style='background-color: {$status_color};'>
                        {$new_status_display}
                    </div>
                    <p style='color: #6b7280; margin: 10px 0;'>{$template['message']}</p>
                </div>
                
                <div class='timeline'>
                    <p style='margin: 0; color: #374151; font-weight: 600;'>Update:</p>
                    <p style='margin: 5px 0 0 0; color: #6b7280;'>{$template['timeline']}</p>
                </div>
                
                {$notes_html}
                
                <div style='background: #f9fafb; padding: 20px; border-radius: 6px; margin: 20px 0;'>
                    <p style='margin: 0 0 10px 0; color: #374151; font-weight: 600;'>Order Information:</p>
                    <p style='margin: 5px 0; color: #6b7280;'><strong>Order Number:</strong> {$order_number}</p>
                    <p style='margin: 5px 0; color: #6b7280;'><strong>Order Date:</strong> {$order_date}</p>
                    <p style='margin: 5px 0; color: #6b7280;'><strong>Customer:</strong> {$customer_name}</p>
                </div>
                
                <h3 style='color: #374151; margin: 25px 0 15px 0;'>Order Items</h3>
                <table class='item-table'>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$items_html}
                    </tbody>
                </table>
                
                <div style='text-align: right; margin-top: 20px; padding-top: 15px; border-top: 2px solid #e5e7eb;'>
                    <p style='margin: 0; font-size: 18px; font-weight: bold; color: #111827;'>
                        Total Amount: {$functions->formatPrice($total)}
                    </p>
                </div>
                
                <div class='footer'>
                    <p>If you have any questions about your order, please contact our support team.</p>
                    <p>© " . date('Y') . " Cartella Store. All rights reserved.</p>
                    <p style='font-size: 11px; color: #d1d5db; margin-top: 10px;'>
                        This is an automated message. Please do not reply to this email.
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }

    // Add this method to your EmailHelper class
public static function sendNewOrderNotification($pdo, $functions, $order_id, $admin_email = null) {
    // Capture all errors to buffer
    self::$errorBuffer = '';
    set_error_handler(function($severity, $message, $file, $line) {
        self::$errorBuffer .= "Error: $message in $file on line $line\n";
        return true;
    });
    
    try {
        // Check if PHPMailerWrapper is available
        if (!class_exists('PHPMailerWrapper')) {
            // Try to include it directly
            $phpmailerPath = __DIR__ . '/PHPMailerWrapper.php';
            if (file_exists($phpmailerPath)) {
                require_once $phpmailerPath;
            } else {
                error_log("PHPMailerWrapper file not found at: $phpmailerPath");
                restore_error_handler();
                return false;
            }
        }
        
        // Get order details
        $order_sql = "SELECT o.*, p.provider_reference as transaction_id,
                     COUNT(oi.order_item_id) as item_count
                    FROM orders o
                    LEFT JOIN payments p ON o.id = p.order_id
                    LEFT JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.id = ?";
        
        $order_stmt = $pdo->prepare($order_sql);
        $order_stmt->execute([$order_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            error_log("Order $order_id not found for new order notification");
            restore_error_handler();
            return false;
        }
        
        // Get order items with more details
        $items_sql = "SELECT oi.*, p.name as product_name, p.main_image as image, p.sku
                     FROM order_items oi 
                     LEFT JOIN products p ON oi.product_id = p.product_id 
                     WHERE oi.order_id = ?";
        
        $items_stmt = $pdo->prepare($items_sql);
        $items_stmt->execute([$order_id]);
        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            error_log("No items found for order $order_id");
            restore_error_handler();
            return false;
        }
        
        // Format items for email
        $formatted_items = [];
        foreach ($items as $item) {
            $price = floatval($item['price']);
            $quantity = intval($item['quantity']);
            $total_price = $price * $quantity;
            
            $formatted_items[] = [
                'name' => $item['product_name'] ?? 'Unknown Product',
                'sku' => $item['sku'] ?? 'N/A',
                'price' => $price,
                'formatted_price' => $functions->formatPrice($price),
                'quantity' => $quantity,
                'total_price' => $total_price,
                'formatted_total' => $functions->formatPrice($total_price),
                'color' => $item['color'] ?? '',
                'size' => $item['size'] ?? '',
                'image' => $item['image'] ?? 'assets/images/placeholder-product.jpg'
            ];
        }
        
        // Get payment method display name
        $payment_methods = [
            'mtn_momo' => 'MTN Mobile Money',
            'paystack_inline' => 'Paystack',
            'cash_on_delivery' => 'Cash on Delivery',
            'bank_transfer' => 'Bank Transfer'
        ];
        
        $payment_method_display = $payment_methods[$order['payment_method']] ?? ucfirst(str_replace('_', ' ', $order['payment_method']));
        
        // Generate email HTML
        $email_html = self::createNewOrderNotificationEmail($order, $formatted_items, $functions, $payment_method_display);
        
        // Determine recipient email
        if (empty($admin_email)) {
            // Get from environment or use default
            $admin_email = $_ENV['ADMIN_EMAIL'] ?? $_ENV['MAIL_USER'] ?? 'admin@cartella.local';
        }
        
        // Try to send email
        $email_sent = false;
        try {
            $mailer = new PHPMailerWrapper();
            $subject = "🛍️ New Order Received - " . $order['order_number'];
            $email_sent = $mailer->sendHtml($admin_email, $subject, $email_html);
            
            if ($email_sent) {
                error_log("New order notification sent to $admin_email for order $order_id");
            } else {
                error_log("Failed to send new order notification for order $order_id");
            }
        } catch (Exception $e) {
            self::$errorBuffer .= "Mailer error: " . $e->getMessage() . "\n";
            $email_sent = false;
        }
        
        restore_error_handler();
        
        if (!empty(self::$errorBuffer)) {
            error_log("Email helper errors for new order notification $order_id: " . self::$errorBuffer);
        }
        
        return $email_sent;
    } catch (Exception $e) {
        restore_error_handler();
        error_log("Email helper exception for new order notification $order_id: " . $e->getMessage());
        return false;
    }
}

private static function createNewOrderNotificationEmail($order, $items, $functions, $payment_method_display) {
    $order_number = htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8');
    $customer_name = htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8');
    $customer_email = htmlspecialchars($order['customer_email'], ENT_QUOTES, 'UTF-8');
    $customer_phone = htmlspecialchars($order['customer_phone'], ENT_QUOTES, 'UTF-8');
    $order_date = date('F j, Y g:i A', strtotime($order['order_date']));
    $shipping_address = htmlspecialchars($order['shipping_address'], ENT_QUOTES, 'UTF-8');
    $shipping_city = htmlspecialchars($order['shipping_city'] ?? '', ENT_QUOTES, 'UTF-8');
    $shipping_region = htmlspecialchars($order['shipping_region'] ?? '', ENT_QUOTES, 'UTF-8');
    $shipping_postal_code = htmlspecialchars($order['shipping_postal_code'] ?? '', ENT_QUOTES, 'UTF-8');
    
    // Format items for email with more details for admin
    $items_html = '';
    $subtotal = 0;
    foreach ($items as $item) {
        $name = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
        $sku = htmlspecialchars($item['sku'], ENT_QUOTES, 'UTF-8');
        $quantity = $item['quantity'];
        $price = $item['formatted_price'];
        $total = $item['formatted_total'];
        
        $variant_info = '';
        if (!empty($item['color']) || !empty($item['size'])) {
            $variant_info = '<br><small style="color: #666;">';
            if (!empty($item['color'])) $variant_info .= 'Color: ' . htmlspecialchars($item['color']) . ' ';
            if (!empty($item['size'])) $variant_info .= 'Size: ' . htmlspecialchars($item['size']);
            $variant_info .= '</small>';
        }
        
        $items_html .= "
        <tr>
            <td style='padding: 10px; border-bottom: 1px solid #eee;'>
                <strong>{$name}</strong><br>
                <small style='color: #666;'>SKU: {$sku}</small>
                {$variant_info}
            </td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$quantity}</td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>{$price}</td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'><strong>{$total}</strong></td>
        </tr>";
        
        $subtotal += $item['total_price'];
    }
    
    $shipping_cost = floatval($order['shipping_cost'] ?? 0);
    $tax_amount = floatval($order['tax_amount'] ?? 0);
    $discount_amount = floatval($order['discount_amount'] ?? 0);
    $total = $subtotal + $shipping_cost + $tax_amount - $discount_amount;
    
    // Format shipping address
    $full_shipping_address = $shipping_address;
    if (!empty($shipping_city)) $full_shipping_address .= "<br>" . $shipping_city;
    if (!empty($shipping_region)) $full_shipping_address .= ", " . $shipping_region;
    if (!empty($shipping_postal_code)) $full_shipping_address .= " " . $shipping_postal_code;
    
    // Admin action URLs
    $admin_order_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/admin/a_order_details.php?id=' . $order['id'];
    $admin_orders_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/admin/a_orders.php';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <title>New Order Received - {$order_number}</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
            .container { max-width: 700px; margin: 0 auto; background: #ffffff; border-radius: 10px; border: 1px solid #e0e0e0; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .header p { margin: 10px 0 0; opacity: 0.9; }
            .content { padding: 30px; }
            .alert-box { background: #e8f4fd; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .section { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
            .section:last-child { border-bottom: none; }
            .section-title { color: #333; font-size: 18px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
            .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .info-item { margin-bottom: 10px; }
            .info-label { color: #666; font-size: 14px; margin-bottom: 5px; }
            .info-value { font-weight: bold; color: #333; }
            .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .table th { background: #f8f9fa; text-align: left; padding: 12px 10px; border-bottom: 2px solid #dee2e6; color: #495057; font-weight: 600; }
            .table td { padding: 12px 10px; border-bottom: 1px solid #dee2e6; }
            .totals { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .total-row { display: flex; justify-content: space-between; padding: 8px 0; }
            .total-row.grand-total { border-top: 2px solid #dee2e6; margin-top: 10px; padding-top: 15px; font-size: 18px; font-weight: bold; }
            .action-buttons { margin-top: 30px; text-align: center; }
            .btn { display: inline-block; background: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 0 10px; }
            .btn-view { background: #2196F3; }
            .btn-orders { background: #9C27B0; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; color: #666; font-size: 12px; }
            .highlight { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🛍️ New Order Received!</h1>
                <p>Order #{$order_number} requires your attention</p>
            </div>
            
            <div class='content'>
                <div class='alert-box'>
                    <strong>Action Required:</strong> A new order has been placed and is awaiting processing.
                </div>
                
                <div class='section'>
                    <h2 class='section-title'>Order Summary</h2>
                    <div class='info-grid'>
                        <div class='info-item'>
                            <div class='info-label'>Order Number</div>
                            <div class='info-value'>{$order_number}</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Order Date & Time</div>
                            <div class='info-value'>{$order_date}</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Customer Name</div>
                            <div class='info-value'>{$customer_name}</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Customer Email</div>
                            <div class='info-value'>{$customer_email}</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Customer Phone</div>
                            <div class='info-value'>{$customer_phone}</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Payment Method</div>
                            <div class='info-value'>{$payment_method_display}</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Order Status</div>
                            <div class='info-value' style='color: #ff9800; font-weight: bold;'>Pending</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Payment Status</div>
                            <div class='info-value' style='color: " . ($order['payment_status'] === 'completed' ? '#4CAF50' : '#ff9800') . ";'>
                                " . ucfirst($order['payment_status']) . "
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class='section'>
                    <h2 class='section-title'>Shipping Address</h2>
                    <div class='highlight'>
                        {$full_shipping_address}
                    </div>
                </div>
                
                <div class='section'>
                    <h2 class='section-title'>Order Items ({$order['item_count']} items)</h2>
                    <table class='table'>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th width='80'>Qty</th>
                                <th width='100'>Price</th>
                                <th width='100'>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$items_html}
                        </tbody>
                    </table>
                </div>
                
                <div class='totals'>
                    <div class='total-row'>
                        <span>Subtotal:</span>
                        <span>{$functions->formatPrice($subtotal)}</span>
                    </div>
                    " . ($shipping_cost > 0 ? "
                    <div class='total-row'>
                        <span>Shipping:</span>
                        <span>{$functions->formatPrice($shipping_cost)}</span>
                    </div>
                    " : "") . "
                    " . ($tax_amount > 0 ? "
                    <div class='total-row'>
                        <span>Tax:</span>
                        <span>{$functions->formatPrice($tax_amount)}</span>
                    </div>
                    " : "") . "
                    " . ($discount_amount > 0 ? "
                    <div class='total-row'>
                        <span>Discount:</span>
                        <span style='color: #e74c3c;'>-{$functions->formatPrice($discount_amount)}</span>
                    </div>
                    " : "") . "
                    <div class='total-row grand-total'>
                        <span>Grand Total:</span>
                        <span>{$functions->formatPrice($total)}</span>
                    </div>
                </div>
                
                <div class='action-buttons'>
                    <a href='{$admin_order_url}' class='btn btn-view' target='_blank'>View Order Details</a>
                    <a href='{$admin_orders_url}' class='btn btn-orders' target='_blank'>View All Orders</a>
                </div>
                
                <div style='margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;'>
                    <p style='margin: 0; color: #666; font-size: 13px;'>
                        <strong>Quick Actions:</strong><br>
                        1. Review the order details<br>
                        2. Verify payment status<br>
                        3. Update order status to 'Processing' when ready<br>
                        4. Prepare items for shipping
                    </p>
                </div>
            </div>
            
            <div class='footer'>
                <p>This is an automated notification. You received this email because you are listed as an administrator.</p>
                <p>© " . date('Y') . " Cartella Store. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
}
}
?>