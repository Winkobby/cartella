<?php
// forgot_password.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';
require_once 'includes/PHPMailerWrapper.php';

$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Check if email exists in users table
        $user = $functions->getUserByEmail($email);
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(50));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            try {
                $pdo = $database->getConnection();
                
                // Delete any existing tokens for this email
                $deleteStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $deleteStmt->execute([$email]);
                
                // Insert new token
                $insertStmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                $insertStmt->execute([$email, $token, $expires_at]);
                
                // Send reset email using PHPMailerWrapper
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
                $userName = $user['full_name'] ?? '';
                
                $mailer = new PHPMailerWrapper();
                
                // Create HTML email content
                $htmlContent = getPasswordResetEmailTemplate($userName, $reset_link);
                $subject = "Password Reset Request - Cartella";
                
                $sent = $mailer->sendHtml($email, $subject, $htmlContent);
                
                if ($sent) {
                    $message = 'Password reset instructions have been sent to your email.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to send reset email. Please try again.';
                    $message_type = 'error';
                }
                
            } catch (Exception $e) {
                $message = 'An error occurred. Please try again.';
                $message_type = 'error';
                error_log("Password reset error: " . $e->getMessage());
            }
        } else {
            // For security, don't reveal if email exists
            $message = 'If that email address exists in our system, we have sent password reset instructions.';
            $message_type = 'info';
        }
    }
}

function getPasswordResetEmailTemplate($userName, $resetLink) {
    $greeting = $userName ? "Hello $userName!" : "Hello!";
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; color: white; }
            .content { padding: 30px; background: #f9f9f9; }
            .button { background: #8b5cf6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; }
            .footer { text-align: center; margin-top: 20px; padding: 20px; font-size: 12px; color: #666; border-top: 1px solid #eee; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Cartella Store</h1>
                <p>Password Reset Request</p>
            </div>
            <div class='content'>
                <h2>$greeting</h2>
                <p>You requested to reset your password for your Cartella account.</p>
                <p>Click the button below to reset your password:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$resetLink' class='button'>Reset Password</a>
                </p>
                <div class='warning'>
                    <p><strong>Important:</strong> This link will expire in 1 hour for security reasons.</p>
                </div>
                <p>If you didn't request this password reset, please ignore this email. Your account remains secure.</p>
                <div class='footer'>
                    <p>If you're having trouble clicking the button, copy and paste this URL into your browser:</p>
                    <p><a href='$resetLink' style='color: #8b5cf6;'>$resetLink</a></p>
                    <p>&copy; " . date('Y') . " Cartella Store. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>";
}
?>

<?php require_once 'includes/header.php'; ?>

    <div class="px-4 sm:px-6 lg:px-8 items-center justify-center flex py-8">

        <div class="max-w-md w-full">
            <!-- Logo -->
            <div class="text-center mb-4">
                <h2 class="text-2xl font-bold text-purple-800">Reset Your Password</h2>
                <p class="mt-2 text-gray-700">Enter your email to receive reset instructions</p>
            </div>

          
            <div class="bg-white rounded-2xl shadow-md overflow-hidden p-4">
                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg <?php 
                        echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 
                             ($message_type === 'error' ? 'bg-red-100 text-red-700 border border-red-200' : 
                             'bg-blue-100 text-blue-700 border border-blue-200');
                    ?>">
                        <div class="flex items-center">
                            <i class="fas <?php 
                                echo $message_type === 'success' ? 'fa-check-circle' : 
                                     ($message_type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
                            ?> mr-2"></i>
                            <span><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6 p-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-purple-600"></i>Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition"
                            placeholder="Enter your email address"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition font-medium"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>Send Reset Instructions
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Remember your password? 
                        <a href="signin.php" class="text-purple-600 hover:text-purple-700 font-medium">Sign in here</a>
                    </p>
                </div>

                <div class="mt-3 pt-6 border-t border-gray-200 mb-3">
                    <div class="text-center">
                        <a href="index.php" class="text-gray-500 hover:text-gray-700 text-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Homepage
                        </a>
                    </div>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="text-center mb-12 mt-3">
                <p class="text-purple-600 text-sm">
                    <i class="fas fa-shield-alt mr-1"></i>We'll never share your email with anyone else.
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const emailInput = document.getElementById('email');
            
            form.addEventListener('submit', function(e) {
                const email = emailInput.value.trim();
                if (!email) {
                    e.preventDefault();
                    showError('Please enter your email address.');
                    return;
                }
                
                // Basic email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    showError('Please enter a valid email address.');
                    return;
                }
            });
            
            function showError(message) {
                // Remove existing error messages
                const existingError = document.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }
                
                // Create error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message mb-4 p-3 bg-red-100 text-red-700 rounded-lg border border-red-200';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i>${message}`;
                
                // Insert before form
                form.parentNode.insertBefore(errorDiv, form);
                
                // Focus on email input
                emailInput.focus();
            }
        });
    </script>
<?php require_once 'includes/footer.php'; ?>