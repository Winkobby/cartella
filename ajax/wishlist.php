<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output errors to response

// Function to send JSON response
function sendJsonResponse($data) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    echo json_encode($data);
    exit;
}

try {
    // Initialize database first
    $database = new Database();
    $database->getConnection();

    // Initialize functions and auth
    $functions = new Functions();
    $functions->setDatabase($database);

    $auth = new Auth();

    // Get action from request
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // Parse JSON request body if content type is JSON
    $json_data = [];
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $json_data = json_decode($input, true) ?? [];
    }

    // Check if user is logged in for wishlist actions
    if (in_array($action, ['add_to_wishlist', 'remove_from_wishlist', 'get_wishlist_status', 'get_wishlist_count', 'get_count', 'clear_wishlist', 'get_wishlist_items', 'get_wishlist', 'move_to_cart']) && !$auth->isLoggedIn()) {
        sendJsonResponse(['success' => false, 'message' => 'Please login to use wishlist']);
    }

    switch ($action) {
        case 'add_to_wishlist':
            addToWishlist($functions, $json_data);
            break;
        case 'remove_from_wishlist':
            removeFromWishlist($functions, $json_data);
            break;
        case 'get_wishlist_status':
            getWishlistStatus($functions);
            break;
        case 'get_wishlist_count':
            getWishlistCount($functions);
            break;
        case 'get_count':
            // Backwards-compatible alias
            getWishlistCount($functions);
            break;
        case 'clear_wishlist':
            clearWishlist($functions);
            break;
        case 'get_wishlist_items':
            getWishlistItems($functions);
            break;
        case 'get_wishlist':
            // Backwards-compatible endpoint used by some frontend pages (returns `products` key)
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id) {
                sendJsonResponse(['success' => false, 'message' => 'Please login to use wishlist']);
            }

            $items = $functions->getWishlistItems($user_id);
            if ($items === false) {
                sendJsonResponse(['success' => false, 'message' => 'Error loading wishlist items']);
            }

            $formatted_products = [];
            foreach ($items as $item) {
                $image_path = $item['main_image'];
                if (!empty($image_path) && strpos($image_path, '/') === false && strpos($image_path, 'assets/') === false) {
                    $image_path = 'assets/images/uploads/' . $image_path;
                }

                $formatted_products[] = [
                    'product_id' => $item['product_id'],
                    'name' => $item['name'],
                    'price' => floatval($item['price']),
                    'image_url' => $image_path,
                    'stock_quantity' => intval($item['stock_quantity']),
                    'formatted_price' => $functions->formatPrice($functions->calculateDiscountedPrice($item['price'], $item['discount']))
                ];
            }

            sendJsonResponse(['success' => true, 'products' => $formatted_products]);
            break;
        case 'move_to_cart':
            moveToCart($functions, $json_data);
            break;
        default:
            sendJsonResponse(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    // Log the error (you might want to log to a file in production)
    error_log("Wishlist API Error: " . $e->getMessage());
    
    sendJsonResponse([
        'success' => false, 
        'message' => 'An error occurred while processing your request',
        'error' => 'Internal server error'
    ]);
}

function addToWishlist($functions, $json_data) {
    $product_id = intval($json_data['product_id'] ?? $_POST['product_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if ($product_id <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid product']);
    }

    $result = $functions->addToWishlist($user_id, $product_id);
    
    // Get updated wishlist count
    if ($result['success']) {
        $result['wishlist_count'] = $functions->getWishlistCount($user_id);
    }
    
    sendJsonResponse($result);
}

function removeFromWishlist($functions, $json_data) {
    $product_id = intval($json_data['product_id'] ?? $_POST['product_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if ($product_id <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid product']);
    }

    $result = $functions->removeFromWishlist($user_id, $product_id);
    
    // Get updated wishlist count
    if ($result['success']) {
        $result['wishlist_count'] = $functions->getWishlistCount($user_id);
    }
    
    sendJsonResponse($result);
}

function getWishlistStatus($functions) {
    $product_id = intval($_GET['product_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if ($product_id <= 0) {
        sendJsonResponse(['success' => false, 'in_wishlist' => false]);
    }

    $inWishlist = $functions->isInWishlist($user_id, $product_id);
    sendJsonResponse(['success' => true, 'in_wishlist' => $inWishlist]);
}

function getWishlistCount($functions) {
    $user_id = $_SESSION['user_id'];
    $count = $functions->getWishlistCount($user_id);
    sendJsonResponse(['success' => true, 'count' => $count]);
}

function clearWishlist($functions) {
    $user_id = $_SESSION['user_id'];
    $result = $functions->clearWishlist($user_id);
    sendJsonResponse($result);
}

function getWishlistItems($functions) {
    $user_id = $_SESSION['user_id'];
    $items = $functions->getWishlistItems($user_id);
    
    if ($items === false) {
        sendJsonResponse(['success' => false, 'message' => 'Error loading wishlist items']);
    }
    
    // Format items for JSON response - MATCH CART'S FORMAT
    $formatted_items = [];
    foreach ($items as $item) {
        // Use the same image field name as cart ('image' instead of 'main_image')
        $image_path = $item['main_image'];
        
        // If it's just a filename, prepend the uploads path (like cart does)
        if (!empty($image_path) && strpos($image_path, '/') === false && strpos($image_path, 'assets/') === false) {
            $image_path = 'assets/images/uploads/' . $image_path;
        }
        
        // Use the same field name as cart
        $formatted_items[] = [
            'product_id' => $item['product_id'],
            'name' => $item['name'],
            'price' => floatval($item['price']),
            'discount' => floatval($item['discount']),
            'image' => $image_path, // Use 'image' field like cart does
            'stock_quantity' => intval($item['stock_quantity']),
            'date_added' => $item['date_added'],
            'category_name' => $item['category_name'],
            'discounted_price' => $functions->calculateDiscountedPrice($item['price'], $item['discount']),
            'formatted_price' => $functions->formatPrice($functions->calculateDiscountedPrice($item['price'], $item['discount'])),
            'formatted_original_price' => $functions->formatPrice($item['price'])
        ];
    }
    
    sendJsonResponse(['success' => true, 'items' => $formatted_items]);
}

function moveToCart($functions, $json_data) {
    $product_id = intval($json_data['product_id'] ?? $_POST['product_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if ($product_id <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid product']);
    }

    // First remove from wishlist
    $remove_result = $functions->removeFromWishlist($user_id, $product_id);
    
    if ($remove_result['success']) {
        // Then add to cart
        $add_result = $functions->addToCart($product_id, 1);
        
        if ($add_result) {
            // Get updated counts
            $wishlist_count = $functions->getWishlistCount($user_id);
            $cart_count = $functions->getCartCount($user_id);
            
            sendJsonResponse([
                'success' => true, 
                'message' => 'Product moved to cart successfully!',
                'wishlist_count' => $wishlist_count,
                'cart_count' => $cart_count
            ]);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Failed to add product to cart.']);
        }
    } else {
        sendJsonResponse(['success' => false, 'message' => $remove_result['message']]);
    }
}
?>