<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$db = new Database();
$pdo = $db->getConnection();

echo "=== Orders Table ===\n";
$stmt = $pdo->query('DESCRIBE orders');
$cols = $stmt->fetchAll();
foreach($cols as $c) {
    echo "  - {$c['Field']}\n";
}

echo "\n=== Order Items Table ===\n";
$stmt = $pdo->query('DESCRIBE order_items');
$cols = $stmt->fetchAll();
foreach($cols as $c) {
    echo "  - {$c['Field']}\n";
}
?>
