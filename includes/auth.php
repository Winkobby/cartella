<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

class Auth {
    private $db;
    private $functions;
    
    public function __construct() {
        $this->db = new Database();
        $this->functions = new Functions();
    }

    // User registration
    public function register($first_name, $last_name, $email, $password, $phone = null, $subscribe_newsletter = 1) {
        // Validate inputs
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        try {
            $conn = $this->db->getConnection();
            
            // Check if email already exists
            $sql = "SELECT user_id FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                return ['success' => false, 'message' => 'Email already registered.'];
            }

            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $full_name = $first_name . ' ' . $last_name;

            // Insert user with all required fields
            $sql = "INSERT INTO users (first_name, last_name, full_name, email, password_hash, phone, role) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Customer')";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$first_name, $last_name, $full_name, $email, $password_hash, $phone]);
            
            if ($result) {
                $user_id = $conn->lastInsertId();
                
                // Create notification preferences for the new user
                $sql_prefs = "INSERT INTO user_notification_preferences (user_id, newsletter) VALUES (?, ?)";
                $stmt_prefs = $conn->prepare($sql_prefs);
                $stmt_prefs->execute([$user_id, $subscribe_newsletter]);
                
                // Get the created user data
                $sql = "SELECT user_id, first_name, last_name, full_name, email, role FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $this->loginUser($user['user_id'], $user['email'], $user['full_name'], $user['role']);
                
                return [
                    'success' => true, 
                    'message' => 'Registration successful!',
                    'user_id' => $user_id,
                    'user' => $user,
                    'role' => $user['role'] // Add role to response
                ];
            } else {
                return ['success' => false, 'message' => 'Registration failed. Please try again.'];
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }

    // User login
    // User login - Updated to ensure role is returned
public function login($email, $password, $remember = false) {
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required.'];
    }

    try {
        $conn = $this->db->getConnection();
        
        // Get user by email
        $sql = "SELECT user_id, first_name, last_name, full_name, email, password_hash, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Login successful
        $this->loginUser($user['user_id'], $user['email'], $user['full_name'], $user['role']);
        
        // Handle remember me token
        if ($remember) {
            $this->setRememberToken($user['user_id']);
        }
        
        // DEBUG: Log the role being returned
        error_log("Login successful for user: {$user['email']} with role: {$user['role']}");
        
        return [
            'success' => true, 
            'message' => 'Login successful!',
            'user' => $user,
            'role' => $user['role'] // Explicitly include role at root level
        ];
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred. Please try again.'];
    }
}

    // Set remember me token
    private function setRememberToken($user_id) {
        try {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            setcookie('remember_token', $token, $expiry, '/', '', false, true);
            
            $conn = $this->db->getConnection();
            $sql = "UPDATE users SET remember_token = ?, token_expiry = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), $user_id]);
            
        } catch (Exception $e) {
            error_log("Remember token error: " . $e->getMessage());
        }
    }

    // Set session after login
    // Set session after login
private function loginUser($user_id, $email, $full_name, $role) {
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $full_name;
    $_SESSION['user_role'] = $role;  // This is the key line!
    $_SESSION['logged_in'] = true;
    
    // Debug log
    error_log("User logged in - ID: $user_id, Email: $email, Role: $role");
    
    // Update last login
    try {
        $conn = $this->db->getConnection();
        $sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Last login update error: " . $e->getMessage());
    }
}

    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    // Check if user has specific role
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Allow multiple roles to be checked
        if (is_array($role)) {
            return in_array($_SESSION['user_role'], $role);
        }
        
        return $_SESSION['user_role'] === $role;
    }

    // Check if user is admin
    public function isAdmin() {
        return $this->hasRole('Admin');
    }

    // Redirect user based on their role
    public function redirectBasedOnRole($default = 'index.php') {
        if (!$this->isLoggedIn()) {
            return $default;
        }
        
        $role = $_SESSION['user_role'];
        
        switch ($role) {
            case 'Admin':
                return 'a_index.php';
            case 'vendor':
                return 'vendor_dashboard.php';
            case 'Customer':
            default:
                return $default;
        }
    }

    // User logout
    public function logout() {
        // Clear remember token
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully.'];
    }

    // Get current user info
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'role' => $_SESSION['user_role'] ?? null
        ];
    }
}

// Initialize auth
$auth = new Auth();

// Helper functions
function isLoggedIn() {
    global $auth;
    return $auth->isLoggedIn();
}

function isAdmin() {
    global $auth;
    return $auth->isAdmin();
}

function hasRole($role) {
    global $auth;
    return $auth->hasRole($role);
}

function redirectBasedOnRole($default = 'index.php') {
    global $auth;
    return $auth->redirectBasedOnRole($default);
}
?>