<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Sign Up - ' . APP_NAME;
$meta_description = 'Create a new account on ' . APP_NAME . ' to start shopping';
?>

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - Your Premium Online Shopping Destination</title>
    <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/3081/3081559.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/toast.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
</head>

<body class="bg-gray-100">
    <!-- Main Content -->
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto py-4">
            <!-- Logo/Brand -->
            <div class="text-center mb-8">
                <a href="index.php" class="inline-block">
                    <h1 class="text-3xl font-bold mt-6 bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                        <?php echo htmlspecialchars(SITE_NAME ?? APP_NAME); ?>

                    </h1>
                </a>
                <p class="mt-2 text-gray-600">Create your account to get started.</p>
            </div>

            <!-- Sign Up Container -->
            <div class="bg-white rounded-md shadow-md overflow-hidden">
                <div class="p-6">
                    <!-- Messages Container -->
                    <div id="message-container"></div>

                    <!-- Sign Up Form -->
                    <form id="signup-form" class="space-y-2">
                        <input type="hidden" name="action" value="signup">

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    First Name
                                </label>
                                <input type="text" id="first_name" name="first_name" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                    placeholder="First name">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Last Name
                                </label>
                                <input type="text" id="last_name" name="last_name" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                    placeholder="Last name">
                            </div>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number (Optional)
                            </label>
                            <input type="tel" id="phone" name="phone"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                placeholder="Your phone number">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input type="email" id="email" name="email" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                placeholder="Enter your email">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Password
                            </label>
                            <input type="password" id="password" name="password" required minlength="8"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                placeholder="Create a password">
                            <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters long</p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirm Password
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                placeholder="Confirm your password">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="agree_terms" name="agree_terms" required
                                class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                            <label for="agree_terms" class="ml-2 block text-sm text-gray-700">
                                I agree to the
                                <a href="terms.php" class="text-purple-600 hover:text-purple-500 font-medium">Terms of Service</a>
                                and
                                <a href="privacy.php" class="text-purple-600 hover:text-purple-500 font-medium">Privacy Policy</a>
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="subscribe_newsletter" name="subscribe_newsletter" checked
                                class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                            <label for="subscribe_newsletter" class="ml-2 block text-sm text-gray-700">
                                Subscribe to our newsletter to receive updates on new products, promotions, and exclusive offers
                            </label>
                        </div>

                        <button type="submit" id="signup-btn"
                            class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                            <span class="flex items-center justify-center">
                                <span id="signup-text">Create Account</span>
                                <span id="signup-loading" class="hidden ml-2">
                                    <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </span>
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            Already have an account?
                            <a href="signin.php" class="text-purple-600 hover:text-purple-500 font-medium">
                                Sign in here
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Additional Links -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600">
                    By creating an account, you agree to our
                    <a href="terms.php" class="text-purple-600 hover:text-purple-500 font-medium">Terms of Service</a>
                    and
                    <a href="privacy.php" class="text-purple-600 hover:text-purple-500 font-medium">Privacy Policy</a>
                </p>
            </div>
        </div>
    </div>

    <style>
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 500;
        }

        .message.error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .message.success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
    </style>

    <script>
        function clearMessages() {
            const messageContainer = document.getElementById('message-container');
            messageContainer.innerHTML = '';
        }

        function showMessage(message, type = 'error') {
            const messageContainer = document.getElementById('message-container');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = message;
            messageContainer.appendChild(messageDiv);

            // Auto remove success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    messageDiv.remove();
                }, 5000);
            }
        }

        function setLoading(button, isLoading) {
            const text = button.querySelector('#signup-text');
            const loading = button.querySelector('#signup-loading');

            if (isLoading) {
                text.classList.add('hidden');
                loading.classList.remove('hidden');
                button.disabled = true;
            } else {
                text.classList.remove('hidden');
                loading.classList.add('hidden');
                button.disabled = false;
            }
        }

        // Sign Up Form Handler
        document.getElementById('signup-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validate passwords match
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                showMessage('Passwords do not match', 'error');
                return;
            }

            const formData = new FormData(this);
            const button = document.getElementById('signup-btn');

            setLoading(button, true);
            clearMessages();

            try {
                const response = await fetch('ajax/auth.php?action=register', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('Account created successfully! Please sign in.', 'success');

                    // Redirect to signin page after successful registration
                    setTimeout(() => {
                        window.location.href = 'signin.php';
                    }, 2000);
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                console.error('Signup error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            } finally {
                setLoading(button, false);
            }
        });

        // Real-time password validation
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');

            if (passwordInput && confirmInput) {
                function validatePasswords() {
                    const password = passwordInput.value;
                    const confirm = confirmInput.value;

                    if (confirm && password !== confirm) {
                        confirmInput.style.borderColor = '#ef4444';
                    } else {
                        confirmInput.style.borderColor = '#d1d5db';
                    }
                }

                passwordInput.addEventListener('input', validatePasswords);
                confirmInput.addEventListener('input', validatePasswords);
            }
        });
    </script>

</body>

</html>