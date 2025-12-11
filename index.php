<?php
// Include header and database connection
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/settings_helper.php';

// Initialize database first
 $database = new Database();

// Load free-shipping settings from DB
 $pdo = $database->getConnection();
 $free_shipping_threshold = floatval(SettingsHelper::get($pdo, 'free_shipping_threshold', 0));

// Initialize functions and set database
 $functions = new Functions();
 $functions->setDatabase($database);

 $free_shipping_text = $free_shipping_threshold > 0
    ? 'free shipping on orders over <b>' . $functions->formatPrice($free_shipping_threshold) . '</b>'
    : 'free shipping today';

// Get banners from database
 $banners = $functions->getActiveBanners();

// Get featured products from database
 $featured_products = $functions->getFeaturedProducts(12);

// Get all categories from database
 $categories = $functions->getAllCategories();

// Get popular products (best sellers)
 $popular_products = $functions->getPopularProducts(12);

// Get new arrivals
 $new_arrivals = $functions->getNewArrivals(8);

// Get top selling products by category
 $category_products = [];
foreach ($categories as $category) {
    $category_products[$category['category_id']] = [
        'category' => $category,
        'products' => $functions->getProductsByCategory($category['category_id'], 8)
    ];
}

// Get testimonials
 $testimonials = $functions->getTestimonials(3);

// Get featured brands
 $featured_brands = $functions->getFeaturedBrands();
?>
<?php require_once 'includes/header.php'; ?>

<!-- ===== Banner Carousel ===== -->
<?php if (!empty($banners)): ?>
<section class="relative py-0 overflow-hidden">
    <div class="relative h-96 overflow-hidden" id="bannerSlider">
        <div class="flex transition-transform duration-700 ease-out h-full" id="bannerTrack">
            <?php foreach ($banners as $banner): ?>
                <div class="min-w-full h-full relative group">
                    <a href="<?php echo htmlspecialchars($banner['link_url'] ?? '#'); ?>" class="block h-full">
                        <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" 
                            alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                            class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/30 group-hover:bg-black/40 transition-colors"></div>
                        <div class="absolute inset-0 flex flex-col justify-center items-center text-center text-white p-6">
                            <h2 class="text-4xl lg:text-5xl font-bold mb-3"><?php echo htmlspecialchars($banner['title']); ?></h2>
                            <p class="text-lg lg:text-xl mb-6 max-w-2xl"><?php echo htmlspecialchars($banner['description']); ?></p>
                            <button onclick="event.preventDefault(); event.stopPropagation(); location.href='<?php echo htmlspecialchars($banner['link_url'] ?? '#'); ?>'" 
                                class="bg-purple-600 text-white px-8 py-3 rounded-lg hover:bg-purple-700 transition font-semibold">
                                <?php echo htmlspecialchars($banner['button_text']); ?>
                            </button>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Banner Controls -->
    <div class="absolute bottom-6 left-6 lg:left-10 right-6 lg:right-10 flex items-center justify-between z-10">
        <div class="flex gap-2">
            <button id="bannerPrev" class="bg-white/80 hover:bg-white text-gray-900 w-10 h-10 rounded-full flex items-center justify-center transition">‹</button>
            <button id="bannerNext" class="bg-white/80 hover:bg-white text-gray-900 w-10 h-10 rounded-full flex items-center justify-center transition">›</button>
        </div>
        <?php if (count($banners) > 1): ?>
            <div class="flex gap-2">
                <?php for ($i = 0; $i < count($banners); $i++): ?>
                    <button class="bannerDot w-2 h-2 rounded-full <?php echo $i === 0 ? 'bg-white' : 'bg-white/50'; ?> hover:bg-white transition" data-index="<?php echo $i; ?>"></button>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- ===== Hero Section ===== -->
<section class="relative py-6 lg:py-12 overflow-hidden bg-gradient-to-br from-purple-50 to-pink-50">
    <div class="container mx-auto px-2 max-w-6xl ">
        <div class="grid lg:grid-cols-2 gap-8 items-center">
            <div class="text-center lg:text-left">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-800 mb-4 leading-tight">
                    Discover Amazing Products at <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600">Great Prices</span>
                </h1>
                <p class="text-base text-gray-600 mb-6 max-w-lg mx-auto lg:mx-0">
                    Shop latest trends with <?php echo $free_shipping_text; ?>. Quality products, unbeatable prices.
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
                    <a href="#featured-products" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition transform hover:scale-105 text-center font-medium shadow-lg">
                        Shop Now
                    </a>
                    <a href="#categories" class="border border-purple-600 text-purple-600 px-6 py-3 rounded-lg hover:bg-purple-600 hover:text-white transition text-center font-medium">
                        Browse Categories
                    </a>
                </div>
            </div>
            <div class="relative order-first lg:order-last">
                <div class="relative rounded-2xl overflow-hidden shadow-xl">
                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                        alt="Shopping" class="w-full max-w-md mx-auto">
                    <div class="absolute -bottom-4 -right-4 bg-white p-3 rounded-lg shadow-lg border transform rotate-3 hover:rotate-0 transition-transform duration-300">
                        <p class="text-xs text-gray-600 font-medium">Limited Time</p>
                        <p class="text-lg font-bold text-purple-600">50% OFF</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Animated background elements -->
    <div class="absolute top-10 left-10 w-20 h-20 bg-purple-200 rounded-full opacity-20 blur-xl"></div>
    <div class="absolute bottom-10 right-10 w-32 h-32 bg-pink-200 rounded-full opacity-20 blur-2xl"></div>
    <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-yellow-200 rounded-full opacity-10 blur-lg"></div>
</section>

    <!-- ===== Featured Carousel ===== -->
    <?php if (!empty($featured_products)): ?>
    <section class="py-6 container mx-auto px-2 max-w-6xl">
        <div class="bg-white rounded-sm shadow-sm p-4 lg:p-6 relative overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Top Featured Picks</h2>
                    <p class="text-gray-600 text-sm">Swipe through our best 5 deals</p>
                </div>
                <div class="flex items-center gap-2">
                    <button id="featPrev" class="w-9 h-9 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-100 focus:outline-none">‹</button>
                    <button id="featNext" class="w-9 h-9 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-100 focus:outline-none">›</button>
                </div>
            </div>

            <div class="overflow-hidden" id="featuredSlider">
                <div class="flex transition-transform duration-500 ease-out" id="featuredTrack">
                    <?php foreach (array_slice($featured_products, 0, 5) as $product):
                        $final_price = $functions->calculateDiscountedPrice($product['price'], $product['discount']);
                        $truncated_name = (strlen($product['name']) > 40) ? substr($product['name'], 0, 40) . '...' : $product['name'];
                    ?>
                        <a href="product.php?product=<?php echo $product['slug']; ?>" class="block min-w-full lg:min-w-1/2 xl:min-w-1/3 px-2">
                            <div class="flex flex-col lg:flex-row gap-4 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 h-full">
                                <div class="flex-1 flex items-center justify-center bg-white rounded-lg p-3">
                                    <img src="<?php echo $functions->getProductImage($product['main_image']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        class="w-40 h-40 object-contain">
                                </div>
                                <div class="flex-[2] flex flex-col justify-between gap-3">
                                    <div>
                                        <span class="inline-block bg-purple-100 text-purple-700 text-xs font-semibold px-2 py-1 rounded">Featured</span>
                                        <h3 class="text-lg font-semibold text-gray-800 mt-2 line-clamp-2"><?php echo $truncated_name; ?></h3>
                                        <p class="text-sm text-gray-600 line-clamp-2"><?php echo $product['short_description'] ?? 'Shop this great find picked just for you.'; ?></p>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-xl font-bold text-purple-700"><?php echo $functions->formatPrice($final_price); ?></span>
                                            <?php if ($product['discount'] > 0): ?>
                                                <span class="text-sm text-gray-400 line-through"><?php echo $functions->formatPrice($product['price']); ?></span>
                                                <span class="text-xs font-semibold text-red-500">-<?php echo round($product['discount']); ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                        <button onclick="event.preventDefault(); event.stopPropagation(); addToCart(<?php echo $product['product_id']; ?>)" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition text-sm flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            <span class="hidden sm:inline">Add to Cart</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== Quick Categories Section ===== -->
    <section id="categories" class="py-6 container mx-auto px-2 max-w-6xl">
        <div class=" bg-white rounded-sm shadow-sm py-4">
            <div class="text-center mb-8">
                <h2 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-3">Shop by Category</h2>
                <p class="text-gray-600 text-sm">Quickly find what you're looking for</p>
            </div>

            <?php if (!empty($categories)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 max-w-4xl mx-auto">
                    <?php
                    // Define category icons and colors
                    $category_styles = [
                        'Electronics' => ['icon' => '📱', 'color' => 'from-blue-500 to-blue-600'],
                        'Fashion' => ['icon' => '👕', 'color' => 'from-pink-500 to-pink-600'],
                        'Groceries' => ['icon' => '🛒', 'color' => 'from-green-500 to-green-600'],
                        'Perfumes' => ['icon' => '💎', 'color' => 'from-purple-500 to-purple-600'],
                        'Home' => ['icon' => '🏠', 'color' => 'from-orange-500 to-orange-600'],
                        'Sports' => ['icon' => '⚽', 'color' => 'from-red-500 to-red-600'],
                        'Health & Beauty' => ['icon' => '💄', 'color' => 'from-pink-400 to-pink-500'],
                        'Beauty' => ['icon' => '💄', 'color' => 'from-pink-400 to-pink-500'],
                        'Toys' => ['icon' => '🧸', 'color' => 'from-yellow-500 to-yellow-600']
                        
                    ];

                    foreach ($categories as $category):
                        $category_name = $category['category_name'];
                        $style = $category_styles[$category_name] ?? ['icon' => '🛍️', 'color' => 'from-gray-500 to-gray-600'];
                        $product_count = $functions->getProductCountByCategory($category['category_id']);
                    ?>
                        <a href="<?php echo $functions->getCategoryLink($category['category_id']); ?>"
                            class="category-card group block text-center p-4 rounded-lg bg-gradient-to-br <?php echo $style['color']; ?> text-white hover:shadow-md transition-all duration-300 transform hover:scale-105">
                            <div class="text-2xl mb-2 transform group-hover:scale-110 transition-transform"><?php echo $style['icon']; ?></div>
                            <h3 class="font-semibold text-sm mb-1"><?php echo $category_name; ?></h3>
                            <p class="text-xs opacity-90"><?php echo $product_count; ?> items</p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">No categories available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===== Featured Products Section ===== -->
    <section id="featured-products" class="container mx-auto px-2 max-w-6xl py-4">
        <div class="bg-white rounded-sm shadow-sm p-4">
            <div class="flex justify-between items-center mb-8 border-b pb-4">
                <div>
                    <h4 class="text-xl lg:text-xl font-bold text-gray-800 mb-1">Featured Products</h2>
                        <p class="text-gray-600 text-sm">Handpicked items just for you</p>
                </div>
                <a href="products.php?filter=featured" class="text-purple-600 hover:text-purple-700 text-sm font-medium flex items-center gap-1">
                    See All
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>

            <?php if (!empty($featured_products)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                    <?php foreach ($featured_products as $product):
                        $final_price = $functions->calculateDiscountedPrice($product['price'], $product['discount']);
                        $avg_rating = $functions->getAverageRating($product['product_id']);
                        $review_count = $functions->getProductReviews($product['product_id'], 1) ? count($functions->getProductReviews($product['product_id'], 1)) : 0;
                        // Truncate product name to 20 characters
                        $truncated_name = (strlen($product['name']) > 20) ? substr($product['name'], 0, 20) . '...' : $product['name'];
                    ?>
                        <div class="product-card bg-white rounded-lg overflow-hidden">
                            <div class="relative">
                                <div class="product-image-container relative overflow-hidden rounded-lg bg-white">
                                    <?php if ($product['discount'] > 0): ?>
                                        <span class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded z-10">-<?php echo $product['discount']; ?>%</span>
                                    <?php endif; ?>

                            

                                    <a href="product.php?product=<?php echo $product['slug']; ?>">
                                        <img src="<?php echo $functions->getProductImage($product['main_image']); ?>"
                                            alt="<?php echo $truncated_name; ?>"
                                            class="product-image group-hover:scale-105 transition-transform duration-300">
                                    </a>
                                </div>

                                <div class="p-3">

                                    <a href="product.php?product=<?php echo $product['slug']; ?>" class="block">
                                        <h3 class="text-gray-800 text-sm leading-tight line-clamp-2 hover:text-purple-600 transition" title="<?php echo htmlspecialchars($product['name']); ?>"><?php echo $truncated_name; ?></h3>
                                    </a>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-base font-bold text-purple-600"><?php echo $functions->formatPrice($final_price); ?></span>
                                            <?php if ($product['discount'] > 0): ?>
                                                <span class="text-xs text-gray-400 line-through "><?php echo $functions->formatPrice($product['price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <!-- <button onclick="addToCart(<?php echo $product['product_id']; ?>)"
                                        class="bg-purple-600 text-white p-2 rounded-full hover:bg-purple-700 transition transform hover:scale-110">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </button> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">No featured products available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===== Popular Products Section ===== -->
    <?php if (!empty($popular_products)): ?>
        <section class="container mx-auto px-2 max-w-6xl py-2">
            <div class="bg-white rounded-sm shadow-sm p-4">
                <div class="flex justify-between items-center mb-8 border-b pb-4">
                    <div>
                        <h2 class="text-xl lg:text-xl font-bold text-gray-800 mb-1">Popular Products</h2>
                        <p class="text-gray-600 text-sm">What customers are loving right now</p>
                    </div>
                    <a href="products.php?sort=popular" class="text-purple-600 hover:text-purple-700 text-sm font-medium flex items-center gap-1">
                        See All
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                    <?php foreach ($popular_products as $product):
                        $final_price = $functions->calculateDiscountedPrice($product['price'], $product['discount']);
                        $avg_rating = $functions->getAverageRating($product['product_id']);
                        $review_count = $functions->getProductReviews($product['product_id'], 1) ? count($functions->getProductReviews($product['product_id'], 1)) : 0;
                        // Truncate product name to 20 characters
                        $truncated_name = (strlen($product['name']) > 20) ? substr($product['name'], 0, 20) . '...' : $product['name'];
                    ?>
                        <div class="product-card bg-white rounded-lg overflow-hidden">
                            <div class="relative">
                                <div class="product-image-container relative overflow-hidden rounded-lg bg-white">
                                    <?php if ($product['discount'] > 0): ?>
                                        <span class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded z-10">-<?php echo $product['discount']; ?>%</span>
                                    <?php endif; ?>

                                    <!-- <?php
                                    $days_old = floor((time() - strtotime($product['date_added'])) / (60 * 60 * 24));
                                    if ($days_old <= 7):
                                    ?>
                                        <span class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded z-10">NEW</span>
                                    <?php endif; ?> -->

                                    <a href="product.php?product=<?php echo $product['slug']; ?>">
                                        <img src="<?php echo $functions->getProductImage($product['main_image']); ?>"
                                            alt="<?php echo $truncated_name; ?>"
                                            class="product-image group-hover:scale-105 transition-transform duration-300">
                                    </a>
                                </div>

                                <div class="p-3">
                                    <div class="mb-2">
                                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded"><?php echo $product['category_name']; ?></span>
                                    </div>

                                    <a href="product.php?product=<?php echo $product['slug']; ?>" class="block">
                                        <h3 class="text-gray-800 text-sm leading-tight line-clamp-2 hover:text-purple-600 transition" title="<?php echo htmlspecialchars($product['name']); ?>"><?php echo $truncated_name; ?></h3>
                                    </a>

                                    <div class="flex items-center mt-1 mb-2">
                                        <div class="rating-stars text-yellow-400 text-xs">
                                            <?php echo str_repeat('★', round($avg_rating)) . str_repeat('☆', 5 - round($avg_rating)); ?>
                                        </div>
                                        <span class="text-xs text-gray-500 ml-1">(<?php echo $review_count; ?>)</span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-base font-bold text-purple-600"><?php echo $functions->formatPrice($final_price); ?></span>
                                            <?php if ($product['discount'] > 0): ?>
                                                <span class="text-xs text-gray-400 line-through "><?php echo $functions->formatPrice($product['price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <!-- <button onclick="addToCart(<?php echo $product['product_id']; ?>)"
                                        class="bg-purple-600 text-white p-2 rounded-full hover:bg-purple-700 transition transform hover:scale-110">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </button> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ===== Products by Category Sections ===== -->
    <!-- <?php foreach ($category_products as $category_data):
        if (!empty($category_data['products'])):
            $category = $category_data['category'];
            $products = $category_data['products'];
    ?>
            <section class="py-2">
                <div class="container mx-auto px-2 max-w-6xl bg-white rounded-lg py-6">
                    <div class="flex justify-between items-center mb-8 border-b pb-4">
                        <div>
                            <h2 class="text-xl lg:text-xl font-bold text-gray-800 mb-1"><?php echo $category['category_name']; ?></h2>
                            <p class="text-gray-600 text-sm">Best selling <?php echo strtolower($category['category_name']); ?> products</p>
                        </div>
                        <a href="<?php echo $functions->getCategoryLink($category['category_id']); ?>" class="text-purple-600 hover:text-purple-700 text-sm font-medium flex items-center gap-1">
                            See All
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>

                    <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-2">
                        <?php foreach ($products as $product):
                            $final_price = $functions->calculateDiscountedPrice($product['price'], $product['discount']);
                            $avg_rating = $functions->getAverageRating($product['product_id']);
                            $truncated_name = (strlen($product['name']) > 20) ? substr($product['name'], 0, 20) . '...' : $product['name'];
                        ?>
                            <div class="product-card bg-white rounded-lg overflow-hidden">
                                <div class="relative">
                                    <div class="product-image-container relative overflow-hidden rounded-lg bg-white">
                                        <?php if ($product['discount'] > 0): ?>
                                            <span class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded z-10">-<?php echo $product['discount']; ?>%</span>
                                        <?php endif; ?>

                                        <?php if ($product['is_new']): ?>
                                            <span class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded z-10">NEW</span>
                                        <?php endif; ?>

                                        <a href="product.php?product=<?php echo $product['slug']; ?>">
                                            <img src="<?php echo $functions->getProductImage($product['main_image']); ?>"
                                                alt="<?php echo $truncated_name; ?>"
                                                class="product-image group-hover:scale-105 transition-transform duration-300">
                                        </a>
                                    </div>

                                    <div class="p-3">
                                        <a href="product.php?product=<?php echo $product['slug']; ?>" class="block">
                                            <h3 class="text-gray-800 text-sm  leading-tight line-clamp-2 hover:text-purple-600 transition" title="<?php echo htmlspecialchars($product['name']); ?>"><?php echo $truncated_name; ?></h3>
                                        </a>

                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="text-xs md:text-base font-bold text-purple-600"><?php echo $functions->formatPrice($final_price); ?></span>
                                                <?php if ($product['discount'] > 0): ?>
                                                    <span class="text-xs text-gray-400 line-through "><?php echo $functions->formatPrice($product['price']); ?></span>
                                                <?php endif; ?>
                                            </div>
                
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
    <?php endif;
    endforeach; ?> -->

    <!-- ===== Special Offers Banner ===== -->
    <section class="py-8 bg-gradient-to-r from-purple-600 to-pink-600 text-white">
        <div class="container mx-auto px-2 max-w-6xl">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-center md:text-left">
                    <h3 class="text-xl font-bold mb-1">Summer Sale! Up to 50% Off</h3>
                    <p class="text-purple-100 text-sm">Limited time offer on selected items</p>
                </div>
                <div class="flex gap-3">
                    <a href="products.php?filter=discounted" class="bg-white text-purple-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition font-medium text-sm">
                        Shop Sale
                    </a>
                    <a href="products.php?sort=newest" class="border border-white text-white px-4 py-2 rounded-lg hover:bg-white hover:text-purple-600 transition font-medium text-sm">
                        New Arrivals
                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- ===== Why Choose Us Section ===== -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8">
                <h2 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-3">Why Shop With Us?</h2>
            </div>

            <div class="grid md:grid-cols-4 gap-6 max-w-4xl mx-auto">
                <div class="text-center p-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2">Quality Products</h3>
                    <p class="text-gray-600 text-sm">Carefully selected items for your satisfaction</p>
                </div>

                <div class="text-center p-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2">Free Shipping</h3>
                    <p class="text-gray-600 text-sm">On orders over <strong>GHS500</strong></p>
                </div>

                <div class="text-center p-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2">Secure Payment</h3>
                    <p class="text-gray-600 text-sm">100% secure checkout process</p>
                </div>

                <div class="text-center p-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2">24/7 Support</h3>
                    <p class="text-gray-600 text-sm">Always here to help you</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== Customer Reviews Section ===== -->
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8">
                <h2 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-3">What Our Customers Say</h2>
                <p class="text-gray-600 text-sm">Real reviews from real customers</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <?php foreach ($testimonials as $review): ?>
                    <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                <?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?>
                            </div>
                            <div class="ml-3">
                                <h4 class="font-semibold text-gray-800 text-sm"><?php echo $review['customer_name']; ?></h4>
                                <div class="rating-stars text-yellow-400 text-xs">
                                    <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm italic">
                            "<?php echo $review['comment']; ?>"
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== Newsletter Section ===== -->
    <section class="py-12 bg-gradient-to-br from-purple-50 to-pink-50">
        <div class="container mx-auto px-4">
            <div class="max-w-2xl mx-auto text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-3">Stay Updated</h2>
                <p class="text-gray-600 text-sm mb-6">
                    Get the latest updates on new products and special offers
                </p>
                <form id="newsletterFormIndex" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                    <div class="flex-1">
                        <input type="email" name="email" placeholder="Enter your email" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 text-sm">
                    </div>
                    <button type="submit"
                        class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-medium text-sm flex items-center justify-center min-w-32 relative">
                        <span id="newsletterBtnTextIndex">Subscribe</span>
                        <svg id="newsletterBtnIconIndex" class="hidden w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                        <svg id="newsletterLoadingIndex" class="hidden w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>
                <p id="newsletterMessageIndex" class="text-sm mt-3 hidden"></p>
                <p class="text-xs text-gray-500 mt-2">We respect your privacy. Unsubscribe at any time.</p>
            </div>
        </div>
    </section>
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
        object-fit: contain;
        transition: transform 0.5s ease;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .category-card {
        min-height: 100px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .product-card {
        transition: all 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .rating-stars {
        letter-spacing: -1px;
    }
</style>

<script>
    (function () {
        const track = document.getElementById('featuredTrack');
        const slider = document.getElementById('featuredSlider');
        if (!track || !slider) return;

        const slides = Array.from(track.children);
        const prevBtn = document.getElementById('featPrev');
        const nextBtn = document.getElementById('featNext');
        let index = 0;

        const update = () => {
            const slideWidth = slider.clientWidth;
            track.style.transform = `translateX(-${index * slideWidth}px)`;
        };

        const clampIndex = () => {
            const maxIndex = Math.max(0, slides.length - 1);
            if (index < 0) index = maxIndex;
            if (index > maxIndex) index = 0;
        };

        prevBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            index -= 1;
            clampIndex();
            update();
        });

        nextBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            index += 1;
            clampIndex();
            update();
        });

        window.addEventListener('resize', update);
        update();
    })();
</script>

<script>
    // Add to cart function
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

    // Add to wishlist function
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

    // Newsletter subscription handler for index page
    document.addEventListener('DOMContentLoaded', function() {
        const newsletterFormIndex = document.getElementById('newsletterFormIndex');
        const newsletterMessageIndex = document.getElementById('newsletterMessageIndex');
        const newsletterBtnTextIndex = document.getElementById('newsletterBtnTextIndex');
        const newsletterBtnIconIndex = document.getElementById('newsletterBtnIconIndex');
        const newsletterLoadingIndex = document.getElementById('newsletterLoadingIndex');

        if (newsletterFormIndex) {
            newsletterFormIndex.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const email = formData.get('email');

                // Validate email
                if (!email || !isValidEmail(email)) {
                    showNewsletterMessageIndex('Please enter a valid email address.', 'error');
                    return;
                }

                // Show loading state
                setNewsletterLoadingIndex(true);

                try {
                    const response = await fetch('ajax/newsletter.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `email=${encodeURIComponent(email)}`
                    });

                    const data = await response.json();

                    if (data.success) {
                        showNewsletterMessageIndex(data.message, 'success');
                        newsletterFormIndex.reset();
                    } else {
                        showNewsletterMessageIndex(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Newsletter subscription error:', error);
                    showNewsletterMessageIndex('An error occurred. Please try again.', 'error');
                } finally {
                    setNewsletterLoadingIndex(false);
                }
            });
        }

        function setNewsletterLoadingIndex(loading) {
            const button = newsletterFormIndex.querySelector('button');
            if (!button) return;

            if (loading) {
                // Show loading spinner, hide text and arrow icon
                if (newsletterBtnTextIndex) newsletterBtnTextIndex.style.display = 'none';
                if (newsletterBtnIconIndex) newsletterBtnIconIndex.style.display = 'none';
                if (newsletterLoadingIndex) newsletterLoadingIndex.classList.remove('hidden');
                button.disabled = true;
            } else {
                // Show text, hide loading spinner
                if (newsletterBtnTextIndex) newsletterBtnTextIndex.style.display = 'inline';
                if (newsletterBtnIconIndex) newsletterBtnIconIndex.style.display = 'inline';
                if (newsletterLoadingIndex) newsletterLoadingIndex.classList.add('hidden');
                button.disabled = false;
            }
        }

        function showNewsletterMessageIndex(message, type) {
            if (!newsletterMessageIndex) return;

            newsletterMessageIndex.textContent = message;
            newsletterMessageIndex.className = `text-sm mt-3 ${type === 'success' ? 'text-green-600' : 'text-red-600'}`;
            newsletterMessageIndex.classList.remove('hidden');

            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    if (newsletterMessageIndex) {
                        newsletterMessageIndex.classList.add('hidden');
                    }
                }, 5000);
            }
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    });

    // Banner carousel functionality
    (function () {
        const track = document.getElementById('bannerTrack');
        const slider = document.getElementById('bannerSlider');
        if (!track || !slider) return;

        const slides = Array.from(track.children);
        const prevBtn = document.getElementById('bannerPrev');
        const nextBtn = document.getElementById('bannerNext');
        const dots = document.querySelectorAll('.bannerDot');
        let index = 0;
        let autoPlayInterval;

        const update = () => {
            const slideWidth = slider.clientWidth;
            track.style.transform = `translateX(-${index * slideWidth}px)`;
            
            // Update dots
            dots.forEach((dot, i) => {
                dot.classList.toggle('bg-white', i === index);
                dot.classList.toggle('bg-white/50', i !== index);
            });
        };

        const clampIndex = () => {
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;
        };

        const startAutoPlay = () => {
            autoPlayInterval = setInterval(() => {
                index += 1;
                clampIndex();
                update();
            }, 5000);
        };

        const stopAutoPlay = () => {
            clearInterval(autoPlayInterval);
        };

        prevBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            stopAutoPlay();
            index -= 1;
            clampIndex();
            update();
            startAutoPlay();
        });

        nextBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            stopAutoPlay();
            index += 1;
            clampIndex();
            update();
            startAutoPlay();
        });

        dots.forEach(dot => {
            dot.addEventListener('click', (e) => {
                stopAutoPlay();
                index = parseInt(dot.dataset.index);
                update();
                startAutoPlay();
            });
        });

        window.addEventListener('resize', update);
        update();
        startAutoPlay();
    })();
</script>

<?php require_once 'includes/footer.php'; ?>