<?php
/**
 * Messages AJAX Endpoint - Debug Version
 */

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
session_start();

// Simple error handler to catch any issues
function handleError($e) {
    error_log("AJAX Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
    exit();
}

// Register error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

try {
    // Get the action parameter
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // Log the request for debugging
    error_log("AJAX Request: action=$action, POST=" . json_encode($_POST));
    
    if (empty($action)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No action specified'
        ]);
        exit();
    }
    
    // Include required files
    $config_path = __DIR__ . '/../includes/config.php';
    if (!file_exists($config_path)) {
        throw new Exception("Config file not found at: $config_path");
    }
    require_once $config_path;
    
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/settings_helper.php';
    
    // Check if user is authenticated
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access. Please login as admin.'
        ]);
        exit();
    }
    
    // Initialize database connection
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        SettingsHelper::init($pdo);
    } catch (Exception $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
    
    // Route to appropriate function based on action
    switch ($action) {
        case 'get_message_stats':
            getMessageStats($pdo);
            break;
            
        case 'get_messages':
            getMessages($pdo);
            break;
            
        case 'get_message_details':
            getMessageDetails($pdo);
            break;
            
        case 'update_message_status':
            updateMessageStatus($pdo);
            break;
            
        case 'send_reply':
            sendReply($pdo);
            break;
            
        case 'delete_message':
            deleteMessage($pdo);
            break;
            
        case 'bulk_action':
            bulkAction($pdo);
            break;
            
        case 'mark_all_read':
            markAllAsRead($pdo);
            break;
            
        case 'get_message_count':
            getMessageCount($pdo);
            break;
            
        case 'search_messages':
            searchMessages($pdo);
            break;

        case 'get_reply_history':
            getReplyHistory($pdo);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action: ' . $action
            ]);
            exit();
    }
    
} catch (Exception $e) {
    handleError($e);
}

/**
 * Get message statistics - SIMPLIFIED VERSION
 */
function getMessageStats($pdo) {
    try {
        $stats = [
            'total' => 0,
            'new' => 0,
            'read' => 0,
            'replied' => 0,
            'archived' => 0,
            'today' => 0,
            'week' => 0
        ];
        
        // Check if contacts table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'contacts'");
        if (!$table_check->rowCount()) {
            // Table doesn't exist, return empty stats
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            return;
        }
        
        // Total messages
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts");
        $stats['total'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // New messages
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'new'");
        $stats['new'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Read messages
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'read'");
        $stats['read'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Replied messages
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'replied'");
        $stats['replied'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Archived messages
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'archived'");
        $stats['archived'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Today's messages
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE DATE(created_at) = CURDATE()");
        $stats['today'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // This week's messages
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
        $stats['week'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Message stats error: ' . $e->getMessage());
    }
}

/**
 * Get messages with pagination - SIMPLIFIED VERSION
 */
function getMessages($pdo) {
    try {
        // Get parameters with defaults
        $filter = $_POST['filter'] ?? 'all';
        $page = max(1, (int)($_POST['page'] ?? 1));
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Check if table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'contacts'");
        if (!$table_check->rowCount()) {
            echo json_encode([
                'success' => true,
                'messages' => [],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 0,
                    'total_records' => 0,
                    'per_page' => $per_page,
                    'has_previous' => false,
                    'has_next' => false
                ]
            ]);
            return;
        }
        
        // Build WHERE clause
        $where = "1=1";
        $params = [];
        
        if ($filter !== 'all') {
            $where .= " AND status = ?";
            $params[] = $filter;
        }
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM contacts WHERE $where";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = (int)$count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get messages
        $sql = "SELECT 
                    id,
                    name,
                    email,
                    phone,
                    subject,
                    message,
                    status,
                    created_at
                FROM contacts 
                WHERE $where 
                ORDER BY 
                    CASE status 
                        WHEN 'new' THEN 1
                        WHEN 'read' THEN 2
                        WHEN 'replied' THEN 3
                        ELSE 4
                    END,
                    created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($sql);
        
        // Add pagination parameters
        $params[] = $per_page;
        $params[] = $offset;
        
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format messages
        foreach ($messages as &$message) {
            $message['created_at_formatted'] = formatDate($message['created_at']);
            $message['message_preview'] = strlen($message['message']) > 100 
                ? substr($message['message'], 0, 100) . '...' 
                : $message['message'];
        }
        
        // Calculate pagination
        $total_pages = ceil($total / $per_page);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_records' => $total,
                'per_page' => $per_page,
                'has_previous' => $page > 1,
                'has_next' => $page < $total_pages
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Get messages error: ' . $e->getMessage());
    }
}

/**
 * Get message details - SIMPLIFIED VERSION
 */
function getMessageDetails($pdo) {
    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Message ID is required'
            ]);
            return;
        }
        
        $sql = "SELECT 
                    id,
                    name,
                    email,
                    phone,
                    subject,
                    message,
                    status,
                    ip_address,
                    created_at
                FROM contacts 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$message) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Message not found'
            ]);
            return;
        }
        
        // Format date
        $message['created_at_formatted'] = formatDate($message['created_at']);
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Message details error: ' . $e->getMessage());
    }
}

/**
 * Update message status - SIMPLIFIED VERSION
 */
function updateMessageStatus($pdo) {
    try {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if (!$id) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Message ID is required'
            ]);
            return;
        }
        
        $valid_statuses = ['new', 'read', 'replied', 'archived'];
        if (!in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid status: ' . $status
            ]);
            return;
        }
        
        $sql = "UPDATE contacts SET status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Update status error: ' . $e->getMessage());
    }
}

/**
 * Send reply - SIMPLIFIED VERSION
 */
/**
 * Send reply - FIXED VERSION WITH contact_replies INSERT
 */
function sendReply($pdo) {
    try {
        $contact_id = (int)($_POST['contact_id'] ?? 0);
        $to_email = filter_var($_POST['to_email'] ?? '', FILTER_SANITIZE_EMAIL);
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $admin_id = $_SESSION['user_id'] ?? 0;
        
        // Validate inputs
        if (!$contact_id || !$to_email || !$subject || !$message || !$admin_id) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'All fields are required'
            ]);
            return;
        }
        
        if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email address: ' . $to_email
            ]);
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // 1. Update message status
            $sql = "UPDATE contacts SET status = 'replied' WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$contact_id]);
            
            // 2. Insert into contact_replies table
            $reply_sql = "INSERT INTO contact_replies (contact_id, admin_id, subject, message) VALUES (?, ?, ?, ?)";
            $reply_stmt = $pdo->prepare($reply_sql);
            $reply_stmt->execute([$contact_id, $admin_id, $subject, $message]);
            $reply_id = $pdo->lastInsertId();
            
            // 3. Send email to customer
            require_once __DIR__ . '/../includes/PHPMailerWrapper.php';
            $mailer = new PHPMailerWrapper();
            $adminName = $_SESSION['user_name'] ?? 'Admin';
            $replySubject = $subject;
            $replyBody = "<p>Dear Customer,</p><p>" . nl2br(htmlspecialchars($message)) . "</p><p>Best regards,<br>" . htmlspecialchars($adminName) . "</p>";
            $emailSent = $mailer->sendHtml($to_email, $replySubject, $replyBody);
            
            if ($emailSent) {
                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Reply sent and saved successfully',
                    'reply_id' => $reply_id
                ]);
            } else {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to send email to customer'
                ]);
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw new Exception('Transaction failed: ' . $e->getMessage());
        }
        
    } catch (Exception $e) {
        throw new Exception('Send reply error: ' . $e->getMessage());
    }
}

/**
 * Delete message - SIMPLIFIED VERSION
 */
function deleteMessage($pdo) {
    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Message ID is required'
            ]);
            return;
        }
        
        $sql = "DELETE FROM contacts WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Delete message error: ' . $e->getMessage());
    }
}

/**
 * Bulk actions - SIMPLIFIED VERSION
 */
function bulkAction($pdo) {
    try {
        $action = $_POST['bulk_action'] ?? '';
        $ids = $_POST['ids'] ?? '';
        
        if (empty($action) || empty($ids)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Action and message IDs are required'
            ]);
            return;
        }
        
        // Convert IDs to array
        $id_array = array_map('intval', explode(',', $ids));
        $id_array = array_filter($id_array);
        
        if (empty($id_array)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No valid message IDs provided'
            ]);
            return;
        }
        
        // Create placeholders
        $placeholders = str_repeat('?,', count($id_array) - 1) . '?';
        
        switch ($action) {
            case 'mark_read':
                $sql = "UPDATE contacts SET status = 'read' WHERE id IN ($placeholders)";
                break;
                
            case 'mark_replied':
                $sql = "UPDATE contacts SET status = 'replied' WHERE id IN ($placeholders)";
                break;
                
            case 'archive':
                $sql = "UPDATE contacts SET status = 'archived' WHERE id IN ($placeholders)";
                break;
                
            case 'delete':
                $sql = "DELETE FROM contacts WHERE id IN ($placeholders)";
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action: ' . $action
                ]);
                return;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($id_array);
        $affected = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'message' => "Action completed. $affected message(s) updated.",
            'affected' => $affected
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Bulk action error: ' . $e->getMessage());
    }
}

/**
 * Mark all as read
 */
function markAllAsRead($pdo) {
    try {
        $sql = "UPDATE contacts SET status = 'read' WHERE status = 'new'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $affected = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'message' => "$affected message(s) marked as read",
            'affected' => $affected
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Mark all read error: ' . $e->getMessage());
    }
}

/**
 * Get new message count
 */
function getMessageCount($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM contacts WHERE status = 'new'");
        $stmt->execute();
        $count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Message count error: ' . $e->getMessage());
    }
}

/**
 * Search messages
 */
function searchMessages($pdo) {
    try {
        $search_term = trim($_POST['search'] ?? '');
        $page = max(1, (int)($_POST['page'] ?? 1));
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        if (empty($search_term)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Search term is required'
            ]);
            return;
        }
        
        $where = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
        $params = [
            "%$search_term%",
            "%$search_term%",
            "%$search_term%",
            "%$search_term%"
        ];
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM contacts WHERE $where";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = (int)$count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get search results
        $sql = "SELECT 
                    id,
                    name,
                    email,
                    phone,
                    subject,
                    message,
                    status,
                    created_at
                FROM contacts 
                WHERE $where 
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $per_page;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format messages
        foreach ($messages as &$message) {
            $message['created_at_formatted'] = formatDate($message['created_at']);
            $message['message_preview'] = strlen($message['message']) > 100 
                ? substr($message['message'], 0, 100) . '...' 
                : $message['message'];
        }
        
        // Calculate pagination
        $total_pages = ceil($total / $per_page);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_records' => $total,
                'per_page' => $per_page
            ],
            'search_info' => [
                'term' => $search_term,
                'found' => $total
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Search messages error: ' . $e->getMessage());
    }
}

/**
 * Helper function to format date
 */
function formatDate($date_string) {
    if (empty($date_string)) return null;
    
    try {
        $date = new DateTime($date_string);
        $now = new DateTime();
        $interval = $now->diff($date);
        
        if ($interval->days === 0) {
            return 'Today at ' . $date->format('g:i A');
        } elseif ($interval->days === 1) {
            return 'Yesterday at ' . $date->format('g:i A');
        } elseif ($interval->days < 7) {
            return $date->format('l') . ' at ' . $date->format('g:i A');
        } else {
            return $date->format('M j, Y \a\t g:i A');
        }
    } catch (Exception $e) {
        return $date_string;
    }
}

/**
 * Get replied messages
 */
function getReplyHistory($pdo) {
    try {
        $contact_id = (int)($_POST['contact_id'] ?? 0);
        
        if (!$contact_id) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Contact ID is required'
            ]);
            return;
        }
        
        $sql = "SELECT cr.*, u.username, u.email as admin_email 
                FROM contact_replies cr 
                JOIN users u ON cr.admin_id = u.user_id 
                WHERE cr.contact_id = ? 
                ORDER BY cr.sent_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$contact_id]);
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($replies as &$reply) {
            $reply['sent_at_formatted'] = formatDate($reply['sent_at']);
            $reply['message_formatted'] = nl2br(htmlspecialchars($reply['message']));
        }
        
        echo json_encode([
            'success' => true,
            'replies' => $replies
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Get reply history error: ' . $e->getMessage());
    }
}
// End of file
?>