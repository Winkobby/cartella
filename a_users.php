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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['ajax_action']) {
            case 'bulk_action':
                $bulk_action = $_POST['bulk_action'] ?? '';
                $selected_users = $_POST['selected_users'] ?? [];

                if (empty($selected_users)) {
                    echo json_encode(['success' => false, 'message' => 'Please select users to perform bulk action.']);
                    exit;
                }

                if (empty($bulk_action)) {
                    echo json_encode(['success' => false, 'message' => 'Please select a bulk action.']);
                    exit;
                }

                $user_ids = array_map('intval', $selected_users);
                $placeholders = implode(',', array_fill(0, count($user_ids), '?'));

                switch ($bulk_action) {
                    case 'activate':
                        $query = "UPDATE users SET is_active = 1 WHERE user_id IN ($placeholders)";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute($user_ids);
                        $message = count($user_ids) . " user(s) activated successfully.";
                        break;

                    case 'deactivate':
                        $query = "UPDATE users SET is_active = 0 WHERE user_id IN ($placeholders)";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute($user_ids);
                        $message = count($user_ids) . " user(s) deactivated successfully.";
                        break;

                    case 'make_admin':
                        $query = "UPDATE users SET role = 'Admin' WHERE user_id IN ($placeholders)";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute($user_ids);
                        $message = count($user_ids) . " user(s) promoted to admin.";
                        break;

                    case 'make_customer':
                        $query = "UPDATE users SET role = 'Customer' WHERE user_id IN ($placeholders)";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute($user_ids);
                        $message = count($user_ids) . " user(s) set as customers.";
                        break;

                    default:
                        echo json_encode(['success' => false, 'message' => 'Invalid bulk action.']);
                        exit;
                }

                echo json_encode(['success' => true, 'message' => $message]);
                break;

            case 'single_action':
                $action = $_POST['action'] ?? '';
                $user_id = intval($_POST['user_id'] ?? 0);

                if (!$user_id) {
                    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
                    exit;
                }

                switch ($action) {
                    case 'toggle_active':
                        $query = "UPDATE users SET is_active = NOT is_active WHERE user_id = ?";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute([$user_id]);
                        $message = "User status updated successfully.";
                        break;

                    case 'toggle_role':
                        $query = "UPDATE users SET role = CASE WHEN role = 'Admin' THEN 'Customer' ELSE 'Admin' END WHERE user_id = ?";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute([$user_id]);
                        $message = "User role updated successfully.";
                        break;

                    default:
                        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
                        exit;
                }

                echo json_encode(['success' => true, 'message' => $message]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid AJAX action.']);
                break;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;

// Build query with filters
$query = "SELECT 
            u.*,
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as total_spent,
            MAX(o.order_date) as last_order_date
          FROM users u 
          LEFT JOIN orders o ON u.user_id = o.user_id 
          WHERE 1=1";

$params = [];

// Apply filters
if (!empty($search)) {
    $query .= " AND (u.email LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($role)) {
    $query .= " AND u.role = ?";
    $params[] = $role;
}

if (!empty($status)) {
    if ($status === 'active') {
        $query .= " AND u.is_active = 1";
    } elseif ($status === 'inactive') {
        $query .= " AND u.is_active = 0";
    }
}

// Group by user
$query .= " GROUP BY u.user_id";

// Apply sorting
switch ($sort) {
    case 'name_asc':
        $query .= " ORDER BY u.full_name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY u.full_name DESC";
        break;
    case 'email_asc':
        $query .= " ORDER BY u.email ASC";
        break;
    case 'email_desc':
        $query .= " ORDER BY u.email DESC";
        break;
    case 'orders_asc':
        $query .= " ORDER BY total_orders ASC";
        break;
    case 'orders_desc':
        $query .= " ORDER BY total_orders DESC";
        break;
    case 'spent_asc':
        $query .= " ORDER BY total_spent ASC";
        break;
    case 'spent_desc':
        $query .= " ORDER BY total_spent DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY u.date_joined ASC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY u.date_joined DESC";
        break;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM ($query) as filtered_users";
$count_stmt = $database->getConnection()->prepare($count_query);
$count_stmt->execute($params);
$total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pagination
$total_pages = ceil($total_users / $per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $per_page;

$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Execute main query
$stmt = $database->getConnection()->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$stats_query = "SELECT 
                 COUNT(*) as total_users,
                 COUNT(CASE WHEN role = 'Admin' THEN 1 END) as admin_users,
                 COUNT(CASE WHEN role = 'Customer' THEN 1 END) as customer_users,
                 COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_users,
                 COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_users,
                 COUNT(CASE WHEN DATE(date_joined) = CURDATE() THEN 1 END) as new_today
               FROM users";
$stats_stmt = $database->getConnection()->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'Manage Users';
$meta_description = 'Manage user accounts and permissions';
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-4 lg:py-8">
    <div class="container mx-auto px-3 lg:px-4 max-w-7xl">
        <!-- Header Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-6 lg:mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Manage Users</h1>
                    <p class="text-gray-600 mt-2 text-sm lg:text-base">View and manage user accounts and permissions</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <a href="a_user_add.php"
                        class="inline-flex items-center justify-center px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm lg:text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add New User
                    </a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div id="ajax-messages"></div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Users</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_users']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Users</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['active_users']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Admins</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['admin_users']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Customers</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['customer_users']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">New Today</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['new_today']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-6">
            <form method="GET" class="space-y-4" id="filter-form">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm lg:text-sm"
                               placeholder="Name, email, phone...">
                    </div>

                    <!-- Role Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm lg:text-sm">
                            <option value="">All Roles</option>
                            <option value="Admin" <?php echo $role === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="Customer" <?php echo $role === 'Customer' ? 'selected' : ''; ?>>Customer</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm lg:text-sm">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm lg:text-sm">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
                            <option value="email_asc" <?php echo $sort === 'email_asc' ? 'selected' : ''; ?>>Email A-Z</option>
                            <option value="email_desc" <?php echo $sort === 'email_desc' ? 'selected' : ''; ?>>Email Z-A</option>
                            <option value="orders_desc" <?php echo $sort === 'orders_desc' ? 'selected' : ''; ?>>Most Orders</option>
                            <option value="spent_desc" <?php echo $sort === 'spent_desc' ? 'selected' : ''; ?>>Highest Spenders</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="text-sm text-gray-600">
                        <span id="users-count"><?php echo $total_users; ?></span> user(s) found
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <button type="submit" 
                                class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm lg:text-sm w-full sm:w-auto flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Apply Filters
                        </button>
                        <a href="a_users.php" 
                           class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm lg:text-sm w-full sm:w-auto flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- View Toggle (Mobile only) -->
        <div class="md:hidden flex items-center justify-between mb-3">
            <div class="text-sm font-medium text-gray-700">View:</div>
            <div class="flex bg-gray-100 rounded-lg p-1">
                <button id="tableViewBtn" class="px-3 py-1.5 rounded-md text-sm font-medium bg-white shadow-sm text-gray-700">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    Table
                </button>
                <button id="cardViewBtn" class="px-3 py-1.5 rounded-md text-sm font-medium text-gray-500 hover:text-gray-700">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                    Cards
                </button>
            </div>
        </div>

        <!-- Users Container -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Bulk Actions -->
            <div id="bulk-action-form" class="hidden md:block">
                <div class="border-b border-gray-200 px-4 lg:px-6 py-3 lg:py-4 bg-gray-50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <select name="bulk_action" id="bulk_action" class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="">Bulk Actions</option>
                            <option value="activate">Activate Users</option>
                            <option value="deactivate">Deactivate Users</option>
                            <option value="make_admin">Make Admin</option>
                            <option value="make_customer">Make Customer</option>
                        </select>
                        <button type="button" onclick="confirmBulkAction()" 
                                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium text-sm w-full sm:w-auto">
                            Apply
                        </button>
                    </div>
                    <div class="text-sm text-gray-600 whitespace-nowrap">
                        Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                    </div>
                </div>
            </div>

            <!-- Mobile Bulk Actions -->
            <div id="mobile-bulk-actions" class="md:hidden p-3 bg-gray-50 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="mobile-select-all" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                    <label for="mobile-select-all" class="text-sm text-gray-700">Select All</label>
                    <select name="mobile_bulk_action" id="mobile_bulk_action" class="ml-auto px-2 py-1 border border-gray-300 rounded text-sm">
                        <option value="">Actions</option>
                        <option value="activate">Activate</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="make_admin">Make Admin</option>
                        <option value="make_customer">Make Customer</option>
                    </select>
                </div>
            </div>

            <!-- Desktop Table (hidden on mobile) -->
            <div id="desktop-table" class="overflow-x-auto hidden md:block">
                <table class="w-full min-w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Contact</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Orders</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Total Spent</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="users-table-body">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" class="px-4 lg:px-6 py-8 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No users found</p>
                                    <p class="text-sm mt-1">Try adjusting your search or filter criteria</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50 transition-colors" id="user-<?php echo $user['user_id']; ?>">
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="selected_users[]" value="<?php echo $user['user_id']; ?>" 
                                               class="user-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full overflow-hidden">
                                                <?php if (!empty($user['profile_image'])): ?>
                                                    <img class="h-10 w-10 object-cover" src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                                                <?php else: ?>
                                                    <div class="h-10 w-10 flex items-center justify-center bg-gray-100 text-gray-400 rounded-full">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                        </svg>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-3 lg:ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    ID: <?php echo $user['user_id']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden lg:table-cell">
                                        <div class="text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                        <?php if (!empty($user['phone'])): ?>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($user['phone']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden md:table-cell">
                                        <div class="font-medium"><?php echo $user['total_orders']; ?></div>
                                        <?php if ($user['last_order_date']): ?>
                                            <div class="text-xs text-gray-500">
                                                Last: <?php echo date('M j, Y', strtotime($user['last_order_date'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden xl:table-cell">
                                        <div class="font-medium">₵<?php echo number_format($user['total_spent'], 2); ?></div>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $user['role'] === 'Admin' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($user['date_joined'])); ?>
                                        <div class="text-xs text-gray-500">
                                            <?php echo date('g:i A', strtotime($user['date_joined'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <!-- Edit Button -->
                                            <a href="a_user_edit.php?id=<?php echo $user['user_id']; ?>"
                                                class="inline-flex items-center justify-center border border-blue-300 text-blue-600 p-2 rounded-xl hover:bg-blue-50 transition-all duration-300 hover:scale-105 group"
                                                title="Edit User">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>

                                            <!-- Toggle Active Button -->
                                            <button onclick="toggleActive(<?php echo $user['user_id']; ?>)"
                                                class="inline-flex items-center justify-center border border-green-300 text-green-600 p-2 rounded-xl hover:bg-green-50 transition-all duration-300 hover:scale-105 group"
                                                title="<?php echo $user['is_active'] ? 'Deactivate User' : 'Activate User'; ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>

                                            <!-- Toggle Role Button -->
                                            <button onclick="toggleRole(<?php echo $user['user_id']; ?>)"
                                                class="inline-flex items-center justify-center border border-yellow-300 text-yellow-600 p-2 rounded-xl hover:bg-yellow-50 transition-all duration-300 hover:scale-105 group"
                                                title="<?php echo $user['role'] === 'Admin' ? 'Make Customer' : 'Make Admin'; ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards (visible on mobile) -->
            <div id="mobile-cards" class="md:hidden">
                <?php if (empty($users)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <p class="text-lg font-medium">No users found</p>
                        <p class="text-sm mt-1">Try adjusting your search or filter criteria</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-3 p-3">
                        <?php foreach ($users as $user): ?>
                            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-sm transition-all duration-200 mobile-card" id="mobile-user-<?php echo $user['user_id']; ?>">
                                <!-- Top row: Checkbox and User Info -->
                                <div class="flex items-center justify-between mb-3">
                                    <!-- Checkbox -->
                                    <input type="checkbox" name="mobile_selected_users[]" value="<?php echo $user['user_id']; ?>"
                                        class="mobile-user-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                    
                                    <!-- User ID -->
                                    <div class="text-right">
                                        <div class="text-xs text-gray-500 hidden">ID: <?php echo $user['user_id']; ?></div>
                                    </div>
                                </div>
                                
                                <!-- User Profile -->
                                <div class="flex items-center gap-3 mb-4">
                                    <!-- Profile Image -->
                                    <div class="flex-shrink-0 h-14 w-14 bg-gray-200 rounded-full overflow-hidden">
                                        <?php if (!empty($user['profile_image'])): ?>
                                            <img class="h-14 w-14 object-cover" src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                                        <?php else: ?>
                                            <div class="h-14 w-14 flex items-center justify-center bg-gray-100 text-gray-400 rounded-full">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Name and Email -->
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-gray-900 truncate">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 truncate">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Status Badges -->
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium 
                                        <?php echo $user['role'] === 'Admin' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                    
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium 
                                        <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                
                                <!-- Contact Info -->
                                <div class="space-y-1 mb-3 text-sm">
                                    <?php if (!empty($user['phone'])): ?>
                                        <div class="flex items-center gap-2 text-gray-700">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                            <span><?php echo htmlspecialchars($user['phone']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center gap-2 text-gray-700">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span>Joined: <?php echo date('M j, Y', strtotime($user['date_joined'])); ?></span>
                                    </div>
                                </div>
                                
                                <!-- User Stats Grid -->
                                <div class="grid grid-cols-3 gap-3 mb-3 p-3 bg-gray-50 rounded-lg">
                                    <!-- Orders -->
                                    <div class="text-center">
                                        <div class="text-xs text-gray-500 mb-1">Orders</div>
                                        <div class="text-base font-bold text-gray-900">
                                            <?php echo $user['total_orders']; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Total Spent -->
                                    <div class="text-center">
                                        <div class="text-xs text-gray-500 mb-1">Spent</div>
                                        <div class="text-base font-bold text-gray-900">
                                            ₵<?php echo number_format($user['total_spent'], 0); ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Last Order -->
                                    <div class="text-center">
                                        <div class="text-xs text-gray-500 mb-1">Last Order</div>
                                        <div class="text-sm text-gray-700">
                                            <?php echo $user['last_order_date'] ? date('M j', strtotime($user['last_order_date'])) : 'None'; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                    <!-- Quick Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs text-gray-500">
                                            Member for <?php 
                                                $joinDate = new DateTime($user['date_joined']);
                                                $now = new DateTime();
                                                $interval = $joinDate->diff($now);
                                                echo $interval->y > 0 ? $interval->y . ' years' : ($interval->m > 0 ? $interval->m . ' months' : $interval->d . ' days');
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex gap-1 ml-2">
                                        <!-- Edit -->
                                        <a href="a_user_edit.php?id=<?php echo $user['user_id']; ?>"
                                            class="inline-flex items-center justify-center w-10 h-10 border border-blue-300 text-blue-600 rounded-lg hover:bg-blue-50 transition-all duration-200"
                                            title="Edit User">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        
                                        <!-- Toggle Active -->
                                        <button onclick="toggleActive(<?php echo $user['user_id']; ?>)"
                                            class="inline-flex items-center justify-center w-10 h-10 border border-green-300 text-green-600 rounded-lg hover:bg-green-50 transition-all duration-200"
                                            title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                        
                                        <!-- Toggle Role -->
                                        <button onclick="toggleRole(<?php echo $user['user_id']; ?>)"
                                            class="inline-flex items-center justify-center w-10 h-10 border border-yellow-300 text-yellow-600 rounded-lg hover:bg-yellow-50 transition-all duration-200"
                                            title="<?php echo $user['role'] === 'Admin' ? 'Make Customer' : 'Make Admin'; ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="border-t border-gray-200 px-4 lg:px-6 py-4 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-700 text-center sm:text-left">
                        Showing <?php echo (($current_page - 1) * $per_page) + 1; ?> to 
                        <?php echo min($current_page * $per_page, $total_users); ?> of 
                        <?php echo $total_users; ?> results
                    </div>
                    <div class="flex flex-wrap justify-center gap-2">
                        <?php if ($current_page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm">
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $current_page - 2 && $i <= $current_page + 2)): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="px-3 py-2 border rounded-lg transition-colors text-sm
                                          <?php echo $i == $current_page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif ($i == $current_page - 3 || $i == $current_page + 3): ?>
                                <span class="px-3 py-2 text-gray-500 text-sm">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Select all checkboxes
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        // Also update mobile checkboxes
        const mobileCheckboxes = document.querySelectorAll('.mobile-user-checkbox');
        mobileCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // AJAX Functions
    async function performAjaxAction(action, data) {
        try {
            const formData = new FormData();
            formData.append('ajax_action', action);

            for (const key in data) {
                if (Array.isArray(data[key])) {
                    data[key].forEach(value => {
                        formData.append(key + '[]', value);
                    });
                } else {
                    formData.append(key, data[key]);
                }
            }

            const response = await fetch('a_users.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showNotification(result.message, 'success');
                // Reload the page to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(result.message, 'error');
            }

            return result;
        } catch (error) {
            console.error('AJAX error:', error);
            showNotification('An error occurred. Please try again.', 'error');
            return {
                success: false,
                message: 'Network error'
            };
        }
    }

    // Bulk Actions
    function confirmBulkAction() {
        const selectedUsers = document.querySelectorAll('.user-checkbox:checked');
        const bulkAction = document.getElementById('bulk_action').value;

        if (selectedUsers.length === 0) {
            showNotification('Please select users to perform bulk action.', 'warning');
            return;
        }

        if (!bulkAction) {
            showNotification('Please select a bulk action.', 'warning');
            return;
        }

        const userIds = Array.from(selectedUsers).map(cb => cb.value);

        let message = `Are you sure you want to ${bulkAction.replace('_', ' ')} ${selectedUsers.length} user(s)?`;
        let type = 'warning';
        let confirmText = 'Confirm';

        if (bulkAction === 'deactivate') {
            message = `Are you sure you want to deactivate ${selectedUsers.length} user(s)? They will not be able to access their accounts.`;
        } else if (bulkAction === 'make_admin') {
            message = `Are you sure you want to make ${selectedUsers.length} user(s) administrators? They will have full access to the admin panel.`;
        }

        showConfirmationModal(
            'Confirm Bulk Action',
            message,
            () => {
                performAjaxAction('bulk_action', {
                    bulk_action: bulkAction,
                    selected_users: userIds
                });
            }, {
                type: type,
                confirmText: confirmText,
                cancelText: 'Cancel'
            }
        );
    }

    // Mobile Bulk Actions
    document.getElementById('mobile_bulk_action').addEventListener('change', function() {
        if (this.value) {
            confirmMobileBulkAction(this.value);
            this.value = ''; // Reset dropdown
        }
    });

    function confirmMobileBulkAction(action) {
        const selectedUsers = document.querySelectorAll('.mobile-user-checkbox:checked');
        
        if (selectedUsers.length === 0) {
            showNotification('Please select users to perform bulk action.', 'warning');
            return;
        }

        const userIds = Array.from(selectedUsers).map(cb => cb.value);

        let message = `Are you sure you want to ${action.replace('_', ' ')} ${selectedUsers.length} user(s)?`;
        let type = 'warning';
        let confirmText = 'Confirm';

        if (action === 'deactivate') {
            message = `Are you sure you want to deactivate ${selectedUsers.length} user(s)? They will not be able to access their accounts.`;
        } else if (action === 'make_admin') {
            message = `Are you sure you want to make ${selectedUsers.length} user(s) administrators? They will have full access to the admin panel.`;
        }

        showConfirmationModal(
            'Confirm Bulk Action',
            message,
            () => {
                performAjaxAction('bulk_action', {
                    bulk_action: action,
                    selected_users: userIds
                });
            }, {
                type: type,
                confirmText: confirmText,
                cancelText: 'Cancel'
            }
        );
    }

    // Single User Actions with Confirmation
    function toggleActive(userId) {
        showConfirmationModal(
            'Toggle User Status',
            'Are you sure you want to change this user\'s active status?',
            () => {
                performAjaxAction('single_action', {
                    action: 'toggle_active',
                    user_id: userId
                });
            }, {
                type: 'info',
                confirmText: 'Update',
                cancelText: 'Cancel'
            }
        );
    }

    function toggleRole(userId) {
        showConfirmationModal(
            'Toggle User Role',
            'Are you sure you want to change this user\'s role? This will affect their permissions.',
            () => {
                performAjaxAction('single_action', {
                    action: 'toggle_role',
                    user_id: userId
                });
            }, {
                type: 'warning',
                confirmText: 'Update',
                cancelText: 'Cancel'
            }
        );
    }

    // Quick search functionality
    let searchTimeout;
    document.querySelector('input[name="search"]').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filter-form').submit();
        }, 500);
    });

    // Show notification function
    function showNotification(message, type = 'info') {
        try {
            if (window.toast && typeof window.toast[type] === 'function') {
                window.toast[type](message);
                return;
            }

            if (window.toast && typeof window.toast.show === 'function') {
                window.toast.show(message, type);
                return;
            }
        } catch (e) {
            console.error('Toast error:', e);
        }

        // Fallback simple notification
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    // View toggle for mobile
    const tableViewBtn = document.getElementById('tableViewBtn');
    const cardViewBtn = document.getElementById('cardViewBtn');
    const desktopTable = document.getElementById('desktop-table');
    const mobileCards = document.getElementById('mobile-cards');

    // Initially show cards on mobile
    if (window.innerWidth < 768) {
        desktopTable.classList.add('hidden');
        mobileCards.classList.remove('hidden');
        tableViewBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-700');
        tableViewBtn.classList.add('text-gray-500');
        cardViewBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-700');
        cardViewBtn.classList.remove('text-gray-500');
    }

    tableViewBtn.addEventListener('click', function() {
        desktopTable.classList.remove('hidden');
        mobileCards.classList.add('hidden');
        tableViewBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-700');
        tableViewBtn.classList.remove('text-gray-500');
        cardViewBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-700');
        cardViewBtn.classList.add('text-gray-500');
        // Store preference
        localStorage.setItem('adminUsersViewPreference', 'table');
    });

    cardViewBtn.addEventListener('click', function() {
        desktopTable.classList.add('hidden');
        mobileCards.classList.remove('hidden');
        cardViewBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-700');
        cardViewBtn.classList.remove('text-gray-500');
        tableViewBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-700');
        tableViewBtn.classList.add('text-gray-500');
        // Store preference
        localStorage.setItem('adminUsersViewPreference', 'cards');
    });

    // Load saved preference
    document.addEventListener('DOMContentLoaded', function() {
        const savedView = localStorage.getItem('adminUsersViewPreference');
        if (window.innerWidth < 768 && savedView === 'table') {
            tableViewBtn.click();
        } else if (window.innerWidth < 768 && savedView === 'cards') {
            cardViewBtn.click();
        }
    });

    // Mobile select all
    document.getElementById('mobile-select-all').addEventListener('change', function() {
        const mobileCheckboxes = document.querySelectorAll('.mobile-user-checkbox');
        mobileCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        // Also update desktop checkboxes
        const desktopCheckboxes = document.querySelectorAll('.user-checkbox');
        desktopCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Sync checkbox states between mobile and desktop
    function syncCheckboxes() {
        const desktopCheckboxes = document.querySelectorAll('.user-checkbox');
        const mobileCheckboxes = document.querySelectorAll('.mobile-user-checkbox');
        
        // When desktop checkbox changes, update mobile
        desktopCheckboxes.forEach((checkbox, index) => {
            checkbox.addEventListener('change', function() {
                if (mobileCheckboxes[index]) {
                    mobileCheckboxes[index].checked = this.checked;
                }
            });
        });
        
        // When mobile checkbox changes, update desktop
        mobileCheckboxes.forEach((checkbox, index) => {
            checkbox.addEventListener('change', function() {
                if (desktopCheckboxes[index]) {
                    desktopCheckboxes[index].checked = this.checked;
                }
            });
        });
    }

    // Run checkbox sync after page loads
    document.addEventListener('DOMContentLoaded', syncCheckboxes);
</script>

<style>
    /* Mobile card styling */
    @media (max-width: 767px) {
        .mobile-card {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .mobile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Status badge colors */
        .bg-purple-100 { background-color: #e9d5ff; }
        .text-purple-800 { color: #5b21b6; }
        .bg-orange-100 { background-color: #ffedd5; }
        .text-orange-800 { color: #9a3412; }
        .bg-green-100 { background-color: #d1fae5; }
        .text-green-800 { color: #065f46; }
        .bg-red-100 { background-color: #fee2e2; }
        .text-red-800 { color: #991b1b; }
        
        /* Ensure text doesn't overflow */
        .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Better spacing for mobile */
        .mobile-card > * + * {
            margin-top: 12px;
        }
        
        /* Checkbox sizing for mobile */
        .mobile-user-checkbox {
            width: 20px !important;
            height: 20px !important;
        }
    }

    /* Touch-friendly buttons on mobile */
    @media (max-width: 767px) {
        button, 
        .mobile-user-checkbox {
            min-height: 44px;
            min-width: 44px;
        }
        
        .mobile-user-checkbox {
            width: 24px;
            height: 24px;
        }
        
        /* Action buttons */
        .mobile-card button[title] {
            position: relative;
        }
        
        .mobile-card button[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 10;
            margin-bottom: 5px;
        }
    }

    /* Desktop view */
    @media (min-width: 768px) {
        #desktop-table {
            display: block !important;
        }
        
        #mobile-cards {
            display: none !important;
        }
    }

    .animate-fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Custom scrollbar for table */
    .overflow-x-auto::-webkit-scrollbar {
        height: 6px;
    }

    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Mobile optimizations */
    @media (max-width: 640px) {
        .container {
            padding-left: 12px;
            padding-right: 12px;
        }
    }

    /* Loading state for buttons */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-right-color: transparent;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Fix checkbox size for mobile and desktop */
input[type="checkbox"] {
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    min-height: 18px !important;
}

/* Desktop checkbox styling */
.user-checkbox {
    width: 16px !important;
    height: 16px !important;
}

/* Mobile specific checkbox sizing */
@media (max-width: 767px) {
    input[type="checkbox"].mobile-user-checkbox {
        width: 20px !important;
        height: 20px !important;
        min-width: 20px !important;
        min-height: 20px !important;
    }
    
    /* Ensure checkbox doesn't affect layout */
    .mobile-user-checkbox {
        flex-shrink: 0;
        margin-right: 12px;
    }
    
    /* Make sure touch targets are still good */
    button, 
    input[type="checkbox"] {
        min-height: 44px;
        min-width: 44px;
    }
}

/* Mobile bulk actions checkbox */
#mobile-select-all {
    width: 20px !important;
    height: 20px !important;
}

/* Specific fix for the mobile cards checkbox */
.mobile-card input[type="checkbox"] {
    width: 20px !important;
    height: 20px !important;
    flex-shrink: 0;
}
</style>

<?php require_once 'includes/footer.php'; ?>