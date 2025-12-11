<?php
require_once 'includes/header.php';

?>
<style>
    .coupon-success {
        border-color: #10b981 !important;
        background-color: #f0fdf4 !important;
    }

    .coupon-error {
        border-color: #ef4444 !important;
        background-color: #fef2f2 !important;
    }

    .discount-line {
        color: #10b981;
        font-weight: 600;
    }

    .coupon-applied {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    
</style>
<div class="container mx-auto px-2 max-w-6xl py-8 md:py-2 lg:py-2 pb-2">
    <nav class="flex text-xs ">
        <nav class="flex items-center justify-center lg:justify-start space-x-2 text-xs text-gray-600">
            <a href="index.php" class="hover:text-purple-600 transition-colors">Home</a>
            <span class="text-gray-400">›</span>
            <span class="text-gray-600 font-medium">Cart</span>
        </nav>
    </nav>
</div>

<div class=" lg:py-1 ">
    <div class="container mx-auto px-2 max-w-6xl lg:mb-8 ">
        <!-- Cart Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-2">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <!-- Cart Header -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="p-3 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <h2 class="text-xl font-semibold text-gray-900">Cart</h2>
                            </div>
                            <div class="flex items-center gap-3">
                                <button id="remove-selected-btn" class="hidden px-3 py-1.5 text-purple-600 text-sm rounded-lg hover:bg-red-200 transition-colors font-medium">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Remove Selected (<span id="selected-count">0</span>)
                                </button>
                                <span class="text-sm text-gray-500" id="cart-items-count">
                                    <?php echo $functions->getCartItemCount(); ?> items
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Items List -->
                    <div id="cart-items-container">
                        <!-- Cart items will be loaded here via AJAX -->
                        <div class="p-8 text-center">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto mb-4"></div>
                            <p class="text-gray-600">Loading cart items...</p>
                        </div>
                    </div>

                    <!-- Empty Cart State -->
                    <div id="empty-cart" class="hidden p-12 text-center">
                        <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Your cart is empty</h3>
                        <p class="text-gray-500 mb-6">Start shopping to add items to your cart</p>
                        <a href="products.php" class="inline-flex items-center px-6 py-3 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            Start Shopping
                        </a>
                    </div>
                </div>

                <!-- Continue Shopping -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                        <a href="products.php" class="flex items-center text-purple-600 hover:text-purple-700 font-medium">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Continue Shopping
                        </a>
                        <button id="clear-cart-btn" class="px-3 py-1.5 text-sm border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors font-medium">
                            Clear Cart
                        </button>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 sticky top-32">
                    <div class="p-3 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Order Summary</h3>
                    </div>

                    <div class="p-6">
                        <!-- Coupon Section -->
                        <div class="mb-6">
                            <div id="coupon-section">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Have a coupon code?</label>
                                <div class="flex space-x-2">
                                    <input type="text"
                                        id="coupon-code"
                                        placeholder="Enter coupon code"
                                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <button id="apply-coupon-btn"
                                        class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        Apply
                                    </button>
                                </div>
                                <div id="coupon-message" class="mt-2 text-sm hidden"></div>
                            </div>

                            <!-- Applied Coupon Display -->
                            <div id="applied-coupon" class="hidden mt-3 p-3 rounded-lg coupon-applied">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="font-medium" id="applied-coupon-code"></span>
                                        <span class="text-sm opacity-90 ml-2" id="applied-coupon-discount"></span>
                                    </div>
                                    <button id="remove-coupon-btn" class="text-white hover:text-gray-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Details -->
                        <div id="cart-summary">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
                        </div>

                        <!-- Checkout Button -->
                        <button id="checkout-btn" class="w-full bg-purple-600 text-white text-sm py-3 px-1.5 rounded-lg font-semibold hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed mt-6">
                            Proceed to Checkout
                        </button>

                        <!-- Security Badges -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex items-center justify-center space-x-6 text-gray-400">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-xs">Secure</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-xs">Guaranteed</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
        <span class="text-gray-700">Updating cart...</span>
    </div>
</div>

<script>
    class CartManager {
        constructor() {
            this.appliedCoupon = null;
            this.init();
        }

        init() {
            this.loadCartItems();
            this.loadCartSummary();
            this.bindEvents();
            this.checkAppliedCoupon();
        }

        bindEvents() {
            // Clear cart button
            document.getElementById('clear-cart-btn').addEventListener('click', () => {
                this.clearCart();
            });

            // Checkout button
            document.getElementById('checkout-btn').addEventListener('click', () => {
                this.proceedToCheckout();
            });

            // Coupon events
            document.getElementById('apply-coupon-btn').addEventListener('click', () => {
                this.applyCoupon();
            });

            document.getElementById('remove-coupon-btn').addEventListener('click', () => {
                this.removeCoupon();
            });

            // Enter key for coupon input
            document.getElementById('coupon-code').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.applyCoupon();
                }
            });

            // Remove selected button
            document.getElementById('remove-selected-btn').addEventListener('click', () => {
                this.removeSelected();
            });
        }

        async loadCartItems() {
            try {
                const response = await fetch('ajax/cart.php?action=get_cart_preview');
                const data = await response.json();

                if (data.success && data.items && data.items.length > 0) {
                    this.renderCartItems(data.items);
                    this.updateCartCount(data.totalItems);
                } else {
                    this.showEmptyCart();
                }
            } catch (error) {
                console.error('Error loading cart items:', error);
                this.showError('Failed to load cart items');
            }
        }

        async loadCartSummary() {
            try {
                const params = new URLSearchParams();
                if (this.appliedCoupon) {
                    params.append('coupon_code', this.appliedCoupon.code);
                }

                const response = await fetch(`ajax/cart.php?action=get_cart_summary&${params}`);
                const data = await response.json();

                if (data.success) {
                    this.renderCartSummary(data);
                }
            } catch (error) {
                console.error('Error loading cart summary:', error);
            }
        }

        async applyCoupon() {
            const couponCode = document.getElementById('coupon-code').value.trim();
            const couponBtn = document.getElementById('apply-coupon-btn');
            const couponMessage = document.getElementById('coupon-message');

            if (!couponCode) {
                this.showCouponMessage('Please enter a coupon code', 'error');
                return;
            }

            couponBtn.disabled = true;
            couponBtn.textContent = 'Applying...';

            try {
                const response = await fetch('ajax/cart.php?action=apply_coupon', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        coupon_code: couponCode
                    })
                });

                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response');
                }

                const data = await response.json();

                if (data.success) {
                    this.appliedCoupon = {
                        code: couponCode,
                        discount_amount: data.discount_amount,
                        discount_type: data.discount_type,
                        discount_value: data.discount_value
                    };

                    this.showAppliedCoupon();
                    this.showCouponMessage(data.message, 'success');
                    await this.loadCartSummary();

                    // Clear input
                    document.getElementById('coupon-code').value = '';
                } else {
                    this.showCouponMessage(data.message, 'error');
                }
            } catch (error) {
                console.error('Error applying coupon:', error);
                this.showCouponMessage('Failed to apply coupon. Please try again.', 'error');
            } finally {
                couponBtn.disabled = false;
                couponBtn.textContent = 'Apply';
            }
        }

        async removeCoupon() {
            try {
                const response = await fetch('ajax/cart.php?action=remove_coupon', {
                    method: 'POST'
                });

                const data = await response.json();

                if (data.success) {
                    this.appliedCoupon = null;
                    this.hideAppliedCoupon();
                    this.showCouponMessage(data.message, 'success');
                    await this.loadCartSummary();
                } else {
                    this.showCouponMessage(data.message, 'error');
                }
            } catch (error) {
                console.error('Error removing coupon:', error);
                this.showCouponMessage('Failed to remove coupon', 'error');
            }
        }

        showAppliedCoupon() {
            const appliedCouponSection = document.getElementById('applied-coupon');
            const couponCodeElement = document.getElementById('applied-coupon-code');
            const couponDiscountElement = document.getElementById('applied-coupon-discount');

            if (this.appliedCoupon) {
                couponCodeElement.textContent = this.appliedCoupon.code;

                let discountText = '';
                if (this.appliedCoupon.type === 'percentage') {
                    discountText = `-${this.appliedCoupon.value}%`;
                } else {
                    discountText = `-GHS ${parseFloat(this.appliedCoupon.value).toFixed(2)}`;
                }

                couponDiscountElement.textContent = discountText;
                appliedCouponSection.classList.remove('hidden');
            }
        }

        hideAppliedCoupon() {
            const appliedCouponSection = document.getElementById('applied-coupon');
            appliedCouponSection.classList.add('hidden');
        }

        showCouponMessage(message, type) {
            const couponMessage = document.getElementById('coupon-message');
            const couponInput = document.getElementById('coupon-code');

            couponMessage.textContent = message;
            couponMessage.className = 'mt-2 text-sm';

            if (type === 'success') {
                couponMessage.classList.add('text-green-600');
                couponInput.classList.add('coupon-success');
                couponInput.classList.remove('coupon-error');
            } else {
                couponMessage.classList.add('text-red-600');
                couponInput.classList.add('coupon-error');
                couponInput.classList.remove('coupon-success');
            }

            couponMessage.classList.remove('hidden');

            // Auto-hide success messages after 3 seconds
            if (type === 'success') {
                setTimeout(() => {
                    couponMessage.classList.add('hidden');
                    couponInput.classList.remove('coupon-success');
                }, 3000);
            }
        }

        async checkAppliedCoupon() {
            try {
                const response = await fetch('ajax/cart.php?action=get_applied_coupon');
                const data = await response.json();

                if (data.success && data.coupon) {
                    this.appliedCoupon = {
                        code: data.coupon.code,
                        discount: data.coupon.discount_amount,
                        type: data.coupon.discount_type,
                        value: data.coupon.discount_value
                    };
                    this.showAppliedCoupon();
                }
            } catch (error) {
                console.error('Error checking applied coupon:', error);
            }
        }

        renderCartSummary(data) {
            const container = document.getElementById('cart-summary');

            console.log('Cart summary data:', data); // Debug log

            // Use safe property access with fallbacks
            const subtotal = data.formatted_total || data.formatted_subtotal || 'GHS 0.00';
            const discountAmount = data.discount_amount || 0;
            const formattedDiscount = data.formatted_discount || 'GHS 0.00';
            const shippingCost = data.shipping_cost || 'Free';
            const taxAmount = data.tax_amount || 'GHS 0.00';
            const cartCount = data.cart_count || 0;
            
            // Set grandTotal to 0 if cart is empty, otherwise use the calculated total
            const grandTotal = cartCount === 0 ? 'GHS 0.00' : (data.grand_total || data.formatted_total || 'GHS 0.00');

            let summaryHTML = `
        <div class="space-y-4">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Subtotal</span>
                <span class="font-medium">${subtotal}</span>
            </div>`;

            // Add discount line if coupon is applied and discount amount > 0
            if (discountAmount > 0) {
                summaryHTML += `
            <div class="flex justify-between text-sm discount-line">
                <span>Discount</span>
                <span>-${formattedDiscount}</span>
            </div>`;
            }

            // Only show shipping and tax if cart is not empty
            if (cartCount > 0) {
                summaryHTML += `
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Shipping</span>
                <span class="font-medium text-green-600">${shippingCost}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Tax</span>
                <span class="font-medium">${taxAmount}</span>
            </div>`;
            }

            summaryHTML += `
            <div class="border-t border-gray-200 pt-4">
                <div class="flex justify-between text-lg font-semibold">
                    <span>Total</span>
                    <span>${grandTotal}</span>
                </div>
            </div>
        </div>`;

            container.innerHTML = summaryHTML;

            // Update checkout button state
            const checkoutBtn = document.getElementById('checkout-btn');
            checkoutBtn.disabled = cartCount === 0;
        }


        renderCartItems(items) {
            const container = document.getElementById('cart-items-container');
            const emptyCart = document.getElementById('empty-cart');

            container.innerHTML = '';
            emptyCart.classList.add('hidden');

            items.forEach((item, index) => {
                const itemElement = this.createCartItemElement(item, index);
                container.appendChild(itemElement);
            });
        }

        createCartItemElement(item, index) {
            const itemTotal = item.price * item.quantity;

            return this.htmlToElement(`
        <div class="p-4 border-b border-gray-200 last:border-b-0 cart-item" data-key="${item.key}">
            <!-- Mobile Layout -->
            <div class="flex flex-col sm:hidden space-y-4">
                <!-- Product Image and Name Row -->
                <div class="flex space-x-2">
                    <!-- Checkbox -->
                    <div class="flex items-start pt-1">
                        <input type="checkbox" class="item-checkbox w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500" data-key="${item.key}">
                    </div>
                    <!-- Product Image -->
                    <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg overflow-hidden">
                        <img src="${item.image || 'assets/images/placeholder-product.jpg'}" 
                             alt="${item.name}" 
                             class="w-full h-full object-cover">
                    </div>
                    
                    <!-- Product Name and Price -->
                    <div class="flex-1">
                        <h3 class="text-sm  text-gray-700 line-clamp-2">${item.name}</h3>
                        ${item.size ? `<div class="text-xs text-gray-500 mt-1">Size: <span class="font-medium text-gray-700">${item.size}</span></div>` : ''}
                        <p class="text-purple-600 text-sm font-semibold">${this.formatPrice(item.price)}</p>
                    </div>
                    
                    <!-- Remove Button -->
                    <button class="remove-item-btn text-gray-400 hover:text-red-500 transition-colors" 
                            data-key="${item.key}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-.894-.553L7 5h10l-.106.447A1 1 0 0116 7m-7 0h6" />
                    </svg>
                    </button>
                </div>
                
                <!-- Quantity and Total Row -->
                <div class="flex justify-between items-center">
                    <!-- Quantity Controls -->
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Qty:</span>
                        <div class="flex items-center border border-gray-300 rounded-lg">
                            <button class="decrease-qty w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors" 
                                    data-key="${item.key}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </button>
                            <input type="number" 
                                   value="${item.quantity}" 
                                   min="1" 
                                   max="99"
                                   class="quantity-input w-12 h-8 text-center border-0 focus:ring-0 focus:outline-none"
                                   data-key="${item.key}">
                            <button class="increase-qty w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors" 
                                    data-key="${item.key}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Item Total -->
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Total</p>
                        <p class="text-base font-semibold text-gray-900">${this.formatPrice(itemTotal)}</p>
                    </div>
                </div>
            </div>
            
            <!-- Desktop Layout -->
            <div class="hidden sm:flex space-x-4">
                <!-- Checkbox -->
                <div class="flex items-center">
                    <input type="checkbox" class="item-checkbox w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500" data-key="${item.key}">
                </div>
                <!-- Product Image -->
                <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg overflow-hidden">
                    <img src="${item.image || 'assets/images/placeholder-product.jpg'}" 
                         alt="${item.name}" 
                         class="w-full h-full object-cover">
                </div>
                
                <!-- Product Details -->
                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="text-md text-gray-700 mb-1">${item.name}</h3>
                            ${item.size ? `<div class="text-xs text-gray-500">Size: <span class="font-medium text-gray-700">${item.size}</span></div>` : ''}
                            <p class="text-purple-600 font-semibold text-sm">${this.formatPrice(item.price)}</p>
                        </div>
                        <button class="remove-item-btn text-gray-400 hover:text-red-500 transition-colors ml-4" 
                                data-key="${item.key}">
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-.894-.553L7 5h10l-.106.447A1 1 0 0116 7m-7 0h6" />
                        </svg>
                        </button>
                    </div>
                    
                    <!-- Quantity Controls -->
                    <div class="flex items-center justify-between mt-1">
                        <div class="flex items-center space-x-3">
                            <span class="text-sm text-gray-600">Quantity:</span>
                            <div class="flex items-center border border-gray-300 rounded-lg">
                                <button class="decrease-qty w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors" 
                                        data-key="${item.key}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                    </svg>
                                </button>
                                <input type="number" 
                                       value="${item.quantity}" 
                                       min="1" 
                                       max="99"
                                       class="quantity-input w-12 h-8 text-center border-0 focus:ring-0 focus:outline-none"
                                       data-key="${item.key}">
                                <button class="increase-qty w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors" 
                                        data-key="${item.key}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Total</p>
                            <p class="text-md font-semibold text-gray-900">${this.formatPrice(itemTotal)}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);
        }


        async updateQuantity(cartKey, newQuantity) {
            if (newQuantity < 1) return;

            this.showLoading();

            try {
                const formData = new FormData();
                formData.append('cart_key', cartKey);
                formData.append('quantity', newQuantity);

                const response = await fetch('ajax/cart.php?action=update_cart_quantity', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    await this.loadCartItems();
                    await this.loadCartSummary();
                    this.updateHeaderCartCount(data.cart_count);
                    this.showSuccess('Cart updated successfully');
                } else {
                    this.showError(data.message || 'Failed to update quantity');
                }
            } catch (error) {
                console.error('Error updating quantity:', error);
                this.showError('Failed to update quantity');
            } finally {
                this.hideLoading();
            }
        }

        async removeItem(cartKey) {
            const confirmed = await this.showConfirmModal(
                'Remove Item',
                'Are you sure you want to remove this item from your cart?',
                'warning'
            );

            if (!confirmed) {
                return;
            }

            this.showLoading();

            try {
                const formData = new FormData();
                formData.append('cart_key', cartKey);

                const response = await fetch('ajax/cart.php?action=remove_from_cart', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    await this.loadCartItems();
                    await this.loadCartSummary();
                    this.updateHeaderCartCount(data.cart_count);
                    this.showSuccess('Item removed from cart');
                } else {
                    this.showError(data.message || 'Failed to remove item');
                }
            } catch (error) {
                console.error('Error removing item:', error);
                this.showError('Failed to remove item');
            } finally {
                this.hideLoading();
            }
        }

        async clearCart() {
            const confirmed = await this.showConfirmModal(
                'Clear Cart',
                'Are you sure you want to clear your entire cart? This action cannot be undone.',
                'error'
            );

            if (!confirmed) {
                return;
            }

            this.showLoading();

            try {
                const response = await fetch('ajax/cart.php?action=clear_cart', {
                    method: 'POST'
                });

                const data = await response.json();

                if (data.success) {
                    this.showEmptyCart();
                    await this.loadCartSummary();
                    this.updateHeaderCartCount(0);
                    this.showSuccess('Cart cleared successfully');
                } else {
                    this.showError(data.message || 'Failed to clear cart');
                }
            } catch (error) {
                console.error('Error clearing cart:', error);
                this.showError('Failed to clear cart');
            } finally {
                this.hideLoading();
            }
        }

        updateRemoveSelectedButton() {
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
            const removeBtn = document.getElementById('remove-selected-btn');
            const selectedCount = document.getElementById('selected-count');

            // Update selected count
            selectedCount.textContent = checkedBoxes.length;

            // Show/hide remove button
            if (checkedBoxes.length > 0) {
                removeBtn.classList.remove('hidden');
            } else {
                removeBtn.classList.add('hidden');
            }
        }

        async removeSelected() {
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                this.showError('Please select items to remove');
                return;
            }

            const confirmed = await this.showConfirmModal(
                'Remove Selected Items',
                `Are you sure you want to remove ${checkedBoxes.length} item(s) from your cart?`,
                'warning'
            );

            if (!confirmed) {
                return;
            }

            this.showLoading();

            const cartKeys = Array.from(checkedBoxes).map(cb => cb.dataset.key);

            try {
                const response = await fetch('ajax/cart.php?action=bulk_remove', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cart_keys: cartKeys
                    })
                });

                const data = await response.json();

                if (data.success) {
                    await this.loadCartItems();
                    await this.loadCartSummary();
                    this.updateHeaderCartCount(data.cart_count);
                    this.showSuccess(`${checkedBoxes.length} item(s) removed from cart`);
                    this.updateRemoveSelectedButton();
                } else {
                    this.showError(data.message || 'Failed to remove items');
                }
            } catch (error) {
                console.error('Error removing items:', error);
                this.showError('Failed to remove items');
            } finally {
                this.hideLoading();
            }
        }

        proceedToCheckout() {
            const checkoutBtn = document.getElementById('checkout-btn');
            if (checkoutBtn.disabled) return;

            // Redirect to checkout page
            window.location.href = 'checkout.php';
        }

        showEmptyCart() {
            const container = document.getElementById('cart-items-container');
            const emptyCart = document.getElementById('empty-cart');

            container.innerHTML = '';
            emptyCart.classList.remove('hidden');
            this.updateCartCount(0);
        }

        updateCartCount(count) {
            const countElement = document.getElementById('cart-items-count');
            if (countElement) {
                countElement.textContent = `${count} ${count === 1 ? 'item' : 'items'}`;
            }
        }

        updateHeaderCartCount(count) {
            const headerCount = document.getElementById('cart-count');
            if (headerCount) {
                headerCount.textContent = count;
            }
            localStorage.setItem('cartCount', count);
        }

        // Utility functions
        formatPrice(price) {
            return `GHS ${parseFloat(price).toFixed(2)}`;
        }

        htmlToElement(html) {
            const template = document.createElement('template');
            template.innerHTML = html.trim();
            return template.content.firstChild;
        }

        showLoading() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        }

        hideLoading() {
            document.getElementById('loading-overlay').classList.add('hidden');
        }

        showConfirmModal(title, message, type = 'warning') {
            return new Promise((resolve) => {
                showConfirmationModal(
                    title,
                    message,
                    () => resolve(true), {
                        type: type,
                        confirmText: type === 'warning' ? 'Remove' : 'Clear',
                        cancelText: 'Cancel'
                    }
                );

                const originalHideModal = window.hideConfirmationModal;
                window.hideConfirmationModal = function() {
                    if (typeof originalHideModal === 'function') {
                        originalHideModal();
                    }
                    resolve(false);
                    window.hideConfirmationModal = originalHideModal;
                };
            });
        }

        showSuccess(message) {
            if (typeof showNotification === 'function') {
                showNotification(message, 'success');
                return;
            }
            alert(message);
        }

        showError(message) {
            if (typeof showNotification === 'function') {
                showNotification(message, 'error');
                return;
            }
            alert(message);
        }
    }

    // Initialize cart manager when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        const cartManager = new CartManager();

        // Event delegation for dynamic elements
        document.addEventListener('click', function(e) {
            // Remove item
            if (e.target.closest('.remove-item-btn')) {
                const cartKey = e.target.closest('.remove-item-btn').dataset.key;
                cartManager.removeItem(cartKey);
            }

            // Decrease quantity
            if (e.target.closest('.decrease-qty')) {
                const cartKey = e.target.closest('.decrease-qty').dataset.key;
                const input = document.querySelector(`.quantity-input[data-key="${cartKey}"]`);
                const newQuantity = parseInt(input.value) - 1;
                cartManager.updateQuantity(cartKey, newQuantity);
            }

            // Increase quantity
            if (e.target.closest('.increase-qty')) {
                const cartKey = e.target.closest('.increase-qty').dataset.key;
                const input = document.querySelector(`.quantity-input[data-key="${cartKey}"]`);
                const newQuantity = parseInt(input.value) + 1;
                cartManager.updateQuantity(cartKey, newQuantity);
            }
        });

        // Input change for quantity
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('quantity-input')) {
                const cartKey = e.target.dataset.key;
                const newQuantity = parseInt(e.target.value);
                cartManager.updateQuantity(cartKey, newQuantity);
            }

            // Individual checkbox change
            if (e.target.classList.contains('item-checkbox')) {
                cartManager.updateRemoveSelectedButton();
            }
        });
    });
</script>

<?php
require_once 'includes/footer.php';
?>

<script>
    (function(){
        function applyMaintenanceToCart() {
            try {
                var btn = document.getElementById('checkout-btn');
                if (!btn) return;

                if (window.APP_MAINTENANCE) {
                    btn.disabled = true;
                    btn.title = 'Checkout disabled due to maintenance.' + (window.MAINTENANCE_END ? (' Scheduled to end: ' + window.MAINTENANCE_END) : '');
                    btn.addEventListener('click', maintenancePrevent, { once: false });
                    if (!document.getElementById('maintenance-cart-note')) {
                        var note = document.createElement('div');
                        note.id = 'maintenance-cart-note';
                        note.style.marginTop = '8px';
                        note.style.color = '#a00';
                        note.textContent = 'Checkout is temporarily disabled due to maintenance.' + (window.MAINTENANCE_END ? (' Scheduled to end: ' + window.MAINTENANCE_END) : '');
                        btn.parentNode.insertBefore(note, btn.nextSibling);
                    }
                } else {
                    btn.disabled = false;
                    btn.title = '';
                    btn.removeEventListener('click', maintenancePrevent);
                    var note = document.getElementById('maintenance-cart-note');
                    if (note) note.parentNode.removeChild(note);
                }
            } catch (e) {}
        }

        function maintenancePrevent(e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof showNotification === 'function') {
                showNotification('Checkout is temporarily disabled due to maintenance.', 'error');
            } else {
                alert('Checkout is temporarily disabled due to maintenance.');
            }
        }

        document.addEventListener('DOMContentLoaded', applyMaintenanceToCart);
        window.addEventListener('load', applyMaintenanceToCart);

        var last = typeof window.APP_MAINTENANCE !== 'undefined' ? window.APP_MAINTENANCE : null;
        setInterval(function(){
            if (typeof window.APP_MAINTENANCE !== 'undefined' && window.APP_MAINTENANCE !== last) {
                last = window.APP_MAINTENANCE;
                applyMaintenanceToCart();
            }
        }, 500);
    })();
</script>