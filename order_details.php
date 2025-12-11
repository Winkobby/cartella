<?php
require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['order_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    header('Location: orders.php');
    exit;
}
?>

<div class="py-8 md:py-2">
    <div class="container mx-auto px-2 max-w-6xl">
        <!-- Header -->
        <div class="mb-2">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

                <!-- Left Side: Icon + Title + Breadcrumb -->
                <div class="lg:text-left">

                    <!-- Breadcrumb -->
                    <nav class="flex items-center justify-center lg:justify-start space-x-2 text-xs text-gray-600">
                        <a href="index.php" class="hover:text-purple-600 transition-colors">Home</a>
                        <span class="text-gray-400">›</span>
                        <a href="orders.php" class="hover:text-purple-600 transition-colors">My Orders</a>
                        <span class="text-gray-400">›</span>
                        <span class="text-gray-600">Order Details</span>
                    </nav>
                </div>

                <!-- Right Side: Print Button -->
                <div class="flex items-center gap-2">
                    <a href="orders.php" class="flex gap-2 bg-white border border-gray-200 px-3 py-1.5 text-sm rounded-2xl shadow-sm text-purple-600 hover:text-purple-700"> <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>

                    <button onclick="window.print()"
                        class="flex gap-2 bg-white border border-gray-200 px-3 py-1.5 text-sm rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 text-gray-700 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span class="font-medium">Print</span>
                    </button>
                </div>

            </div>
        </div>


        <!-- Loading State -->
        <div id="loading-order" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Loading order details...</p>
        </div>

        <!-- Order Details Content -->
        <div id="order-content" class="hidden">
            <!-- Order Header Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-3">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Order Number</h3>
                        <p id="order-number" class="text-base font-semibold text-gray-900"></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Order Date</h3>
                        <p id="order-date" class="text-base font-semibold text-gray-900"></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Status</h3>
                        <div id="order-status" class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium"></div>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Total Amount</h3>
                        <p id="order-total" class="text-base font-semibold text-gray-900"></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                <!-- Left Column - Order Items & Summary -->
                <div class="lg:col-span-2 space-y-3 mb-4">
                    <!-- Order Items -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Order Items</h2>
                        </div>
                        <div id="order-items" class="divide-y divide-gray-200">
                            <!-- Items will be loaded here -->
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Order Summary</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span id="subtotal" class="font-medium text-gray-700"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shipping</span>
                                    <span id="shipping" class="font-medium text-gray-700"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tax</span>
                                    <span id="tax" class="font-medium text-gray-700"></span>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 pt-3">
                                    <span class="text-lg font-semibold text-gray-900">Total</span>
                                    <span id="total" class="text-lg font-semibold text-purple-900"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Customer & Shipping Info -->
                <div class="space-y-3 mb-4">
                    <!-- Customer Information -->
                    <div class="bg-white rounded-lg shadow-sm border border-purple-200">
                        <div class="p-6 border-b border-purple-600">
                            <h2 class="text-xl font-semibold text-gray-900">Customer Information</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Name</h3>
                                    <p id="customer-name" class="text-gray-900"></p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Email</h3>
                                    <p id="customer-email" class="text-gray-900"></p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Phone</h3>
                                    <p id="customer-phone" class="text-gray-900"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Shipping Address</h2>
                        </div>
                        <div class="p-6">
                            <div id="shipping-address" class="space-y-2 text-gray-900">
                                <!-- Address will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Payment Information</h2>
                        </div>
                        <div class="p-6">
                            <div id="payment-info" class="space-y-3">
                                <!-- Payment info will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Order Actions -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Order Actions</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <button id="cancel-order-btn" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 transition-colors hidden">
                                    Cancel Order
                                </button>
                                <button id="track-order-btn" class="w-full border border-purple-600 text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-purple-50 transition-colors hidden">
                                    Track Package
                                </button>
                                <button id="return-order-btn" class="w-full border border-gray-300 text-gray-700 px-4 py-2 rounded-lg font-semibold hover:bg-gray-50 transition-colors hidden">
                                    Return Items
                                </button>
                                <button id="reorder-btn" class="w-full border border-green-600 text-green-600 px-4 py-2 rounded-lg font-semibold hover:bg-green-50 transition-colors hidden">
                                    Reorder Items
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <div id="error-state" class="hidden text-center py-12">
            <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2" id="error-title">Order Not Found</h3>
            <p class="text-gray-600 mb-6" id="error-message">The order you're looking for doesn't exist or you don't have permission to view it.</p>
            <a href="orders.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                Back to Orders
            </a>
        </div>
    </div>
</div>

<script>
    class OrderDetailsManager {
        constructor() {
            this.orderId = <?php echo json_encode($order_id); ?>;
            this.currentOrder = null;

            this.init();
        }

        init() {
            this.bindEvents();
            this.loadOrderDetails();
        }

        bindEvents() {
            // Order action buttons
            document.getElementById('cancel-order-btn').addEventListener('click', () => {
                this.showCancelModal();
            });

            document.getElementById('track-order-btn').addEventListener('click', () => {
                this.trackOrder();
            });

            document.getElementById('return-order-btn').addEventListener('click', () => {
                this.returnOrder();
            });

            document.getElementById('reorder-btn').addEventListener('click', () => {
                this.reorderItems();
            });
        }

        async loadOrderDetails() {
            this.showLoading(true);

            try {
                const response = await fetch(`ajax/orders.php?action=get_order_details&order_id=${this.orderId}`);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.currentOrder = data.order;
                    this.renderOrderDetails(data.order, data.items);
                    this.showOrderContent();
                } else {
                    this.showError('Order Not Found', data.message || 'Failed to load order details');
                }
            } catch (error) {
                console.error('Error loading order details:', error);
                this.showError('Network Error', 'Failed to load order details. Please try again.');
            } finally {
                this.showLoading(false);
            }
        }

        renderOrderDetails(order, items) {
            // Update header information
            document.getElementById('order-number').textContent = order.order_number;
            document.getElementById('order-date').textContent = order.formatted_date;
            document.getElementById('order-total').textContent = order.formatted_totals.total_amount;

            // Update status
            const statusElement = document.getElementById('order-status');
            const statusColor = this.getStatusColor(order.status);
            statusElement.className = `inline-flex items-center px-3 py-1 rounded-md text-sm font-medium ${statusColor}`;
            statusElement.textContent = this.formatStatus(order.status);

            // Render order items
            this.renderOrderItems(items);

            // Render order summary
            document.getElementById('subtotal').textContent = order.formatted_totals.subtotal;
            document.getElementById('shipping').textContent = order.formatted_totals.shipping_cost;
            document.getElementById('tax').textContent = order.formatted_totals.tax_amount;
            document.getElementById('total').textContent = order.formatted_totals.total_amount;

            // Render customer information
            document.getElementById('customer-name').textContent = order.customer_name;
            document.getElementById('customer-email').textContent = order.customer_email;
            document.getElementById('customer-phone').textContent = order.customer_phone;

            // Render shipping address
            const shippingAddress = document.getElementById('shipping-address');
            shippingAddress.innerHTML = `
            <p class="font-medium">${this.escapeHtml(order.customer_name)}</p>
            <p>${this.escapeHtml(order.shipping_address.address)}</p>
            <p>${this.escapeHtml(order.shipping_address.city)}, ${this.escapeHtml(order.shipping_address.region)}</p>
            ${order.shipping_address.postal_code ? `<p>${this.escapeHtml(order.shipping_address.postal_code)}</p>` : ''}
        `;

            // Render payment information
            this.renderPaymentInfo(order.payment_info);

            // Setup action buttons based on order status
            this.setupActionButtons(order.status);
        }

        renderOrderItems(items) {
            const container = document.getElementById('order-items');
            let html = '';

            items.forEach(item => {
                html += `
                <div class="p-4">
                    <div class="flex items-start space-x-4">
                        <img src="${item.image || 'assets/images/placeholder-product.jpg'}" 
                             alt="${this.escapeHtml(item.product_name)}" 
                             class="w-16 h-16 object-cover rounded-lg flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-base text-gray-700 truncate">${this.escapeHtml(item.product_name)}</h3>
                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-600">
                                ${item.color ? `<div><span class="font-medium">Color:</span> ${this.escapeHtml(item.color)}</div>` : ''}
                                ${item.size ? `<div><span class="font-medium">Size:</span> ${this.escapeHtml(item.size)}</div>` : ''}
                                <div><span class="font-medium">Qty:</span> ${item.quantity}</div>
                                <div><span class="font-medium">Price:</span> ${this.escapeHtml(item.formatted_price)}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-md font-semibold text-gray-600">${this.escapeHtml(item.formatted_total)}</p>
                        </div>
                    </div>
                </div>
            `;
            });

            container.innerHTML = html;
        }

        renderPaymentInfo(paymentInfo) {
            const container = document.getElementById('payment-info');
            let html = '';

            html += `
            <div>
                <h3 class="text-sm font-medium text-gray-500">Payment Method</h3>
                <p class="text-gray-900">${this.formatPaymentMethod(paymentInfo.method)}</p>
            </div>
        `;

            if (paymentInfo.momo_number) {
                html += `
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Mobile Money</h3>
                    <p class="text-gray-900">${this.escapeHtml(paymentInfo.momo_number)} (${this.escapeHtml(paymentInfo.momo_network)})</p>
                </div>
            `;
            }

            if (paymentInfo.transaction_id) {
                html += `
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Transaction ID</h3>
                    <p class="text-gray-900 font-mono text-sm">${this.escapeHtml(paymentInfo.transaction_id)}</p>
                </div>
            `;
            }

            container.innerHTML = html;
        }

        setupActionButtons(status) {
            const cancelBtn = document.getElementById('cancel-order-btn');
            const trackBtn = document.getElementById('track-order-btn');
            const returnBtn = document.getElementById('return-order-btn');
            const reorderBtn = document.getElementById('reorder-btn');

            // Reset all buttons
            cancelBtn.classList.add('hidden');
            trackBtn.classList.add('hidden');
            returnBtn.classList.add('hidden');
            reorderBtn.classList.add('hidden');

            // Show buttons based on status
            switch (status) {
                case 'pending':
                case 'processing':
                    cancelBtn.classList.remove('hidden');
                    break;
                case 'shipped':
                    trackBtn.classList.remove('hidden');
                    break;
                case 'delivered':
                    returnBtn.classList.remove('hidden');
                    reorderBtn.classList.remove('hidden');
                    break;
                case 'cancelled':
                    reorderBtn.classList.remove('hidden');
                    break;
            }
        }

        showCancelModal() {
            // Use the global confirmation modal from header.php
            showConfirmationModal(
                'Cancel Order',
                'Are you sure you want to cancel this order? This action cannot be undone.',
                () => this.cancelOrder(), {
                    type: 'error',
                    confirmText: 'Yes, Cancel Order',
                    cancelText: 'Keep Order'
                }
            );
        }

        async cancelOrder() {
            if (!this.currentOrder) return;

            try {
                const formData = new FormData();
                formData.append('order_id', this.currentOrder.id);

                const response = await fetch('ajax/orders.php?action=cancel_order', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess('Order cancelled successfully');
                    // Reload page to reflect changes
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    this.showError(data.message || 'Failed to cancel order');
                }
            } catch (error) {
                console.error('Error cancelling order:', error);
                this.showError('Failed to cancel order');
            }
        }

        trackOrder() {
            // Use the global confirmation modal from header.php
            showConfirmationModal(
                'Track Order',
                'Would you like to track the delivery status of this order?',
                () => this.confirmTrackOrder(), {
                    type: 'info',
                    confirmText: 'Track Order',
                    cancelText: 'Cancel'
                }
            );
        }

        async confirmTrackOrder() {
            if (!this.currentOrder) return;

            try {
                const response = await fetch(`ajax/orders.php?action=track_order&order_id=${this.currentOrder.id}`);
                const data = await response.json();

                if (data.success) {
                    // Redirect to tracking page or show tracking information
                    window.location.href = `track_order.php?order_id=${this.currentOrder.order_number}`;
                } else {
                    this.showError(data.message || 'Failed to track order');
                }
            } catch (error) {
                console.error('Error tracking order:', error);
                this.showError('Failed to track order');
            }
        }

        returnOrder() {
            // Use the global confirmation modal from header.php
            showConfirmationModal(
                'Return Items',
                'Would you like to initiate a return for items in this order? You will be redirected to the return page.',
                () => this.confirmReturnOrder(), {
                    type: 'warning',
                    confirmText: 'Start Return',
                    cancelText: 'Keep Order'
                }
            );
        }

        async confirmReturnOrder() {
            if (!this.currentOrder) return;

            // Redirect to return page with order ID
            window.location.href = `return_request.php?order_id=${this.currentOrder.id}`;
        }

        reorderItems() {
            // Use the global confirmation modal from header.php
            showConfirmationModal(
                'Reorder Items',
                'Would you like to add all items from this order to your cart?',
                () => this.confirmReorderItems(), {
                    type: 'info',
                    confirmText: 'Add to Cart',
                    cancelText: 'Cancel'
                }
            );
        }

        async confirmReorderItems() {
            if (!this.currentOrder) return;

            try {
                const response = await fetch('ajax/orders.php?action=reorder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: this.currentOrder.id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    let message = data.message || 'Items added to cart successfully!';

                    // Handle unavailable items if present
                    if (data.unavailable_items && data.unavailable_items.length > 0) {
                        const unavailableNames = data.unavailable_items.map(item => item.product_name).join(', ');
                        message = `Some items were added to cart. Unavailable items: ${unavailableNames}`;
                    }

                    this.showSuccess(message);

                    // Update cart count
                    this.updateCartCount(data.cart_count);

                } else {
                    this.showError(data.message || 'Failed to add items to cart');
                }
            } catch (error) {
                console.error('Error reordering items:', error);
                this.showError('Failed to add items to cart. Please try again.');
            }
        }

        // UI Helper Methods
        showLoading(show) {
            document.getElementById('loading-order').classList.toggle('hidden', !show);
        }

        showOrderContent() {
            document.getElementById('order-content').classList.remove('hidden');
        }

        showError(title, message) {
            document.getElementById('error-title').textContent = title;
            document.getElementById('error-message').textContent = message;
            document.getElementById('error-state').classList.remove('hidden');
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

        formatPaymentMethod(method) {
            const methodMap = {
                'mtn_momo': 'MTN Mobile Money',
                'vodafone_cash': 'Vodafone Cash',
                'airteltigo_money': 'AirtelTigo Money',
                'card': 'Credit/Debit Card'
            };
            return methodMap[method] || method;
        }

        showSuccess(message) {
            if (typeof showNotification === 'function') {
                showNotification(message, 'success');
            } else {
                alert('Success: ' + message);
            }
        }

        showError(message) {
            if (typeof showNotification === 'function') {
                showNotification(message, 'error');
            } else {
                alert('Error: ' + message);
            }
        }

        showInfo(message) {
            if (typeof showNotification === 'function') {
                showNotification(message, 'info');
            } else {
                alert('Info: ' + message);
            }
        }

        updateCartCount(cartCount) {
            // Update header cart count
            const headerCount = document.getElementById('cart-count');
            if (headerCount) {
                headerCount.textContent = cartCount;
            }

            // Update localStorage for persistence
            localStorage.setItem('cartCount', cartCount);

            // If there's a global cart count update function, call it
            if (typeof updateCartCount === 'function') {
                updateCartCount(cartCount);
            }
        }
    }

    // Initialize order details manager
    const orderDetailsManager = new OrderDetailsManager();
</script>

<style>
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white !important;
        }

        .bg-gray-50 {
            background: white !important;
        }

        .shadow-sm,
        .hover\:shadow-md {
            box-shadow: none !important;
        }

        .border-gray-200 {
            border-color: #000 !important;
        }
    }
</style>

<?php
require_once 'includes/footer.php';
?>