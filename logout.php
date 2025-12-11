<?php
// logout.php

// Load configuration FIRST (before any session operations)
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Now start session AFTER config has set session parameters
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize database and functions
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Check if this is a confirmed logout request
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'true';

if ($confirmed) {
    // Perform the actual logout
    performLogout();
} else {
    // Show logout confirmation page
    showLogoutConfirmation();
}

function performLogout() {
    global $functions;
    
    $was_logged_in = isset($_SESSION['user_id']);
    $user_id = $was_logged_in ? $_SESSION['user_id'] : null;
    $username = $was_logged_in ? ($_SESSION['username'] ?? 'User') : null;

    // Store user data before destroying session
    $user_data = [];
    if ($was_logged_in) {
        $user_data = [
            'user_id' => $user_id,
            'username' => $username,
            'email' => $_SESSION['email'] ?? ''
        ];
    }

    // Comprehensive session cleanup
    $_SESSION = array();

    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();

    // Clear additional cookies
    setcookie('remember_me', '', time() - 3600, '/', '', true, true);
    setcookie('user_preferences', '', time() - 3600, '/', '', true, true);

    // Set security headers
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Redirect to login page with success message
    header('Location: signin.php?logout=success&username=' . urlencode($username));
    exit();
}

function showLogoutConfirmation() {
    // Check if user is actually logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: signin.php');
        exit();
    }

    $username = $_SESSION['username'] ?? 'User';
    $email = $_SESSION['email'] ?? '';
    
    // Set page metadata
    $page_title = 'Logout Confirmation';
    $meta_description = 'Confirm logout from your account';
    
    // Display logout confirmation page
    displayLogoutConfirmationPage($username, $email);
}

function displayLogoutConfirmationPage($username, $email) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Logout Confirmation</title>
        <meta name="description" content="Confirm logout from your account">
        
        <!-- Security headers -->
        <meta http-equiv="X-Frame-Options" content="DENY">
        <meta http-equiv="X-Content-Type-Options" content="nosniff">
        <meta http-equiv="X-XSS-Protection" content="1; mode=block">
        
        <!-- Styles -->
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            .logout-container {
               
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .logout-card {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
                backdrop-filter: blur(10px);
            }
            .user-avatar {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
        </style>
    </head>
    <body class="logout-container">
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-md mx-auto">
                <!-- Logout Confirmation Card -->
                <div class="logout-card p-8">
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <div class="w-20 h-20 user-avatar rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-user text-white text-2xl"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-800 mb-2">Logout Confirmation</h1>
                        <p class="text-gray-600">Are you sure you want to log out?</p>
                    </div>

                    <!-- User Info -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-purple-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($username); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($email); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Security Notice -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-shield-alt text-blue-500 mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-blue-800 text-sm mb-1">Security Notice</h4>
                                <p class="text-blue-700 text-sm">Logging out will securely end your session and clear your browsing data from this device.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-4">
                        <a href="account.php" 
                           class="flex-1 bg-gray-500 text-white py-3 px-6 rounded-lg font-semibold hover:bg-gray-600 transition-colors text-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Go Back
                        </a>
                        <a href="logout.php?confirm=true" 
                           class="flex-1 bg-red-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-red-700 transition-colors text-center">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Log Out
                        </a>
                    </div>

                    <!-- Quick Links -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <a href="index.php" class="text-gray-600 hover:text-purple-600 transition-colors text-center">
                                <i class="fas fa-home mr-1"></i>
                                Home
                            </a>
                            <a href="help.php" class="text-gray-600 hover:text-purple-600 transition-colors text-center">
                                <i class="fas fa-question-circle mr-1"></i>
                                Help
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Security Badges -->
                <div class="text-center mt-6">
                    <div class="flex items-center justify-center space-x-6 text-gray-400 text-sm">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-lock"></i>
                            <span>Secure Logout</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-shield-alt"></i>
                            <span>Data Protected</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>