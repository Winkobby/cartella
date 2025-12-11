<?php
// Start session and include config first
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get order details from session or URL parameters
 $order_id = $_GET['order_id'] ?? $_SESSION['last_order_id'] ?? null;

// If we have an order ID, fetch the complete order details from database
 $order_number = null;
 $total_amount = null;

if ($order_id) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $sql = "SELECT order_number, total_amount FROM orders WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Use the actual order number from the database
            $order_number = $order['order_number'];
            $total_amount = $order['total_amount'];
        }
        
        // Fetch order items to display on confirmation (includes size)
        $items_sql = "SELECT oi.*, p.name as product_name, p.main_image as image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->execute([$order_id]);
        $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get support contact information from settings
        require_once 'includes/SettingsHelper.php';
        $support_email = SettingsHelper::get($conn, 'support_email', 'support@yourstore.com');
        $whatsapp_number = $_ENV['WHATSAPP_NUMBER'] ?? '+233 24 123 4567';
        
    } catch (Exception $e) {
        error_log("Error fetching order details: " . $e->getMessage());
        $support_email = 'support@yourstore.com';
        $whatsapp_number = '+233 24 123 4567';
    }
} else {
    $support_email = 'support@yourstore.com';
    $whatsapp_number = '+233 24 123 4567';
}

// Fallback to session/URL parameters if database fetch fails
if (!$order_number) {
    $order_number = $_GET['order_number'] ?? $_SESSION['last_order_number'] ?? null;
}
if (!$total_amount) {
    $total_amount = $_GET['total_amount'] ?? $_SESSION['last_order_total'] ?? null;
}

// If no order data, redirect to home
if (!$order_id && !$order_number) {
    header('Location: index.php');
    exit;
}

// Clear session order data after displaying
unset($_SESSION['last_order_id'], $_SESSION['last_order_number'], $_SESSION['last_order_total']);

// Now include header after session handling
require_once 'includes/header.php';
?>


<div class="min-h-screen bg-gradient-to-br from-purple-50 to-blue-50 py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Success Header -->
        <div class="text-center mb-12">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Order Confirmed!</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Thank you for your purchase. Your order has been received and is being processed.
            </p>
        </div>

        <!-- Order Summary Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-8 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 p-6">
                <h2 class="text-2xl font-bold text-white mb-2">Order Summary</h2>
                <div class="flex flex-wrap items-center justify-between gap-4 text-white">
                    <div>
                        <p class="text-purple-200">Order Number</p>
                        <p class="text-xl font-semibold" id="orderNumber"><?php echo htmlspecialchars($order_number ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-purple-200">Total Amount</p>
                        <p class="text-xl font-semibold">
                            <?php 
                            if ($total_amount) {
                                echo 'GHS ' . number_format(floatval($total_amount), 2);
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-purple-200">Order Date</p>
                        <p class="text-xl font-semibold"><?php echo date('F j, Y'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- What's Next -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            What's Next?
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-green-100 rounded-full flex items-center justify-center mt-1">
                                    <span class="text-green-600 text-sm font-bold">1</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Order Confirmation</p>
                                    <p class="text-sm text-gray-600">You'll receive an email confirmation shortly</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mt-1">
                                    <span class="text-blue-600 text-sm font-bold">2</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Order Processing</p>
                                    <p class="text-sm text-gray-600">We're preparing your items for shipment</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center mt-1">
                                    <span class="text-purple-600 text-sm font-bold">3</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Shipping</p>
                                    <p class="text-sm text-gray-600">Your order will be shipped within 24-48 hours</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Support Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Need Help?
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">WhatsApp Support</p>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp_number); ?>" target="_blank" class="text-sm text-purple-600 hover:text-purple-700">
                                        <?php echo htmlspecialchars($whatsapp_number); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Email Support</p>
                                    <a href="mailto:<?php echo htmlspecialchars($support_email); ?>" class="text-sm text-purple-600 hover:text-purple-700">
                                        <?php echo htmlspecialchars($support_email); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Items List -->
            <?php if (!empty($order_items) && is_array($order_items)): ?>
            <div class="p-6 bg-white border-t border-gray-100  mb-6 rounded-2xl shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Items</h3>
                <div class="space-y-4">
                    <?php foreach ($order_items as $it): ?>
                        <div class="flex items-center space-x-4">
                            <img src="<?php echo htmlspecialchars($it['image'] ?: 'assets/images/placeholder-product.jpg'); ?>" alt="<?php echo htmlspecialchars($it['product_name']); ?>" class="w-16 h-16 object-cover rounded-lg">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($it['product_name']); ?></div>
                                <div class="text-xs text-gray-600 mt-1">
                                    <?php if (!empty($it['size'])): ?>
                                        <span class="mr-3"><strong>Size:</strong> <?php echo htmlspecialchars($it['size']); ?></span>
                                    <?php endif; ?>
                                    <span class="mr-3"><strong>Qty:</strong> <?php echo intval($it['quantity']); ?></span>
                                    <span><strong>Price:</strong> GHS <?php echo number_format(floatval($it['price']), 2); ?></span>
                                </div>
                            </div>
                            <div class="text-sm font-semibold text-gray-700">GHS <?php echo number_format(floatval($it['price'] * $it['quantity']), 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        <!-- Delivery Information -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8 mt-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Delivery Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 mb-1">Processing Time</h4>
                    <p class="text-sm text-gray-600">1-2 business days</p>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 mb-1">Free Shipping</h4>
                    <p class="text-sm text-gray-600">On all orders</p>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 mb-1">Delivery</h4>
                    <p class="text-sm text-gray-600">3-5 business days</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center space-y-4 md:space-y-0 md:space-x-4 md:flex md:justify-center">
            <a href="orders.php" class="w-full md:w-auto bg-purple-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-purple-700 transition-colors flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span>View My Orders</span>
            </a>
            <a href="products.php" class="w-full md:w-auto border border-purple-600 text-purple-600 px-8 py-4 rounded-lg font-semibold hover:bg-purple-50 transition-colors flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                <span>Continue Shopping</span>
            </a>
            <a href="index.php" class="w-full md:w-auto border border-gray-300 text-gray-700 px-8 py-4 rounded-lg font-semibold hover:bg-gray-50 transition-colors flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>Back to Home</span>
            </a>
        </div>

        <!-- Trust Badges -->
        <div class="mt-12 pt-8 border-t border-gray-200">
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-6">Trusted by thousands of customers</p>
                <div class="flex flex-wrap justify-center items-center gap-8 opacity-60">
                    <div class="flex items-center space-x-2">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Secure Payment</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Fast Delivery</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Easy Returns</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">24/7 Support</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple confetti effect
    function createConfetti() {
        const colors = ['#8B5CF6', '#6366F1', '#10B981', '#F59E0B', '#EF4444'];
        const container = document.body;
        
        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.cssText = `
                position: fixed;
                width: 10px;
                height: 10px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                top: -10px;
                left: ${Math.random() * 100}%;
                opacity: ${Math.random() * 0.5 + 0.5};
                border-radius: 2px;
                z-index: 9999;
                pointer-events: none;
            `;
            
            container.appendChild(confetti);
            
            const animation = confetti.animate([
                { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
                { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg)`, opacity: 0 }
            ], {
                duration: Math.random() * 3000 + 2000,
                easing: 'cubic-bezier(0.1, 0.8, 0.3, 1)'
            });
            
            animation.onfinish = () => confetti.remove();
        }
    }
    
    // Trigger confetti on page load
    setTimeout(createConfetti, 500);
    
    // Add copy functionality to order number
    const orderNumber = document.getElementById('orderNumber');
    if (orderNumber) {
        orderNumber.addEventListener('click', function() {
            navigator.clipboard.writeText(this.textContent).then(() => {
                const originalText = this.textContent;
                this.textContent = 'Copied!';
                setTimeout(() => {
                    this.textContent = originalText;
                }, 2000);
            });
        });
        
        orderNumber.style.cursor = 'pointer';
        orderNumber.title = 'Click to copy order number';
    }
});
</script>

<style>
.confetti {
    animation: confetti-fall linear forwards;
}

@keyframes confetti-fall {
    to {
        transform: translateY(100vh) rotate(360deg);
        opacity: 0;
    }
}

/* Smooth animations */
/* .bg-white {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.bg-white:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
} */
</style>

<?php
require_once 'includes/footer.php';
?>