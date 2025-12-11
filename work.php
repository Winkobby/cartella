<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$database = new Database();

try {
    $pdo = $database->getConnection();
    
    $test_ids = [8, 19, 23, 20, 14, 11, 12, 2, 25];
    
    echo "<h2>Testing Product Existence</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Product ID</th><th>Exists?</th><th>Product Name</th></tr>";
    
    foreach ($test_ids as $id) {
        $stmt = $pdo->prepare("SELECT product_id, name FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<tr>";
        echo "<td>$id</td>";
        echo "<td>" . ($product ? "YES" : "NO") . "</td>";
        echo "<td>" . ($product ? $product['name'] : "Not Found") . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Also test the IN clause
    echo "<h2>Testing IN Clause</h2>";
    $placeholders = str_repeat('?,', count($test_ids) - 1) . '?';
    $sql = "SELECT product_id, name FROM products WHERE product_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($test_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($products) . " products with IN clause<br>";
    foreach ($products as $p) {
        echo "ID: " . $p['product_id'] . " - " . $p['name'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>