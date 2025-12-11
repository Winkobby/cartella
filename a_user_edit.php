<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';


// Check if user is admin - FIXED: using user_role instead of role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    error_log("Admin access denied - Redirecting to signin. User role: " . ($_SESSION['user_role'] ?? 'not set'));
    header('Location: signin.php');
    exit;
}


$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$user_id) {
    header('Location: a_users.php');
    exit;
}

// Get user data
try {
    $query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $database->getConnection()->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: a_users.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: a_users.php');
    exit;
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'Customer';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $address = trim($_POST['address'] ?? '');

    // Validate form data
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required.';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        // Check if email already exists (excluding current user)
        $check_email_query = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $check_stmt = $database->getConnection()->prepare($check_email_query);
        $check_stmt->execute([$email, $user_id]);
        if ($check_stmt->fetch()) {
            $errors['email'] = 'This email is already registered.';
        }
    }

    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters long.';
        }

        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
    }

    // If no errors, update user
    if (empty($errors)) {
        try {
            if (!empty($password)) {
                // Update with new password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET full_name = ?, email = ?, phone = ?, password_hash = ?, role = ?, is_active = ?, address = ? WHERE user_id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$full_name, $email, $phone, $password_hash, $role, $is_active, $address, $user_id]);
            } else {
                // Update without changing password
                $query = "UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, is_active = ?, address = ? WHERE user_id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$full_name, $email, $phone, $role, $is_active, $address, $user_id]);
            }

            $success = true;
            $_SESSION['success_message'] = 'User updated successfully!';
            
            // Refresh user data
            $query = "SELECT * FROM users WHERE user_id = ?";
            $stmt = $database->getConnection()->prepare($query);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $errors['general'] = 'Error updating user: ' . $e->getMessage();
        }
    }
}

$page_title = 'Edit User - ' . htmlspecialchars($user['full_name']);
$meta_description = 'Edit user account information';
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-4 lg:py-8">
    <div class="container mx-auto px-3 lg:px-4 max-w-4xl">
        <!-- Header Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-6 lg:mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="a_users.php" class="text-blue-600 hover:text-blue-800 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Edit User</h1>
                    </div>
                    <p class="text-gray-600 text-sm lg:text-base">Update user account information and permissions</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                        <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                        <?php echo $user['role'] === 'Admin' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800'; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-green-800"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-red-800"><?php echo $errors['general']; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- User Information Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">User Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">User ID:</span>
                    <p class="font-medium text-gray-900">#<?php echo $user['user_id']; ?></p>
                </div>
                <div>
                    <span class="text-gray-500">Joined:</span>
                    <p class="font-medium text-gray-900"><?php echo date('F j, Y g:i A', strtotime($user['date_joined'])); ?></p>
                </div>
                <div>
                    <span class="text-gray-500">Last Login:</span>
                    <p class="font-medium text-gray-900">
                        <?php echo $user['last_login'] ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- User Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <form method="POST" class="p-4 lg:p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                    <!-- Personal Information -->
                    <div class="lg:col-span-2">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Personal Information</h2>
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? $user['full_name']); ?>" 
                               class="w-full px-3 py-2 border <?php echo isset($errors['full_name']) ? 'border-red-300' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               placeholder="Enter full name" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['full_name']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>" 
                               class="w-full px-3 py-2 border <?php echo isset($errors['email']) ? 'border-red-300' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               placeholder="Enter email address" required>
                        <?php if (isset($errors['email'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['email']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? $user['phone']); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               placeholder="Enter phone number">
                    </div>

                    <!-- Account Settings -->
                    <div class="lg:col-span-2 mt-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Account Settings</h2>
                    </div>

                    <!-- Password (Optional) -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" id="password" name="password" 
                               class="w-full px-3 py-2 border <?php echo isset($errors['password']) ? 'border-red-300' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               placeholder="Leave blank to keep current password">
                        <?php if (isset($errors['password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['password']; ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-xs text-gray-500">Minimum 6 characters. Leave empty to keep current password.</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="w-full px-3 py-2 border <?php echo isset($errors['confirm_password']) ? 'border-red-300' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               placeholder="Confirm new password">
                        <?php if (isset($errors['confirm_password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['confirm_password']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                        <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            <option value="Customer" <?php echo ($_POST['role'] ?? $user['role']) === 'Customer' ? 'selected' : ''; ?>>Customer</option>
                            <option value="Admin" <?php echo ($_POST['role'] ?? $user['role']) === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Account Status</label>
                        <div class="flex items-center space-x-3">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" value="1" <?php echo ($_POST['is_active'] ?? $user['is_active']) ? 'checked' : ''; ?> 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Active Account</span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Inactive users cannot log in</p>
                    </div>

                    <!-- Address -->
                    <div class="lg:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <textarea id="address" name="address" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                  placeholder="Enter address"><?php echo htmlspecialchars($_POST['address'] ?? $user['address']); ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row justify-end gap-3">
                    <a href="a_users.php" 
                       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm text-center">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Password strength indicator (only show when password field has value)
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthIndicator = document.getElementById('password-strength');
    
    if (!strengthIndicator) {
        const indicator = document.createElement('p');
        indicator.id = 'password-strength';
        indicator.className = 'mt-1 text-xs';
        this.parentNode.appendChild(indicator);
    }
    
    const indicator = document.getElementById('password-strength');
    
    if (password.length === 0) {
        indicator.textContent = '';
    } else if (password.length < 6) {
        indicator.textContent = 'Weak';
        indicator.className = 'mt-1 text-xs font-medium text-red-600';
    } else if (password.length < 10) {
        indicator.textContent = 'Medium';
        indicator.className = 'mt-1 text-xs font-medium text-yellow-600';
    } else {
        indicator.textContent = 'Strong';
        indicator.className = 'mt-1 text-xs font-medium text-green-600';
    }
});

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password.length === 0 && confirmPassword.length === 0) {
        return;
    }
    
    const matchIndicator = document.getElementById('password-match');
    
    if (!matchIndicator) {
        const indicator = document.createElement('p');
        indicator.id = 'password-match';
        indicator.className = 'mt-1 text-xs';
        this.parentNode.appendChild(indicator);
    }
    
    const indicator = document.getElementById('password-match');
    
    if (confirmPassword.length === 0) {
        indicator.textContent = '';
    } else if (password === confirmPassword) {
        indicator.textContent = 'Passwords match';
        indicator.className = 'mt-1 text-xs font-medium text-green-600';
    } else {
        indicator.textContent = 'Passwords do not match';
        indicator.className = 'mt-1 text-xs font-medium text-red-600';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>