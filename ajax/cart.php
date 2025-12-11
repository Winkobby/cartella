<?php
// Fix the require paths and add session start
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/settings_helper.php';
// Add this after your includes
global $pdo;

// Ensure we have a database connection
if (!isset($pdo)) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
}
header('Content-Type: application/json');

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize database first
$database = new Database();
$database->getConnection();

// Initialize functions and set database
$functions = new Functions();
$functions->setDatabase($database);

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Parse JSON request body if content type is JSON
$json_data = [];
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $json_body = file_get_contents('php://input');
    $json_data = json_decode($json_body, true) ?? [];
}

switch ($action) {
    case 'add_to_cart':
        addToCart();
        break;
    case 'get_cart_count':
        getCartCount();
        break;
    case 'get_cart_preview':
        getCartPreview();
        break;
    case 'update_cart_quantity':
        updateCartQuantity();
        break;
    case 'remove_from_cart':
        removeFromCart();
        break;
    case 'clear_cart':
        clearCart();
        break;
    case 'get_cart_summary':
        getCartSummary();
        break;
    case 'apply_coupon':
        applyCoupon();
        break;
    case 'remove_coupon':
        removeCoupon();
        break;
    case 'get_applied_coupon':
        getAppliedCoupon();
        break;
  
case 'bulk_remove':
    bulkRemoveFromCart();
    break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function updateCartQuantity()
{
    global $functions, $json_data;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    $cart_key = $json_data['cart_key'] ?? $_POST['cart_key'] ?? '';
    $quantity = intval($json_data['quantity'] ?? $_POST['quantity'] ?? 1);

    if (empty($cart_key) || $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }

    // Update cart quantity
    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]['quantity'] = $quantity;

        $cartCount = $functions->getCartItemCount();
        $cartTotal = $functions->getCartTotal();

        echo json_encode([
            'success' => true,
            'message' => 'Quantity updated',
            'cart_count' => $cartCount,
            'cart_total' => $cartTotal,
            'formatted_total' => $functions->formatPrice($cartTotal),
            'cart_preview' => getCartPreviewData()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    }
}

function removeFromCart()
{
    global $functions;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    $cart_key = $_POST['cart_key'] ?? '';

    if (empty($cart_key)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }

    // Remove from cart
    $result = $functions->removeFromCart($cart_key);

    if ($result) {
        $cartCount = $functions->getCartItemCount();
        $cartTotal = $functions->getCartTotal();

        echo json_encode([
            'success' => true,
            'message' => 'Item removed from cart',
            'cart_count' => $cartCount,
            'cart_total' => $cartTotal,
            'formatted_total' => $functions->formatPrice($cartTotal),
            'cart_preview' => getCartPreviewData()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
    }
}

function getCartPreviewData()
{
    global $functions;

    $cartItems = $_SESSION['cart'] ?? [];
    $previewItems = array_slice($cartItems, 0, 3, true); // preserve keys
    $itemCount = count($cartItems);
    $totalAmount = $functions->getCartTotal();

    // Format items properly for the associative array structure
    $formattedItems = [];
    foreach ($previewItems as $cartKey => $item) {
        // Ensure name is decoded and safely escaped for display
        $itemName = $item['name'] ?? 'Product';
        $displayName = htmlspecialchars($itemName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $formattedItems[] = [
            'key' => $cartKey,
            'name' => $displayName,
            'price' => floatval($item['price'] ?? 0),
            'quantity' => intval($item['quantity'] ?? 1),
            'size' => $item['size'] ?? null,
            'image' => $item['image'] ?? 'assets/images/placeholder-product.jpg'
        ];
    }

    return [
        'items' => $formattedItems,
        'item_count' => $itemCount,
        'total' => $functions->formatPrice($totalAmount)
    ];
}

function addToCart()
{
    global $functions, $json_data;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    // Get from JSON or POST data
    $product_id = intval($json_data['product_id'] ?? $_POST['product_id'] ?? 0);
    $quantity = intval($json_data['quantity'] ?? $_POST['quantity'] ?? 1);
    // optional selected size
    $selected_size = $json_data['selected_size'] ?? $_POST['selected_size'] ?? null;

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        return;
    }

    // Add to cart (pass selected size if provided)
    $result = $functions->addToCart($product_id, $quantity, null, $selected_size);

    if ($result) {
        $cartCount = $functions->getCartItemCount();
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart',
            'cart_count' => $cartCount
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
    }
}

function getCartCount()
{
    global $functions;

    $count = $functions->getCartItemCount();
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
}

function getCartPreview()
{
    global $functions;

    $cartItems = $_SESSION['cart'] ?? [];
    $items = [];
    $totalAmount = 0;
    $totalItems = 0;

    foreach ($cartItems as $cartKey => $item) {
        // Ensure name is decoded and safely escaped for display
        $itemName = $item['name'] ?? 'Product';
        $displayName = htmlspecialchars($itemName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $items[] = [
            'key' => $cartKey,
            'name' => $displayName,
            'price' => floatval($item['price'] ?? 0),
            'quantity' => intval($item['quantity'] ?? 1),
            'size' => $item['size'] ?? null,
            'image' => $item['image'] ?? 'assets/images/placeholder-product.jpg'
        ];
        $totalAmount += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        $totalItems += ($item['quantity'] ?? 1);
    }

    echo json_encode([
        'success' => true,
        'items' => $items,
        'totalAmount' => $totalAmount,
        'totalItems' => $totalItems,
        'formattedTotal' => $functions->formatPrice($totalAmount)
    ]);
}

function clearCart()
{
    global $functions;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    // Clear cart
    $functions->clearCart();

    echo json_encode([
        'success' => true,
        'message' => 'Cart cleared successfully',
        'cart_count' => 0,
        'cart_total' => 0,
        'formatted_total' => 'GHS 0.00'
    ]);
}

function getCartSummary() {
    global $functions, $pdo;

    $cart_total = calculateCartTotal();
    $tax_amount = 0; // No tax for now

    $discount_amount = 0;
    $applied_coupon = null;

    // Defaults from settings
    $shipping_enabled = '1';
    $default_shipping = 0.0;
    $free_threshold = 0.0;

    try {
        if (isset($pdo)) {
            SettingsHelper::init($pdo);
            $shipping_enabled = SettingsHelper::get($pdo, 'shipping_enabled', $shipping_enabled);
            $default_shipping = floatval(SettingsHelper::get($pdo, 'shipping_cost', $default_shipping));
            $free_threshold = floatval(SettingsHelper::get($pdo, 'free_shipping_threshold', $free_threshold));
        }
    } catch (Exception $e) {
        // ignore and use defaults
    }

    // Calculate discount if coupon is applied
    if (isset($_SESSION['applied_coupon'])) {
        $coupon = $_SESSION['applied_coupon'];
        $discount_amount = $coupon['discount_amount'] ?? 0;
        $applied_coupon = $coupon;
    }

    // Determine shipping cost
    $shipping_cost = 0.0;
    if ($shipping_enabled === '1' || $shipping_enabled === 1 || $shipping_enabled === true) {
        // Start with default
        $shipping_cost = $default_shipping;

        // Free if coupon specifically gives free shipping
        if (!empty($applied_coupon) && ($applied_coupon['discount_type'] ?? '') === 'shipping') {
            $shipping_cost = 0.0;
        }

        // Free if subtotal meets free shipping threshold (and threshold > 0)
        if ($free_threshold > 0 && $cart_total >= $free_threshold) {
            $shipping_cost = 0.0;
        }
    } else {
        $shipping_cost = 0.0;
    }

    $grand_total = $cart_total - $discount_amount + $shipping_cost + $tax_amount;

    echo json_encode([
        'success' => true,
        'cart_count' => count($_SESSION['cart'] ?? []),
        'subtotal' => $cart_total,
        'formatted_subtotal' => 'GHS ' . number_format($cart_total, 2),
        'discount_amount' => $discount_amount,
        'formatted_discount' => 'GHS ' . number_format($discount_amount, 2),
        'shipping_cost' => $shipping_cost,
        'formatted_shipping' => $shipping_cost == 0 ? 'Free' : 'GHS ' . number_format($shipping_cost, 2),
        'tax_amount' => $tax_amount,
        'formatted_tax' => 'GHS ' . number_format($tax_amount, 2),
        'grand_total' => $grand_total,
        'formatted_grand_total' => 'GHS ' . number_format($grand_total, 2),
        'applied_coupon' => $applied_coupon,
        'shipping_enabled' => ($shipping_enabled === '1' || $shipping_enabled === 1 || $shipping_enabled === true) ? true : false,
        'free_shipping_threshold' => $free_threshold
    ]);
}



function validateCoupon($coupon_code)
{
    global $pdo;

    // Check if PDO connection exists
    if (!isset($pdo)) {
        return ['valid' => false, 'message' => 'Database connection error'];
    }

    try {
        // Get current date and user ID
        $current_date = date('Y-m-d H:i:s');
        $user_id = $_SESSION['user_id'] ?? 0;

        // Find active coupon
        $sql = "SELECT * FROM coupons 
                WHERE code = ? 
                AND is_active = 1 
                AND start_date <= ? 
                AND (end_date >= ? OR end_date IS NULL)
                AND (usage_limit IS NULL OR used_count < usage_limit)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$coupon_code, $current_date, $current_date]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid or expired coupon code'];
        }

        // Check if user has already used this coupon
        if ($coupon['user_usage_limit'] > 0 && $user_id > 0) {
            $usage_sql = "SELECT COUNT(*) as usage_count FROM coupon_usage 
                         WHERE coupon_id = ? AND user_id = ?";
            $usage_stmt = $pdo->prepare($usage_sql);
            $usage_stmt->execute([$coupon['coupon_id'], $user_id]);
            $usage_count = $usage_stmt->fetch(PDO::FETCH_ASSOC)['usage_count'];

            if ($usage_count >= $coupon['user_usage_limit']) {
                return ['valid' => false, 'message' => 'You have already used this coupon'];
            }
        }

        // Calculate cart total
        $cart_total = calculateCartTotal();

        // Check minimum order amount
        if ($cart_total < ($coupon['min_order_amount'] ?? 0)) {
            $min_amount = $coupon['min_order_amount'];
            return ['valid' => false, 'message' => "Minimum order amount of GHS {$min_amount} required"];
        }

        // Calculate discount amount
        $discount_amount = 0;

        switch ($coupon['discount_type']) {
            case 'percentage':
                $discount_amount = ($cart_total * $coupon['discount_value']) / 100;
                // Apply max discount limit if set
                if (!empty($coupon['max_discount_amount']) && $discount_amount > $coupon['max_discount_amount']) {
                    $discount_amount = $coupon['max_discount_amount'];
                }
                break;

            case 'fixed':
                $discount_amount = min($coupon['discount_value'], $cart_total);
                break;

            case 'shipping':
                // For shipping discounts, we'll handle this in the cart summary
                $discount_amount = $coupon['discount_value'];
                break;
        }

        return [
            'valid' => true,
            'coupon_id' => $coupon['coupon_id'],
            'discount_amount' => $discount_amount,
            'discount_type' => $coupon['discount_type'],
            'discount_value' => $coupon['discount_value'],
            'max_discount_amount' => $coupon['max_discount_amount'] ?? null
        ];
    } catch (Exception $e) {
        error_log("Coupon validation error: " . $e->getMessage());
        return ['valid' => false, 'message' => 'Error validating coupon'];
    }
}

// Add this function to calculate cart total
function calculateCartTotal()
{
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }

    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    return $total;
}

// Update the applyCoupon function to track usage
function applyCoupon()
{
    global $pdo;

    // Get JSON data properly
    $json_input = file_get_contents('php://input');
    $json_data = json_decode($json_input, true);
    $coupon_code = trim($json_data['coupon_code'] ?? '');

    if (empty($coupon_code)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a coupon code']);
        return;
    }

    try {
        // Validate coupon
        $coupon_valid = validateCoupon($coupon_code);

        if ($coupon_valid['valid']) {
            $_SESSION['applied_coupon'] = [
                'coupon_id' => $coupon_valid['coupon_id'],
                'code' => $coupon_code,
                'discount_amount' => $coupon_valid['discount_amount'],
                'discount_type' => $coupon_valid['discount_type'],
                'discount_value' => $coupon_valid['discount_value'],
                'max_discount_amount' => $coupon_valid['max_discount_amount'] ?? null
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Coupon applied successfully!',
                'discount_amount' => $coupon_valid['discount_amount'],
                'discount_type' => $coupon_valid['discount_type'],
                'discount_value' => $coupon_valid['discount_value']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $coupon_valid['message']]);
        }
    } catch (Exception $e) {
        error_log("Apply coupon error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error applying coupon']);
    }
}

// Update the removeCoupon function
function removeCoupon()
{
    unset($_SESSION['applied_coupon']);
    echo json_encode(['success' => true, 'message' => 'Coupon removed successfully']);
}

// Update get_applied_coupon function
function getAppliedCoupon()
{
    if (isset($_SESSION['applied_coupon'])) {
        echo json_encode(['success' => true, 'coupon' => $_SESSION['applied_coupon']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No coupon applied']);
    }
}

// Add this function to record coupon usage when order is placed
function recordCouponUsage($order_id)
{
    global $pdo;

    if (!isset($_SESSION['applied_coupon']) || !isset($_SESSION['user_id'])) {
        return;
    }

    $coupon = $_SESSION['applied_coupon'];
    $user_id = $_SESSION['user_id'];

    try {
        $ownTx = false;
        if ($pdo && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $ownTx = true;
        }

        // Record usage
        $usage_sql = "INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) 
                     VALUES (?, ?, ?, ?)";
        $usage_stmt = $pdo->prepare($usage_sql);
        $usage_stmt->execute([
            $coupon['coupon_id'],
            $user_id,
            $order_id,
            $coupon['discount_amount']
        ]);

        // Update coupon usage count
        $update_sql = "UPDATE coupons SET used_count = used_count + 1 WHERE coupon_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$coupon['coupon_id']]);

        if ($ownTx) {
            $pdo->commit();
        }
    } catch (Exception $e) {
        try { if ($ownTx && $pdo && $pdo->inTransaction()) { $pdo->rollBack(); } } catch (Exception $rbEx) { error_log('Rollback error in recordCouponUsage: ' . $rbEx->getMessage()); }
        error_log("Error recording coupon usage: " . $e->getMessage());
    }
}

function bulkRemoveFromCart()
{
    global $functions;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    // Get JSON data
    $json_data = json_decode(file_get_contents('php://input'), true);
    $cart_keys = $json_data['cart_keys'] ?? $_POST['cart_keys'] ?? [];
    
    if (empty($cart_keys)) {
        echo json_encode(['success' => false, 'message' => 'No items selected']);
        return;
    }

    // Validate that cart_keys is an array
    if (!is_array($cart_keys)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data format']);
        return;
    }

    $removed_count = 0;
    $failed_keys = [];
    
    // Remove each selected item
    foreach ($cart_keys as $cart_key) {
        if (isset($_SESSION['cart'][$cart_key])) {
            unset($_SESSION['cart'][$cart_key]);
            $removed_count++;
        } else {
            $failed_keys[] = $cart_key;
        }
    }

    $cartCount = $functions->getCartItemCount();
    $cartTotal = $functions->getCartTotal();

    $response = [
        'success' => true,
        'message' => "{$removed_count} item(s) removed from cart",
        'removed_count' => $removed_count,
        'cart_count' => $cartCount,
        'cart_total' => $cartTotal,
        'formatted_total' => $functions->formatPrice($cartTotal),
        'cart_preview' => getCartPreviewData()
    ];
    
    if (!empty($failed_keys)) {
        $response['failed_keys'] = $failed_keys;
    }

    echo json_encode($response);
}
