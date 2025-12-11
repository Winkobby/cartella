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
        // Check if email already exists
        $check_email_query = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = $database->getConnection()->prepare($check_email_query);
        $check_stmt->execute([$email]);
        if ($check_stmt->fetch()) {
            $errors['email'] = 'This email is already registered.';
        }
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // If no errors, create user
    if (empty($errors)) {
        try {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $query = "INSERT INTO users (full_name, email, phone, password_hash, role, is_active, address, date_joined) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $database->getConnection()->prepare($query);
            $stmt->execute([$full_name, $email, $phone, $password_hash, $role, $is_active, $address]);

            $success = true;
            $_SESSION['success_message'] = 'User created successfully!';
            
            // Redirect to users list
            header('Location: a_users.php');
            exit;

        } catch (PDOException $e) {
            $errors['general'] = 'Error creating user: ' . $e->getMessage();
        }
    }
}

$page_title = 'Add New User';
$meta_description = 'Create a new user account';
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-4 lg:py-8">
    <div class="container mx-auto px-3 lg:px-4 max-w-4xl">
        <!-- Header Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-6 lg:mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="a_users.php" class="text-purple-600 hover:text-purple-800 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Add New User</h1>
                    </div>
                    <p class="text-gray-600 text-sm lg:text-base">Create a new user account with specific permissions</p>
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
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                               class="w-full px-3 py-2 border <?php echo isset($errors['full_name']) ? 'border-red-300' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                               placeholder="Enter full name" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['full_name']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               class="w-full px-3 py-2 border <?php echo isset($errors['email']) ? 'border-red-300' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                               placeholder="Enter email address" required>
                        <?php if (isset($errors['email'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['email']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                               placeholder="Enter phone number">
                    </div>

                    <!-- Account Settings -->
                    <div class="lg:col-span-2 mt-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Account Settings</h2>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                        <input type="password" id="password" name="password" 
                               class="w-full px-3 py-2 border <?php echo isset($errors['password']) ? 'border-red-300' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                               placeholder="Enter password" required>
                        <?php if (isset($errors['password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['password']; ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-xs text-gray-500">Minimum 6 characters</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="w-full px-3 py-2 border <?php echo isset($errors['confirm_password']) ? 'border-red-300' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                               placeholder="Confirm password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['confirm_password']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                        <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors">
                            <option value="Customer" <?php echo ($_POST['role'] ?? 'Customer') === 'Customer' ? 'selected' : ''; ?>>Customer</option>
                            <option value="Admin" <?php echo ($_POST['role'] ?? '') === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Account Status</label>
                        <div class="flex items-center space-x-3">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" value="1" <?php echo isset($_POST['is_active']) ? 'checked' : 'checked'; ?> 
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
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                                  placeholder="Enter address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row justify-end gap-3">
                    <a href="a_users.php" 
                       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm text-center">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Password strength indicator
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
    let strength = '';
    let color = '';
    
    if (password.length === 0) {
        strength = '';
    } else if (password.length < 6) {
        strength = 'Weak';
        color = 'text-red-600';
    } else if (password.length < 10) {
        strength = 'Medium';
        color = 'text-yellow-600';
    } else {
        strength = 'Strong';
        color = 'text-green-600';
    }
    
    indicator.textContent = strength;
    indicator.className = `mt-1 text-xs font-medium ${color}`;
});

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
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