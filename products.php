<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Initialize database first
$database = new Database();

// Initialize functions and set database
$functions = new Functions();
$functions->setDatabase($database);
$top_selling_products = $functions->getTopSellingProducts(25);

$clean_params = [];

// Clean category parameter - accept both slug and ID
if (isset($_GET['category'])) {
    $raw_category = $_GET['category'];
    if (is_array($raw_category)) {
        $clean_params['category'] = 'all';
        error_log("Category was array, reset to 'all'");
    } else {
        // Remove any HTML/error messages from the category parameter
        $clean_category = preg_replace('/<br\s*\/?>|<b>|<\/b>|Warning:|Array to string conversion.*|on line \d+/i', '', $raw_category);
        $clean_category = trim($clean_category);

        if ($clean_category === 'all' || $clean_category === '' || stripos($clean_category, 'array') !== false) {
            $clean_params['category'] = 'all';
        } else {
            // Accept slug (string) or ID (numeric)
            $clean_params['category'] = $clean_category;
        }
    }
} else {
    $clean_params['category'] = 'all';
}

// Clean other parameters
$clean_params['search'] = isset($_GET['search']) ? $functions->sanitizeInput($_GET['search']) : '';
$clean_params['sort'] = isset($_GET['sort']) ? $functions->sanitizeInput($_GET['sort']) : 'newest';
$clean_params['page'] = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$clean_params['per_page'] = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
$clean_params['price_min'] = isset($_GET['price_min']) ? max(0, (float)$_GET['price_min']) : '';
$clean_params['price_max'] = isset($_GET['price_max']) ? max(0, (float)$_GET['price_max']) : '';
$clean_params['brand'] = isset($_GET['brand']) ? $functions->sanitizeInput($_GET['brand']) : '';
$clean_params['filter'] = isset($_GET['filter']) ? $functions->sanitizeInput($_GET['filter']) : 'all';

// Use cleaned parameters
$category = $clean_params['category'];
$search = $clean_params['search'];
$sort = $clean_params['sort'];
$page = $clean_params['page'];
$per_page = $clean_params['per_page'];
$price_min = $clean_params['price_min'];
$price_max = $clean_params['price_max'];
$brand = $clean_params['brand'];
$filter = $clean_params['filter'];

// Ensure category is scalar to avoid notices when echoing in hidden inputs
if (!is_scalar($category)) {
    $category = 'all';
}

// If URL is corrupted, redirect to clean URL
$current_url = $_SERVER['REQUEST_URI'];
if (strpos($current_url, 'Warning:') !== false || strpos($current_url, 'Array to string conversion') !== false) {
    $clean_url = 'products.php?' . http_build_query($clean_params);
    header("Location: $clean_url");
    exit();
}

// Fix category parameter - convert slug to ID if needed
$category_filter = 'all';
if ($category !== 'all') {
    if (is_numeric($category)) {
        // It's an ID
        $category_filter = (int)$category;
    } else {
        // It's a slug, look it up
        $category_data = $functions->getCategoryBySlug($category);
        $category_filter = $category_data ? $category_data['category_id'] : 'all';
    }
}

// Get categories for filter dropdown
$categories = $functions->getAllCategories();

// Get brands for filter dropdown
$brands = $functions->getAllBrands();

// Helper to normalize product names consistently (matches product.php)
function decodeProductNameValue($rawName)
{
    // Handle single and double-encoded entities (e.g., &amp;apos; -> &apos; -> ')
    $decoded = (string)$rawName;
    for ($i = 0; $i < 2; $i++) {
        $candidate = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($candidate === $decoded) {
            break;
        }
        $decoded = $candidate;
    }

    return [
        'decoded' => $decoded,
        'safe'    => $decoded,  // Already decoded, no need to re-escape
    ];
}

// Get products based on filters
$products_data = $functions->getProducts($category_filter, $search, $sort, $page, $per_page, $price_min, $price_max, $brand, $filter);
$products = $products_data['products'];
$total_products = $products_data['total'];
$total_pages = $products_data['total_pages'];

// Get featured products for sidebar
$featured_products = $functions->getFeaturedProducts(4);

// Get discounted products for sidebar
$discounted_products = $functions->getDiscountedProducts(3);

// Get new arrivals for slider
$new_arrivals = $functions->getNewArrivals(8);

// ========== GET RECENTLY VIEWED PRODUCTS FOR DISPLAY ==========
$recently_viewed_products = [];
$viewed_ids = [];

if (!empty($_SESSION['recently_viewed']) && count($_SESSION['recently_viewed']) > 0) {
    // Get IDs from session
    $viewed_ids = [];
    foreach ($_SESSION['recently_viewed'] as $id) {
        $viewed_ids[] = (int)$id;
    }

    // Limit to 6 products
    $viewed_ids = array_slice($viewed_ids, 0, 6);

    if (!empty($viewed_ids)) {
        try {
            $pdo = $database->getConnection();

            // Using FIELD() for ordering (MySQL specific)
            $placeholders = str_repeat('?,', count($viewed_ids) - 1) . '?';
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.category_id 
                    WHERE p.product_id IN ($placeholders) 
                    ORDER BY FIELD(p.product_id, $placeholders)";

            // Prepare parameters - IDs twice (for WHERE and ORDER BY)
            $params = array_merge($viewed_ids, $viewed_ids);

            $stmt = $pdo->prepare($sql);
            foreach ($params as $index => $value) {
                $stmt->bindValue($index + 1, $value, PDO::PARAM_INT);
            }

            if ($stmt->execute()) {
                $recently_viewed_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Error fetching recently viewed: " . $e->getMessage());
        }
    }
}
// ========== END RECENTLY VIEWED ==========

// Get current category name if selected
$category_name = '';
if ($category_filter !== 'all') {
    $category_data = $functions->getCategoryById($category_filter);
    $category_name = $category_data ? $category_data['category_name'] : '';
}

// Generate page title
$page_title = 'All Products'; // Default title
if ($category_filter !== 'all' && !empty($category_name)) {
    $page_title = $category_name . ' Products';
} elseif (!empty($search)) {
    $page_title = 'Search Results for "' . $search . '"';
}

// Get price range for slider
$price_range = $functions->getPriceRange($category_filter, $brand, $search);
$min_price = $price_range['min_price'];
$max_price = $price_range['max_price'];

// Set default values if no price filter is applied
if (empty($price_min)) $price_min = $min_price;
if (empty($price_max)) $price_max = $max_price;

// Generate meta description
$meta_description = 'Browse our wide selection of quality products'; // Default description
if ($category_filter !== 'all' && !empty($category_name)) {
    $meta_description = 'Browse our selection of ' . $category_name . ' products';
} elseif (!empty($search)) {
    $meta_description = 'Search results for "' . $search . '" at Cartella';
}
?>

<?php require_once 'includes/header.php'; ?>
<?php
// Helper function to get category icons based on category name (matching index.php)
function getCategoryIcon($categoryName)
{
    $icons = [
        'Electronics' => '📱',
        'Fashion' => '👕',
        'Clothing' => '👕',
        'Groceries' => '🛒',
        'Perfumes' => '💎',
        'Home' => '🏠',
        'Home & Kitchen' => '🏠',
        'Sports' => '⚽',
        'Health & Beauty' => '💄',
        'Beauty' => '💄',
        'Toys' => '🧸',
        'Books' => '📚',
        'Automotive' => '🚗',
        'Food' => '🍕'
    ];

    foreach ($icons as $key => $icon) {
        if (stripos($categoryName, $key) !== false) {
            return $icon;
        }
    }

    // Default icon for other categories
    return '🛍️';
}

// Helper function to get icon colors based on category (matching index.php)
function getCategoryIconColor($categoryName)
{
    $colors = [
        'Electronics' => 'bg-gradient-to-br from-blue-500 to-blue-600',
        'Fashion' => 'bg-gradient-to-br from-pink-500 to-pink-600',
        'Clothing' => 'bg-gradient-to-br from-pink-500 to-pink-600',
        'Groceries' => 'bg-gradient-to-br from-green-500 to-green-600',
        'Perfumes' => 'bg-gradient-to-br from-purple-500 to-purple-600',
        'Home' => 'bg-gradient-to-br from-orange-500 to-orange-600',
        'Home & Kitchen' => 'bg-gradient-to-br from-green-500 to-green-600',
        'Beauty' => 'bg-gradient-to-br from-pink-400 to-pink-500',
        'Health & Beauty' => 'bg-gradient-to-br from-pink-400 to-pink-500',
        'Books' => 'bg-gradient-to-br from-yellow-500 to-yellow-600',
        'Sports' => 'bg-gradient-to-br from-red-500 to-red-600',
        'Toys' => 'bg-gradient-to-br from-yellow-500 to-yellow-600',
        'Automotive' => 'bg-gradient-to-br from-gray-600 to-gray-700',
        'Food' => 'bg-gradient-to-br from-orange-500 to-orange-600',
    ];

    foreach ($colors as $key => $color) {
        if (stripos($categoryName, $key) !== false) {
            return $color;
        }
    }

    // Default color for other categories
    return 'bg-gradient-to-br from-gray-500 to-gray-600';
}
?>
<!-- Mobile Filter Button -->
<!-- <div class="lg:hidden fixed bottom-6 right-6 z-40">
    <button onclick="toggleMobileFilters()" class="bg-purple-600 text-white p-4 rounded-full shadow-lg hover:bg-purple-700 transition-all duration-300 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
        </svg>
    </button>
</div> -->

<!-- Mobile Filter Overlay -->
<div id="mobileFilterOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden lg:hidden transition-opacity duration-300">
    <div class="absolute inset-y-0 left-0 w-80 bg-white transform transition-transform duration-300 -translate-x-full" id="mobileFilterSidebar">
        <div class="h-full overflow-y-auto">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-purple-600 text-white">
                <h2 class="text-lg font-semibold">Filters & Categories</h2>
                <button onclick="toggleMobileFilters()" class="text-white hover:text-gray-200 p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Mobile Filter Content -->
            <div class="p-4 space-y-6">
                <!-- Categories with Expandable Sections -->
                <div class="border border-gray-200 rounded-lg">
                    <button onclick="toggleMobileSection('categories')" class="w-full px-4 py-3 text-left font-semibold text-gray-800 bg-gray-50 hover:bg-gray-100 rounded-t-lg flex justify-between items-center">
                        <span>Categories</span>
                        <svg id="categories-arrow" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="categories-content" class="px-4 pb-3 hidden">
                        <ul class="space-y-2">
                            <!-- ALL PRODUCTS - Always show as default -->
                            <li>
                                <a href="products.php"
                                    class="block rounded text-sm py-1.5 px-2
                                <?php echo ($category === 'all' || !isset($_GET['category'])) ?
                                    'text-purple-600 font-medium' :
                                    'text-gray-700 hover:text-purple-700'; ?>">
                                    All Products
                                    <span class="float-right text-gray-500 text-xs">
                                        (<?php echo $functions->getTotalProductCount(); ?>)
                                    </span>
                                </a>
                            </li>

                            <!-- DYNAMIC CATEGORIES -->
                            <?php foreach ($categories as $cat): ?>
                                <li>
                                    <a href="products.php?category=<?php echo $cat['slug']; ?>"
                                        class="block rounded text-sm py-1.5 px-2
                                    <?php echo ($category == $cat['slug'] || $category == $cat['category_id']) ?
                                        'text-purple-600 font-medium' :
                                        'text-gray-700 hover:text-purple-700'; ?>">
                                        <?php echo $cat['category_name']; ?>
                                        <span class="float-right text-gray-500 text-xs ">
                                            <?php echo $functions->getProductCountByCategory($cat['category_id']); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Mobile Price Filter Section -->
                <div class="border border-gray-200 rounded-lg">
                    <button onclick="toggleMobileSection('price')" class="w-full px-4 py-3 text-left font-semibold text-gray-800 bg-gray-50 hover:bg-gray-100 rounded-t-lg flex justify-between items-center">
                        <span>Price Range</span>
                        <svg id="price-arrow" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="price-content" class="px-4 pb-3 hidden">
                        <form action="products.php" method="GET" class="space-y-4 mt-2" id="mobilePriceFilterForm">
                            <?php if ($category !== 'all'): ?>
                                <input type="hidden" name="category" value="<?php echo $category; ?>">
                            <?php endif; ?>

                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo $search; ?>">
                            <?php endif; ?>

                            <!-- Modern Price Range Display -->
                            <div class="flex justify-between items-center mb-4">
                                <div class="price-range-display" id="mobilePriceRangeDisplay">
                                    GHS <?php echo $price_min ?: $min_price; ?> - GHS <?php echo $price_max ?: $max_price; ?>
                                </div>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                    Range: GHS <?php echo $min_price; ?> - GHS <?php echo $max_price; ?>
                                </span>
                            </div>

                            <!-- Price Slider -->
                            <div class="space-y-4">
                                <div class="relative">
                                    <div id="mobilePriceSlider" class="h-2 bg-gray-200 rounded-full"></div>
                                </div>

                                <div class="flex space-x-4">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Min Price</label>
                                        <div class="price-input-group">
                                            <input type="number" name="price_min" id="mobilePriceMinInput"
                                                value="<?php echo $price_min; ?>"
                                                min="<?php echo $min_price; ?>"
                                                max="<?php echo $max_price; ?>"
                                                step="1"
                                                class="price-input w-full">
                                        </div>
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Price</label>
                                        <div class="price-input-group">
                                            <input type="number" name="price_max" id="mobilePriceMaxInput"
                                                value="<?php echo $price_max; ?>"
                                                min="<?php echo $min_price; ?>"
                                                max="<?php echo $max_price; ?>"
                                                step="1"
                                                class="price-input w-full">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                                <select name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 text-sm">
                                    <option value="">All Brands</option>
                                    <?php foreach ($brands as $brand_item): ?>
                                        <option value="<?php echo $brand_item; ?>" <?php echo $brand === $brand_item ? 'selected' : ''; ?>>
                                            <?php echo $brand_item; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 transition text-sm">
                                Apply Filters
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Sort Options -->
                <div class="border border-gray-200 rounded-lg">
                    <button onclick="toggleMobileSection('sort')" class="w-full px-4 py-3 text-left font-semibold text-gray-800 bg-gray-50 hover:bg-gray-100 rounded-t-lg flex justify-between items-center">
                        <span>Sort By</span>
                        <svg id="sort-arrow" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="sort-content" class="px-4 pb-3 hidden">
                        <ul class="space-y-2 mt-2">
                            <li>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>"
                                    class="block py-2 px-3 rounded <?php echo $sort === 'newest' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                    Newest First
                                </a>
                            </li>
                            <li>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'popular'])); ?>"
                                    class="block py-2 px-3 rounded <?php echo $sort === 'popular' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                    Most Popular
                                </a>
                            </li>
                            <li>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_low'])); ?>"
                                    class="block py-2 px-3 rounded <?php echo $sort === 'price_low' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                    Price: Low to High
                                </a>
                            </li>
                            <li>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_high'])); ?>"
                                    class="block py-2 px-3 rounded <?php echo $sort === 'price_high' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                    Price: High to Low
                                </a>
                            </li>
                            <li>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'discounted'])); ?>"
                                    class="block py-2 px-3 rounded <?php echo $sort === 'discounted' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                    Discounted Items
                                </a>
                            </li>
                            <li>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'featured'])); ?>"
                                    class="block py-2 px-3 rounded <?php echo $sort === 'featured' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                    Featured Items
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick View Modal -->
<div id="quickViewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity duration-300">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto transform transition-transform duration-300 scale-95" id="quickViewContent">
            <!-- Quick view content will be loaded here via AJAX -->
        </div>
    </div>
</div>

<!-- Top Selling Items -->
<section class="py-6 lg:py-2">
    <div class="container mx-auto px-2 max-w-6xl">
        <div class="bg-white rounded-sm shadow-sm overflow-hidden">
            <div class="text-gray-700 border-b p-4 border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h2 class="text-xl font-bold ">Top Selling Products</h2>
                        <p class="text-gray-600 text-sm ">Our most popular items loved by customers</p>
                    </div>

                </div>
            </div>

            <?php if (!empty($top_selling_products)): ?>
                <div class="relative px-1 py-3">
                    <!-- Navigation Buttons -->
                    <button class="absolute left-2 top-1/2 transform -translate-y-1/2 z-10 bg-white hover:bg-purple-600 text-gray-600 hover:text-white border border-gray-300 hover:border-purple-600 rounded-full w-10 h-10 flex items-center justify-center transition-all duration-300 shadow-lg hover:shadow-xl" onclick="scrollTopSelling(-1)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>

                    <button class="absolute right-2 top-1/2 transform -translate-y-1/2 z-10 bg-white hover:bg-purple-600 text-gray-600 hover:text-white border border-gray-300 hover:border-purple-600 rounded-full w-10 h-10 flex items-center justify-center transition-all duration-300 shadow-lg hover:shadow-xl" onclick="scrollTopSelling(1)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <!-- Slider Container -->
                    <div class="overflow-hidden">
                        <div class="six-column-slider" id="topSellingSlider">
                            <?php foreach ($top_selling_products as $product):
                                $productName = decodeProductNameValue($product['name'] ?? '');
                                $productNameDecoded = $productName['decoded'];
                                $productNameSafe = $productName['safe'];
                                $discounted_price = $functions->calculateDiscountedPrice($product['price'], $product['discount']);
                                $discount_percentage = $product['discount'] > 0 ? round($product['discount']) : 0;
                                $avg_rating = $functions->getAverageRating($product['product_id']);
                                $review_count = $functions->getProductReviews($product['product_id'], 1) ? count($functions->getProductReviews($product['product_id'], 1)) : 0;
                            ?>
                                <div class="slider-item">
                                    <div class="product-card-slider group">
                                        <div class="product-image-container-slider relative overflow-hidden rounded-lg bg-gray-100">
                                            <a href="product.php?product=<?php echo $product['slug']; ?>">
                                                <img src="<?php echo $functions->getProductImage($product['main_image']); ?>"
                                                    alt="<?php echo $productNameSafe; ?>"
                                                    class="product-image-slider">
                                            </a>

                                            <!-- Product Badges -->
                                            <div class="absolute top-3 left-3 flex flex-col gap-2">
                                                <?php if ($discount_percentage > 0): ?>
                                                    <span class="discount-badge-slider bg-red-100 text-red-500">
                                                        -<?php echo $discount_percentage; ?>%
                                                    </span>
                                                <?php endif; ?>

                                            </div>

                                            <!-- Quick Actions -->
                                            <div class="quick-actions-slider">
                                                <button onclick="addToWishlist(<?php echo $product['product_id']; ?>)"
                                                    class="quick-action-btn-slider " title="Add to Wishlist">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                                    </svg>
                                                </button>
                                                <!-- <button onclick="openQuickView(<?php echo $product['product_id']; ?>)"
                                                    class="quick-action-btn-slider" title="Quick View">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </button> -->
                                                <button onclick="addToCart(<?php echo $product['product_id']; ?>)"
                                                    class="bg-white text-purple-600 p-2 rounded-full shadow-md hover:scale-110 transition transform">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                    </svg>
                                                </button>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="product-content-slider p-4">
                                            <div class="category-tag-slider mb-2">
                                                <span class="category-badge-slider">
                                                    <?php echo $product['category_name']; ?>
                                                </span>
                                            </div>

                                            <a href="product.php?product=<?php echo $product['slug']; ?>">
                                                <h3 class="product-name-slider">
                                                    <?php
                                                    echo (strlen($productNameSafe) > 20) ? substr($productNameSafe, 0, 20) . '...' : $productNameSafe;
                                                    ?>
                                                </h3>
                                            </a>


                                            <!-- Rating -->
                                            <div class="rating-section-slider">
                                                <div class="flex items-center gap-1">
                                                    <div class="flex text-yellow-400 text-sm">
                                                        <?php echo str_repeat('★', round($avg_rating)) . str_repeat('☆', 5 - round($avg_rating)); ?>
                                                    </div>
                                                    <span class="text-xs font-semibold text-gray-800"><?php echo number_format($avg_rating, 1); ?></span>
                                                    <span class="rating-text-slider text-xs text-gray-500">(<?php echo $review_count; ?>)</span>
                                                </div>
                                            </div>

                                            <!-- Price Section -->
                                            <div class="price-section-slider">
                                                <?php if ($discount_percentage > 0): ?>
                                                    <div class="price-stack">
                                                        <span class="current-price-slider">
                                                            <?php echo $functions->formatPrice($discounted_price); ?>
                                                        </span>
                                                        <span class="original-price-slider">
                                                            <?php echo $functions->formatPrice($product['price']); ?>
                                                        </span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="price-stack">
                                                        <span class="current-price-slider">
                                                            <?php echo $functions->formatPrice($product['price']); ?>
                                                        </span>
                                                        <span class="best-price-badge-slider">
                                                            Best Price
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- <button onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                                                    class="add-to-cart-btn-slider w-full">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                Add to Cart
                                            </button> -->
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    /* Prevent layout shift when modal opens/closes */
    html.modal-open {
        overflow: hidden;
        padding-right: var(--scrollbar-width, 17px);
    }

    /* Six Column Slider Styles */
    .six-column-slider {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        scroll-behavior: smooth;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 8px 4px;
    }

    .six-column-slider::-webkit-scrollbar {
        display: none;
    }

    .slider-item {
        flex: 0 0 calc(16.666% - 14px);
        /* 6 items per row with gap */
        min-width: 0;
    }

    /* Product Card Styles for Slider */
    .product-card-slider {
        background: white;
        /* border: 1px solid #f1f5f9; */
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        /* box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); */
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .product-card-slider:hover {
        border-color: #8b5cf6;
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.15);
        transform: translateY(-4px);
    }

    .product-image-container-slider {
        aspect-ratio: 1/1;
        position: relative;
        flex-shrink: 0;
    }

    .product-image-slider {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .product-card-slider:hover .product-image-slider {
        transform: scale(1.05);
    }

    /* Badges for Slider */
    .discount-badge-slider {

        font-size: 11px;
        font-weight: 700;
        padding: 3px 6px;
        border-radius: 4px;
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.3);
    }

    .bestseller-badge-slider {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        font-size: 10px;
        font-weight: 600;
        padding: 3px 6px;
        border-radius: 4px;
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3);
    }

    .category-badge-slider {
        background: #f8fafc;
        color: #64748b;
        font-size: 10px;
        font-weight: 500;
        padding: 2px 6px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }

    .best-price-badge-slider {
        background: #10b981;
        color: white;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 5px;
        border-radius: 6px;
    }

    /* Quick Actions for Slider */
    .quick-actions-slider {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        opacity: 0;
        transform: translateX(10px);
        transition: all 0.3s ease;
    }

    .product-card-slider:hover .quick-actions-slider {
        opacity: 1;
        transform: translateX(0);
    }

    .quick-action-btn-slider {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .quick-action-btn-slider:hover {
        background: #8b5cf6;
        border-color: #8b5cf6;
        color: white;
        transform: scale(1.1);
    }

    /* Product Content for Slider */
    .product-content-slider {
        padding: 16px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .product-name-slider {
        font-size: 14px;
        font-weight: 400;
        color: #1e293b;
        line-height: 1.3;
        /* margin-bottom: 1px; */
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 10px;
        /* tighter */
    }


    .product-name-slider:hover {
        color: #8b5cf6;
    }

    /* Rating for Slider */
    .rating-section-slider {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 5px;
    }

    .rating-text-slider {
        font-size: 11px;
        color: #64748b;
        font-weight: 500;
    }

    /* make price section stack vertically and left-aligned */
    .price-section-slider {
        display: block;
        /* keep outer spacing rules if needed */
        margin-bottom: 12px;
    }

    /* vertical stack for prices */
    .price-stack {
        display: flex;
        flex-direction: column;
        gap: 4px;
        /* space between current & original */
        align-items: flex-start;
    }

    /* current price (prominent) */
    .current-price-slider {
        font-size: 16px;
        font-weight: 700;
        color: #8b5cf6;
        line-height: 1;
    }

    /* original price (smaller, struck-through, shown under current price) */
    .original-price-slider {
        font-size: 12px;
        color: #60606eff;
        text-decoration: line-through;
        font-weight: 400;
        line-height: 1;
    }

    /* best-price badge placed under price when no discount */
    .best-price-badge-slider {
        background: #10b981;
        color: white;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 6px;
    }


    /* Add to Cart Button for Slider */
    .add-to-cart-btn-slider {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        border: none;
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        margin-top: auto;
    }

    .add-to-cart-btn-slider:hover {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }

    /* Responsive Design for 6-column slider */
    @media (max-width: 1536px) {
        .slider-item {
            flex: 0 0 calc(16.666% - 14px);
            /* 5 items on 2xl screens */
        }
    }

    @media (max-width: 1280px) {
        .slider-item {
            flex: 0 0 calc(25% - 12px);
            /* 4 items on xl screens */
        }
    }

    @media (max-width: 1024px) {
        .slider-item {
            flex: 0 0 calc(33.333% - 11px);
            /* 3 items on lg screens */
        }

        .product-content-slider {
            padding: 12px;
        }

        .product-name-slider {
            font-size: 13px;
            min-height: 34px;
        }

        .current-price-slider {
            font-size: 15px;
        }
    }

    @media (max-width: 768px) {
        .slider-item {
            flex: 0 0 calc(50% - 8px);
            /* 2 items on md screens */
        }

        .relative.px-12 {
            padding-left: 8px;
            padding-right: 8px;
        }

        .absolute.left-2,
        .absolute.right-2 {
            display: none;
            /* Hide arrows on mobile */
        }

        .product-name-slider {
            font-size: 13px;
            min-height: 32px;
        }

        .current-price-slider {
            font-size: 14px;
        }

        .add-to-cart-btn-slider {
            padding: 8px 10px;
            font-size: 12px;
        }
    }

    @media (max-width: 480px) {
        .slider-item {
            flex: 0 0 calc(50% - 6px);
            /* 2 items on sm screens */
        }

        .product-content-slider {
            padding: 10px;
        }

        .section-header {
            padding: 12px;
        }

        .section-title {
            font-size: 20px;
        }
    }

    /* Hide scrollbar but keep functionality */
    .six-column-slider {
        -webkit-overflow-scrolling: touch;
        scroll-snap-type: x mandatory;
    }

    .slider-item {
        scroll-snap-align: start;
    }

    /* Additional mobile optimizations */
    @media (max-width: 480px) {
        .slider-item {
            flex: 0 0 calc(50% - 4px) !important;
        }

        .product-content-slider {
            padding: 8px 6px !important;
        }

        .product-name-slider {
            font-size: 12px !important;
            min-height: 18px !important;
            margin-bottom: 2px !important;
        }

        .current-price-slider {
            font-size: 13px !important;
        }

        .category-badge-slider {
            font-size: 9px !important;
            padding: 1px 4px !important;
        }
    }

    /* Ensure smooth transitions */
    .six-column-slider {
        scroll-behavior: smooth;
        transition: scroll-left 0.5s ease;
    }

    /* Hide arrows on mobile as intended */
    @media (max-width: 768px) {

        .absolute.left-2,
        .absolute.right-2 {
            display: none !important;
        }
    }

    /* Replace the existing @media (max-width: 768px) section for slider-item */
    @media (max-width: 768px) {
        .slider-item {
            flex: 0 0 calc(33.333% - 8px) !important;
            /* Changed from 50% to 33.333% for 3 items */
        }

        .relative.px-12 {
            padding-left: 8px;
            padding-right: 8px;
        }

        .absolute.left-2,
        .absolute.right-2 {
            display: none !important;
        }

        .product-name-slider {
            font-size: 11px !important;
            /* Smaller font for mobile */
            min-height: 32px;
        }

        .current-price-slider {
            font-size: 13px !important;
            /* Smaller price font */
        }

        .add-to-cart-btn-slider {
            padding: 6px 8px !important;
            /* Smaller button */
            font-size: 10px !important;
        }

        /* Make images smaller */
        .product-image-container-slider {
            aspect-ratio: 1/1.2;
            /* Slightly taller ratio */
        }
    }

    /* Update the 480px breakpoint as well */
    @media (max-width: 480px) {
        .slider-item {
            flex: 0 0 calc(33.333% - 6px) !important;
            /* Changed from 50% to 33.333% */
        }

        .product-content-slider {
            padding: 6px 4px !important;
            /* Smaller padding */
        }

        .section-header {
            padding: 10px !important;
        }

        .section-title {
            font-size: 18px !important;
        }

        .product-name-slider {
            font-size: 10px !important;
            min-height: 18px !important;
            margin-bottom: 2px !important;
        }

        /* Hide category badges completely on very small screens */
        .category-badge-slider {
            display: none !important;
        }
    }
</style>

<script>
    // Enhanced Top Selling Slider with Auto-scroll
    function initTopSellingSlider() {
        const slider = document.getElementById('topSellingSlider');
        if (!slider) return;

        let autoScrollInterval;
        const scrollSpeed = 5000; // 3 seconds
        const scrollAmount = 1;

        function startAutoScroll() {
            autoScrollInterval = setInterval(() => {
                const itemWidth = slider.querySelector('.slider-item')?.offsetWidth + 16;
                if (itemWidth) {
                    const maxScroll = slider.scrollWidth - slider.clientWidth;

                    if (slider.scrollLeft >= maxScroll - 10) {
                        // If at end, scroll to beginning
                        slider.scrollTo({
                            left: 0,
                            behavior: 'smooth'
                        });
                    } else {
                        // Scroll to next item
                        slider.scrollBy({
                            left: itemWidth * scrollAmount,
                            behavior: 'smooth'
                        });
                    }
                    updateTopSellingButtons();
                }
            }, scrollSpeed);
        }

        function stopAutoScroll() {
            if (autoScrollInterval) {
                clearInterval(autoScrollInterval);
            }
        }

        // Start auto-scroll
        startAutoScroll();

        // Pause on hover
        slider.addEventListener('mouseenter', stopAutoScroll);
        slider.addEventListener('mouseleave', startAutoScroll);

        // Pause on touch
        slider.addEventListener('touchstart', stopAutoScroll);
        slider.addEventListener('touchend', () => {
            setTimeout(startAutoScroll, 5000);
        });

        // Update navigation buttons
        function updateTopSellingButtons() {
            const prevBtn = document.querySelector('.absolute.left-2');
            const nextBtn = document.querySelector('.absolute.right-2');

            if (!prevBtn || !nextBtn) return;

            const maxScroll = slider.scrollWidth - slider.clientWidth;

            if (slider.scrollLeft <= 10) {
                prevBtn.style.opacity = '0.5';
                prevBtn.style.cursor = 'not-allowed';
            } else {
                prevBtn.style.opacity = '1';
                prevBtn.style.cursor = 'pointer';
            }

            if (slider.scrollLeft >= maxScroll - 10) {
                nextBtn.style.opacity = '0.5';
                nextBtn.style.cursor = 'not-allowed';
            } else {
                nextBtn.style.opacity = '1';
                nextBtn.style.cursor = 'pointer';
            }
        }

        // Enhanced scroll function with hybrid behavior
        function scrollTopSelling(direction) {
            const itemWidth = slider.querySelector('.slider-item')?.offsetWidth + 16;
            if (itemWidth) {
                // Stop auto-scroll temporarily when manually navigating
                stopAutoScroll();
                slider.scrollBy({
                    left: direction * itemWidth * 2,
                    behavior: 'smooth'
                });

                // Restart auto-scroll after a delay
                setTimeout(startAutoScroll, 7000);
            }
            updateTopSellingButtons();
        }

        // Update the existing scrollTopSelling function
        window.scrollTopSelling = scrollTopSelling;

        // Initialize
        updateTopSellingButtons();
        slider.addEventListener('scroll', updateTopSellingButtons);

        // Touch/swipe support
        let startX;
        let scrollLeft;

        slider.addEventListener('touchstart', (e) => {
            startX = e.touches[0].pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
            stopAutoScroll();
        });

        slider.addEventListener('touchmove', (e) => {
            if (!startX) return;
            const x = e.touches[0].pageX - slider.offsetLeft;
            const walk = (x - startX) * 2;
            slider.scrollLeft = scrollLeft - walk;
        });

        slider.addEventListener('touchend', () => {
            setTimeout(startAutoScroll, 3000);
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                scrollTopSelling(-1);
                e.preventDefault();
            } else if (e.key === 'ArrowRight') {
                scrollTopSelling(1);
                e.preventDefault();
            }
        });
    }

    // Update your DOMContentLoaded event listener
    document.addEventListener('DOMContentLoaded', function() {
        const slider = document.getElementById('topSellingSlider');
        if (slider) {
            initTopSellingSlider();
        }

        // Your existing initialization code...
        updateNavButtons();

        const grids = document.querySelectorAll('.products-grid');
        grids.forEach(grid => {
            grid.addEventListener('scroll', updateNavButtons);
        });
    });
</script>

<!-- Products Section -->
<section class="mt-1">

    <div class="container mx-auto px-2 max-w-6xl">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Desktop Sidebar Filters -->
            <aside class="hidden lg:block lg:w-1/4 w-full">
                <div class="bg-white border border-gray-100 rounded-sm shadow-sm p-6 mb-3">
                    <h3 class="text-lg font-semibold mb-4">Categories</h3>
                    <ul class="space-y-2">
                        <!-- ALL PRODUCTS - Always show as default -->
                        <li>
                            <a href="products.php"
                                class="block rounded text-xs py-1.5 px-2
                                <?php echo ($category === 'all' || !isset($_GET['category'])) ?
                                    'text-purple-600 font-medium' :
                                    'text-gray-700 hover:text-purple-700'; ?>">
                                All Products
                                <span class="float-right text-gray-500 text-xs">
                                    (<?php echo $functions->getTotalProductCount(); ?>)
                                </span>
                            </a>
                        </li>

                        <!-- DYNAMIC CATEGORIES -->
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="products.php?category=<?php echo $cat['slug']; ?>"
                                    class="block rounded text-sm py-1.5 px-2
                                    <?php echo ($category == $cat['slug'] || $category == $cat['category_id']) ?
                                        'text-purple-600 font-medium' :
                                        'text-gray-700 hover:text-purple-700'; ?>">
                                    <?php echo $cat['category_name']; ?>
                                    <span class="float-right text-gray-500 text-xs ">
                                        <?php echo $functions->getProductCountByCategory($cat['category_id']); ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Price Filter with Slider -->
                <div class="bg-white border border-gray-100 rounded-sm shadow-sm p-6 mb-3">
                    <h3 class="text-lg font-semibold mb-4">Filter by Price</h3>
                    <form action="products.php" method="GET" class="space-y-4" id="priceFilterForm">
                        <?php if ($category !== 'all'): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars(is_scalar($category) ? $category : 'all'); ?>">
                        <?php endif; ?>

                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo $search; ?>">
                        <?php endif; ?>


                        <!-- Modern Price Range Display -->
                        <div class="flex justify-between items-center mb-4">
                            <div class="price-range-display" id="priceRangeDisplay">
                                GHS <?php echo $price_min ?: $min_price; ?> - GHS <?php echo $price_max ?: $max_price; ?>
                            </div>
                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                Range: GHS <?php echo $min_price; ?> - GHS <?php echo $max_price; ?>
                            </span>
                        </div>

                        <!-- Price Slider -->
                        <div class="space-y-4">
                            <div class="relative">
                                <div id="priceSlider" class="h-2 bg-gray-200 rounded-full"></div>
                            </div>

                            <div class="flex space-x-2">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Price</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2.5 text-gray-500 text-sm">GHS</span>
                                        <input type="number" name="price_min" id="priceMinInput"
                                            value="<?php echo $price_min; ?>" min="0" step="1"
                                            class="pl-12 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 w-full text-sm">
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Price</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2.5 text-gray-500 text-sm">GHS</span>
                                        <input type="number" name="price_max" id="priceMaxInput"
                                            value="<?php echo $price_max; ?>" min="0" step="1"
                                            class="pl-12 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 w-full text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                            <select name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 text-sm">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $brand_item): ?>
                                    <option value="<?php echo $brand_item; ?>" <?php echo $brand === $brand_item ? 'selected' : ''; ?>>
                                        <?php echo $brand_item; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 transition text-sm">
                            Apply Filters
                        </button>

                        <?php if ($price_min || $price_max): ?>
                            <a href="products.php?<?php
                                                    $query = $_GET;
                                                    unset($query['price_min']);
                                                    unset($query['price_max']);
                                                    echo http_build_query($query);
                                                    ?>" class="w-full bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 transition text-sm text-center block">
                                Clear Price Filter
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="bg-white rounded-sm shadow-sm p-6 mb-3 border border-gray-100">
                    <h3 class="text-lg font-semibold mb-4">Sort By</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>"
                                class="block py-1 px-3 text-sm rounded <?php echo $sort === 'newest' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                Newest First
                            </a>
                        </li>
                        <li>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'popular'])); ?>"
                                class="block py-1 px-3 text-sm rounded <?php echo $sort === 'popular' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                Most Popular
                            </a>
                        </li>
                        <li>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_low'])); ?>"
                                class="block py-1 px-3 text-sm rounded <?php echo $sort === 'price_low' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                Price: Low to High
                            </a>
                        </li>
                        <li>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_high'])); ?>"
                                class="block py-1 px-3 text-sm rounded <?php echo $sort === 'price_high' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                Price: High to Low
                            </a>
                        </li>
                        <li>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'discounted'])); ?>"
                                class="block py-1 px-3 text-sm rounded <?php echo $sort === 'discounted' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                Discounted Items
                            </a>
                        </li>
                        <li>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'featured'])); ?>"
                                class="block py-1 px-3 text-sm rounded <?php echo $sort === 'featured' ? 'bg-purple-100 text-purple-600 font-medium' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                Featured Items
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Featured Products Slider -->
                <div class="bg-white rounded-sm shadow-sm p-6 mb-3 border border-gray-100">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Featured Products</h3>
                        <div class="flex space-x-1">
                            <button class="featured-prev p-1 rounded hover:bg-gray-100 transition">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button class="featured-next p-1 rounded hover:bg-gray-100 transition">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="featured-slider relative overflow-hidden">
                        <div class="swiper-wrapper">
                            <?php foreach ($featured_products as $product):
                                $productName = decodeProductNameValue($product['name'] ?? '');
                                $productNameDecoded = $productName['decoded'];
                                $productNameSafe = $productName['safe'];
                            ?>
                                <div class="swiper-slide">
                                    <div class="flex space-x-3 p-2 rounded-lg hover:bg-gray-50 transition">
                                        <a href="product.php?product=<?php echo $product['slug']; ?>" class="flex-shrink-0">
                                            <img src="<?php echo $functions->getProductImage($product['main_image']); ?>"
                                                alt="<?php echo $productNameSafe; ?>"
                                                class="w-16 h-16 object-cover rounded-lg">
                                        </a>
                                        <div class="flex-1 min-w-0">
                                            <a href="product.php?product=<?php echo $product['slug']; ?>"
                                                class="text-sm font-medium text-gray-800 hover:text-purple-600 line-clamp-2 leading-tight">
                                                <?php echo strlen($productNameSafe) > 20 ? substr($productNameSafe, 0, 30) . '...' : $productNameSafe; ?>
                                            </a>
                                            <p class="text-sm text-purple-600 font-semibold mt-1">
                                                <?php echo $functions->formatPrice($functions->calculateDiscountedPrice($product['price'], $product['discount'])); ?>
                                            </p>
                                            <?php if ($product['discount'] > 0): ?>
                                                <p class="text-xs text-gray-500 line-through">
                                                    <?php echo $functions->formatPrice($product['price']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination dots -->
                        <div class="featured-pagination flex justify-center space-x-1 mt-3"></div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="w-full lg:flex-1 lg:min-w-0">
                <!-- Header with Search, Results Count and View Toggle -->
                <div class="bg-white rounded-sm shadow-sm p-4 md:p-6 mb-2">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="flex-1">
                            <h1 class="text-xl md:text-xl font-bold text-gray-800">
                                <?php echo $page_title; ?>
                            </h1>

                        </div>

                        <div class="flex items-center space-x-3 md:space-x-4 ">
                            <!-- View Toggle -->
                            <div class="flex items-center space-x-1 bg-gray-100 rounded-lg p-1 md:hidden">
                                <button id="grid-view-btn" class="p-2 rounded-md bg-white shadow-sm view-toggle active" data-view="grid" title="Grid View">
                                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                    </svg>
                                </button>
                                <button id="list-view-btn" class="p-2 rounded-md view-toggle" data-view="list" title="List View">
                                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Mobile Filter Button (Visible on mobile) -->
                            <button onclick="toggleMobileFilters()" class="lg:hidden bg-purple-600 text-white p-2 rounded-md hover:bg-purple-700 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                </svg>
                            </button>

                            <!-- Search -->
                              <div class="w-full md:w-auto">
                                <p class="text-gray-600 text-sm md:text-base">
                                    Showing <?php echo ($total_products > 0) ? min($per_page, $total_products) : 0; ?>
                                    of <?php echo $total_products; ?> products
                                    <?php if ($category === 'all'): ?>
                                        <span class="text-purple-600 font-medium">(All Categories)</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Category Slider -->
                <div class="bg-white rounded-sm shadow-sm p-4 mb-2 overflow-hidden">
                    <div class="relative">
                        <!-- Navigation arrows (hidden on mobile) -->
                        <button class="category-prev absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white border border-gray-300 text-gray-600 hover:text-purple-600 hover:border-purple-400 rounded-full w-8 h-8 flex items-center justify-center shadow-md transition-all duration-200 hidden md:flex">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>

                        <button class="category-next absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white border border-gray-300 text-gray-600 hover:text-purple-600 hover:border-purple-400 rounded-full w-8 h-8 flex items-center justify-center shadow-md transition-all duration-200 hidden md:flex">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>

                        <!-- Slider Container -->
                        <div class="category-slider-container">
                            <div class="category-slider flex gap-2 pb-2 overflow-x-auto scrollbar-hide" id="categorySlider">
                                <!-- "All" Category -->
                                <a href="products.php"
                                    class="category-slide flex-shrink-0 flex flex-col items-center justify-center p-3 rounded-xl border-2 min-w-[120px] transition-all duration-300 <?php echo ($category === 'all' || !isset($_GET['category'])) ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-purple-300 hover:bg-purple-50'; ?>">
                                    <div class="w-12 h-12 flex items-center justify-center bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-full mb-2">
                                        <span class="text-2xl">🛍️</span>
                                    </div>
                                    <span class="text-sm font-medium text-gray-800 text-center">All Products</span>
                                    <span class="text-xs text-gray-500 mt-1"><?php echo $functions->getTotalProductCount(); ?> items</span>
                                </a>

                                <!-- Dynamic Categories -->
                                <?php foreach ($categories as $cat): ?>
                                    <a href="products.php?category=<?php echo $cat['slug']; ?>"
                                        class="category-slide flex-shrink-0 flex flex-col items-center justify-center p-3 rounded-xl border-2 min-w-[120px] transition-all duration-300 <?php echo ($category == $cat['slug'] || $category == $cat['category_id']) ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-purple-300 hover:bg-purple-50'; ?>">
                                        <!-- Category Icon (dynamic based on category name) -->
                                        <div class="w-12 h-12 flex items-center justify-center rounded-full mb-2 <?php echo getCategoryIconColor($cat['category_name']); ?>">
                                            <span class="text-2xl"><?php echo getCategoryIcon($cat['category_name']); ?></span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-800 text-center"><?php echo $cat['category_name']; ?></span>
                                        <span class="text-xs text-gray-500 mt-1"><?php echo $functions->getProductCountByCategory($cat['category_id']); ?> items</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    /* Category Slider Styles */
                    .category-slider-container {
                        position: relative;
                        padding: 0 10px;
                        margin: 0 -10px;
                    }

                    .category-slider {
                        scroll-behavior: smooth;
                        scrollbar-width: none;
                        -ms-overflow-style: none;
                        scroll-snap-type: x mandatory;
                        padding-bottom: 8px;
                    }

                    .category-slider::-webkit-scrollbar {
                        display: none;
                    }

                    .category-slide {
                        scroll-snap-align: start;
                        cursor: pointer;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    }

                    .category-slide:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
                    }

                    .category-slide.active {
                        transform: translateY(-2px);
                        box-shadow: 0 6px 16px rgba(139, 92, 246, 0.2);
                    }

                    /* Hide scrollbar but keep functionality */
                    .scrollbar-hide {
                        -ms-overflow-style: none;
                        scrollbar-width: none;
                    }

                    .scrollbar-hide::-webkit-scrollbar {
                        display: none;
                    }

                    /* Responsive adjustments */
                    @media (max-width: 768px) {
                        .category-slide {
                            min-width: 110px;
                            padding: 12px;
                        }

                        .category-slide .w-12 {
                            width: 40px;
                            height: 40px;
                        }

                        .category-slide svg {
                            width: 20px;
                            height: 20px;
                        }
                    }

                    @media (max-width: 480px) {
                        .category-slide {
                            min-width: 100px;
                            padding: 10px;
                        }

                        .category-slide .text-sm {
                            font-size: 12px;
                        }

                        .category-slide .text-xs {
                            font-size: 10px;
                        }
                    }

                    /* Active state pulse animation */
                    @keyframes pulse-border {
                        0% {
                            box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.7);
                        }

                        70% {
                            box-shadow: 0 0 0 6px rgba(139, 92, 246, 0);
                        }

                        100% {
                            box-shadow: 0 0 0 0 rgba(139, 92, 246, 0);
                        }
                    }

                    .category-slide.border-purple-500 {
                        animation: pulse-border 2s infinite;
                    }
                </style>

                

                <div class="bg-white rounded-lg shadow-sm p-1 md:p-4 mb-3">
                    <!-- Products Content -->
                    <?php if (count($products) > 0): ?>

                        <!-- Grid View -->
                        <div id="grid-view" class="products-view">
                            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-4 gap-2 mb-4">
                                <?php foreach ($products as $product):
                                    $productName = decodeProductNameValue($product['name'] ?? '');
                                    $productNameDecoded = $productName['decoded'];
                                    $productNameSafe = $productName['safe'];
                                    $discounted_price = $functions->calculateDiscountedPrice($product['price'], $product['discount']);
                                    $discount_percentage = $product['discount'] > 0 ? round($product['discount']) : 0;

                                    $badges = [];

                                    if ($discount_percentage > 0) {
                                        $badges[] = [
                                            'text'  => '-' . $discount_percentage . '%',
                                            'color' => 'bg-red-200 text-red-600'
                                        ];
                                    }

                                    // NEW BADGE from database instead of checking 7 days
                                    if (!empty($product['is_new']) && $product['is_new'] == 1) {
                                        $badges[] = ['text' => 'new', 'color' => 'bg-green-400'];
                                    }

                                    // Add "Best Seller" badge for products with high ratings
                                    $avg_rating = $functions->getAverageRating($product['product_id']);
                                    if ($avg_rating >= 4.5) {
                                        $badges[] = ['text' => 'BEST', 'color' => 'bg-yellow-500'];
                                    }
                                ?>
                                    <div class="product-card bg-white rounded-lg overflow-hidden group flex flex-col h-full relative">
                                        <div class="product-image-container-slider relative overflow-hidden rounded-lg bg-gray-100">

                                            <!-- Product Badges -->
                                            <div class="absolute top-2 left-2 right-2 flex flex-wrap gap-1 z-10">
                                                <?php foreach (array_slice($badges, 0, 2) as $badge): ?>
                                                    <span class="<?php echo $badge['color']; ?> text-white text-xs px-2 py-0.5 rounded-full block sm:inline-block">
                                                        <?php echo $badge['text']; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>

                                            <a href="product.php?product=<?php echo $product['slug']; ?>">
                                                <img src="<?php echo $functions->getProductImage($product['main_image']); ?>"
                                                    alt="<?php echo $productNameSafe; ?>"
                                                    class="product-image-slider">
                                            </a>

                                            <!-- Quick Actions - Hover Overlay -->
                                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                <div class="flex space-x-2">
                                                    <button onclick="addToWishlist(<?php echo $product['product_id']; ?>)"
                                                        class="bg-white text-purple-600 p-2 rounded-full shadow-md hover:scale-110 transition transform">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                                        </svg>
                                                    </button>
                                                    <button onclick="openQuickView(<?php echo $product['product_id']; ?>)"
                                                        class="bg-white text-purple-600 p-2 rounded-full shadow-md hover:scale-110 transition transform hidden sm:block">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="p-2 flex flex-col h-full">
                                            <a href="product.php?product=<?php echo $product['slug']; ?>" class="block mb-1">
                                                <h3 class="text-gray-800 text-xs leading-tight hover:text-purple-600 transition line-clamp-2">
                                                    <?php echo $productNameSafe; ?>
                                                </h3>
                                            </a>

                                            <!-- Rating Section -->
                                            <div class="flex items-center gap-1 mb-1">
                                                <div class="text-yellow-400 text-sm">
                                                    <?php echo str_repeat('★', round($avg_rating)) . str_repeat('☆', 5 - round($avg_rating)); ?>
                                                </div>
                                                <span class="text-xs font-semibold text-gray-800">
                                                    <?php echo number_format($avg_rating, 1); ?>
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    (<?php $review_count = $functions->getProductReviews($product['product_id'], 1) ? count($functions->getProductReviews($product['product_id'], 1)) : 0;
                                                        echo $review_count; ?>)
                                                </span>
                                            </div>

                                            <!-- Price Section -->
                                            <div class="mb-2">
                                                <?php if ($product['discount'] > 0): ?>
                                                    <div class="flex flex-col gap-0">
                                                        <span class="text-sm font-bold text-gray-700">
                                                            <?php echo $functions->formatPrice($functions->calculateDiscountedPrice($product['price'], $product['discount'])); ?>
                                                        </span>
                                                        <span class="text-xs text-gray-500 line-through">
                                                            <?php echo $functions->formatPrice($product['price']); ?>
                                                        </span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-sm font-bold text-gray-700 block">
                                                        <?php echo $functions->formatPrice($product['price']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Add to Cart Button - Icon always visible on mobile, full button on hover (desktop) -->
                                            <div class="mt-auto">
                                                <!-- Mobile: Cart Icon Button (always visible) -->
                                                <button onclick="addToCart(<?php echo $product['product_id']; ?>)"
                                                    class="md:hidden w-full bg-purple-600 text-white py-1.5 px-2 rounded hover:bg-purple-700 transition-all duration-200 flex items-center justify-center">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Desktop: Full Button (visible on hover) -->
                                                <button onclick="addToCart(<?php echo $product['product_id']; ?>)"
                                                    class="hidden md:flex w-full bg-purple-600 text-white py-3 px-2 rounded hover:bg-purple-700 transition-all duration-200 items-center justify-center gap-1 text-sm font-medium opacity-0 group-hover:opacity-100">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                    </svg>
                                                    Add to Cart
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- List View -->
                        <div id="list-view" class="products-view hidden">
                            <div class="space-y-4 mb-8">
                                <?php foreach ($products as $product):
                                    $productName = decodeProductNameValue($product['name'] ?? '');
                                    $productNameDecoded = $productName['decoded'];
                                    $productNameSafe = $productName['safe'];
                                    // Determine product badges for list view
                                    $discounted_price = $functions->calculateDiscountedPrice($product['price'], $product['discount']);
                                    $discount_percentage = $product['discount'] > 0 ? round($product['discount']) : 0;

                                    $badges = [];

                                    if ($discount_percentage > 0) {
                                        $badges[] = [
                                            'text'  => '-' . $discount_percentage . '%',
                                            'color' => 'bg-red-500'
                                        ];
                                    }

                                    if (!empty($product['is_new']) && $product['is_new'] == 1) {
                                        $badges[] = ['text' => 'NEW', 'color' => 'bg-green-200'];
                                    }
                                    // Add "Best Seller" badge for products with high ratings
                                    $avg_rating = $functions->getAverageRating($product['product_id']);
                                    if ($avg_rating >= 4.5) {
                                        $badges[] = ['text' => 'BEST', 'color' => 'bg-yellow-500'];
                                    }

                                    // Truncate description for list view
                                    $description = !empty($product['description']) ? $product['description'] : 'No description available.';
                                    $truncated_description = strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description;
                                ?>
                                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300 flex flex-col sm:flex-row h-full">
                                        <div class="sm:w-1/4 relative flex-shrink-0">
                                            <a href="product.php?id=<?php echo $product['product_id']; ?>" class="block h-full">
                                                <img src="<?php echo $functions->getProductImage($product['main_image']); ?>"
                                                    alt="<?php echo $productNameSafe; ?>"
                                                    class="w-full h-32 sm:h-full object-contain">
                                            </a>
                                            <div class="absolute top-2 left-2 flex flex-wrap gap-1">
                                                <?php foreach (array_slice($badges, 0, 2) as $badge): ?>
                                                    <span class="<?php echo $badge['color']; ?> text-white text-xs px-2 py-1 rounded">
                                                        <?php echo $badge['text']; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="sm:w-3/4 p-4 flex flex-col flex-grow">
                                            <div class="flex flex-col h-full">
                                                <div class="flex-grow">
                                                    <div class="flex justify-between items-start mb-2">
                                                        <div class="block flex-1">
                                                            <div class="mb-2">
                                                                <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded">
                                                                    <?php echo $product['category_name']; ?>
                                                                </span>
                                                            </div>

                                                            <a href="product.php?id=<?php echo $product['product_id']; ?>">
                                                                <h3 class="text-base font-semibold text-gray-800 hover:text-purple-600 transition line-clamp-2">
                                                                    <?php echo $productNameSafe; ?>
                                                                </h3>
                                                            </a>
                                                        </div>
                                                        <div class="flex space-x-2 ml-3">
                                                            <button onclick="addToWishlist(<?php echo $product['product_id']; ?>)"
                                                                class="bg-white p-1.5 rounded border border-purple-600 hover:bg-purple-50 transition">
                                                                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                                                </svg>
                                                            </button>
                                                            <button onclick="openQuickView(<?php echo $product['product_id']; ?>)"
                                                                class="bg-white p-1.5 rounded border border-purple-600 hover:bg-purple-50 transition">
                                                                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center mb-2 gap-2">
                                                        <div class="flex items-center gap-1">
                                                            <div class="text-yellow-400 text-xs">
                                                                <?php
                                                                $prod_rating = $functions->getAverageRating($product['product_id']);
                                                                echo str_repeat('★', round($prod_rating)) . str_repeat('☆', 5 - round($prod_rating));
                                                                ?>
                                                            </div>
                                                            <span class="text-xs font-semibold text-gray-800">
                                                                <?php echo number_format($prod_rating, 1); ?>
                                                            </span>
                                                        </div>
                                                        <span class="text-xs text-gray-500">
                                                            (<?php echo $functions->getProductReviews($product['product_id'], 1) ? count($functions->getProductReviews($product['product_id'], 1)) : '0'; ?>)
                                                        </span>
                                                    </div>

                                                    <p class="text-gray-600 text-sm mb-2 line-clamp-2">
                                                        <?php echo $truncated_description; ?>
                                                    </p>
                                                </div>

                                                <!-- Price and Add to Cart - Always at bottom -->
                                                <div class="mt-auto pt-3 border-t border-gray-200">
                                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                                                        <div class="flex items-center">
                                                            <div>
                                                                <?php if ($product['discount'] > 0): ?>
                                                                    <span class="text-sm font-semibold text-red-500 line-through">
                                                                        <?php echo $functions->formatPrice($product['price']); ?>
                                                                    </span>
                                                                    <span class="text-base font-bold text-purple-600 ml-2">
                                                                        <?php echo $functions->formatPrice($functions->calculateDiscountedPrice($product['price'], $product['discount'])); ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="text-base font-bold text-purple-600">
                                                                        <?php echo $functions->formatPrice($product['price']); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <button onclick="addToCart(<?php echo $product['product_id']; ?>)"
                                                            class="bg-purple-600 text-white px-3 py-1.5 rounded hover:bg-purple-700 transition flex items-center space-x-1 justify-center text-sm">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                            </svg>
                                                            <span>Add to Cart</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="flex justify-center mt-8">
                                <nav class="flex items-center gap-2">
                                    <!-- First page -->
                                    <?php if ($page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>"
                                            class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded hover:bg-gray-50 transition text-sm text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Previous -->
                                    <?php if ($page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                                            class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded hover:bg-gray-50 transition text-sm text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    $start_page = max(1, $page - 1);
                                    $end_page = min($total_pages, $page + 1);

                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                    ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                            class="w-8 h-8 flex items-center justify-center <?php echo $i == $page ? 'bg-purple-500 text-white font-semibold' : 'border border-gray-300 text-gray-700 hover:bg-gray-50'; ?> rounded transition text-sm">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php } ?>

                                    <!-- Next -->
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                                            class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded hover:bg-gray-50 transition text-sm text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Last page -->
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"
                                            class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded hover:bg-gray-50 transition text-sm text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="bg-white rounded-lg shadow-md p-6 md:p-8 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0L9 10.343l7.071 7.071a4 4 0 001.414 0L9 17.657l-7.071-7.071a4 4 0 00-1.414 0L2.343 9.172a4 4 0 015.656 0L9 13.657l7.071-7.071a4 4 0 001.414 0L9 6.343l-7.071 7.071a4 4 0 00-1.414 0z"></path>
                            </svg>
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">No products found</h3>
                            <p class="text-gray-600 mb-4">
                                <?php if (!empty($search)): ?>
                                    No products found matching "<?php echo $search; ?>"
                                <?php else: ?>
                                    No products found in this category
                                <?php endif; ?>
                            </p>
                            <a href="products.php" class="inline-block bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 transition">
                                Browse All Products
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
</section>

<script>
    function scrollTopSellingProducts(direction) {
        const grid = document.getElementById('top-selling-products-grid');
        const scrollAmount = 300;
        grid.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    }

    // Update navigation button states based on scroll position
    function updateNavButtons() {
        const featuredGrid = document.getElementById('featured-products-grid');
        const topSellingGrid = document.getElementById('top-selling-products-grid');

    }

    // Initialize slider navigation
    document.addEventListener('DOMContentLoaded', function() {
        updateNavButtons();

        // Update buttons on scroll
        const grids = document.querySelectorAll('.products-grid');
        grids.forEach(grid => {
            grid.addEventListener('scroll', updateNavButtons);
        });
    });
    // Mobile Filter Functions
    function toggleMobileFilters() {
        const overlay = document.getElementById('mobileFilterOverlay');
        const sidebar = document.getElementById('mobileFilterSidebar');

        if (overlay.classList.contains('hidden')) {
            overlay.classList.remove('hidden');
            setTimeout(() => {
                sidebar.classList.remove('-translate-x-full');
            }, 50);
        } else {
            sidebar.classList.add('-translate-x-full');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300);
        }
    }

    // Expandable sections for mobile
    function toggleMobileSection(section) {
        const content = document.getElementById(section + '-content');
        const arrow = document.getElementById(section + '-arrow');

        content.classList.toggle('hidden');
        arrow.classList.toggle('rotate-180');
    }

    // Close mobile filters when clicking overlay
    document.getElementById('mobileFilterOverlay').addEventListener('click', function(e) {
        if (e.target === this) {
            toggleMobileFilters();
        }
    });

    // View Toggle Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const viewToggleButtons = document.querySelectorAll('.view-toggle');
        const gridView = document.getElementById('grid-view');
        const listView = document.getElementById('list-view');

        // Load saved view preference
        const savedView = localStorage.getItem('productsView') || 'grid';
        setActiveView(savedView);

        viewToggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const view = this.getAttribute('data-view');
                setActiveView(view);
                localStorage.setItem('productsView', view);
            });
        });

        function setActiveView(view) {
            // Update views
            gridView.classList.toggle('hidden', view !== 'grid');
            listView.classList.toggle('hidden', view !== 'list');

            // Update buttons
            viewToggleButtons.forEach(btn => {
                if (btn.getAttribute('data-view') === view) {
                    btn.classList.add('active', 'bg-white', 'shadow-sm');
                    btn.classList.remove('bg-transparent');
                } else {
                    btn.classList.remove('active', 'bg-white', 'shadow-sm');
                    btn.classList.add('bg-transparent');
                }
            });
        }
    });

    // Quick View Functionality
    function openQuickView(productId) {
        const modal = document.getElementById('quickViewModal');
        const content = document.getElementById('quickViewContent');

        // Calculate scrollbar width and set CSS variable
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        document.documentElement.style.setProperty('--scrollbar-width', scrollbarWidth + 'px');

        // Add modal-open class to html element
        document.documentElement.classList.add('modal-open');

        // Show loading state
        content.innerHTML = `
        <div class="flex items-center justify-center p-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
        </div>
    `;

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.querySelector('#quickViewContent').classList.remove('scale-95');
            modal.querySelector('#quickViewContent').classList.add('scale-100');
        }, 50);

        // Load product details via AJAX
        fetch(`ajax/quickview.php?product_id=${productId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                content.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading quick view:', error);
                content.innerHTML = `
                <div class="p-8 text-center">
                    <p class="text-red-500">Error loading product details. Please try again.</p>
                    <button onclick="closeQuickView()" class="mt-4 bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                        Close
                    </button>
                </div>
            `;
            });
    }

    function closeQuickView() {
        const modal = document.getElementById('quickViewModal');
        const content = document.getElementById('quickViewContent');

        // Remove modal-open class from html element
        document.documentElement.classList.remove('modal-open');

        content.classList.remove('scale-100');
        content.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Close quick view when clicking outside
    document.getElementById('quickViewModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeQuickView();
        }
    });

    // Close quick view with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQuickView();
        }
    });

    // Quantity update function for quick view
    function updateQuantity(change) {
        const input = document.getElementById('quickview-quantity');
        if (input) {
            let quantity = parseInt(input.value) + change;
            if (quantity < 1) quantity = 1;
            if (quantity > 10) quantity = 10;
            input.value = quantity;
        }
    }

    // Add to cart from quick view
    function addToCartFromQuickView(productId) {
        const quantityInput = document.getElementById('quickview-quantity');
        const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

        fetch('ajax/cart.php?action=add_to_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    showNotification('Product added to cart!', 'success');
                    closeQuickView();
                } else {
                    showNotification(data.message || 'Error adding product to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error adding product to cart', 'error');
            });
    }

    // Swipe functionality for mobile filters
    let touchStartX = 0;
    let touchEndX = 0;

    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });

    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });

    function handleSwipe() {
        const swipeThreshold = 50;
        const swipeDistance = touchEndX - touchStartX;

        if (Math.abs(swipeDistance) > swipeThreshold) {
            if (swipeDistance > 0 && touchStartX < 50) {
                // Swipe right from left edge - open filters
                toggleMobileFilters();
            } else if (swipeDistance < 0) {
                // Swipe left - close filters if open
                const overlay = document.getElementById('mobileFilterOverlay');
                if (!overlay.classList.contains('hidden')) {
                    toggleMobileFilters();
                }
            }
        }
    }

    // Existing cart and wishlist functions
    function addToCart(productId) {
        fetch('ajax/cart.php?action=add_to_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    showNotification('Product added to cart!', 'success');
                } else {
                    showNotification(data.message || 'Error adding product to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error adding product to cart', 'error');
            });
    }

    function addToWishlist(productId) {
        <?php if (isset($_SESSION['user_id'])): ?>
            fetch('ajax/wishlist.php?action=add_to_wishlist', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || 'Product added to wishlist!', 'success');
                        // Update wishlist count
                        updateWishlistCountDisplay(data.wishlist_count);
                    } else {
                        showNotification(data.message || 'Error adding to wishlist', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error adding to wishlist', 'error');
                });
        <?php else: ?>
            window.location.href = 'signin.php?redirect=' + encodeURIComponent(window.location.href);
        <?php endif; ?>
    }

    // Modern Price Slider Functionality
    function initPriceSliders() {
        const dynamicMin = <?php echo $min_price; ?>;
        const dynamicMax = <?php echo $max_price; ?>;
        const currentMin = <?php echo $price_min ?: $min_price; ?>;
        const currentMax = <?php echo $price_max ?: $max_price; ?>;

        //  console.log('Price Range:', dynamicMin, dynamicMax, currentMin, currentMax);

        // Initialize desktop slider
        initDesktopSlider(dynamicMin, dynamicMax, currentMin, currentMax);

        // Initialize mobile slider
        initMobileSlider(dynamicMin, dynamicMax, currentMin, currentMax);
    }

    function initDesktopSlider(dynamicMin, dynamicMax, currentMin, currentMax) {
        const slider = document.getElementById('priceSlider');
        const priceMinInput = document.getElementById('priceMinInput');
        const priceMaxInput = document.getElementById('priceMaxInput');
        const priceRangeDisplay = document.getElementById('priceRangeDisplay');

        // Check if all required elements exist
        if (!slider || !priceMinInput || !priceMaxInput || !priceRangeDisplay) {
            console.warn('Desktop price slider elements not found');
            return;
        }

        try {
            noUiSlider.create(slider, {
                start: [currentMin, currentMax],
                connect: true,
                range: {
                    'min': dynamicMin,
                    'max': dynamicMax
                },
                step: 1,
                behaviour: 'smooth-drag',
                format: {
                    to: function(value) {
                        return Math.round(value);
                    },
                    from: function(value) {
                        return Number(value);
                    }
                }
            });

            // Update inputs and display when slider changes
            slider.noUiSlider.on('update', function(values, handle) {
                const minValue = Math.round(values[0]);
                const maxValue = Math.round(values[1]);

                // Update input fields
                if (priceMinInput) priceMinInput.value = minValue;
                if (priceMaxInput) priceMaxInput.value = maxValue;

                // Update the display with animation
                if (priceRangeDisplay) {
                    priceRangeDisplay.textContent = `GHS ${minValue} - GHS ${maxValue}`;
                    priceRangeDisplay.style.transform = 'scale(1.02)';
                    setTimeout(() => {
                        priceRangeDisplay.style.transform = 'scale(1)';
                    }, 150);
                }
            });

            slider.noUiSlider.on('start', function() {
                // Add pulse effect to handles
                const handles = slider.querySelectorAll('.noUi-handle');
                handles.forEach(handle => {
                    handle.classList.add('pulse');
                });
            });

            slider.noUiSlider.on('end', function() {
                // Remove pulse effect
                const handles = slider.querySelectorAll('.noUi-handle');
                handles.forEach(handle => {
                    handle.classList.remove('pulse');
                });
            });

            // Update slider when min input changes
            if (priceMinInput) {
                priceMinInput.addEventListener('change', function() {
                    let value = Math.max(dynamicMin, Math.min(parseInt(this.value) || dynamicMin, dynamicMax));

                    // Ensure min doesn't exceed max
                    const currentMaxValue = parseInt(priceMaxInput?.value) || dynamicMax;
                    if (value > currentMaxValue) {
                        value = currentMaxValue - 1;
                        this.value = value;
                    }

                    slider.noUiSlider.set([value, null]);
                });

                // Real-time input validation
                priceMinInput.addEventListener('input', function() {
                    let value = parseInt(this.value) || dynamicMin;
                    if (value < dynamicMin) this.value = dynamicMin;
                    if (value > dynamicMax) this.value = dynamicMax;
                });
            }

            // Update slider when max input changes
            if (priceMaxInput) {
                priceMaxInput.addEventListener('change', function() {
                    let value = Math.max(dynamicMin, Math.min(parseInt(this.value) || dynamicMax, dynamicMax));

                    // Ensure max doesn't go below min
                    const currentMinValue = parseInt(priceMinInput?.value) || dynamicMin;
                    if (value < currentMinValue) {
                        value = currentMinValue + 1;
                        this.value = value;
                    }

                    slider.noUiSlider.set([null, value]);
                });

                // Real-time input validation
                priceMaxInput.addEventListener('input', function() {
                    let value = parseInt(this.value) || dynamicMax;
                    if (value < dynamicMin) this.value = dynamicMin;
                    if (value > dynamicMax) this.value = dynamicMax;
                });
            }

            // console.log('Desktop price slider initialized successfully');

        } catch (error) {
            console.error('Error initializing desktop price slider:', error);
        }
    }

    function initMobileSlider(dynamicMin, dynamicMax, currentMin, currentMax) {
        const mobileSlider = document.getElementById('mobilePriceSlider');
        const mobilePriceMinInput = document.getElementById('mobilePriceMinInput');
        const mobilePriceMaxInput = document.getElementById('mobilePriceMaxInput');
        const mobilePriceRangeDisplay = document.getElementById('mobilePriceRangeDisplay');

        // Check if all required elements exist
        if (!mobileSlider || !mobilePriceMinInput || !mobilePriceMaxInput || !mobilePriceRangeDisplay) {
            console.warn('Mobile price slider elements not found');
            return;
        }

        try {
            noUiSlider.create(mobileSlider, {
                start: [currentMin, currentMax],
                connect: true,
                range: {
                    'min': dynamicMin,
                    'max': dynamicMax
                },
                step: 1,
                behaviour: 'smooth-drag',
                format: {
                    to: function(value) {
                        return Math.round(value);
                    },
                    from: function(value) {
                        return Number(value);
                    }
                }
            });

            // Update inputs and display when mobile slider changes
            mobileSlider.noUiSlider.on('update', function(values, handle) {
                const minValue = Math.round(values[0]);
                const maxValue = Math.round(values[1]);

                // Update input fields
                if (mobilePriceMinInput) mobilePriceMinInput.value = minValue;
                if (mobilePriceMaxInput) mobilePriceMaxInput.value = maxValue;

                // Update the display with animation
                if (mobilePriceRangeDisplay) {
                    mobilePriceRangeDisplay.textContent = `GHS ${minValue} - GHS ${maxValue}`;
                    mobilePriceRangeDisplay.style.transform = 'scale(1.02)';
                    setTimeout(() => {
                        mobilePriceRangeDisplay.style.transform = 'scale(1)';
                    }, 150);
                }
            });

            // Update mobile slider when min input changes
            if (mobilePriceMinInput) {
                mobilePriceMinInput.addEventListener('change', function() {
                    let value = Math.max(dynamicMin, Math.min(parseInt(this.value) || dynamicMin, dynamicMax));

                    // Ensure min doesn't exceed max
                    const currentMaxValue = parseInt(mobilePriceMaxInput?.value) || dynamicMax;
                    if (value > currentMaxValue) {
                        value = currentMaxValue - 1;
                        this.value = value;
                    }

                    mobileSlider.noUiSlider.set([value, null]);
                });

                // Real-time input validation for mobile
                mobilePriceMinInput.addEventListener('input', function() {
                    let value = parseInt(this.value) || dynamicMin;
                    if (value < dynamicMin) this.value = dynamicMin;
                    if (value > dynamicMax) this.value = dynamicMax;
                });
            }

            // Update mobile slider when max input changes
            if (mobilePriceMaxInput) {
                mobilePriceMaxInput.addEventListener('change', function() {
                    let value = Math.max(dynamicMin, Math.min(parseInt(this.value) || dynamicMax, dynamicMax));

                    // Ensure max doesn't go below min
                    const currentMinValue = parseInt(mobilePriceMinInput?.value) || dynamicMin;
                    if (value < currentMinValue) {
                        value = currentMinValue + 1;
                        this.value = value;
                    }

                    mobileSlider.noUiSlider.set([null, value]);
                });

                // Real-time input validation for mobile
                mobilePriceMaxInput.addEventListener('input', function() {
                    let value = parseInt(this.value) || dynamicMax;
                    if (value < dynamicMin) this.value = dynamicMin;
                    if (value > dynamicMax) this.value = dynamicMax;
                });
            }

            // console.log('Mobile price slider initialized successfully');

        } catch (error) {
            console.error('Error initializing mobile price slider:', error);
        }
    }

    // Featured Products Slider
    function initFeaturedSlider() {
        if (typeof Swiper !== 'undefined') {
            new Swiper('.featured-slider', {
                slidesPerView: 1,
                spaceBetween: 10,
                navigation: {
                    nextEl: '.featured-next',
                    prevEl: '.featured-prev',
                },
                pagination: {
                    el: '.featured-pagination',
                    clickable: true,
                    renderBullet: function(index, className) {
                        return '<span class="' + className + ' w-2 h-2 bg-gray-300 rounded-full inline-block mx-1"></span>';
                    },
                },
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                loop: true,
            });
        }
    }

    // New Arrivals Slider
    function initNewArrivalsSlider() {
        if (typeof Swiper !== 'undefined') {
            new Swiper('.new-arrivals-slider', {
                slidesPerView: 1,
                spaceBetween: 20,
                navigation: {
                    nextEl: '.new-arrivals-next',
                    prevEl: '.new-arrivals-prev',
                },
                pagination: {
                    el: '.new-arrivals-pagination',
                    clickable: true,
                    renderBullet: function(index, className) {
                        return '<span class="' + className + ' w-2 h-2 bg-gray-300 rounded-full inline-block mx-1"></span>';
                    },
                },
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                loop: true,
                breakpoints: {
                    640: {
                        slidesPerView: 2,
                    },
                    768: {
                        slidesPerView: 3,
                    },
                    1024: {
                        slidesPerView: 5, // Changed from 4 to 5
                    },
                },
            });
        }
    }

    // Initialize sliders when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Load required libraries
        loadPriceSliderLibrary();
        loadFeaturedSliderLibrary();
        loadNewArrivalsSliderLibrary();
    });

    function loadPriceSliderLibrary() {
        if (typeof noUiSlider === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js';
            script.onload = function() {
                // console.log('noUiSlider library loaded successfully');
                // Wait a bit for the library to fully initialize
                setTimeout(initPriceSliders, 100);
            };
            script.onerror = function() {
                console.error('Failed to load noUiSlider library');
                // Hide slider elements and show fallback message
                document.querySelectorAll('.noUi-target').forEach(el => {
                    el.style.display = 'none';
                });
                document.querySelectorAll('.price-range-display').forEach(el => {
                    el.innerHTML = '<span class="text-red-500">Price slider unavailable</span>';
                });
            };
            document.head.appendChild(script);

            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css';
            document.head.appendChild(link);
        } else {
            // Library already loaded, initialize immediately
            //console.log('noUiSlider library already loaded');
            initPriceSliders();
        }
    }

    function loadFeaturedSliderLibrary() {
        if (typeof Swiper === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js';
            script.onload = initFeaturedSlider;
            document.head.appendChild(script);

            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css';
            document.head.appendChild(link);
        } else {
            initFeaturedSlider();
        }
    }

    function loadNewArrivalsSliderLibrary() {
        if (typeof Swiper === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js';
            script.onload = initNewArrivalsSlider;
            document.head.appendChild(script);

            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css';
            document.head.appendChild(link);
        } else {
            initNewArrivalsSlider();
        }
    }

    // Ensure default category selection
    document.addEventListener('DOMContentLoaded', function() {
        // Check if no category is selected and set 'all' as default
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('category') && !urlParams.has('search')) {
            // Update the active state visually
            const allProductsLink = document.querySelector('a[href="products.php"]');
            if (allProductsLink) {
                allProductsLink.classList.add('bg-purple-100', 'text-purple-600', 'font-medium');
                allProductsLink.classList.remove('text-gray-700', 'hover:bg-gray-100');
            }
        }

        // Also handle mobile view
        const mobileAllProductsLink = document.querySelector('#mobileFilterOverlay a[href="products.php"]');
        if (mobileAllProductsLink && !urlParams.has('category') && !urlParams.has('search')) {
            mobileAllProductsLink.classList.add('bg-purple-100', 'text-purple-600', 'font-medium');
            mobileAllProductsLink.classList.remove('text-gray-700', 'hover:bg-gray-100');
        }
    });

    // Add touch support for mobile cart buttons
    document.addEventListener('DOMContentLoaded', function() {
        if (window.innerWidth < 768) {
            const productCards = document.querySelectorAll('.product-card');

            productCards.forEach(card => {
                let touchStartY = 0;
                let touchEndY = 0;

                card.addEventListener('touchstart', function(e) {
                    touchStartY = e.touches[0].clientY;
                    // Remove active class from all other cards
                    productCards.forEach(c => c.classList.remove('active'));
                    // Add active class to this card
                    this.classList.add('active');
                });

                card.addEventListener('touchend', function(e) {
                    touchEndY = e.changedTouches[0].clientY;
                    const swipeDistance = touchEndY - touchStartY;

                    // If swipe up, keep the cart button visible
                    if (swipeDistance < -20) {
                        this.classList.add('active');
                    }
                    // If swipe down or tap elsewhere, hide
                    else if (swipeDistance > 20) {
                        this.classList.remove('active');
                    }
                });

                // Close cart button when clicking outside
                document.addEventListener('touchstart', function(e) {
                    if (!card.contains(e.target)) {
                        card.classList.remove('active');
                    }
                });
            });
        }
    });
</script>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .view-toggle.active {
        background-color: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }

    .products-view {
        transition: opacity 0.3s ease;
    }

    /* Fix for all discount badges on mobile */
    @media (max-width: 768px) {

        /* Top selling products slider badges */
        .discount-badge-slider {
            font-size: 10px !important;
            font-weight: 800 !important;
            padding: 4px 6px !important;
            border-radius: 6px !important;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.4) !important;
            z-index: 20;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .bestseller-badge-slider {
            font-size: 9px !important;
            font-weight: 700 !important;
            padding: 3px 5px !important;
            display: block !important;
        }

        /* Main product grid badges */
        .product-card .absolute.top-2.left-2.right-2 span {
            font-size: 10px !important;
            font-weight: 700 !important;
            padding: 3px 5px !important;
            margin: 1px !important;
            display: inline-block !important;
        }

        /* Ensure badges container is visible */
        .product-image-container-slider .absolute.top-3.left-3,
        .product-card .absolute.top-2.left-2.right-2 {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Position adjustments for mobile */
        .absolute.top-3.left-3 {
            top: 8px !important;
            left: 8px !important;
        }

        .product-card .absolute.top-2.left-2.right-2 {
            top: 6px !important;
            left: 6px !important;
            right: 6px !important;
        }
    }

    /* Enhanced mobile hover effect removal */
    @media (max-width: 768px) {

        /* Remove all hover effects on product cards */
        .product-card-slider:hover,
        .product-card:hover,
        .product-image-container-slider:hover,
        .product-card-slider .quick-actions-slider,
        .product-card .quick-actions-slider,
        .product-card-slider:hover .product-image-slider,
        .product-card:hover .product-image-slider {
            border-color: transparent !important;
            box-shadow: none !important;
            transform: none !important;
            opacity: 1 !important;
            background: none !important;
        }

        /* Remove hover overlay effects */
        .product-card .absolute.inset-0.bg-black {
            display: none !important;
        }

        /* Hide quick actions on hover for mobile */
        .product-card-slider:hover .quick-actions-slider,
        .product-card:hover .quick-actions-slider {
            opacity: 0 !important;
            transform: translateX(10px) !important;
        }

        /* Remove image scaling on hover for mobile */
        .product-card-slider:hover .product-image-slider,
        .product-card:hover .product-image-slider {
            transform: none !important;
        }

        /* Remove hover effects on buttons */
        .quick-action-btn-slider:hover,
        .add-to-cart-btn-slider:hover,
        .bg-white.text-purple-600.p-2.rounded-full:hover {
            transform: none !important;
            background: white !important;
            color: #8b5cf6 !important;
        }

        /* Remove hover effects on navigation arrows */
        .absolute.left-2:hover,
        .absolute.right-2:hover {
            background: white !important;
            color: #6b7280 !important;
            border-color: #e5e7eb !important;
        }

        /* Remove hover effects on category badges */
        .category-badge-slider:hover,
        .bg-purple-100.text-purple-600:hover {
            background: #f3f4f6 !important;
            color: #6b7280 !important;
        }

        /* Remove hover effects on links */
        .product-name-slider:hover,
        .product-name-slider:hover {
            color: #1e293b !important;
        }
    }

    /* Reduced hover effects on product cards */
    .product-card-slider:hover {
        border-color: #e5e7eb !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        transform: translateY(-2px) !important;
    }

    .product-card:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06) !important;
    }

    /* Reduced image hover effect */
    .product-card-slider:hover .product-image-slider {
        transform: scale(1.02) !important;
    }

    /* Reduced quick actions hover effect */
    .quick-action-btn-slider:hover {
        transform: scale(1.05) !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    }

    /* Reduced add to cart button hover effect */
    .add-to-cart-btn-slider:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 6px rgba(139, 92, 246, 0.2) !important;
    }

    /* Ultra small screens */
    @media (max-width: 480px) {
        .discount-badge-slider {
            font-size: 9px !important;
            padding: 3px 5px !important;
            min-width: 26px;
        }

        .bestseller-badge-slider {
            font-size: 8px !important;
            padding: 2px 4px !important;
        }

        .product-card .absolute.top-2.left-2.right-2 span {
            font-size: 9px !important;
            padding: 2px 4px !important;
        }
    }

    /* Mobile optimizations */
    @media (max-width: 768px) {
        .product-card {
            margin-bottom: 0.5rem;
            gap: 0.2rem;
        }

        /* Reduce image size in quick view for mobile */
        #quickViewContent .grid img {
            max-height: 200px !important;
        }

        /* Ensure buttons are visible on mobile */
        #quickViewContent .flex.space-x-4 {
            flex-direction: column;
            gap: 0.5rem;
        }

        #quickViewContent .flex.space-x-4 button {
            width: 100%;
        }
    }

    @media (max-width: 768px) {

        /* Remove hover effects on mobile */
        .product-card-slider:hover {
            border-color: transparent;
            box-shadow: none;
            transform: none;
        }

        .product-card:hover {
            transform: none;
        }

        /* Hide quick actions on hover for mobile */
        .product-card-slider:hover .quick-actions-slider,
        .product-card:hover .quick-actions-slider {
            opacity: 0;
            transform: translateX(10px);
        }

        /* Remove image scaling on hover for mobile */
        .product-card-slider:hover .product-image-slider,
        .product-card:hover .product-image-slider {
            transform: none;
        }

        /* Remove hover overlay effects */
        .product-card .absolute.inset-0.bg-black {
            display: none !important;
        }
    }

    /* Hover effects for product cards */
    /* .product-card:hover {
        transform: translateY(-1px);
    } */

    /* Quick view modal animations */
    #quickViewContent {
        transition: transform 0.3s ease;
    }

    /* Modern Price Slider Styles */
    .noUi-target {
        background: #f3f4f6;
        border: none;
        border-radius: 10px;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        height: 6px;
    }

    .noUi-connect {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
    }

    .noUi-handle {
        background: linear-gradient(135deg, #ffffff, #f9fafb);
        border: 3px solid #8b5cf6;
        border-radius: 50%;
        box-shadow:
            0 4px 12px rgba(139, 92, 246, 0.3),
            0 2px 4px rgba(0, 0, 0, 0.1),
            inset 0 1px 2px rgba(255, 255, 255, 0.8);
        cursor: grab;
        height: 22px !important;
        width: 22px !important;
        right: -11px !important;
        top: -8px !important;
        transition: all 0.2s ease;
    }

    .noUi-handle:before,
    .noUi-handle:after {
        display: none;
    }

    .noUi-handle:hover {
        background: linear-gradient(135deg, #ffffff, #f0f9ff);
        border-color: #7c3aed;
        box-shadow:
            0 6px 16px rgba(139, 92, 246, 0.4),
            0 3px 6px rgba(0, 0, 0, 0.1),
            inset 0 1px 2px rgba(255, 255, 255, 0.9);
        transform: scale(1.1);
    }

    .noUi-handle:active {
        cursor: grabbing;
        box-shadow:
            0 8px 20px rgba(139, 92, 246, 0.5),
            0 4px 8px rgba(0, 0, 0, 0.15),
            inset 0 1px 3px rgba(255, 255, 255, 0.8);
        transform: scale(1.05);
    }

    .noUi-handle:focus {
        outline: none;
        box-shadow:
            0 0 0 3px rgba(139, 92, 246, 0.2),
            0 6px 16px rgba(139, 92, 246, 0.4),
            0 3px 6px rgba(0, 0, 0, 0.1);
    }

    /* Tooltip styles for values */
    .noUi-tooltip {
        background: #1f2937;
        border: none;
        border-radius: 6px;
        color: white;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 8px;
        bottom: -30px;
    }

    .noUi-handle .noUi-tooltip {
        transform: translateX(-50%);
    }

    /* Horizontal rule style for the track */
    .noUi-target .noUi-base {
        background: linear-gradient(to right, #e5e7eb, #d1d5db);
        border-radius: 10px;
    }

    /* Active state for the slider */
    .noUi-state-active .noUi-handle {
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
    }

    /* Disabled state */
    .noUi-state-disabled .noUi-connect {
        background: #9ca3af;
    }

    .noUi-state-disabled .noUi-handle {
        background: #d1d5db;
        border-color: #9ca3af;
        box-shadow: none;
    }

    /* Vertical center alignment for handles */
    .noUi-horizontal .noUi-handle {
        top: 50%;
        transform: translateY(-50%);
    }

    .noUi-horizontal .noUi-handle:hover,
    .noUi-horizontal .noUi-handle:active {
        transform: translateY(-50%) scale(1.1);
    }

    /* Price range display styling */
    .price-range-display {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        padding: 8px 12px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
    }

    .price-input-group {
        position: relative;
    }

    .price-input-group:before {
        content: "GHS";
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-weight: 500;
        font-size: 14px;
        z-index: 10;
    }

    .price-input {
        padding-left: 50px !important;
        border: 2px solid #e5e7eb;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .price-input:focus {
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    /* Mobile-specific slider styles */
    @media (max-width: 768px) {
        .noUi-target {
            height: 5px;
        }

        .noUi-handle {
            height: 20px !important;
            width: 20px !important;
            right: -10px !important;
        }

        .price-range-display {
            font-size: 13px;
            padding: 6px 10px;
        }
    }

    /* Animation for slider value changes */
    @keyframes pulse-glow {
        0% {
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        50% {
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.5);
        }

        100% {
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
    }

    .noUi-handle.pulse {
        animation: pulse-glow 0.6s ease-in-out;
    }

    /* Custom tooltip for current selection */
    .slider-tooltip {
        position: absolute;
        background: #1f2937;
        color: white;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        z-index: 1000;
        pointer-events: none;
        opacity: 0;
        transform: translateY(-10px);
        transition: all 0.2s ease;
    }

    .slider-tooltip:after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: #1f2937;
    }

    .slider-tooltip.show {
        opacity: 1;
        transform: translateY(-15px);
    }

    /* Featured Slider Styles */
    .featured-slider .swiper-slide {
        height: auto;
    }

    .featured-pagination .swiper-pagination-bullet-active {
        background-color: #8b5cf6;
    }

    /* New Arrivals Slider Styles */
    .new-arrivals-slider .swiper-slide {
        height: auto;
    }

    .new-arrivals-pagination .swiper-pagination-bullet-active {
        background-color: #8b5cf6;
    }

    /* Ensure featured product text doesn't overflow */
    .featured-slider .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Ensure all product cards have consistent height behavior */
    .products-view .grid>div,
    .products-view .space-y-4>div {
        display: flex;
    }

    .product-card,
    .bg-white.rounded-lg.border {
        width: 100%;
    }

    /* Fix for grid view alignment */
    .grid.grid-cols-2>div,
    .grid.grid-cols-3>div,
    .grid.grid-cols-4>div {
        display: flex;
    }

    /* Ensure new arrivals slider items have consistent height */
    .new-arrivals-slider .swiper-slide {
        height: auto;
    }

    .new-arrivals-slider .swiper-slide>div {
        height: 100%;
    }

    /* Ensure all product cards have consistent height behavior */
    .products-view .grid>div,
    .products-view .space-y-4>div,
    .new-arrivals-slider .swiper-slide>div {
        display: flex;
    }

    .product-card,
    .bg-white.rounded-lg.border,
    .new-arrivals-slider .bg-white {
        width: 100%;
    }

    /* Fix for grid view alignment */
    .grid.grid-cols-2>div,
    .grid.grid-cols-3>div,
    .grid.grid-cols-4>div {
        display: flex;
    }

    /* Hide product descriptions on mobile devices */
    @media (max-width: 640px) {
        .product-description {
            display: none !important;
        }
    }

    /* Add to Cart Button on Hover Styles */
    .product-card {
        position: relative;
        overflow: hidden;
    }

    /* Hide Add to Cart button by default, show on hover */
    .product-card .absolute.bottom-0 {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Desktop hover effects */
    @media (min-width: 768px) {
        .product-card:hover .absolute.bottom-0 {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
    }

    /* Mobile touch support */
    @media (max-width: 767px) {

        /* Show button on mobile when product card is active/touched */
        .product-card.active .absolute.bottom-0 {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        /* Touch feedback */
        .product-card:active {
            transform: scale(0.98);
        }
    }

    /* Ensure the cart button doesn't interfere with other elements */
    .product-card .absolute.bottom-0 {
        z-index: 30;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
        border-radius: 0 0 8px 8px;
    }

    /* Smooth transition for the entire card */
    .product-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .product-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transform: translateY(-4px);
    }

    /* Ensure the button is properly positioned */
    .product-card>div:last-child {
        position: relative;
    }
</style>

<!-- Recently Viewed Products Section -->
<?php if (!empty($recently_viewed_products) && is_array($recently_viewed_products)): ?>
    <section class="py-4 container mx-auto px-2 max-w-6xl">
        <div class="border border-gray-100 rounded-lg p-4 bg-white shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-1">Recently Viewed</h2>
                    <p class="text-sm text-gray-600">Products you've recently checked out</p>
                </div>
                <?php if (isset($_SESSION['recently_viewed']) && count($_SESSION['recently_viewed']) > 6): ?>
                    <a href="products.php" class="text-purple-600 hover:text-purple-700 font-medium text-sm flex items-center gap-1">
                        <span>View All</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>

            <div class="relative">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <?php foreach ($recently_viewed_products as $viewed):
                        $productName = decodeProductNameValue($viewed['name'] ?? '');
                        $productNameSafe = $productName['safe'];
                    ?>
                        <div class="bg-white rounded-lg overflow-hidden hover:shadow-md transition group">
                            <div class="relative">
                                <?php if (!empty($viewed['discount']) && $viewed['discount'] > 0): ?>
                                    <span class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded z-10">
                                        <?php echo round($viewed['discount']); ?>% OFF
                                    </span>
                                <?php endif; ?>

                                <a href="product.php?id=<?php echo $viewed['product_id']; ?>">
                                    <?php
                                    $image_src = 'https://via.placeholder.com/300x300?text=No+Image';
                                    if (!empty($viewed['main_image'])) {
                                        $image_src = $functions->getProductImage($viewed['main_image']);
                                    }
                                    ?>
                                    <img src="<?php echo $image_src; ?>"
                                        alt="<?php echo $productNameSafe; ?>"
                                        class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300"
                                        onerror="this.src='https://via.placeholder.com/300x300?text=Image+Error'">
                                </a>
                            </div>

                            <div class="p-3">
                                <a href="product.php?id=<?php echo $viewed['product_id']; ?>" class="block">
                                    <h3 class="text-gray-800 text-sm leading-tight line-clamp-2 hover:text-purple-600 transition mb-2">
                                        <?php echo $productNameSafe; ?>
                                    </h3>
                                </a>

                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <?php if (!empty($viewed['discount']) && $viewed['discount'] > 0): ?>
                                            <span class="text-xs text-gray-500 line-through block">
                                                <?php echo $functions->formatPrice($viewed['price']); ?>
                                            </span>
                                            <span class="text-sm font-bold text-purple-600">
                                                <?php echo $functions->formatPrice($functions->calculateDiscountedPrice($viewed['price'], $viewed['discount'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-sm font-bold text-purple-600">
                                                <?php echo $functions->formatPrice($viewed['price']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <button onclick="addToCart(<?php echo $viewed['product_id']; ?>)"
                                    class="w-full bg-purple-600 text-white py-1.5 px-2 rounded hover:bg-purple-700 transition font-medium flex items-center justify-center space-x-1 text-xs">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span>Add to Cart</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>
<script>
                    // Initialize Category Slider
                    document.addEventListener('DOMContentLoaded', function() {
                        initCategorySlider();

                        // Add touch/swipe support for mobile
                        initCategorySliderTouch();

                        // Update active state on load
                        updateActiveCategoryIndicator();
                    });

                    function initCategorySlider() {
                        const slider = document.getElementById('categorySlider');
                        const prevBtn = document.querySelector('.category-prev');
                        const nextBtn = document.querySelector('.category-next');

                        if (!slider) return;

                        // Calculate scroll amount based on slide width
                        const getScrollAmount = () => {
                            const slide = slider.querySelector('.category-slide');
                            return slide ? slide.offsetWidth + 8 : 120; // 8px gap
                        };

                        // Next button
                        if (nextBtn) {
                            nextBtn.addEventListener('click', () => {
                                slider.scrollBy({
                                    left: getScrollAmount(),
                                    behavior: 'smooth'
                                });
                            });
                        }

                        // Previous button
                        if (prevBtn) {
                            prevBtn.addEventListener('click', () => {
                                slider.scrollBy({
                                    left: -getScrollAmount(),
                                    behavior: 'smooth'
                                });
                            });
                        }

                        // Update button visibility based on scroll position
                        function updateNavButtons() {
                            const maxScroll = slider.scrollWidth - slider.clientWidth;

                            if (prevBtn) {
                                prevBtn.style.opacity = slider.scrollLeft > 10 ? '1' : '0.5';
                                prevBtn.style.pointerEvents = slider.scrollLeft > 10 ? 'auto' : 'none';
                            }

                            if (nextBtn) {
                                nextBtn.style.opacity = slider.scrollLeft < maxScroll - 10 ? '1' : '0.5';
                                nextBtn.style.pointerEvents = slider.scrollLeft < maxScroll - 10 ? 'auto' : 'none';
                            }
                        }

                        slider.addEventListener('scroll', updateNavButtons);
                        window.addEventListener('resize', updateNavButtons);

                        // Initialize button states
                        updateNavButtons();

                        // Auto-scroll on arrow key press
                        document.addEventListener('keydown', (e) => {
                            if (e.key === 'ArrowLeft') {
                                slider.scrollBy({
                                    left: -getScrollAmount(),
                                    behavior: 'smooth'
                                });
                                e.preventDefault();
                            } else if (e.key === 'ArrowRight') {
                                slider.scrollBy({
                                    left: getScrollAmount(),
                                    behavior: 'smooth'
                                });
                                e.preventDefault();
                            }
                        });
                    }

                    function initCategorySliderTouch() {
                        const slider = document.getElementById('categorySlider');
                        if (!slider) return;

                        let isDown = false;
                        let startX;
                        let scrollLeft;

                        slider.addEventListener('mousedown', (e) => {
                            isDown = true;
                            slider.classList.add('active');
                            startX = e.pageX - slider.offsetLeft;
                            scrollLeft = slider.scrollLeft;
                        });

                        slider.addEventListener('mouseleave', () => {
                            isDown = false;
                            slider.classList.remove('active');
                        });

                        slider.addEventListener('mouseup', () => {
                            isDown = false;
                            slider.classList.remove('active');
                        });

                        slider.addEventListener('mousemove', (e) => {
                            if (!isDown) return;
                            e.preventDefault();
                            const x = e.pageX - slider.offsetLeft;
                            const walk = (x - startX) * 2; // Scroll multiplier
                            slider.scrollLeft = scrollLeft - walk;
                        });

                        // Touch events for mobile
                        slider.addEventListener('touchstart', (e) => {
                            isDown = true;
                            startX = e.touches[0].pageX - slider.offsetLeft;
                            scrollLeft = slider.scrollLeft;
                        });

                        slider.addEventListener('touchend', () => {
                            isDown = false;
                        });

                        slider.addEventListener('touchmove', (e) => {
                            if (!isDown) return;
                            const x = e.touches[0].pageX - slider.offsetLeft;
                            const walk = (x - startX) * 2;
                            slider.scrollLeft = scrollLeft - walk;
                        });
                    }

                    function updateActiveCategoryIndicator() {
                        const activeSlide = document.querySelector('.category-slide.border-purple-500');
                        const allSlides = document.querySelectorAll('.category-slide');

                        // Remove active class from all slides
                        allSlides.forEach(slide => {
                            slide.classList.remove('active');
                        });

                        // Add active class to current category
                        if (activeSlide) {
                            activeSlide.classList.add('active');

                            // Auto-scroll to active category
                            setTimeout(() => {
                                activeSlide.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'nearest',
                                    inline: 'center'
                                });
                            }, 300);
                        }
                    }
                </script>
<?php require_once 'includes/footer.php'; ?>