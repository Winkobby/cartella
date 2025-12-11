<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
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
            case 'create_coupon':
                $code = strtoupper(trim($_POST['code']));
                $description = trim($_POST['description'] ?? '');
                $discount_type = $_POST['discount_type'];
                $discount_value = floatval($_POST['discount_value']);
                $min_order_amount = floatval($_POST['min_order_amount'] ?? 0);
                $max_discount_amount = floatval($_POST['max_discount_amount'] ?? 0);
                $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : NULL;
                $user_usage_limit = intval($_POST['user_usage_limit'] ?? 1);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                // Validate coupon code
                $check_query = "SELECT coupon_id FROM coupons WHERE code = ?";
                $check_stmt = $database->getConnection()->prepare($check_query);
                $check_stmt->execute([$code]);

                if ($check_stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Coupon code already exists.']);
                    exit;
                }

                // Create coupon
                $insert_query = "INSERT INTO coupons (
                    code, description, discount_type, discount_value, min_order_amount, 
                    max_discount_amount, usage_limit, user_usage_limit, start_date, end_date, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $insert_stmt = $database->getConnection()->prepare($insert_query);
                $result = $insert_stmt->execute([
                    $code,
                    $description,
                    $discount_type,
                    $discount_value,
                    $min_order_amount,
                    $max_discount_amount,
                    $usage_limit,
                    $user_usage_limit,
                    $start_date,
                    $end_date,
                    $is_active
                ]);

                if ($result) {
                    $coupon_id = $database->getConnection()->lastInsertId();
                    
                    // Send notifications for new coupon/sale
                    try {
                        if (file_exists(__DIR__ . '/includes/NotificationEngine.php')) {
                            require_once __DIR__ . '/includes/NotificationEngine.php';
                            $pdo = $database->getConnection();
                            $notificationEngine = new NotificationEngine($pdo, $functions);
                            
                            // Prepare coupon data with ID
                            $coupon_notification_data = [
                                'coupon_id' => $coupon_id,
                                'code' => $code,
                                'description' => $description,
                                'discount_type' => $discount_type,
                                'discount_value' => $discount_value,
                                'min_order_amount' => $min_order_amount,
                                'end_date' => $end_date,
                                'is_active' => $is_active
                            ];
                            
                            // Send notification
                            $notify_result = $notificationEngine->notifyCouponCreated($coupon_notification_data);
                            error_log("Coupon notification sent: " . json_encode($notify_result));
                        }
                    } catch (Exception $e) {
                        error_log("Error sending coupon notification: " . $e->getMessage());
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Coupon created successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create coupon.']);
                }
                break;

            case 'update_coupon_status':
                $coupon_id = intval($_POST['coupon_id']);
                $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;

                $update_query = "UPDATE coupons SET is_active = ? WHERE coupon_id = ?";
                $update_stmt = $database->getConnection()->prepare($update_query);
                $result = $update_stmt->execute([$is_active, $coupon_id]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Coupon status updated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update coupon status.']);
                }
                break;

            case 'delete_coupon':
                $coupon_id = intval($_POST['coupon_id']);

                $delete_query = "DELETE FROM coupons WHERE coupon_id = ?";
                $delete_stmt = $database->getConnection()->prepare($delete_query);
                $result = $delete_stmt->execute([$coupon_id]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Coupon deleted successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete coupon.']);
                }
                break;

            case 'get_coupon_details':
                $coupon_id = intval($_POST['coupon_id']);

                if (!$coupon_id) {
                    echo json_encode(['success' => false, 'message' => 'Invalid coupon ID.']);
                    exit;
                }

                // Get coupon details
                $query = "SELECT * FROM coupons WHERE coupon_id = ?";
                $stmt = $database->getConnection()->prepare($query);
                $stmt->execute([$coupon_id]);
                $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$coupon) {
                    echo json_encode(['success' => false, 'message' => 'Coupon not found.']);
                    exit;
                }

                echo json_encode([
                    'success' => true,
                    'coupon' => $coupon
                ]);
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
$status = $_GET['status'] ?? '';
$discount_type = $_GET['discount_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;

// Build query with filters
$query = "SELECT 
            coupon_id, 
            code, 
            description,
            discount_type, 
            discount_value, 
            min_order_amount, 
            max_discount_amount,
            usage_limit, 
            used_count, 
            user_usage_limit,
            start_date, 
            end_date, 
            is_active,
            created_at
          FROM coupons 
          WHERE 1=1";

$params = [];

// Apply filters
if (!empty($search)) {
    $query .= " AND (code LIKE ? OR description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($status)) {
    if ($status === 'active') {
        $query .= " AND is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE() AND (usage_limit IS NULL OR used_count < usage_limit)";
    } elseif ($status === 'inactive') {
        $query .= " AND is_active = 0";
    } elseif ($status === 'expired') {
        $query .= " AND end_date < CURDATE()";
    } elseif ($status === 'upcoming') {
        $query .= " AND start_date > CURDATE()";
    } elseif ($status === 'limit_reached') {
        $query .= " AND usage_limit IS NOT NULL AND used_count >= usage_limit";
    }
}

if (!empty($discount_type)) {
    $query .= " AND discount_type = ?";
    $params[] = $discount_type;
}

if (!empty($date_from)) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
}

// Apply sorting
switch ($sort) {
    case 'code_asc':
        $query .= " ORDER BY code ASC";
        break;
    case 'code_desc':
        $query .= " ORDER BY code DESC";
        break;
    case 'usage_asc':
        $query .= " ORDER BY used_count ASC";
        break;
    case 'usage_desc':
        $query .= " ORDER BY used_count DESC";
        break;
    case 'date_asc':
        $query .= " ORDER BY created_at ASC";
        break;
    case 'date_desc':
        $query .= " ORDER BY created_at DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY created_at DESC";
        break;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM ($query) as filtered_coupons";
$count_stmt = $database->getConnection()->prepare($count_query);
$count_stmt->execute($params);
$total_coupons = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pagination
$total_pages = ceil($total_coupons / $per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $per_page;

$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Execute main query
$stmt = $database->getConnection()->prepare($query);
$stmt->execute($params);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get coupon statistics
$stats_query = "SELECT 
                 COUNT(*) as total_coupons,
                 COUNT(CASE WHEN is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE() AND (usage_limit IS NULL OR used_count < usage_limit) THEN 1 END) as active_coupons,
                 COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_coupons,
                 COUNT(CASE WHEN end_date < CURDATE() THEN 1 END) as expired_coupons,
                 COUNT(CASE WHEN start_date > CURDATE() THEN 1 END) as upcoming_coupons,
                 COUNT(CASE WHEN usage_limit IS NOT NULL AND used_count >= usage_limit THEN 1 END) as limit_reached_coupons,
                 SUM(used_count) as total_usage
               FROM coupons";
$stats_stmt = $database->getConnection()->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'Coupon Management';
$meta_description = 'Manage discount coupons and promotions';
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-4 lg:py-8">
    <div class="container mx-auto px-3 lg:px-4 max-w-7xl">
        <!-- Header Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-6 lg:mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Coupon Management</h1>
                    <p class="text-gray-600 mt-2 text-sm lg:text-base">Create and manage discount coupons for your store.</p>
                </div>
                <button onclick="openCreateModal()"
                    class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create New Coupon
                    </span>
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div id="ajax-messages"></div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Coupons</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_coupons']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['active_coupons']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Upcoming</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['upcoming_coupons']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Expired</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['expired_coupons']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Usage</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_usage']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-6">
            <form method="GET" class="space-y-4" id="filter-form">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                            placeholder="Coupon code, description...">
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="upcoming" <?php echo $status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="limit_reached" <?php echo $status === 'limit_reached' ? 'selected' : ''; ?>>Limit Reached</option>
                        </select>
                    </div>

                    <!-- Discount Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Discount Type</label>
                        <select name="discount_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                            <option value="">All Types</option>
                            <option value="percentage" <?php echo $discount_type === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                            <option value="fixed" <?php echo $discount_type === 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                            <option value="shipping" <?php echo $discount_type === 'shipping' ? 'selected' : ''; ?>>Free Shipping</option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="text-sm text-gray-600">
                        <span id="coupons-count"><?php echo $total_coupons; ?></span> coupon(s) found
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm w-full sm:w-auto flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Apply Filters
                        </button>
                        <a href="a_coupons.php"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm w-full sm:w-auto flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Coupons Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Table Header -->
            <div class="border-b border-gray-200 px-4 lg:px-6 py-4 bg-gray-50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="text-sm text-gray-600 whitespace-nowrap">
                    Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                </div>
            </div>

            <!-- Coupons Table -->
            <div class="overflow-x-auto">
                <table class="w-full min-w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coupon Code</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Min Order</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Usage</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validity</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="coupons-table-body">
                        <?php if (empty($coupons)): ?>
                            <tr>
                                <td colspan="8" class="px-4 lg:px-6 py-8 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No coupons found</p>
                                    <p class="text-sm mt-1">Try adjusting your search or filter criteria</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($coupons as $coupon): ?>
                                <tr class="hover:bg-gray-50 transition-colors" id="coupon-<?php echo $coupon['coupon_id']; ?>">
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono font-bold text-gray-700">
                                            <?php echo htmlspecialchars($coupon['code']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Created: <?php echo date('M j, Y', strtotime($coupon['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate">
                                            <?php echo htmlspecialchars($coupon['description'] ?? 'No description'); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php
                                            if ($coupon['discount_type'] === 'percentage') {
                                                echo $coupon['discount_value'] . '%';
                                                if ($coupon['max_discount_amount'] > 0) {
                                                    echo '<div class="text-xs text-gray-500">Max: ₵' . number_format($coupon['max_discount_amount'], 2) . '</div>';
                                                }
                                            } elseif ($coupon['discount_type'] === 'fixed') {
                                                echo '₵' . number_format($coupon['discount_value'], 2);
                                            } else {
                                                echo 'Free Shipping';
                                            }
                                            ?>
                                        </div>
                                        <div class="text-xs text-gray-500 capitalize">
                                            <?php echo $coupon['discount_type']; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden lg:table-cell">
                                        <?php echo $coupon['min_order_amount'] > 0 ? '₵' . number_format($coupon['min_order_amount'], 2) : 'None'; ?>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden lg:table-cell">
                                        <div class="font-medium">
                                            <?php echo $coupon['used_count'] . '/' . ($coupon['usage_limit'] ?: '∞'); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo $coupon['user_usage_limit'] . ' per user'; ?>
                                        </div>
                                        <?php if ($coupon['usage_limit'] > 0): ?>
                                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                                <div class="bg-blue-600 h-1.5 rounded-full"
                                                    style="width: <?php echo min(100, ($coupon['used_count'] / $coupon['usage_limit']) * 100); ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo date('M j, Y', strtotime($coupon['start_date'])); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            to <?php echo date('M j, Y', strtotime($coupon['end_date'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php
                                            $now = time();
                                            $start = strtotime($coupon['start_date']);
                                            $end = strtotime($coupon['end_date']);

                                            if (!$coupon['is_active']) {
                                                echo 'bg-gray-100 text-gray-800';
                                            } elseif ($now < $start) {
                                                echo 'bg-yellow-100 text-yellow-800';
                                            } elseif ($now > $end) {
                                                echo 'bg-red-100 text-red-800';
                                            } elseif ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
                                                echo 'bg-orange-100 text-orange-800';
                                            } else {
                                                echo 'bg-green-100 text-green-800';
                                            }
                                            ?>">
                                            <?php
                                            if (!$coupon['is_active']) {
                                                echo 'Inactive';
                                            } elseif ($now < $start) {
                                                echo 'Upcoming';
                                            } elseif ($now > $end) {
                                                echo 'Expired';
                                            } elseif ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
                                                echo 'Limit Reached';
                                            } else {
                                                echo 'Active';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <!-- View Details Button -->
                                            <button onclick="viewCouponDetails(<?php echo $coupon['coupon_id']; ?>)"
                                                class="inline-flex items-center justify-center border border-blue-200 bg-blue-100 text-blue-600 p-2 rounded-lg hover:bg-blue-50 transition-all duration-200 group"
                                                title="View Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </button>

                                            <!-- Activate/Deactivate Button -->
                                            <button onclick="toggleCouponStatus(<?php echo $coupon['coupon_id']; ?>, <?php echo $coupon['is_active'] == 1 ? 'true' : 'false'; ?>)"
                                                class="inline-flex items-center justify-center border <?php echo $coupon['is_active'] ? 'border-yellow-300 bg-yellow-100 text-yellow-600 hover:bg-yellow-50' : 'border-green-300 bg-green-100 text-green-600 hover:bg-green-50'; ?> p-2 rounded-lg transition-all duration-200 group"
                                                title="<?php echo $coupon['is_active'] ? 'Deactivate Coupon' : 'Activate Coupon'; ?>">
                                                <?php if ($coupon['is_active']): ?>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                <?php else: ?>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                                    </svg>
                                                <?php endif; ?>
                                            </button>

                                            <!-- Delete Button -->
                                            <button onclick="deleteCoupon(<?php echo $coupon['coupon_id']; ?>)"
                                                class="inline-flex items-center justify-center border border-red-300 bg-red-100 text-red-600 p-2 rounded-lg hover:bg-red-50 transition-all duration-200 group"
                                                title="Delete Coupon">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="border-t border-gray-200 px-4 lg:px-6 py-4 bg-gray-50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-700 text-center sm:text-left">
                            Showing <?php echo (($current_page - 1) * $per_page) + 1; ?> to
                            <?php echo min($current_page * $per_page, $total_coupons); ?> of
                            <?php echo $total_coupons; ?> results
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
</div>

<!-- Create Coupon Modal -->
<div id="createCouponModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeCreateModal()"></div>

        <!-- Modal panel -->
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Create New Coupon</h3>
                    <p class="text-sm text-gray-500 mt-1">Fill in the coupon details below</p>
                </div>
                <button type="button" onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form id="createCouponForm" onsubmit="submitCreateCoupon(event)" class="p-6 space-y-4">
                <!-- Coupon Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Coupon Code *</label>
                    <input type="text" id="code" name="code" required maxlength="50"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                        placeholder="SUMMER25">
                    <p class="text-xs text-gray-500 mt-1">Uppercase letters and numbers only</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                        placeholder="Summer sale discount"></textarea>
                </div>

                <!-- Discount Type & Value -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="discount_type" class="block text-sm font-medium text-gray-700 mb-1">Discount Type *</label>
                        <select id="discount_type" name="discount_type" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                            <option value="shipping">Free Shipping</option>
                        </select>
                    </div>
                    <div>
                        <label for="discount_value" class="block text-sm font-medium text-gray-700 mb-1">Discount Value *</label>
                        <input type="number" id="discount_value" name="discount_value" required min="0" step="0.01"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                            placeholder="10.00">
                    </div>
                </div>

                <!-- Order Limits -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="min_order_amount" class="block text-sm font-medium text-gray-700 mb-1">Minimum Order</label>
                        <input type="number" id="min_order_amount" name="min_order_amount" min="0" step="0.01"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                            placeholder="0.00">
                    </div>
                    <div>
                        <label for="max_discount_amount" class="block text-sm font-medium text-gray-700 mb-1">Max Discount</label>
                        <input type="number" id="max_discount_amount" name="max_discount_amount" min="0" step="0.01"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                            placeholder="No limit">
                    </div>
                </div>

                <!-- Usage Limits -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-1">Total Usage Limit</label>
                        <input type="number" id="usage_limit" name="usage_limit" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                            placeholder="No limit">
                    </div>
                    <div>
                        <label for="user_usage_limit" class="block text-sm font-medium text-gray-700 mb-1">Per User Limit</label>
                        <input type="number" id="user_usage_limit" name="user_usage_limit" min="1" value="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                    </div>
                </div>

                <!-- Date Range -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
                        <input type="date" id="end_date" name="end_date" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                    </div>
                </div>

                <!-- Active Status -->
                <div class="flex items-center">
                    <input type="checkbox" id="is_active" name="is_active" value="1" checked
                        class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">
                        Activate coupon immediately
                    </label>
                </div>

                <!-- Form Actions -->
                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeCreateModal()"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors text-sm font-medium">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors text-sm font-medium">
                        Create Coupon
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Coupon Details Modal -->
<div id="couponDetailsModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="details-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeCouponDetailsModal()"></div>

        <!-- Modal panel -->
        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Coupon Details</h3>
                    <p class="text-sm text-gray-500 mt-1">View and manage coupon information</p>
                </div>
                <button type="button" onclick="closeCouponDetailsModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div id="couponDetailsContent" class="p-6">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let currentCouponId = null;

// Modal Functions
function openCreateModal() {
    const modal = document.getElementById('createCouponModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Set date defaults
    const today = new Date().toISOString().split('T')[0];
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    const nextMonthStr = nextMonth.toISOString().split('T')[0];
    
    document.getElementById('start_date').value = today;
    document.getElementById('end_date').value = nextMonthStr;
}

function closeCreateModal() {
    const modal = document.getElementById('createCouponModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    document.getElementById('createCouponForm').reset();
}

async function viewCouponDetails(couponId) {
    try {
        const response = await fetch('a_coupons.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ajax_action=get_coupon_details&coupon_id=${couponId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            currentCouponId = couponId;
            const coupon = result.coupon;
            const content = document.getElementById('couponDetailsContent');
            
            // Format dates
            const formatDate = (dateString) => {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            };
            
            // Determine status
            const now = new Date();
            const startDate = new Date(coupon.start_date);
            const endDate = new Date(coupon.end_date);
            
            let status = 'Inactive';
            let statusClass = 'bg-gray-100 text-gray-800';
            
            if (coupon.is_active) {
                if (now < startDate) {
                    status = 'Upcoming';
                    statusClass = 'bg-yellow-100 text-yellow-800';
                } else if (now > endDate) {
                    status = 'Expired';
                    statusClass = 'bg-red-100 text-red-800';
                } else if (coupon.usage_limit > 0 && coupon.used_count >= coupon.usage_limit) {
                    status = 'Limit Reached';
                    statusClass = 'bg-orange-100 text-orange-800';
                } else {
                    status = 'Active';
                    statusClass = 'bg-green-100 text-green-800';
                }
            }
            
            // Calculate days remaining
            const daysRemaining = Math.max(0, Math.ceil((endDate - now) / (1000 * 60 * 60 * 24)));
            
            content.innerHTML = `
                <div class="space-y-6">
                    <!-- Coupon Header -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <div class="text-sm font-medium text-gray-500">Coupon Code</div>
                                <div class="text-2xl font-bold text-gray-900 font-mono mt-1">${coupon.code}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                                    ${status}
                                </span>
                            </div>
                        </div>
                        ${coupon.description ? `
                            <div class="mt-3">
                                <p class="text-sm text-gray-700">${coupon.description}</p>
                            </div>
                        ` : ''}
                    </div>
                    
                    <!-- Information Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Discount Information -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 uppercase tracking-wider mb-3">Discount Information</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Type:</span>
                                        <span class="text-sm font-medium capitalize">${coupon.discount_type}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Value:</span>
                                        <span class="text-sm font-medium">
                                            ${coupon.discount_type === 'percentage' ? coupon.discount_value + '%' : 
                                              coupon.discount_type === 'fixed' ? '₵' + parseFloat(coupon.discount_value).toFixed(2) : 
                                              'Free Shipping'}
                                        </span>
                                    </div>
                                    ${coupon.max_discount_amount > 0 ? `
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Max Discount:</span>
                                            <span class="text-sm font-medium">₵${parseFloat(coupon.max_discount_amount).toFixed(2)}</span>
                                        </div>
                                    ` : ''}
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Min Order:</span>
                                        <span class="text-sm font-medium">
                                            ${coupon.min_order_amount > 0 ? '₵' + parseFloat(coupon.min_order_amount).toFixed(2) : 'None'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Validity Period -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 uppercase tracking-wider mb-3">Validity Period</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Start Date:</span>
                                        <span class="text-sm font-medium">${formatDate(coupon.start_date)}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">End Date:</span>
                                        <span class="text-sm font-medium">${formatDate(coupon.end_date)}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Days Remaining:</span>
                                        <span class="text-sm font-medium">${daysRemaining} days</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Usage Information -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 uppercase tracking-wider mb-3">Usage Information</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Total Usage:</span>
                                        <span class="text-sm font-medium">${coupon.used_count} / ${coupon.usage_limit || '∞'}</span>
                                    </div>
                                    ${coupon.usage_limit > 0 ? `
                                        <div class="space-y-1">
                                            <div class="flex justify-between text-xs text-gray-500">
                                                <span>Usage Progress</span>
                                                <span>${Math.round((coupon.used_count / coupon.usage_limit) * 100)}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" 
                                                     style="width: ${Math.min(100, (coupon.used_count / coupon.usage_limit) * 100)}%"></div>
                                            </div>
                                        </div>
                                    ` : ''}
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Per User Limit:</span>
                                        <span class="text-sm font-medium">${coupon.user_usage_limit} time(s)</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional Info -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 uppercase tracking-wider mb-3">Additional Information</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Created On:</span>
                                        <span class="text-sm font-medium">${formatDate(coupon.created_at)}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Active:</span>
                                        <span class="text-sm font-medium">
                                            ${coupon.is_active ? 
                                                '<span class="inline-flex items-center text-green-600"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Yes</span>' : 
                                                '<span class="inline-flex items-center text-red-600"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg> No</span>'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="pt-6 border-t border-gray-200">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button onclick="toggleCouponStatus(${coupon.coupon_id}, ${coupon.is_active})"
                                class="flex-1 px-4 py-2 ${coupon.is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700'} text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors text-sm font-medium">
                                ${coupon.is_active ? 'Deactivate Coupon' : 'Activate Coupon'}
                            </button>
                            <button onclick="deleteCoupon(${coupon.coupon_id})"
                                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors text-sm font-medium">
                                Delete Coupon
                            </button>
                            <button onclick="closeCouponDetailsModal()"
                                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors text-sm font-medium">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Show the modal
            const modal = document.getElementById('couponDetailsModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error loading coupon details:', error);
        showNotification('Error loading coupon details', 'error');
    }
}

function closeCouponDetailsModal() {
    const modal = document.getElementById('couponDetailsModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    currentCouponId = null;
}

// AJAX Functions
async function performAjaxAction(action, data) {
    try {
        const formData = new FormData();
        formData.append('ajax_action', action);
        
        for (const key in data) {
            formData.append(key, data[key]);
        }
        
        const response = await fetch('a_coupons.php', {
            method: 'POST',
            body: formData
        });
        
        return await response.json();
    } catch (error) {
        console.error('AJAX error:', error);
        return { success: false, message: 'Network error' };
    }
}

// Coupon Actions
async function toggleCouponStatus(couponId, currentStatus) {
    if (!confirm(`Are you sure you want to ${currentStatus ? 'deactivate' : 'activate'} this coupon?`)) {
        return;
    }
    
    const result = await performAjaxAction('update_coupon_status', {
        coupon_id: couponId,
        is_active: currentStatus ? 0 : 1
    });
    
    if (result.success) {
        showNotification(result.message, 'success');
        setTimeout(() => window.location.reload(), 1000);
    } else {
        showNotification(result.message, 'error');
    }
}

async function deleteCoupon(couponId) {
    if (!confirm('Are you sure you want to delete this coupon? This action cannot be undone.')) {
        return;
    }
    
    const result = await performAjaxAction('delete_coupon', {
        coupon_id: couponId
    });
    
    if (result.success) {
        showNotification(result.message, 'success');
        setTimeout(() => window.location.reload(), 1000);
    } else {
        showNotification(result.message, 'error');
    }
}

async function submitCreateCoupon(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    // Validate dates
    const startDate = new Date(data.start_date);
    const endDate = new Date(data.end_date);
    
    if (endDate <= startDate) {
        showNotification('End date must be after start date', 'error');
        return;
    }
    
    // Format data
    if (!data.usage_limit) data.usage_limit = null;
    if (!data.min_order_amount) data.min_order_amount = 0;
    if (!data.max_discount_amount) data.max_discount_amount = 0;
    data.is_active = data.is_active ? 1 : 0;
    
    const result = await performAjaxAction('create_coupon', data);
    
    if (result.success) {
        showNotification(result.message, 'success');
        closeCreateModal();
        setTimeout(() => window.location.reload(), 1000);
    } else {
        showNotification(result.message, 'error');
    }
}

// Utility Functions
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' :
                  type === 'error' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>' :
                  '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'}
            </svg>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => notification.remove(), 3000);
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Auto-format coupon code
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    }
    
    // Validate end date
    const endDateInput = document.getElementById('end_date');
    if (endDateInput) {
        endDateInput.addEventListener('change', function() {
            const startDate = document.getElementById('start_date').value;
            if (startDate && this.value <= startDate) {
                showNotification('End date must be after start date', 'warning');
                this.value = '';
            }
        });
    }
    
    // Close modals on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateModal();
            closeCouponDetailsModal();
        }
    });
    
    // Quick search
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('filter-form').submit();
            }, 500);
        });
    }
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    const createModal = document.getElementById('createCouponModal');
    const detailsModal = document.getElementById('couponDetailsModal');
    
    if (createModal && !createModal.classList.contains('hidden')) {
        const modalContent = createModal.querySelector('.relative');
        if (!modalContent.contains(e.target)) {
            closeCreateModal();
        }
    }
    
    if (detailsModal && !detailsModal.classList.contains('hidden')) {
        const modalContent = detailsModal.querySelector('.relative');
        if (!modalContent.contains(e.target)) {
            closeCouponDetailsModal();
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>