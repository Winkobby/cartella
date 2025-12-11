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

// Handle form submission for adding/editing categories
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $category_name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($category_name)) {
        $_SESSION['error_message'] = 'Category name is required.';
        header('Location: a_categories.php');
        exit;
    }
    
    try {
        if ($action === 'add') {
            // Check if category already exists
            $check_query = "SELECT COUNT(*) as count FROM categories WHERE category_name = ?";
            $check_stmt = $database->getConnection()->prepare($check_query);
            $check_stmt->execute([$category_name]);
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $_SESSION['error_message'] = 'Category with this name already exists.';
                header('Location: a_categories.php');
                exit;
            }
            
            $query = "INSERT INTO categories (category_name, description) VALUES (?, ?)";
            $stmt = $database->getConnection()->prepare($query);
            $stmt->execute([$category_name, $description]);
            $_SESSION['success_message'] = 'Category added successfully!';
            
        } elseif ($action === 'edit' && isset($_POST['category_id'])) {
            $category_id = intval($_POST['category_id']);
            
            // Check if category already exists (excluding current category)
            $check_query = "SELECT COUNT(*) as count FROM categories WHERE category_name = ? AND category_id != ?";
            $check_stmt = $database->getConnection()->prepare($check_query);
            $check_stmt->execute([$category_name, $category_id]);
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $_SESSION['error_message'] = 'Category with this name already exists.';
                header('Location: a_categories.php');
                exit;
            }
            
            $query = "UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?";
            $stmt = $database->getConnection()->prepare($query);
            $stmt->execute([$category_name, $description, $category_id]);
            $_SESSION['success_message'] = 'Category updated successfully!';
        }
        
        header('Location: a_categories.php');
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        header('Location: a_categories.php');
        exit;
    }
}

// Handle AJAX requests for category operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['ajax_action']) {
            case 'delete_category':
                $category_id = intval($_POST['category_id'] ?? 0);
                
                if ($category_id > 0) {
                    // Check if category has products
                    $check_query = "SELECT COUNT(*) as product_count FROM products WHERE category_id = ?";
                    $check_stmt = $database->getConnection()->prepare($check_query);
                    $check_stmt->execute([$category_id]);
                    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['product_count'] > 0) {
                        $response['message'] = 'Cannot delete category with products. Remove products first.';
                    } else {
                        $delete_query = "DELETE FROM categories WHERE category_id = ?";
                        $delete_stmt = $database->getConnection()->prepare($delete_query);
                        $delete_stmt->execute([$category_id]);
                        
                        $response['success'] = true;
                        $response['message'] = 'Category deleted successfully!';
                    }
                } else {
                    $response['message'] = 'Invalid category ID';
                }
                break;
                
            case 'get_category':
                $category_id = intval($_POST['category_id'] ?? 0);
                
                if ($category_id > 0) {
                    $query = "SELECT * FROM categories WHERE category_id = ?";
                    $stmt = $database->getConnection()->prepare($query);
                    $stmt->execute([$category_id]);
                    $category = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($category) {
                        $response['success'] = true;
                        $response['category'] = $category;
                    } else {
                        $response['message'] = 'Category not found';
                    }
                } else {
                    $response['message'] = 'Invalid category ID';
                }
                break;
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get search parameter
$search = $_GET['search'] ?? '';

// Build base and params for search filter
$base = "FROM categories c WHERE 1=1";
$params = [];
if (!empty($search)) {
    $base .= " AND (c.category_name LIKE ? OR c.description LIKE ? )";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Count matching categories (accurate for search results)
$count_sql = "SELECT COUNT(*) " . $base;
$count_stmt = $database->getConnection()->prepare($count_sql);
$count_stmt->execute($params);
$matched_count = (int)$count_stmt->fetchColumn();

// Sorting and pagination parameters
$sort = $_GET['sort'] ?? 'name_asc';
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 20;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_pages = $per_page > 0 ? (int)ceil($matched_count / $per_page) : 1;
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}
$offset = ($current_page - 1) * $per_page;

// Fetch categories with product counts via LEFT JOIN aggregate
$sql = "SELECT c.*, COALESCE(pc.product_count,0) as product_count "
    . "FROM categories c "
    . "LEFT JOIN (SELECT category_id, COUNT(*) AS product_count FROM products WHERE stock_quantity > 0 GROUP BY category_id) pc ON c.category_id = pc.category_id "
    . "WHERE 1=1";

// Append search filter again for the main query
if (!empty($search)) {
    $sql .= " AND (c.category_name LIKE ? OR c.description LIKE ? )";
}

// Determine ORDER BY based on sort param
switch ($sort) {
    case 'name_desc':
        $orderClause = ' ORDER BY c.category_name DESC';
        break;
    case 'id_asc':
        $orderClause = ' ORDER BY c.category_id ASC';
        break;
    case 'id_desc':
        $orderClause = ' ORDER BY c.category_id DESC';
        break;
    case 'products_asc':
        $orderClause = ' ORDER BY pc.product_count ASC';
        break;
    case 'products_desc':
        $orderClause = ' ORDER BY pc.product_count DESC';
        break;
    case 'name_asc':
    default:
        $orderClause = ' ORDER BY c.category_name ASC';
        break;
}

$sql .= $orderClause;

// Pagination
$sql .= " LIMIT ? OFFSET ?";

$stmt = $database->getConnection()->prepare($sql);
// Bind params for main query (same order as $params)
$execParams = $params;
$execParams[] = (int)$per_page;
$execParams[] = (int)$offset;
$stmt->execute($execParams);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Preserve page categories to avoid clobbering by header includes
$page_categories = $categories;

// Get total count for stats
$total_cats_query = "SELECT COUNT(*) as total_categories FROM categories";
$total_cats_stmt = $database->getConnection()->prepare($total_cats_query);
$total_cats_stmt->execute();
$total_cats_result = $total_cats_stmt->fetch(PDO::FETCH_ASSOC);

$total_prods_query = "SELECT COUNT(*) as total_products FROM products";
$total_prods_stmt = $database->getConnection()->prepare($total_prods_query);
$total_prods_stmt->execute();
$total_prods_result = $total_prods_stmt->fetch(PDO::FETCH_ASSOC);

$cats_with_prods_query = "SELECT COUNT(DISTINCT category_id) as categories_with_products FROM products WHERE category_id IS NOT NULL";
$cats_with_prods_stmt = $database->getConnection()->prepare($cats_with_prods_query);
$cats_with_prods_stmt->execute();
$cats_with_prods_result = $cats_with_prods_stmt->fetch(PDO::FETCH_ASSOC);

$stock_query = "SELECT COALESCE(SUM(stock_quantity), 0) as total_products_in_stock FROM products";
$stock_stmt = $database->getConnection()->prepare($stock_query);
$stock_stmt->execute();
$stock_result = $stock_stmt->fetch(PDO::FETCH_ASSOC);

// Merge all stats
$stats = array_merge([
    'total_categories' => 0,
    'categories_with_products' => 0,
    'total_products' => 0,
    'total_products_in_stock' => 0
], $total_cats_result, $total_prods_result, $cats_with_prods_result, $stock_result);

$page_title = 'Manage Categories';
$meta_description = 'Manage product categories';

?>

<?php require_once 'includes/admin_header.php'; ?>
<div class="min-h-screen bg-gray-50 py-4 lg:py-8">
    <div class="container mx-auto px-3 lg:px-4 max-w-7xl">

        <!-- Display PHP session messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 animate-fade-in">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-green-800 font-medium"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 animate-fade-in">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 font-medium"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Header Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-3 lg:mb-3">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Manage Categories</h1>
                    <p class="text-gray-600 mt-2 text-sm lg:text-base">Organize your products into categories</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <button onclick="openAddModal()"
                        class="inline-flex items-center justify-center px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm lg:text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add New Category
                    </button>
                    <a href="a_products.php"
                        class="inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium text-sm lg:text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        Manage Products
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-3">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Categories</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_categories']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Products</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_products']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Categories</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['categories_with_products']; ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php echo $stats['total_categories'] - $stats['categories_with_products']; ?> empty</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">In Stock</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_products_in_stock']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-3">
            <form method="GET" action="a_categories.php" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search Input -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Categories</label>
                        <input type="search" 
                               name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm lg:text-sm"
                               placeholder="Search by category name or description...">
                    </div>

                    <!-- Sort Dropdown -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
                            <option value="products_desc" <?php echo $sort === 'products_desc' ? 'selected' : ''; ?>>Most Products</option>
                            <option value="products_asc" <?php echo $sort === 'products_asc' ? 'selected' : ''; ?>>Fewest Products</option>
                            <option value="id_desc" <?php echo $sort === 'id_desc' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="id_asc" <?php echo $sort === 'id_asc' ? 'selected' : ''; ?>>Oldest First</option>
                        </select>
                    </div>

                    <!-- Results Per Page -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Per Page</label>
                        <select name="per_page" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pt-2">
                    <div class="text-sm text-gray-600">
                        <?php if (!empty($search)): ?>
                            Found <?php echo $matched_count; ?> category(ies) for "<?php echo htmlspecialchars($search); ?>"
                        <?php else: ?>
                            Total <?php echo $matched_count; ?> category(ies)
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm lg:text-sm flex items-center justify-center w-full sm:w-auto">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Apply Filters
                        </button>
                        <a href="a_categories.php" 
                           class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm lg:text-sm flex items-center justify-center w-full sm:w-auto">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear All
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Categories Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Products</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Description</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($page_categories)): ?>
                            <tr>
                                <td colspan="4" class="px-4 lg:px-6 py-8 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No categories found</p>
                                    <p class="text-sm mt-1">
                                        <?php if (!empty($search)): ?>
                                            No categories match "<?php echo htmlspecialchars($search); ?>"
                                        <?php else: ?>
                                            Start by adding your first category
                                        <?php endif; ?>
                                    </p>
                                    <?php if (empty($search)): ?>
                                        <button onclick="openAddModal()" 
                                               class="inline-block mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm">
                                            Add New Category
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($page_categories as $category): 
                                $product_count = $category['product_count'] ?? 0;
                                // Fallback direct query if product count is not available
                                if ($product_count == 0) {
                                    $direct_count_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ? AND stock_quantity > 0";
                                    $direct_count_stmt = $database->getConnection()->prepare($direct_count_query);
                                    $direct_count_stmt->execute([$category['category_id']]);
                                    $direct_result = $direct_count_stmt->fetch(PDO::FETCH_ASSOC);
                                    $product_count = $direct_result['count'] ?? 0;
                                }
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors" id="category-<?php echo $category['category_id']; ?>">
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                                </svg>
                                            </div>
                                            <div class="ml-3 lg:ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                                </div>
                                                <?php if (!empty($category['description']) && strlen($category['description']) > 50): ?>
                                                    <div class="text-xs text-gray-500 truncate max-w-xs lg:max-w-sm">
                                                        <?php echo htmlspecialchars(substr($category['description'], 0, 50)) . '...'; ?>
                                                    </div>
                                                <?php elseif (!empty($category['description'])): ?>
                                                    <div class="text-xs text-gray-500">
                                                        <?php echo htmlspecialchars($category['description']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden md:table-cell">
                                        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $product_count > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $product_count; ?> product(s)
                                        </div>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 text-sm text-gray-900 hidden lg:table-cell">
                                        <?php if (!empty($category['description'])): ?>
                                            <div class="max-w-xs lg:max-w-sm truncate">
                                                <?php echo htmlspecialchars($category['description']); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400">No description</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <!-- Edit Button -->
                                            <button onclick="openEditModal(<?php echo $category['category_id']; ?>)"
                                                class="inline-flex items-center justify-center border border-blue-300 text-blue-600 p-2 rounded-xl hover:bg-blue-50 transition-all duration-300 hover:scale-105 group"
                                                title="Edit Category">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>

                                            <!-- Delete Button -->
                                            <button onclick="deleteCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars(addslashes($category['category_name'])); ?>')"
                                                class="inline-flex items-center justify-center border border-red-300 text-red-600 p-2 rounded-xl hover:bg-red-50 transition-all duration-300 hover:scale-105 group"
                                                title="Delete Category">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                            
                                            <!-- View Products Button -->
                                            <a href="a_products.php?category=<?php echo $category['category_id']; ?>"
                                               class="inline-flex items-center justify-center border border-green-300 text-green-600 p-2 rounded-xl hover:bg-green-50 transition-all duration-300 hover:scale-105 group"
                                               title="View Products in this Category">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
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
                            <?php echo min($current_page * $per_page, $matched_count); ?> of 
                            <?php echo $matched_count; ?> results
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

<!-- Add/Edit Category Modal -->
<div id="category-modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50 hidden">
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:my-8 w-full sm:max-w-lg">
                <!-- Modal header -->
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900" id="modal-title">Add New Category</h3>
                    </div>
                    
                    <!-- Category Form -->
                    <form id="category-form" method="POST" class="space-y-4">
                        <input type="hidden" name="action" id="form-action" value="add">
                        <input type="hidden" name="category_id" id="category-id" value="">
                        
                        <div>
                            <label for="modal-category-name" class="block text-sm font-medium text-gray-700 mb-2">
                                Category Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="modal-category-name" 
                                   name="category_name" 
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                   placeholder="Enter category name">
                            <div id="category-name-error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div>

                        <div>
                            <label for="modal-description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description
                            </label>
                            <textarea 
                                id="modal-description" 
                                name="description" 
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="Enter category description (optional)"></textarea>
                        </div>
                        
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" 
                                    onclick="closeModal()"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm">
                                Cancel
                            </button>
                            <button type="submit"
                                    id="modal-submit-btn"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm flex items-center">
                                <span id="submit-text">Create Category</span>
                                <svg id="loading-spinner" class="hidden w-4 h-4 ml-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Modal functions
function openAddModal() {
    document.getElementById('modal-title').textContent = 'Add New Category';
    document.getElementById('form-action').value = 'add';
    document.getElementById('category-id').value = '';
    document.getElementById('modal-category-name').value = '';
    document.getElementById('modal-description').value = '';
    document.getElementById('submit-text').textContent = 'Create Category';
    document.getElementById('modal-submit-btn').classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
    document.getElementById('modal-submit-btn').classList.add('bg-blue-600', 'hover:bg-blue-700');
    
    const errorDiv = document.getElementById('category-name-error');
    errorDiv.classList.add('hidden');
    errorDiv.textContent = '';
    
    document.getElementById('category-modal').classList.remove('hidden');
    document.getElementById('modal-category-name').focus();
}

function openEditModal(categoryId) {
    // Show loading state
    document.getElementById('modal-title').textContent = 'Loading...';
    document.getElementById('modal-category-name').value = '';
    document.getElementById('modal-description').value = '';
    document.getElementById('submit-text').textContent = 'Loading...';
    
    const errorDiv = document.getElementById('category-name-error');
    errorDiv.classList.add('hidden');
    errorDiv.textContent = '';
    
    document.getElementById('category-modal').classList.remove('hidden');
    
    // Fetch category data
    const formData = new FormData();
    formData.append('ajax_action', 'get_category');
    formData.append('category_id', categoryId);
    
    fetch('a_categories.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modal-title').textContent = 'Edit Category';
            document.getElementById('form-action').value = 'edit';
            document.getElementById('category-id').value = data.category.category_id;
            document.getElementById('modal-category-name').value = data.category.category_name;
            document.getElementById('modal-description').value = data.category.description || '';
            document.getElementById('submit-text').textContent = 'Update Category';
            document.getElementById('modal-submit-btn').classList.remove('bg-blue-600', 'hover:bg-blue-700');
            document.getElementById('modal-submit-btn').classList.add('bg-yellow-600', 'hover:bg-yellow-700');
            document.getElementById('modal-category-name').focus();
        } else {
            showToast('error', data.message || 'Failed to load category');
            closeModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Network error. Please try again.');
        closeModal();
    });
}

function closeModal() {
    document.getElementById('category-modal').classList.add('hidden');
}

// Delete category function
function deleteCategory(categoryId, categoryName) {
    showConfirmationModal(
        'Delete Category',
        `Are you sure you want to delete the category "${categoryName}"? This action cannot be undone and will fail if the category has products.`,
        () => {
            performDelete(categoryId);
        }, {
            type: 'error',
            confirmText: 'Delete',
            cancelText: 'Cancel'
        }
    );
}

async function performDelete(categoryId) {
    try {
        const formData = new FormData();
        formData.append('ajax_action', 'delete_category');
        formData.append('category_id', categoryId);
        
        const response = await fetch('a_categories.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('success', result.message);
            // Remove the row from the table
            const row = document.getElementById(`category-${categoryId}`);
            if (row) {
                row.remove();
            }
            // Update statistics count
            updateCategoryCount();
        } else {
            showToast('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Network error. Please try again.');
    }
}

// Update category count in UI
function updateCategoryCount() {
    const rows = document.querySelectorAll('tbody tr');
    const count = rows.length - (rows[0]?.querySelector('td[colspan]') ? 1 : 0);
    
    const statsElement = document.querySelector('[class*="text-sm text-gray-600"]');
    if (statsElement) {
        const search = new URLSearchParams(window.location.search).get('search');
        if (search) {
            statsElement.textContent = `Found ${count} category(ies) for "${search}"`;
        }
    }
}

// Toast notification function
function showToast(type, message) {
    // Check if we have toast function from admin_header
    if (window.toast && typeof window.toast[type] === 'function') {
        window.toast[type](message);
        return;
    }
    
    // Fallback to simple alert
    alert(message);
}

// Handle form submission
document.getElementById('category-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('modal-submit-btn');
    const spinner = document.getElementById('loading-spinner');
    const submitText = document.getElementById('submit-text');
    
    // Show loading state
    submitBtn.disabled = true;
    spinner.classList.remove('hidden');
    submitText.textContent = 'Processing...';
    
    // Simple form validation
    const categoryName = document.getElementById('modal-category-name').value.trim();
    const errorDiv = document.getElementById('category-name-error');
    
    if (!categoryName) {
        errorDiv.textContent = 'Category name is required';
        errorDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        spinner.classList.add('hidden');
        submitText.textContent = document.getElementById('form-action').value === 'add' ? 'Create Category' : 'Update Category';
        return;
    }
    
    // Submit the form
    this.submit();
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('category-modal').classList.contains('hidden')) {
        closeModal();
    }
});

// Close modal when clicking on backdrop
document.getElementById('category-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>