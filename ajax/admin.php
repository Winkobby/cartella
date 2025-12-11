<?php
// ajax/admin.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';


// Suppress PHP errors from being sent to AJAX
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Check if user is admin
/* if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'customer') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
} */

$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_quick_stats':
            getQuickStats($database);
            break;
        case 'get_dashboard_stats':
            getDashboardStats($database);
            break;
        case 'get_recent_orders':
            getRecentOrders($database);
            break;
        case 'get_low_stock_products':
            getLowStockProducts($database);
            break;
        // Message management actions
        case 'get_message_count':
            getMessageCount($database);
            break;
        case 'get_message_stats':
            getMessageStats($database);
            break;
        case 'get_messages':
            getMessages($database);
            break;
        case 'get_message_details':
            getMessageDetails($database);
            break;
        case 'update_message_status':
            updateMessageStatus($database);
            break;
        case 'send_reply':
            sendReply($database);
            break;
        case 'delete_message':
            deleteMessage($database);
            break;
        case 'bulk_action':
            bulkAction($database);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getQuickStats($database) {
    try {
        // Get orders count (excluding cancelled)
        $orders_query = "SELECT COUNT(*) as count FROM orders WHERE status != 'cancelled'";
        $orders_stmt = $database->getConnection()->prepare($orders_query);
        $orders_stmt->execute();
        $orders_count = $orders_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Get products count
        $products_query = "SELECT COUNT(*) as count FROM products";
        $products_stmt = $database->getConnection()->prepare($products_query);
        $products_stmt->execute();
        $products_count = $products_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Get users count
        $users_query = "SELECT COUNT(*) as count FROM users";
        $users_stmt = $database->getConnection()->prepare($users_query);
        $users_stmt->execute();
        $users_count = $users_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Get today's orders
        $today_orders_query = "SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE()";
        $today_orders_stmt = $database->getConnection()->prepare($today_orders_query);
        $today_orders_stmt->execute();
        $today_orders_count = $today_orders_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Get total revenue (completed orders only)
        $revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status = 'processing' AND payment_status = 'completed'";
        $revenue_stmt = $database->getConnection()->prepare($revenue_query);
        $revenue_stmt->execute();
        $total_revenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
        
        echo json_encode([
            'success' => true,
            'orders' => (int)$orders_count,
            'products' => (int)$products_count,
            'users' => (int)$users_count,
            'today_orders' => (int)$today_orders_count,
            'total_revenue' => (float)$total_revenue
        ]);
        
    } catch (PDOException $e) {
        error_log("Quick Stats Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching statistics',
            'orders' => 0,
            'products' => 0,
            'users' => 0,
            'today_orders' => 0,
            'total_revenue' => 0
        ]);
    }
}

function getDashboardStats($database) {
    try {
        // Total orders
        $total_orders_query = "SELECT COUNT(*) as count FROM orders WHERE status != 'cancelled'";
        $total_orders_stmt = $database->getConnection()->prepare($total_orders_query);
        $total_orders_stmt->execute();
        $total_orders = $total_orders_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Pending orders
        $pending_orders_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
        $pending_orders_stmt = $database->getConnection()->prepare($pending_orders_query);
        $pending_orders_stmt->execute();
        $pending_orders = $pending_orders_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total products
        $total_products_query = "SELECT COUNT(*) as count FROM products";
        $total_products_stmt = $database->getConnection()->prepare($total_products_query);
        $total_products_stmt->execute();
        $total_products = $total_products_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Low stock products (less than 10)
        $low_stock_query = "SELECT COUNT(*) as count FROM products WHERE stock_quantity < 10";
        $low_stock_stmt = $database->getConnection()->prepare($low_stock_query);
        $low_stock_stmt->execute();
        $low_stock_products = $low_stock_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total customers
        $total_customers_query = "SELECT COUNT(*) as count FROM users WHERE role = 'Customer'";
        $total_customers_stmt = $database->getConnection()->prepare($total_customers_query);
        $total_customers_stmt->execute();
        $total_customers = $total_customers_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // New customers this month
        $new_customers_query = "SELECT COUNT(*) as count FROM users WHERE role = 'Customer' AND MONTH(date_joined) = MONTH(CURRENT_DATE()) AND YEAR(date_joined) = YEAR(CURRENT_DATE())";
        $new_customers_stmt = $database->getConnection()->prepare($new_customers_query);
        $new_customers_stmt->execute();
        $new_customers = $new_customers_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total revenue
        $revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status = 'processing' AND payment_status = 'completed'";
        $revenue_stmt = $database->getConnection()->prepare($revenue_query);
        $revenue_stmt->execute();
        $total_revenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
        
        // Revenue this month
        $month_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status = 'processing' AND payment_status = 'completed' AND MONTH(order_date) = MONTH(CURRENT_DATE()) AND YEAR(order_date) = YEAR(CURRENT_DATE())";
        $month_revenue_stmt = $database->getConnection()->prepare($month_revenue_query);
        $month_revenue_stmt->execute();
        $month_revenue = $month_revenue_stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_orders' => (int)$total_orders,
                'pending_orders' => (int)$pending_orders,
                'total_products' => (int)$total_products,
                'low_stock_products' => (int)$low_stock_products,
                'total_customers' => (int)$total_customers,
                'new_customers' => (int)$new_customers,
                'total_revenue' => (float)$total_revenue,
                'month_revenue' => (float)$month_revenue
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Dashboard Stats Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching dashboard statistics'
        ]);
    }
}

function getRecentOrders($database) {
    try {
        $query = "SELECT 
                    o.id,
                    o.order_number,
                    o.customer_name,
                    o.total_amount,
                    o.status,
                    o.order_date,
                    COUNT(oi.order_item_id) as item_count
                  FROM orders o
                  LEFT JOIN order_items oi ON o.id = oi.order_id
                  WHERE o.status != 'cancelled'
                  GROUP BY o.id
                  ORDER BY o.order_date DESC
                  LIMIT 10";
        
        $stmt = $database->getConnection()->prepare($query);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'orders' => $orders
        ]);
        
    } catch (PDOException $e) {
        error_log("Recent Orders Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching recent orders',
            'orders' => []
        ]);
    }
}

function getLowStockProducts($database) {
    try {
        $query = "SELECT 
                    product_id,
                    name,
                    brand,
                    stock_quantity,
                    price,
                    main_image
                  FROM products 
                  WHERE stock_quantity < 10 
                  ORDER BY stock_quantity ASC 
                  LIMIT 10";
        
        $stmt = $database->getConnection()->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
        
    } catch (PDOException $e) {
        error_log("Low Stock Products Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching low stock products',
            'products' => []
        ]);
    }
}

// Additional helper function for order status counts
function getOrderStatusCounts($database) {
    try {
        $query = "SELECT 
                    status,
                    COUNT(*) as count
                  FROM orders 
                  WHERE status != 'cancelled'
                  GROUP BY status";
        
        $stmt = $database->getConnection()->prepare($query);
        $stmt->execute();
        $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $status_counts;
        
    } catch (PDOException $e) {
        error_log("Order Status Counts Error: " . $e->getMessage());
        return [];
    }
}

// Function to get sales data for charts
function getSalesData($database, $period = 'monthly') {
    try {
        switch ($period) {
            case 'weekly':
                $query = "SELECT 
                            DATE_FORMAT(order_date, '%Y-%u') as period,
                            SUM(total_amount) as revenue,
                            COUNT(*) as orders
                          FROM orders 
                          WHERE status = 'processing' 
                            AND payment_status = 'completed'
                            AND order_date >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
                          GROUP BY DATE_FORMAT(order_date, '%Y-%u')
                          ORDER BY period DESC
                          LIMIT 12";
                break;
                
            case 'monthly':
            default:
                $query = "SELECT 
                            DATE_FORMAT(order_date, '%Y-%m') as period,
                            SUM(total_amount) as revenue,
                            COUNT(*) as orders
                          FROM orders 
                          WHERE status = 'processing' 
                            AND payment_status = 'completed'
                            AND order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                          GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                          ORDER BY period DESC
                          LIMIT 12";
                break;
        }
        
        $stmt = $database->getConnection()->prepare($query);
        $stmt->execute();
        $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $sales_data;
        
    } catch (PDOException $e) {
        error_log("Sales Data Error: " . $e->getMessage());
        return [];
    }
}

// Function to get top selling products
function getTopSellingProducts($database, $limit = 10) {
    try {
        $query = "SELECT 
                    p.product_id,
                    p.name,
                    p.brand,
                    p.price,
                    p.main_image,
                    SUM(oi.quantity) as total_sold
                  FROM products p
                  INNER JOIN order_items oi ON p.product_id = oi.product_id
                  INNER JOIN orders o ON oi.order_id = o.id
                  WHERE o.status = 'processing'
                  GROUP BY p.product_id, p.name, p.brand, p.price, p.main_image
                  ORDER BY total_sold DESC
                  LIMIT :limit";
        
        $stmt = $database->getConnection()->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $top_products;
        
    } catch (PDOException $e) {
        error_log("Top Selling Products Error: " . $e->getMessage());
        return [];
    }
}

// ==================== MESSAGE MANAGEMENT FUNCTIONS ====================

// Get new message count for notification badge
function getMessageCount($database) {
    try {
        $query = "SELECT COUNT(*) as count FROM contacts WHERE status = 'new'";
        $stmt = $database->getConnection()->prepare($query);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'success' => true,
            'count' => (int)$count
        ]);
    } catch (PDOException $e) {
        error_log("Message Count Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'count' => 0]);
    }
}

// Get message statistics
function getMessageStats($database) {
    try {
        $pdo = $database->getConnection();
        
        $total_query = "SELECT COUNT(*) as count FROM contacts";
        $total_stmt = $pdo->prepare($total_query);
        $total_stmt->execute();
        $total = $total_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $new_query = "SELECT COUNT(*) as count FROM contacts WHERE status = 'new'";
        $new_stmt = $pdo->prepare($new_query);
        $new_stmt->execute();
        $new = $new_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $read_query = "SELECT COUNT(*) as count FROM contacts WHERE status = 'read'";
        $read_stmt = $pdo->prepare($read_query);
        $read_stmt->execute();
        $read = $read_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $replied_query = "SELECT COUNT(*) as count FROM contacts WHERE status = 'replied'";
        $replied_stmt = $pdo->prepare($replied_query);
        $replied_stmt->execute();
        $replied = $replied_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total' => (int)$total,
                'new' => (int)$new,
                'read' => (int)$read,
                'replied' => (int)$replied
            ]
        ]);
    } catch (PDOException $e) {
        error_log("Message Stats Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching stats']);
    }
}

// Get messages with pagination
function getMessages($database) {
    try {
        $pdo = $database->getConnection();
        $filter = $_POST['filter'] ?? 'all';
        $page = (int)($_POST['page'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $where = "WHERE 1=1";
        if ($filter !== 'all') {
            $where .= " AND status = :status";
        }
        
        // Check if contacts table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'contacts'");
        if ($table_check->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Contacts table missing. Please run the SQL migration.']);
            return;
        }

        $count_query = "SELECT COUNT(*) as count FROM contacts $where";
        $count_stmt = $pdo->prepare($count_query);
        if ($filter !== 'all') {
            $count_stmt->bindValue(':status', $filter, PDO::PARAM_STR);
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $query = "SELECT * FROM contacts $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
        if ($filter !== 'all') {
            $stmt->bindValue(':status', $filter, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_pages = ceil($total / $per_page);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_records' => (int)$total,
                'per_page' => $per_page
            ]
        ]);
    } catch (PDOException $e) {
        error_log("Get Messages Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching messages']);
    }
}

// Get message details
function getMessageDetails($database) {
    try {
        $pdo = $database->getConnection();
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
            return;
        }
        
        // Check if contacts table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'contacts'");
        if ($table_check->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Contacts table missing. Please run the SQL migration.']);
            return;
        }

        $query = "SELECT * FROM contacts WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$message) {
            echo json_encode(['success' => false, 'message' => 'Message not found']);
            return;
        }
        
        echo json_encode(['success' => true, 'message' => $message]);
    } catch (PDOException $e) {
        error_log("Get Message Details Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching message details']);
    }
}

// Update message status
function updateMessageStatus($database) {
    try {
        $pdo = $database->getConnection();
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if (!$id || !in_array($status, ['new', 'read', 'replied', 'archived'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }
        
        // Check if contacts table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'contacts'");
        if ($table_check->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Contacts table missing. Please run the SQL migration.']);
            return;
        }

        $query = "UPDATE contacts SET status = :status WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } catch (PDOException $e) {
        error_log("Update Message Status Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating status']);
    }
}

// Send reply
function sendReply($database) {
    try {
        require_once '../includes/Mailer.php';
        require_once '../includes/settings_helper.php';
        
        $pdo = $database->getConnection();
        SettingsHelper::init($pdo);
        
        $contact_id = (int)($_POST['contact_id'] ?? 0);
        $to_email = trim($_POST['to_email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        if (!$contact_id || !$to_email || !$subject || !$message) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            return;
        }
        
        $site_name = SettingsHelper::get($pdo, 'site_name', SITE_NAME);
        $site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        
        $mailer = new Mailer();
        $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333}.container{max-width:600px;margin:0 auto;padding:20px}.header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:30px;text-align:center;border-radius:8px 8px 0 0}.content{background:#f9f9f9;padding:30px;border-radius:0 0 8px 8px}.footer{text-align:center;margin-top:20px;color:#666;font-size:12px}</style></head><body><div class='container'><div class='header'><h1 style='margin:0'>Message from {$site_name}</h1></div><div class='content'>" . nl2br(htmlspecialchars($message)) . "<p style='margin-top:30px'>Best regards,<br><strong>The {$site_name} Team</strong></p></div><div class='footer'><p>This email was sent from {$site_name}</p><p><a href='{$site_url}' style='color:#667eea'>Visit our website</a></p></div></div></body></html>";
        
        $sent = $mailer->sendHtml($to_email, $subject, $html);
        
        // Check if contacts table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'contacts'");
        if ($table_check->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Contacts table missing. Please run the SQL migration.']);
            return;
        }

        if ($sent) {
            $update_query = "UPDATE contacts SET status = 'replied' WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->bindValue(':id', $contact_id, PDO::PARAM_INT);
            $update_stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Reply sent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email']);
        }
    } catch (Exception $e) {
        error_log("Send Reply Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error sending reply']);
    }
}

// Delete message
function deleteMessage($database) {
    try {
        $pdo = $database->getConnection();
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
            return;
        }
        
        // Check if contacts table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'contacts'");
        if ($table_check->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Contacts table missing. Please run the SQL migration.']);
            return;
        }

        $query = "DELETE FROM contacts WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
    } catch (PDOException $e) {
        error_log("Delete Message Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error deleting message']);
    }
}

// Bulk actions
function bulkAction($database) {
    try {
        $pdo = $database->getConnection();
        $action = $_POST['bulk_action'] ?? '';
        $ids = $_POST['ids'] ?? '';
        
        if (!$action || !$ids) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }
        
        $id_array = explode(',', $ids);
        $id_array = array_map('intval', $id_array);
        $id_array = array_filter($id_array);
        
        if (empty($id_array)) {
            echo json_encode(['success' => false, 'message' => 'No valid IDs provided']);
            return;
        }
        
        $placeholders = str_repeat('?,', count($id_array) - 1) . '?';
        
        // Check if contacts table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'contacts'");
        if ($table_check->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Contacts table missing. Please run the SQL migration.']);
            return;
        }

        switch ($action) {
            case 'mark_read':
                $query = "UPDATE contacts SET status = 'read' WHERE id IN ($placeholders)";
                break;
            case 'archive':
                $query = "UPDATE contacts SET status = 'archived' WHERE id IN ($placeholders)";
                break;
            case 'delete':
                $query = "DELETE FROM contacts WHERE id IN ($placeholders)";
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                return;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($id_array);
        
        echo json_encode(['success' => true, 'message' => 'Bulk action completed successfully']);
    } catch (PDOException $e) {
        error_log("Bulk Action Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error performing bulk action']);
    }
}
?>

