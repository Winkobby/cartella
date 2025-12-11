<?php
// Initialize notification preferences table if it doesn't exist
// Run this once to set up the database schema

require_once '../includes/config.php';
require_once '../includes/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Create notification preferences table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
        `preference_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT(11) NOT NULL,
        `new_products` TINYINT(1) DEFAULT 1,
        `featured_products` TINYINT(1) DEFAULT 1,
        `sales_promotions` TINYINT(1) DEFAULT 1,
        `important_news` TINYINT(1) DEFAULT 1,
        `order_updates` TINYINT(1) DEFAULT 1,
        `newsletter` TINYINT(1) DEFAULT 1,
        `product_reviews` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `user_id` (`user_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    
    echo "<h2 style='color: green;'>✓ Notification Preferences Table Created Successfully</h2>";
    echo "<p>You can now use the notification preferences feature in the account page.</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Error Creating Table</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
