<?php
require_once 'includes/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if contacts table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'contacts'");
    if ($stmt->rowCount() > 0) {
        echo "✓ contacts table exists\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE contacts");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Columns: " . implode(", ", $columns) . "\n";
        
        // Count records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Total messages: $count\n";
    } else {
        echo "✗ contacts table MISSING - Please run the SQL file!\n";
        echo "\nRun this SQL in phpMyAdmin:\n";
        echo file_get_contents('database/contacts_table.sql');
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
