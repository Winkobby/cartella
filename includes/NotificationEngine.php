<?php
/**
 * Notification System for CartMate
 * Handles automated email notifications based on user preferences
 */

class NotificationEngine {
    private $pdo;
    private $functions;
    private $emailHelper;
    private $mailerReady = false;
    
    public function __construct($pdo, $functions) {
        $this->pdo = $pdo;
        $this->functions = $functions;
        
        // Load email helper if available
        if (file_exists(__DIR__ . '/email_helper.php')) {
            require_once __DIR__ . '/email_helper.php';
        }

        $this->prepareMailer();
    }

    /**
     * Ensure PHPMailerWrapper is available
     */
    private function prepareMailer() {
        if (class_exists('PHPMailerWrapper')) {
            $this->mailerReady = true;
            return;
        }

        $wrapperPath = __DIR__ . '/PHPMailerWrapper.php';
        if (file_exists($wrapperPath)) {
            require_once $wrapperPath;
        } elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }

        $this->mailerReady = class_exists('PHPMailerWrapper');
        if (!$this->mailerReady) {
            error_log('NotificationEngine: PHPMailerWrapper not found');
        }
    }
    
    /**
     * Send notification for new product
     */
    public function notifyNewProduct($product_data) {
        try {
            if (!$this->mailerReady) {
                return [
                    'success' => false,
                    'error' => 'Mailer not configured',
                    'emails_sent' => 0,
                    'target_count' => 0
                ];
            }
            $product_id = $product_data['product_id'] ?? null;
            $name = $product_data['name'] ?? 'New Product';
            $is_featured = $product_data['is_featured'] ?? 0;
            $is_new = $product_data['is_new'] ?? 0;
            
            // Determine notification type
            $notification_type = 'new_products';
            if ($is_featured) {
                $notification_type = 'featured_products';
            }
            
            // Get users who want this notification (defaults to enabled if prefs missing)
            $query = "
                SELECT u.user_id, u.email, u.first_name 
                FROM users u
                LEFT JOIN user_notification_preferences unp ON u.user_id = unp.user_id
                WHERE u.is_active = 1
                AND COALESCE(unp.{$notification_type}, 1) = 1
            ";
            
            $stmt = $this->pdo->query($query);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $target_count = count($users);
            
            // Send emails to each user
            $email_count = 0;
            foreach ($users as $user) {
                if ($this->sendProductNotificationEmail($user, $product_data, $notification_type)) {
                    $email_count++;
                }
            }
            
            return [
                'success' => true,
                'emails_sent' => $email_count,
                'target_count' => $target_count,
                'product_id' => $product_id,
                'product_name' => $name
            ];
            
        } catch (Exception $e) {
            error_log("Error notifying new product: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send notification for new coupon/sale
     */
    public function notifyCouponCreated($coupon_data) {
        try {
            if (!$this->mailerReady) {
                return [
                    'success' => false,
                    'error' => 'Mailer not configured',
                    'emails_sent' => 0,
                    'target_count' => 0
                ];
            }
            $coupon_id = $coupon_data['coupon_id'] ?? null;
            $code = $coupon_data['code'] ?? 'PROMOTION';
            $discount_value = $coupon_data['discount_value'] ?? 0;
            $discount_type = $coupon_data['discount_type'] ?? 'percentage';
            
            // Get users who want sales/promotions notifications (defaults to enabled if prefs missing)
            $query = "
                SELECT u.user_id, u.email, u.first_name 
                FROM users u
                LEFT JOIN user_notification_preferences unp ON u.user_id = unp.user_id
                WHERE u.is_active = 1
                AND COALESCE(unp.sales_promotions, 1) = 1
            ";
            
            $stmt = $this->pdo->query($query);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $target_count = count($users);
            
            // Send emails to each user
            $email_count = 0;
            foreach ($users as $user) {
                if ($this->sendCouponNotificationEmail($user, $coupon_data)) {
                    $email_count++;
                }
            }
            
            return [
                'success' => true,
                'emails_sent' => $email_count,
                'target_count' => $target_count,
                'coupon_id' => $coupon_id,
                'coupon_code' => $code
            ];
            
        } catch (Exception $e) {
            error_log("Error notifying coupon: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send product notification email
     */
    private function sendProductNotificationEmail($user, $product_data, $notification_type) {
        try {
            if (!$this->mailerReady) {
                return false;
            }
            $product_id = $product_data['product_id'] ?? null;
            $name = $product_data['name'] ?? 'New Product';
            $description = $product_data['description'] ?? $product_data['short_description'] ?? '';
            $price = $product_data['price'] ?? 0;
            $discount = $product_data['discount'] ?? 0;
            $main_image = $product_data['main_image'] ?? 'assets/images/placeholder-product.jpg';
            $is_featured = $product_data['is_featured'] ?? 0;
            
            // Format price
            $formatted_price = $this->functions->formatPrice($price);
            $discount_price = $price * (1 - ($discount / 100));
            $formatted_discount_price = $this->functions->formatPrice($discount_price);
            
            // Create email subject
            if ($is_featured) {
                $subject = "⭐ Featured Product Alert: {$name}";
                $type_text = "Featured Product";
            } else {
                $subject = "🆕 New Product: {$name}";
                $type_text = "New Product";
            }
            
            // Build email HTML
            $email_html = $this->createProductNotificationEmail(
                $user['first_name'],
                $name,
                $description,
                $formatted_price,
                $formatted_discount_price,
                $discount,
                $main_image,
                $product_id,
                $type_text
            );
            
            // Send email
            if (class_exists('PHPMailerWrapper')) {
                $mailer = new PHPMailerWrapper();
                return $mailer->sendHtml($user['email'], $subject, $email_html);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error sending product notification email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send coupon notification email
     */
    private function sendCouponNotificationEmail($user, $coupon_data) {
        try {
            if (!$this->mailerReady) {
                return false;
            }
            $code = $coupon_data['code'] ?? 'PROMOTION';
            $description = $coupon_data['description'] ?? '';
            $discount_value = $coupon_data['discount_value'] ?? 0;
            $discount_type = $coupon_data['discount_type'] ?? 'percentage';
            $min_order = $coupon_data['min_order_amount'] ?? 0;
            $end_date = $coupon_data['end_date'] ?? '';
            
            // Format discount display
            $discount_display = $discount_type === 'percentage' 
                ? "{$discount_value}% OFF" 
                : $this->functions->formatPrice($discount_value) . " OFF";
            
            $subject = "🎉 Exclusive Promotion: {$discount_display}!";
            
            // Build email HTML
            $email_html = $this->createCouponNotificationEmail(
                $user['first_name'],
                $code,
                $description,
                $discount_display,
                $discount_type,
                $discount_value,
                $min_order,
                $end_date
            );
            
            // Send email
            if (class_exists('PHPMailerWrapper')) {
                $mailer = new PHPMailerWrapper();
                return $mailer->sendHtml($user['email'], $subject, $email_html);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error sending coupon notification email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send order update notification
     */
    public function notifyOrderUpdate($order_id, $order_status, $user_email, $user_name) {
        try {
            if (!$this->mailerReady) {
                error_log('NotificationEngine: mailer not configured for order update');
                return false;
            }
            // Get user preferences (defaults to enabled if prefs missing)
            $query = "
                SELECT u.user_id FROM users u 
                LEFT JOIN user_notification_preferences unp ON u.user_id = unp.user_id
                WHERE u.email = ? AND u.is_active = 1 AND COALESCE(unp.order_updates, 1) = 1
            ";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$user_email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false; // User doesn't want order updates
            }
            
            // Get order details
            $order_query = "SELECT * FROM orders WHERE id = ?";
            $order_stmt = $this->pdo->prepare($order_query);
            $order_stmt->execute([$order_id]);
            $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return false;
            }
            
            // Create email subject and content based on status
            $status_messages = [
                'Pending' => '⏳ Your order is being processed',
                'Processing' => '📦 Your order is being prepared',
                'Shipped' => '🚚 Your order has been shipped',
                'Delivered' => '✅ Your order has been delivered',
                'Cancelled' => '❌ Your order has been cancelled'
            ];
            
            $subject = "Order Update: " . ($status_messages[$order_status] ?? $order_status);
            
            $email_html = $this->createOrderUpdateEmail(
                $user_name,
                $order['order_number'] ?? $order_id,
                $order_status,
                $order
            );
            
            // Send email
            if (class_exists('PHPMailerWrapper')) {
                $mailer = new PHPMailerWrapper();
                return $mailer->sendHtml($user_email, $subject, $email_html);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error sending order update notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create product notification email HTML
     */
    private function createProductNotificationEmail($user_name, $product_name, $description, $price, $discount_price, $discount, $image, $product_id, $type_text) {
        $base_url = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $product_url = $base_url . "/product.php?id={$product_id}";
        
        // Convert relative image path to absolute URL with fallback
        if ($image) {
            // If it's already a full URL, use it
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                // URL is good
            } else {
                // Convert relative path to absolute
                $image = ltrim($image, '/');
                $image = $base_url . "/" . $image;
            }
        } else {
            // Use placeholder if no image
            $image = $base_url . "/assets/images/placeholder-product.jpg";
        }
        
        // Decode HTML entities the same way as product.php does
        $product_name = html_entity_decode($product_name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $discount_badge = $discount > 0 ? "<span style='background: #ff6b6b; color: white; padding: 5px 10px; border-radius: 4px; font-weight: bold;'>-{$discount}%</span>" : "";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; }
                .product-card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .product-image { max-width: 100%; height: auto; border-radius: 4px; margin-bottom: 15px; }
                .product-name { font-size: 24px; font-weight: bold; margin: 15px 0; }
                .product-description { color: #666; margin: 15px 0; line-height: 1.6; }
                .price-section { margin: 20px 0; }
                .original-price { font-size: 18px; text-decoration: line-through; color: #999; }
                .final-price { font-size: 28px; font-weight: bold; color: #ff6b6b; margin-top: 10px; }
                .cta-button { background: #667eea; color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; margin-top: 15px; }
                .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999; }
                .badge { display: inline-block; background: #4CAF50; color: white; padding: 8px 15px; border-radius: 4px; font-weight: bold; margin-bottom: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📢 {$type_text} Alert!</h1>
                </div>
                <div class='content'>
                    <p>Hi {$user_name},</p>
                    <p>We found something special for you!</p>
                    
                    <div class='product-card'>
                        <div class='badge'>{$type_text}</div>
                        <img src='{$image}' alt='{$product_name}' class='product-image' style='width: 100%;'>
                        <div class='product-name'>{$product_name}</div>
                        <div class='product-description'>{$description}</div>
                        
                        <div class='price-section'>
                            <div class='original-price'>{$price}</div>
                            {$discount_badge}
                            <div class='final-price'>{$discount_price}</div>
                        </div>
                        
                        <a href='{$product_url}' class='cta-button'>View Product</a>
                    </div>
                    
                    <p>Visit our store to explore more amazing products!</p>
                </div>
                <div class='footer'>
                    <p>You received this email because you're subscribed to {$type_text} notifications.</p>
                    <p>You can change your notification preferences in your account settings.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Create coupon notification email HTML
     */
    private function createCouponNotificationEmail($user_name, $code, $description, $discount_display, $discount_type, $discount_value, $min_order, $end_date) {
        $shop_url = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/products.php";
        $min_order_text = $min_order > 0 ? "Minimum order: " . $this->functions->formatPrice($min_order) : "No minimum order";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; }
                .header { background: linear-gradient(135deg, #FF6B6B 0%, #FFB347 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; }
                .coupon-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; text-align: center; margin: 20px 0; }
                .coupon-code { font-size: 48px; font-weight: bold; letter-spacing: 2px; margin: 15px 0; font-family: 'Courier New', monospace; }
                .discount-badge { font-size: 36px; font-weight: bold; margin: 15px 0; }
                .details { font-size: 14px; line-height: 1.8; margin: 20px 0; }
                .cta-button { background: #667eea; color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; margin-top: 15px; }
                .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999; }
                .warning { color: #ff6b6b; font-size: 12px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Exclusive Offer!</h1>
                </div>
                <div class='content'>
                    <p>Hi {$user_name},</p>
                    <p>We have an amazing offer just for you!</p>
                    
                    <div class='coupon-card'>
                        <div class='discount-badge'>{$discount_display}</div>
                        <p style='font-size: 16px; margin: 10px 0;'>{$description}</p>
                        <div style='border: 2px dashed white; padding: 15px; border-radius: 4px; margin: 15px 0;'>
                            <p style='font-size: 12px; margin: 0 0 5px 0;'>Use coupon code:</p>
                            <div class='coupon-code'>{$code}</div>
                        </div>
                        <div class='details'>
                            <div>✅ {$min_order_text}</div>
                            <div>📅 Valid until: {$end_date}</div>
                        </div>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='{$shop_url}' class='cta-button'>Shop Now</a>
                    </div>
                    
                    <div class='warning'>
                        ⏰ Don't miss out! This offer expires on {$end_date}
                    </div>
                </div>
                <div class='footer'>
                    <p>You received this email because you're subscribed to sales & promotions notifications.</p>
                    <p>You can change your notification preferences in your account settings.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Create order update email HTML
     */
    private function createOrderUpdateEmail($user_name, $order_number, $status, $order) {
        $tracking_url = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/track_order.php?order_id={$order_number}";
        
        $status_icons = [
            'Pending' => '⏳',
            'Processing' => '📦',
            'Shipped' => '🚚',
            'Delivered' => '✅',
            'Cancelled' => '❌'
        ];
        
        $status_icon = $status_icons[$status] ?? '📌';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; }
                .header { background: #667eea; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; }
                .order-status { background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .status-badge { font-size: 48px; text-align: center; margin: 15px 0; }
                .order-info { margin: 15px 0; }
                .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ddd; }
                .cta-button { background: #667eea; color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; margin-top: 15px; }
                .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Order Status Update</h1>
                </div>
                <div class='content'>
                    <p>Hi {$user_name},</p>
                    
                    <div class='order-status'>
                        <div class='status-badge'>{$status_icon}</div>
                        <h2 style='text-align: center; color: #667eea;'>{$status}</h2>
                        
                        <div class='order-info'>
                            <div class='info-row'>
                                <span>Order Number:</span>
                                <strong>{$order_number}</strong>
                            </div>
                            <div class='info-row'>
                                <span>Order Date:</span>
                                <strong>" . (isset($order['order_date']) ? date('M d, Y', strtotime($order['order_date'])) : 'N/A') . "</strong>
                            </div>
                            <div class='info-row'>
                                <span>Total Amount:</span>
                                <strong>" . $this->functions->formatPrice($order['total_amount'] ?? 0) . "</strong>
                            </div>
                        </div>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='{$tracking_url}' class='cta-button'>Track Order</a>
                    </div>
                    
                    <p>If you have any questions, please don't hesitate to contact our support team.</p>
                </div>
                <div class='footer'>
                    <p>You received this email because you're subscribed to order update notifications.</p>
                    <p>You can change your notification preferences in your account settings.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>
