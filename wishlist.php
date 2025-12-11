<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Initialize database and functions
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];

// Page metadata
$page_title = 'My Wishlist';
$meta_description = 'View and manage your wishlist items';
?>
<?php require_once 'includes/header.php'; ?>
<!-- Confirmation Modal -->
<div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="modal-content">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2" id="modal-title">Confirm Action</h3>
            <p class="text-gray-600 text-center mb-6" id="modal-message">Are you sure you want to proceed?</p>
        </div>
        <div class="flex space-x-3 px-6 py-4 bg-gray-50 rounded-b-lg">
            <button id="modal-cancel-btn" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors font-medium">
                Cancel
            </button>
            <button id="modal-confirm-btn" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
                Confirm
            </button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
        <span class="text-gray-700">Processing...</span>
    </div>
</div>

<!-- Wishlist Section -->
<section class="py-8 lg:py-4">
    <div class="container mx-auto px-2 max-w-6xl">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">My Wishlist</h1>
                    <p class="text-gray-600" id="wishlist-summary">
                        Loading your wishlist...
                    </p>
                </div>

                <div class="mt-4 md:mt-0 flex space-x-3" id="wishlist-actions" style="display: none;">
                    <a href="products.php"
                        class="bg-purple-600 text-sm text-white px-3 py-2 rounded-md hover:bg-purple-700 transition flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Continue Shopping</span>
                    </a>
                    <button onclick="clearWishlist()"
                        class="bg-gray-200 text-sm text-gray-700 px-3 py-2 rounded-md hover:bg-gray-300 transition flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Clear All</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Wishlist Content -->
        <div id="wishlist-content">
            <!-- Content will be loaded via AJAX -->
            <div class="bg-white rounded-lg shadow-sm p-12 text-center border-b border-gray-200 mb-4">
                <div class="max-w-md mx-auto">
                    <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Loading your wishlist...</h2>
                    <div class="animate-pulse">
                        <div class="h-4 bg-gray-200 rounded w-3/4 mx-auto mb-2"></div>
                        <div class="h-4 bg-gray-200 rounded w-1/2 mx-auto"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

<script>
    // Wishlist Manager Class
    class WishlistManager {
        constructor() {
            this.modalManager = null;
            this.isProcessing = false; // Add processing flag
            this.init();
        }

        init() {
            this.initializeModalManager();
            this.bindGlobalEvents();
            this.loadWishlistData();
        }

        // Modal Management
        initializeModalManager() {
            this.modalManager = new ModalManager();
        }

        // Event Binding
        bindGlobalEvents() {
            // Sync wishlist count across tabs
            window.addEventListener('storage', (e) => {
                if (e.key === 'wishlistCount') {
                    this.updateWishlistCount(e.newValue);
                }
            });

            // Handle clicks on dynamically created elements - FIXED: Use data attributes instead of onclick
            document.addEventListener('click', (e) => {
                if (this.isProcessing) return; // Prevent multiple clicks

                const removeBtn = e.target.closest('[data-action="remove-from-wishlist"]');
                const moveToCartBtn = e.target.closest('[data-action="move-to-cart"]');

                if (removeBtn) {
                    const productId = removeBtn.dataset.productId;
                    if (productId) this.removeFromWishlist(parseInt(productId));
                }

                if (moveToCartBtn) {
                    const productId = moveToCartBtn.dataset.productId;
                    if (productId) this.moveToCart(parseInt(productId));
                }
            });
        }

        // Data Loading
        async loadWishlistData() {
            await Promise.all([
                this.loadWishlist(),
                this.loadWishlistCount()
            ]);
        }

        async loadWishlist() {
            try {
                const response = await fetch('ajax/wishlist.php?action=get_wishlist_items');

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error('Server returned non-JSON response');
                }

                const data = await response.json();

                if (data.success) {
                    this.renderWishlist(data.items);
                    this.updateWishlistSummary(data.items.length);
                    this.updateWishlistCount(data.items.length);
                } else {
                    this.showNotification(data.message || 'Error loading wishlist', 'error');
                }
            } catch (error) {
                console.error('Error loading wishlist:', error);
                this.showNotification('Error loading wishlist: ' + error.message, 'error');
                this.showErrorState();
            }
        }

        async loadWishlistCount() {
            try {
                const response = await fetch('ajax/wishlist.php?action=get_wishlist_count');
                const data = await response.json();

                if (data.success) {
                    this.updateWishlistCount(data.count);
                }
            } catch (error) {
                console.error('Error loading wishlist count:', error);
            }
        }

        // Rendering
        renderWishlist(items) {
            const container = document.getElementById('wishlist-content');

            if (!items || items.length === 0) {
                this.showEmptyState();
                return;
            }

            container.innerHTML = this.generateWishlistHTML(items);
            this.setupImageHandlers();
        }

        generateWishlistHTML(items) {
            const mobileView = this.generateMobileView(items);
            const desktopView = this.generateDesktopView(items);
            const quickActions = this.generateQuickActions(items.length);

            return mobileView + desktopView + quickActions;
        }

        generateMobileView(items) {
            return `
        <div class="md:hidden">
            ${items.map(item => `
                <div class="border-b border-gray-200 bg-white p-4 wishlist-item" data-product-id="${item.product_id}">
                    <div class="flex space-x-4">
                        <a href="product.php?id=${item.product_id}" class="flex-shrink-0">
                            <img src="${item.image}" 
                                 alt="${item.name}" 
                                 class="w-20 h-20 object-cover rounded-lg"
                                 onerror="this.onerror=null; this.src='assets/images/placeholder-product.jpg';">
                        </a>
                        
                        <div class="flex-1 min-w-0">
                            <a href="product.php?id=${item.product_id}" 
            class="font-medium text-gray-800 hover:text-purple-600 line-clamp-2">
                ${item.name}
            </a>

                            
                            <div class="mt-2">
                                ${this.generatePriceHTML(item)}
                            </div>

                            <div class="mt-2">
                                ${this.generateStockStatusHTML(item)}
                            </div>

                            <div class="mt-3 flex space-x-2">
                                ${this.generateMobileActionButtonsHTML(item)}
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-xs text-gray-500">
                        Added on ${new Date(item.date_added).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                    </div>
                </div>
            `).join('')}
        </div>
    `;
        }


        generateDesktopView(items) {
            return `
        <div class="hidden md:block">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Added</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${items.map(item => `
                            <tr class="hover:bg-gray-50 transition-colors duration-150 wishlist-item" data-product-id="${item.product_id}">
                                <td class="px-6 py-4"> <!-- removed whitespace-nowrap -->
    <div class="flex items-center">
        <a href="product.php?id=${item.product_id}" class="flex-shrink-0">
            <img src="${item.image}" 
                 alt="${item.name}" 
                 class="w-16 h-16 object-cover rounded-lg"
                 onerror="this.onerror=null; this.src='assets/images/placeholder-product.jpg';">
        </a>
        <div class="ml-4 max-w-[15rem]">
            <a href="product.php?id=${item.product_id}" 
               class="text-sm font-medium text-gray-800 hover:text-purple-600 line-clamp-2">
                ${item.name}
            </a>
        </div>
    </div>
</td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    ${this.generatePriceHTML(item, true)}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    ${this.generateStockStatusHTML(item)}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    ${new Date(item.date_added).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        ${this.generateActionButtonsHTML(item, true)}
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
        }

        generateQuickActions(itemCount) {
            if (itemCount === 0) return '';

            return `
            <div class="mt-6 bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="flex flex-wrap gap-4">
                    <a href="products.php?filter=discounted" 
                       class="bg-blue-100 text-blue-700 px-4 py-2 rounded-md hover:bg-blue-200 transition flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                        </svg>
                        <span>View Discounted Items</span>
                    </a>
                    <a href="products.php?sort=newest" 
                       class="bg-green-100 text-green-700 px-4 py-2 rounded-md hover:bg-green-200 transition flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>New Arrivals</span>
                    </a>
                </div>
            </div>
        `;
        }

        generatePriceHTML(item, isDesktop = false) {
            if (item.discount > 0) {
                return `
                ${isDesktop ? `
                    <div class="text-sm font-bold text-purple-600">${item.formatted_price}</div>
                    <div class="text-sm text-gray-500 line-through">${item.formatted_original_price}</div>
                ` : `
                    <span class="text-lg font-bold text-purple-600">${item.formatted_price}</span>
                    <span class="text-sm text-gray-500 line-through ml-2">${item.formatted_original_price}</span>
                `}
            `;
            }

            return `
            ${isDesktop ? `
                <div class="text-sm font-bold text-purple-600">${item.formatted_price}</div>
            ` : `
                <span class="text-lg font-bold text-purple-600">${item.formatted_price}</span>
            `}
        `;
        }

        generateStockStatusHTML(item) {
            if (item.stock_quantity > 0) {
                return `
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    In Stock
                </span>
            `;
            }

            return `
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Out of Stock
            </span>
        `;
        }

        generateMobileActionButtonsHTML(item) {
            const moveToCartBtn = item.stock_quantity > 0 ? `
            <button data-action="move-to-cart" 
                    data-product-id="${item.product_id}"
                    class="flex-1 bg-purple-600 text-white text-sm py-3 px-3 rounded-md hover:bg-purple-700 transition text-center flex items-center justify-center space-x-1"
                    title="Move to Cart">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5.5M7 13l2.5 5.5m0 0L17 21m-7.5-2.5h9M17 21v-4a2 2 0 00-2-2h-2m-2.5 2.5L12 14"></path>
                </svg>
                <span class="hidden xs:inline">Cart</span>
            </button>
        ` : '';

            const removeBtn = `
            <button data-action="remove-from-wishlist" 
                    data-product-id="${item.product_id}"
                    class="flex-1 bg-gray-200 text-gray-700 text-sm py-3 px-3 rounded-md hover:bg-gray-300 transition text-center flex items-center justify-center space-x-1"
                    title="Remove from Wishlist">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <span class="hidden xs:inline">Remove</span>
            </button>
        `;

            return moveToCartBtn + removeBtn;
        }

        generateActionButtonsHTML(item, isDesktop = false) {
            const moveToCartBtn = item.stock_quantity > 0 ? `
            <button data-action="move-to-cart" 
                    data-product-id="${item.product_id}"
                    class="${isDesktop ? 'bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition text-sm flex items-center space-x-2' : 'flex-1 bg-purple-600 text-white text-sm py-2 px-3 rounded-md hover:bg-purple-700 transition text-center flex items-center justify-center space-x-1'}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5.5M7 13l2.5 5.5m0 0L17 21m-7.5-2.5h9M17 21v-4a2 2 0 00-2-2h-2m-2.5 2.5L12 14"></path>
                </svg>
                <span>Move to Cart</span>
            </button>
        ` : '';

            const removeBtn = `
            <button data-action="remove-from-wishlist" 
                    data-product-id="${item.product_id}"
                    class="${isDesktop ? 'bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition text-sm flex items-center space-x-2' : 'flex-1 bg-gray-200 text-gray-700 text-sm py-2 px-3 rounded-md hover:bg-gray-300 transition text-center flex items-center justify-center space-x-1'}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <span>Remove</span>
            </button>
        `;

            return isDesktop ? moveToCartBtn + removeBtn : moveToCartBtn + removeBtn;
        }

        // Action Methods
        async removeFromWishlist(productId) {
            if (this.isProcessing) return;

            const confirmed = await this.showConfirmation(
                'Remove Item',
                'Are you sure you want to remove this item from your wishlist?',
                'Remove',
                'Keep Item'
            );

            if (!confirmed) return;

            this.setProcessingState(true);
            const item = this.getItemElement(productId);
            this.setItemLoadingState(item, true);

            try {
                const response = await fetch('ajax/wishlist.php?action=remove_from_wishlist', {
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
                    this.showNotification('Item removed from wishlist', 'success');
                    this.updateWishlistCount(data.wishlist_count);
                    await this.loadWishlist();
                } else {
                    this.showNotification(data.message || 'Error removing item', 'error');
                    this.setItemLoadingState(item, false);
                }
            } catch (error) {
                console.error('Error removing from wishlist:', error);
                this.showNotification('Error removing item', 'error');
                this.setItemLoadingState(item, false);
            } finally {
                this.setProcessingState(false);
            }
        }

        async clearWishlist() {
            if (this.isProcessing) return;

            const confirmed = await this.showConfirmation(
                'Clear Wishlist',
                'Are you sure you want to clear your entire wishlist? This action cannot be undone.',
                'Clear All',
                'Cancel'
            );

            if (!confirmed) return;

            this.setProcessingState(true);
            this.showLoading('Clearing wishlist...');

            try {
                const response = await fetch('ajax/wishlist.php?action=clear_wishlist');
                const data = await response.json();

                if (data.success) {
                    this.showNotification('Wishlist cleared successfully', 'success');
                    this.updateWishlistCount(0);
                    await this.loadWishlist();
                } else {
                    this.showNotification(data.message || 'Error clearing wishlist', 'error');
                }
            } catch (error) {
                console.error('Error clearing wishlist:', error);
                this.showNotification('Error clearing wishlist', 'error');
            } finally {
                this.hideLoading();
                this.setProcessingState(false);
            }
        }

        async moveToCart(productId) {
            if (this.isProcessing) return;

            this.setProcessingState(true);
            const item = this.getItemElement(productId);
            this.setItemLoadingState(item, true);
            this.showLoading('Moving to cart...');

            try {
                // First remove from wishlist
                const removeResponse = await fetch('ajax/wishlist.php?action=remove_from_wishlist', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                });

                const removeData = await removeResponse.json();

                if (removeData.success) {
                    // Then add to cart
                    const cartResponse = await fetch('ajax/cart.php?action=add_to_cart', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: 1
                        })
                    });

                    const cartData = await cartResponse.json();

                    if (cartData.success) {
                        this.showNotification('Product moved to cart successfully!', 'success');
                        this.updateWishlistCount(removeData.wishlist_count);
                        this.updateCartCount(cartData.cart_count);
                        await this.loadWishlist();
                    } else {
                        this.showNotification(cartData.message || 'Error adding to cart', 'error');
                        this.setItemLoadingState(item, false);
                    }
                } else {
                    this.showNotification(removeData.message || 'Error removing from wishlist', 'error');
                    this.setItemLoadingState(item, false);
                }
            } catch (error) {
                console.error('Error moving to cart:', error);
                this.showNotification('Error moving to cart: ' + error.message, 'error');
                this.setItemLoadingState(item, false);
            } finally {
                this.hideLoading();
                this.setProcessingState(false);
            }
        }

        // Utility Methods
        getItemElement(productId) {
            return document.querySelector(`.wishlist-item[data-product-id="${productId}"]`);
        }

        setItemLoadingState(item, isLoading) {
            if (!item) return;

            if (isLoading) {
                item.style.opacity = '0.5';
                item.style.pointerEvents = 'none';
            } else {
                item.style.opacity = '1';
                item.style.pointerEvents = 'auto';
            }
        }

        setProcessingState(isProcessing) {
            this.isProcessing = isProcessing;

            // Disable/enable all action buttons
            const actionButtons = document.querySelectorAll('[data-action]');
            actionButtons.forEach(button => {
                if (isProcessing) {
                    button.disabled = true;
                    button.style.opacity = '0.6';
                    button.style.cursor = 'not-allowed';
                } else {
                    button.disabled = false;
                    button.style.opacity = '1';
                    button.style.cursor = 'pointer';
                }
            });
        }

        setupImageHandlers() {
            const images = document.querySelectorAll('#wishlist-content img');
            images.forEach(img => {
                img.addEventListener('error', () => {
                    img.src = 'assets/images/placeholder-product.jpg';
                });
            });
        }

        // UI State Management
        showEmptyState() {
            const container = document.getElementById('wishlist-content');
            container.innerHTML = `
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <div class="max-w-md mx-auto">
                    <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Your wishlist is empty</h2>
                    <p class="text-gray-600 mb-8">
                        Start adding items you love to your wishlist. They'll be saved here for you to access anytime.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="products.php" 
                           class="bg-purple-600 text-white px-8 py-3 rounded-md hover:bg-purple-700 transition font-medium">
                            Start Shopping
                        </a>
                        <a href="account.php" 
                           class="bg-gray-200 text-gray-700 px-8 py-3 rounded-md hover:bg-gray-300 transition font-medium">
                            View Account
                        </a>
                    </div>
                </div>
            </div>
        `;
        }

        showErrorState() {
            const container = document.getElementById('wishlist-content');
            container.innerHTML = `
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <div class="max-w-md mx-auto">
                    <svg class="w-16 h-16 mx-auto text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Unable to Load Wishlist</h2>
                    <p class="text-gray-600 mb-4">
                        There was an error loading your wishlist. Please try again.
                    </p>
                    <button onclick="wishlistManager.loadWishlist()" class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 transition">
                        Retry
                    </button>
                </div>
            </div>
        `;
        }

        updateWishlistSummary(count) {
            const summary = document.getElementById('wishlist-summary');
            const actions = document.getElementById('wishlist-actions');

            if (summary && actions) {
                if (count > 0) {
                    summary.textContent = `You have ${count} item${count !== 1 ? 's' : ''} in your wishlist`;
                    actions.style.display = 'flex';
                } else {
                    summary.textContent = 'Your wishlist is empty';
                    actions.style.display = 'none';
                }
            }
        }

        updateWishlistCount(count) {
            const wishlistCountElement = document.getElementById('wishlist-count');
            if (wishlistCountElement) {
                wishlistCountElement.textContent = count;
                localStorage.setItem('wishlistCount', count);
            }
        }

        updateCartCount(count) {
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = count;
                localStorage.setItem('cartCount', count);
            }
        }

        // Modal and Notification Methods
        async showConfirmation(title, message, confirmText = 'Confirm', cancelText = 'Cancel') {
            if (this.modalManager && this.modalManager.modal) {
                return await this.modalManager.show(title, message, confirmText, cancelText);
            } else {
                return confirm(`${title}\n\n${message}`);
            }
        }

        showLoading(message = 'Processing...') {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                const messageEl = overlay.querySelector('span');
                messageEl.textContent = message;
                overlay.classList.remove('hidden');
            }
        }

        hideLoading() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.classList.add('hidden');
            }
        }

        showNotification(message, type = 'info') {
            if (typeof showNotification === 'function') {
                showNotification(message, type);
            } else {
                // Fallback notification
                console.log(`${type.toUpperCase()}: ${message}`);
            }
        }
    }

    // Modal Manager Class
    class ModalManager {
        constructor() {
            this.modal = document.getElementById('confirmation-modal');
            this.modalContent = document.getElementById('modal-content');
            this.modalTitle = document.getElementById('modal-title');
            this.modalMessage = document.getElementById('modal-message');
            this.modalCancelBtn = document.getElementById('modal-cancel-btn');
            this.modalConfirmBtn = document.getElementById('modal-confirm-btn');

            this.currentResolve = null;

            if (this.isModalAvailable()) {
                this.bindModalEvents();
            }
        }

        isModalAvailable() {
            return this.modal && this.modalCancelBtn && this.modalConfirmBtn;
        }

        bindModalEvents() {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.hide(false);
                }
            });

            this.modalCancelBtn.addEventListener('click', () => {
                this.hide(false);
            });

            this.modalConfirmBtn.addEventListener('click', () => {
                this.hide(true);
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !this.modal.classList.contains('hidden')) {
                    this.hide(false);
                }
            });
        }

        show(title, message, confirmText = 'Confirm', cancelText = 'Cancel') {
            return new Promise((resolve) => {
                this.currentResolve = resolve;

                this.modalTitle.textContent = title;
                this.modalMessage.textContent = message;
                this.modalConfirmBtn.textContent = confirmText;
                this.modalCancelBtn.textContent = cancelText;

                this.modal.classList.remove('hidden');
                setTimeout(() => {
                    this.modalContent.classList.remove('scale-95', 'opacity-0');
                    this.modalContent.classList.add('scale-100', 'opacity-100');
                }, 50);
            });
        }

        hide(confirmed = false) {
            this.modalContent.classList.remove('scale-100', 'opacity-100');
            this.modalContent.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                this.modal.classList.add('hidden');

                if (this.currentResolve) {
                    this.currentResolve(confirmed);
                    this.currentResolve = null;
                }
            }, 300);
        }
    }

    // Global wishlist manager instance
    let wishlistManager;

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        wishlistManager = new WishlistManager();
    });

    // Global functions for onclick handlers
    function clearWishlist() {
        if (wishlistManager) {
            wishlistManager.clearWishlist();
        }
    }
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

    .wishlist-item {
        transition: all 0.3s ease;
    }

    .wishlist-item.removing {
        opacity: 0.5;
        transform: translateX(-100%);
    }

    /* Smooth transitions for table rows */
    tbody tr {
        transition: all 0.2s ease-in-out;
    }

    /* Responsive table */
    @media (max-width: 768px) {
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

    /* Loading animation */
    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .loading {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Extra small screen responsive text */
    @media (max-width: 475px) {
        .xs\:hidden {
            display: none;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>