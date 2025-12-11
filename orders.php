<?php
// Start session and check login at VERY TOP
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is logged in - BEFORE any output
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get featured products for the slider
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);
$featured_products = $functions->getFeaturedProducts(12);
$top_selling_products = $functions->getTopSellingProducts(24); // Add this line

// Now include header
require_once 'includes/header.php';
?>

<div class="py-6 md:py-2">
    <div class="container mx-auto px-2 max-w-6xl">
        <!-- Page Header -->
        <div class="mb-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="lg:text-left">

                    <nav class="flex items-center justify-center lg:justify-start space-x-2 text-xs text-gray-600">
                        <a href="index.php" class="hover:text-purple-600 transition-colors">Home</a>
                        <span class="text-gray-400">›</span>
                        <a href="account.php" class="hover:text-purple-600 transition-colors">My Account</a>
                        <span class="text-gray-400">›</span>
                        <span class="text-purple-600 font-medium">My Orders</span>
                    </nav>
                </div>

                <div class="flex flex-col sm:flex-row items-right">
                    <!--  <div class="bg-white rounded-lg px-4 py-3 shadow-sm border border-gray-200">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900" id="total-orders-count">0</div>
                            <div class="text-sm text-gray-600">Total Orders</div>
                        </div>
                    </div> -->
                    <div class="text-2xl font-bold text-gray-900 hidden md:hiddent" id="total-orders-count">0</div>
                    <button onclick="window.location.href='track_order.php'"
                        class="bg-purple-600 text-white px-3 py-1.5 text-sm rounded-lg font-semibold hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-2 group">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Track Order
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 rounded-xl">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" id="pending-count">0</div>
                        <div class="text-sm text-gray-600">Pending</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 rounded-xl">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" id="delivered-count">0</div>
                        <div class="text-sm text-gray-600">Delivered</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 rounded-xl">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" id="processing-count">0</div>
                        <div class="text-sm text-gray-600">Processing</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-red-100 rounded-xl">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" id="cancelled-count">0</div>
                        <div class="text-sm text-gray-600">Cancelled</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Layout -->
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Filters Sidebar -->
            <div class="lg:w-80 flex-shrink-0">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200  sticky top-20">
                    <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 p-3">Filter Orders</h3>
                    <div class="mb-6 p-6">
                        <!-- Search -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search Orders</label>
                            <div class="relative">
                                <input type="search" id="search-orders" placeholder="Search by order # or item..."
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 pl-11 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-gray-50 transition-colors">
                                <svg class="absolute left-4 top-3.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Order Status</label>
                            <select id="status-filter" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-gray-50 transition-colors">
                                <option value="">All Orders</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- Date Range Filter -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                            <select id="date-range" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-gray-50 transition-colors">
                                <option value="all">All Time</option>
                                <option value="7">Last 7 Days</option>
                                <option value="30" selected>Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                                <option value="365">Last Year</option>
                            </select>
                        </div>

                        <!-- Sort By -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                            <select id="sort-by" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-gray-50 transition-colors">
                                <option value="newest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                                <option value="total_high">Total: High to Low</option>
                                <option value="total_low">Total: Low to High</option>
                            </select>
                        </div>

                        <!-- Clear Filters Button -->
                        <button onclick="ordersManager.clearAllFilters()"
                            class="w-full border border-gray-300 text-gray-700 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-all duration-300">
                            Clear All Filters
                        </button>
                    </div>

                    <!-- Active Filters -->
                    <div id="active-filters" class="hidden p-6">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Active Filters</h4>
                        <div class="space-y-2" id="active-filters-list">
                            <!-- Active filters will appear here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Content -->
            <div class="flex-1">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 p-3">Orders</h3>
                    <div class="space-y-4">
                        <!-- Loading State -->
                        <div id="loading-orders" class="text-center py-16">
                            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-600 mx-auto mb-4"></div>
                            <p class="text-gray-600 text-lg">Loading your orders...</p>
                            <p class="text-gray-500 text-sm mt-2">Please wait while we fetch your order history</p>
                        </div>

                        <!-- Empty State -->
                        <div id="empty-orders" class="hidden text-center py-16">
                            <div class="w-32 h-32 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-16 h-16 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">No orders yet</h3>
                            <p class="text-gray-600 mb-8 max-w-md mx-auto">You haven't placed any orders yet. Start shopping to discover amazing products!</p>
                            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                <a href="products.php" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-8 py-4 rounded-lg font-semibold hover:shadow-lg transition-all duration-300">
                                    Start Shopping
                                </a>

                            </div>
                        </div>

                        <!-- Orders Grid -->
                        <div id="orders-grid" class="hidden grid grid-cols-1 gap-3 p-4">
                            <!-- Orders will be loaded here via JavaScript -->
                        </div>

                        <!-- Pagination -->
                        <div id="pagination" class="hidden flex items-center justify-between border-t border-gray-200 p-4 ">
                            <div class="flex-1 flex justify-between items-center">
                                <button id="prev-page" class="relative inline-flex items-center px-6 py-3 border border-gray-300 text-sm font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    Previous
                                </button>
                                <div class="hidden md:flex items-center gap-2">
                                    <span id="page-info" class="text-sm text-gray-700 font-medium"></span>
                                    <span class="text-gray-400">•</span>
                                    <span id="items-info" class="text-sm text-gray-500"></span>
                                </div>
                                <button id="next-page" class="relative inline-flex items-center px-6 py-3 border border-gray-300 text-sm font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                    Next
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Orders Container -->

            </div>
        </div>


    

    </div>

</div>

<script>
    // Add to Cart function for featured products
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

    // Add to Wishlist function for featured products
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
</script>

<script>
    // Update the scroll functions for the new layout
    function scrollFeaturedProducts(direction) {
        const grid = document.getElementById('featured-products-grid');
        const cardWidth = 224; // w-56 = 224px
        const gap = 16; // gap-4 = 16px
        const scrollAmount = (cardWidth + gap) * 5; // Scroll 5 cards at a time

        grid.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    }

    function scrollTopSellingProducts(direction) {
        const grid = document.getElementById('top-selling-products-grid');
        const cardWidth = 224; // w-56 = 224px
        const gap = 16; // gap-4 = 16px
        const scrollAmount = (cardWidth + gap) * 5; // Scroll 5 cards at a time

        grid.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    }

    // Update navigation button states
    function updateSpaciousNavButtons() {
        const featuredGrid = document.getElementById('featured-products-grid');
        const topSellingGrid = document.getElementById('top-selling-products-grid');

        if (!featuredGrid || !topSellingGrid) return;

        const featuredPrev = featuredGrid.closest('.products-slider').querySelector('.prev');
        const featuredNext = featuredGrid.closest('.products-slider').querySelector('.next');
        const topSellingPrev = topSellingGrid.closest('.products-slider').querySelector('.prev');
        const topSellingNext = topSellingGrid.closest('.products-slider').querySelector('.next');

        // Featured products buttons
        if (featuredGrid.scrollLeft <= 10) {
            featuredPrev.classList.add('opacity-50', 'cursor-not-allowed');
            featuredPrev.classList.remove('opacity-100', 'cursor-pointer');
        } else {
            featuredPrev.classList.remove('opacity-50', 'cursor-not-allowed');
            featuredPrev.classList.add('opacity-100', 'cursor-pointer');
        }

        if (featuredGrid.scrollLeft + featuredGrid.clientWidth >= featuredGrid.scrollWidth - 10) {
            featuredNext.classList.add('opacity-50', 'cursor-not-allowed');
            featuredNext.classList.remove('opacity-100', 'cursor-pointer');
        } else {
            featuredNext.classList.remove('opacity-50', 'cursor-not-allowed');
            featuredNext.classList.add('opacity-100', 'cursor-pointer');
        }

        // Top selling products buttons
        if (topSellingGrid.scrollLeft <= 10) {
            topSellingPrev.classList.add('opacity-50', 'cursor-not-allowed');
            topSellingPrev.classList.remove('opacity-100', 'cursor-pointer');
        } else {
            topSellingPrev.classList.remove('opacity-50', 'cursor-not-allowed');
            topSellingPrev.classList.add('opacity-100', 'cursor-pointer');
        }

        if (topSellingGrid.scrollLeft + topSellingGrid.clientWidth >= topSellingGrid.scrollWidth - 10) {
            topSellingNext.classList.add('opacity-50', 'cursor-not-allowed');
            topSellingNext.classList.remove('opacity-100', 'cursor-pointer');
        } else {
            topSellingNext.classList.remove('opacity-50', 'cursor-not-allowed');
            topSellingNext.classList.add('opacity-100', 'cursor-pointer');
        }
    }

    // Initialize spacious slider navigation
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for images to load before calculating sizes
        setTimeout(updateSpaciousNavButtons, 500);

        // Update buttons on scroll
        const spaciousGrids = document.querySelectorAll('.spacious-products-grid');
        spaciousGrids.forEach(grid => {
            grid.addEventListener('scroll', updateSpaciousNavButtons);
        });

        // Also update on window resize
        window.addEventListener('resize', updateSpaciousNavButtons);
    });
</script>

<style>
    .spacious-products-grid {
        display: flex;
        /* gap: 0.2rem; */
        /* gap-4 */
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        scroll-behavior: smooth;
        padding: 0.5rem 0.5rem;
        /* px-2 */
        scroll-snap-type: x mandatory;
    }

    .spacious-products-grid::-webkit-scrollbar {
        display: none;
    }

    .spacious-product-card {
        background: white;
        /* border: 1px solid #e5e7eb; */
        border-radius: 0.75rem;
        overflow: hidden;
        transition: all 0.3s ease;
        flex-shrink: 0;
        width: 14rem;
        /* w-56 */
    }

    .spacious-product-card:hover {
        border-color: #8b5cf6;
        box-shadow: 0 10px 25px rgba(139, 92, 246, 0.15);
        transform: translateY(-4px);
    }

    .spacious-product-image {
        width: 100%;
        height: 10rem;
        /* h-40 */
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .spacious-product-card:hover .spacious-product-image {
        transform: scale(1.05);
    }

    .spacious-product-content {
        padding: 0.75rem;
        /* p-3 */
    }

    .spacious-product-name {

        color: #374151;
        line-height: 1.3;
        /* Reduced from 1.4 */
        margin-bottom: 0.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        /* Removed min-height to allow natural height */
    }

    .price-section {
        margin-bottom: 0.75rem;
    }

    .current-price {
        font-size: 1rem;
        /* text-base */
        font-weight: 700;
    }

    .original-price {
        font-size: 0.75rem;
        /* text-xs */
    }

    .discount-badge {

        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
    }

    .sales-badge,
    .featured-badge {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
    }

    .add-to-cart-btn {
        width: 100%;
        background: #8b5cf6;
        color: white;
        border: none;
        padding: 0.5rem;
        /* py-2 */
        border-radius: 0.5rem;
        font-size: 0.875rem;
        /* text-sm */
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .add-to-cart-btn:hover {
        background: #7c3aed;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }

    .best-price-tag {
        background: #dcfce7;
        color: #16a34a;
    }

    /* Enhanced slider navigation */
    .slider-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 50%;
        width: 3rem;
        height: 3rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 10;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .slider-nav-btn:hover {
        background: #8b5cf6;
        border-color: #8b5cf6;
        color: white;
        box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
    }

    .slider-nav-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
    }

    .see-all-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 1rem;
        background: #f8fafc;
        color: #6b7280;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.3s ease;
    }

    .see-all-btn:hover {
        background: #f1f5f9;
        color: #374151;
        border-color: #d1d5db;
        transform: translateY(-1px);
    }

    /* Responsive Design */
    @media (max-width: 1280px) {
        .spacious-product-card {
            width: 13rem;
            /* Adjust for smaller screens */
        }
    }

    @media (max-width: 1024px) {
        .spacious-product-card {
            width: 12rem;
            /* Adjust for tablets */
        }

        .spacious-product-image {
            height: 9rem;
        }
    }

    @media (max-width: 768px) {
        .spacious-product-card {
            width: 11rem;
        }

        .spacious-product-image {
            height: 8rem;
        }

        .spacious-product-content {
            padding: 0.5rem;
        }

        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .see-all-btn {
            align-self: flex-end;
        }

        .slider-nav-btn {
            width: 2.5rem;
            height: 2.5rem;
        }
    }

    @media (max-width: 640px) {
        .spacious-product-card {
            width: 10rem;
        }

        .spacious-products-grid {
            gap: 0.5rem;
        }
    }

    @media (max-width: 480px) {
        .spacious-product-card {
            width: 9rem;
        }

        .spacious-product-image {
            height: 7rem;
        }

        .spacious-product-name {
            font-size: 0.75rem;
            min-height: 1rem;
        }

        .current-price {
            font-size: 0.875rem;
        }
    }
</style>

<style>
    .animate-scale-in {
        animation: scaleIn 0.2s ease-out;
    }

    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .order-card {
        transition: all 0.3s ease;
    }

    .order-card:hover {
        transform: translateY(-2px);
    }

    .status-badge {
        transition: all 0.3s ease;
    }

    .product-grid {
        display: grid;
        gap: 0.5rem;
    }

    @media (min-width: 640px) {
        .product-grid {
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        }
    }

    .fade-in {
        animation: fadeIn 0.5s ease-in;
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

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .filter-tag {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 0.75rem;
        color: #374151;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin: 2px;
    }

    /* Mobile responsive improvements */
    @media (max-width: 768px) {
        .order-header-mobile {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .order-actions-mobile {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .order-price-mobile {
            text-align: left;
            margin-top: 8px;
        }

        .order-status-mobile {
            align-self: flex-start;
        }
    }

    @media (max-width: 640px) {
        .order-actions-mobile {
            grid-template-columns: 1fr 1fr;
        }

        .order-action-full {
            grid-column: 1 / -1;
        }
    }

    .animate-scale-in {
        animation: scaleIn 0.2s ease-out;
    }

    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Enhanced styles for icon buttons */
    .order-actions-mobile .group:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .order-actions-mobile .group:active {
        transform: scale(0.95);
    }

    /* Tooltip styles for better UX */
    .order-actions-mobile button {
        position: relative;
    }

    .order-actions-mobile button:hover::after {
        content: attr(title);
        position: absolute;
        bottom: -40px;
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 1000;
        pointer-events: none;
    }

    .order-actions-mobile button:hover::before {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-bottom-color: #333;
        z-index: 1000;
        pointer-events: none;
    }

    /* Mobile optimizations */
    @media (max-width: 640px) {
        .order-actions-mobile button {
            min-height: 44px;
            min-width: 44px;
        }

        .order-actions-mobile .grid {
            gap: 8px;
        }
    }

    /* Desktop optimizations */
    @media (min-width: 641px) {
        .order-actions-mobile {
            align-items: center;
        }

        .order-actions-mobile .grid {
            gap: 12px;
        }
    }

    /* Compact Product Grid Styles - Same as cart.php */
    .compact-products-container {
        position: relative;
        overflow: hidden;
        width: 100%;
    }

    .compact-products-grid {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        scroll-behavior: smooth;
        padding: 8px 0;
        scroll-snap-type: x mandatory;
    }

    .compact-products-grid::-webkit-scrollbar {
        display: none;
    }

    .compact-product-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        overflow: hidden;
        transition: all 0.3s ease;
        flex: 0 0 calc(16.666% - 7px);
        min-width: 0;
        scroll-snap-align: start;
    }

    .compact-product-card:hover {
        border-color: #8b5cf6;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
        transform: translateY(-2px);
    }

    .compact-product-image {
        width: 100%;
        height: 100px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .compact-product-card:hover .compact-product-image {
        transform: scale(1.05);
    }

    .compact-product-content {
        padding: 6px;
    }

    .compact-product-name {
        font-size: 11px;
        font-weight: 500;
        color: #374151;
        line-height: 1.3;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 28px;
    }

    .compact-price-section {
        display: flex;
        flex-direction: column;
        gap: 1px;
        margin-bottom: 4px;
    }

    .compact-current-price {
        font-size: 12px;
        font-weight: 700;
        color: #8b5cf6;
    }

    .compact-original-price {
        font-size: 10px;
        color: #6b7280;
        text-decoration: line-through;
    }

    .compact-discount-badge {
        background: #ef4444;
        color: white;
        font-size: 9px;
        font-weight: 700;
        padding: 1px 4px;
        border-radius: 3px;
        position: absolute;
        top: 4px;
        right: 4px;
    }

    .compact-add-to-cart {
        width: 100%;
        background: #8b5cf6;
        color: white;
        border: none;
        padding: 4px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 3px;
        margin-bottom: 3px;
    }

    .compact-add-to-cart:hover {
        background: #7c3aed;
        transform: translateY(-1px);
    }

    .compact-sales-badge {
        background: #10b981;
        color: white;
        font-size: 8px;
        font-weight: 600;
        padding: 1px 4px;
        border-radius: 8px;
        display: inline-block;
    }

    /* Product slider styles */
    .products-slider {
        position: relative;
        padding: 0 40px;
    }

    .slider-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 10;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .slider-nav-btn:hover {
        background: #8b5cf6;
        border-color: #8b5cf6;
        color: white;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }

    .slider-nav-btn.prev {
        left: 0;
    }

    .slider-nav-btn.next {
        right: 0;
    }

    .slider-nav-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding: 0 12px;
    }

    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
    }

    .see-all-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #f8fafc;
        color: #6b7280;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .see-all-btn:hover {
        background: #f1f5f9;
        color: #374151;
        border-color: #d1d5db;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .compact-product-card {
            flex: 0 0 calc(20% - 7px);
        }
    }

    @media (max-width: 1024px) {
        .compact-product-card {
            flex: 0 0 calc(25% - 7px);
        }

        .products-slider {
            padding: 0 30px;
        }
    }

    @media (max-width: 768px) {
        .compact-product-card {
            flex: 0 0 calc(33.333% - 7px);
        }

        .compact-product-image {
            height: 90px;
        }

        .compact-product-content {
            padding: 5px;
        }

        .compact-product-name {
            font-size: 10px;
            height: 26px;
        }

        .compact-current-price {
            font-size: 11px;
        }

        .products-slider {
            padding: 0 20px;
        }

        .slider-nav-btn {
            width: 32px;
            height: 32px;
        }

        .section-title {
            font-size: 18px;
        }
    }

    @media (max-width: 480px) {
        .compact-product-card {
            flex: 0 0 calc(50% - 7px);
        }

        .compact-product-image {
            height: 80px;
        }

        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .see-all-btn {
            align-self: flex-end;
        }
    }
</style>

<script>
    class OrdersManager {
        constructor() {
            this.currentPage = 1;
            this.limit = 6;
            this.statusFilter = '';
            this.dateRange = '30';
            this.sortBy = 'newest';
            this.searchQuery = '';

            this.init();
        }

        init() {
            this.bindEvents();
            this.loadOrders();
            this.loadOrderCount();
            this.loadStatusCounts();
        }

        bindEvents() {
            // Filter and sort changes
            document.getElementById('status-filter').addEventListener('change', (e) => {
                this.statusFilter = e.target.value;
                this.currentPage = 1;
                this.loadOrders();
                this.updateActiveFilters();
            });

            document.getElementById('date-range').addEventListener('change', (e) => {
                this.dateRange = e.target.value;
                this.currentPage = 1;
                this.loadOrders();
                this.updateActiveFilters();
            });

            document.getElementById('sort-by').addEventListener('change', (e) => {
                this.sortBy = e.target.value;
                this.currentPage = 1;
                this.loadOrders();
            });

            // Search with debounce
            let searchTimeout;
            document.getElementById('search-orders').addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchQuery = e.target.value;
                    this.currentPage = 1;
                    this.loadOrders();
                    this.updateActiveFilters();
                }, 500);
            });

            // Pagination
            document.getElementById('prev-page').addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.loadOrders();
                }
            });

            document.getElementById('next-page').addEventListener('click', () => {
                this.currentPage++;
                this.loadOrders();
            });
        }

        async loadOrders() {
            this.showLoading(true);

            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    limit: this.limit,
                    ...(this.statusFilter && {
                        status: this.statusFilter
                    }),
                    ...(this.dateRange && {
                        date_range: this.dateRange
                    }),
                    ...(this.sortBy && {
                        sort: this.sortBy
                    }),
                    ...(this.searchQuery && {
                        search: this.searchQuery
                    })
                });

                const response = await fetch(`ajax/orders.php?action=get_orders&${params}`);
                const data = await response.json();

                if (data.success) {
                    this.renderOrders(data.orders);
                    this.renderPagination(data.pagination);
                } else {
                    this.showEmptyState();
                }
            } catch (error) {
                console.error('Error loading orders:', error);
                this.showEmptyState();
            } finally {
                this.showLoading(false);
            }
        }

        async loadOrderCount() {
            try {
                const params = new URLSearchParams();
                if (this.statusFilter) params.append('status', this.statusFilter);
                if (this.dateRange) params.append('date_range', this.dateRange);
                if (this.searchQuery) params.append('search', this.searchQuery);

                const response = await fetch(`ajax/orders.php?action=get_order_count&${params}`);
                const data = await response.json();

                if (data.success) {
                    document.getElementById('total-orders-count').textContent = data.count;
                }
            } catch (error) {
                console.error('Error loading order count:', error);
            }
        }

        async loadStatusCounts() {
            try {
                const statuses = ['pending', 'processing', 'delivered', 'cancelled'];
                for (const status of statuses) {
                    const response = await fetch(`ajax/orders.php?action=get_order_count&status=${status}&date_range=365`);
                    const data = await response.json();
                    if (data.success) {
                        document.getElementById(`${status}-count`).textContent = data.count;
                    }
                }
            } catch (error) {
                console.error('Error loading status counts:', error);
            }
        }

        // DELETE ORDER FUNCTIONALITY
        async deleteOrder(orderId) {
            showConfirmationModal(
                'Delete Order',
                'Are you sure you want to permanently delete this order? This action cannot be undone and all order data will be lost.',
                () => this.performDeleteOrder(orderId), {
                    type: 'error',
                    confirmText: 'Delete Order',
                    cancelText: 'Keep Order'
                }
            );
        }

        async performDeleteOrder(orderId) {
            try {
                const response = await fetch('ajax/orders.php?action=delete_order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Order deleted successfully', 'success');
                    this.loadOrders();
                    this.loadOrderCount();
                    this.loadStatusCounts();
                } else {
                    showNotification(data.message || 'Failed to delete order', 'error');
                }
            } catch (error) {
                console.error('Error deleting order:', error);
                showNotification('Failed to delete order', 'error');
            }
        }

        renderOrders(orders) {
            const container = document.getElementById('orders-grid');

            if (!orders || orders.length === 0) {
                this.showEmptyState();
                return;
            }

            let html = '';

            orders.forEach(order => {
                const statusColor = this.getStatusColor(order.status);

                // pick a thumbnail (first image or placeholder)
                const thumb = (order.item_images && order.item_images.length > 0) ? order.item_images[0] : 'assets/images/placeholder-product.jpg';
                // Truncate to first word of the first item name (e.g., 'Shoes' from 'Men\'s Casual Shoes')
                let rawFirst = (order.item_names && order.item_names.length > 0) ? order.item_names[0] : ('Order #' + order.order_number);
                const firstName = this.escapeHtml(String(rawFirst).split(/\s+/)[0]);

                html += `
            <div class="order-card bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all duration-300 overflow-hidden fade-in">
                <div class="p-4 lg:p-6 flex items-start gap-4">
                    <!-- Left: Thumbnail -->
                    <div class="flex-shrink-0">
                        <img src="${thumb}" alt="${this.escapeHtml(firstName)}" class="w-20 h-20 object-cover rounded-md border">
                    </div>

                    <!-- Middle: Details -->
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="text-sm md:text-base font-semibold text-gray-900">${firstName}</h4>
                                <div class="text-xs text-gray-500 mt-1">Order ${this.escapeHtml(order.order_number)}</div>
                            </div>
                            <div class="hidden lg:block text-right">
                                <div class="text-md font-bold text-gray-700">${this.escapeHtml(order.formatted_total)}</div>
                                <div class="text-xs text-gray-500">Total Amount</div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-3">
                            <span class="status-badge inline-flex items-center px-3 py-1 rounded-md text-sm font-semibold ${statusColor}">${this.formatStatus(order.status)}</span>
                            <div class="text-sm text-gray-600">${this.escapeHtml(order.formatted_date)} • ${order.item_count} item${order.item_count !== 1 ? 's' : ''}</div>
                        </div>

                        ${order.item_names && order.item_names.length > 0 ? `
                        <p class="text-sm text-gray-600 mt-3 line-clamp-2">${order.item_names.slice(0,3).map(n => this.escapeHtml(n)).join(', ')}${order.item_names.length>3 ? '...' : ''}</p>
                        ` : ''}

                        <!-- Mobile: SEE DETAILS (visible only on small screens) -->
                        <a href="order_details.php?order_id=${order.id}" class="lg:hidden mt-3 inline-block text-sm text-purple-600 font-semibold">See Details</a>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex-shrink-0 ml-4 flex flex-col items-end justify-between">
                        <a href="order_details.php?order_id=${order.id}" class="text-xs md:text-sm text-purple-600 font-semibold px-3 py-2 rounded hover:bg-orange-50 transition-colors">See Details</a>
                        <div class="lg:hidden mt-3 text-sm font-semibold text-gray-900">${this.escapeHtml(order.formatted_total)}</div>
                    </div>
                </div>

                <!-- Compact Action Icons Row -->
                <div class="px-4 lg:px-6 pb-4">
                    <div class="flex items-center gap-2">
                        ${order.status === 'shipped' || order.status === 'processing' ? `
                        <button onclick="ordersManager.trackOrder(${order.id}, '${this.escapeHtml(order.order_number)}')" 
                                class="border border-gray-300 text-gray-700 p-2 rounded-xl hover:bg-gray-50 transition-all duration-300 hover:scale-105 flex items-center justify-center group"
                                title="Track Order">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                        ` : '<div class="hidden sm:block"></div>'}

                        ${order.status === 'delivered' ? `
                        <button onclick="ordersManager.initiateReturn(${order.id})" 
                                class="border border-blue-300 text-blue-600 p-2 rounded-xl hover:bg-blue-50 transition-all duration-300 hover:scale-105 flex items-center justify-center group"
                                title="Return Order">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path>
                            </svg>
                        </button>
                        ` : '<div class="hidden sm:block"></div>'}

                        ${(order.status === 'pending' || order.status === 'processing') ? `
                        <button onclick="ordersManager.cancelOrder(${order.id})" 
                                class="border border-red-300 text-red-600 p-2 rounded-xl hover:bg-red-50 transition-all duration-300 hover:scale-105 flex items-center justify-center group"
                                title="Cancel Order">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        ` : '<div class="hidden sm:block"></div>'}

                        <button onclick="ordersManager.reorderWithConfirmation(${order.id})" 
                                class="border border-green-300 text-green-600 p-2 rounded-xl hover:bg-green-50 transition-all duration-300 hover:scale-105 flex items-center justify-center group"
                                title="Reorder Items">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>

                        <button onclick="ordersManager.deleteOrder(${order.id})" 
                                class="border border-gray-400 text-gray-600 p-2 rounded-xl hover:bg-gray-100 transition-all duration-300 hover:scale-105 flex items-center justify-center group"
                                title="Delete Order">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
            `;
            });

            container.innerHTML = html;
            this.showOrdersGrid();
        }

        renderPagination(pagination) {
            const container = document.getElementById('pagination');
            const prevBtn = document.getElementById('prev-page');
            const nextBtn = document.getElementById('next-page');
            const pageInfo = document.getElementById('page-info');
            const itemsInfo = document.getElementById('items-info');

            if (pagination.pages <= 1) {
                container.classList.add('hidden');
                return;
            }

            container.classList.remove('hidden');

            prevBtn.disabled = pagination.page === 1;
            nextBtn.disabled = pagination.page === pagination.pages;

            pageInfo.textContent = `Page ${pagination.page} of ${pagination.pages}`;
            itemsInfo.textContent = `${pagination.total} orders`;
        }

        updateActiveFilters() {
            const container = document.getElementById('active-filters');
            const filtersList = document.getElementById('active-filters-list');
            const filters = [];

            if (this.statusFilter) {
                filters.push({
                    type: 'status',
                    value: this.statusFilter,
                    label: `Status: ${this.formatStatus(this.statusFilter)}`
                });
            }

            if (this.dateRange && this.dateRange !== 'all') {
                const dateLabels = {
                    '7': 'Last 7 Days',
                    '30': 'Last 30 Days',
                    '90': 'Last 90 Days',
                    '365': 'Last Year'
                };
                filters.push({
                    type: 'date',
                    value: this.dateRange,
                    label: dateLabels[this.dateRange]
                });
            }

            if (this.searchQuery) {
                filters.push({
                    type: 'search',
                    value: this.searchQuery,
                    label: `Search: "${this.searchQuery}"`
                });
            }

            if (filters.length === 0) {
                container.classList.add('hidden');
                return;
            }

            let html = filters.map(filter => `
            <div class="filter-tag">
                ${filter.label}
                <button onclick="ordersManager.removeFilter('${filter.type}')" class="ml-1 text-gray-500 hover:text-gray-700">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `).join('');

            filtersList.innerHTML = html;
            container.classList.remove('hidden');
        }

        removeFilter(type) {
            switch (type) {
                case 'status':
                    this.statusFilter = '';
                    document.getElementById('status-filter').value = '';
                    break;
                case 'date':
                    this.dateRange = 'all';
                    document.getElementById('date-range').value = 'all';
                    break;
                case 'search':
                    this.searchQuery = '';
                    document.getElementById('search-orders').value = '';
                    break;
            }
            this.currentPage = 1;
            this.loadOrders();
            this.updateActiveFilters();
        }

        clearAllFilters() {
            this.statusFilter = '';
            this.dateRange = 'all';
            this.searchQuery = '';

            document.getElementById('status-filter').value = '';
            document.getElementById('date-range').value = 'all';
            document.getElementById('search-orders').value = '';

            this.currentPage = 1;
            this.loadOrders();
            this.updateActiveFilters();
        }

        getOrderProgress(status) {
            const progressMap = {
                'pending': 25,
                'processing': 50,
                'shipped': 75,
                'delivered': 100,
                'cancelled': 0
            };
            return progressMap[status] || 0;
        }

        // REORDER FUNCTIONALITY
        // REORDER FUNCTIONALITY
        async reorder(orderId) {
            try {
                console.log('Starting reorder for order:', orderId);

                const response = await fetch('ajax/orders.php?action=reorder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('Reorder response:', data);

                if (data.success) {
                    let message = data.message || 'Items added to cart successfully!';

                    if (data.unavailable_items && data.unavailable_items.length > 0) {
                        const unavailableNames = data.unavailable_items.map(item => item.product_name).join(', ');
                        message = `Some items were added to cart. Unavailable items: ${unavailableNames}`;
                    }

                    showNotification(message, 'success');

                    // Update cart count
                    this.updateCartCount(data.cart_count);

                } else {
                    showNotification(data.message || 'Failed to add items to cart', 'error');
                }
            } catch (error) {
                console.error('Error reordering:', error);
                showNotification('Failed to add items to cart. Please try again.', 'error');
            }
        }

        // REORDER WITH CONFIRMATION MODAL
        reorderWithConfirmation(orderId) {
            showConfirmationModal(
                'Reorder Items',
                'Would you like to add all items from this order to your cart?',
                () => this.reorder(orderId), {
                    type: 'info',
                    confirmText: 'Add to Cart',
                    cancelText: 'Cancel'
                }
            );
        }

       
        // TRACK ORDER FUNCTIONALITY
        async trackOrder(orderId, orderNumber) {
            try {
                const response = await fetch(`ajax/orders.php?action=get_order_details&order_id=${orderId}`);
                const data = await response.json();

                if (data.success) {
                    this.showTrackingInfo(data.order, data.items);
                } else {
                    showNotification(data.message || 'Failed to load order details', 'error');
                }
            } catch (error) {
                console.error('Error tracking order:', error);
                showNotification('Failed to load tracking information', 'error');
            }
        }

        // MODAL MANAGEMENT - Create proper tracking modal
        showTrackingInfo(order, items = []) {
            const itemCount = items ? items.length : 0;

            const modalContent = `
        <div class="space-y-6">
            <div class="text-center">
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Order #${this.escapeHtml(order.order_number)}</h3>
                <p class="text-gray-600">Status: <span class="font-semibold ${this.getStatusColor(order.status)}">${this.formatStatus(order.status)}</span></p>
            </div>

            <div class="bg-gray-50 rounded-xl p-4">
                <h4 class="font-semibold text-gray-900 mb-3">Order Information</h4>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Order Date:</span>
                        <span class="font-medium">${this.escapeHtml(order.formatted_date)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Items:</span>
                        <span class="font-medium">${itemCount} item${itemCount !== 1 ? 's' : ''}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Amount:</span>
                        <span class="font-medium">${this.escapeHtml(order.formatted_totals?.total_amount || order.formatted_total)}</span>
                    </div>
                </div>
            </div>

            ${items && items.length > 0 ? `
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <h4 class="font-semibold text-gray-900 mb-3">Order Items</h4>
                <div class="space-y-3 max-h-40 overflow-y-auto">
                    ${items.slice(0, 3).map(item => `
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                            <div class="flex items-center gap-3">
                                <img src="${item.image || 'assets/images/placeholder-product.jpg'}" 
                                     alt="${this.escapeHtml(item.product_name)}" 
                                     class="w-10 h-10 rounded-lg object-cover">
                                <div>
                                    <p class="font-medium text-sm text-gray-900">${this.escapeHtml(item.product_name)}</p>
                                    <p class="text-xs text-gray-500">Qty: ${item.quantity}</p>
                                </div>
                            </div>
                            <span class="font-medium text-sm">${this.escapeHtml(item.formatted_price)}</span>
                        </div>
                    `).join('')}
                    ${items.length > 3 ? `
                        <div class="text-center text-sm text-gray-500 pt-2">
                            and ${items.length - 3} more item${items.length - 3 !== 1 ? 's' : ''}
                        </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}

            <div class="text-center text-sm text-gray-600">
                <p>For detailed tracking information and shipping updates, please visit the order details page.</p>
            </div>

            <div class="flex justify-center space-x-4 pt-4">
                <button onclick="ordersManager.hideTrackingModal()" 
                        class="bg-purple-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-purple-700 transition-colors">
                    Close
                </button>
                <button onclick="window.location.href='order_details.php?order_id=${order.id}'" 
                        class="border border-gray-300 text-gray-700 px-6 py-2 rounded-xl font-semibold hover:bg-gray-50 transition-colors">
                    View Full Details
                </button>
            </div>
        </div>
    `;

            this.showTrackingModal('Order Tracking', modalContent);
        }

        showTrackingModal(title, content) {
            // Create or update tracking modal
            let modal = document.getElementById('tracking-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'tracking-modal';
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden p-4';
                modal.innerHTML = `
            <div class="bg-white rounded-2xl max-w-md w-full max-h-[80vh] overflow-hidden animate-scale-in">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900" id="tracking-modal-title"></h3>
                    <button onclick="ordersManager.hideTrackingModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[calc(80vh-140px)]" id="tracking-modal-content"></div>
            </div>
        `;
                document.body.appendChild(modal);
            }

            document.getElementById('tracking-modal-title').textContent = title;
            document.getElementById('tracking-modal-content').innerHTML = content;
            modal.classList.remove('hidden');
        }

        hideTrackingModal() {
            const modal = document.getElementById('tracking-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Also update the trackOrder method to ensure it's passing items correctly
        async trackOrder(orderId, orderNumber) {
            try {
                const response = await fetch(`ajax/orders.php?action=get_order_details&order_id=${orderId}`);
                const data = await response.json();

                if (data.success) {
                    // Make sure we're passing both order and items
                    this.showTrackingInfo(data.order, data.items || []);
                } else {
                    showNotification(data.message || 'Failed to load order details', 'error');
                }
            } catch (error) {
                console.error('Error tracking order:', error);
                showNotification('Failed to load tracking information', 'error');
            }
        }

        // CANCEL ORDER FUNCTIONALITY - Using header.php confirmation modal
        async cancelOrder(orderId) {
            showConfirmationModal(
                'Cancel Order',
                'Are you sure you want to cancel this order? This action cannot be undone.',
                () => this.performCancelOrder(orderId), {
                    type: 'warning',
                    confirmText: 'Cancel Order',
                    cancelText: 'Keep Order'
                }
            );
        }

        async performCancelOrder(orderId) {
            try {
                const formData = new FormData();
                formData.append('order_id', orderId);

                const response = await fetch('ajax/orders.php?action=cancel_order', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Order cancelled successfully', 'success');
                    this.loadOrders();
                    this.loadOrderCount();
                    this.loadStatusCounts();
                } else {
                    showNotification(data.message || 'Failed to cancel order', 'error');
                }
            } catch (error) {
                console.error('Error cancelling order:', error);
                showNotification('Failed to cancel order', 'error');
            }
        }

        // RETURN ORDER FUNCTIONALITY (Placeholder)
        initiateReturn(orderId) {
            showNotification('Return functionality coming soon!', 'info');
        }

        // MODAL MANAGEMENT
        showCustomModal(title, content) {
            // Use the header.php confirmation modal for custom modals
            showConfirmationModal(
                title,
                content,
                () => {}, // Empty callback since this is just for display
                {
                    type: 'info',
                    confirmText: 'Close',
                    cancelText: ''
                }
            );
        }

        // UI Helper Methods
        showLoading(show) {
            const loading = document.getElementById('loading-orders');
            loading.classList.toggle('hidden', !show);
        }

        showEmptyState() {
            document.getElementById('empty-orders').classList.remove('hidden');
            document.getElementById('orders-grid').classList.add('hidden');
            document.getElementById('pagination').classList.add('hidden');
        }

        showOrdersGrid() {
            document.getElementById('empty-orders').classList.add('hidden');
            document.getElementById('orders-grid').classList.remove('hidden');
        }

        // Utility Methods
        escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return String(unsafe)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        getStatusColor(status) {
            const colors = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'processing': 'bg-blue-100 text-blue-800',
                'shipped': 'bg-purple-100 text-purple-800',
                'delivered': 'bg-green-100 text-green-800',
                'cancelled': 'bg-red-100 text-red-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }

        formatStatus(status) {
            const statusMap = {
                'pending': 'Pending',
                'processing': 'Processing',
                'shipped': 'Shipped',
                'delivered': 'Delivered',
                'cancelled': 'Cancelled'
            };
            return statusMap[status] || status;
        }

        updateCartCount(cartCount) {
            console.log('Updating cart count to:', cartCount);

            // Update header cart count
            const headerCount = document.getElementById('cart-count');
            if (headerCount) {
                headerCount.textContent = cartCount;
                // Add animation for visual feedback
                headerCount.classList.add('animate-pulse');
                setTimeout(() => {
                    headerCount.classList.remove('animate-pulse');
                }, 1000);
            }

            // Update localStorage for persistence
            localStorage.setItem('cartCount', cartCount);

            // Update any other cart count elements
            const cartCountElements = document.querySelectorAll('.cart-count, [data-cart-count]');
            cartCountElements.forEach(element => {
                element.textContent = cartCount;
            });

            // If there's a global cart count update function, call it
            if (typeof updateCartCount === 'function') {
                updateCartCount(cartCount);
            }

            // Dispatch custom event for other components to listen to
            window.dispatchEvent(new CustomEvent('cartUpdated', {
                detail: {
                    count: cartCount
                }
            }));
        }
    }

    // Compact Product Slider Functionality for Order Page
    function scrollFeaturedProducts(direction) {
        const grid = document.getElementById('featured-products-grid');
        const containerWidth = grid.clientWidth;
        const scrollAmount = containerWidth;

        grid.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    }

    function scrollTopSellingProducts(direction) {
        const grid = document.getElementById('top-selling-products-grid');
        const containerWidth = grid.clientWidth;
        const scrollAmount = containerWidth;

        grid.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    }

     // Add to Cart function for compact products
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

    // Initialize orders manager
    const ordersManager = new OrdersManager();
</script>

<?php
require_once 'includes/footer.php';
?>