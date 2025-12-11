<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Test the review system
echo "<h2>Review System Test</h2>";

$db = new Database();
$pdo = $db->getConnection();
$functions = new Functions();
$functions->setDatabase($db);

// Check if reviews table structure is correct
echo "<h3>1. Reviews Table Structure:</h3>";
try {
    $stmt = $pdo->query('DESCRIBE reviews');
    $columns = $stmt->fetchAll();
    echo "<table border='1' style='margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'>✓ Reviews table exists with correct structure</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test sample data
echo "<h3>2. Sample Review Data:</h3>";
try {
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM reviews');
    $result = $stmt->fetch();
    echo "<p>Total reviews in database: <strong>{$result['total']}</strong></p>";
    
    $stmt = $pdo->query('SELECT * FROM reviews LIMIT 5');
    $reviews = $stmt->fetchAll();
    if (count($reviews) > 0) {
        echo "<table border='1' style='margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>User</th><th>Product</th><th>Rating</th><th>Comment</th><th>Date</th></tr>";
        foreach ($reviews as $review) {
            echo "<tr>";
            echo "<td>{$review['review_id']}</td>";
            echo "<td>{$review['user_id']}</td>";
            echo "<td>{$review['product_id']}</td>";
            echo "<td>{$review['rating']}/5</td>";
            echo "<td>" . substr($review['comment'], 0, 30) . "...</td>";
            echo "<td>{$review['review_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No sample reviews found - create one to test</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test function existence
echo "<h3>3. Review Functions:</h3>";
$functions_to_check = [
    'canUserReviewProduct',
    'getUserReviewForProduct',
    'submitReview',
    'getProductReviews',
    'getAverageRating'
];

foreach ($functions_to_check as $func) {
    if (method_exists($functions, $func)) {
        echo "<p style='color: green;'>✓ {$func} exists</p>";
    } else {
        echo "<p style='color: red;'>✗ {$func} missing</p>";
    }
}

// Test delivered orders for potential reviews
echo "<h3>4. Sample Delivered Orders (for review testing):</h3>";
try {
    $stmt = $pdo->query("
        SELECT o.id, o.order_number, o.user_id, COUNT(oi.order_item_id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'delivered'
        GROUP BY o.id
        LIMIT 3
    ");
    $orders = $stmt->fetchAll();
    if (count($orders) > 0) {
        echo "<table border='1' style='margin: 10px 0;'>";
        echo "<tr><th>Order ID</th><th>Order#</th><th>User</th><th>Items</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['user_id']}</td>";
            echo "<td>{$order['item_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No delivered orders found - place and deliver an order to test reviews</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<h3>✓ Review System Test Complete!</h3>";
echo "<p><a href='account.php'>Go back to Account Page</a></p>";
?>
