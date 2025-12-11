<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Initialize database and functions
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Get search parameters with proper validation
$search_query = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['search']) ? trim($_GET['search']) : '');
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : '';
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : '';
$brand = isset($_GET['brand']) ? $_GET['brand'] : '';
$discount = isset($_GET['discount']) ? (int)$_GET['discount'] : '';

// Fix category parameter - ensure it's a string and handle invalid values
$category = 'all';
if (isset($_GET['category'])) {
    if (is_array($_GET['category'])) {
        $category = 'all';
    } else {
        $category = trim($_GET['category']);
        if (empty($category) || $category === 'Array' || $category === '<br />' || strpos($category, 'Warning') !== false) {
            $category = 'all';
        }
    }
}

// If no search query, redirect to products page
if (empty($search_query)) {
    header('Location: products.php');
    exit();
}

// Get search results
$search_results = $functions->getProducts($category, $search_query, $sort, $page, $per_page, $price_min, $price_max, $brand, $discount);
$products = $search_results['products'];
$total_results = $search_results['total'];
$total_pages = $search_results['total_pages'];

// Get categories and brands for filters
$categories = $functions->getAllCategories();
$brands = $functions->getAllBrands();

// Get price range for search results
$price_range = $functions->getPriceRange($category, $brand, $search_query, $discount);
$min_price = $price_range['min_price'];
$max_price = $price_range['max_price'];

// Page metadata
$page_title = "Search: \"$search_query\"";
$meta_description = "Search results for \"$search_query\" - Find exactly what you're looking for at Cartella.";

require_once 'includes/header.php';

// Helper function to build URL parameters safely
function buildUrlParams($params)
{
    $safeParams = [];
    foreach ($params as $key => $value) {
        if (!empty($value) && $value !== 'all' && $value !== 'Array') {
            $safeParams[$key] = $value;
        }
    }
    return http_build_query($safeParams);
}
?>

<!-- Modern Search Header -->
<section class="py-6 bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 text-white">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="text-center">
            <!-- Breadcrumb -->
            <nav class="flex justify-center mb-4">
                <ol class="flex items-center space-x-2 text-sm text-purple-200">
                    <li><a href="index.php" class="hover:text-white transition">Home</a></li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </li>
                    <li class="text-white font-medium">Search Results</li>
                </ol>
            </nav>

            <h1 class="text-3xl md:text-5xl font-bold mb-4">
                Search Results
            </h1>

            <!-- Search Query Display -->
            <div class="max-w-2xl mx-auto">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mb-6">
                    <span class="text-lg text-purple-100">Showing results for:</span>
                    <div class="relative group">
                        <span class="text-xl font-semibold bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20">
                            "<?php echo htmlspecialchars($search_query); ?>"
                        </span>
                        <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-0 h-0.5 bg-white transition-all duration-300 group-hover:w-full"></div>
                    </div>
                </div>

                <!-- Results Stats -->
                <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                    <div class="bg-white/10 backdrop-blur-sm px-3 py-1 rounded-full">
                        <span class="text-purple-100">Found</span>
                        <strong class="text-white ml-1"><?php echo $total_results; ?></strong>
                        <span class="text-purple-100 ml-1">products</span>
                    </div>

                    <?php if (!empty($category) && $category !== 'all'): ?>
                        <?php
                        $category_name = '';
                        foreach ($categories as $cat) {
                            if ($cat['category_id'] == $category) {
                                $category_name = $cat['category_name'];
                                break;
                            }
                        }
                        ?>
                        <?php if ($category_name): ?>
                            <div class="bg-white/10 backdrop-blur-sm px-3 py-1 rounded-full">
                                <span class="text-purple-100">in</span>
                                <strong class="text-white ml-1"><?php echo $category_name; ?></strong>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search Results Section -->
<section class="" style="background-color: #F1F1F2">
    <div class="container mx-auto px-4 py-4 max-w-6xl">
        <div class="flex flex-col lg:flex-row gap-4">

            <!-- Mobile Filter Toggle -->
            <div class="lg:hidden mb-4">
                <button onclick="toggleMobileFilters()" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition">
                    <span class="font-semibold text-gray-700">Filters & Options</span>
                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" id="filter-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>

            <!-- Sidebar Filters -->
            <aside class="lg:w-1/4 hidden lg:block" id="filters-sidebar">
                <!-- Category Filter -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-3">
                    <h3 class="font-bold text-lg mb-4 text-gray-700 border-b border-gray-200">CATEGORY</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="?q=<?php echo urlencode($search_query); ?>&category=all&sort=<?php echo $sort; ?><?php echo $price_min ? '&price_min=' . $price_min : ''; ?><?php echo $price_max ? '&price_max=' . $price_max : ''; ?><?php echo $brand ? '&brand=' . urlencode($brand) : ''; ?><?php echo $discount ? '&discount=' . $discount : ''; ?>"
                                class="<?php echo $category == 'all' ? 'text-purple-600 font-semibold' : 'text-gray-700'; ?> hover:text-purple-600 transition">
                                All Categories
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="?q=<?php echo urlencode($search_query); ?>&category=<?php echo $cat['category_id']; ?>&sort=<?php echo $sort; ?><?php echo $price_min ? '&price_min=' . $price_min : ''; ?><?php echo $price_max ? '&price_max=' . $price_max : ''; ?><?php echo $brand ? '&brand=' . urlencode($brand) : ''; ?><?php echo $discount ? '&discount=' . $discount : ''; ?>"
                                    class="<?php echo $category == $cat['category_id'] ? 'text-purple-600 font-semibold' : 'text-gray-700'; ?> hover:text-purple-600 transition">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Price Filter -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-3">
                    <h3 class="font-bold text-lg mb-4 text-gray-700 border-b border-gray-200">PRICE (GHC)</h3>
                    <div id="price-slider" class="mb-4"></div>
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span id="price-min-display"><?php echo $min_price; ?></span>
                        <span id="price-max-display"><?php echo $max_price; ?></span>
                    </div>
                    <form action="search.php" method="GET" id="priceFilterForm">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                        <input type="hidden" name="category" value="<?php echo $category; ?>">
                        <input type="hidden" name="brand" value="<?php echo $brand; ?>">
                        <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                        <input type="hidden" name="discount" value="<?php echo $discount; ?>">
                        <input type="hidden" name="price_min" id="priceMinInput" value="<?php echo $price_min ?: $min_price; ?>">
                        <input type="hidden" name="price_max" id="priceMaxInput" value="<?php echo $price_max ?: $max_price; ?>">
                        <button type="submit" class="mt-2 w-full bg-purple-600 font-medium text-white py-2 rounded-lg hover:bg-purple-700 transition">
                            Apply Price Filter
                        </button>
                    </form>
                </div>

                <!-- Discount Percentage Filter -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-3">
                    <h3 class="font-bold text-lg mb-4 text-gray-700 border-b border-gray-200">DISCOUNT PERCENTAGE</h3>
                    <div class="space-y-2">
                        <?php
                        $discount_ranges = [
                            '50' => '50% or more',
                            '40' => '40% or more',
                            '30' => '30% or more',
                            '20' => '20% or more',
                            '10' => '10% or more'
                        ];
                        ?>
                        <?php foreach ($discount_ranges as $discount_value => $discount_label): ?>
                            <div class="flex items-center">
                                <input type="checkbox"
                                    id="discount-<?php echo $discount_value; ?>"
                                    class="rounded text-purple-600 focus:ring-purple-500"
                                    onchange="filterByDiscount(this, <?php echo $discount_value; ?>)"
                                    <?php echo ($discount >= $discount_value) ? 'checked' : ''; ?>>
                                <label for="discount-<?php echo $discount_value; ?>" class="ml-2 text-gray-700 cursor-pointer">
                                    <?php echo $discount_label; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Brand Filter -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-bold text-lg mb-4 text-gray-700 border-b border-gray-200">BRAND</h3>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="checkbox"
                                id="brand-all"
                                class="rounded text-purple-600 focus:ring-purple-500"
                                onchange="filterByBrand('')"
                                <?php echo empty($brand) ? 'checked' : ''; ?>>
                            <label for="brand-all" class="ml-2 text-gray-700 cursor-pointer">All Brands</label>
                        </div>
                        <?php foreach ($brands as $brand_item): ?>
                            <div class="flex items-center">
                                <input type="checkbox"
                                    id="brand-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $brand_item))); ?>"
                                    class="rounded text-purple-600 focus:ring-purple-500"
                                    onchange="filterByBrand('<?php echo htmlspecialchars($brand_item); ?>')"
                                    <?php echo $brand === $brand_item ? 'checked' : ''; ?>>
                                <label for="brand-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $brand_item))); ?>" class="ml-2 text-gray-700 cursor-pointer">
                                    <?php echo htmlspecialchars($brand_item); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="lg:w-3/4">
                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-3">
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div class="mb-4 md:mb-0">
                            <h1 class="text-xl md:text-2xl font-bold text-gray-700">Search Results</h1>
                            <p class="text-gray-600 mt-1 text-sm md:text-base"><?php echo $total_results; ?> products found</p>
                        </div>
                        <div class="flex flex-col xs:flex-row gap-2 md:gap-4 w-full md:w-auto">
                            <!-- Brand Sort -->
                            <div class="relative flex-1 md:flex-none">
                                <select class="appearance-none bg-white border border-gray-300 rounded-lg py-2 px-3 md:px-4 pr-8 focus:outline-none focus:border-purple-500 text-sm w-full" onchange="sortByBrand(this.value)">
                                    <option value="">All Brands</option>
                                    <?php foreach ($brands as $brand_item): ?>
                                        <option value="<?php echo htmlspecialchars($brand_item); ?>" <?php echo $brand === $brand_item ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand_item); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Price Sort -->
                            <div class="relative flex-1 md:flex-none">
                                <select class="appearance-none bg-white border border-gray-300 rounded-lg py-2 px-3 md:px-4 pr-8 focus:outline-none focus:border-purple-500 text-sm w-full" onchange="sortByPrice(this.value)">
                                    <option value="">Sort by Price</option>
                                    <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="bg-white rounded-lg shadow-sm p-2 md:p-4 mb-3">
                    <?php if (count($products) > 0): ?>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-1">
                            <?php foreach ($products as $product):
                                $discounted_price = $functions->calculateDiscountedPrice($product['price'], $product['discount']);
                                $discount_percentage = $product['discount'] > 0 ? round($product['discount']) : 0;
                                $product_name = htmlspecialchars($product['name']);
                                $is_in_wishlist = isset($_SESSION['user_id']) ? $functions->isInWishlist($_SESSION['user_id'], $product['product_id']) : false;
                            ?>
                                <div class="bg-white rounded-md overflow-hidden product-card transition-shadow duration-300 hover:shadow-sm relative group">
                                    <div class="p-3 relative">
                                        <div class="product-image-container relative overflow-hidden rounded-lg bg-gray-100">  
                                        <!-- Wishlist Button -->
                                        <button onclick="toggleWishlist(<?php echo $product['product_id']; ?>, this)"
                                            class="absolute top-3 right-3 z-10 w-8 h-8 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-all duration-300 group/wishlist"
                                            data-in-wishlist="<?php echo $is_in_wishlist ? 'true' : 'false'; ?>">
                                            <svg class="w-4 h-4 <?php echo $is_in_wishlist ? 'text-red-500 fill-red-500' : 'text-gray-600 group-hover/wishlist:text-red-500'; ?> transition-colors"
                                                fill="<?php echo $is_in_wishlist ? 'currentColor' : 'none'; ?>"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                            </svg>
                                        </button>

                                        <!-- Discount Badge -->
                                        <?php if ($discount_percentage > 0): ?>
                                            <div class="absolute top-3 left-3 z-10">
                                                <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">
                                                    <?php echo $discount_percentage; ?>% OFF
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Product Image -->
                                        <a href="product.php?id=<?php echo $product['product_id']; ?>" ></a>
                                          <img src="<?php echo $functions->getProductImage($product['main_image']); ?>"
                                                    alt="<?php echo $product_name; ?>"
                                                    class="product-image group-hover:scale-105 transition-transform duration-300">
                                        </a>
</div>
                                        <!-- Product Name -->
                                        <?php
                                        $product_name_clean = html_entity_decode($product_name);
                                        $product_name_trunc = strlen($product_name_clean) > 50
                                            ? substr($product_name_clean, 0, 50) . '...'
                                            : $product_name_clean;

                                        ?>
                                        <a href="product.php?id=<?php echo $product['product_id']; ?>" class="block mb-1 group/title">
                                            <h3 class="font-semibold text-xs text-gray-700 line-clamp-2 group-hover/title:text-purple-600 transition-colors">
                                                <?php echo $product_name_trunc; ?>
                                            </h3>
                                        </a>

                                        <!-- Price Section -->
                                        <div class="flex flex-col mb-1">
                                            <span class="text-md font-semibold text-gray-800">
                                                GHC <?php echo number_format($discounted_price, 2); ?>
                                            </span>
                                            <?php if ($discount_percentage > 0): ?>
                                                <span class="text-sm text-gray-500 line-through">
                                                    GHC <?php echo number_format($product['price'], 2); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Add to Cart Button -->
                                        <div class="lg:opacity-0 lg:group-hover:opacity-100 lg:transform lg:translate-y-2 lg:group-hover:translate-y-0 transition-all duration-300">
                                            <button onclick="addToCart(<?php echo $product['product_id']; ?>)"
                                                class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition font-medium text-sm flex items-center justify-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Sort by Section -->
                        <div class="mt-6 md:mt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div class="text-sm text-gray-600 order-2 sm:order-1">
                                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                            </div>
                            <div class="flex items-center space-x-2 w-full sm:w-auto order-1 sm:order-2">
                                <span class="text-gray-600 text-sm whitespace-nowrap">Sort by:</span>
                                <select class="appearance-none bg-white border border-gray-300 rounded-lg py-2 px-3 md:px-4 pr-8 focus:outline-none focus:border-purple-500 text-sm w-full sm:w-auto" onchange="sortBy(this.value)">
                                    <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                                    <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                </select>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="flex justify-center mt-6 md:mt-8">
                                <nav class="flex flex-wrap justify-center gap-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?q=<?php echo urlencode($search_query); ?>&category=<?php echo $category; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page - 1; ?><?php echo $price_min ? '&price_min=' . $price_min : ''; ?><?php echo $price_max ? '&price_max=' . $price_max : ''; ?><?php echo $brand ? '&brand=' . urlencode($brand) : ''; ?><?php echo $discount ? '&discount=' . $discount : ''; ?>"
                                            class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                                            ← Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <a href="?q=<?php echo urlencode($search_query); ?>&category=<?php echo $category; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?><?php echo $price_min ? '&price_min=' . $price_min : ''; ?><?php echo $price_max ? '&price_max=' . $price_max : ''; ?><?php echo $brand ? '&brand=' . urlencode($brand) : ''; ?><?php echo $discount ? '&discount=' . $discount : ''; ?>"
                                            class="px-3 py-2 rounded-lg transition text-sm font-medium min-w-[40px] text-center <?php echo $i == $page ? 'bg-purple-600 text-white shadow-lg' : 'bg-white border border-gray-300 hover:bg-gray-50 text-gray-700'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <a href="?q=<?php echo urlencode($search_query); ?>&category=<?php echo $category; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page + 1; ?><?php echo $price_min ? '&price_min=' . $price_min : ''; ?><?php echo $price_max ? '&price_max=' . $price_max : ''; ?><?php echo $brand ? '&brand=' . urlencode($brand) : ''; ?><?php echo $discount ? '&discount=' . $discount : ''; ?>"
                                            class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                                            Next →
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- No Results -->
                        <div class="bg-white rounded-lg shadow-sm p-6 md:p-8 text-center">
                            <div class="max-w-md mx-auto">
                                <div class="w-20 h-20 md:w-24 md:h-24 mx-auto mb-4 md:mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-10 h-10 md:w-12 md:h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-3">No products found</h3>
                                <p class="text-gray-600 mb-4 md:mb-6 text-sm md:text-base">
                                    We couldn't find any products matching <strong class="text-purple-600">"<?php echo htmlspecialchars($search_query); ?>"</strong>
                                </p>
                                <a href="products.php" class="inline-block bg-purple-600 text-white px-5 py-2.5 md:px-6 md:py-3 rounded-lg hover:bg-purple-700 transition font-semibold text-sm md:text-base">
                                    Browse All Products
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</section>

<!-- Mobile Filters Overlay -->
<div id="mobileFiltersOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden lg:hidden transition-opacity duration-300">
    <div class="absolute inset-y-0 left-0 w-80 bg-white transform transition-transform duration-300 -translate-x-full" id="mobileFiltersSidebar">
        <div class="h-full overflow-y-auto">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gradient-to-r from-purple-600 to-indigo-600 text-white">
                <h2 class="text-lg font-semibold">Search Filters</h2>
                <button onclick="toggleMobileFilters()" class="text-white hover:text-gray-200 p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <!-- Mobile filters content - same as desktop but adjusted for mobile -->
                <div class="space-y-4">
                    <!-- Category Filter -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="font-bold text-lg mb-3">CATEGORY</h3>
                        <ul class="space-y-2">
                            <li>
                                <a href="?q=<?php echo urlencode($search_query); ?>&category=all&sort=<?php echo $sort; ?><?php echo $price_min ? '&price_min=' . $price_min : ''; ?><?php echo $price_max ? '&price_max=' . $price_max : ''; ?><?php echo $brand ? '&brand=' . urlencode($brand) : ''; ?><?php echo $discount ? '&discount=' . $discount : ''; ?>"
                                    class="<?php echo $category == 'all' ? 'text-purple-600 font-semibold' : 'text-gray-700'; ?> hover:text-purple-600 transition block py-1">
                                    All Categories
                                </a>
                            </li>
                            <?php foreach ($categories as $cat): ?>
                                <li>
                                    <a href="?q=<?php echo urlencode($search_query); ?>&category=<?php echo $cat['category_id']; ?>&sort=<?php echo $sort; ?><?php echo $price_min ? '&price_min=' . $price_min : ''; ?><?php echo $price_max ? '&price_max=' . $price_max : ''; ?><?php echo $brand ? '&brand=' . urlencode($brand) : ''; ?><?php echo $discount ? '&discount=' . $discount : ''; ?>"
                                        class="<?php echo $category == $cat['category_id'] ? 'text-purple-600 font-semibold' : 'text-gray-700'; ?> hover:text-purple-600 transition block py-1">
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-3">
                        <h3 class="font-bold text-lg mb-4">DISCOUNT PERCENTAGE</h3>
                        <div class="space-y-2">
                            <?php
                            $discount_ranges = [
                                '50' => '50% or more',
                                '40' => '40% or more',
                                '30' => '30% or more',
                                '20' => '20% or more',
                                '10' => '10% or more'
                            ];
                            ?>
                            <?php foreach ($discount_ranges as $discount_value => $discount_label): ?>
                                <div class="flex items-center">
                                    <input type="checkbox"
                                        id="discount-<?php echo $discount_value; ?>"
                                        class="rounded text-purple-600 focus:ring-purple-500"
                                        onchange="filterByDiscount(this, <?php echo $discount_value; ?>)"
                                        <?php echo ($discount >= $discount_value) ? 'checked' : ''; ?>>
                                    <label for="discount-<?php echo $discount_value; ?>" class="ml-2 text-gray-700 cursor-pointer">
                                        <?php echo $discount_label; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Brand Filter -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="font-bold text-lg mb-4">BRAND</h3>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input type="checkbox"
                                    id="brand-all"
                                    class="rounded text-purple-600 focus:ring-purple-500"
                                    onchange="filterByBrand('')"
                                    <?php echo empty($brand) ? 'checked' : ''; ?>>
                                <label for="brand-all" class="ml-2 text-gray-700 cursor-pointer">All Brands</label>
                            </div>
                            <?php foreach ($brands as $brand_item): ?>
                                <div class="flex items-center">
                                    <input type="checkbox"
                                        id="brand-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $brand_item))); ?>"
                                        class="rounded text-purple-600 focus:ring-purple-500"
                                        onchange="filterByBrand('<?php echo htmlspecialchars($brand_item); ?>')"
                                        <?php echo $brand === $brand_item ? 'checked' : ''; ?>>
                                    <label for="brand-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $brand_item))); ?>" class="ml-2 text-gray-700 cursor-pointer">
                                        <?php echo htmlspecialchars($brand_item); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
     .product-image-container {
        aspect-ratio: 1/1;
        position: relative;
        flex-shrink: 0;
    }

    .product-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Modern Rounded Price Slider Styles */
    .noUi-target {
        background: #f3f4f6;
        border-radius: 10px;
        border: none;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .noUi-connect {
        background: linear-gradient(135deg, #9333ea, #7c3aed);
        border-radius: 10px;
    }

    .noUi-handle {
        background: white;
        border: 3px solid #9333ea;
        border-radius: 50%;
        box-shadow: 0 2px 6px rgba(147, 51, 234, 0.3);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .noUi-handle:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(147, 51, 234, 0.4);
    }

    .noUi-handle:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.2);
    }

    .noUi-handle:after {
        display: none;
    }

    .noUi-handle:before {
        display: none;
    }

    .noUi-horizontal {
        height: 8px;
    }

    .noUi-horizontal .noUi-handle {
        width: 20px;
        height: 20px;
        right: -10px;
        top: -6px;
    }
</style>

<script>
    // Mobile Filters Toggle
    function toggleMobileFilters() {
        const overlay = document.getElementById('mobileFiltersOverlay');
        const sidebar = document.getElementById('mobileFiltersSidebar');
        const arrow = document.getElementById('filter-arrow');

        if (overlay.classList.contains('hidden')) {
            overlay.classList.remove('hidden');
            setTimeout(() => {
                sidebar.classList.remove('-translate-x-full');
            }, 50);
            if (arrow) arrow.classList.add('rotate-180');
        } else {
            sidebar.classList.add('-translate-x-full');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300);
            if (arrow) arrow.classList.remove('rotate-180');
        }
    }

    // Filter functions
    function filterByDiscount(checkbox, discountValue) {
        const url = new URL(window.location.href);
        if (checkbox.checked) {
            url.searchParams.set('discount', discountValue);
        } else {
            url.searchParams.delete('discount');
        }
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    function filterByBrand(brandValue) {
        const url = new URL(window.location.href);
        if (brandValue) {
            url.searchParams.set('brand', brandValue);
        } else {
            url.searchParams.delete('brand');
        }
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    function sortByBrand(brandValue) {
        const url = new URL(window.location.href);
        if (brandValue) {
            url.searchParams.set('brand', brandValue);
        } else {
            url.searchParams.delete('brand');
        }
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    function sortByPrice(sortValue) {
        const url = new URL(window.location.href);
        if (sortValue) {
            url.searchParams.set('sort', sortValue);
        }
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    function sortBy(sortValue) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sortValue);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    // Enhanced Wishlist Functionality
    async function toggleWishlist(productId, button) {
        <?php if (!isset($_SESSION['user_id'])): ?>
            window.location.href = 'signin.php?redirect=' + encodeURIComponent(window.location.href);
            return;
        <?php endif; ?>

        const isInWishlist = button.getAttribute('data-in-wishlist') === 'true';

        try {
            const response = await fetch('ajax/wishlist.php?action=' + (isInWishlist ? 'remove_from_wishlist' : 'add_to_wishlist'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId
                })
            });

            const data = await response.json();

            if (data.success) {
                // Update button state
                button.setAttribute('data-in-wishlist', !isInWishlist);
                const heartIcon = button.querySelector('svg');

                if (!isInWishlist) {
                    // Added to wishlist
                    heartIcon.classList.remove('text-gray-600');
                    heartIcon.classList.add('text-red-500', 'fill-red-500');
                    showNotification('Added to wishlist!', 'success');
                } else {
                    // Removed from wishlist
                    heartIcon.classList.remove('text-red-500', 'fill-red-500');
                    heartIcon.classList.add('text-gray-600');
                    heartIcon.setAttribute('fill', 'none');
                    showNotification('Removed from wishlist', 'info');
                }

                // Add animation
                button.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    button.style.transform = 'scale(1)';
                }, 200);

            } else {
                showNotification(data.message || 'Error updating wishlist', 'error');
            }
        } catch (error) {
            console.error('Error updating wishlist:', error);
            showNotification('Error updating wishlist', 'error');
        }
    }

    // Price slider initialization
    function initPriceSlider() {
        const priceMin = <?php echo $min_price; ?>;
        const priceMax = <?php echo $max_price; ?>;
        const currentMin = <?php echo $price_min ?: $min_price; ?>;
        const currentMax = <?php echo $price_max ?: $max_price; ?>;

        if (typeof noUiSlider !== 'undefined') {
            const slider = document.getElementById('price-slider');
            const priceMinInput = document.getElementById('priceMinInput');
            const priceMaxInput = document.getElementById('priceMaxInput');
            const priceMinDisplay = document.getElementById('price-min-display');
            const priceMaxDisplay = document.getElementById('price-max-display');

            if (slider) {
                noUiSlider.create(slider, {
                    start: [currentMin, currentMax],
                    connect: true,
                    range: {
                        'min': priceMin,
                        'max': priceMax
                    },
                    step: 1
                });

                slider.noUiSlider.on('update', function(values) {
                    const minValue = Math.round(values[0]);
                    const maxValue = Math.round(values[1]);

                    priceMinInput.value = minValue;
                    priceMaxInput.value = maxValue;
                    priceMinDisplay.textContent = minValue;
                    priceMaxDisplay.textContent = maxValue;
                });
            }
        }
    }

    // Load required libraries and initialize
    function loadPriceSliderLibrary() {
        if (typeof noUiSlider === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js';
            script.onload = initPriceSlider;
            document.head.appendChild(script);

            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css';
            document.head.appendChild(link);
        } else {
            initPriceSlider();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadPriceSliderLibrary();

        // Close mobile filters when clicking overlay
        document.getElementById('mobileFiltersOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                toggleMobileFilters();
            }
        });
    });

    // Close mobile filters with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const overlay = document.getElementById('mobileFiltersOverlay');
            if (!overlay.classList.contains('hidden')) {
                toggleMobileFilters();
            }
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>