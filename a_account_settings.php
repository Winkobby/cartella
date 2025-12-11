<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Admin only
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$database = new Database();
$pdo = $database->getConnection();

$user_id = intval($_SESSION['user_id']);
$message = '';

// Load user (use full_name and password_hash as in other pages)
$stmt = $pdo->prepare('SELECT user_id, full_name, email FROM users WHERE user_id = ? LIMIT 1');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo 'User not found';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_account'])) {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // basic validation
    if ($email === '') {
        $message = 'Email is required.';
    } else {
        // check if email used by another user
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1');
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $message = 'Email already in use by another account.';
        } else {
            // update

            if ($password !== '') {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, password_hash = ? WHERE user_id = ?');
                $stmt->execute([$name, $email, $password_hash, $user_id]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE user_id = ?');
                $stmt->execute([$name, $email, $user_id]);
            }

            // update session email/name if changed
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $name;

            $message = 'Account updated successfully.';
            // reload user
            $stmt = $pdo->prepare('SELECT user_id, full_name, email FROM users WHERE user_id = ? LIMIT 1');
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

$page_title = 'Account Settings';
$meta_description = 'Update your admin account details';
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-6 lg:py-8">
    <div class="container mx-auto px-4 max-w-2xl">
        <!-- Header -->
        <div class="mb-6 lg:mb-8">
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Account Settings</h1>
            <p class="text-gray-600 mt-2">Manage your personal information and security</p>
        </div>

        <!-- Success Message -->
        <?php if ($message): ?>
            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 animate-fade-in">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-green-800 font-medium"><?php echo htmlspecialchars($message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Account Settings Form -->
        <form method="POST" class="space-y-6">
            <input type="hidden" name="save_account" value="1">

            <!-- Profile Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Profile Information</h3>
                        <p class="text-sm text-gray-600">Update your personal details</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                               required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               placeholder="Enter your full name">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                               required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               placeholder="your.email@example.com">
                    </div>
                </div>
            </div>

            <!-- Security Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Security</h3>
                        <p class="text-sm text-gray-600">Update your password</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="password" 
                               id="password"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors pr-10"
                               placeholder="Leave blank to keep current password">
                        <button type="button" 
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                title="Show/Hide password">
                            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye-off-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <div id="password-strength" class="mt-2 hidden">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div id="strength-bar" class="h-full bg-red-500 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <span id="strength-text" class="text-xs font-medium text-gray-600">Weak</span>
                        </div>
                        <ul id="password-rules" class="text-xs text-gray-500 mt-2 space-y-1">
                            <li class="flex items-center">
                                <svg class="w-3 h-3 mr-1 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                At least 8 characters
                            </li>
                            <li class="flex items-center">
                                <svg class="w-3 h-3 mr-1 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Contains uppercase & lowercase
                            </li>
                            <li class="flex items-center">
                                <svg class="w-3 h-3 mr-1 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Contains numbers & symbols
                            </li>
                        </ul>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Leave blank if you don't want to change your password</p>
                </div>
            </div>

            <!-- Current Session Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Current Session</h3>
                        <p class="text-sm text-gray-600">Your current login information</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-gray-500">Logged in as</div>
                        <div class="font-medium text-gray-900 truncate"><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-gray-500">User ID</div>
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['user_id'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-end">
                <a href="a_dashboard.php" 
                   class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center justify-center shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword() {
    const passwordField = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    const eyeOffIcon = document.getElementById('eye-off-icon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeOffIcon.classList.remove('hidden');
    } else {
        passwordField.type = 'password';
        eyeIcon.classList.remove('hidden');
        eyeOffIcon.classList.add('hidden');
    }
}

// Password strength indicator
const passwordField = document.getElementById('password');
const strengthBar = document.getElementById('strength-bar');
const strengthText = document.getElementById('strength-text');
const passwordRules = document.getElementById('password-rules').querySelectorAll('li');
const strengthContainer = document.getElementById('password-strength');

if (passwordField) {
    passwordField.addEventListener('input', function() {
        const password = this.value;
        
        if (password === '') {
            strengthContainer.classList.add('hidden');
            return;
        }
        
        strengthContainer.classList.remove('hidden');
        
        // Calculate password strength
        let score = 0;
        const rules = [
            password.length >= 8,
            /[A-Z]/.test(password) && /[a-z]/.test(password),
            /\d/.test(password) && /[^A-Za-z0-9]/.test(password)
        ];
        
        // Update rule checkmarks
        rules.forEach((rule, index) => {
            const icon = passwordRules[index].querySelector('svg');
            if (rule) {
                score++;
                icon.classList.remove('text-gray-300');
                icon.classList.add('text-green-500');
            } else {
                icon.classList.remove('text-green-500');
                icon.classList.add('text-gray-300');
            }
        });
        
        // Update strength bar and text
        let strength, color, width;
        if (score === 0) {
            strength = 'Very Weak';
            color = 'bg-red-500';
            width = '20%';
        } else if (score === 1) {
            strength = 'Weak';
            color = 'bg-orange-500';
            width = '40%';
        } else if (score === 2) {
            strength = 'Good';
            color = 'bg-yellow-500';
            width = '60%';
        } else {
            strength = 'Strong';
            color = 'bg-green-500';
            width = '100%';
        }
        
        strengthBar.className = `h-full ${color} rounded-full transition-all duration-300`;
        strengthBar.style.width = width;
        strengthText.textContent = strength;
    });
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const email = document.querySelector('input[name="email"]').value.trim();
    const name = document.querySelector('input[name="full_name"]').value.trim();
    
    if (!email || !name) {
        e.preventDefault();
        alert('Please fill in all required fields marked with *');
        return false;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address');
        return false;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>