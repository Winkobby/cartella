<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// If already logged in and admin, redirect to admin dashboard
if (isset($_SESSION['user_id'])) {
    // If user is admin, go to admin index
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
        header('Location: a_index.php');
        exit();
    }
    // Otherwise go to account
    header('Location: account.php');
    exit();
}

$page_title = 'Admin Sign In - ' . (defined('SITE_NAME') ? SITE_NAME : APP_NAME);
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

    
    <style>

        .message.error { 
            background: #fef2f2; 
            border: 1px solid #fecaca; 
            color: #dc2626; 
            padding: 12px; 
            margin-bottom: 16px; 
            border-radius: 8px;
        }
        .message.success { 
            background: #f0fdf4; 
            border: 1px solid #bbf7d0; 
            color: #16a34a; 
            padding: 12px; 
            margin-bottom: 16px; 
            border-radius: 8px;
        }
        .card-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
    </style>
</head>
<body  class="bg-gray-100" >
    <div class="min-h-screen flex flex-col justify-center items-center px-4 py-12">
        <!-- Logo/Brand Header -->
        <div class="w-full max-w-md text-center mb-8">
            <div class="flex justify-center items-center space-x-3 mb-4">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-purple-700">
                    <?php echo htmlspecialchars(SITE_NAME ?? APP_NAME); ?>
                </h1>
            </div>
            <p class="text-purple-600 text-lg">Administrator Portal</p>
        </div>

        <!-- Login Card -->
        <div class="w-full max-w-md bg-white rounded-2xl card-shadow overflow-hidden p-8">
            <!-- Status Message -->
            <div id="message-container"></div>

            <!-- Title -->
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Admin Sign In</h2>
                <p class="mt-2 text-gray-600 text-sm">Enter your administrator credentials to access the dashboard</p>
            </div>

            <!-- Login Form -->
            <form id="a-signin-form" class="space-y-6">
                <input type="hidden" name="action" value="signin">

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-purple-500"></i>Email Address
                    </label>
                    <input type="email" id="email" name="email" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                           placeholder="admin@domain.com"
                           autocomplete="email">
                </div>

                <!-- Password -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-key mr-2 text-purple-500"></i>Password
                        </label>
                        <a href="forgot_password.php" class="text-xs text-purple-600 hover:text-purple-800 hover:underline">
                            Forgot password?
                        </a>
                    </div>
                    <input type="password" id="password" name="password" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                           placeholder="Your password"
                           autocomplete="current-password">
                </div>

                <!-- Submit Button -->
                <button type="submit" id="a-signin-btn" 
                        class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-3 rounded-lg font-medium hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In as Administrator
                </button>
            </form>

            <!-- Maintenance Notice -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-tools text-blue-600"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Maintenance Mode</h3>
                            <p class="mt-1 text-sm text-blue-700">
                                This page is accessible to administrators only during maintenance windows.
                                Regular users will be redirected to the maintenance page.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back Link -->
            <div class="mt-6 text-center">
                <a href="index.php" class="inline-flex items-center text-sm text-gray-600 hover:text-purple-600 hover:underline">
                    <i class="fas fa-arrow-left mr-2"></i>Return to Home Page
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-purple-100 text-sm">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME ?? APP_NAME); ?>. All rights reserved.
            </p>

        </div>
    </div>

    <script>
    document.getElementById('a-signin-form').addEventListener('submit', async function(e){
        e.preventDefault();
        const btn = document.getElementById('a-signin-btn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing in...';
        
        const formData = new FormData(this);
        const msgContainer = document.getElementById('message-container');
        msgContainer.innerHTML = '';

        try {
            const res = await fetch('ajax/auth.php?action=login', { 
                method: 'POST', 
                body: formData 
            });
            const data = await res.json();

            function showMessage(text, type='error'){
                const d = document.createElement('div');
                d.className = 'message ' + (type === 'success' ? 'success' : 'error');
                d.innerHTML = `
                    <div class="flex items-start">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} mr-2 mt-0.5"></i>
                        <span>${text}</span>
                    </div>
                `;
                msgContainer.appendChild(d);
            }

            if (data.success) {
                // Only allow admin role to proceed here
                const role = data.role || (data.user && data.user.role);
                if (role === 'Admin') {
                    showMessage('Login successful. Redirecting to admin dashboard...', 'success');
                    
                    // Add a slight delay for user to see success message
                    setTimeout(function(){ 
                        window.location.href = 'a_index.php'; 
                    }, 1000);
                } else {
                    showMessage('Access denied: This account does not have administrator privileges.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } else {
                showMessage(data.message || 'Login failed. Please check your credentials.', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
                
                // Clear password field on error
                document.getElementById('password').value = '';
            }
        } catch (err) {
            console.error(err);
            showMessage('Network error. Please check your connection and try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // Auto-focus email field on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('email').focus();
        
        // Add enter key navigation
        document.getElementById('email').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('password').focus();
            }
        });
        
        document.getElementById('password').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('a-signin-form').dispatchEvent(new Event('submit'));
            }
        });
    });
    </script>
</body>
</html>