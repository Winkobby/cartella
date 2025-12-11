<?php
/**
 * Migration script to add slug column to categories table
 * and generate slugs for existing categories
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$database = new Database();
$pdo = $database->getConnection();

try {
    // Check if slug column exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM categories LIKE 'slug'");
    $columnExists = $checkColumn->rowCount() > 0;

    if (!$columnExists) {
        // Add slug column
        echo "Adding slug column to categories table...\n";
        $pdo->exec("ALTER TABLE categories ADD COLUMN slug VARCHAR(100) UNIQUE AFTER category_name");
        echo "✓ Slug column added\n";
    } else {
        echo "✓ Slug column already exists\n";
    }

    // Function to generate slug
    function generateSlug($text) {
        // Convert to lowercase
        $slug = strtolower($text);
        // Replace spaces with hyphens
        $slug = preg_replace('/\s+/', '-', $slug);
        // Remove any special characters
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        // Remove duplicate hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        // Trim hyphens
        $slug = trim($slug, '-');
        return $slug;
    }

    // Get all categories
    $categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_id")->fetchAll(PDO::FETCH_ASSOC);

    echo "\nGenerating slugs for categories:\n";
    foreach ($categories as $category) {
        $slug = generateSlug($category['category_name']);
        $stmt = $pdo->prepare("UPDATE categories SET slug = ? WHERE category_id = ?");
        $stmt->execute([$slug, $category['category_id']]);
        echo "✓ {$category['category_name']} → {$slug}\n";
    }

    echo "\n✓ Migration completed successfully!\n";
    echo "\nCategories table now has a 'slug' column.\n";
    echo "Usage: http://yoursite.com/products.php?category=electronics\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
