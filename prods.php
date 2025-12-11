<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Initialize database and functions
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Page title
$page_title = 'Recently Viewed Products';

// Get all recently viewed products from session
$recently_viewed_products = [];
$total_viewed = 0;

if (!empty($_SESSION['recently_viewed'])) {
    $viewed_ids = array_map('intval', $_SESSION['recently_viewed']);
    $total_viewed = count($viewed_ids);

    if (!empty($viewed_ids)) {
        try {
            $pdo = $database->getConnection();

            // First try: use getProductDetails function for each product
            foreach ($viewed_ids as $product_id) {
                $product = $functions->getProductDetails($product_id);
                if ($product) {
                    $recently_viewed_products[] = $product;
                }
            }

            // If still empty, try direct database query
            if (empty($recently_viewed_products) && !empty($viewed_ids)) {
                $placeholders = str_repeat('?,', count($viewed_ids) - 1) . '?';

                $sql = "SELECT * FROM products WHERE product_id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($viewed_ids);
                $db_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Get categories for these products
                foreach ($db_products as &$product) {
                    $cat_stmt = $pdo->prepare("SELECT name FROM categories WHERE category_id = ?");
                    $cat_stmt->execute([$product['category_id']]);
                    $category = $cat_stmt->fetch(PDO::FETCH_ASSOC);
                    $product['category_name'] = $category ? $category['name'] : 'Uncategorized';
                }

                // Reorder to match session order
                $ordered = [];
                foreach ($viewed_ids as $id) {
                    foreach ($db_products as $product) {
                        if ($product['product_id'] == $id) {
                            $ordered[] = $product;
                            break;
                        }
                    }
                }
                $recently_viewed_products = $ordered;
            }
        } catch (PDOException $e) {
            // Silently handle errors in production
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<!-- Breadcrumb -->
<div class="container mx-auto px-2 max-w-6xl py-8 md:py-2 lg:py-2 pb-2">
    <nav class="flex text-xs">
        <a href="index.php" class="text-purple-600 hover:text-purple-700">Home</a>
        <span class="mx-1 text-gray-400">></span>
        <span class="text-gray-800">Recently Viewed Products</span>
    </nav>
</div>

<!-- Main Content -->
<section class="py-4">
    <div class="container mx-auto px-2 max-w-6xl">
        <!-- Header -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-gray-700 mb-2">Recently Viewed Products</h1>
                    <!-- <p class="text-gray-600">Your browsing history - <?php echo $total_viewed; ?> product<?php echo $total_viewed != 1 ? 's' : ''; ?> viewed</p> -->
                </div>
                <?php if ($total_viewed > 0): ?>
                    <button onclick="clearRecentlyViewed()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition text-sm">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear History
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-4">

            <!-- Products Grid -->
            <?php if (!empty($recently_viewed_products)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <?php foreach ($recently_viewed_products as $product): 
                        $productNameDecoded = html_entity_decode((string)($product['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $productNameSafe = htmlspecialchars($productNameDecoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    ?>
                        <div class="bg-white rounded-lg overflow-hidden hover:shadow-md transition-all duration-300 group relative">
                            <!-- Product Image -->
                            <div class="relative product-card-container">
                                <?php if (!empty($product['discount']) && $product['discount'] > 0): ?>
                                    <span class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded z-10 font-semibold">
                                        <?php echo $product['discount']; ?>% OFF
                                    </span>
                                <?php endif; ?>

                                <!-- Out of Stock Badge -->
                                <?php if (isset($product['stock_quantity']) && $product['stock_quantity'] <= 0): ?>
                                    <span class="absolute top-2 left-2 bg-gray-500 text-white text-xs px-2 py-1 rounded z-10 font-semibold">
                                        OUT OF STOCK
                                    </span>
                                <?php endif; ?>

                                <a href="product.php?id=<?php echo $product['product_id']; ?>">
                                    <?php
                                    $image_src = 'https://via.placeholder.com/300x300?text=No+Image';
                                    if (!empty($product['main_image'])) {
                                        $image_src = $functions->getProductImage($product['main_image']);
                                    }
                                    ?>
                                    <img src="<?php echo $image_src; ?>"
                                        alt="<?php echo $productNameSafe; ?>"
                                        class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300"
                                        onerror="this.src='https://via.placeholder.com/300x300?text=Image+Error'">
                                </a>

                                <!-- Test version - remove hover requirement -->
                                <div class="hidden lg:block absolute bottom-0 left-0 right-0 bg-white bg-opacity-95 z-40 rounded-md">
                                    <button onclick="ajaxAddToCart(<?php echo $product['product_id']; ?>); event.preventDefault();"
                                        class="w-full bg-purple-600 text-white py-2 px-3 hover:bg-purple-700 transition font-medium flex items-center justify-center space-x-2 text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <span>Add to Cart</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Product Details -->
                            <div class="p-4">
                                <!-- Category -->
                                <?php if (!empty($product['category_name'])): ?>
                                    <a href="products.php?category=<?php echo $product['category_id']; ?>" class="text-xs text-purple-600 hover:text-purple-700 font-medium">
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                    </a>
                                <?php endif; ?>

                                <!-- Product Name -->
                                <a href="product.php?id=<?php echo $product['product_id']; ?>" class="block mt-1">
                                    <h3 class="text-gray-800 text-sm leading-tight line-clamp-2 hover:text-purple-600 transition mb-2">
                                        <?php echo $productNameSafe; ?>
                                    </h3>
                                </a>

                                <!-- Rating (if available) -->
                                <?php
                                $avg_rating = $functions->getAverageRating($product['product_id']);
                                if ($avg_rating > 0):
                                ?>
                                    <div class="flex items-center mb-2">
                                        <div class="text-yellow-400 text-xs">
                                            <?php echo str_repeat('★', floor($avg_rating)) . str_repeat('☆', 5 - floor($avg_rating)); ?>
                                        </div>
                                        <span class="text-xs text-gray-500 ml-1">(<?php echo number_format($avg_rating, 1); ?>)</span>
                                    </div>
                                <?php endif; ?>

                                <!-- Price -->
                                <div class="mb-2">
                                    <?php if (!empty($product['discount']) && $product['discount'] > 0): ?>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs text-gray-500 line-through">
                                                <?php echo $functions->formatPrice($product['price']); ?>
                                            </span>
                                            <span class="text-sm font-bold text-purple-600">
                                                <?php echo $functions->formatPrice($functions->calculateDiscountedPrice($product['price'], $product['discount'])); ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-sm font-bold text-purple-600">
                                            <?php echo $functions->formatPrice($product['price']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Mobile Add to Cart Button -->
                                <?php if (!isset($product['stock_quantity']) || $product['stock_quantity'] > 0): ?>
                                    <!--  <button onclick="ajaxAddToCart(<?php echo $product['product_id']; ?>); event.preventDefault();"
                                    class="lg:hidden w-full bg-purple-600 text-white py-2 px-2 rounded hover:bg-purple-700 transition font-medium flex items-center justify-center space-x-1 text-xs">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span>Add to Cart</span>
                                </button> -->
                                <?php else: ?>
                                    <button disabled class="lg:hidden w-full bg-gray-400 text-white py-2 px-2 rounded font-medium text-xs">
                                        Out of Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-12 text-center">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">No Recently Viewed Products</h2>
                    <p class="text-gray-600 mb-6">
                        <?php if ($total_viewed > 0): ?>
                            You have <?php echo $total_viewed; ?> products in your history, but we couldn't load them.
                        <?php else: ?>
                            You haven't viewed any products yet. Start browsing to see your history here!
                        <?php endif; ?>
                    </p>
                    <a href="products.php" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-medium">
                        Browse Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
    // Clear recently viewed history using confirmation modal
    // Clear recently viewed history - Updated to use custom confirmation modal
    function clearRecentlyViewed() {
        showConfirmationModal(
            'Clear Viewing History',
            'Are you sure you want to clear all your recently viewed products? This action cannot be undone.',
            performClearRecentlyViewed, {
                type: 'warning',
                confirmText: 'Clear History',
                cancelText: 'Keep History'
            }
        );
    }

    function performClearRecentlyViewed() {
        showNotification('Clearing history...', 'info');

        fetch('ajax/clear_recently_viewed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Viewing history cleared successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'Failed to clear history', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error clearing history', 'error');
            });
    }

    // AJAX Add to Cart
    function ajaxAddToCart(productId) {
        const btn = event.target;
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mx-auto"></div>';

        fetch('ajax/cart.php?action=add_to_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
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
</script>

<style>
    /* Product Card Hover Effects */
    .product-card-container {
        position: relative;
        overflow: hidden;
    }

    @media (min-width: 1024px) {
        .product-card-container .group-hover\:translate-y-0 {
            box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
        }
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<?php require_once 'includes/footer.php'; ?>