<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output to browser

require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    // Initialize database
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    switch ($action) {
        case 'get_addresses':
            getAddresses($conn, $user_id);
            break;
            
        case 'get_address':
            getAddress($conn, $user_id);
            break;
            
        case 'add_address':
            addAddress($conn, $user_id);
            break;
            
        case 'update_address':
            updateAddress($conn, $user_id);
            break;
            
        case 'delete_address':
            deleteAddress($conn, $user_id);
            break;
            
        case 'set_default':
            setDefaultAddress($conn, $user_id);
            break;
            
        case 'get_addresses_count':
            getAddressesCount($conn, $user_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Address API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function getAddresses($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT address_id, address_type, street_address, city, region, postal_code, country, is_default, created_at
            FROM user_addresses 
            WHERE user_id = ? 
            ORDER BY is_default DESC, created_at DESC
        ");
        $stmt->execute([$user_id]);
        $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'addresses' => $addresses]);
    } catch (Exception $e) {
        error_log("Get addresses error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch addresses', 'addresses' => []]);
    }
}

function getAddress($conn, $user_id) {
    $address_id = $_GET['id'] ?? 0;
    
    // Debug: log the received parameters
    error_log("getAddress called with ID: " . $address_id . ", User ID: " . $user_id);
    
    if (!$address_id) {
        echo json_encode(['success' => false, 'message' => 'Address ID is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT address_id, address_type, street_address, city, region, postal_code, country, is_default
            FROM user_addresses 
            WHERE address_id = ? AND user_id = ?
        ");
        $stmt->execute([$address_id, $user_id]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug: log the query result
        error_log("Query result: " . ($address ? "Found address" : "Address not found"));
        
        if ($address) {
            echo json_encode(['success' => true, 'address' => $address]);
        } else {
            // Check if address exists but doesn't belong to user
            $checkStmt = $conn->prepare("SELECT address_id FROM user_addresses WHERE address_id = ?");
            $checkStmt->execute([$address_id]);
            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($exists) {
                echo json_encode(['success' => false, 'message' => 'Address not found in your account']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Address not found']);
            }
        }
    } catch (Exception $e) {
        error_log("Get address error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch address']);
    }
}

function addAddress($conn, $user_id) {
    $data = getFormData();
    
    // Validate required fields
    $required = ['street_address', 'city', 'country'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "{$field} is required"]);
            return;
        }
    }
    
    try {
        $conn->beginTransaction();
        
        // If setting as default, remove default from other addresses
        if (!empty($data['is_default'])) {
            $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        $stmt = $conn->prepare("
            INSERT INTO user_addresses (user_id, address_type, street_address, city, region, postal_code, country, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $is_default = !empty($data['is_default']) ? 1 : 0;
        $region = $data['state'] ?? ($data['region'] ?? '');
        
        $success = $stmt->execute([
            $user_id,
            $data['address_type'] ?? 'home',
            $data['street_address'],
            $data['city'],
            $region,
            $data['postal_code'] ?? '',
            $data['country'],
            $is_default
        ]);
        
        $conn->commit();
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Address added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add address']);
        }
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Add address error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to add address']);
    }
}

function updateAddress($conn, $user_id) {
    $data = getFormData();
    $address_id = $data['address_id'] ?? 0;
    
    if (!$address_id) {
        echo json_encode(['success' => false, 'message' => 'Address ID is required']);
        return;
    }
    
    try {
        $conn->beginTransaction();
        
        // Verify address belongs to user
        $checkStmt = $conn->prepare("SELECT address_id FROM user_addresses WHERE address_id = ? AND user_id = ?");
        $checkStmt->execute([$address_id, $user_id]);
        
        if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['success' => false, 'message' => 'Address not found']);
            return;
        }
        
        // If setting as default, remove default from other addresses
        if (!empty($data['is_default'])) {
            $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        $stmt = $conn->prepare("
            UPDATE user_addresses 
            SET address_type = ?, street_address = ?, city = ?, region = ?, postal_code = ?, country = ?, is_default = ?, updated_at = NOW()
            WHERE address_id = ? AND user_id = ?
        ");
        
        $is_default = !empty($data['is_default']) ? 1 : 0;
        $region = $data['state'] ?? ($data['region'] ?? '');
        
        $success = $stmt->execute([
            $data['address_type'] ?? 'home',
            $data['street_address'],
            $data['city'],
            $region,
            $data['postal_code'] ?? '',
            $data['country'],
            $is_default,
            $address_id,
            $user_id
        ]);
        
        $conn->commit();
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Address updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update address']);
        }
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Update address error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update address']);
    }
}

function deleteAddress($conn, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $address_id = $input['address_id'] ?? 0;
    
    if (!$address_id) {
        echo json_encode(['success' => false, 'message' => 'Address ID is required']);
        return;
    }
    
    try {
        // Verify address belongs to user and is not default
        $checkStmt = $conn->prepare("SELECT is_default FROM user_addresses WHERE address_id = ? AND user_id = ?");
        $checkStmt->execute([$address_id, $user_id]);
        $address = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$address) {
            echo json_encode(['success' => false, 'message' => 'Address not found']);
            return;
        }
        
        if ($address['is_default']) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete default address']);
            return;
        }
        
        $stmt = $conn->prepare("DELETE FROM user_addresses WHERE address_id = ? AND user_id = ?");
        $success = $stmt->execute([$address_id, $user_id]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete address']);
        }
    } catch (Exception $e) {
        error_log("Delete address error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete address']);
    }
}

function setDefaultAddress($conn, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $address_id = $input['address_id'] ?? 0;
    
    if (!$address_id) {
        echo json_encode(['success' => false, 'message' => 'Address ID is required']);
        return;
    }
    
    try {
        $conn->beginTransaction();
        
        // Verify address belongs to user
        $checkStmt = $conn->prepare("SELECT address_id FROM user_addresses WHERE address_id = ? AND user_id = ?");
        $checkStmt->execute([$address_id, $user_id]);
        
        if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['success' => false, 'message' => 'Address not found']);
            return;
        }
        
        // Remove default from all addresses
        $stmt1 = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        $stmt1->execute([$user_id]);
        
        // Set new default
        $stmt2 = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?");
        $stmt2->execute([$address_id, $user_id]);
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Default address updated successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Set default address error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update default address']);
    }
}

function getAddressesCount($conn, $user_id) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_addresses WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'count' => intval($data['count'])]);
    } catch (Exception $e) {
        error_log("Get addresses count error: " . $e->getMessage());
        echo json_encode(['success' => true, 'count' => 0]);
    }
}

function getFormData() {
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        return $_POST;
    }
}
?>