<?php
// Set header immediately before any output
header('Content-Type: application/json; charset=utf-8');

// Disable output buffering to prevent HTML from being prepended
if (ob_get_level() > 0) {
    ob_clean();
}

try {
    require_once '../includes/config.php';
    require_once '../includes/db.php';
    require_once '../includes/functions.php';
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $database = new Database();
    $conn = $database->getConnection();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error initializing: ' . $e->getMessage()]);
    exit;
}

// Handle AJAX actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_preferences':
                // Get user's notification preferences
                $stmt = $conn->prepare("
                    SELECT * FROM user_notification_preferences WHERE user_id = ?
                ");
                $stmt->execute([$user_id]);
                $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // If no preferences exist, create default ones
                if (!$preferences) {
                    $stmt = $conn->prepare("
                        INSERT INTO user_notification_preferences (user_id) VALUES (?)
                    ");
                    $stmt->execute([$user_id]);
                    
                    // Fetch the newly created preferences
                    $stmt = $conn->prepare("
                        SELECT * FROM user_notification_preferences WHERE user_id = ?
                    ");
                    $stmt->execute([$user_id]);
                    $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                
                echo json_encode([
                    'success' => true,
                    'preferences' => $preferences
                ]);
                break;
            
            case 'update_preference':
                // Update a specific preference
                $preference_key = $_POST['preference_key'] ?? '';
                $value = isset($_POST['value']) ? (int)$_POST['value'] : 0;
                
                if (!$preference_key) {
                    echo json_encode(['success' => false, 'message' => 'Invalid preference key']);
                    exit;
                }
                
                // Validate preference key
                $valid_keys = ['new_products', 'featured_products', 'sales_promotions', 'important_news', 'order_updates', 'newsletter', 'product_reviews'];
                
                if (!in_array($preference_key, $valid_keys)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid preference key']);
                    exit;
                }
                
                // Check if preferences exist for this user
                $check_stmt = $conn->prepare("SELECT preference_id FROM user_notification_preferences WHERE user_id = ?");
                $check_stmt->execute([$user_id]);
                $exists = $check_stmt->fetch();
                
                if (!$exists) {
                    // Create default preferences first
                    $insert_stmt = $conn->prepare("INSERT INTO user_notification_preferences (user_id) VALUES (?)");
                    $insert_stmt->execute([$user_id]);
                }
                
                // Update the preference
                $update_query = "UPDATE user_notification_preferences SET {$preference_key} = ?, updated_at = NOW() WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->execute([$value, $user_id]);
                
                // If updating newsletter preference, also update newsletter_subscribers table
                if ($preference_key === 'newsletter') {
                    updateNewsletterSubscription($user_id, $value, $conn);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Preference updated successfully',
                    'preference_key' => $preference_key,
                    'value' => $value
                ]);
                break;
            
            case 'update_all_preferences':
                // Update multiple preferences at once
                $preferences_data = json_decode(file_get_contents('php://input'), true);
                
                if (!$preferences_data) {
                    echo json_encode(['success' => false, 'message' => 'No preferences provided']);
                    exit;
                }
                
                // Check if preferences exist for this user
                $check_stmt = $conn->prepare("SELECT preference_id FROM user_notification_preferences WHERE user_id = ?");
                $check_stmt->execute([$user_id]);
                $exists = $check_stmt->fetch();
                
                if (!$exists) {
                    // Create default preferences first
                    $insert_stmt = $conn->prepare("INSERT INTO user_notification_preferences (user_id) VALUES (?)");
                    $insert_stmt->execute([$user_id]);
                }
                
                // Valid keys
                $valid_keys = ['new_products', 'featured_products', 'sales_promotions', 'important_news', 'order_updates', 'newsletter', 'product_reviews'];
                $updates = [];
                $values = [];
                
                foreach ($preferences_data as $key => $value) {
                    if (in_array($key, $valid_keys)) {
                        $updates[] = "{$key} = ?";
                        $values[] = (int)$value;
                    }
                }
                
                if (empty($updates)) {
                    echo json_encode(['success' => false, 'message' => 'No valid preferences to update']);
                    exit;
                }
                
                // Add user_id and updated_at
                $values[] = $user_id;
                
                $update_query = "UPDATE user_notification_preferences SET " . implode(", ", $updates) . ", updated_at = NOW() WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->execute($values);
                
                // Update newsletter subscription if newsletter preference was changed
                if (isset($preferences_data['newsletter'])) {
                    updateNewsletterSubscription($user_id, (int)$preferences_data['newsletter'], $conn);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'All preferences updated successfully',
                    'updated_preferences' => $preferences_data
                ]);
                break;
            
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

/**
 * Update newsletter subscription based on preference
 */
function updateNewsletterSubscription($user_id, $subscribe, $conn) {
    try {
        // Get user email
        $user_stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        $email = $user['email'];
        
        // Check if subscriber exists
        $check_stmt = $conn->prepare("SELECT subscriber_id, subscription_status FROM newsletter_subscribers WHERE email = ?");
        $check_stmt->execute([$email]);
        $subscriber = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subscribe) {
            // Subscribe user
            if ($subscriber) {
                // Update existing subscription
                $update_stmt = $conn->prepare("
                    UPDATE newsletter_subscribers 
                    SET subscription_status = 'active', unsubscribed_at = NULL, subscribed_at = NOW()
                    WHERE email = ?
                ");
                $update_stmt->execute([$email]);
            } else {
                // Create new subscription
                $token = bin2hex(random_bytes(32));
                $insert_stmt = $conn->prepare("
                    INSERT INTO newsletter_subscribers (email, subscription_status, subscribed_at, token)
                    VALUES (?, 'active', NOW(), ?)
                ");
                $insert_stmt->execute([$email, $token]);
            }
        } else {
            // Unsubscribe user
            if ($subscriber) {
                $update_stmt = $conn->prepare("
                    UPDATE newsletter_subscribers 
                    SET subscription_status = 'inactive', unsubscribed_at = NOW()
                    WHERE email = ?
                ");
                $update_stmt->execute([$email]);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating newsletter subscription: " . $e->getMessage());
        return false;
    }
}
?>
