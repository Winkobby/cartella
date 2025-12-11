<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_once 'includes/header.php';
?>

<div class="min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Track Your Order</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Enter your order number and email to track the status of your order
            </p>
        </div>

        <!-- Track Order Form -->
        <div class="bg-white rounded-2xl shadow-sm p-8 mb-8">
            <form id="track-order-form" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="order-number" class="block text-sm font-medium text-gray-700 mb-2">
                            Order Number
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   id="order-number" 
                                   name="order_number" 
                                   placeholder="e.g., ORD-2024-001234"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-lg"
                                   required>
                            <svg class="absolute right-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   placeholder="your@email.com"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-lg"
                                   required>
                            <svg class="absolute right-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full bg-purple-600 text-white py-4 px-6 rounded-lg font-semibold text-lg hover:bg-purple-700 transition-colors flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <span>Track Order</span>
                </button>
            </form>
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="hidden text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Tracking your order...</p>
        </div>

        <!-- Error State -->
        <div id="error-state" class="hidden bg-red-50 border border-red-200 rounded-lg p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-red-800 mb-2">Order Not Found</h3>
            <p class="text-red-600" id="error-message">We couldn't find an order with that information. Please check your order number and email.</p>
        </div>

        <!-- Order Tracking Results -->
        <div id="tracking-results" class="hidden space-y-6">
            <!-- Order Header -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6 text-white">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-lg lg:text-2xl font-bold mb-1">Order #<span id="result-order-number"></span></h2>
                            <p class="text-purple-100">Placed on <span id="result-order-date"></span></p>
                        </div>
                        <div class="text-right">
                            <div id="result-order-status" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-white bg-opacity-20">
                                <!-- Status will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-1">Delivery Address</h3>
                            <p class="text-sm text-gray-600" id="delivery-address">Loading...</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-1">Total Amount</h3>
                            <p class="text-sm text-gray-600" id="total-amount">Loading...</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-1">Payment Method</h3>
                            <p class="text-sm text-gray-600" id="payment-method">Loading...</p>
                        </div>
                    </div>

                    <!-- Order Items Preview -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h3>
                        <div id="order-items-preview" class="space-y-3">
                            <!-- Items will be loaded here -->
                        </div>
                        <div class="mt-4 text-center">
                            <button onclick="window.location.href='order_details.php?order_id='+window.currentOrderId" 
                                    class="text-purple-600 hover:text-purple-700 font-medium">
                                View Full Order Details →
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tracking Timeline -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Order Timeline</h3>
                <div id="tracking-timeline" class="space-y-4">
                    <!-- Timeline will be loaded here -->
                </div>
            </div>

            <!-- Help Section -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-2xl p-6 text-center">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Need Help?</h3>
                <p class="text-gray-600 mb-4">If you have any questions about your order, our customer service team is here to help.</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="contact.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                        Contact Support
                    </a>
                    <a href="tel:+233241234567" class="bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-purple-50 transition-colors">
                        Call Us
                    </a>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
class TrackOrderManager {
    constructor() {
        this.currentOrderId = null;
        this.init();
    }

    init() {
        this.bindEvents();
        
        // Check if there's an order ID in the URL
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');
        if (orderId) {
            document.getElementById('order-number').value = orderId;
        }
    }

    bindEvents() {
        document.getElementById('track-order-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.trackOrder();
        });
    }

    async trackOrder() {
        const orderNumber = document.getElementById('order-number').value.trim();
        const email = document.getElementById('email').value.trim();

        if (!orderNumber || !email) {
            this.showError('Please enter both order number and email address.');
            return;
        }

        this.showLoading(true);
        this.hideError();
        this.hideResults();

        try {
            const response = await fetch('ajax/orders.php?action=track_order_public', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_number: orderNumber,
                    email: email
                })
            });

            // Handle non-OK responses
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
            }

            const text = await response.text();
            let data;
            
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError, 'Response text:', text);
                throw new Error('Invalid response from server');
            }

            if (data.success) {
                this.currentOrderId = data.order.id;
                // Set it globally for the button onclick
                window.currentOrderId = data.order.id;
                this.showTrackingResults(data.order, data.items, data.timeline);
            } else {
                this.showError(data.message || 'Order not found. Please check your order number and email.');
            }
        } catch (error) {
            console.error('Error tracking order:', error);
            this.showError('Failed to track order. Please try again later.');
        } finally {
            this.showLoading(false);
        }
    }

    showTrackingResults(order, items, timeline) {
        // Update order header
        document.getElementById('result-order-number').textContent = order.order_number;
        document.getElementById('result-order-date').textContent = order.formatted_date;
        
        // Update status
        const statusElement = document.getElementById('result-order-status');
        const statusColor = this.getStatusColor(order.status);
        statusElement.className = `inline-flex items-center px-4 py-1 rounded-full text-sm font-medium ${statusColor}`;
        statusElement.innerHTML = `
            ${this.getStatusIcon(order.status)}
            ${this.formatStatus(order.status)}
        `;

        // Update order info - fix potential undefined values
        const city = order.shipping_address?.city || '';
        const region = order.shipping_address?.region || '';
        document.getElementById('delivery-address').textContent = 
            `${city}${city && region ? ', ' : ''}${region}`;
        document.getElementById('total-amount').textContent = order.formatted_totals?.total_amount || 'GHS 0.00';
        document.getElementById('payment-method').textContent = this.formatPaymentMethod(order.payment_info?.method);

        // Render order items preview
        this.renderOrderItemsPreview(items || []);

        // Render timeline
        this.renderTimeline(timeline || []);

        // Update the "View Full Order Details" button with proper event listener
        this.updateOrderDetailsButton();

        // Show results
        this.showResults();
    }

    updateOrderDetailsButton() {
        const orderDetailsButton = document.querySelector('#order-items-preview + .mt-4 button');
        if (orderDetailsButton && this.currentOrderId) {
            // Remove the old onclick and add a proper event listener
            orderDetailsButton.onclick = null;
            orderDetailsButton.addEventListener('click', () => {
                this.viewOrderDetails();
            });
        }
    }

    viewOrderDetails() {
        if (this.currentOrderId) {
            window.location.href = `order_details.php?order_id=${this.currentOrderId}`;
        } else {
            alert('Unable to view order details. Please try tracking your order again.');
        }
    }

    renderOrderItemsPreview(items) {
        const container = document.getElementById('order-items-preview');
        
        let html = '';
        items.slice(0, 3).forEach(item => {
            html += `
                <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                    <img src="${item.image || 'assets/images/placeholder-product.jpg'}" 
                         alt="${this.escapeHtml(item.product_name)}" 
                         class="w-12 h-12 object-cover rounded">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">${this.escapeHtml(item.product_name)}</h4>
                        <p class="text-sm text-gray-600">Qty: ${item.quantity} • ${this.escapeHtml(item.formatted_price)}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-medium text-gray-900">${this.escapeHtml(item.formatted_total)}</p>
                    </div>
                </div>
            `;
        });

        if (items.length > 3) {
            html += `
                <div class="text-center text-sm text-gray-600 pt-2">
                    and ${items.length - 3} more item${items.length - 3 !== 1 ? 's' : ''}
                </div>
            `;
        }

        container.innerHTML = html;
    }

    renderTimeline(timeline) {
        const container = document.getElementById('tracking-timeline');
        
        if (!timeline || timeline.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-600">Tracking information will be available once your order is processed.</p>
                </div>
            `;
            return;
        }

        let html = '';
        timeline.forEach((entry, index) => {
            const isLast = index === timeline.length - 1;
            
            html += `
                <div class="flex items-start space-x-4 ${isLast ? '' : 'pb-4 border-l-2 border-gray-200'}">
                    <div class="flex-shrink-0 w-8 h-8 ${entry.completed ? 'bg-purple-600' : 'bg-gray-300'} rounded-full flex items-center justify-center relative z-10">
                        ${entry.completed ? 
                            '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                            '<div class="w-2 h-2 bg-white rounded-full"></div>'
                        }
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium ${entry.completed ? 'text-gray-900' : 'text-gray-500'}">${this.escapeHtml(entry.title)}</h4>
                        <p class="text-sm ${entry.completed ? 'text-gray-600' : 'text-gray-400'}">${this.escapeHtml(entry.description)}</p>
                        <p class="text-xs text-gray-500 mt-1">${this.escapeHtml(entry.formatted_date)}</p>
                        ${entry.notes ? `<p class="text-sm text-gray-500 mt-2">${this.escapeHtml(entry.notes)}</p>` : ''}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // UI Helper Methods
    showLoading(show) {
        document.getElementById('loading-state').classList.toggle('hidden', !show);
    }

    showError(message) {
        document.getElementById('error-message').textContent = message;
        document.getElementById('error-state').classList.remove('hidden');
    }

    hideError() {
        document.getElementById('error-state').classList.add('hidden');
    }

    showResults() {
        document.getElementById('tracking-results').classList.remove('hidden');
    }

    hideResults() {
        document.getElementById('tracking-results').classList.add('hidden');
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

    getStatusIcon(status) {
        const icons = {
            'pending': '⏳',
            'processing': '🔄',
            'shipped': '🚚',
            'delivered': '✅',
            'cancelled': '❌'
        };
        return icons[status] || '📦';
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
            'paystack_inline': 'Paystack',
            'mtn_momo': 'MTN Mobile Money',
            'vodafone_cash': 'Vodafone Cash',
            'airteltigo_money': 'AirtelTigo Money',
            'card': 'Credit/Debit Card'
        };
        return methodMap[method] || method;
    }
}

// Initialize track order manager
const trackOrderManager = new TrackOrderManager();
</script>

<?php
require_once 'includes/footer.php';
?>