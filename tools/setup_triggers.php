<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$database = new Database();
$conn = $database->getConnection();

try {
    echo "Creating triggers...\n";
    
    // Trigger 1: On Insert
    $trigger1 = "
    CREATE TRIGGER IF NOT EXISTS `update_product_rating_on_insert` 
    AFTER INSERT ON `reviews`
    FOR EACH ROW
    BEGIN
        IF NEW.status = 'approved' THEN
            UPDATE `products` p
            SET p.average_rating = (
                SELECT COALESCE(AVG(r.rating), 0)
                FROM `reviews` r
                WHERE r.product_id = NEW.product_id 
                AND r.status = 'approved'
            ),
            p.review_count = (
                SELECT COUNT(*)
                FROM `reviews` r
                WHERE r.product_id = NEW.product_id 
                AND r.status = 'approved'
            )
            WHERE p.product_id = NEW.product_id;
        END IF;
    END;
    ";
    
    // Trigger 2: On Update
    $trigger2 = "
    CREATE TRIGGER IF NOT EXISTS `update_product_rating_on_update` 
    AFTER UPDATE ON `reviews`
    FOR EACH ROW
    BEGIN
        IF OLD.status != NEW.status OR OLD.rating != NEW.rating THEN
            UPDATE `products` p
            SET p.average_rating = (
                SELECT COALESCE(AVG(r.rating), 0)
                FROM `reviews` r
                WHERE r.product_id = OLD.product_id 
                AND r.status = 'approved'
            ),
            p.review_count = (
                SELECT COUNT(*)
                FROM `reviews` r
                WHERE r.product_id = OLD.product_id 
                AND r.status = 'approved'
            )
            WHERE p.product_id = OLD.product_id;
        END IF;
        
        UPDATE `products` p
        SET p.average_rating = (
            SELECT COALESCE(AVG(r.rating), 0)
            FROM `reviews` r
            WHERE r.product_id = NEW.product_id 
            AND r.status = 'approved'
        ),
        p.review_count = (
            SELECT COUNT(*)
            FROM `reviews` r
            WHERE r.product_id = NEW.product_id 
            AND r.status = 'approved'
        )
        WHERE p.product_id = NEW.product_id;
    END;
    ";
    
    // Trigger 3: On Delete (optional)
    $trigger3 = "
    CREATE TRIGGER IF NOT EXISTS `update_product_rating_on_delete` 
    AFTER DELETE ON `reviews`
    FOR EACH ROW
    BEGIN
        UPDATE `products` p
        SET p.average_rating = (
            SELECT COALESCE(AVG(r.rating), 0)
            FROM `reviews` r
            WHERE r.product_id = OLD.product_id 
            AND r.status = 'approved'
        ),
        p.review_count = (
            SELECT COUNT(*)
            FROM `reviews` r
            WHERE r.product_id = OLD.product_id 
            AND r.status = 'approved'
        )
        WHERE p.product_id = OLD.product_id;
    END;
    ";
    
    // Execute triggers
    $conn->exec("DROP TRIGGER IF EXISTS update_product_rating_on_insert");
    $conn->exec($trigger1);
    echo "✓ Created trigger for INSERT\n";
    
    $conn->exec("DROP TRIGGER IF EXISTS update_product_rating_on_update");
    $conn->exec($trigger2);
    echo "✓ Created trigger for UPDATE\n";
    
    $conn->exec("DROP TRIGGER IF EXISTS update_product_rating_on_delete");
    $conn->exec($trigger3);
    echo "✓ Created trigger for DELETE\n";
    
    echo "\n✅ All triggers created successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>