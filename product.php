<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Initialize database and functions
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Load free-shipping settings from DB
require_once 'includes/settings_helper.php';
$pdo = $database->getConnection();
$free_shipping_threshold = floatval(SettingsHelper::get($pdo, 'free_shipping_threshold', 0));
$shipping_cost = floatval(SettingsHelper::get($pdo, 'shipping_cost', 0));
$cart_total = $functions->getCartTotal();
$remaining_for_free = max(0, $free_shipping_threshold - $cart_total);
$progress_percent = $free_shipping_threshold > 0 ? min(100, ($cart_total / $free_shipping_threshold) * 100) : 100;


// Handle both URL formats
$product_id = 0;
$product_param = null;

// Check for clean URL format: /product/8
if (preg_match('#/product/(\d+)#', $_SERVER['REQUEST_URI'], $matches)) {
    $product_id = (int)$matches[1];
    error_log("Clean URL detected - Product ID: " . $product_id);
}
// Check for traditional format: product.php?id=8 or product.php?product=slug
elseif (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    error_log("Traditional URL detected - Product ID: " . $product_id);
} elseif (isset($_GET['product'])) {
    $product_param = $_GET['product'];
    error_log("Product slug/ID detected: " . $product_param);
    
    // Check if it's numeric (ID) or slug
    if (is_numeric($product_param)) {
        $product_id = (int)$product_param;
    } else {
        // Try to get product by slug
        $product_by_slug = $functions->getProductBySlug($product_param);
        if ($product_by_slug) {
            $product_id = (int)$product_by_slug['product_id'];
            error_log("Found product by slug: " . $product_param . " -> ID: " . $product_id);
        }
    }
} else {
    error_log("No product ID found in URL");
}

//error_log("Final Product ID: " . $product_id);

// Validate product ID
if ($product_id <= 0) {
    error_log("Invalid product ID - Redirecting to products.php");
    header('Location: products.php');
    exit;
}

// Get product details with debugging
error_log("Fetching product details for ID: " . $product_id);
$product = $functions->getProductDetails($product_id);

// Normalize product name once to avoid showing encoded entities (handles double-encoding)
$productNameRaw = (string)($product['name'] ?? '');
$productNameDecoded = $productNameRaw;
for ($i = 0; $i < 2; $i++) {
    $candidate = html_entity_decode($productNameDecoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($candidate === $productNameDecoded) {
        break;
    }
    $productNameDecoded = $candidate;
}
$productNameSafe = htmlspecialchars($productNameDecoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$productNameJson = json_encode($productNameDecoded, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

if (!$product) {
    error_log("Product not found in database - ID: " . $product_id);

    // Let's check what's actually in the database
    try {
        $pdo = $database->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $db_product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($db_product) {
            error_log("Product found in raw query: " . print_r($db_product, true));
        } else {
            error_log("Product not found in raw query either");

            // Check if product exists but is deleted/disabled
            $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND (is_active = 0 OR stock_quantity <= 0)");
            $stmt->execute([$product_id]);
            $inactive_product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($inactive_product) {
                error_log("Product exists but is inactive/out of stock: " . print_r($inactive_product, true));
            }
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
    }

    header('Location: products.php');
    exit;
}

error_log("Product found: " . $product['name'] . " (ID: " . $product['product_id'] . ")");
error_log("Product found: " . $product['name'] . " (ID: " . $product['product_id'] . ")");

// ========== RECENTLY VIEWED PRODUCTS TRACKING ==========
if (!isset($_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'] = [];
}

// Remove current product if already exists (to avoid duplicates)
$temp_viewed = [];
foreach ($_SESSION['recently_viewed'] as $id) {
    if ($id != $product_id) {
        $temp_viewed[] = $id;
    }
}
$_SESSION['recently_viewed'] = $temp_viewed;

// Add current product to the beginning
array_unshift($_SESSION['recently_viewed'], $product_id);

// Keep only the last 10 viewed products
$_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 20);

error_log("Recently viewed session: " . print_r($_SESSION['recently_viewed'], true));
// ========== END RECENTLY VIEWED TRACKING ==========

// Get product reviews
$reviews = $functions->getProductReviews($product_id);
$average_rating = $functions->getAverageRating($product_id);
$review_count = count($reviews);

// Get related products
$related_products = $functions->getRelatedProducts($product_id, $product['category_id'], 12);

// ========== GET RECENTLY VIEWED PRODUCTS FOR DISPLAY ==========
$recently_viewed_products = [];
$viewed_ids = [];

if (!empty($_SESSION['recently_viewed']) && count($_SESSION['recently_viewed']) > 1) {
    // Get IDs excluding current product
    $viewed_ids = [];
    foreach ($_SESSION['recently_viewed'] as $id) {
        if ($id != $product_id) {
            $viewed_ids[] = (int)$id;
        }
    }

    // Limit to 6 products
    $viewed_ids = array_slice($viewed_ids, 0, 6);


    if (!empty($viewed_ids)) {
        try {
            $pdo = $database->getConnection();

            // Method 1: Using FIELD() for ordering (MySQL specific)
            $placeholders = str_repeat('?,', count($viewed_ids) - 1) . '?';
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.category_id 
                    WHERE p.product_id IN ($placeholders) 
                    ORDER BY FIELD(p.product_id, $placeholders)";

            error_log("SQL Query: $sql");

            // Prepare parameters - IDs twice (for WHERE and ORDER BY)
            $params = array_merge($viewed_ids, $viewed_ids);

            $stmt = $pdo->prepare($sql);
            foreach ($params as $index => $value) {
                $stmt->bindValue($index + 1, $value, PDO::PARAM_INT);
            }

            if ($stmt->execute()) {
                $recently_viewed_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Method 1 - Fetched " . count($recently_viewed_products) . " products");

                if (empty($recently_viewed_products)) {
                    // Method 2: Try without JOIN
                    error_log("Trying Method 2 (no JOIN)...");
                    $simple_sql = "SELECT * FROM products WHERE product_id IN ($placeholders)";
                    $simple_stmt = $pdo->prepare($simple_sql);
                    $simple_stmt->execute($viewed_ids);
                    $simple_products = $simple_stmt->fetchAll(PDO::FETCH_ASSOC);

                    error_log("Method 2 - Fetched " . count($simple_products) . " products");

                    if (!empty($simple_products)) {
                        // Get categories separately
                        foreach ($simple_products as &$prod) {
                            $cat_stmt = $pdo->prepare("SELECT name FROM categories WHERE category_id = ?");
                            $cat_stmt->execute([$prod['category_id']]);
                            $category = $cat_stmt->fetch(PDO::FETCH_ASSOC);
                            $prod['category_name'] = $category ? $category['name'] : 'Uncategorized';
                        }

                        // Reorder manually
                        $ordered = [];
                        foreach ($viewed_ids as $id) {
                            foreach ($simple_products as $prod) {
                                if ($prod['product_id'] == $id) {
                                    $ordered[] = $prod;
                                    break;
                                }
                            }
                        }
                        $recently_viewed_products = $ordered;
                    }
                }
            } else {
                error_log("SQL execution failed");
            }

            // DEBUG: Log what we got
            error_log("Final products count: " . count($recently_viewed_products));
            if (!empty($recently_viewed_products)) {
                foreach ($recently_viewed_products as $prod) {
                    error_log("  - ID: " . $prod['product_id'] . ", Name: " . substr($prod['name'], 0, 50));
                }
            } else {
                error_log("WARNING: No products in final array!");

                // Method 3: Direct test query
                error_log("Method 3: Direct test...");
                $test_sql = "SELECT COUNT(*) as count FROM products WHERE product_id IN (6, 8, 19, 23, 20, 14)";
                $test_result = $pdo->query($test_sql)->fetch(PDO::FETCH_ASSOC);
                error_log("Products in database with those IDs: " . $test_result['count']);
            }

            error_log("=== RECENTLY VIEWED DEBUG END ===");
        } catch (Exception $e) {
            error_log("Exception in recently viewed: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
        }
    }
}
// ========== END GET RECENTLY VIEWED PRODUCTS ==========
// Get product reviews
$reviews = $functions->getProductReviews($product_id);
$average_rating = $functions->getAverageRating($product_id);
$review_count = count($reviews);

// Get related products
$related_products = $functions->getRelatedProducts($product_id, $product['category_id'], 8);

// Check if user can review this product
$can_review = false;
if (isset($_SESSION['user_id'])) {
    $can_review = $functions->canUserReviewProduct($_SESSION['user_id'], $product_id);
    $user_review = $functions->getUserReviewForProduct($_SESSION['user_id'], $product_id);
}

// Get gallery images
$gallery_images = [];
if (!empty($product['gallery_images'])) {
    $gallery_images = explode(',', $product['gallery_images']);
}

// Get active coupons for sidebar
$active_coupons = [];
try {
    $pdo = $database->getConnection();
    $current_date = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT * FROM coupons 
        WHERE is_active = 1 
        AND start_date <= ? 
        AND end_date >= ? 
        AND (usage_limit IS NULL OR used_count < usage_limit)
        ORDER BY discount_value DESC
        LIMIT 3
    ");
    $stmt->execute([$current_date, $current_date]);
    $active_coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching coupons: " . $e->getMessage());
}

// Get store statistics for sidebar
$store_stats = [];
try {
    // Total products
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_products FROM products WHERE stock_quantity > 0");
    $stmt->execute();
    $store_stats['total_products'] = $stmt->fetchColumn();

    // Total categories
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_categories FROM categories");
    $stmt->execute();
    $store_stats['total_categories'] = $stmt->fetchColumn();

    // Total orders today
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as orders_today FROM orders WHERE DATE(order_date) = ?");
    $stmt->execute([$today]);
    $store_stats['orders_today'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching store stats: " . $e->getMessage());
}

// Determine product badges
$badges = [];
if ($product['discount'] > 0) {
    $badges[] = ['text' => $product['discount'] . '% OFF', 'color' => 'bg-red-500'];
}
// Add "New" badge for products added in the last 7 days
$days_old = floor((time() - strtotime($product['date_added'])) / (60 * 60 * 24));
if ($days_old <= 7) {
    $badges[] = ['text' => 'NEW', 'color' => 'bg-green-500'];
}
// Add "Best Seller" badge for products with high ratings
if ($average_rating >= 4.5) {
    $badges[] = ['text' => 'BEST', 'color' => 'bg-yellow-500'];
}
// Add "Limited Stock" badge
if ($product['stock_quantity'] <= 10 && $product['stock_quantity'] > 0) {
    $badges[] = ['text' => 'LOW STOCK', 'color' => 'bg-orange-500'];
}
// Add "Out of Stock" badge
if ($product['stock_quantity'] <= 0) {
    $badges[] = ['text' => 'OUT OF STOCK', 'color' => 'bg-gray-500'];
}

// Update page title and meta description
$page_title = $productNameDecoded . ' - ' . (defined('SITE_NAME') ? SITE_NAME : 'Cartella');
$meta_description = substr($product['description'], 0, 160) . '...';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $result = $functions->addToCart($product_id, $quantity);
        if ($result) {
            $_SESSION['success_message'] = 'Product added to cart successfully!';
        }
    }

    if (isset($_POST['add_review']) && isset($_SESSION['user_id'])) {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

        if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
            $result = $functions->submitReview($_SESSION['user_id'], $product_id, $rating, $comment);
            if ($result['success']) {
                $_SESSION['success_message'] = 'Review submitted successfully!';
                header('Location: product.php?id=' . $product_id);
                exit;
            }
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<!-- Success Message -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="fixed top-20 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transition-all duration-300">
        <?php echo $_SESSION['success_message']; ?>
        <?php unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>

<!-- Breadcrumb -->

<div class="container mx-auto px-2 max-w-6xl py-8 md:py-2 lg:py-2 pb-2">
    <nav class="flex text-xs ">
        <a href="index.php" class="text-purple-600 hover:text-purple-700">Home</a>
        <span class="mx-1 text-gray-400">></span>
        <a href="products.php" class="text-gray-600 hover:text-purple-600">Products</a>
        <span class="mx-1 text-gray-400">></span>
        <a href="products.php?category=<?php echo $product['category_id']; ?>" class="text-gray-600 hover:text-purple-600">
            <?php echo $product['category_name']; ?>
        </a>
        <span class="mx-1 text-gray-400">></span>
        <span class="text-gray-800 "><?php echo $productNameSafe; ?></span>
    </nav>
</div>

<!-- Main Content Section -->
<section class="flex flex-col lg:flex-row gap-4 rounded-lg py-4">
    <div class="container mx-auto px-2 max-w-6xl">
        <div class="flex flex-col lg:flex-row gap-4 ">
            <!-- Left Content Section (Scrollable) -->
            <div class="lg:w-3/4">

                <div class="border border-gray-100 rounded-lg p-6 bg-white shadow-sm">

                    <!-- Product Images & Details -->
                    <div class="grid lg:grid-cols-5 gap-8 mb-8">
                        <!-- Product Images Section -->
                        <div class="lg:col-span-2">
                            <!-- Product Image Gallery -->
                            <div class="product-image-gallery">
                                <!-- Main Image Display -->
                                <div class="main-image-container">
                                    <div class="zoom-container" id="zoomContainer">
                                        <img id="mainImage"
                                            src="<?php echo $functions->getProductImage($product['main_image']); ?>"
                                            alt="<?php echo $productNameSafe; ?>"
                                            class="main-image">
                                        <div id="zoomLens" class="zoom-lens"></div>
                                    </div>
                                </div>

                                <!-- Thumbnail Gallery -->
                                <div class="thumbnail-container">
                                    <button class="thumb-nav thumb-prev" id="thumbPrev">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M15 18l-6-6 6-6" />
                                        </svg>
                                    </button>

                                    <div class="thumbnail-slider" id="thumbnailSlider">
                                        <!-- Main image thumbnail -->
                                        <div class="thumbnail-item active"
                                            onclick="changeMainImage('<?php echo $functions->getProductImage($product['main_image']); ?>', 0)">
                                            <img src="<?php echo $functions->getProductImage($product['main_image']); ?>"
                                                alt="Main product image"
                                                class="thumbnail-img">
                                        </div>

                                        <!-- Gallery image thumbnails -->
                                        <?php if (!empty($gallery_images)): ?>
                                            <?php foreach ($gallery_images as $index => $image): ?>
                                                <?php
                                                // Clean the image path
                                                $clean_image = trim($image);
                                                $image_src = $functions->getProductImage($clean_image);
                                                ?>
                                                <div class="thumbnail-item"
                                                    onclick="changeMainImage('<?php echo $image_src; ?>', <?php echo $index + 1; ?>)">
                                                    <img src="<?php echo $image_src; ?>"
                                                        alt="Product image <?php echo $index + 2; ?>"
                                                        class="thumbnail-img">
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <button class="thumb-nav thumb-next" id="thumbNext">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M9 18l6-6-6-6" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <style>
                            /* Product Image Gallery Styles */
                            .product-image-gallery {
                                width: 100%;
                            }

                            /* Main Image Container */
                            .main-image-container {
                                position: relative;
                                width: 100%;
                                aspect-ratio: 1;
                                background: #f8f9fa;
                                border-radius: 8px;
                                overflow: hidden;
                                margin-bottom: 15px;
                                /* border: 1px solid #e9ecef; */
                            }

                            .zoom-container {
                                position: relative;
                                width: 100%;
                                height: 100%;
                                cursor: zoom-in;
                            }

                            .main-image {
                                width: 100%;
                                height: 100%;
                                object-fit: contain;
                                transition: opacity 0.3s ease;
                            }

                            /* Zoom Lens */
                            .zoom-lens {
                                position: absolute;
                                border: 2px solid #8b5cf6;
                                background: rgba(255, 255, 255, 0.3);
                                width: 100px;
                                height: 100px;
                                pointer-events: none;
                                opacity: 0;
                                transition: opacity 0.2s ease;
                            }

                            /* Zoom Result Window */
                            .zoom-result {
                                position: absolute;
                                top: 0;
                                left: 105%;
                                width: 400px;
                                height: 400px;
                                border: 1px solid #ddd;
                                background: white;
                                border-radius: 8px;
                                overflow: hidden;
                                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                                z-index: 1000;
                                display: none;
                            }

                            .zoom-result img {
                                position: absolute;
                                max-width: none;
                            }

                            /* Thumbnail Container */
                            .thumbnail-container {
                                position: relative;
                                display: flex;
                                align-items: center;
                                gap: 10px;
                            }

                            .thumbnail-slider {
                                display: flex;
                                gap: 8px;
                                overflow: hidden;
                                flex: 1;
                                scroll-behavior: smooth;
                            }

                            .thumbnail-item {
                                flex: 0 0 calc(20% - 6.4px);
                                aspect-ratio: 1;
                                border: 2px solid transparent;
                                border-radius: 6px;
                                overflow: hidden;
                                cursor: pointer;
                                transition: all 0.3s ease;
                                background: #f8f9fa;
                                position: relative;
                            }

                            .thumbnail-item:hover {
                                border-color: #8b5cf6;
                                transform: scale(1.05);
                            }

                            .thumbnail-item.active {
                                border-color: #8b5cf6;
                                box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2);
                            }

                            .thumbnail-item.active::after {
                                content: '';
                                position: absolute;
                                inset: 0;
                                border: 1px solid #8b5cf6;
                                border-radius: 4px;
                                pointer-events: none;
                            }

                            .thumbnail-img {
                                width: 100%;
                                height: 100%;
                                object-fit: cover;
                                transition: transform 0.3s ease;
                            }

                            .thumbnail-item:hover .thumbnail-img {
                                transform: scale(1.1);
                            }

                            /* Thumbnail Navigation */
                            .thumb-nav {
                                background: white;
                                border: 1px solid #e9ecef;
                                border-radius: 50%;
                                width: 36px;
                                height: 36px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                cursor: pointer;
                                transition: all 0.3s ease;
                                z-index: 10;
                                flex-shrink: 0;
                            }

                            .thumb-nav:hover {
                                background: #8b5cf6;
                                border-color: #8b5cf6;
                                color: white;
                                transform: scale(1.1);
                            }

                            .thumb-nav:disabled {
                                opacity: 0.5;
                                cursor: not-allowed;
                            }

                            .thumb-nav:disabled:hover {
                                background: white;
                                border-color: #e9ecef;
                                color: #6c757d;
                                transform: scale(1);
                            }

                            /* Responsive Design */
                            @media (max-width: 768px) {
                                .thumbnail-item {
                                    flex: 0 0 calc(25% - 6px);
                                }

                                .thumb-nav {
                                    width: 32px;
                                    height: 32px;
                                }

                                .zoom-result {
                                    display: none !important;
                                    /* Disable zoom on mobile */
                                }

                                .zoom-container {
                                    cursor: default;
                                }
                            }

                            @media (max-width: 480px) {
                                .thumbnail-item {
                                    flex: 0 0 calc(20% - 6.4px);
                                }
                            }

                            /* Touch device optimizations */
                            @media (hover: none) {
                                .zoom-container {
                                    cursor: default;
                                }

                                .zoom-lens {
                                    display: none;
                                }

                                .zoom-result {
                                    display: none !important;
                                }
                            }
                        </style>

                        <script>
                            // Product Image Gallery JavaScript
                            let currentImageIndex = 0;
                            let totalImages = <?php echo !empty($gallery_images) ? count($gallery_images) + 1 : 1; ?>;
                            let zoomLens = document.getElementById('zoomLens');
                            let zoomContainer = document.getElementById('zoomContainer');
                            let mainImage = document.getElementById('mainImage');
                            let thumbnailSlider = document.getElementById('thumbnailSlider');

                            // Store all image paths in an array
                            const imagePaths = [
                                '<?php echo $functions->getProductImage($product['main_image']); ?>'
                                <?php if (!empty($gallery_images)): ?>
                                    <?php foreach ($gallery_images as $image): ?>, '<?php echo $functions->getProductImage(trim($image)); ?>'
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            ];

                            // Initialize gallery
                            document.addEventListener('DOMContentLoaded', function() {
                                initializeZoom();
                                initializeThumbnailNavigation();
                            });

                            // Change main image - simplified version
                            function changeMainImage(imageSrc, index) {
                                // Update current index and thumbnail active state IMMEDIATELY
                                currentImageIndex = index;
                                updateActiveThumbnail(index);
                                
                                // Update main image with loading effect
                                mainImage.style.opacity = '0.5';

                                // Preload the image
                                const tempImg = new Image();
                                tempImg.onload = function() {
                                    mainImage.src = imageSrc;
                                    mainImage.style.opacity = '1';
                                    updateThumbnailNavigation();
                                };
                                tempImg.onerror = function() {
                                    // If image fails to load, restore opacity
                                    mainImage.style.opacity = '1';
                                    console.error('Failed to load image:', imageSrc);
                                };
                                tempImg.src = imageSrc;
                            }

                            // Update active thumbnail
                            function updateActiveThumbnail(index) {
                                // Get all thumbnail items
                                const thumbnails = document.querySelectorAll('.thumbnail-item');
                                
                                // Remove active class from all thumbnails
                                thumbnails.forEach(item => {
                                    item.classList.remove('active');
                                });

                                // Add active class to current thumbnail (with bounds checking)
                                if (index >= 0 && index < thumbnails.length) {
                                    thumbnails[index].classList.add('active');
                                    console.log('Updated thumbnail active state to index:', index);
                                } else {
                                    console.warn('Thumbnail index out of bounds:', index, 'total thumbnails:', thumbnails.length);
                                }
                            }

                            // Initialize thumbnail navigation
                            function initializeThumbnailNavigation() {
                                const prevBtn = document.getElementById('thumbPrev');
                                const nextBtn = document.getElementById('thumbNext');

                                prevBtn.addEventListener('click', () => navigateThumbnails('prev'));
                                nextBtn.addEventListener('click', () => navigateThumbnails('next'));

                                updateThumbnailNavigation();
                            }

                            // Navigate thumbnails
                            function navigateThumbnails(direction) {
                                if (direction === 'prev') {
                                    currentImageIndex = Math.max(0, currentImageIndex - 1);
                                } else {
                                    currentImageIndex = Math.min(totalImages - 1, currentImageIndex + 1);
                                }

                                // Update main image using the stored image path
                                changeMainImage(imagePaths[currentImageIndex], currentImageIndex);

                                // Scroll thumbnail into view
                                const thumbnails = document.querySelectorAll('.thumbnail-item');
                                const sliderWidth = thumbnailSlider.offsetWidth;
                                const itemWidth = thumbnails[0]?.offsetWidth + 8 || 0;
                                const visibleItems = Math.floor(sliderWidth / itemWidth);
                                const maxScroll = (thumbnails.length - visibleItems) * itemWidth;

                                const scrollPosition = Math.min(
                                    Math.max(0, currentImageIndex * itemWidth - (visibleItems - 1) * itemWidth / 2),
                                    maxScroll
                                );
                                thumbnailSlider.scrollTo({
                                    left: scrollPosition,
                                    behavior: 'smooth'
                                });

                                updateThumbnailNavigation();
                            }

                            // Update thumbnail navigation buttons
                            function updateThumbnailNavigation() {
                                const prevBtn = document.getElementById('thumbPrev');
                                const nextBtn = document.getElementById('thumbNext');

                                prevBtn.disabled = currentImageIndex === 0;
                                nextBtn.disabled = currentImageIndex === totalImages - 1;
                            }

                            // Initialize zoom functionality
                            function initializeZoom() {
                                // Only initialize zoom on desktop
                                if (window.innerWidth <= 768) return;

                                const zoomResult = document.createElement('div');
                                zoomResult.className = 'zoom-result';
                                zoomContainer.appendChild(zoomResult);

                                let zoomResultImg = document.createElement('img');
                                zoomResult.appendChild(zoomResultImg);

                                zoomContainer.addEventListener('mouseenter', function() {
                                    zoomLens.style.opacity = '1';
                                    zoomResult.style.display = 'block';
                                });

                                zoomContainer.addEventListener('mouseleave', function() {
                                    zoomLens.style.opacity = '0';
                                    zoomResult.style.display = 'none';
                                });

                                zoomContainer.addEventListener('mousemove', function(e) {
                                    e.preventDefault();

                                    const rect = zoomContainer.getBoundingClientRect();
                                    const x = e.clientX - rect.left;
                                    const y = e.clientY - rect.top;

                                    // Position lens
                                    const lensWidth = 100;
                                    const lensHeight = 100;
                                    let lensX = x - lensWidth / 2;
                                    let lensY = y - lensHeight / 2;

                                    // Constrain lens within container
                                    lensX = Math.max(0, Math.min(lensX, rect.width - lensWidth));
                                    lensY = Math.max(0, Math.min(lensY, rect.height - lensHeight));

                                    zoomLens.style.left = lensX + 'px';
                                    zoomLens.style.top = lensY + 'px';

                                    // Calculate zoom
                                    const scaleX = zoomResult.offsetWidth / lensWidth;
                                    const scaleY = zoomResult.offsetHeight / lensHeight;

                                    zoomResultImg.src = mainImage.src;
                                    zoomResultImg.style.width = (rect.width * scaleX) + 'px';
                                    zoomResultImg.style.height = (rect.height * scaleY) + 'px';
                                    zoomResultImg.style.left = -(lensX * scaleX) + 'px';
                                    zoomResultImg.style.top = -(lensY * scaleY) + 'px';
                                });
                            }

                            // Keyboard navigation
                            document.addEventListener('keydown', function(e) {
                                if (e.key === 'ArrowLeft') {
                                    navigateThumbnails('prev');
                                } else if (e.key === 'ArrowRight') {
                                    navigateThumbnails('next');
                                }
                            });

                            // Touch swipe support for mobile
                            let touchStartX = 0;
                            let touchEndX = 0;

                            mainImage.addEventListener('touchstart', function(e) {
                                touchStartX = e.changedTouches[0].screenX;
                            });

                            mainImage.addEventListener('touchend', function(e) {
                                touchEndX = e.changedTouches[0].screenX;
                                handleSwipe();
                            });

                            function handleSwipe() {
                                const swipeThreshold = 50;
                                const diff = touchStartX - touchEndX;

                                if (Math.abs(diff) > swipeThreshold) {
                                    if (diff > 0) {
                                        navigateThumbnails('next');
                                    } else {
                                        navigateThumbnails('prev');
                                    }
                                }
                            }
                        </script>

                        <div class="lg:col-span-3 space-y-4">

                            <!-- Product Badges -->
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($badges as $badge): ?>
                                    <span class="<?php echo $badge['color']; ?> text-white text-xs px-2 py-1 rounded">
                                        <?php echo $badge['text']; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-lg text-gray-600 border-b border-gray-200 pb-2 font-semibold">
                                <?php echo $productNameSafe; ?>
                            </div>

                            <!-- Rating and Stock -->
                            <div class="flex items-center space-x-3 text-sm">
                                <div class="flex items-center">
                                    <div class="text-yellow-400">
                                        <?php echo str_repeat('★', round($average_rating)) . str_repeat('☆', 5 - round($average_rating)); ?>
                                    </div>
                                    <span class="text-gray-600 ml-1"><?php echo number_format($average_rating, 1); ?></span>
                                </div>
                                <span class="text-gray-400">•</span>
                                <a href="#reviews" class="text-purple-600 hover:text-purple-700">
                                    <?php echo $review_count; ?> reviews
                                </a>
                                <span class="text-gray-400">•</span>
                                <span class="<?php echo $product['stock_quantity'] > 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                                    <?php echo $product['stock_quantity'] > 0 ? $product['stock_quantity'] . ' in stock' : 'Out of stock'; ?>
                                </span>
                            </div>

                            <!-- Price -->
                            <div class="space-y-1">
                                <?php if ($product['discount'] > 0): ?>
                                    <div class="flex items-center space-x-3">
                                        <span class="text-xl font-bold text-gray-800">
                                            <?php echo $functions->formatPrice($functions->calculateDiscountedPrice($product['price'], $product['discount'])); ?>
                                        </span>
                                        <span class="text-md text-gray-500 line-through">
                                            <?php echo $functions->formatPrice($product['price']); ?>
                                        </span>
                                        <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-xs font-medium hidden md:block">
                                            Save <?php echo $functions->formatPrice($product['price'] - $functions->calculateDiscountedPrice($product['price'], $product['discount'])); ?>
                                        </span>
                                    </div>

                                <?php else: ?>
                                    <span class="text-3xl font-bold text-purple-600">
                                        <?php echo $functions->formatPrice($product['price']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Quick Actions -->
                            <div class="space-y-3">
                                <!-- Quantity -->
                                <?php
                                // Prepare size options: support JSON array or comma-separated string in $product['size']
                                $sizeOptions = [];
                                if (!empty($product['size'])) {
                                    // Try JSON decode first
                                    $decoded = json_decode($product['size'], true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                        $sizeOptions = $decoded;
                                    } else {
                                        // split by comma or pipe or semicolon
                                        $sizeOptions = preg_split('/\s*[,|;]\s*/', $product['size']);
                                    }
                                    // Trim and filter empty
                                    $sizeOptions = array_values(array_filter(array_map('trim', $sizeOptions)));
                                }
                                ?>

                                <?php if (!empty($sizeOptions)): ?>
                                    <div class="flex items-center space-x-3">
                                        <span class="text-gray-700 font-medium text-sm">Size:</span>
                                        <div id="sizeOptions" class="flex items-center gap-2">
                                            <?php foreach ($sizeOptions as $i => $s): ?>
                                                <button type="button" class="size-option px-3 py-1 border rounded text-sm hover:border-purple-600 focus:outline-none" data-size="<?php echo htmlspecialchars($s); ?>" aria-pressed="<?php echo $i===0 ? 'true' : 'false'; ?>">
                                                    <span class="size-label"><?php echo htmlspecialchars($s); ?></span>
                                                    <?php if ($i===0): ?>
                                                        <svg class="w-4 h-4 ml-2 inline-block text-green-600 check-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <?php else: ?>
                                                        <svg class="w-4 h-4 ml-2 inline-block text-green-600 check-icon hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <?php endif; ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" id="selectedSize" name="selected_size" value="<?php echo htmlspecialchars($sizeOptions[0] ?? ''); ?>">
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center space-x-3">
                                    <span class="text-gray-700 font-medium text-sm">Qty:</span>
                                    <div class="flex items-center border border-gray-300 rounded">
                                        <button type="button" onclick="updateQuantity(-1)" class="px-3 py-1 text-gray-600 hover:text-purple-600 transition">-</button>
                                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>"
                                            class="w-12 text-center border-0 focus:ring-0 focus:outline-none text-sm">
                                        <button type="button" onclick="updateQuantity(1)" class="px-3 py-1 text-gray-600 hover:text-purple-600 transition">+</button>
                                    </div>
                                    <span class="text-xs text-gray-500">Max: <?php echo $product['stock_quantity']; ?></span>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <button type="button" id="addToCartBtn"
                                            onclick="ajaxAddToCart(<?php echo $product_id; ?>)"
                                            class="flex-1 bg-purple-600 text-white py-3 px-4 rounded hover:bg-purple-700 transition font-medium flex items-center justify-center space-x-2 text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            <span>Add to Cart</span>
                                        </button>
                                    <?php else: ?>
                                        <button disabled class="flex-1 bg-gray-400 text-white py-3 px-4 rounded font-medium text-sm">
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>

                                    <div class="flex space-x-1">
                                        <button onclick="addToWishlist(<?php echo $product_id; ?>)"
                                            class="bg-gray-100 text-gray-600 py-3 px-3 rounded hover:bg-gray-200 transition flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="shareProduct()"
                                            class="bg-gray-100 text-gray-600 py-3 px-3 rounded hover:bg-gray-200 transition flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Info -->
                            <div class="border-t pt-3 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">SKU:</span>
                                    <span class="font-medium"><?php echo $product['sku'] ?: 'N/A'; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Category:</span>
                                    <a href="products.php?category=<?php echo $product['category_id']; ?>" class="font-medium text-purple-600 hover:text-purple-700">
                                        <?php echo $product['category_name']; ?>
                                    </a>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Brand:</span>
                                     <a href="products.php?brand=<?php echo $product['brand'] ; ?>" class="text-purple-600 hover:text-purple-700">
                                         <?php echo $product['brand'] ?: 'Generic'; ?> 
                                    </a>
                                    <!-- <span class="font-medium"><?php echo $product['brand'] ?: 'Generic'; ?></span> -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description Preview -->
                    <div class="mb-8 border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Description</h3>

                        <?php
                        // Decode description the same way as product name to fix special characters
                        $descRaw = (string)($product['description'] ?? '');
                        $descDecoded = html_entity_decode($descRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $lines = preg_split('/\r\n|\r|\n/', $descDecoded);
                        ?>

                        <ul class="list-disc pl-6 text-gray-600 text-sm">
                            <?php foreach ($lines as $line): ?>

                                <?php if (trim($line) === ''): ?>
                                    <!-- Add spacing for empty lines -->
                                    <li class="list-none h-3"></li>

                                <?php else: ?>
                                    <li class="mb-1"><?php echo htmlspecialchars($line, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></li>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </ul>
                    </div>


                    <!-- Tabs Navigation -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex space-x-6" aria-label="Tabs">
                            <button id="specifications-tab" class="py-3 px-1 border-b-2 font-medium text-sm text-purple-600 border-purple-600"
                                onclick="switchTab('specifications')">
                                Specifications
                            </button>
                            <button id="reviews-tab" class="py-3 px-1 border-b-2 font-medium text-sm text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300"
                                onclick="switchTab('reviews')">
                                Reviews (<?php echo $review_count; ?>)
                            </button>
                        </nav>
                    </div>

                    <!-- Tabs Content -->
                    <div>
                        <!-- Specifications Tab -->
                        <div id="specifications-content" class="tab-content">
                            <div class="bg-white rounded border">
                                <div class="divide-y divide-gray-200 text-sm">
                                    <!-- Basic Information -->
                                    <div class="grid grid-cols-3 py-3 px-4">
                                        <div class="text-gray-600 font-medium">SKU</div>
                                        <div class="col-span-2"><?php echo $product['sku'] ?: 'N/A'; ?></div>
                                    </div>
                                    <div class="grid grid-cols-3 py-3 px-4">
                                        <div class="text-gray-600 font-medium">Brand</div>
                                        <div class="col-span-2"><?php echo $product['brand'] ?: 'Generic'; ?></div>
                                    </div>
                                    <div class="grid grid-cols-3 py-3 px-4">
                                        <div class="text-gray-600 font-medium">Category</div>
                                        <div class="col-span-2"><?php echo $product['category_name']; ?></div>
                                    </div>

                                    <!-- Physical Specifications -->
                                    <div class="grid grid-cols-3 py-3 px-4">
                                        <div class="text-gray-600 font-medium">Weight</div>
                                        <div class="col-span-2"><?php echo $product['weight'] ? $product['weight'] . ' kg' : 'N/A'; ?></div>
                                    </div>
                                    <?php if (!empty($product['dimensions'])): ?>
                                        <div class="grid grid-cols-3 py-3 px-4">
                                            <div class="text-gray-600 font-medium">Dimensions</div>
                                            <div class="col-span-2"><?php echo $product['dimensions']; ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Appearance -->
                                    <?php if (!empty($product['color'])): ?>
                                        <div class="grid grid-cols-3 py-3 px-4">
                                            <div class="text-gray-600 font-medium">Color</div>
                                            <div class="col-span-2"><?php echo $product['color']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($product['size'])): ?>
                                        <div class="grid grid-cols-3 py-3 px-4">
                                            <div class="text-gray-600 font-medium">Size</div>
                                            <div class="col-span-2"><?php echo $product['size']; ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Material & Build -->
                                    <?php if (!empty($product['material'])): ?>
                                        <div class="grid grid-cols-3 py-3 px-4">
                                            <div class="text-gray-600 font-medium">Material</div>
                                            <div class="col-span-2"><?php echo $product['material']; ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Warranty & Support -->
                                    <?php if (!empty($product['warranty'])): ?>
                                        <div class="grid grid-cols-3 py-3 px-4">
                                            <div class="text-gray-600 font-medium">Warranty</div>
                                            <div class="col-span-2"><?php echo $product['warranty']; ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Additional Specifications -->
                                    <?php if (!empty($product['model'])): ?>
                                        <div class="grid grid-cols-3 py-3 px-4">
                                            <div class="text-gray-600 font-medium">Model</div>
                                            <div class="col-span-2"><?php echo $product['model']; ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($product['manufacturer'])): ?>
                                        <div class="grid grid-cols-3 py-3 px-4">
                                            <div class="text-gray-600 font-medium">Manufacturer</div>
                                            <div class="col-span-2"><?php echo $product['manufacturer']; ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($product['country_of_origin'])): ?>
                                        <div class="grid grid-cols-3 py-3 px-4">
                                            <div class="text-gray-600 font-medium">Country of Origin</div>
                                            <div class="col-span-2"><?php echo $product['country_of_origin']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Reviews Tab -->
                        <div id="reviews-content" class="tab-content hidden">
                            <div class="space-y-6">
                                <!-- Review Summary -->
                                <div class="bg-white rounded border p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">Customer Reviews</h3>
                                            <div class="flex items-center space-x-2 mt-1">
                                                <div class="text-yellow-400">
                                                    <?php echo str_repeat('★', round($average_rating)) . str_repeat('☆', 5 - round($average_rating)); ?>
                                                </div>
                                                <span class="text-gray-600 text-sm"><?php echo number_format($average_rating, 1); ?> (<?php echo $review_count; ?> reviews)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Add Review Form -->
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <?php if ($can_review && !$user_review): ?>
                                            <div class="border-t pt-4">
                                                <h4 class="text-sm font-semibold text-gray-800 mb-3">Write a Review</h4>
                                                <form id="reviewForm">
                                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                                    <div class="space-y-3">
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-700 mb-1">Your Rating</label>
                                                            <div class="flex space-x-1" id="rating-stars">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <button type="button" onclick="setRating(<?php echo $i; ?>)"
                                                                        class="text-lg text-gray-300 hover:text-yellow-400 transition"
                                                                        data-rating="<?php echo $i; ?>">
                                                                        ★
                                                                    </button>
                                                                <?php endfor; ?>
                                                            </div>
                                                            <input type="hidden" name="rating" id="rating-value" value="0" required>
                                                        </div>
                                                        <div>
                                                            <label for="comment" class="block text-xs font-medium text-gray-700 mb-1">Your Review</label>
                                                            <textarea name="comment" id="comment" rows="3"
                                                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-purple-500 focus:border-purple-500 text-sm"
                                                                placeholder="Share your experience..." required></textarea>
                                                        </div>
                                                        <button type="button" onclick="submitReview()"
                                                            class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition text-sm">
                                                            Submit Review
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php elseif ($user_review): ?>
                                            <div class="border-t pt-4">
                                                <div class="bg-purple-500 border border-purple-700 rounded p-3">
                                                    <p class="text-white text-sm">You have already reviewed this product.</p>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="border-t pt-4">
                                                <div class="bg-yellow-500 border border-yellow-700 rounded p-3">
                                                    <p class="text-gray-800 text-sm">Purchase required to review.</p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="border-t pt-4 text-center">
                                            <p class="text-gray-600 text-sm mb-2">Please <a href="signin.php" class="text-purple-600 hover:text-purple-700">sign in</a> to write a review.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Reviews List -->
                                <div class="space-y-3 reviews-container max-h-80 overflow-y-auto">
                                    <?php if (!empty($reviews)): ?>
                                        <?php foreach ($reviews as $review):
                                            $user_avatar = $functions->getUserAvatar($review['user_id'], $review['customer_name']);
                                        ?>
                                            <div class="bg-white rounded border p-3">
                                                <div class="flex items-start space-x-3 mb-2">
                                                    <?php if (strpos($user_avatar, 'data:image/svg+xml') === 0): ?>
                                                        <div class="flex-shrink-0">
                                                            <img src="<?php echo $user_avatar; ?>" alt="<?php echo $review['customer_name']; ?>" class="w-8 h-8 rounded-full">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="flex-shrink-0">
                                                            <img src="<?php echo $user_avatar; ?>" alt="<?php echo $review['customer_name']; ?>" class="w-8 h-8 rounded-full object-cover">
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-start justify-between">
                                                            <div>
                                                                <h5 class="font-medium text-gray-800 text-sm"><?php echo $review['customer_name']; ?></h5>
                                                                <div class="text-yellow-400 text-xs">
                                                                    <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                                                </div>
                                                            </div>
                                                            <span class="text-xs text-gray-500 whitespace-nowrap">
                                                                <?php echo date('M j, Y', strtotime($review['review_date'])); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="text-gray-600 text-sm"><?php echo $review['comment']; ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="bg-white rounded border p-4 text-center">
                                            <p class="text-gray-600 text-sm">No reviews yet. Be the first to review!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Related Products Section -->

                <?php if (!empty($related_products)): ?>
                    <section class="bg-white mt-4 rounded-lg shadow-sm border border-gray-200 ">
                        <div class="container mx-auto p-6">
                            <div class="mb-6">
                                <h2 class="text-xl font-bold text-gray-800">Related Products</h2>
                            </div>

                            <div class="grid grid-cols-3 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                                <?php foreach ($related_products as $related): ?>
                                    <div class="bg-white rounded-lg overflow-hidden hover:shadow-sm ">
                                        <div class="relative">
                                            <?php if ($related['discount'] > 0): ?>
                                                <span class="absolute top-1 right-1 bg-red-500 text-white text-xs px-1 py-0.5 rounded z-10">
                                                    <?php echo $related['discount']; ?>% OFF
                                                </span>
                                            <?php endif; ?>

                                            <a href="product.php?id=<?php echo $related['product_id']; ?>">
                                                <img src="<?php echo $functions->getProductImage($related['main_image']); ?>"
                                                    alt="<?php echo $related['name']; ?>"
                                                    class="w-full h-32 object-cover hover:scale-105 transition-transform duration-300 rounded-md">
                                            </a>
                                        </div>

                                        <div class="p-2">
                                            <a href="product.php?id=<?php echo $related['product_id']; ?>" class="block">
                                                <h3 class="text-gray-800 text-sm leading-tight line-clamp-2 hover:text-purple-600 transition mb-1">
                                                    <?php echo strlen($related['name']) > 15 ? substr($related['name'], 0, 15) . '...' : $related['name']; ?>
                                                </h3>
                                            </a>

                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <?php if ($related['discount'] > 0): ?>
                                                        <span class="text-xs text-gray-500 line-through">
                                                            <?php echo $functions->formatPrice($related['price']); ?>
                                                        </span>
                                                        <span class="text-xs md:text-sm lg:text-sm font-bold text-purple-600 block">
                                                            <?php echo $functions->formatPrice($functions->calculateDiscountedPrice($related['price'], $related['discount'])); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-sm font-bold text-purple-600">
                                                            <?php echo $functions->formatPrice($related['price']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <!-- Right Sidebar Section (Sticky) -->
            <div class="lg:w-1/4">

                <div class="sticky-sidebar space-y-6">
                    <!-- Store Information Card -->
                    <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Store Information
                        </h3>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Products:</span>
                                <span class="font-medium text-purple-600"><?php echo $store_stats['total_products'] ?? '0'; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Categories:</span>
                                <span class="font-medium text-purple-600"><?php echo $store_stats['total_categories'] ?? '0'; ?></span>
                            </div>
                            <!-- <div class="flex justify-between items-center">
                                <span class="text-gray-600">Orders Today:</span>
                                <span class="font-medium text-green-600"><?php echo $store_stats['orders_today'] ?? '0'; ?></span>
                            </div> -->
                        </div>
                    </div>

                    <!-- Active Coupons Card -->
                    <?php if (!empty($active_coupons)): ?>
                        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg p-4 text-white shadow-lg">
                            <h3 class="text-lg font-semibold mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                                </svg>
                                Active Coupons
                            </h3>

                            <div class="space-y-2">
                                <?php foreach ($active_coupons as $coupon): ?>
                                    <div class="bg-white bg-opacity-20 rounded-lg p-3 backdrop-blur-sm">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-lg"><?php echo $coupon['code']; ?></span>
                                            <span class="bg-white text-purple-600 px-2 py-1 rounded text-xs font-bold">
                                                <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                                    <?php echo $coupon['discount_value']; ?>% OFF
                                                <?php elseif ($coupon['discount_type'] === 'fixed'): ?>
                                                    GHS <?php echo $coupon['discount_value']; ?> OFF
                                                <?php else: ?>
                                                    FREE SHIPPING
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <p class="text-white text-opacity-90 text-xs mb-2"><?php echo $coupon['description']; ?></p>
                                        <div class="flex justify-between items-center text-xs text-white text-opacity-75">
                                            <span>Min: GHS <?php echo $coupon['min_order_amount']; ?></span>
                                            <span>Valid until: <?php echo date('M j, Y', strtotime($coupon['end_date'])); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Shipping Information Card - Optimized for Sidebar -->
                    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                        <!-- Header with icon -->
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="p-2 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg shadow-sm">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-800">Shipping & Returns</h3>
                                <div class="px-2 py-0.5 bg-green-50 text-green-700 text-xs rounded-full inline-block mt-1">
                                    Trust & Safety
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Features -->
                        <div class="space-y-3 mb-4">
                            <!-- Free Shipping -->
                            <div class="flex items-start space-x-2 p-2 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex-shrink-0 p-1.5 bg-blue-50 rounded-md">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-gray-800">Free Shipping</h4>
                                        <span class="text-xs  text-green-600 bg-green-50 px-1.5 py-0.5 rounded">Save <?php echo $functions->formatPrice($shipping_cost); ?>+</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-0.5">On orders above <?php echo $functions->formatPrice($free_shipping_threshold); ?></p>
                                    <div class="flex items-center text-xs text-gray-500 mt-1">
                                        <span class="mr-1">🚚</span>
                                        <span>Nationwide delivery</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Fast Delivery -->
                            <div class="flex items-start space-x-2 p-2 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex-shrink-0 p-1.5 bg-yellow-50 rounded-md">
                                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-gray-800">Fast Delivery</h4>
                                        <span class="text-xs  text-yellow-600 bg-yellow-50 px-1.5 py-0.5 rounded">2-5 Days</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-0.5">Express shipping available</p>
                                    <div class="flex items-center text-xs text-gray-500 mt-1">
                                        <span class="mr-1">⚡</span>
                                        <span>Same-day in Accra</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Easy Returns -->
                            <div class="flex items-start space-x-2 p-2 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex-shrink-0 p-1.5 bg-purple-50 rounded-md">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-gray-800">Easy Returns</h4>
                                        <span class="text-xs text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded">15 Days</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-0.5">Hassle-free returns</p>
                                    <div class="flex items-center text-xs text-gray-500 mt-1">
                                        <span class="mr-1">🔄</span>
                                        <span>Free return pickup</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Benefits -->
                        <div class="border-t border-gray-100 pt-3">
                            <div class="grid grid-cols-2 gap-2 text-center">
                                <div class="flex flex-col items-center p-2 rounded-lg bg-gray-50">
                                    <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center mb-1">
                                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-medium text-gray-700">100% Secure</span>
                                </div>
                                <div class="flex flex-col items-center p-2 rounded-lg bg-gray-50">
                                    <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mb-1">
                                        <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-medium text-gray-700">24/7 Support</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar for Free Shipping -->
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <div class="text-center mb-2">
                                <span class="text-xs font-medium text-gray-700">
                                    Your cart is <span class="text-green-600 font-bold"><?php echo $functions->formatPrice($remaining_for_free); ?></span> away from free shipping!
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                <div class="bg-gradient-to-r from-green-400 to-green-500 h-1.5 rounded-full" style="width: <?php echo round($progress_percent, 1); ?>%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span><?php echo $functions->formatPrice(0); ?></span>
                                <span><?php echo $functions->formatPrice($free_shipping_threshold); ?></span>
                            </div>
                        </div>

                        <!-- Policy Link -->
                        <div class="mt-3 text-center">
                            <a href="shipping-policy.php"
                                class="inline-flex items-center text-xs font-medium text-purple-600 hover:text-purple-700 hover:underline transition-colors">
                                View Full Policy
                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
  
                    <!-- Security Badges -->
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-2 text-center">Secure Shopping</h3>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="text-center">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-1">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs text-gray-600">Secure</span>
                            </div>
                            <div class="text-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-1">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs text-gray-600">SSL</span>
                            </div>
                            <div class="text-center">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-1">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <span class="text-xs text-gray-600">Verified</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recently Viewed Products Section -->
<section class="py-4 container mx-auto px-2 max-w-6xl">
    <div class="border border-gray-100 rounded-lg p-4 bg-white shadow-sm ">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Recently Viewed</h2>
               
            </div>
            <?php if (isset($_SESSION['recently_viewed']) && count($_SESSION['recently_viewed']) > 1): ?>
                <a href="prods.php" class="text-purple-500 px-4 py-2 rounded-lg hover:bg-purple-50 transition font-medium text-sm flex items-center">
                    <span>See All (<?php echo count($_SESSION['recently_viewed']); ?>)</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($recently_viewed_products) && is_array($recently_viewed_products)): ?>
            <div class="relative">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <?php foreach ($recently_viewed_products as $viewed): ?>
                        <div class="bg-white rounded-lg overflow-hidden hover:shadow-sm ">
                            <div class="relative ">
                                <?php if (!empty($viewed['discount']) && $viewed['discount'] > 0): ?>
                                    <span class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded z-10">
                                        <?php echo $viewed['discount']; ?>% OFF
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
                                        alt="<?php echo htmlspecialchars($viewed['name']); ?>"
                                        class="w-full h-40 object-cover hover:scale-105 transition-transform duration-300"
                                        onerror="this.src='https://via.placeholder.com/300x300?text=Image+Error'">
                                </a>
                            </div>

                            <div class="p-3">
                                <a href="product.php?id=<?php echo $viewed['product_id']; ?>" class="block">
                                    <h3 class="text-gray-800 text-sm leading-tight line-clamp-2 hover:text-purple-600 transition mb-1">
                                        <?php echo htmlspecialchars($viewed['name']); ?>
                                    </h3>
                                </a>

                                <div class="flex items-center justify-between">
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

                                <button onclick="ajaxAddToCart(<?php echo $viewed['product_id']; ?>); event.preventDefault();"
                                    class="w-full mt-2 bg-purple-600 text-white py-1.5 px-2 rounded hover:bg-purple-700 transition font-medium flex items-center justify-center space-x-1 text-xs">
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
        <?php else: ?>
            <!-- Fallback: Show products from session using direct query -->
            <?php
            if (!empty($viewed_ids)):
                try {
                    $pdo = $database->getConnection();
                    $placeholders = str_repeat('?,', count($viewed_ids) - 1) . '?';
                    $fallback_sql = "SELECT * FROM products WHERE product_id IN ($placeholders) LIMIT 6";
                    $fallback_stmt = $pdo->prepare($fallback_sql);
                    $fallback_stmt->execute($viewed_ids);
                    $fallback_products = $fallback_stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($fallback_products)):
            ?>
                        <div class="relative">
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                                <?php foreach ($fallback_products as $viewed): ?>
                                    <div class="bg-white rounded-lg overflow-hidden hover:shadow-md transition-shadow duration-300">
                                       
                                        <a href="product.php?id=<?php echo $viewed['product_id']; ?>">
                                            <?php
                                            $image_src = 'https://via.placeholder.com/300x300?text=No+Image';
                                            if (!empty($viewed['main_image'])) {
                                                $image_src = $functions->getProductImage($viewed['main_image']);
                                            }
                                            ?>
                                            <img src="<?php echo $image_src; ?>"
                                                alt="<?php echo htmlspecialchars($viewed['name']); ?>"
                                                class="w-full h-40 object-cover hover:scale-105 transition-transform duration-300 hover:shadow-sm rounded-md"
                                                onerror="this.src='https://via.placeholder.com/300x300?text=Image+Error'">
                                        </a>
                                        <div class="p-3">
                                            <a href="product.php?id=<?php echo $viewed['product_id']; ?>" class="block">
                                                <h3 class="text-gray-800 text-sm leading-tight line-clamp-2 hover:text-purple-600 transition ">           
                                                    <?php echo htmlspecialchars($viewed['name']); ?>
                                                </h3>
                                            </a>
                                            <div>
                                                <?php if (!empty($viewed['discount']) && $viewed['discount'] > 0): ?>
                                                    <span class="text-sm font-bold text-gray-700">
                                                        <?php echo $functions->formatPrice($functions->calculateDiscountedPrice($viewed['price'], $viewed['discount'])); ?>
                                                    </span>
                                                    <span class="text-xs text-gray-500 line-through block">
                                                        <?php echo $functions->formatPrice($viewed['price']); ?>
                                                    </span>

                                                <?php else: ?>
                                                    <span class="text-sm font-bold text-gray-700">
                                                        <?php echo $functions->formatPrice($viewed['price']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php
                    else:
                    ?>
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <p class="text-gray-600">Browse more products to see recently viewed items here.</p>
                            <a href="products.php" class="inline-block mt-3 bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                                Browse Products
                            </a>
                        </div>
            <?php
                    endif;
                } catch (Exception $e) {
                    // Silently fail for fallback query
                }
            endif;
            ?>
        <?php endif; ?>
    </div>
</section>

<script>
    
    // Sticky sidebar functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.sticky-sidebar');
        const sidebarOffset = 100;

        function updateSidebarPosition() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const containerRect = sidebar.parentElement.getBoundingClientRect();

            if (scrollTop > containerRect.top + sidebarOffset) {
                sidebar.style.position = 'sticky';
                sidebar.style.top = sidebarOffset + 'px';
            } else {
                sidebar.style.position = 'relative';
                sidebar.style.top = 'auto';
            }
        }

        window.addEventListener('scroll', updateSidebarPosition);
        window.addEventListener('resize', updateSidebarPosition);
        updateSidebarPosition();
    });

    // Simple image change function
    function changeMainImage(src) {
        const mainImage = document.getElementById('mainImage');
        mainImage.src = src;
    }

    // Quantity Update
    function updateQuantity(change) {
        const input = document.getElementById('quantity');
        let quantity = parseInt(input.value) + change;
        const maxQuantity = <?php echo $product['stock_quantity']; ?>;

        if (quantity < 1) quantity = 1;
        if (quantity > maxQuantity) quantity = maxQuantity;

        input.value = quantity;
    }

    // Tab Switching
    function switchTab(tabName) {
        // Hide all tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Remove active state from all tabs
        document.querySelectorAll('[id$="-tab"]').forEach(tab => {
            tab.classList.remove('text-purple-600', 'border-purple-600');
            tab.classList.add('text-gray-500', 'border-transparent');
        });

        // Show selected tab content
        document.getElementById(tabName + '-content').classList.remove('hidden');

        // Add active state to selected tab
        document.getElementById(tabName + '-tab').classList.remove('text-gray-500', 'border-transparent');
        document.getElementById(tabName + '-tab').classList.add('text-purple-600', 'border-purple-600');
    }

    // Rating System
    function setRating(rating) {
        document.getElementById('rating-value').value = rating;
        const stars = document.querySelectorAll('#rating-stars button');

        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
    }

    // Share Product
    function shareProduct() {
        if (navigator.share) {
                navigator.share({
                    title: <?php echo $productNameJson; ?>,
                    text: 'Check out this amazing product!',
                    url: window.location.href,
                })
                .then(() => console.log('Successful share'))
                .catch((error) => console.log('Error sharing:', error));
        } else {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Product link copied to clipboard!');
            });
        }
    }

    // Add to Wishlist
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
                        showNotification(data.message, 'success');
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

    // AJAX Add to Cart
    function ajaxAddToCart(productId) {
        const qty = parseInt(document.getElementById('quantity').value) || 1;
        const btn = document.getElementById('addToCartBtn');
        if (!btn) return;
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mx-auto"></div>';

        // include selected size if present
        const selectedSizeInput = document.getElementById('selectedSize');
        const selectedSize = selectedSizeInput ? selectedSizeInput.value : null;

        const payload = { product_id: productId, quantity: qty };
        if (selectedSize) payload.selected_size = selectedSize;

        fetch('ajax/cart.php?action=add_to_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    showNotification(data.message || 'Product added to cart!', 'success');
                } else {
                    showNotification(data.message || 'Error adding product to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error adding product to cart', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
    }

    // Submit Review via AJAX
    function submitReview() {
        const rating = document.getElementById('rating-value').value;
        const comment = document.getElementById('comment').value.trim();
        const productId = document.querySelector('input[name="product_id"]').value;

        if (rating < 1 || rating > 5) {
            showNotification('Please select a rating', 'error');
            return;
        }

        if (!comment) {
            showNotification('Please write a review comment', 'error');
            return;
        }

        const submitBtn = document.querySelector('button[onclick="submitReview()"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mx-auto"></div>';
        submitBtn.disabled = true;

        fetch('ajax/reviews.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'submit_review',
                    product_id: parseInt(productId),
                    rating: parseInt(rating),
                    comment: comment
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error submitting review', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
    }

    // Initialize first tab
    document.addEventListener('DOMContentLoaded', function() {
        switchTab('specifications');
        // Size option click handling (if present)
        const sizeContainer = document.getElementById('sizeOptions');
        if (sizeContainer) {
            sizeContainer.querySelectorAll('.size-option').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Deselect others
                    sizeContainer.querySelectorAll('.size-option').forEach(b => {
                        b.classList.remove('border-purple-600');
                        b.setAttribute('aria-pressed', 'false');
                        const icon = b.querySelector('.check-icon'); if (icon) icon.classList.add('hidden');
                    });

                    // Select this
                    this.classList.add('border-purple-600');
                    this.setAttribute('aria-pressed', 'true');
                    const icon = this.querySelector('.check-icon'); if (icon) icon.classList.remove('hidden');

                    const size = this.getAttribute('data-size') || '';
                    const hidden = document.getElementById('selectedSize');
                    if (hidden) hidden.value = size;
                });
            });
            // ensure first option visually selected
            const first = sizeContainer.querySelector('.size-option');
            if (first) first.classList.add('border-purple-600');
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

    .tab-content {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Custom scrollbar for reviews */
    .reviews-container {
        scrollbar-width: thin;
        scrollbar-color: #c4b5fd #f1f1f1;
    }

    .reviews-container::-webkit-scrollbar {
        width: 4px;
    }

    .reviews-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .reviews-container::-webkit-scrollbar-thumb {
        background: #c4b5fd;
        border-radius: 10px;
    }

    .reviews-container::-webkit-scrollbar-thumb:hover {
        background: #a78bfa;
    }

    /* Sticky sidebar styles */
    .sticky-sidebar {
        transition: all 0.3s ease;
    }

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
</style>

<?php require_once 'includes/footer.php'; ?>