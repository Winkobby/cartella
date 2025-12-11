<?php
// Minimal connection test
$host = 'localhost';
$db = 'cartmate_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    echo "✓ Connected to database\n\n";
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Products table columns:\n";
    echo str_repeat("=", 40) . "\n";
    
    $slug_exists = false;
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
        if (strtolower($col['Field']) === 'slug') {
            $slug_exists = true;
        }
    }
    
    echo str_repeat("=", 40) . "\n";
    if ($slug_exists) {
        echo "✓ SLUG COLUMN EXISTS\n";
    } else {
        echo "✗ SLUG COLUMN MISSING - Need to run migration\n";
    }
    
    // Test generateSlug function
    echo "\nTesting generateSlug function:\n";
    echo str_repeat("=", 40) . "\n";
    
    function generateSlug($text) {
        $slug = strtolower($text);
        $slug = str_replace(' ', '-', $slug);
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
    
    $test_names = [
        "iPhone 15 Pro Max",
        "Nike Air Max 270",
        "Product & Services",
        "Coffee's Best Brew",
        "50% Discount!"
    ];
    
    foreach ($test_names as $name) {
        $slug = generateSlug($name);
        echo "$name -> $slug\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
