<?php
// ajax/coupon.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Start session to access database connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get current date and time
    $now = date('Y-m-d H:i:s');
    
    // Debug: Log the current time
    error_log("Current time for coupon query: " . $now);
    
    // Fixed query using positional parameters
    $sql = "SELECT 
                code, 
                description, 
                discount_type, 
                discount_value, 
                min_order_amount,
                max_discount_amount
            FROM coupons 
            WHERE is_active = 1 
            AND start_date <= ? 
            AND end_date >= ? 
            AND (usage_limit IS NULL OR used_count < usage_limit)
            ORDER BY 
                CASE 
                    WHEN discount_type = 'percentage' THEN discount_value 
                    WHEN discount_type = 'fixed' THEN discount_value * 10
                    ELSE 0 
                END DESC,
                created_at DESC
            LIMIT 3";
    
    $stmt = $pdo->prepare($sql);
    
    // Execute with positional parameters
    $stmt->execute([$now, $now]);
    
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the number of coupons found
    error_log("Coupons found: " . count($coupons));
    
    echo json_encode([
        'success' => true,
        'coupons' => $coupons,
        'count' => count($coupons)
    ]);
    
} catch (PDOException $e) {
    error_log("PDO Error in coupon.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'coupons' => []
    ]);
} catch (Exception $e) {
    error_log("General Error in coupon.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'coupons' => []
    ]);
}
?>