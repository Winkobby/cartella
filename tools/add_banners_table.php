<?php
/**
 * Migration Script: Add Banners Table
 * 
 * This script creates the banners table for managing homepage banners.
 * 
 * Usage: php tools/add_banners_table.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$database = new Database();
$pdo = $database->getConnection();

echo "=== Banners Table Migration Script ===\n\n";

// Step 1: Check if table exists
echo "Step 1: Checking if 'banners' table exists...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'banners'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        echo "  → Creating 'banners' table...\n";
        
        $sql = "CREATE TABLE `banners` (
            `banner_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(150) NOT NULL,
            `description` TEXT,
            `image_url` VARCHAR(255) NOT NULL,
            `link_url` VARCHAR(255),
            `button_text` VARCHAR(50) DEFAULT 'Shop Now',
            `is_active` TINYINT(1) DEFAULT 1,
            `position` INT(11) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active (is_active),
            INDEX idx_position (position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        echo "  ✓ Table created successfully!\n\n";
    } else {
        echo "  ✓ Table already exists.\n\n";
    }

    // Step 2: Insert sample banners
    echo "Step 2: Checking for existing banners...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM banners");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $bannerCount = $result['count'];

    if ($bannerCount === 0) {
        echo "  → Adding sample banners...\n";
        
        $samples = [
            [
                'title' => 'Summer Sale! Up to 50% Off',
                'description' => 'Limited time offer on selected items',
                'image_url' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
                'link_url' => 'products.php?filter=discounted',
                'button_text' => 'Shop Sale',
                'is_active' => 1,
                'position' => 1
            ],
            [
                'title' => 'New Arrivals',
                'description' => 'Check out the latest products added to our store',
                'image_url' => 'https://images.unsplash.com/photo-1556742212-5b321e3e5e2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
                'link_url' => 'products.php?sort=newest',
                'button_text' => 'View New',
                'is_active' => 1,
                'position' => 2
            ],
            [
                'title' => 'Free Shipping Offer',
                'description' => 'Get free shipping on orders over GHS500',
                'image_url' => 'https://images.unsplash.com/photo-1576941089067-2de3e3692519?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
                'link_url' => 'products.php',
                'button_text' => 'Start Shopping',
                'is_active' => 1,
                'position' => 3
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO banners (title, description, image_url, link_url, button_text, is_active, position) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($samples as $sample) {
            $stmt->execute([
                $sample['title'],
                $sample['description'],
                $sample['image_url'],
                $sample['link_url'],
                $sample['button_text'],
                $sample['is_active'],
                $sample['position']
            ]);
            echo "  ✓ Added banner: {$sample['title']}\n";
        }
        echo "\n";
    } else {
        echo "  ✓ Banners already exist ($bannerCount banners found).\n\n";
    }

    echo "✓ Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
