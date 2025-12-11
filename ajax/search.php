<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Initialize database and functions
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Get search parameters
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : '';
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : '';
$brand = isset($_GET['brand']) ? $_GET['brand'] : '';

try {
    // Get search results
    $search_results = $functions->getProducts($category, $search_query, $sort, $page, $per_page, $price_min, $price_max, $brand);
    $products = $search_results['products'];
    $total_results = $search_results['total'];
    $total_pages = $search_results['total_pages'];

    // Prepare products data for JSON response
    $products_data = [];
    foreach ($products as $product) {
        $discounted_price = $functions->calculateDiscountedPrice($product['price'], $product['discount']);
        $avg_rating = $functions->getAverageRating($product['product_id']);
        $review_count = $functions->getProductReviews($product['product_id'], 1) ? count($functions->getProductReviews($product['product_id'], 1)) : 0;
        
        $products_data[] = [
            'product_id' => $product['product_id'],
            'name' => htmlspecialchars($product['name']),
            'description' => !empty($product['description']) ? htmlspecialchars($product['description']) : '',
            'price' => $product['price'],
            'discount' => $product['discount'],
            'discounted_price' => $discounted_price,
            'main_image' => $functions->getProductImage($product['main_image']),
            'category_name' => $product['category_name'],
            'is_new' => $product['is_new'],
            'average_rating' => $avg_rating,
            'review_count' => $review_count
        ];
    }

    echo json_encode([
        'success' => true,
        'products' => $products_data,
        'total_results' => $total_results,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'search_query' => $search_query
    ]);

} catch (Exception $e) {
    error_log("AJAX Search Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Search failed. Please try again.',
        'products' => [],
        'total_results' => 0,
        'total_pages' => 0,
        'current_page' => 1
    ]);
}
?>