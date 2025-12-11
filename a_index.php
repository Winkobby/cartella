<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    header('Location: signin.php');
    exit;
}

$page_title = 'Admin Dashboard';
$meta_description = 'E-commerce administration dashboard';
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-4 lg:py-8">
    <div class="container mx-auto px-3 lg:px-4 max-w-7xl">
        <!-- Header Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 mb-6 lg:mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Dashboard Overview</h1>
                    <p class="text-gray-600 mt-2 text-sm lg:text-base">Welcome back! Here's what's happening with your store today.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500"><?php echo date('l, F j, Y'); ?></span>
                </div>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6 lg:mb-8" id="stats-container">
            <!-- Loading skeleton -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6">
                <div class="animate-pulse">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gray-200 rounded-lg p-3 w-12 h-12"></div>
                        <div class="ml-4 flex-1">
                            <div class="h-4 bg-gray-200 rounded w-1/3 mb-2"></div>
                            <div class="h-6 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Repeat for 4 cards -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6">
                <div class="animate-pulse">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gray-200 rounded-lg p-3 w-12 h-12"></div>
                        <div class="ml-4 flex-1">
                            <div class="h-4 bg-gray-200 rounded w-1/3 mb-2"></div>
                            <div class="h-6 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6">
                <div class="animate-pulse">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gray-200 rounded-lg p-3 w-12 h-12"></div>
                        <div class="ml-4 flex-1">
                            <div class="h-4 bg-gray-200 rounded w-1/3 mb-2"></div>
                            <div class="h-6 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6">
                <div class="animate-pulse">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gray-200 rounded-lg p-3 w-12 h-12"></div>
                        <div class="ml-4 flex-1">
                            <div class="h-4 bg-gray-200 rounded w-1/3 mb-2"></div>
                            <div class="h-6 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 mb-6 lg:mb-8">
            <!-- Recent Orders -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 px-4 lg:px-6 py-4 bg-gray-50 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
                        <a href="a_orders.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All</a>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div id="recent-orders-container">
                            <!-- Loading skeleton for table -->
                            <div class="animate-pulse">
                                <div class="space-y-4">
                                    <div class="h-8 bg-gray-200 rounded"></div>
                                    <div class="space-y-3">
                                        <?php for($i=0; $i<5; $i++): ?>
                                        <div class="h-12 bg-gray-200 rounded"></div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 px-4 lg:px-6 py-4 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Top Products</h2>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div id="top-products-container">
                            <!-- Loading skeleton -->
                            <div class="animate-pulse">
                                <div class="space-y-4">
                                    <?php for($i=0; $i<5; $i++): ?>
                                    <div class="flex items-center gap-3 p-3">
                                        <div class="w-10 h-10 bg-gray-200 rounded-lg"></div>
                                        <div class="flex-1">
                                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats & Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6" id="quick-stats-container">
            <!-- Loading skeletons for quick stats -->
            <?php for($i=0; $i<4; $i++): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6">
                <div class="animate-pulse">
                    <div class="h-6 bg-gray-200 rounded w-1/2 mb-4"></div>
                    <div class="space-y-3">
                        <?php for($j=0; $j<4; $j++): ?>
                        <div class="flex justify-between">
                            <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
// Dashboard AJAX Manager
class DashboardManager {
    constructor() {
        this.baseUrl = 'ajax/ajax_dashboard.php';
        this.isLoading = false;
        this.refreshInterval = 30000; // 30 seconds
        this.init();
    }

    init() {
        this.loadAllData();
        this.setupAutoRefresh();
        this.setupInteractivity();
    }

    async loadAllData() {
        try {
            this.showLoading(true);
            const response = await fetch(`${this.baseUrl}?type=all`);
            const result = await response.json();
            
            if (result.success) {
                this.updateDashboard(result.data);
            } else {
                this.showError('Failed to load dashboard data');
            }
        } catch (error) {
            console.error('Error loading dashboard:', error);
            this.showError('Connection error. Please try again.');
        } finally {
            this.showLoading(false);
        }
    }

    async loadStats() {
        try {
            const response = await fetch(`${this.baseUrl}?type=stats`);
            const result = await response.json();
            
            if (result.success) {
                this.updateStats(result.data);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    async loadRecentOrders() {
        try {
            const response = await fetch(`${this.baseUrl}?type=recent_orders`);
            const result = await response.json();
            
            if (result.success) {
                this.updateRecentOrders(result.data.recent_orders);
            }
        } catch (error) {
            console.error('Error loading recent orders:', error);
        }
    }

    updateDashboard(data) {
        // Update statistics cards
        this.updateStats({
            order_stats: data.order_stats,
            product_stats: data.product_stats,
            user_stats: data.user_stats
        });

        // Update recent orders
        this.updateRecentOrders(data.recent_orders);

        // Update top products
        this.updateTopProducts(data.top_products);

        // Update quick stats
        this.updateQuickStats({
            order_stats: data.order_stats,
            revenue_data: data.revenue_data
        });
    }

    updateStats(data) {
        const { order_stats = {}, product_stats = {}, user_stats = {} } = data;
        
        // Total Revenue Card
        const revenueCard = document.querySelector('#stats-container > div:nth-child(1)');
        if (revenueCard) {
            revenueCard.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                        <p class="text-2xl font-semibold text-gray-900">₵${this.formatNumber(order_stats.total_revenue || 0)}</p>
                        <p class="text-xs text-gray-500 mt-1">${order_stats.completed_payments || 0} completed payments</p>
                    </div>
                </div>
            `;
        }

        // Total Orders Card
        const ordersCard = document.querySelector('#stats-container > div:nth-child(2)');
        if (ordersCard) {
            ordersCard.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">Total Orders</p>
                        <p class="text-2xl font-semibold text-gray-900">${order_stats.total_orders || 0}</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">${order_stats.pending_orders || 0} pending</span>
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full">${order_stats.processing_orders || 0} processing</span>
                        </div>
                    </div>
                </div>
            `;
        }

        // Products Card
        const productsCard = document.querySelector('#stats-container > div:nth-child(3)');
        if (productsCard) {
            productsCard.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">Products</p>
                        <p class="text-2xl font-semibold text-gray-900">${product_stats.total_products || 0}</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                            <span>${product_stats.total_stock || 0} in stock</span>
                            ${(product_stats.out_of_stock || 0) > 0 ? 
                                `<span class="bg-red-100 text-red-800 px-2 py-1 rounded-full">${product_stats.out_of_stock} out of stock</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        // Customers Card
        const customersCard = document.querySelector('#stats-container > div:nth-child(4)');
        if (customersCard) {
            customersCard.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">Customers</p>
                        <p class="text-2xl font-semibold text-gray-900">${user_stats.total_users || 0}</p>
                        <div class="text-xs text-gray-500 mt-1">
                            ${user_stats.new_today || 0} new today
                        </div>
                    </div>
                </div>
            `;
        }
    }

    updateRecentOrders(orders) {
        const container = document.getElementById('recent-orders-container');
        if (!container) return;

        if (!orders || orders.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="text-gray-500">No recent orders found</p>
                </div>
            `;
            return;
        }

        let html = `
            <div class="overflow-x-auto">
                <table class="w-full min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
        `;

        orders.forEach(order => {
            const statusClass = this.getStatusClass(order.status);
            const date = new Date(order.order_date);
            const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            
            html += `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            ${this.escapeHtml(order.order_number)}
                        </div>
                        <div class="text-xs text-gray-500">
                            ${formattedDate}
                        </div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 hidden sm:table-cell">
                        ${this.escapeHtml(order.customer_name)}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div class="font-medium">₵${this.formatNumber(order.total_amount)}</div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                            ${this.capitalizeFirst(order.status)}
                        </span>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="a_order_details.php?id=${order.id}" class="text-blue-600 hover:text-blue-900 transition-colors">
                            View
                        </a>
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;
    }

    updateTopProducts(products) {
        const container = document.getElementById('top-products-container');
        if (!container) return;

        if (!products || products.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <p class="text-gray-500">No product sales data</p>
                </div>
            `;
            return;
        }

        let html = '<div class="space-y-4">';
        
        products.forEach((product, index) => {
            html += `
                <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-gray-200 rounded-lg overflow-hidden">
                        ${product.main_image ? 
                            `<img class="w-10 h-10 object-cover" src="${this.escapeHtml(product.main_image)}" alt="${this.escapeHtml(product.name)}">` :
                            `<div class="w-10 h-10 flex items-center justify-center bg-gray-100 text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>`
                        }
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            ${this.escapeHtml(product.name)}
                        </p>
                        <p class="text-xs text-gray-500">
                            ${product.total_sold} sold • ₵${this.formatNumber(product.total_revenue)}
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            #${index + 1}
                        </span>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    updateQuickStats(data) {
        const { order_stats = {}, revenue_data = [] } = data;
        const container = document.getElementById('quick-stats-container');
        if (!container) return;

        // Order Status
        container.children[0].innerHTML = `
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Status</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Pending</span>
                    <span class="text-sm font-medium text-gray-900">${order_stats.pending_orders || 0}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Processing</span>
                    <span class="text-sm font-medium text-gray-900">${order_stats.processing_orders || 0}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Shipped</span>
                    <span class="text-sm font-medium text-gray-900">${order_stats.shipped_orders || 0}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Delivered</span>
                    <span class="text-sm font-medium text-gray-900">${order_stats.delivered_orders || 0}</span>
                </div>
            </div>
        `;

        // Payment Status
        container.children[1].innerHTML = `
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Status</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Completed</span>
                    <span class="text-sm font-medium text-green-600">${order_stats.completed_payments || 0}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Pending</span>
                    <span class="text-sm font-medium text-yellow-600">${order_stats.pending_payments || 0}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Cancelled</span>
                    <span class="text-sm font-medium text-red-600">${order_stats.cancelled_orders || 0}</span>
                </div>
            </div>
        `;

        // Quick Actions (static content)
        container.children[2].innerHTML = `
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="a_pro.php" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-colors group">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Add New Product</span>
                </a>
                <a href="a_orders.php?status=pending" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-green-300 hover:bg-green-50 transition-colors group">
                    <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Process Orders</span>
                </a>
                <a href="a_customers.php" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50 transition-colors group">
                    <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Manage Customers</span>
                </a>
            </div>
        `;

        // System Info (static content)
        container.children[3].innerHTML = `
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Info</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Server Time</span>
                    <span class="text-sm font-medium text-gray-900">${new Date().toLocaleTimeString()}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">PHP Version</span>
                    <span class="text-sm font-medium text-gray-900"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Database</span>
                    <span class="text-sm font-medium text-gray-900">MySQL</span>
                </div>
            </div>
        `;
    }

    setupAutoRefresh() {
        // Auto-refresh data every 30 seconds
        setInterval(() => {
            this.loadStats();
            this.loadRecentOrders();
        }, this.refreshInterval);
    }

    setupInteractivity() {
        // Add hover effects to stat cards
        document.addEventListener('DOMContentLoaded', () => {
            const statCards = document.querySelectorAll('.bg-white.rounded-xl');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.transition = 'transform 0.2s ease-in-out';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Add refresh button functionality
            const refreshBtn = document.getElementById('refresh-dashboard');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => {
                    this.loadAllData();
                });
            }
        });
    }

    showLoading(show) {
        this.isLoading = show;
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = show ? 'block' : 'none';
        }
    }

    showError(message) {
        // Create or update error message display
        const errorContainer = document.getElementById('error-container') || this.createErrorContainer();
        errorContainer.innerHTML = `
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>${message}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
    }

    createErrorContainer() {
        const container = document.createElement('div');
        container.id = 'error-container';
        const mainContainer = document.querySelector('.container');
        if (mainContainer) {
            mainContainer.insertBefore(container, mainContainer.firstChild);
        }
        return container;
    }

    // Utility functions
    formatNumber(num) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getStatusClass(status) {
        const classes = {
            'delivered': 'bg-green-100 text-green-800',
            'shipped': 'bg-blue-100 text-blue-800',
            'processing': 'bg-yellow-100 text-yellow-800',
            'cancelled': 'bg-red-100 text-red-800',
            'pending': 'bg-gray-100 text-gray-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }

    capitalizeFirst(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
}

// Initialize dashboard when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new DashboardManager();
    
    // Simple real-time clock update
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        
        const clockElement = document.querySelector('[data-clock]');
        if (clockElement) {
            clockElement.textContent = timeString;
        }
    }

    setInterval(updateClock, 1000);
    updateClock();
});
</script>

<?php require_once 'includes/footer.php'; ?>