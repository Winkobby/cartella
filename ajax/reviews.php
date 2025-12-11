<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Initialize database and functions
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);
$conn = $database->getConnection();

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit reviews']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle different actions
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $input = json_decode($input, true);
        $action = $input['action'] ?? '';
    } else {
        $action = $_POST['action'] ?? '';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
}

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

switch ($action) {
    case 'get_pending_reviews':
        getPendingReviews($conn, $user_id);
        break;

    case 'get_order_items_for_review':
        getOrderItemsForReview($conn, $functions, $user_id);
        break;

    case 'submit_review':
        submitProductReview($conn, $functions, $user_id);
        break;

    case 'skip_review':
        skipReview($conn, $user_id);
        break;

    case 'get_product_reviews':
        getProductReviews($conn, $_GET['product_id'] ?? 0);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Get pending reviews for delivered orders
 */
function getPendingReviews($conn, $user_id)
{
    try {
        // First, get orders that are delivered and not reviewed yet
        $sql = "SELECT 
                    o.id as order_id,
                    o.order_number,
                    o.order_date,
                    COUNT(DISTINCT oi.product_id) as unreviewed_count,
                    GROUP_CONCAT(DISTINCT p.name SEPARATOR '||') as product_names,
                    GROUP_CONCAT(DISTINCT oi.product_id SEPARATOR ',') as product_ids
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.product_id
                LEFT JOIN reviews r ON oi.product_id = r.product_id AND r.user_id = o.user_id
                WHERE o.user_id = ? 
                    AND o.status = 'delivered'
                    AND r.review_id IS NULL
                GROUP BY o.id, o.order_number, o.order_date
                HAVING unreviewed_count > 0
                ORDER BY o.order_date DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $pendingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the response
        $reviews = [];
        foreach ($pendingOrders as $order) {
            $productNames = explode('||', $order['product_names']);
            $productIds = explode(',', $order['product_ids']);

            for ($i = 0; $i < count($productNames); $i++) {
                $reviews[] = [
                    'order_id' => $order['order_id'],
                    'order_number' => $order['order_number'],
                    'product_id' => $productIds[$i] ?? 0,
                    'product_name' => $productNames[$i] ?? 'Unknown Product',
                    'order_date' => $order['order_date']
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'reviews' => $reviews,
            'count' => count($reviews)
        ]);
    } catch (Exception $e) {
        error_log("Error in getPendingReviews: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching pending reviews', 'error' => $e->getMessage()]);
    }
}

/**
 * Get order items for review modal
 */
function getOrderItemsForReview($conn, $functions, $user_id) {
    try {
        $order_id = $_GET['order_id'] ?? 0;
        if (!$order_id) {
            echo json_encode(['success' => false, 'message' => 'Order ID is required']);
            return;
        }
        
        // Verify the order belongs to the user and is delivered
        $verify_sql = "SELECT id, order_number FROM orders 
                      WHERE id = ? AND user_id = ? AND status = 'delivered'";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->execute([$order_id, $user_id]);
        $order = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Invalid order or order not delivered yet']);
            return;
        }
        
        // FIXED SQL QUERY - Added COALESCE to handle null values
        $sql = "SELECT 
                    oi.order_item_id,
                    oi.product_id,
                    COALESCE(oi.product_name, p.name) as product_name,
                    COALESCE(oi.product_price, p.price) as product_price,
                    oi.quantity,
                    p.main_image,
                    r.review_id,
                    r.rating,
                    r.comment,
                    r.created_at as review_date
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                LEFT JOIN reviews r ON oi.product_id = r.product_id AND r.user_id = ?
                WHERE oi.order_id = ?
                ORDER BY oi.order_item_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Always ensure items is an array
        if ($items === false) {
            $items = [];
        }
        
        // Format product images and determine if review exists
        foreach ($items as &$item) {
            // Parse product images
            $images = [];
            $main_image = '';
            
            if (!empty($item['main_image'])) {
                try {
                    $decoded = json_decode($item['main_image'], true);
                    if (is_array($decoded) && !empty($decoded)) {
                        $images = $decoded;
                        $main_image = $images[0];
                    } else {
                        $main_image = $item['main_image'];
                    }
                } catch (Exception $e) {
                    $main_image = $item['main_image'];
                }
            }
            
            // Set the main image URL
            if (empty($main_image)) {
                $main_image = 'assets/images/placeholder-product.jpg';
            }
            
            // Ensure image path is correct
            if (strpos($main_image, 'http') !== 0) {
                if (strpos($main_image, 'assets/') !== 0 && strpos($main_image, '../') !== 0) {
                    $main_image = 'assets/uploads/' . ltrim($main_image, '/');
                }
            }
            
            // Ensure all fields have values
            $item['main_image'] = $main_image;
            $item['product_name'] = $item['product_name'] ?? 'Unknown Product';
            $item['product_price'] = floatval($item['product_price'] ?? 0);
            $item['quantity'] = intval($item['quantity'] ?? 1);
            $item['has_review'] = !empty($item['review_id']);
            $item['rating'] = $item['rating'] ?? 0;
            $item['comment'] = $item['comment'] ?? '';
            $item['review_date'] = $item['review_date'] ?? '';
        }
        
        echo json_encode([
            'success' => true,
            'order_number' => $order['order_number'],
            'items' => $items
        ]);
        
    } catch (Exception $e) {
        error_log("Error in getOrderItemsForReview: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error fetching order items', 
            'error' => $e->getMessage(),
            'items' => []
        ]);
    }
}

/**
 * Submit a product review
 */
function submitProductReview($conn, $functions, $user_id)
{
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data) {
            $data = $_POST;
        }

        $product_id = $data['product_id'] ?? 0;
        $rating = intval($data['rating'] ?? 0);
        $comment = trim($data['comment'] ?? '');

        // Validate input
        if (!$product_id || $product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            return;
        }

        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
            return;
        }

        if (empty($comment) || strlen($comment) < 10) {
            echo json_encode(['success' => false, 'message' => 'Please write a review of at least 10 characters']);
            return;
        }

        if (strlen($comment) > 1000) {
            echo json_encode(['success' => false, 'message' => 'Review cannot exceed 1000 characters']);
            return;
        }

        // Check if user can review this product
        if (!$functions->canUserReviewProduct($user_id, $product_id)) {
            echo json_encode(['success' => false, 'message' => 'You can only review products you have purchased and received']);
            return;
        }

        // Check if user already reviewed this product
        if ($functions->getUserReviewForProduct($user_id, $product_id)) {
            echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
            return;
        }

        // Submit the review
        $result = $functions->submitReview($user_id, $product_id, $rating, $comment);

        if ($result['success']) {
            // Update product review count and average rating
            updateProductReviewStats($conn, $product_id);
        }

        echo json_encode($result);
    } catch (Exception $e) {
        error_log("Error in submitProductReview: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error submitting review', 'error' => $e->getMessage()]);
    }
}

/**
 * Update product review statistics
 */
function updateProductReviewStats($conn, $product_id)
{
    try {
        $sql = "UPDATE products p 
                SET p.average_rating = (
                    SELECT COALESCE(AVG(rating), 0) 
                    FROM reviews 
                    WHERE product_id = ? AND status = 'approved'
                ),
                p.review_count = (
                    SELECT COUNT(*) 
                    FROM reviews 
                    WHERE product_id = ? AND status = 'approved'
                )
                WHERE p.product_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$product_id, $product_id, $product_id]);
    } catch (Exception $e) {
        error_log("Error updating product review stats: " . $e->getMessage());
    }
}

/**
 * Skip review for a product
 */
function skipReview($conn, $user_id)
{
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data) {
            $data = $_POST;
        }

        $product_id = $data['product_id'] ?? 0;
        $order_id = $data['order_id'] ?? 0;

        if (!$product_id || !$order_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid product or order']);
            return;
        }

        // Just return success - skipping means we don't store anything
        // Client-side will handle removing the item from the list
        echo json_encode(['success' => true, 'message' => 'Review skipped']);
    } catch (Exception $e) {
        error_log("Error in skipReview: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error skipping review']);
    }
}


/**
 * Get reviews for a specific product
 */
function getProductReviews($conn, $product_id)
{
    try {
        if (!$product_id || $product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            return;
        }

        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = intval($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        // Get reviews with user information
        $sql = "SELECT r.*, u.full_name, u.email 
                FROM reviews r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.product_id = ? 
                AND r.status = 'approved'
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$product_id, $limit, $offset]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $count_sql = "SELECT COUNT(*) as total 
                      FROM reviews 
                      WHERE product_id = ? AND status = 'approved'";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->execute([$product_id]);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Format user names (use first name only)
        foreach ($reviews as &$review) {
            $nameParts = explode(' ', $review['full_name'], 2);
            $review['user_name'] = $nameParts[0];
            $review['initial'] = strtoupper(substr($review['user_name'], 0, 1));
            $review['formatted_date'] = date('M j, Y', strtotime($review['created_at']));
        }

        echo json_encode([
            'success' => true,
            'reviews' => $reviews,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    } catch (Exception $e) {
        error_log("Error in getProductReviews: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching reviews']);
    }
}
