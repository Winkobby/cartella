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

$page_title = 'Sign In - ' . APP_NAME;
$meta_description = 'Sign in to your ' . APP_NAME . ' account to continue shopping';

// Get social login configs
$google_client_id = isset($config['google_client_id']) ? $config['google_client_id'] : '';
$facebook_app_id = isset($config['facebook_app_id']) ? $config['facebook_app_id'] : '';
$github_client_id = isset($config['github_client_id']) ? $config['github_client_id'] : '';
?>

<!DOCTYPE html>
<html lang="en">

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
            <!-- Back Button -->
            <div class="mb-4">
                <a href="index.php" class="inline-flex items-center text-gray-600 hover:text-purple-600 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span class="ml-2 font-medium">Back to Home</span>
                </a>
            </div>

            <!-- Logo/Brand -->
            <div class="text-center mb-8">
                <a href="index.php" class="inline-block">
                    <h1 class="text-3xl font-bold mt-6 bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                        <?php echo htmlspecialchars(SITE_NAME ?? APP_NAME); ?>

                    </h1>
                </a>
                <p class="mt-2 text-gray-600">Welcome back! Please sign in to your account.</p>
            </div>

            <!-- Sign In Container -->
            <div class="bg-white rounded-md shadow-md overflow-hidden">
                <div class="p-6">
                    <!-- Messages Container -->
                    <div id="message-container"></div>

                    <!-- Social Login Buttons -->
                    <div class="mb-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Or continue with</span>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-3 gap-3">
                            <!-- Google -->
                            <a href="<?php echo !empty($google_client_id) ? 'ajax/auth.php?action=google_login' : '#'; ?>"
                                class="w-full inline-flex justify-center py-2.5 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 <?php echo empty($google_client_id) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                <svg class="w-5 h-5" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                </svg>
                            </a>

                            <!-- Facebook -->
                            <a href="<?php echo !empty($facebook_app_id) ? 'ajax/auth.php?action=facebook_login' : '#'; ?>"
                                class="w-full inline-flex justify-center py-2.5 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 <?php echo empty($facebook_app_id) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                                </svg>
                            </a>

                            <!-- GitHub -->
                            <a href="<?php echo !empty($github_client_id) ? 'ajax/auth.php?action=github_login' : '#'; ?>"
                                class="w-full inline-flex justify-center py-2.5 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 <?php echo empty($github_client_id) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                <svg class="w-5 h-5 text-gray-800" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="relative mb-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">Or sign in with email</span>
                        </div>
                    </div>

                    <!-- Sign In Form -->
                    <form id="signin-form" class="space-y-4">
                        <input type="hidden" name="action" value="signin">

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
                            <input type="password" id="password" name="password" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                placeholder="Enter your password">
                        </div>

                        <div class="flex items-center justify-between mt-2">
                            <div class="flex items-center">
                                <input type="checkbox" id="remember" name="remember"
                                    class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                <label for="remember" class="ml-2 block text-sm text-gray-700">
                                    Remember me
                                </label>
                            </div>
                            <a href="forgot_password.php" class="text-sm text-purple-600 hover:text-purple-500 font-medium">
                                Forgot password?
                            </a>
                        </div>

                        <button type="submit" id="signin-btn"
                            class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                            <span class="flex items-center justify-center">
                                <span id="signin-text">Sign In</span>
                                <span id="signin-loading" class="hidden ml-2">
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
                            Don't have an account?
                            <a href="signup.php" class="text-purple-600 hover:text-purple-500 font-medium">
                                Create one here
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Additional Links -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600">
                    By signing in, you agree to our
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

        .social-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .social-btn svg {
            transition: transform 0.2s;
        }

        .social-btn:hover svg {
            transform: scale(1.1);
        }
    </style>

    <script>
        // Handle social login buttons
        document.querySelectorAll('.social-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                if (this.classList.contains('cursor-not-allowed')) {
                    e.preventDefault();
                    showMessage('This login method is not configured yet.', 'error');
                }
            });
        });

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
            const text = button.querySelector('#signin-text');
            const loading = button.querySelector('#signin-loading');

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

        // Sign In Form Handler
        document.getElementById('signin-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const button = document.getElementById('signin-btn');

            setLoading(button, true);
            clearMessages();

            try {
                const response = await fetch('ajax/auth.php?action=login', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('Login successful! Redirecting...', 'success');

                    setTimeout(() => {
                        const redirectParam = new URLSearchParams(window.location.search).get('redirect');
                        if (redirectParam) {
                            window.location.href = redirectParam;
                        } else {
                            // Use the role from response
                            const role = data.role || (data.user && data.user.role);

                            if (role === 'Admin') {
                                window.location.href = 'a_index.php';
                            } else {
                                window.location.href = 'account.php';
                            }
                        }
                    }, 1500);
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                console.error('Login error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            } finally {
                setLoading(button, false);
            }
        });
    </script>

</body>

</html>