<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Initialize database and functions
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Get product ID from request
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id <= 0) {
    die('Invalid product ID');
}

// Get product details
$product = $functions->getProductById($product_id);

if (!$product) {
    die('Product not found');
}

// Get product reviews
$reviews = $functions->getProductReviews($product_id, 5);
$average_rating = $functions->getAverageRating($product_id);
$review_count = count($reviews);

// Get related products
$related_products = $functions->getRelatedProducts($product_id, $product['category_id'], 4);
?>

<div class="relative">
    <!-- Close Button - Improved for mobile -->
    <button onclick="closeQuickView()" class="absolute top-2 right-2 md:top-4 md:right-4 z-20 bg-white rounded-full p-2 shadow-lg hover:bg-gray-100 transition border border-gray-200">
        <svg class="w-5 h-5 md:w-6 md:h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-8 p-4 md:p-6">
        <!-- Product Images - Improved for mobile -->
        <div class="lg:col-span-2 space-y-2">
            <div class="bg-gray-100 rounded-lg overflow-hidden">
                <img src="<?php echo !empty($product['main_image']) ? $product['main_image'] : 'assets/images/placeholder-product.jpg'; ?>"
                    alt="<?php echo $product['name']; ?>"
                    class="w-full h-32 md:h-48 lg:h-64 object-contain"
                    onerror="this.src='assets/images/placeholder-product.jpg'">
            </div>
            <?php if (!empty($product['gallery_images'])): ?>
                <div class="grid grid-cols-4 gap-2">
                    <?php
                    $gallery_images = explode(',', $product['gallery_images']);
                    foreach (array_slice($gallery_images, 0, 4) as $image):
                    ?>
                        <div class="bg-gray-100 rounded-md overflow-hidden cursor-pointer">
                            <img src="<?php echo trim($image); ?>"
                                alt="<?php echo $product['name']; ?>"
                                class="w-full h-12 md:h-16 object-cover hover:opacity-75 transition"
                                onerror="this.src='assets/images/placeholder-product.jpg'">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Details - Improved for mobile -->
        <div class="lg:col-span-3 space-y-2">
            <div>
                <?php if ($product['discount'] > 0): ?>
                    <span class="bg-red-500 text-white text-xs md:text-sm px-2 md:px-3 py-1 rounded-full">
                        <?php echo $product['discount']; ?>% OFF
                    </span>
                <?php endif; ?>

                <h1 class="text-md md:text-xl lg:text-xl font-bold text-gray-800 mt-2 leading-tight">
                    <?php echo $product['name']; ?>
                </h1>

                <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 mt-2 space-y-2 sm:space-y-0">
                    <div class="flex items-center">
                        <div class="text-yellow-400 text-sm md:text-lg">
                            <?php echo str_repeat('★', round($average_rating)) . str_repeat('☆', 5 - round($average_rating)); ?>
                        </div>
                        <span class="text-gray-600 text-xs md:text-sm ml-2">(<?php echo $review_count; ?> reviews)</span>
                    </div>
                    <span class="text-green-600 font-medium text-sm md:text-base">In Stock</span>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-2 sm:space-y-0">
                    <?php if ($product['discount'] > 0): ?>
                        <span class="text-xl md:text-2xl font-bold text-purple-600">
                            <?php echo $functions->formatPrice($functions->calculateDiscountedPrice($product['price'], $product['discount'])); ?>
                        </span>
                        <span class="text-sm md:text-md text-gray-500 line-through">
                            <?php echo $functions->formatPrice($product['price']); ?>
                        </span>
                        <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-xs md:text-sm font-medium">
                            Save <?php echo $functions->formatPrice($product['price'] - $functions->calculateDiscountedPrice($product['price'], $product['discount'])); ?>
                        </span>
                    <?php else: ?>
                        <span class="text-xl md:text-2xl lg:text-3xl font-bold text-purple-600">
                            <?php echo $functions->formatPrice($product['price']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            $description = !empty($product['description']) ? $product['description'] : 'No description available.';
            $truncated_description = strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description;

            ?>

            <div class="prose max-w-none text-gray-600 text-sm md:text-base">
                 <?php echo $truncated_description; ?></p>
            </div>

            <!-- Quantity and Actions - Improved for mobile -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center space-y-3 sm:space-y-0 sm:space-x-4">
                    <span class="text-gray-700 font-medium text-sm md:text-base">Quantity:</span>
                    <div class="flex items-center border border-gray-300 rounded-lg w-fit">
                        <button class="px-3 md:px-4 py-2 text-gray-600 hover:text-purple-600 transition text-sm md:text-base" onclick="updateQuantity(-1)">-</button>
                        <input type="number" id="quickview-quantity" value="1" min="1" max="10"
                            class="w-12 md:w-16 text-center border-0 focus:ring-0 focus:outline-none text-sm md:text-base">
                        <button class="px-3 md:px-4 py-2 text-gray-600 hover:text-purple-600 transition text-sm md:text-base" onclick="updateQuantity(1)">+</button>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
                    <button onclick="addToCartFromQuickView(<?php echo $product['product_id']; ?>)"
                        class="bg-purple-600 text-white py-3 px-4 md:px-6 rounded-lg hover:bg-purple-700 transition font-medium flex items-center justify-center space-x-2 text-sm md:text-base">
                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span>Add to Cart</span>
                    </button>

                    <button onclick="addToWishlist(<?php echo $product['product_id']; ?>)"
                        class="bg-gray-100 text-gray-600 py-3 px-4 rounded-lg hover:bg-gray-200 transition flex items-center justify-center text-sm md:text-base">
                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        <span class="ml-2 sm:hidden">Add to Wishlist</span>
                    </button>
                </div>
            </div>

     
        </div>
    </div>

 
</div>

<style>
    /* Custom breakpoint for extra small screens */
    @media (min-width: 475px) {
        .xs\:grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    /* Improved line clamping */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Better text rendering on mobile */
    * {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* Improved touch targets for mobile */
    button,
    a {
        min-height: 44px;
        min-width: 44px;
    }

    /* Better scrolling on mobile */
    #quickViewContent {
        -webkit-overflow-scrolling: touch;
    }
</style>

<script>
    function updateQuantity(change) {
        const input = document.getElementById('quickview-quantity');
        let quantity = parseInt(input.value) + change;
        if (quantity < 1) quantity = 1;
        if (quantity > 10) quantity = 10;
        input.value = quantity;
    }

    function addToCartFromQuickView(productId) {
        const quantity = document.getElementById('quickview-quantity').value;

        fetch('../ajax/cart.php?action=add_to_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: parseInt(quantity)
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

    // Add touch event improvements for mobile
    document.addEventListener('DOMContentLoaded', function() {
        const quickViewContent = document.getElementById('quickViewContent');

        // Prevent background scroll when quick view is open
        if (quickViewContent) {
            quickViewContent.addEventListener('touchstart', function(e) {
                e.stopPropagation();
            });

            quickViewContent.addEventListener('touchmove', function(e) {
                e.stopPropagation();
            });
        }

        // Improve button touch feedback
        const buttons = document.querySelectorAll('button');
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });

            button.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });
    });
</script>