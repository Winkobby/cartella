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
                $selected_products = $_POST['selected_products'] ?? [];

                if (empty($selected_products)) {
                    echo json_encode(['success' => false, 'message' => 'Please select products to perform bulk action.']);
                    exit;
                }

                if (empty($bulk_action)) {
                    echo json_encode(['success' => false, 'message' => 'Please select a bulk action.']);
                    exit;
                }

                $product_ids = array_map('intval', $selected_products);
                $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

                // Initialize notification engine for product updates
                $pdo = $database->getConnection();
                $notificationEngine = null;
                if (file_exists(__DIR__ . '/includes/NotificationEngine.php')) {
                    require_once __DIR__ . '/includes/NotificationEngine.php';
                    $notificationEngine = new NotificationEngine($pdo, $functions);
                }

                switch ($bulk_action) {
                    case 'delete':
                        $query = "DELETE FROM products WHERE product_id IN ($placeholders)";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute($product_ids);
                        $message = count($product_ids) . " product(s) deleted successfully.";
                        break;

                    case 'activate':
                        $query = "UPDATE products SET is_new = 1 WHERE product_id IN ($placeholders)";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute($product_ids);

                        // Send notifications for newly activated products
                        if ($notificationEngine) {
                            foreach ($product_ids as $pid) {
                                $product = $functions->getProductById($pid);
                                if ($product) {
                                    $notification_data = [
                                        'product_id' => $pid,
                                        'product_name' => $product['name'],
                                        'product_price' => $product['price'],
                                        'main_image' => $product['main_image']
                                    ];
                                    $notificationEngine->notifyNewProduct($notification_data);
                                }
                            }
                        }

                        $message = count($product_ids) . " product(s) marked as new arrivals.";
                        break;

                    case 'deactivate':
                        $query = "UPDATE products SET is_new = 0 WHERE product_id IN ($placeholders)";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute($product_ids);
                        $message = count($product_ids) . " product(s) removed from new arrivals.";
                        break;

                    case 'featured':
                        $query = "UPDATE products SET is_featured = 1 WHERE product_id IN ($placeholders)";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute($product_ids);
                        
                        // Send notifications for featured products
                        if ($notificationEngine) {
                            foreach ($product_ids as $pid) {
                                $product = $functions->getProductById($pid);
                                if ($product) {
                                    $notification_data = [
                                        'product_id' => $pid,
                                        'product_name' => $product['name'],
                                        'product_price' => $product['price'],
                                        'main_image' => $product['main_image'],
                                        'is_new' => 0,
                                        'is_featured' => 1
                                    ];
                                    $notificationEngine->notifyNewProduct($notification_data);
                                }
                            }
                        }
                        
                        $message = count($product_ids) . " product(s) marked as featured.";
                        break;

                    case 'unfeatured':
                        $query = "UPDATE products SET is_featured = 0 WHERE product_id IN ($placeholders)";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute($product_ids);
                        $message = count($product_ids) . " product(s) removed from featured.";
                        break;

                    default:
                        echo json_encode(['success' => false, 'message' => 'Invalid bulk action.']);
                        exit;
                }

                echo json_encode(['success' => true, 'message' => $message]);
                exit;

            case 'single_action':
                $action = $_POST['action'] ?? '';
                $product_id = intval($_POST['product_id'] ?? 0);

                if (!$product_id) {
                    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
                    exit;
                }

                switch ($action) {
                    case 'delete':
                        $query = "DELETE FROM products WHERE product_id = ?";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute([$product_id]);
                        $message = "Product deleted successfully.";
                        break;

                    case 'toggle_new':
                        $query = "UPDATE products SET is_new = NOT is_new WHERE product_id = ?";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute([$product_id]);
                        
                        // Send notification if product is marked as new
                        $product = $functions->getProductById($product_id);
                        if ($product && $product['is_new']) {
                            if ($notificationEngine) {
                                $notification_data = [
                                    'product_id' => $product_id,
                                    'product_name' => $product['name'],
                                    'product_price' => $product['price'],
                                    'main_image' => $product['main_image'],
                                    'is_new' => 1,
                                    'is_featured' => 0
                                ];
                                $notificationEngine->notifyNewProduct($notification_data);
                            }
                        }
                        
                        $message = "Product new arrival status updated successfully.";
                        break;

                    case 'toggle_featured':
                        $query = "UPDATE products SET is_featured = NOT is_featured WHERE product_id = ?";
                        $stmt = $database->getConnection()->prepare($query);
                        $stmt->execute([$product_id]);
                        
                        // Send notification if product is marked as featured
                        $product = $functions->getProductById($product_id);
                        if ($product && $product['is_featured']) {
                            if ($notificationEngine) {
                                $notification_data = [
                                    'product_id' => $product_id,
                                    'product_name' => $product['name'],
                                    'product_price' => $product['price'],
                                    'main_image' => $product['main_image'],
                                    'is_new' => 0,
                                    'is_featured' => 1
                                ];
                                $notificationEngine->notifyNewProduct($notification_data);
                            }
                        }
                        
                        $message = "Product featured status updated successfully.";
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
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$featured = $_GET['featured'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;

// Build query with filters
$query = "SELECT 
            p.*, 
            c.category_name,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.product_id) as total_sold
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE 1=1";

$params = [];

// Apply filters
if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.brand LIKE ? OR p.sku LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($category)) {
    $query .= " AND p.category_id = ?";
    $params[] = $category;
}

// Apply sorting
switch ($sort) {
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'stock_asc':
        $query .= " ORDER BY p.stock_quantity ASC";
        break;
    case 'stock_desc':
        $query .= " ORDER BY p.stock_quantity DESC";
        break;
    case 'best_selling':
        $query .= " ORDER BY total_sold DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY p.date_added DESC";
        break;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM ($query) as filtered_products";
$count_stmt = $database->getConnection()->prepare($count_query);
$count_stmt->execute($params);
$total_products = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pagination
$total_pages = ceil($total_products / $per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $per_page;

$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Execute main query
$stmt = $database->getConnection()->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter dropdown
$categories = $functions->getAllCategories();

// Additional statistics for admin cards
$stats = [];
try {
    $totProdQ = "SELECT COUNT(*) as total_products FROM products";
    $totProdStmt = $database->getConnection()->prepare($totProdQ);
    $totProdStmt->execute();
    $totProdRes = $totProdStmt->fetch(PDO::FETCH_ASSOC);

    $totCatQ = "SELECT COUNT(*) as total_categories FROM categories";
    $totCatStmt = $database->getConnection()->prepare($totCatQ);
    $totCatStmt->execute();
    $totCatRes = $totCatStmt->fetch(PDO::FETCH_ASSOC);

    $inStockQ = "SELECT COALESCE(SUM(stock_quantity),0) as total_in_stock FROM products";
    $inStockStmt = $database->getConnection()->prepare($inStockQ);
    $inStockStmt->execute();
    $inStockRes = $inStockStmt->fetch(PDO::FETCH_ASSOC);

    $lowStockQ = "SELECT COUNT(*) as low_stock_count FROM products WHERE stock_quantity > 0 AND stock_quantity < 10";
    $lowStockStmt = $database->getConnection()->prepare($lowStockQ);
    $lowStockStmt->execute();
    $lowStockRes = $lowStockStmt->fetch(PDO::FETCH_ASSOC);

    $featuredQ = "SELECT COUNT(*) as featured_products FROM products WHERE is_featured = 1";
    $featuredStmt = $database->getConnection()->prepare($featuredQ);
    $featuredStmt->execute();
    $featuredRes = $featuredStmt->fetch(PDO::FETCH_ASSOC);

    $stats = array_merge([
        'total_products' => 0,
        'total_categories' => 0,
        'total_in_stock' => 0,
        'low_stock_count' => 0,
        'featured_products' => 0
    ], $totProdRes ?: [], $totCatRes ?: [], $inStockRes ?: [], $lowStockRes ?: [], $featuredRes ?: []);
} catch (PDOException $e) {
    error_log('Stats error: ' . $e->getMessage());
}

$page_title = 'Manage Products';
$meta_description = 'Manage your product inventory';
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-4 lg:py-8">
    <div class="container mx-auto px-3 lg:px-4 max-w-7xl">
        <!-- Header Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-3 lg:mb-3">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Manage Products</h1>
                    <p class="text-gray-600 mt-2 text-sm lg:text-base">View, edit, and manage your product inventory</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <a href="a_pro.php"
                        class="inline-flex items-center justify-center px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-900 transition-colors font-semibold text-sm lg:text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add New Product
                    </a>
                    <a href="a_categories.php"
                        class="inline-flex items-center justify-center px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm lg:text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Manage Categories
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-2 mt-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Products</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo (int)($stats['total_products'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Categories</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo (int)($stats['total_categories'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M6 3v4M18 3v4M4 21h16" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">In Stock</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo (int)($stats['total_in_stock'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Low Stock (&lt;10)</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo (int)($stats['low_stock_count'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div id="ajax-messages"></div>

        <!-- Filters and Search Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-3">
            <form method="GET" class="space-y-4" id="filter-form">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm lg:text-sm"
                            placeholder="Search products...">
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm lg:text-sm">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm lg:text-sm">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
                            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price Low to High</option>
                            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price High to Low</option>
                            <option value="stock_asc" <?php echo $sort === 'stock_asc' ? 'selected' : ''; ?>>Stock Low to High</option>
                            <option value="stock_desc" <?php echo $sort === 'stock_desc' ? 'selected' : ''; ?>>Stock High to Low</option>
                            <option value="best_selling" <?php echo $sort === 'best_selling' ? 'selected' : ''; ?>>Best Selling</option>
                        </select>
                    </div>

                    <!-- Items per page -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Items per page</label>
                        <select name="per_page" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm lg:text-sm">
                            <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="text-sm text-gray-600">
                        <span id="products-count"><?php echo $total_products; ?></span> product(s) found
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <button type="submit"
                            class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm lg:text-sm w-full sm:w-auto flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Apply Filters
                        </button>
                        <a href="a_products.php"
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

        <!-- Products Container -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Bulk Actions -->
            <div id="bulk-action-form" class="hidden md:block">
                <div class="border-b border-gray-200 px-4 lg:px-6 py-3 lg:py-4 bg-gray-50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <select name="bulk_action" id="bulk_action" class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="">Bulk Actions</option>
                            <option value="activate">Mark as New Arrival</option>
                            <option value="deactivate">Remove from New Arrivals</option>
                            <option value="featured">Mark as Featured</option>
                            <option value="unfeatured">Remove from Featured</option>
                            <option value="delete">Delete</option>
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
                    <input type="checkbox" id="mobile-select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="mobile-select-all" class="text-sm text-gray-700">Select All</label>
                    <select name="mobile_bulk_action" id="mobile_bulk_action" class="ml-auto px-2 py-1 border border-gray-300 rounded text-sm">
                        <option value="">Actions</option>
                        <option value="activate">Mark New</option>
                        <option value="deactivate">Unmark New</option>
                        <option value="featured">Mark Featured</option>
                        <option value="unfeatured">Unmark Featured</option>
                        <option value="delete">Delete</option>
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
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Featured</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="products-table-body">
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="px-4 lg:px-6 py-8 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No products found</p>
                                    <p class="text-sm mt-1">Try adjusting your search or filter criteria</p>
                                    <a href="a_pro.php" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                        Add Your First Product
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product):
                                $productName = htmlspecialchars(
                                    html_entity_decode((string)($product['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                                    ENT_QUOTES | ENT_HTML5,
                                    'UTF-8'
                                );
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors" id="product-<?php echo $product['product_id']; ?>">
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="selected_products[]" value="<?php echo $product['product_id']; ?>"
                                            class="product-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-lg overflow-hidden">
                                                <?php if (!empty($product['main_image'])): ?>
                                                    <img class="h-10 w-10 object-cover" src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo $productName; ?>">
                                                <?php else: ?>
                                                    <div class="h-10 w-10 flex items-center justify-center bg-gray-100 text-gray-400">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-3 lg:ml-4">
                                                <div class="text-sm font-medium text-gray-900 max-w-xs truncate">
                                                    <?php echo $productName; ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    SKU: <?php echo !empty($product['sku']) ? htmlspecialchars($product['sku']) : 'N/A'; ?>
                                                    <?php if (!empty($product['brand'])): ?>
                                                        • <?php echo htmlspecialchars($product['brand']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($product['total_sold'] > 0): ?>
                                                    <div class="text-xs text-gray-400">
                                                        Sold: <?php echo $product['total_sold']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <div class="font-medium">₵<?php echo number_format($product['price'], 2); ?></div>
                                        <?php if ($product['discount'] > 0): ?>
                                            <div class="text-xs text-red-600">
                                                -<?php echo $product['discount']; ?>%
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-700 font-medium">
                                            <?php echo $product['stock_quantity']; ?>
                                        </div>
                                        <?php if ($product['stock_quantity'] < 10): ?>
                                            <div class="text-xs text-red-600 font-medium">Low Stock</div>
                                        <?php elseif ($product['stock_quantity'] == 0): ?>
                                            <div class="text-xs text-red-600 font-medium">Out of Stock</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $product['is_new'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $product['is_new'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($product['is_featured']): ?>
                                            <svg class="w-5 h-5 text-yellow-500 fill-current" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-5 h-5 text-gray-300 fill-current" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <!-- Edit Button -->
                                            <a href="a_pro.php?id=<?php echo $product['product_id']; ?>"
                                                class="inline-flex items-center justify-center border border-blue-300 text-blue-600 p-2 rounded-xl hover:bg-blue-50 transition-all duration-300 hover:scale-105 group"
                                                title="Edit Product">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>

                                            <!-- Toggle New Arrival Button -->
                                            <button onclick="toggleNew(<?php echo $product['product_id']; ?>)"
                                                class="inline-flex items-center justify-center border border-green-300 text-green-600 p-2 rounded-xl hover:bg-green-50 transition-all duration-300 hover:scale-105 group"
                                                title="Toggle New Arrival">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>

                                            <!-- Toggle Featured Button -->
                                            <button onclick="toggleFeatured(<?php echo $product['product_id']; ?>)"
                                                class="inline-flex items-center justify-center border border-yellow-300 text-yellow-600 p-2 rounded-xl hover:bg-yellow-50 transition-all duration-300 hover:scale-105 group"
                                                title="Toggle Featured">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            </button>

                                            <!-- Delete Button -->
                                            <button onclick="confirmDelete(<?php echo $product['product_id']; ?>)"
                                                class="inline-flex items-center justify-center border border-red-300 text-red-600 p-2 rounded-xl hover:bg-red-50 transition-all duration-300 hover:scale-105 group"
                                                title="Delete Product">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 011-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
            <!-- Mobile Cards (visible on mobile) -->
            <div id="mobile-cards" class="md:hidden">
                <?php if (empty($products)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="text-lg font-medium">No products found</p>
                        <p class="text-sm mt-1">Try adjusting your search or filter criteria</p>
                        <a href="a_pro.php" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                            Add Your First Product
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-3 p-3">
                        <?php foreach ($products as $product):
                            $productName = htmlspecialchars(
                                html_entity_decode((string)($product['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                                ENT_QUOTES | ENT_HTML5,
                                'UTF-8'
                            );
                            // Truncate name if too long
                            $displayName = strlen($productName) > 50 ? substr($productName, 0, 50) . '...' : $productName;
                        ?>
                            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-sm transition-all duration-200 mobile-card" id="mobile-product-<?php echo $product['product_id']; ?>">
                                <!-- Top row: Checkbox and Status Badges (ALWAYS VISIBLE) -->
                                <div class="flex items-center justify-between mb-3">
                                    <!-- Checkbox -->
                                    <input type="checkbox" name="mobile_selected_products[]" value="<?php echo $product['product_id']; ?>"
                                        class="mobile-product-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">

                                    <!-- Status Badges - NOW ON TOP ROW, ALWAYS VISIBLE -->
                                    <div class="flex gap-2">
                                        <?php if ($product['is_new']): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 whitespace-nowrap">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                                New
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($product['is_featured']): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800 whitespace-nowrap">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                                Featured
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Second row: Image and Product Info -->
                                <div class="flex items-start gap-3 mb-3">
                                    <!-- Product Image -->
                                    <div class="flex-shrink-0">
                                        <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden">
                                            <?php if (!empty($product['main_image'])): ?>
                                                <img class="w-20 h-20 object-cover" src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo $productName; ?>">
                                            <?php else: ?>
                                                <div class="w-20 h-20 flex items-center justify-center bg-gray-100 text-gray-400">
                                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Product Info - FULL WIDTH for name -->
                                    <div class="flex-1 min-w-0">
                                        <!-- Product Name - FULL WIDTH, NO CONSTRAINT -->
                                        <h3 class="text-sm font-semibold text-gray-900 leading-tight mb-2 break-words"><?php echo $displayName; ?></h3>

                                        <!-- Category, SKU, Brand - vertical layout -->
                                        <div class="space-y-1">
                                            <div class="text-xs text-gray-500">
                                                <span class="font-medium">Category:</span> <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                            </div>

                                            <?php if (!empty($product['sku'])): ?>
                                                <div class="text-xs text-gray-500">
                                                    <span class="font-medium">SKU:</span> <?php echo htmlspecialchars($product['sku']); ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($product['brand'])): ?>
                                                <div class="text-xs text-gray-500">
                                                    <span class="font-medium">Brand:</span> <?php echo htmlspecialchars($product['brand']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Third row: Price and Stock Info -->
                                <div class="grid grid-cols-2 gap-3 mb-3 p-3 bg-gray-50 rounded-lg">
                                    <!-- Price -->
                                    <div>
                                        <div class="text-xs text-gray-500 mb-1">Price</div>
                                        <div class="flex items-baseline flex-wrap">
                                            <span class="text-base font-bold text-gray-900">₵<?php echo number_format($product['price'], 2); ?></span>
                                            <?php if ($product['discount'] > 0): ?>
                                                <span class="text-xs text-red-600 ml-2 bg-red-50 px-1.5 py-0.5 rounded whitespace-nowrap">-<?php echo $product['discount']; ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Stock -->
                                    <div>
                                        <div class="text-xs text-gray-500 mb-1">Stock</div>
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="text-sm font-semibold <?php
                                                                                    if ($product['stock_quantity'] == 0) echo 'text-red-600';
                                                                                    elseif ($product['stock_quantity'] < 10) echo 'text-orange-600';
                                                                                    else echo 'text-green-600';
                                                                                    ?>">
                                                    <?php echo $product['stock_quantity']; ?>
                                                </span>
                                                <span class="text-xs text-gray-500 ml-1">units</span>
                                            </div>
                                            <!-- Stock status indicator -->
                                            <?php if ($product['stock_quantity'] == 0): ?>
                                                <span class="text-xs text-red-600 bg-red-50 px-2 py-0.5 rounded-full whitespace-nowrap">Out</span>
                                            <?php elseif ($product['stock_quantity'] < 10): ?>
                                                <span class="text-xs text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full whitespace-nowrap">Low</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Sales info if available -->
                                    <?php if ($product['total_sold'] > 0): ?>
                                        <div class="col-span-2 pt-2 border-t border-gray-200">
                                            <div class="flex justify-between items-center">
                                                <div>
                                                    <div class="text-xs text-gray-500">Total Sold</div>
                                                    <div class="text-sm font-medium text-gray-900"><?php echo $product['total_sold']; ?> units</div>
                                                </div>
                                                <!-- Product ID -->
                                                <div class="text-xs text-gray-400">
                                                    ID: <?php echo $product['product_id']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Just show Product ID if no sales -->
                                        <div class="col-span-2 pt-2 border-t border-gray-200">
                                            <div class="text-xs text-gray-400 text-right">
                                                ID: <?php echo $product['product_id']; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Bottom row: Quick Actions -->
                                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                    <!-- Product ID on left (if not shown above) -->
                                    <?php if ($product['total_sold'] > 0): ?>
                                        <div class="text-xs text-gray-400 opacity-0">ID Placeholder</div>
                                    <?php else: ?>
                                        <div class="text-xs text-gray-400">ID: <?php echo $product['product_id']; ?></div>
                                    <?php endif; ?>

                                    <div class="flex gap-1">
                                        <!-- Edit Button -->
                                        <a href="a_pro.php?id=<?php echo $product['product_id']; ?>"
                                            class="inline-flex items-center justify-center w-10 h-10 border border-blue-300 text-blue-600 rounded-lg hover:bg-blue-50 transition-all duration-200"
                                            title="Edit Product">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>

                                        <!-- Toggle New -->
                                        <button onclick="toggleNew(<?php echo $product['product_id']; ?>)"
                                            class="inline-flex items-center justify-center w-10 h-10 border <?php echo $product['is_new'] ? 'border-green-500 bg-green-50' : 'border-green-300'; ?> text-green-600 rounded-lg hover:bg-green-50 transition-all duration-200"
                                            title="<?php echo $product['is_new'] ? 'Remove from New Arrivals' : 'Mark as New Arrival'; ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>

                                        <!-- Toggle Featured -->
                                        <button onclick="toggleFeatured(<?php echo $product['product_id']; ?>)"
                                            class="inline-flex items-center justify-center w-10 h-10 border <?php echo $product['is_featured'] ? 'border-yellow-500 bg-yellow-50' : 'border-yellow-300'; ?> text-yellow-600 rounded-lg hover:bg-yellow-50 transition-all duration-200"
                                            title="<?php echo $product['is_featured'] ? 'Remove from Featured' : 'Mark as Featured'; ?>">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        </button>

                                        <!-- Delete Button -->
                                        <button onclick="confirmDelete(<?php echo $product['product_id']; ?>)"
                                            class="inline-flex items-center justify-center w-10 h-10 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-all duration-200"
                                            title="Delete Product">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 011-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="border-t border-gray-200 px-4 lg:px-6 py-4 bg-gray-50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-700 text-center sm:text-left">
                            Showing <?php echo (($current_page - 1) * $per_page) + 1; ?> to
                            <?php echo min($current_page * $per_page, $total_products); ?> of
                            <?php echo $total_products; ?> results
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

<script>
    // Select all checkboxes
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        // Also update mobile checkboxes
        const mobileCheckboxes = document.querySelectorAll('.mobile-product-checkbox');
        mobileCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Mobile select all
    document.getElementById('mobile-select-all').addEventListener('change', function() {
        const mobileCheckboxes = document.querySelectorAll('.mobile-product-checkbox');
        mobileCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        // Also update desktop checkboxes
        const desktopCheckboxes = document.querySelectorAll('.product-checkbox');
        desktopCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

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
        localStorage.setItem('adminViewPreference', 'table');
    });

    cardViewBtn.addEventListener('click', function() {
        desktopTable.classList.add('hidden');
        mobileCards.classList.remove('hidden');
        cardViewBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-700');
        cardViewBtn.classList.remove('text-gray-500');
        tableViewBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-700');
        tableViewBtn.classList.add('text-gray-500');
        // Store preference
        localStorage.setItem('adminViewPreference', 'cards');
    });

    // Load saved preference
    document.addEventListener('DOMContentLoaded', function() {
        const savedView = localStorage.getItem('adminViewPreference');
        if (window.innerWidth < 768 && savedView === 'table') {
            tableViewBtn.click();
        } else if (window.innerWidth < 768 && savedView === 'cards') {
            cardViewBtn.click();
        }
    });

    // Mobile bulk actions
    document.getElementById('mobile_bulk_action').addEventListener('change', function() {
        if (this.value) {
            confirmMobileBulkAction(this.value);
            this.value = ''; // Reset dropdown
        }
    });

    function confirmMobileBulkAction(action) {
        const selectedProducts = document.querySelectorAll('.mobile-product-checkbox:checked');

        if (selectedProducts.length === 0) {
            showNotification('Please select products to perform bulk action.', 'warning');
            return;
        }

        const productIds = Array.from(selectedProducts).map(cb => cb.value);

        let message = `Are you sure you want to ${action} ${selectedProducts.length} product(s)?`;
        let type = 'warning';
        let confirmText = 'Confirm';

        if (action === 'delete') {
            message = `Are you sure you want to delete ${selectedProducts.length} product(s)? This action cannot be undone.`;
            type = 'error';
            confirmText = 'Delete';
        }

        showConfirmationModal(
            'Confirm Bulk Action',
            message,
            () => {
                performAjaxAction('bulk_action', {
                    bulk_action: action,
                    selected_products: productIds
                });
            }, {
                type: type,
                confirmText: confirmText,
                cancelText: 'Cancel'
            }
        );
    }

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

            const response = await fetch('a_products.php', {
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
        const selectedProducts = document.querySelectorAll('.product-checkbox:checked');
        const bulkAction = document.getElementById('bulk_action').value;

        if (selectedProducts.length === 0) {
            showNotification('Please select products to perform bulk action.', 'warning');
            return;
        }

        if (!bulkAction) {
            showNotification('Please select a bulk action.', 'warning');
            return;
        }

        const productIds = Array.from(selectedProducts).map(cb => cb.value);

        let message = `Are you sure you want to ${bulkAction} ${selectedProducts.length} product(s)?`;
        let type = 'warning';
        let confirmText = 'Confirm';

        if (bulkAction === 'delete') {
            message = `Are you sure you want to delete ${selectedProducts.length} product(s)? This action cannot be undone.`;
            type = 'error';
            confirmText = 'Delete';
        }

        showConfirmationModal(
            'Confirm Bulk Action',
            message,
            () => {
                performAjaxAction('bulk_action', {
                    bulk_action: bulkAction,
                    selected_products: productIds
                });
            }, {
                type: type,
                confirmText: confirmText,
                cancelText: 'Cancel'
            }
        );
    }

    // Single Product Actions with Confirmation
    function confirmDelete(productId) {
        showConfirmationModal(
            'Delete Product',
            'Are you sure you want to delete this product? This action cannot be undone. All associated data will be permanently removed.',
            () => {
                performAjaxAction('single_action', {
                    action: 'delete',
                    product_id: productId
                });
            }, {
                type: 'error',
                confirmText: 'Delete',
                cancelText: 'Cancel'
            }
        );
    }

    function toggleNew(productId) {
        showConfirmationModal(
            'Toggle New Arrival Status',
            'Are you sure you want to change the new arrival status of this product?',
            () => {
                performAjaxAction('single_action', {
                    action: 'toggle_new',
                    product_id: productId
                });
            }, {
                type: 'info',
                confirmText: 'Update',
                cancelText: 'Cancel'
            }
        );
    }

    function toggleFeatured(productId) {
        showConfirmationModal(
            'Toggle Featured Status',
            'Are you sure you want to change the featured status of this product?',
            () => {
                performAjaxAction('single_action', {
                    action: 'toggle_featured',
                    product_id: productId
                });
            }, {
                type: 'info',
                confirmText: 'Update',
                cancelText: 'Cancel'
            }
        );
    }

    // Quick search functionality
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            document.getElementById('filter-form').submit();
        });
    }

    // Show notification function (uses the global toast system)
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
</script>

<style>
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

    /* Mobile card animations */
    .mobile-card {
        transition: all 0.3s ease;
    }

    .mobile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Swipe actions for mobile cards */
    .mobile-card {
        position: relative;
        overflow: hidden;
    }

    .mobile-card-actions {
        position: absolute;
        top: 0;
        right: -100%;
        bottom: 0;
        display: flex;
        align-items: center;
        background: linear-gradient(to right, transparent, rgba(59, 130, 246, 0.1));
        padding: 0 16px;
        transition: right 0.3s ease;
    }

    .mobile-card:hover .mobile-card-actions {
        right: 0;
    }

    /* Status indicator dots */
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 4px;
    }

    .status-active {
        background-color: #10b981;
    }

    .status-inactive {
        background-color: #9ca3af;
    }

    .status-low-stock {
        background-color: #f59e0b;
    }

    .status-out-of-stock {
        background-color: #ef4444;
    }

    /* Stock progress bar */
    .stock-progress {
        height: 4px;
        border-radius: 2px;
        overflow: hidden;
        background-color: #e5e7eb;
    }

    .stock-progress-fill {
        height: 100%;
        border-radius: 2px;
    }

    .stock-high {
        background-color: #10b981;
    }

    .stock-medium {
        background-color: #f59e0b;
    }

    .stock-low {
        background-color: #ef4444;
    }

    /* Add to your existing CSS */
    @media (max-width: 640px) {
        .mobile-card {
            padding: 16px;
        }

        .mobile-card h3 {
            font-size: 15px;
            line-height: 1.4;
        }

        /* Better text wrapping */
        .text-wrap-balance {
            text-wrap: balance;
        }

        /* Make sure long words break properly */
        .break-words {
            word-break: break-word;
            overflow-wrap: break-word;
        }
    }

    /* For very small screens */
    @media (max-width: 375px) {
        .mobile-card {
            padding: 12px;
        }

        .mobile-card h3 {
            font-size: 14px;
        }

        /* Stack price and stock vertically on very small screens */
        .mobile-card .grid-cols-2 {
            grid-template-columns: 1fr;
            gap: 12px;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>