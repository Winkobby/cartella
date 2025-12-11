<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if reviews table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'reviews'");
    if ($stmt->rowCount() > 0) {
        echo "✓ reviews table exists\n";
        // Check table structure
        $stmt = $pdo->query("DESCRIBE reviews");
        $columns = $stmt->fetchAll();
        echo "Columns:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "✗ reviews table DOES NOT exist\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
