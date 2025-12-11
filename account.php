<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data using PDO
try {
    $database = new Database();
    $conn = $database->getConnection();

    // Use correct column names from your database
    $stmt = $conn->prepare("SELECT user_id, full_name, email, phone, address, role, date_joined FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: signin.php');
        exit();
    }

    // Split full_name into first_name and last_name for compatibility
    $name_parts = explode(' ', $user['full_name'], 2);
    $user['first_name'] = $name_parts[0] ?? '';
    $user['last_name'] = $name_parts[1] ?? '';
    $user['created_at'] = $user['date_joined']; // Alias for compatibility

} catch (Exception $e) {
    // Fallback to session data
    $user = [
        'user_id' => $user_id,
        'first_name' => $_SESSION['user_name'] ?? 'User',
        'last_name' => '',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['phone'] ?? '',
        'created_at' => $_SESSION['date_joined'] ?? date('Y-m-d H:i:s')
    ];
    error_log("Error fetching user data from database: " . $e->getMessage());
}

$page_title = 'My Account';
$meta_description = 'Manage your account settings and preferences';
?>

<?php require_once 'includes/header.php'; ?>
<div class="container mx-auto px-2 max-w-6xl py-8 md:py-2 lg:py-2 pb-2">
    <nav class="flex text-xs">
        <a href="index.php" class="text-purple-600 hover:text-purple-700">Home</a>
        <span class="mx-2 text-gray-400">></span>
        <span class="text-gray-600">My Account</span>
    </nav>
</div>
<!-- Modern Account Interface -->
<div class="py-6 lg:py-4">
    <div class="container mx-auto px-2 max-w-6xl">


        <!-- Header with user info -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-lg font-bold text-white">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </span>
                </div>
                <div>
                    <h1 class="text-lg lg:text-xl font-bold text-gray-800">
                        Hello, <?php echo htmlspecialchars($user['first_name']); ?>!
                    </h1>
                    <p class="text-xs lg:text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
            <!-- Left Sidebar - Made sticky on desktop -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg shadow-sm p-4 mb-4 lg:sticky lg:top-6">
                    <h3 class="font-semibold text-gray-800 mb-4">MY ACCOUNT</h3>
                    <nav class="space-y-2">
                        <button onclick="showTab('dashboard')"
                            class="w-full text-left flex items-center px-3 py-2 text-purple-600 bg-purple-50 rounded hover:bg-purple-50 transition-colors text-sm">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </button>
                        <button onclick="showTab('orders')"
                            class="w-full text-left flex items-center px-3 py-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded transition-colors text-sm">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Orders
                        </button>
                        <button onclick="showTab('pending_reviews')"
                            class="w-full text-left flex items-center px-3 py-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded transition-colors text-sm">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            Pending Reviews
                        </button>
                        <button onclick="showTab('wishlist')"
                            class="w-full text-left flex items-center px-3 py-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded transition-colors text-sm">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            Wishlist
                        </button>
                        <button onclick="showTab('addresses')"
                            class="w-full text-left flex items-center px-3 py-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded transition-colors text-sm">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Addresses
                        </button>
                        <button onclick="showTab('profile')"
                            class="w-full text-left flex items-center px-3 py-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded transition-colors text-sm">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile
                        </button>
                        <button onclick="showTab('password')"
                            class="w-full text-left flex items-center px-3 py-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded transition-colors text-sm">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Password & Security
                        </button>
                        <button onclick="showTab('notifications')"
                            class="w-full text-left flex items-center px-3 py-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded transition-colors text-sm">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            Notifications & Preferences
                        </button>
                    </nav>

                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <button onclick="logout()"
                            class="w-full text-left flex items-center px-3 py-2 text-red-600 hover:bg-red-50 rounded transition-colors text-sm">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Content - Scrolls independently -->
            <div class="lg:col-span-9 lg:max-h-[calc(100vh-200px)] lg:overflow-y-auto shadow-sm">
                <!-- Dashboard Tab -->
                <div id="dashboard-tab" class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="text-blue-600 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600">Completed Orders</p>
                                    <p class="text-xl font-bold text-gray-800" id="completed-orders">0</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="text-purple-600 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600">Pending Orders</p>
                                    <p class="text-xl font-bold text-gray-800" id="pending-orders">0</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="text-pink-600 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600">Wishlist</p>
                                    <p class="text-xl font-bold text-gray-800" id="wishlist-count">0</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="text-green-600 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600">Reviews</p>
                                    <p class="text-xl font-bold text-gray-800" id="reviews-count">0</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-bold text-gray-800">Recent Orders</h2>
                            <button onclick="showTab('orders')" class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                                View all orders →
                            </button>
                        </div>
                        <div id="recent-orders" class="space-y-4">
                            <!-- Loading state -->
                            <div class="text-center py-8">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto mb-3"></div>
                                <p class="text-gray-600">Loading your orders...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Tab -->
                <div id="orders-tab" class="hidden">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-bold text-gray-800">My Orders</h2>
                            <select id="order-status-filter" onchange="loadOrders(1)"
                                class="px-3 py-2 border border-gray-200 rounded text-sm">
                                <option value="all">All Orders</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div id="orders-list" class="space-y-4">
                            <!-- Loading state -->
                            <div class="text-center py-8">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto mb-3"></div>
                                <p class="text-gray-600">Loading your orders...</p>
                            </div>
                        </div>

                        <!-- Pagination for orders -->
                        <div id="orders-pagination" class="mt-4"></div>
                    </div>
                </div>

                <!-- Pending Reviews Tab -->
                <div id="pending_reviews-tab" class="hidden">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-bold text-gray-800">Pending Reviews</h2>
                        </div>

                        <div class="text-center py-12">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-3">You have no orders waiting for feedback</h3>
                            <p class="text-gray-600 mb-8 max-w-md mx-auto">
                                After getting your products delivered, you will be able to rate and review them. Your feedback will be published on the product page to help all <?php echo APP_NAME; ?> users get the best shopping experience!
                            </p>
                            <div class="w-full border-t border-gray-200 mb-8"></div>
                            <a href="products.php" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-medium">
                                Continue Shopping
                            </a>
                        </div>


                    </div>
                </div>

                <!-- Wishlist Tab -->
                <div id="wishlist-tab" class="hidden">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-6">My Wishlist</h2>
                        <div id="wishlist-items" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <!-- Wishlist items will be loaded here -->
                            <div class="text-center py-8 col-span-full">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto mb-3"></div>
                                <p class="text-gray-600">Loading your wishlist...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Addresses Tab -->
                <div id="addresses-tab" class="hidden">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-bold text-gray-800">Shipping Addresses</h2>
                            <button onclick="showAddAddressModal()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition text-sm font-medium">
                                + Add New Address
                            </button>
                        </div>
                        <div id="addresses-list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Addresses will be loaded here -->
                            <div class="text-center py-8 col-span-full">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto mb-3"></div>
                                <p class="text-gray-600">Loading your addresses...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Tab -->
                <div id="profile-tab" class="hidden">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-6">Profile Information</h2>
                        <form id="profile-form" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                    <input type="text" name="first_name" id="first_name"
                                        value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                    <input type="text" name="last_name" id="last_name"
                                        value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" name="email" id="email"
                                    value="<?php echo htmlspecialchars($user['email']); ?>"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" name="phone" id="phone"
                                    value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                            </div>
                            <div class="pt-2">
                                <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition font-medium">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Tab -->
                <div id="password-tab" class="hidden">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-6">Password & Security</h2>
                        <form id="password-form" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                <input type="password" name="current_password" id="current_password" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                <input type="password" name="new_password" id="new_password" required minlength="8"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters long</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" required minlength="8"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                            </div>
                            <div class="pt-2">
                                <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition font-medium">
                                    Update Password
                                </button>
                            </div>
                        </form>

                        <div class="mt-8 pt-8 border-t border-gray-200">
                            <h3 class="text-md font-semibold text-gray-800 mb-4">Security Status</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700 text-sm">Account Created</span>
                                    <span class="text-gray-600 text-sm"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                <!--  <div class="flex items-center justify-between">
                                    <span class="text-gray-700 text-sm">Last Password Change</span>
                                    <span class="text-gray-600 text-sm">Never</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700 text-sm">Two-Factor Authentication</span>
                                    <span class="text-red-600 text-sm">Disabled</span>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>
                <div id="notifications-tab" class="hidden">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-6">Notifications & Preferences</h2>

                        <div class="space-y-6">
                            <!-- Newsletter Subscription -->
                            <div class="border border-gray-200 rounded-lg p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-md font-semibold text-gray-800">Newsletter Subscription</h3>
                                        <p class="text-sm text-gray-600 mt-2">
                                            Subscribe to our newsletter to stay updated with the latest products, exclusive deals, and important announcements.
                                        </p>
                                    </div>
                                    <div class="ml-4 flex-shrink-0">
                                        <input type="checkbox" id="newsletter-checkbox"
                                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500 cursor-pointer"
                                            onchange="updatePreference('newsletter', this.checked)">
                                    </div>
                                </div>
                            </div>

                            <!-- Notification Types -->
                            <div class="border border-gray-200 rounded-lg p-6">
                                <h3 class="text-md font-semibold text-gray-800 mb-4">What would you like to hear about?</h3>
                                <p class="text-sm text-gray-600 mb-6">Choose the types of notifications you'd like to receive:</p>

                                <div class="space-y-4">
                                    <!-- New Products -->
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex items-start">
                                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">New Products</p>
                                                <p class="text-xs text-gray-600">Get notified when we launch new products</p>
                                            </div>
                                        </div>
                                        <input type="checkbox" id="new_products-checkbox"
                                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500 cursor-pointer"
                                            onchange="updatePreference('new_products', this.checked)">
                                    </div>

                                    <!-- Featured Products -->
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex items-start">
                                            <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">Featured Products</p>
                                                <p class="text-xs text-gray-600">Receive updates on our featured and recommended items</p>
                                            </div>
                                        </div>
                                        <input type="checkbox" id="featured_products-checkbox"
                                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500 cursor-pointer"
                                            onchange="updatePreference('featured_products', this.checked)">
                                    </div>

                                    <!-- Sales & Promotions -->
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex items-start">
                                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">Sales & Promotions</p>
                                                <p class="text-xs text-gray-600">Don't miss out on special discounts and promotions</p>
                                            </div>
                                        </div>
                                        <input type="checkbox" id="sales_promotions-checkbox"
                                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500 cursor-pointer"
                                            onchange="updatePreference('sales_promotions', this.checked)">
                                    </div>

                                    <!-- Important News -->
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex items-start">
                                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">Important News</p>
                                                <p class="text-xs text-gray-600">Stay informed about important updates and announcements</p>
                                            </div>
                                        </div>
                                        <input type="checkbox" id="important_news-checkbox"
                                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500 cursor-pointer"
                                            onchange="updatePreference('important_news', this.checked)">
                                    </div>

                                    <!-- Order Updates -->
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex items-start">
                                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">Order Updates</p>
                                                <p class="text-xs text-gray-600">Get notifications about your order status and shipments</p>
                                            </div>
                                        </div>
                                        <input type="checkbox" id="order_updates-checkbox"
                                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500 cursor-pointer"
                                            onchange="updatePreference('order_updates', this.checked)">
                                    </div>

                                    <!-- Product Reviews -->
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex items-start">
                                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 14h4m1-8C6.477 4 4 6.239 4 9c0 5.997 3.97 10.933 9 15.6 5.03-4.667 9-9.603 9-15.6 0-2.761-2.477-5-5.5-5S12 3.239 12 6c0 .556.112 1.083.32 1.6z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">Product Reviews</p>
                                                <p class="text-xs text-gray-600">Receive alerts about reviews on products you've purchased</p>
                                            </div>
                                        </div>
                                        <input type="checkbox" id="product_reviews-checkbox"
                                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500 cursor-pointer"
                                            onchange="updatePreference('product_reviews', this.checked)">
                                    </div>
                                </div>
                            </div>

                            <!-- Save Status Message -->
                            <div id="preference-save-message" class="hidden p-4 rounded-lg bg-green-50 border border-green-200">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm text-green-800">Preferences saved successfully!</span>
                                </div>
                            </div>

                            <!-- Info Box -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm text-blue-800">
                                            <strong>Pro Tip:</strong> By subscribing to our newsletter, you'll get exclusive discounts, early access to new products, and special promotions directly in your inbox!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications & Preferences Tab -->

        </div>
    </div>
</div>

<!-- Address Modal (keep from your existing code) -->
<div id="addressModal" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-4 sm:top-20 mx-auto p-4 sm:p-5 w-full max-w-md">
        <div class="bg-white rounded-lg shadow-xl border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-800" id="addressModalTitle">Add New Address</h3>
                <button onclick="closeAddressModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="address-form" class="space-y-3 sm:space-y-4">
                <input type="hidden" id="address_id" name="address_id">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address Type</label>
                    <select name="address_type" id="address_type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm sm:text-base">
                        <option value="home">Home</option>
                        <option value="work">Work</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Street Address</label>
                    <input type="text" name="street_address" id="street_address" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm sm:text-base"
                        placeholder="Enter your street address">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                        <input type="text" name="city" id="city" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm sm:text-base"
                            placeholder="City">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Region</label>
                        <select name="region" id="region" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm sm:text-base">
                            <option value="">Select Region</option>
                            <option value="Ahafo">Ahafo</option>
                            <option value="Ashanti">Ashanti</option>
                            <option value="Bono">Bono</option>
                            <option value="Bono East">Bono East</option>
                            <option value="Central">Central</option>
                            <option value="Eastern">Eastern</option>
                            <option value="Greater Accra">Greater Accra</option>
                            <option value="North East">North East</option>
                            <option value="Northern">Northern</option>
                            <option value="Oti">Oti</option>
                            <option value="Savannah">Savannah</option>
                            <option value="Upper East">Upper East</option>
                            <option value="Upper West">Upper West</option>
                            <option value="Volta">Volta</option>
                            <option value="Western">Western</option>
                            <option value="Western North">Western North</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                        <input type="text" name="postal_code" id="postal_code"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm sm:text-base"
                            placeholder="Postal Code">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                        <input type="text" name="country" id="country" required value="Ghana" readonly
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm sm:text-base bg-gray-50">
                    </div>
                </div>

                <div class="flex items-center pt-2">
                    <input type="checkbox" name="is_default" id="is_default"
                        class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                    <label for="is_default" class="ml-2 text-sm text-gray-700">Set as default address</label>
                </div>

                <div class="flex justify-end space-x-2 sm:space-x-3 pt-4 sm:pt-6">
                    <button type="button" onclick="closeAddressModal()"
                        class="bg-gray-500 text-white px-4 py-2 sm:px-6 sm:py-3 rounded-lg hover:bg-gray-600 transition-all duration-200 font-semibold text-sm sm:text-base">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 py-2 sm:px-6 sm:py-3 rounded-lg transition-all duration-200 font-semibold text-sm sm:text-base">
                        Save Address
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Review Modal -->
<div id="reviewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-4 sm:top-20 mx-auto p-4 sm:p-5 w-full max-w-2xl">
        <div class="bg-white rounded-lg shadow-xl border border-gray-200">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800">Leave a Review</h3>
                <button onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 max-h-[calc(100vh-200px)] overflow-y-auto">
                <div id="review-items-container" class="space-y-4">
                    <!-- Review items will be loaded here -->
                    <div class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto mb-3"></div>
                        <p class="text-gray-600">Loading items...</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
                <button onclick="closeReviewModal()"
                    class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition font-medium">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Global variables
    let currentTab = 'dashboard';
    let ordersCurrentPage = 1;
    const ORDERS_PER_PAGE = 6;

    // Tab functionality
    function showTab(tabName) {
        event.preventDefault();

        // Hide all tabs
        document.querySelectorAll('[id$="-tab"]').forEach(tab => {
            tab.classList.add('hidden');
        });

        // Remove active class from all tab buttons
        document.querySelectorAll('nav button').forEach(btn => {
            btn.classList.remove('text-purple-600', 'bg-purple-50');
            btn.classList.add('text-gray-600', 'hover:text-purple-600', 'hover:bg-purple-50');
        });

        // Show selected tab
        const targetTab = document.getElementById(tabName + '-tab');
        if (targetTab) {
            targetTab.classList.remove('hidden');
        }

        // Add active class to clicked tab button
        const clickedButton = event.target.closest('button');
        if (clickedButton) {
            clickedButton.classList.remove('text-gray-600', 'hover:text-purple-600', 'hover:bg-purple-50');
            clickedButton.classList.add('text-purple-600', 'bg-purple-50');
        }

        currentTab = tabName;

        // Load tab-specific data
        if (tabName === 'orders') {
            ordersCurrentPage = 1;
            loadOrders(ordersCurrentPage);
        } else if (tabName === 'addresses') {
            loadAddresses();
        } else if (tabName === 'wishlist') {
            loadWishlist();
        } else if (tabName === 'dashboard') {
            loadDashboardData();
        } else if (tabName === 'pending_reviews') {
            loadPendingReviews();
        } else if (tabName === 'notifications') {
            loadNotificationPreferences();
        }
    }

    // Dashboard functions
    async function loadDashboardData() {
        try {
            // Load dashboard stats
            const statsResponse = await fetch('ajax/dashboard.php?action=get_dashboard_stats');
            const statsData = await statsResponse.json();

            if (statsData.success) {
                updateDashboardStats(statsData.stats);
            } else {
                console.warn('Dashboard stats failed:', statsData.message);
                setDefaultStats();
            }

            // Load recent orders
            await loadRecentOrders();

            // Load wishlist count
            await loadWishlistCount();

        } catch (error) {
            console.error('Error loading dashboard data:', error);
            setDefaultStats();
            showEmptyRecentOrders();
        }
    }

    function updateDashboardStats(stats) {
        // Update main stats cards
        document.getElementById('completed-orders').textContent = stats.completed_orders || 0;
        document.getElementById('pending-orders').textContent = stats.pending_orders || 0;
        document.getElementById('wishlist-count').textContent = stats.wishlist_count || 0;
        document.getElementById('reviews-count').textContent = stats.reviews_count || 0;

        // Update any other stats elements
        const totalOrders = (stats.completed_orders || 0) + (stats.pending_orders || 0);
        if (document.getElementById('total-orders')) {
            document.getElementById('total-orders').textContent = totalOrders;
        }
        if (document.getElementById('quick-orders-count')) {
            document.getElementById('quick-orders-count').textContent = totalOrders;
        }
    }

    function setDefaultStats() {
        const defaultStats = {
            'completed-orders': 0,
            'pending-orders': 0,
            'wishlist-count': 0,
            'reviews-count': 0,
            'total-orders': 0,
            'quick-orders-count': 0
        };

        Object.keys(defaultStats).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = defaultStats[id];
            }
        });
    }

    async function loadRecentOrders() {
        try {
            const response = await fetch('ajax/dashboard.php?action=get_recent_orders&limit=3');
            const data = await response.json();

            if (data.success && data.orders && data.orders.length > 0) {
                renderRecentOrders(data.orders);
            } else {
                showEmptyRecentOrders();
            }
        } catch (error) {
            console.error('Error loading recent orders:', error);
            showEmptyRecentOrders();
        }
    }

    function renderRecentOrders(orders) {
        const container = document.getElementById('recent-orders');

        if (!orders || orders.length === 0) {
            showEmptyRecentOrders();
            return;
        }

        container.innerHTML = orders.map(order => `
            <div class="border border-gray-200 rounded-lg p-4 transition-all duration-200">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="text-purple-600 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-800 text-sm truncate">${order.order_number || 'Order #' + order.id}</p>
                            <p class="text-gray-500 text-xs">${formatDate(order.created_at)}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusClass(order.status)} self-start md:self-auto">
                        ${getStatusText(order.status)}
                    </span>
                </div>
                
                <div class="flex flex-col md:flex-row md:items-center md:justify-between pt-3 border-t border-gray-100 gap-2">
                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                        <span class="flex items-center">
                            <svg class="mr-1 text-gray-400 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            ${order.item_count || 1} items
                        </span>
                        <span class="font-semibold text-purple-600">
                            ${order.formatted_total || formatCurrency(order.total_amount || 0)}
                        </span>
                    </div>
                    <a href="order_details.php?order_id=${order.id}" class="text-purple-600 hover:text-purple-700 text-sm font-medium flex items-center justify-end md:justify-start">
                        View Details
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        `).join('');
    }

    function showEmptyRecentOrders() {
        const container = document.getElementById('recent-orders');
        container.innerHTML = `
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="text-gray-400 w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <p class="text-gray-600 mb-2">No recent orders</p>
                <a href="products.php" class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                    Start shopping →
                </a>
            </div>
        `;
    }

    async function loadWishlistCount() {
        try {
            const response = await fetch('ajax/wishlist.php?action=get_count');
            const data = await response.json();

            if (data.success && data.count !== undefined) {
                document.getElementById('wishlist-count').textContent = data.count;
                if (document.getElementById('quick-wishlist-count')) {
                    document.getElementById('quick-wishlist-count').textContent = data.count;
                }
            }
        } catch (error) {
            console.error('Error loading wishlist count:', error);
        }
    }

    // Orders functions
    async function loadOrders(page = 1) {
        try {
            const statusFilter = document.getElementById('order-status-filter').value;
            ordersCurrentPage = page;
            const statusParam = statusFilter && statusFilter !== 'all' ? `&status=${encodeURIComponent(statusFilter)}` : '';
            const url = `ajax/orders.php?action=get_orders&page=${ordersCurrentPage}&limit=${ORDERS_PER_PAGE}${statusParam}`;

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                renderOrders(data.orders, data.pagination || null);
            } else {
                showOrdersError(data.message || 'Failed to load orders');
            }
        } catch (error) {
            console.error('Error loading orders:', error);
            showOrdersError();
        }
    }

    function renderOrders(orders, pagination = null) {
        const container = document.getElementById('orders-list');

        if (!orders || orders.length === 0) {
            container.innerHTML = `
            <div class="text-center py-16">
                <div class="w-32 h-32 bg-gradient-to-br from-purple-100 to-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="text-purple-500 w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">No Orders Found</h3>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    ${document.getElementById('order-status-filter').value !== 'all' ? 
                      'No orders match the selected filter.' : 
                      'Start shopping and your orders will appear here!'}
                </p>
                <a href="products.php" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-8 py-3 rounded-lg hover:shadow-lg transition-all duration-200 font-semibold text-lg inline-flex items-center">
                    <svg class="mr-3 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    Start Shopping
                </a>
            </div>
        `;
            return;
        }

        let html = '';

        orders.forEach(order => {
            // Get thumbnail (first image or placeholder)
            const thumb = (order.item_images && order.item_images.length > 0) ?
                order.item_images[0] :
                'https://via.placeholder.com/80x80?text=No+Image';

            // Get first word of the first item name
            let rawFirst = (order.item_names && order.item_names.length > 0) ?
                order.item_names[0] :
                ('Order #' + (order.order_number || order.id));
            const firstName = rawFirst.split(/\s+/)[0];

            // Format the date
            const formattedDate = formatDate(order.created_at);

            // Status color and text
            const statusColor = getStatusClass(order.status);
            const statusText = getStatusText(order.status);
            const totalAmount = order.formatted_total || formatCurrency(order.total_amount || 0);

            html += `
        <div class="order-card bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all duration-300 overflow-hidden fade-in mb-4">
            <div class="p-4 lg:p-6 flex items-start gap-4">
                <!-- Left: Thumbnail -->
                <div class="flex-shrink-0">
                    <img src="${thumb}" alt="${firstName}" 
                         class="w-20 h-20 object-cover rounded-md border"
                         onerror="this.src='https://via.placeholder.com/80x80?text=Image+Error'">
                </div>

                <!-- Middle: Details -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0">
                            <h4 class="text-sm md:text-base font-semibold text-gray-900 truncate">${firstName}</h4>
                            <div class="text-xs text-gray-500 mt-1 truncate">Order ${order.order_number || '#' + order.id}</div>
                        </div>
                        <div class="hidden lg:block text-right flex-shrink-0 ml-4">
                            <div class="text-md font-bold text-gray-700">${totalAmount}</div>
                            <div class="text-xs text-gray-500">Total Amount</div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-3 flex-wrap">
                        <span class="status-badge inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold ${statusColor}">
                            ${statusText}
                        </span>
                        <div class="text-xs text-gray-600">${formattedDate} • ${order.item_count || 1} item${(order.item_count || 1) !== 1 ? 's' : ''}</div>
                    </div>

                    ${order.item_names && order.item_names.length > 0 ? `
                    <p class="text-sm text-gray-600 mt-3 line-clamp-2">
                        ${order.item_names.slice(0, 3).map(n => n).join(', ')}${order.item_names.length > 3 ? '...' : ''}
                    </p>
                    ` : ''}

                    <!-- Mobile: SEE DETAILS (visible only on small screens) -->
                    <a href="order_details.php?order_id=${order.id}" 
                       class="lg:hidden mt-3 inline-block text-sm text-purple-600 font-semibold hover:text-purple-700 transition-colors">
                        See Details
                    </a>
                </div>

                <!-- Right: Actions (Desktop) -->
                <div class="hidden lg:flex flex-shrink-0 ml-4 flex-col items-end justify-between">
                    <a href="order_details.php?order_id=${order.id}" 
                       class="text-xs md:text-sm text-purple-600 font-semibold px-3 py-2 rounded hover:bg-purple-50 transition-colors whitespace-nowrap">
                        See Details
                    </a>
                    <div class="mt-3 text-sm font-semibold text-gray-900">${totalAmount}</div>
                </div>
            </div>

            <!-- Compact Action Icons Row -->
            <div class="px-4 lg:px-6 pb-4 border-t border-gray-100 pt-4">
                <div class="flex items-center gap-2">
                    <!-- Track Order -->
                    ${(order.status === 'shipped' || order.status === 'processing') ? `
                    <button onclick="trackOrder(${order.id}, '${order.order_number || order.id}')" 
                            class="border border-gray-300 text-gray-700 p-2 rounded-lg hover:bg-gray-50 transition-all duration-200 hover:scale-105 flex items-center justify-center group"
                            title="Track Order">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                    ` : ''}

                    <!-- Return Order -->
                    ${order.status === 'delivered' ? `
                    <button onclick="initiateReturn(${order.id})" 
                            class="border border-blue-300 text-blue-600 p-2 rounded-lg hover:bg-blue-50 transition-all duration-200 hover:scale-105 flex items-center justify-center group"
                            title="Return Order">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path>
                        </svg>
                    </button>
                    ` : ''}

                    <!-- Cancel Order -->
                    ${(order.status === 'pending' || order.status === 'processing') ? `
                    <button onclick="cancelOrder(${order.id})" 
                            class="border border-red-300 text-red-600 p-2 rounded-lg hover:bg-red-50 transition-all duration-200 hover:scale-105 flex items-center justify-center group"
                            title="Cancel Order">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    ` : ''}

                    <!-- Reorder -->
                    <button onclick="reorderWithConfirmation(${order.id})" 
                            class="border border-green-300 text-green-600 p-2 rounded-lg hover:bg-green-50 transition-all duration-200 hover:scale-105 flex items-center justify-center group"
                            title="Reorder Items">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>

                    <!-- Leave Review -->
                    ${order.status === 'delivered' ? `
                    <button onclick="leaveReview(${order.id})" 
                            class="border border-purple-300 text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all duration-200 hover:scale-105 flex items-center justify-center group"
                            title="Leave Review">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </button>
                    ` : ''}

                    <!-- View Details (Mobile) -->
                    <a href="order_details.php?order_id=${order.id}" 
                       class="lg:hidden border border-purple-300 text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all duration-200 hover:scale-105 flex items-center justify-center group"
                       title="View Details">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        `;
        });

        container.innerHTML = html;

        // Render pagination controls when provided
        if (pagination) {
            renderOrdersPagination(pagination);
        }
    }

    // REORDER FUNCTIONALITY - Using the improved reorder function from OrdersManager
    async function reorder(orderId) {
        try {
            console.log('Starting reorder for order:', orderId);

            const response = await fetch('ajax/orders.php?action=reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Reorder response:', data);

            if (data.success) {
                let message = data.message || 'Items added to cart successfully!';

                if (data.unavailable_items && data.unavailable_items.length > 0) {
                    const unavailableNames = data.unavailable_items.map(item => item.product_name).join(', ');
                    message = `Some items were added to cart. Unavailable items: ${unavailableNames}`;
                }

                showNotification(message, 'success');

                // Update cart count
                updateCartCount(data.cart_count);

            } else {
                showNotification(data.message || 'Failed to add items to cart', 'error');
            }
        } catch (error) {
            console.error('Error reordering:', error);
            showNotification('Failed to add items to cart. Please try again.', 'error');
        }
    }

    // REORDER WITH CONFIRMATION MODAL
    function reorderWithConfirmation(orderId) {
        showConfirmationModal(
            'Reorder Items',
            'Would you like to add all items from this order to your cart?',
            () => reorder(orderId), {
                type: 'info',
                confirmText: 'Add to Cart',
                cancelText: 'Cancel'
            }
        );
    }

    function renderOrdersPagination(pagination) {
        const container = document.getElementById('orders-pagination');
        if (!container) return;

        if (!pagination || pagination.total <= ORDERS_PER_PAGE) {
            container.innerHTML = '';
            return;
        }

        const page = pagination.page || ordersCurrentPage || 1;
        const pages = pagination.pages || Math.ceil((pagination.total || 0) / ORDERS_PER_PAGE);
        const startItem = (page - 1) * pagination.limit + 1;
        const endItem = Math.min(pagination.total, page * pagination.limit);

        container.innerHTML = `
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <button id="orders-prev" class="px-4 py-2 border rounded text-sm bg-white hover:bg-gray-50 ${page <= 1 ? 'opacity-50 cursor-not-allowed' : ''}">Previous</button>
                    <button id="orders-next" class="px-4 py-2 border rounded text-sm bg-white hover:bg-gray-50 ${page >= pages ? 'opacity-50 cursor-not-allowed' : ''}">Next</button>
                </div>
                <div class="text-sm text-gray-600">Showing ${startItem}–${endItem} of ${pagination.total}</div>
            </div>
        `;

        // Attach handlers
        const prevBtn = document.getElementById('orders-prev');
        const nextBtn = document.getElementById('orders-next');

        if (prevBtn) {
            prevBtn.onclick = function() {
                if (ordersCurrentPage > 1) {
                    loadOrders(ordersCurrentPage - 1);
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            };
        }

        if (nextBtn) {
            nextBtn.onclick = function() {
                if (ordersCurrentPage < pages) {
                    loadOrders(ordersCurrentPage + 1);
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            };
        }
    }

    function showOrdersError(message) {
        const container = document.getElementById('orders-list');
        container.innerHTML = `
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="text-gray-400 w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Unable to Load Orders</h3>
                <p class="text-gray-600 mb-6">${message || 'There was a problem loading your order history.'}</p>
                <button onclick="loadOrders(ordersCurrentPage)" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition font-semibold">
                    <svg class="mr-2 w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Try Again
                </button>
            </div>
        `;
    }

    // Pending Reviews functions
    // Pending Reviews functions
    async function loadPendingReviews() {
        try {
            const response = await fetch('ajax/reviews.php?action=get_pending_reviews');
            const data = await response.json();

            if (data.success) {
                renderPendingReviews(data.reviews);
            } else {
                console.log('No pending reviews:', data.message);
            }
        } catch (error) {
            console.error('Error loading pending reviews:', error);
            // Keep the default empty state
        }
    }

    function renderPendingReviews(reviews) {
        const container = document.getElementById('pending_reviews-tab');
        if (!container) return;

        if (!reviews || reviews.length === 0) {
            // Keep the default empty state
            container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold text-gray-800">Pending Reviews</h2>
                </div>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">You have no orders waiting for feedback</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">
                        After getting your products delivered, you will be able to rate and review them. Your feedback will be published on the product page to help all <?php echo APP_NAME; ?> users get the best shopping experience!
                    </p>
                    <div class="w-full border-t border-gray-200 mb-8"></div>
                    <a href="products.php" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-medium">
                        Continue Shopping
                    </a>
                </div>
            </div>
        `;
            return;
        }

        // If there are reviews, update the UI
        container.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-bold text-gray-800">Pending Reviews (${reviews.length})</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="pending_reviews_list">
                ${reviews.map(review => `
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start space-x-3 mb-3">
                            <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-800 text-sm truncate">${review.product_name}</h4>
                                <p class="text-xs text-gray-600 mt-1">Order #${review.order_number}</p>
                                <p class="text-xs text-gray-500">Delivered on ${formatDate(review.order_date)}</p>
                            </div>
                        </div>
                        <button onclick="showReviewModal(${review.order_id})" 
                                class="w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition text-sm font-medium">
                            Write Review
                        </button>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    }

    // Review Modal Functions - COMPLETE IMPLEMENTATION
    function showReviewModal(orderId) {
        console.log('Opening review modal for order:', orderId);

        fetch(`ajax/reviews.php?action=get_order_items_for_review&order_id=${orderId}`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    console.log('Rendering modal with', data.items.length, 'items');
                    renderReviewModal(orderId, data.order_number, data.items);
                    document.getElementById('reviewModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                } else {
                    console.error('API error:', data.message);
                    showNotification(data.message || 'Failed to load order items', 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showNotification('Error loading order items: ' + error.message, 'error');
            });
    }

    function closeReviewModal() {
        document.getElementById('reviewModal').classList.add('hidden');
        document.body.style.overflow = 'auto'; // Restore scrolling
    }

    function renderReviewModal(orderId, orderNumber, items) {
        const container = document.getElementById('review-items-container');

        // Filter out items that already have reviews
        const itemsToReview = items.filter(item => !item.has_review);
        const reviewedItems = items.filter(item => item.has_review);

        if (itemsToReview.length === 0) {
            container.innerHTML = `
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="text-green-600 w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">All Items Reviewed</h3>
                <p class="text-gray-600">You have already reviewed all items in Order #${orderNumber}</p>
                ${reviewedItems.length > 0 ? `
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Your Reviews</h4>
                        ${reviewedItems.map(item => `
                            <div class="bg-gray-50 rounded-lg p-3 mb-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-800">${item.product_name}</span>
                                    <div class="flex items-center">
                                        ${renderStars(item.rating)}
                                        <span class="text-xs text-gray-500 ml-2">${item.rating}/5</span>
                                    </div>
                                </div>
                                ${item.comment ? `<p class="text-xs text-gray-600 mt-1">"${item.comment}"</p>` : ''}
                                ${item.review_date ? `<p class="text-xs text-gray-400 mt-1">Reviewed on ${formatDate(item.review_date)}</p>` : ''}
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        `;
            return;
        }

        let html = `
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Review Items from Order #${orderNumber}</h3>
            <p class="text-sm text-gray-600">Please rate and review the following items from your order</p>
        </div>
    `;

        // Items to review
        itemsToReview.forEach((item, index) => {
            html += `
            <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4" id="review-item-${item.product_id}">
                <div class="flex items-start gap-4 mb-4">
                    <img src="${item.main_image}" alt="${item.main_image}" 
                         class="w-20 h-20 object-cover rounded-lg border"
                         onerror="this.src='https://via.placeholder.com/80x80?text=Product'">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800 mb-1">${item.product_name}</h4>
                        <p class="text-sm text-gray-600">Quantity: ${item.quantity} × ₵${parseFloat(item.product_price).toFixed(2)}</p>
                        <p class="text-sm font-semibold text-purple-600 mt-1">Total: ₵${(item.quantity * parseFloat(item.product_price)).toFixed(2)}</p>
                    </div>
                </div>

                <form class="review-form space-y-3" data-product-id="${item.product_id}">
                    <!-- Rating -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                        <div class="flex gap-1" id="rating-container-${item.product_id}">
                            ${[1, 2, 3, 4, 5].map(star => `
                                <button type="button" 
                                        class="star-btn w-10 h-10 text-2xl cursor-pointer transition-all text-gray-300" 
                                        data-rating="${star}" 
                                        data-product="${item.product_id}"
                                        onclick="setRating(${item.product_id}, ${star})">
                                    ★
                                </button>
                            `).join('')}
                        </div>
                        <input type="hidden" name="rating" id="rating-input-${item.product_id}" value="0" required>
                        <p class="text-xs text-gray-500 mt-1" id="rating-label-${item.product_id}">Select a rating</p>
                    </div>

                    <!-- Review Text -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
                        <textarea name="comment" id="comment-${item.product_id}" required minlength="10" maxlength="1000"
                            placeholder="Share your experience with this product... (minimum 10 characters)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                            rows="3"
                            oninput="updateCharCount(${item.product_id})"></textarea>
                        <div class="flex justify-between mt-1">
                            <p class="text-xs text-gray-500" id="char-count-${item.product_id}">0/1000 characters</p>
                            <p class="text-xs text-gray-500">Minimum 10 characters</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2 pt-2">
                        <button type="button" onclick="skipProductReview(${item.product_id})" 
                                class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition font-medium">
                            Skip
                        </button>
                        <button type="button" onclick="submitReview(${item.product_id})"
                                class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                id="submit-btn-${item.product_id}"
                                disabled>
                            Submit Review
                        </button>
                    </div>
                </form>
            </div>
        `;
        });

        // Already reviewed items (if any)
        if (reviewedItems.length > 0) {
            html += `
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Already Reviewed</h4>
                ${reviewedItems.map(item => `
                    <div class="bg-gray-50 rounded-lg p-3 mb-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-800">${item.product_name}</span>
                            <div class="flex items-center">
                                ${renderStars(item.rating)}
                                <span class="text-xs text-gray-500 ml-2">${item.rating}/5</span>
                            </div>
                        </div>
                        ${item.comment ? `<p class="text-xs text-gray-600 mt-1">"${item.comment}"</p>` : ''}
                        ${item.review_date ? `<p class="text-xs text-gray-400 mt-1">Reviewed on ${formatDate(item.review_date)}</p>` : ''}
                    </div>
                `).join('')}
            </div>
        `;
        }

        container.innerHTML = html;

        // Initialize form validation
        itemsToReview.forEach(item => {
            const commentField = document.getElementById(`comment-${item.product_id}`);
            const ratingInput = document.getElementById(`rating-input-${item.product_id}`);
            const submitBtn = document.getElementById(`submit-btn-${item.product_id}`);

            if (commentField && ratingInput && submitBtn) {
                const validateForm = () => {
                    const rating = parseInt(ratingInput.value) || 0;
                    const comment = commentField.value.trim();
                    const isValid = rating >= 1 && rating <= 5 && comment.length >= 10;
                    submitBtn.disabled = !isValid;
                };

                commentField.addEventListener('input', validateForm);
                ratingInput.addEventListener('input', validateForm);
            }
        });
    }

    // Helper function to render stars
    function renderStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += i <= rating ? '★' : '☆';
        }
        return `<span class="text-yellow-400">${stars}</span>`;
    }

    // Set rating for a product
    function setRating(productId, rating) {
        const ratingInput = document.getElementById(`rating-input-${productId}`);
        const ratingLabel = document.getElementById(`rating-label-${productId}`);
        const submitBtn = document.getElementById(`submit-btn-${productId}`);
        const stars = document.querySelectorAll(`[data-product="${productId}"]`);

        ratingInput.value = rating;

        // Update star display
        stars.forEach(star => {
            const starRating = parseInt(star.dataset.rating);
            star.classList.toggle('text-yellow-400', starRating <= rating);
            star.classList.toggle('text-gray-300', starRating > rating);
        });

        // Update rating label
        const labels = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
        ratingLabel.textContent = labels[rating - 1] || 'Select a rating';

        // Enable submit button if rating is selected and comment is long enough
        if (submitBtn) {
            const commentField = document.getElementById(`comment-${productId}`);
            const commentLength = commentField ? commentField.value.trim().length : 0;
            submitBtn.disabled = !(rating > 0 && commentLength >= 10);
        }

        // Trigger form validation
        const event = new Event('input');
        ratingInput.dispatchEvent(event);
    }

    // Update character count
    function updateCharCount(productId) {
        const textarea = document.getElementById(`comment-${productId}`);
        const charCount = document.getElementById(`char-count-${productId}`);
        const submitBtn = document.getElementById(`submit-btn-${productId}`);
        const ratingInput = document.getElementById(`rating-input-${productId}`);
        
        if (textarea && charCount) {
            const length = textarea.value.length;
            charCount.textContent = `${length}/1000 characters`;

            // Change color if approaching limit
            if (length > 900) {
                charCount.classList.add('text-red-600');
            } else {
                charCount.classList.remove('text-red-600');
            }

            // Enable submit button if rating is selected and comment is long enough
            if (submitBtn && ratingInput) {
                const rating = parseInt(ratingInput.value) || 0;
                submitBtn.disabled = !(rating > 0 && length >= 10);
            }
        }
    }

    // Submit review
    function submitReview(productId) {
        console.log('submitReview called for product:', productId);
        const ratingInput = document.getElementById(`rating-input-${productId}`);
        const commentField = document.getElementById(`comment-${productId}`);
        const submitBtn = document.getElementById(`submit-btn-${productId}`);

        console.log('Elements found:', {
            ratingInput: !!ratingInput,
            commentField: !!commentField,
            submitBtn: !!submitBtn
        });

        const rating = parseInt(ratingInput.value) || 0;
        const comment = commentField.value.trim();

        console.log('Review data:', { productId, rating, comment: comment.substring(0, 50) });

        if (rating < 1 || rating > 5) {
            showNotification('Please select a rating', 'error');
            return;
        }

        if (comment.length < 10) {
            showNotification('Please write a review of at least 10 characters', 'error');
            return;
        }

        if (comment.length > 1000) {
            showNotification('Review cannot exceed 1000 characters', 'error');
            return;
        }

        // Disable button during submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="flex items-center justify-center"><span class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>Submitting...</span>';

        console.log('Sending request to ajax/reviews.php');
        fetch('ajax/reviews.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'submit_review',
                    product_id: productId,
                    rating: rating,
                    comment: comment
                })
            })
            .then(response => {
                console.log('Response status:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showNotification('Review submitted successfully!', 'success');

                    // Remove reviewed item from modal
                    const itemElement = document.getElementById(`review-item-${productId}`);
                    if (itemElement) {
                        itemElement.style.opacity = '0.5';
                        itemElement.style.pointerEvents = 'none';
                        setTimeout(() => {
                            itemElement.style.display = 'none';

                            // Check if all items are reviewed
                            const remainingItems = document.querySelectorAll('[id^="review-item-"]').length;
                            if (remainingItems === 0) {
                                setTimeout(() => {
                                    closeReviewModal();
                                    if (currentTab === 'pending_reviews') {
                                        loadPendingReviews();
                                    }
                                }, 1000);
                            }
                        }, 500);
                    }
                } else {
                    showNotification(data.message || 'Failed to submit review', 'error');
                    // Re-enable button
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Review';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error submitting review', 'error');
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Review';
            });
    }

    // Skip product review
    function skipProductReview(productId) {
        const itemElement = document.getElementById(`review-item-${productId}`);
        if (itemElement) {
            itemElement.style.opacity = '0.5';
            itemElement.style.pointerEvents = 'none';
            const form = itemElement.querySelector('.review-form');
            if (form) {
                form.style.display = 'none';
            }
            itemElement.innerHTML += `
            <div class="text-center py-4 text-gray-500 border-t border-gray-200 mt-4">
                <p>Review skipped</p>
            </div>
        `;

            // Check if all items are skipped/reviewed
            setTimeout(() => {
                const remainingItems = document.querySelectorAll('[id^="review-item-"]').length;
                if (remainingItems === 0) {
                    setTimeout(() => {
                        closeReviewModal();
                        if (currentTab === 'pending_reviews') {
                            loadPendingReviews();
                        }
                    }, 1000);
                }
            }, 500);
        }
    }

    function skipReview(productId, orderId) {
        showConfirmationModal(
            'Skip Review',
            'Are you sure you want to skip reviewing this product?',
            function() {
                fetch('ajax/reviews.php?action=skip_review', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            order_id: orderId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Review skipped', 'success');
                            loadPendingReviews();
                        } else {
                            showNotification(data.message || 'Error skipping review', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error skipping review:', error);
                        showNotification('Error skipping review', 'error');
                    });
            }
        );
    }

    // Wishlist functions
    async function loadWishlist() {
        try {
            const response = await fetch('ajax/wishlist.php?action=get_wishlist');
            const data = await response.json();

            if (data.success) {
                renderWishlist(data.products);
            } else {
                showWishlistError();
            }
        } catch (error) {
            console.error('Error loading wishlist:', error);
            showWishlistError();
        }
    }

    function renderWishlist(products) {
        const container = document.getElementById('wishlist-items');

        if (!products || products.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <div class="w-24 h-24 bg-gradient-to-br from-pink-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="text-pink-500 w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Your Wishlist is Empty</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">Save items you love for easy access and quick purchase.</p>
                    <a href="products.php" class="bg-gradient-to-r from-pink-600 to-purple-600 text-white px-8 py-3 rounded-lg transition-all duration-200 font-semibold text-lg inline-flex items-center">
                        <svg class="mr-3 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Browse Products
                    </a>
                </div>
            `;
            return;
        }

        container.innerHTML = products.map(product => `
            <div class="rounded-lg overflow-hidden hover:shadow-md transition-all duration-200">
                <a href="product.php?id=${product.product_id}">
                    <img src="${product.image_url || 'https://via.placeholder.com/300x300'}" 
                         alt="${product.name}" 
                         class="w-full h-48 object-cover rounded-lg"
                         onerror="this.src='https://via.placeholder.com/300x300'">
                </a>
                <div class="p-3">
                    <a href="product.php?id=${product.product_id}" class="block">
                        <h4 class="text-gray-800 text-sm mb-2 line-clamp-2">${product.name && product.name.length > 15 ? product.name.slice(0,15) + '…' : product.name}</h4>
                    </a>
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-purple-600">${formatCurrency(product.price || 0)}</span>
                        <button onclick="removeFromWishlist(${product.product_id})" 
                                class="text-red-600 hover:text-red-700 text-sm">
                            Remove
                        </button>
                    </div>
                    <button onclick="addToCartFromWishlist(${product.product_id})" 
                            class="w-full bg-purple-600 text-white py-2 rounded mt-2 hover:bg-purple-700 transition text-sm">
                        Add to Cart
                    </button>
                </div>
            </div>
        `).join('');
    }

    function showWishlistError() {
        const container = document.getElementById('wishlist-items');
        container.innerHTML = `
            <div class="col-span-full text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="text-gray-400 w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Unable to Load Wishlist</h3>
                <p class="text-gray-600 mb-6">There was a problem loading your wishlist.</p>
                <button onclick="loadWishlist()" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition font-semibold">
                    Try Again
                </button>
            </div>
        `;
    }

    function removeFromWishlist(productId) {
        showConfirmationModal(
            'Remove from Wishlist',
            'Are you sure you want to remove this item from your wishlist?',
            function() {
                fetch('ajax/wishlist.php?action=remove_from_wishlist', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            product_id: productId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Item removed from wishlist', 'success');
                            loadWishlist();
                            loadDashboardData(); // Refresh dashboard stats
                        } else {
                            showNotification(data.message || 'Error removing item', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error removing from wishlist:', error);
                        showNotification('Error removing item', 'error');
                    });
            }
        );
    }

    function addToCartFromWishlist(productId) {
        fetch('ajax/cart.php?action=add_to_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Product added to cart!', 'success');
                    updateCartCount(data.cart_count);
                } else {
                    showNotification(data.message || 'Error adding to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                showNotification('Error adding to cart', 'error');
            });
    }

    // Address functions
    async function loadAddresses() {
        try {
            const response = await fetch('ajax/addresses.php?action=get_addresses');
            const data = await response.json();

            if (data.success) {
                renderAddresses(data.addresses);
            } else {
                showAddressesError();
            }
        } catch (error) {
            console.error('Error loading addresses:', error);
            showAddressesError();
        }
    }

    function renderAddresses(addresses) {
        const container = document.getElementById('addresses-list');

        if (!addresses || addresses.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <div class="w-24 h-24 bg-gradient-to-br from-green-100 to-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="text-green-500 w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">No Saved Addresses</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">Add your shipping addresses for faster checkout.</p>
                    <button onclick="showAddAddressModal()" class="bg-gradient-to-r from-green-600 to-blue-600 text-white px-8 py-3 rounded-lg transition-all duration-200 font-semibold text-lg inline-flex items-center">
                        <svg class="mr-3 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Your First Address
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = addresses.map(address => `
            <div class="border border-gray-200 rounded-lg p-4 ${address.is_default ? 'ring-2 ring-purple-500' : ''}">
                ${address.is_default ? `
                    <span class="inline-block px-2 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded-full mb-2">
                        Default Address
                    </span>
                ` : ''}
                <div class="mb-3">
                    <p class="font-medium text-gray-800 capitalize">${address.address_type || 'Address'}</p>
                    <p class="text-sm text-gray-600 mt-1">${address.street_address || ''}</p>
                    <p class="text-sm text-gray-600">${address.city || ''}, ${address.region || ''} ${address.postal_code || ''}</p>
                    <p class="text-sm text-gray-600">${address.country || 'Ghana'}</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="editAddress(${address.address_id})" 
                            class="text-purple-600 hover:text-purple-700 text-sm">
                        Edit
                    </button>
                    ${!address.is_default ? `
                        <button onclick="setDefaultAddress(${address.address_id})" 
                                class="text-purple-600 hover:text-purple-700 text-sm">
                            Set as Default
                        </button>
                    ` : ''}
                    <button onclick="deleteAddress(${address.address_id})" 
                            class="text-red-600 hover:text-red-700 text-sm">
                        Delete
                    </button>
                </div>
            </div>
        `).join('');
    }

    function showAddressesError() {
        const container = document.getElementById('addresses-list');
        container.innerHTML = `
            <div class="col-span-full text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="text-gray-400 w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Unable to Load Addresses</h3>
                <p class="text-gray-600 mb-6">There was a problem loading your addresses.</p>
                <button onclick="loadAddresses()" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition font-semibold">
                    Try Again
                </button>
            </div>
        `;
    }

    // Address Modal functions
    function showAddAddressModal() {
        document.getElementById('addressModalTitle').textContent = 'Add New Address';
        document.getElementById('address-form').reset();
        document.getElementById('address_id').value = '';
        document.getElementById('is_default').checked = false;
        document.getElementById('addressModal').classList.remove('hidden');
    }

    function closeAddressModal() {
        document.getElementById('addressModal').classList.add('hidden');
    }

    function editAddress(addressId) {
        fetch(`ajax/addresses.php?action=get_address&id=${addressId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const address = data.address;
                    document.getElementById('addressModalTitle').textContent = 'Edit Address';
                    document.getElementById('address_id').value = address.address_id;
                    document.getElementById('address_type').value = address.address_type || 'home';
                    document.getElementById('street_address').value = address.street_address || '';
                    document.getElementById('city').value = address.city || '';
                    document.getElementById('region').value = address.region || '';
                    document.getElementById('postal_code').value = address.postal_code || '';
                    document.getElementById('country').value = address.country || 'Ghana';
                    document.getElementById('is_default').checked = address.is_default == 1;
                    document.getElementById('addressModal').classList.remove('hidden');
                } else {
                    showNotification(data.message || 'Error loading address', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading address:', error);
                showNotification('Error loading address', 'error');
            });
    }

    // Form submissions
    document.addEventListener('DOMContentLoaded', function() {
        // Profile form
        const profileForm = document.getElementById('profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                
                showConfirmationModal(
                    'Update Profile',
                    'Are you sure you want to save these changes to your profile?',
                    function() {
                        const formData = new FormData(form);

                        fetch('ajax/account.php?action=update_profile', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showNotification('Profile updated successfully', 'success');
                                    // Update displayed name initials
                                    const firstName = document.getElementById('first_name').value;
                                    const lastName = document.getElementById('last_name').value;
                                    const initialsElement = document.querySelector('.text-lg.font-bold.text-white');
                                    if (initialsElement && firstName && lastName) {
                                        initialsElement.textContent = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
                                    }
                                } else {
                                    showNotification(data.message || 'Error updating profile', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error updating profile:', error);
                                showNotification('Error updating profile', 'error');
                            });
                    }
                );
            });
        }

        // Password form
        const passwordForm = document.getElementById('password-form');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                if (newPassword !== confirmPassword) {
                    showNotification('Passwords do not match', 'error');
                    return;
                }

                if (newPassword.length < 8) {
                    showNotification('Password must be at least 8 characters long', 'error');
                    return;
                }

                const form = this;
                
                showConfirmationModal(
                    'Change Password',
                    'Are you sure you want to change your password?',
                    function() {
                        const formData = new FormData(form);

                        fetch('ajax/account.php?action=change_password', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showNotification('Password changed successfully', 'success');
                                    form.reset();
                                } else {
                                    showNotification(data.message || 'Error changing password', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error changing password:', error);
                                showNotification('Error changing password', 'error');
                            });
                    }
                );
            });
        }

        // Address form
        const addressForm = document.getElementById('address-form');
        if (addressForm) {
            addressForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const addressId = document.getElementById('address_id').value;
                const action = addressId ? 'update_address' : 'add_address';

                fetch(`ajax/addresses.php?action=${action}`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(addressId ? 'Address updated successfully' : 'Address added successfully', 'success');
                            closeAddressModal();
                            loadAddresses();
                            // Refresh dashboard stats if we're on dashboard
                            if (currentTab === 'dashboard') {
                                loadDashboardData();
                            }
                        } else {
                            showNotification(data.message || 'Error saving address', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error saving address:', error);
                        showNotification('Error saving address', 'error');
                    });
            });
        }
    });

    // Address actions
    function setDefaultAddress(addressId) {
        showConfirmationModal(
            'Set Default Address',
            'Are you sure you want to set this as your default shipping address?',
            function() {
                fetch('ajax/addresses.php?action=set_default', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            address_id: addressId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Default address updated', 'success');
                            loadAddresses();
                        } else {
                            showNotification(data.message || 'Error updating default address', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error setting default address:', error);
                        showNotification('Error updating default address', 'error');
                    });
            }
        );
    }

    function deleteAddress(addressId) {
        showConfirmationModal(
            'Delete Address',
            'Are you sure you want to delete this address? This action cannot be undone.',
            function() {
                fetch('ajax/addresses.php?action=delete_address', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            address_id: addressId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Address deleted successfully', 'success');
                            loadAddresses();
                            // Refresh dashboard stats if we're on dashboard
                            if (currentTab === 'dashboard') {
                                loadDashboardData();
                            }
                        } else {
                            showNotification(data.message || 'Error deleting address', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting address:', error);
                        showNotification('Error deleting address', 'error');
                    });
            }
        );
    }
    // Add this at the end of your script
    window.addEventListener('popstate', function() {
        document.body.style.overflow = 'auto';
        document.body.style.position = 'static';
    });

    // Also fix any existing modal close handlers
    document.querySelectorAll('[onclick*="closeAddressModal"], [onclick*="closeReviewModal"]').forEach(btn => {
        const originalOnClick = btn.onclick;
        btn.onclick = function() {
            if (originalOnClick) originalOnClick.call(this);
            document.body.style.overflow = 'auto';
            document.body.style.position = 'static';
        };
    });
    // Notification Preferences functions
    async function loadNotificationPreferences() {
        try {
            const response = await fetch('ajax/preferences.php?action=get_preferences');
            const data = await response.json();

            if (data.success && data.preferences) {
                updatePreferenceUI(data.preferences);
            } else {
                console.error('Failed to load preferences:', data.message);
                // Use default values if load fails
                updatePreferenceUI({
                    new_products: 1,
                    featured_products: 1,
                    sales_promotions: 1,
                    important_news: 1,
                    order_updates: 1,
                    newsletter: 1,
                    product_reviews: 0
                });
            }
        } catch (error) {
            console.error('Error loading notification preferences:', error);
            showNotification('Error loading preferences', 'error');
        }
    }

    function updatePreferenceUI(preferences) {
        // Update all checkboxes based on preferences
        const preferenceMap = {
            'newsletter': preferences.newsletter,
            'new_products': preferences.new_products,
            'featured_products': preferences.featured_products,
            'sales_promotions': preferences.sales_promotions,
            'important_news': preferences.important_news,
            'order_updates': preferences.order_updates,
            'product_reviews': preferences.product_reviews
        };

        Object.keys(preferenceMap).forEach(key => {
            const checkbox = document.getElementById(key + '-checkbox');
            if (checkbox) {
                checkbox.checked = preferenceMap[key] == 1;
            }
        });
    }

    function updatePreference(preferenceKey, value) {
        // Hide success message initially
        const messageElement = document.getElementById('preference-save-message');
        if (messageElement) {
            messageElement.classList.add('hidden');
        }

        // Send update to server
        const formData = new FormData();
        formData.append('action', 'update_preference');
        formData.append('preference_key', preferenceKey);
        formData.append('value', value ? 1 : 0);

        fetch('ajax/preferences.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    if (messageElement) {
                        messageElement.classList.remove('hidden');
                        // Hide after 3 seconds
                        setTimeout(() => {
                            messageElement.classList.add('hidden');
                        }, 3000);
                    }

                    // Log preference update
                    console.log('Preference updated:', preferenceKey, value);

                    // Show toast notification
                    const preferenceLabel = preferenceKey.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    const status = value ? 'enabled' : 'disabled';
                    showNotification(`${preferenceLabel} ${status}`, 'success');

                } else {
                    showNotification(data.message || 'Error updating preference', 'error');
                    // Revert checkbox if update failed
                    const checkbox = document.getElementById(preferenceKey + '-checkbox');
                    if (checkbox) {
                        checkbox.checked = !value;
                    }
                }
            })
            .catch(error => {
                console.error('Error updating preference:', error);
                showNotification('Error updating preference', 'error');
                // Revert checkbox if update failed
                const checkbox = document.getElementById(preferenceKey + '-checkbox');
                if (checkbox) {
                    checkbox.checked = !value;
                }
            });
    }

    // Order actions
    function cancelOrder(orderId) {
        showConfirmationModal(
            'Cancel Order',
            'Are you sure you want to cancel this order? This action cannot be reversed.',
            function() {
                fetch('ajax/orders.php?action=cancel_order', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            order_id: orderId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Order cancelled successfully', 'success');
                            loadOrders(ordersCurrentPage);
                            // Refresh dashboard if we're on dashboard
                            if (currentTab === 'dashboard') {
                                loadDashboardData();
                            }
                        } else {
                            showNotification(data.message || 'Error cancelling order', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error cancelling order:', error);
                        showNotification('Error cancelling order', 'error');
                    });
            }
        );
    }

    function leaveReview(orderId) {
        // Open the review modal for the order
        showReviewModal(orderId);
    }


    function showReviewModal(orderId) {
        console.log('Opening review modal for order:', orderId);

        fetch(`ajax/reviews.php?action=get_order_items_for_review&order_id=${orderId}`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);

                if (data.success && data.items) {
                    console.log('Rendering modal with', data.items.length, 'items');

                    // Ensure items is an array
                    if (!Array.isArray(data.items)) {
                        console.error('Items is not an array:', data.items);
                        data.items = [];
                    }

                    renderReviewModal(orderId, data.order_number, data.items);
                    document.getElementById('reviewModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                } else {
                    console.error('API error:', data.message || 'No items data');
                    showNotification(data.message || 'Failed to load order items', 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showNotification('Error loading order items: ' + error.message, 'error');
            });
    }

    function closeReviewModal() {
        document.getElementById('reviewModal').classList.add('hidden');
    }


    function skipProductReview(productId) {
        const itemElement = document.getElementById(`review-item-${productId}`);
        if (itemElement) {
            itemElement.style.opacity = '0.5';
            itemElement.style.pointerEvents = 'none';
            const form = itemElement.querySelector('.review-form');
            if (form) {
                form.style.display = 'none';
            }
            itemElement.innerHTML += `
                <div class="text-center py-4 text-gray-500">
                    <p>Review skipped</p>
                </div>
            `;
        }
    }

    // Utility functions
    function formatDate(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        } catch (error) {
            return 'Invalid date';
        }
    }

    function formatCurrency(amount) {
        if (typeof amount !== 'number') {
            amount = parseFloat(amount) || 0;
        }
        return '₵' + amount.toFixed(2);
    }

    function getStatusText(status) {
        if (!status) return 'Processing';

        const statusMap = {
            'pending': 'Pending',
            'processing': 'Processing',
            'shipped': 'Shipped',
            'delivered': 'Delivered',
            'cancelled': 'Cancelled'
        };
        return statusMap[status.toLowerCase()] || status;
    }

    function getStatusClass(status) {
        if (!status) return 'bg-gray-100 text-gray-800';

        switch (status.toLowerCase()) {
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'processing':
                return 'bg-blue-100 text-blue-800';
            case 'shipped':
                return 'bg-purple-100 text-purple-800';
            case 'delivered':
                return 'bg-green-100 text-green-800';
            case 'cancelled':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    // Logout function
    function logout() {
        showConfirmationModal(
            'Sign Out',
            'Are you sure you want to sign out?',
            function() {
                fetch('ajax/auth.php?action=logout')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Signed out successfully', 'success');
                            setTimeout(() => {
                                window.location.href = 'index.php';
                            }, 1000);
                        } else {
                            window.location.href = 'index.php';
                        }
                    })
                    .catch(error => {
                        console.error('Error logging out:', error);
                        window.location.href = 'index.php';
                    });
            }
        );
    }

    function updateCartCount(count) {
        const cartCountElements = document.querySelectorAll('#cart-count');
        cartCountElements.forEach(element => {
            element.textContent = count;
        });
    }

    // Initial load
    document.addEventListener('DOMContentLoaded', function() {
        loadDashboardData();
    });
</script>

<style>
    /* Jumia-like styling */
    .tab-button.active {
        position: relative;
    }

    .tab-button.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #8b5cf6;
    }

    /* Smooth transitions */
    .transition-all {
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }

    /* Card hover effects */
    .shadow-sm {
        transition: box-shadow 0.2s ease-in-out;
    }

    /* Custom scrollbar for right content */
    .lg\:max-h-\[calc\(100vh-200px\)\]::-webkit-scrollbar {
        width: 6px;
    }

    .lg\:max-h-\[calc\(100vh-200px\)\]::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .lg\:max-h-\[calc\(100vh-200px\)\]::-webkit-scrollbar-thumb {
        background: #c7c7c7;
        border-radius: 3px;
    }

    .lg\:max-h-\[calc\(100vh-200px\)\]::-webkit-scrollbar-thumb:hover {
        background: #a5a5a5;
    }

    /* Hide scrollbar for non-webkit browsers */
    .lg\:max-h-\[calc\(100vh-200px\)\] {
        scrollbar-width: thin;
        scrollbar-color: #c7c7c7 #f1f1f1;
    }
</style>

<?php require_once 'includes/footer.php'; ?>