<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$database = new Database();

// Set JSON header for all responses
header('Content-Type: application/json');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'bulk_action':
                $bulk_action = $_POST['bulk_action'] ?? '';
                $selected_categories = $_POST['selected_categories'] ?? [];

                if (empty($selected_categories)) {
                    echo json_encode(['success' => false, 'message' => 'Please select categories to perform bulk action.']);
                    exit;
                }

                if (empty($bulk_action)) {
                    echo json_encode(['success' => false, 'message' => 'Please select a bulk action.']);
                    exit;
                }

                $category_ids = array_map('intval', $selected_categories);
                $placeholders = implode(',', array_fill(0, count($category_ids), '?'));

                switch ($bulk_action) {
                    case 'delete':
                        // Check if categories have products before deletion
                        $check_query = "SELECT COUNT(*) as product_count FROM products WHERE category_id IN ($placeholders)";
                        $check_stmt = $database->getConnection()->prepare($check_query);
                        $check_stmt->execute($category_ids);
                        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($result['product_count'] > 0) {
                            echo json_encode([
                                'success' => false, 
                                'message' => 'Cannot delete categories that have products. Please reassign or delete products first.'
                            ]);
                            exit;
                        }
                        
                        $query = "DELETE FROM categories WHERE category_id IN ($placeholders)";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute($category_ids);
                        $message = count($category_ids) . " category(ies) deleted successfully.";
                        break;

                    default:
                        echo json_encode(['success' => false, 'message' => 'Invalid bulk action.']);
                        exit;
                }

                echo json_encode(['success' => true, 'message' => $message]);
                exit;

            case 'delete':
                $category_id = intval($_POST['category_id'] ?? 0);

                if (!$category_id) {
                    echo json_encode(['success' => false, 'message' => 'Invalid category ID.']);
                    exit;
                }

                // Check if category has products before deletion
                $check_query = "SELECT COUNT(*) as product_count FROM products WHERE category_id = ?";
                $check_stmt = $database->getConnection()->prepare($check_query);
                $check_stmt->execute([$category_id]);
                $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['product_count'] > 0) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Cannot delete category that has products. Please reassign or delete products first.'
                    ]);
                    exit;
                }
                
                $query = "DELETE FROM categories WHERE category_id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$category_id]);
                
                echo json_encode(['success' => true, 'message' => 'Category deleted successfully.']);
                exit;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
                exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// If no valid action found
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
exit;
?>