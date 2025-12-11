<?php
// reset_password.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

$message = '';
$message_type = '';
$valid_token = false;
$token = $_GET['token'] ?? '';

// Validate token
if ($token) {
    try {
        $pdo = $database->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
        $stmt->execute([$token]);
        $reset_request = $stmt->fetch();
        
        if ($reset_request) {
            $valid_token = true;
            $email = $reset_request['email'];
        } else {
            $message = 'Invalid or expired reset link. Please request a new password reset.';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred. Please try again.';
        $message_type = 'error';
    }
} else {
    $message = 'No reset token provided.';
    $message_type = 'error';
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } else {
        try {
            $pdo = $database->getConnection();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user password - using the correct column name 'password_hash'
            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $result = $updateStmt->execute([$hashed_password, $email]);
            
            if ($result && $updateStmt->rowCount() > 0) {
                // Mark token as used
                $markUsedStmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                $markUsedStmt->execute([$token]);
                
                $message = 'Password reset successfully! You can now sign in with your new password.';
                $message_type = 'success';
                $valid_token = false;
            } else {
                throw new Exception("Could not update password - user not found");
            }
            
        } catch (Exception $e) {
            $message = 'An error occurred while resetting your password. Please try again.';
            $message_type = 'error';
        }
    }
}
?>

<!-- Rest of your HTML remains exactly the same -->
<?php require_once 'includes/header.php'; ?>
   <style>

        .auth-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
    </style>
       <div class="px-4 sm:px-6 lg:px-8 items-center justify-center flex">

        <div class="max-w-md w-full py-8">
            <!-- Logo -->
            <div class="text-center mb-4">
                <h2 class="text-2xl font-bold text-purple-800">Reset Your Password</h2>
                <p class="mt-2 text-gray-700">Enter your email to receive reset instructions</p>
            </div>

            <!-- Auth Card -->
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

                <?php if ($valid_token): ?>
                <form method="POST" class="space-y-6" id="resetForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-purple-600"></i>New Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            minlength="6"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition"
                            placeholder="Enter new password"
                        >
                        <div class="mt-2">
                            <div class="password-strength bg-gray-200" id="passwordStrength"></div>
                            <p class="text-xs text-gray-500 mt-1" id="passwordHint">Must be at least 6 characters</p>
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-purple-600"></i>Confirm New Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required 
                            minlength="6"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition"
                            placeholder="Confirm new password"
                        >
                        <p class="text-xs text-gray-500 mt-1" id="passwordMatch"></p>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition font-medium"
                        id="submitBtn"
                    >
                        <i class="fas fa-key mr-2"></i>Reset Password
                    </button>
                </form>
                <?php elseif (!$message): ?>
                    <div class="text-center ">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
                        <p class="text-gray-600">Invalid or expired reset link.</p>
                        <a href="forgot_password.php" class="inline-block mt-4 text-purple-600 hover:text-purple-700 font-medium">
                            Request a new reset link
                        </a>
                    </div>
                <?php endif; ?>

                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Remember your password? 
                        <a href="signin.php" class="text-purple-600 hover:text-purple-700 font-medium">Sign in here</a>
                    </p>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="text-center">
                        <a href="index.php" class="text-gray-500 hover:text-gray-700 text-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Homepage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrength = document.getElementById('passwordStrength');
            const passwordHint = document.getElementById('passwordHint');
            const passwordMatch = document.getElementById('passwordMatch');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('resetForm');

            if (passwordInput) {
                passwordInput.addEventListener('input', checkPasswordStrength);
                confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            }

            function checkPasswordStrength() {
                const password = passwordInput.value;
                let strength = 0;
                let hint = '';

                if (password.length >= 6) strength += 25;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
                if (password.match(/\d/)) strength += 25;
                if (password.match(/[^a-zA-Z\d]/)) strength += 25;

                // Update strength bar
                if (strength === 0) {
                    passwordStrength.style.backgroundColor = '#e5e7eb';
                    passwordHint.textContent = 'Must be at least 6 characters';
                    passwordHint.className = 'text-xs text-gray-500 mt-1';
                } else if (strength <= 25) {
                    passwordStrength.style.backgroundColor = '#ef4444';
                    passwordStrength.style.width = '25%';
                    passwordHint.textContent = 'Weak password';
                    passwordHint.className = 'text-xs text-red-500 mt-1';
                } else if (strength <= 50) {
                    passwordStrength.style.backgroundColor = '#f59e0b';
                    passwordStrength.style.width = '50%';
                    passwordHint.textContent = 'Fair password';
                    passwordHint.className = 'text-xs text-yellow-500 mt-1';
                } else if (strength <= 75) {
                    passwordStrength.style.backgroundColor = '#10b981';
                    passwordStrength.style.width = '75%';
                    passwordHint.textContent = 'Good password';
                    passwordHint.className = 'text-xs text-green-500 mt-1';
                } else {
                    passwordStrength.style.backgroundColor = '#10b981';
                    passwordStrength.style.width = '100%';
                    passwordHint.textContent = 'Strong password';
                    passwordHint.className = 'text-xs text-green-500 mt-1';
                }
            }

            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (confirmPassword === '') {
                    passwordMatch.textContent = '';
                    return;
                }

                if (password === confirmPassword) {
                    passwordMatch.textContent = 'Passwords match';
                    passwordMatch.className = 'text-xs text-green-500 mt-1';
                } else {
                    passwordMatch.textContent = 'Passwords do not match';
                    passwordMatch.className = 'text-xs text-red-500 mt-1';
                }
            }

            if (form) {
                form.addEventListener('submit', function(e) {
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;

                    if (password.length < 6) {
                        e.preventDefault();
                        showError('Password must be at least 6 characters long.');
                        passwordInput.focus();
                        return;
                    }

                    if (password !== confirmPassword) {
                        e.preventDefault();
                        showError('Passwords do not match.');
                        confirmPasswordInput.focus();
                        return;
                    }
                });
            }

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
            }
        });
    </script>

<?php require_once 'includes/footer.php'; ?>