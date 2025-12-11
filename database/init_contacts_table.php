<?php
// Initialize contacts table if it doesn't exist
// Run this once to set up the database schema

require_once '../includes/config.php';
require_once '../includes/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Create contacts table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS `contacts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(20),
        `subject` VARCHAR(255) NOT NULL,
        `message` LONGTEXT NOT NULL,
        `ip_address` VARCHAR(45),
        `user_agent` TEXT,
        `status` ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_email` (`email`),
        KEY `idx_created_at` (`created_at`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    echo json_encode(['success' => true, 'message' => 'Contacts table created successfully']);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
