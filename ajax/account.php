<?php
// Start session first before anything else
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Don't require auth.php if it's just checking session
// require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection(); // PDO

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    switch ($action) {
        case 'update_profile':
            updateProfile($conn, $user_id);
            break;

        case 'change_password':
            changePassword($conn, $user_id);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Account API Error: " . $e->getMessage());
    // Return more detailed error for debugging
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}

function updateProfile($conn, $user_id) {
    $required = ['first_name', 'last_name', 'email'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "{$field} is required"]);
            return;
        }
    }

    // Check if email is already taken by another user (PDO)
    $checkSql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([trim($_POST['email']), $user_id]);
    if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Email already taken']);
        return;
    }

    // Remove date_of_birth from the query
    $stmt = $conn->prepare(
        "UPDATE users 
         SET full_name = CONCAT(?, ' ', ?), email = ?, phone = ?
         WHERE user_id = ?"
    );

    if ($stmt->execute([
        trim($_POST['first_name']),
        trim($_POST['last_name']),
        trim($_POST['email']),
        trim($_POST['phone'] ?? ''),
        $user_id
    ])) {
        // Update session data
        $_SESSION['first_name'] = trim($_POST['first_name']);
        $_SESSION['last_name'] = trim($_POST['last_name']);
        $_SESSION['email'] = trim($_POST['email']);
        $_SESSION['phone'] = trim($_POST['phone'] ?? '');
        // Remove date_of_birth from session

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
}

function changePassword($conn, $user_id) {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    $required = ['current_password', 'new_password', 'confirm_password'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "{$field} is required"]);
            return;
        }
    }

    if ($_POST['new_password'] !== $_POST['confirm_password']) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        return;
    }

    if (strlen($_POST['new_password']) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        return;
    }

    try {
        // Get current password hash
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare select statement: ' . implode(', ', $conn->errorInfo()));
        }
        
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }

        if (!password_verify($_POST['current_password'], $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            return;
        }

        // Update password
        $newPasswordHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        
        if (!$updateStmt) {
            throw new Exception('Failed to prepare update statement: ' . implode(', ', $conn->errorInfo()));
        }
        
        $success = $updateStmt->execute([$newPasswordHash, $user_id]);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to change password']);
        }
    } catch (Exception $e) {
        error_log("Change password error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>