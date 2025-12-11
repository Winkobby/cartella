<?php
/**
 * Migration Script: Add Product Slugs
 * 
 * This script adds a 'slug' column to the products table and generates 
 * SEO-friendly slugs for all existing products based on their names.
 * 
 * Usage: php tools/add_product_slugs.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$database = new Database();
$pdo = $database->getConnection();

echo "=== Product Slug Migration Script ===\n\n";

// Step 1: Check if slug column already exists
echo "Step 1: Checking if 'slug' column exists...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'slug'");
$columnExists = $stmt->rowCount() > 0;

if (!$columnExists) {
    echo "  → Adding 'slug' column to products table...\n";
    $pdo->exec("ALTER TABLE products ADD COLUMN slug VARCHAR(255) UNIQUE AFTER name");
    echo "  ✓ Column added successfully!\n\n";
} else {
    echo "  ✓ Column already exists.\n\n";
}

// Step 2: Generate slugs for all products
echo "Step 2: Generating slugs for all products...\n";

function generateSlug($text) {
    // Convert to lowercase
    $slug = strtolower($text);
    
    // Replace spaces with hyphens
    $slug = str_replace(' ', '-', $slug);
    
    // Remove special characters, keep only alphanumeric and hyphens
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    
    // Remove multiple consecutive hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Trim hyphens from start and end
    $slug = trim($slug, '-');
    
    return $slug;
}

// Get all products
$stmt = $pdo->query("SELECT product_id, name, slug FROM products ORDER BY product_id");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$skipped = 0;
$errors = [];

foreach ($products as $product) {
    // Skip if slug already exists
    if (!empty($product['slug'])) {
        $skipped++;
        echo "  - Product ID {$product['product_id']}: Already has slug '{$product['slug']}'\n";
        continue;
    }
    
    // Generate slug from product name
    $baseSlug = generateSlug($product['name']);
    $slug = $baseSlug;
    $counter = 1;
    
    // Handle duplicate slugs by appending a number
    while (true) {
        $checkStmt = $pdo->prepare("SELECT product_id FROM products WHERE slug = ? AND product_id != ?");
        $checkStmt->execute([$slug, $product['product_id']]);
        
        if ($checkStmt->rowCount() === 0) {
            break; // Slug is unique
        }
        
        $counter++;
        $slug = $baseSlug . '-' . $counter;
    }
    
    // Update the product with the new slug
    try {
        $updateStmt = $pdo->prepare("UPDATE products SET slug = ? WHERE product_id = ?");
        $updateStmt->execute([$slug, $product['product_id']]);
        $updated++;
        echo "  ✓ Product ID {$product['product_id']}: '{$product['name']}' → '{$slug}'\n";
    } catch (Exception $e) {
        $errors[] = "Product ID {$product['product_id']}: " . $e->getMessage();
        echo "  ✗ Error updating product ID {$product['product_id']}\n";
    }
}

echo "\n=== Migration Summary ===\n";
echo "Total products: " . count($products) . "\n";
echo "Updated: $updated\n";
echo "Skipped (already had slugs): $skipped\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n✓ Migration completed!\n";
