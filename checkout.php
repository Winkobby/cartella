<?php
// Include bootstrap/config first WITHOUT header.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Initialize database and functions
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if cart is empty - DO THIS BEFORE ANY OUTPUT
if ($functions->getCartItemCount() === 0) {
    header('Location: cart.php');
    exit;
}

// Check if user is logged in - DO THIS BEFORE ANY OUTPUT
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Fetch user information if logged in
$user_data = [];
if (isset($_SESSION['user_id'])) {
    $user_data = $functions->getUserData($_SESSION['user_id']);
    if (!is_array($user_data)) {
        $user_data = [];
    }
}

// NOW include header.php after all redirects are handled
require_once 'includes/header.php';
?>

<div class="min-h-screen py-8 md:py-4 lg:py-2">
    <div class="container mx-auto px-2 max-w-6xl">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">
                <!-- Left Side: Icon + Title + Breadcrumb -->
                <div class=" lg:text-left">             
                    <!-- Breadcrumb -->
                    <nav class="flex items-center justify-center lg:justify-start space-x-2 text-xs text-gray-600">
                        <a href="index.php" class="hover:text-purple-600 transition-colors">Home</a>
                        <span class="text-gray-400">›</span>
                        <a href="cart.php" class="hover:text-purple-600 transition-colors">Cart</a>
                        <span class="text-gray-400">›</span>
                        <span class="text-purple-600">Checkout</span>
                    </nav>
                </div>
                <!-- Right Side: Optional Extra Info -->
                <div class="flex flex-col sm:flex-row items-center gap-4">
                    <button onclick="window.location.href='cart.php'"
                        class="bg-purple-600 text-white px-3 py-1.5 rounded-md font-semibold hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-2 group">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h18M3 7h18M5 11h14l-1 9H6l-1-9z" />
                        </svg>
                        Review Cart
                    </button>
                </div>

            </div>
        </div>


        <!-- Checkout Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center pl-3 pr-3">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-purple-600 text-white rounded-full">
                        <span>1</span>
                    </div>
                    <div class="ml-2 text-sm font-medium text-purple-600">Cart</div>
                </div>
                <div class="w-16 h-1 bg-purple-600 mx-2"></div>
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-purple-600 text-white rounded-full">
                        <span>2</span>
                    </div>
                    <div class="ml-2 text-sm font-medium text-purple-600">Information</div>
                </div>
                <div class="w-16 h-1 bg-purple-600 mx-2"></div>
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-gray-300 text-gray-600 rounded-full">
                        <span>3</span>
                    </div>
                    <div class="ml-2 text-sm font-medium text-gray-500">Payment</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 ">
            <!-- Checkout Form -->
            <div class="space-y-3 mb-12">
                <!-- Customer Information -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Contact Information</h2>
                    </div>
                    <div class="p-6">
                        <form id="checkout-form">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                        value="<?php echo !empty($user_data['first_name']) ? htmlspecialchars($user_data['first_name']) : ''; ?>">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                        value="<?php echo !empty($user_data['last_name']) ? htmlspecialchars($user_data['last_name']) : ''; ?>">
                                </div>
                            </div>

                            <div class="mt-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                <input type="email" id="email" name="email" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    value="<?php echo !empty($user_data['email']) ? htmlspecialchars($user_data['email']) : ''; ?>">
                            </div>

                            <div class="mt-4">
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    placeholder="e.g., 0241234567"
                                    value="<?php echo !empty($user_data['phone']) ? htmlspecialchars($user_data['phone']) : ''; ?>">
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-gray-900">Shipping Address</h2>
                            <button type="button" id="add-address-btn"
                                class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                                + Add New Address
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <!-- Address Selection Cards -->
                        <div id="addresses-container" class="space-y-4 mb-6">
                            <div class="text-center py-8">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto mb-4"></div>
                                <p class="text-gray-600">Loading your addresses...</p>
                            </div>
                        </div>

                        <!-- New Address Form (Hidden by Default) -->
                        <div id="new-address-form" class="hidden space-y-4 border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900">Add New Address</h3>

                            <div>
                                <label for="address_type" class="block text-sm font-medium text-gray-700 mb-1">Address Type</label>
                                <select id="address_type" name="address_type"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <option value="home">Home</option>
                                    <option value="work">Work</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label for="new_street_address" class="block text-sm font-medium text-gray-700 mb-1">Street Address *</label>
                                <input type="text" id="new_street_address" name="street_address" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="new_city" class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                    <input type="text" id="new_city" name="city" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="new_region" class="block text-sm font-medium text-gray-700 mb-1">Region *</label>
                                    <select id="new_region" name="region" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <option value="">Select Region</option>
                                        <option value="Ahafo">Ahafo</option>
                                        <option value="Ashanti">Ashanti</option>
                                        <option value="Bono">Bono</option>
                                        <option value="Bono East">Bono East</option>
                                        <option value="Central">Central</option>
                                        <option value="Eastern">Eastern</option>
                                        <option value="Greater Accra">Greater Accra</option>
                                        <option value="North East">North East</option>
                                        <option value="Northern">Northern</option>
                                        <option value="Oti">Oti</option>
                                        <option value="Savannah">Savannah</option>
                                        <option value="Upper East">Upper East</option>
                                        <option value="Upper West">Upper West</option>
                                        <option value="Volta">Volta</option>
                                        <option value="Western">Western</option>
                                        <option value="Western North">Western North</option>
                                    </select>

                                </div>
                                <div>
                                    <label for="new_postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                                    <input type="text" id="new_postal_code" name="postal_code"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                            </div>

                            <div>
                                <label for="new_country" class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                                <input type="text" id="new_country" name="country" required value="Ghana" readonly
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-gray-50">
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" id="new_is_default" name="is_default" value="1"
                                    class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                <label for="new_is_default" class="ml-2 text-sm text-gray-700">
                                    Set as default shipping address
                                </label>
                            </div>

                            <div class="flex space-x-3 pt-4">
                                <button type="button" id="save-address-btn"
                                    class="px-6 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition-colors">
                                    Save Address
                                </button>
                                <button type="button" id="cancel-address-btn"
                                    class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </div>

                        <!-- Hidden form fields for selected address -->
                        <input type="hidden" id="selected_address_id" name="address_id">
                        <input type="hidden" id="selected_street_address" name="address">
                        <input type="hidden" id="selected_city" name="city">
                        <input type="hidden" id="selected_region" name="region">
                        <input type="hidden" id="selected_postal_code" name="postal_code">
                        <input type="hidden" id="selected_country" name="country" value="Ghana">
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Payment Method</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <!-- Paystack Inline Payment -->
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-purple-500 transition-colors">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="payment_method" value="paystack_inline" class="h-4 w-4 text-purple-600 focus:ring-purple-500" checked>
                                    <div class="ml-3 flex items-center">
                                        <img src="assets/images/paystack-logo.png" alt="Paystack" class="h-8 w-8 object-contain">
                                        <span class="ml-2 text-sm font-medium text-gray-900">Paystack (Card & Mobile Money)</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Payment Instructions -->
                            <div id="paystack-instructions" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-blue-800 mb-2">Secure Payment via Paystack</h4>
                                <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                                    <li>Pay with Visa, Mastercard, or Mobile Money</li>
                                    <li>Your payment will be processed securely</li>
                                    <li>You'll be redirected to Paystack's secure payment page</li>
                                    <li>Complete your payment and return to order confirmation</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1 mb-12">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 sticky top-20">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Order Summary</h3>
                    </div>

                    <div class="p-6">
                        <!-- Order Items -->
                        <div id="order-items" class="space-y-4 mb-6 max-h-64 overflow-y-auto">
                            <!-- Items will be loaded via AJAX -->
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
                        </div>

                        <!-- Order Totals -->
                        <div id="order-totals" class="space-y-3 border-t border-gray-200 pt-4">
                            <!-- Totals will be loaded via AJAX -->
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-600 mx-auto"></div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="mt-6">
                            <label class="flex items-start">
                                <input type="checkbox" id="terms_agreement" name="terms_agreement" required
                                    class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded mt-1">
                                <span class="ml-2 text-sm text-gray-600">
                                    I agree to the <a href="terms.php" class="text-purple-600 hover:text-purple-700">Terms and Conditions</a>
                                    and <a href="privacy.php" class="text-purple-600 hover:text-purple-700">Privacy Policy</a>
                                </span>
                            </label>
                        </div>

                        <!-- Place Order Button -->
                        <button id="place-order-btn"
                            class="w-full bg-purple-600 text-white py-4 px-6 rounded-lg font-semibold hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed mt-6">
                            <span id="place-order-text">Place Order</span>
                            <span id="place-order-loading" class="hidden">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>

                        <!-- Security Badges -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex items-center justify-center space-x-6 text-gray-400">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-xs">Secure Payment</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-xs">Money Back</span>
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
        <span class="text-gray-700">Processing your order...</span>
    </div>
</div>

<!-- Success Modal -->
<div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Order Placed Successfully!</h3>
            <p class="text-gray-600 mb-6">Your order has been received and is being processed.</p>
            <div class="space-y-3">
                <a href="orders.php" class="w-full bg-purple-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-purple-700 transition-colors block">
                    View My Orders
                </a>
                <a href="index.php" class="w-full border border-gray-300 text-gray-700 py-3 px-6 rounded-lg font-semibold hover:bg-gray-50 transition-colors block">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    class CheckoutManager {
        constructor() {
            this.selectedAddressId = null;
            this.isEditing = false;
            this.currentEditingAddressId = null;
            this.init();
        }

        init() {
            this.loadOrderSummary();
            this.loadAddresses();
            this.bindEvents();
            this.setupFormValidation();
        }

        bindEvents() {
            // Place order button
            document.getElementById('place-order-btn').addEventListener('click', () => {
                this.placeOrder();
            });

            // Address management
            document.getElementById('add-address-btn').addEventListener('click', () => {
                this.showNewAddressForm();
            });

            document.getElementById('cancel-address-btn').addEventListener('click', () => {
                this.hideNewAddressForm();
            });

            document.getElementById('save-address-btn').addEventListener('click', () => {
                this.saveAddress();
            });

            // Real-time validation for contact form
            const contactFields = ['first_name', 'last_name', 'email', 'phone'];
            contactFields.forEach(field => {
                document.getElementById(field).addEventListener('input', () => {
                    this.validateForm();
                });
            });

            // Terms agreement validation
            document.getElementById('terms_agreement').addEventListener('change', () => {
                this.validateForm();
            });
        }

        setupFormValidation() {
            // Remove required attributes from address fields since we handle them separately
            const addressFields = ['address', 'city', 'region', 'postal_code'];
            addressFields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    element.removeAttribute('required');
                }
            });
        }

        async loadAddresses() {
            try {
                const container = document.getElementById('addresses-container');
                container.innerHTML = `
                    <div class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto mb-4"></div>
                        <p class="text-gray-600">Loading your addresses...</p>
                    </div>
                `;

                const response = await fetch('ajax/addresses.php?action=get_addresses');
                const data = await response.json();

                if (data.success) {
                    this.renderAddresses(data.addresses);
                } else {
                    this.showEmptyAddressState();
                }
            } catch (error) {
                console.error('Error loading addresses:', error);
                this.showEmptyAddressState();
            }
        }

        renderAddresses(addresses) {
            const container = document.getElementById('addresses-container');

            if (!addresses || addresses.length === 0) {
                this.showEmptyAddressState();
                return;
            }

            let html = '';
            addresses.forEach(address => {
                const isDefault = address.is_default == 1;
                const isSelected = this.selectedAddressId === address.address_id;

                html += `
                    <div class="address-card border-2 rounded-lg p-4 cursor-pointer transition-all duration-200 ${
                        isSelected 
                            ? 'border-purple-500 bg-purple-50' 
                            : 'border-gray-200 hover:border-gray-300'
                    } ${isDefault ? 'relative' : ''}" 
                         data-address-id="${address.address_id}">
                        ${isDefault ? `
                            <div class="absolute -top-2 -left-2 bg-purple-600 text-white text-xs font-medium px-2 py-1 rounded-full">
                                Default
                            </div>
                        ` : ''}
                        
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="text-sm font-medium text-gray-900 capitalize">${this.escapeHtml(address.address_type)}</span>
                                    ${isDefault ? `
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Default
                                        </span>
                                    ` : ''}
                                </div>
                                
                                <p class="text-sm text-gray-700 mb-1">${this.escapeHtml(address.street_address)}</p>
                                <p class="text-sm text-gray-600">
                                    ${this.escapeHtml(address.city)}, ${this.escapeHtml(address.region)} 
                                    ${address.postal_code ? `, ${this.escapeHtml(address.postal_code)}` : ''}
                                </p>
                                <p class="text-sm text-gray-600">${this.escapeHtml(address.country)}</p>
                            </div>
                            
                            <div class="flex items-center space-x-2 ml-4">
                                <input type="radio" name="selected_address" 
                                       value="${address.address_id}" 
                                       ${isSelected ? 'checked' : ''}
                                       class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300">
                            </div>
                        </div>
                        
                        <div class="mt-3 flex space-x-2">
                            <button type="button" class="edit-address text-sm text-purple-600 hover:text-purple-700 font-medium"
                                    data-address-id="${address.address_id}">
                                Edit
                            </button>
                            ${!isDefault ? `
                                <button type="button" class="delete-address text-sm text-red-600 hover:text-red-700 font-medium ml-4"
                                        data-address-id="${address.address_id}">
                                    Delete
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;

            // Add event listeners to address cards
            container.querySelectorAll('.address-card').forEach(card => {
                card.addEventListener('click', (e) => {
                    if (!e.target.closest('.edit-address') && !e.target.closest('.delete-address')) {
                        this.selectAddress(card.dataset.addressId);
                    }
                });
            });

            // Add event listeners to edit buttons
            container.querySelectorAll('.edit-address').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.editAddress(button.dataset.addressId);
                });
            });

            // Add event listeners to delete buttons
            container.querySelectorAll('.delete-address').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.deleteAddress(button.dataset.addressId);
                });
            });

            // Select address only if none is currently selected, or if the selected one no longer exists
            if (addresses.length > 0) {
                const currentSelectionExists = this.selectedAddressId && 
                    addresses.some(addr => addr.address_id === this.selectedAddressId);
                
                if (!currentSelectionExists) {
                    // No selection or invalid selection - choose default or first address
                    const defaultAddress = addresses.find(addr => addr.is_default == 1) || addresses[0];
                    this.selectAddress(defaultAddress.address_id);
                }
            }
        }

        showEmptyAddressState() {
            const container = document.getElementById('addresses-container');
            container.innerHTML = `
                <div class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No addresses saved</h3>
                    <p class="text-gray-600 mb-4">Add your first shipping address to continue</p>
                    <button type="button" id="add-first-address-btn" 
                            class="px-6 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        Add Address
                    </button>
                </div>
            `;

            document.getElementById('add-first-address-btn').addEventListener('click', () => {
                this.showNewAddressForm();
            });
        }

        showNewAddressForm(isEditing = false) {
            const form = document.getElementById('new-address-form');
            const title = form.querySelector('h3');
            const saveBtn = document.getElementById('save-address-btn');

            if (isEditing) {
                title.textContent = 'Edit Address';
                saveBtn.textContent = 'Update Address';
            } else {
                title.textContent = 'Add New Address';
                saveBtn.textContent = 'Save Address';
                this.clearNewAddressForm();
            }

            form.classList.remove('hidden');
            document.getElementById('add-address-btn').classList.add('hidden');
        }

        hideNewAddressForm() {
            const form = document.getElementById('new-address-form');
            form.classList.add('hidden');
            document.getElementById('add-address-btn').classList.remove('hidden');

            // Reset form state
            this.isEditing = false;
            this.currentEditingAddressId = null;
            this.clearNewAddressForm();
        }

        clearNewAddressForm() {
            document.getElementById('new_street_address').value = '';
            document.getElementById('new_city').value = '';
            document.getElementById('new_region').value = '';
            document.getElementById('new_postal_code').value = '';
            document.getElementById('address_type').value = 'home';
            document.getElementById('new_is_default').checked = false;
        }

        async saveAddress() {
            const streetAddress = document.getElementById('new_street_address').value.trim();
            const city = document.getElementById('new_city').value.trim();
            const region = document.getElementById('new_region').value;

            // Validate required fields
            if (!streetAddress || !city || !region) {
                this.showNotification('Please fill in all required fields', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('street_address', streetAddress);
            formData.append('city', city);
            formData.append('region', region);
            formData.append('postal_code', document.getElementById('new_postal_code').value.trim());
            formData.append('country', 'Ghana');
            formData.append('address_type', document.getElementById('address_type').value);
            formData.append('is_default', document.getElementById('new_is_default').checked ? '1' : '0');

            // Determine the action URL
            let actionUrl = 'ajax/addresses.php?action=add_address';
            if (this.isEditing && this.currentEditingAddressId) {
                actionUrl = 'ajax/addresses.php?action=update_address';
                formData.append('address_id', this.currentEditingAddressId);
            }

            this.showLoading(true);

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    const message = this.isEditing ? 'Address updated successfully' : 'Address added successfully';
                    this.showNotification(message, 'success');
                    this.hideNewAddressForm();
                    await this.loadAddresses();

                    // If we were editing and this was the selected address, keep it selected
                    if (this.isEditing && this.currentEditingAddressId === this.selectedAddressId) {
                        this.selectAddress(this.currentEditingAddressId);
                    }
                } else {
                    this.showNotification(data.message || 'Failed to save address', 'error');
                }
            } catch (error) {
                console.error('Error saving address:', error);
                this.showNotification('Failed to save address', 'error');
            } finally {
                this.hideLoading();
            }
        }

        async editAddress(addressId) {
            try {
                // Fetch address details
                const response = await fetch(`ajax/addresses.php?action=get_address&id=${addressId}`);
                const data = await response.json();

                if (data.success && data.address) {
                    this.populateEditForm(data.address);
                    this.setEditMode(addressId);
                } else {
                    this.showNotification(data.message || 'Failed to load address', 'error');
                }
            } catch (error) {
                console.error('Error loading address:', error);
                this.showNotification('Failed to load address', 'error');
            }
        }

        populateEditForm(address) {
            document.getElementById('new_street_address').value = address.street_address || '';
            document.getElementById('new_city').value = address.city || '';
            document.getElementById('new_region').value = address.region || '';
            document.getElementById('new_postal_code').value = address.postal_code || '';
            document.getElementById('address_type').value = address.address_type || 'home';
            document.getElementById('new_is_default').checked = address.is_default == 1;
        }

        setEditMode(addressId) {
            this.isEditing = true;
            this.currentEditingAddressId = addressId;
            this.showNewAddressForm(true);
        }

        selectAddress(addressId) {
            this.selectedAddressId = addressId;

            // Update UI
            document.querySelectorAll('.address-card').forEach(card => {
                if (card.dataset.addressId === addressId) {
                    card.classList.add('border-purple-500', 'bg-purple-50');
                    card.classList.remove('border-gray-200', 'hover:border-gray-300');
                    card.querySelector('input[type="radio"]').checked = true;
                } else {
                    card.classList.remove('border-purple-500', 'bg-purple-50');
                    card.classList.add('border-gray-200', 'hover:border-gray-300');
                    card.querySelector('input[type="radio"]').checked = false;
                }
            });

            // Trigger form validation
            this.validateForm();
        }

        async deleteAddress(addressId) {
            // Use the global modal instead of browser confirm
            if (typeof showConfirmationModal === 'function') {
                showConfirmationModal(
                    'Delete Address',
                    'Are you sure you want to delete this address? This action cannot be undone.',
                    async () => {
                        await this.performDeleteAddress(addressId);
                    }, {
                        type: 'error',
                        confirmText: 'Delete',
                        cancelText: 'Cancel'
                    }
                );
            } else {
                // Fallback to browser confirm
                if (confirm('Are you sure you want to delete this address? This action cannot be undone.')) {
                    await this.performDeleteAddress(addressId);
                }
            }
        }

        async performDeleteAddress(addressId) {
            this.showLoading(true);

            try {
                const response = await fetch('ajax/addresses.php?action=delete_address', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        address_id: addressId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Address deleted successfully', 'success');
                    // If we deleted the selected address, clear selection
                    if (this.selectedAddressId === addressId) {
                        this.selectedAddressId = null;
                    }
                    await this.loadAddresses();
                } else {
                    this.showNotification(data.message || 'Failed to delete address', 'error');
                }
            } catch (error) {
                console.error('Error deleting address:', error);
                this.showNotification('Failed to delete address', 'error');
            } finally {
                this.hideLoading();
            }
        }

        async loadOrderSummary() {
            try {
                const response = await fetch('ajax/cart.php?action=get_cart_summary');
                const data = await response.json();

                if (data.success) {
                    this.renderOrderItems(data);
                    this.renderOrderTotals(data);
                }
            } catch (error) {
                console.error('Error loading order summary:', error);
            }
        }

        async renderOrderItems(data) {
            const container = document.getElementById('order-items');
            container.innerHTML = '';

            const escapeHtml = (s) => String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            try {
                const resp = await fetch('ajax/cart.php?action=get_cart_preview');
                const preview = await resp.json();

                if (!preview.success) {
                    container.innerHTML = `
                    <div class="text-sm text-gray-600">
                        <div class="flex justify-between mb-2">
                            <span>${data.cart_count} items</span>
                            <span>${data.formatted_total}</span>
                        </div>
                    </div>
                `;
                    return;
                }

                const items = preview.items || [];
                if (items.length === 0) {
                    container.innerHTML = '<div class="text-sm text-gray-600">Your cart is empty</div>';
                    return;
                }

                let html = '<div class="space-y-4">';
                items.forEach(item => {
                    const unitPrice = parseFloat(item.price || 0).toFixed(2);
                    const qty = parseInt(item.quantity || 0, 10);
                    const lineTotal = (parseFloat(item.price || 0) * qty).toFixed(2);
                    const image = item.image && item.image !== '' ? escapeHtml(item.image) : 'assets/images/placeholder-product.jpg';

                    html += `
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <img src="${image}" 
                                 alt="${escapeHtml(item.name)}" 
                                 class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg border border-gray-200"
                                 onerror="this.onerror=null; this.src='assets/images/placeholder-product.jpg';">
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 line-clamp-2">${escapeHtml(item.name)}</div>
                            ${item.size ? `<div class="text-xs text-gray-500 mt-1">Size: ${escapeHtml(item.size)}</div>` : ''}
                            <div class="text-xs text-gray-500 mt-1">Qty: ${qty}</div>
                            <div class="text-xs text-gray-500">GHS ${unitPrice} each</div>
                        </div>
                        <div class="text-sm font-medium text-gray-900 whitespace-nowrap">GHS ${lineTotal}</div>
                    </div>
                `;
                });
                html += '</div>';
                html += '<div class="text-xs text-gray-500 mt-2">Items will be delivered within 3-5 business days</div>';
                container.innerHTML = html;

            } catch (err) {
                console.error('Error rendering order items:', err);
                container.innerHTML = `
                <div class="text-sm text-gray-600">
                    <div class="flex justify-between mb-2">
                        <span>${data.cart_count} items</span>
                        <span>${data.formatted_total}</span>
                    </div>
                    <div class="text-xs text-gray-500">Items will be delivered within 3-5 business days</div>
                </div>
            `;
            }
        }

        renderOrderTotals(data) {
            const container = document.getElementById('order-totals');

            let html = `
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Subtotal</span>
            <span class="font-medium">${data.formatted_subtotal || data.formatted_total}</span>
        </div>`;

            // Add discount line if coupon is applied
            if (data.discount_amount && parseFloat(data.discount_amount) > 0) {
                html += `
            <div class="flex justify-between text-sm discount-line">
                <span>Discount</span>
                <span>-${data.formatted_discount}</span>
            </div>`;
            }

            html += `
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Shipping</span>
            <span class="font-medium text-green-600">${data.formatted_shipping || 'Free'}</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Tax</span>
            <span class="font-medium">${data.formatted_tax || 'GHS 0.00'}</span>
        </div>
        <div class="border-t border-gray-200 pt-3">
            <div class="flex justify-between text-lg font-semibold">
                <span>Total</span>
                <span>${data.formatted_grand_total || data.formatted_total}</span>
            </div>
        </div>`;

            container.innerHTML = html;
        }

        validateForm() {
            // Check contact information fields
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();

            // Basic validation for contact info
            const contactValid = firstName && lastName && email && phone;

            // Check additional requirements
            const termsAgreed = document.getElementById('terms_agreement').checked;
            const addressSelected = this.selectedAddressId !== null;
            const placeOrderBtn = document.getElementById('place-order-btn');

            const isValid = contactValid && termsAgreed && addressSelected;
            placeOrderBtn.disabled = !isValid;

            // Debug logging
            console.log('Form Validation:', {
                contactValid,
                termsAgreed,
                addressSelected,
                isValid
            });

            // Update button state and title
            if (!isValid) {
                if (!addressSelected) {
                    placeOrderBtn.title = 'Please select a shipping address';
                } else if (!termsAgreed) {
                    placeOrderBtn.title = 'Please agree to terms and conditions';
                } else if (!contactValid) {
                    placeOrderBtn.title = 'Please fill in all contact information';
                }
            } else {
                placeOrderBtn.title = 'Place your order';
            }

            return isValid;
        }

        async placeOrder() {
    if (!this.validateForm()) {
        if (!this.selectedAddressId) {
            this.showNotification('Please select a shipping address', 'error');
        } else if (!document.getElementById('terms_agreement').checked) {
            this.showNotification('Please agree to terms and conditions', 'error');
        } else {
            this.showNotification('Please fill in all required contact information', 'error');
        }
        return;
    }

    // Prevent multiple clicks
    const placeOrderBtn = document.getElementById('place-order-btn');
    placeOrderBtn.disabled = true;

    this.showLoading(true);

    try {
        // Collect order data
        const orderData = this.collectOrderData();

        console.log('Sending order data:', orderData);

        // Create order first
        const orderResponse = await fetch('ajax/orders.php?action=create_order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });

        // DEBUG: Log the raw response
        const responseText = await orderResponse.text();
        console.log('Raw server response:', responseText);
        
        // Check if response is JSON
        const contentType = orderResponse.headers.get('content-type');
        console.log('Content-Type:', contentType);
        
        let orderResult;
        try {
            orderResult = JSON.parse(responseText);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
            console.error('Response that failed to parse:', responseText);
            throw new Error('Server returned invalid response. Check console for details.');
        }

        if (!orderResult.success) {
            throw new Error(orderResult.message || 'Failed to create order');
        }

        console.log('Order created successfully:', orderResult);

        // Initialize Paystack inline payment
        await this.initializePaystackPayment(orderResult.order_id, orderResult.total_amount, orderData.customer.email);

    } catch (error) {
        console.error('Error placing order:', error);
        this.showError(error.message || 'An error occurred while processing your order. Please try again.');
        this.resetButtonState();
    }
}

        collectOrderData() {
            // Get address details from the selected address
            const selectedCard = document.querySelector(`.address-card[data-address-id="${this.selectedAddressId}"]`);
            let address = '',
                city = '',
                region = '',
                postal_code = '';

            if (selectedCard) {
                const addressText = selectedCard.querySelector('p.text-gray-700');
                const cityRegionText = selectedCard.querySelectorAll('p.text-gray-600')[0];

                if (addressText) address = addressText.textContent.trim();
                if (cityRegionText) {
                    const parts = cityRegionText.textContent.split(', ');
                    city = parts[0] || '';
                    region = parts[1] || '';
                    postal_code = parts[2] || '';
                }
            }

            return {
                customer: {
                    first_name: document.getElementById('first_name').value.trim(),
                    last_name: document.getElementById('last_name').value.trim(),
                    email: document.getElementById('email').value.trim(),
                    phone: document.getElementById('phone').value.trim()
                },
                shipping: {
                    address: address,
                    city: city,
                    region: region,
                    postal_code: postal_code,
                    country: 'Ghana'
                },
                payment: {
                    method: 'paystack_inline'
                },
                address_id: this.selectedAddressId
            };
        }

        async initializePaystackPayment(orderId, amount, email) {
            try {
                // Get Paystack public key from server
                const configResponse = await fetch('ajax/payment.php?action=get_paystack_config');
                const config = await configResponse.json();

                if (!config.success) {
                    throw new Error('Payment configuration failed');
                }

                const paystackPublicKey = config.public_key;
                const formattedAmount = Math.round(amount * 100); // Convert to kobo

                // Initialize Paystack
                const handler = PaystackPop.setup({
                    key: paystackPublicKey,
                    email: email,
                    amount: formattedAmount,
                    currency: 'GHS',
                    ref: 'ORD_' + orderId + '_' + Date.now(),
                    metadata: {
                        order_id: orderId,
                        custom_fields: [{
                            display_name: "Order ID",
                            variable_name: "order_id",
                            value: orderId
                        }]
                    },
                    callback: (response) => {
                        // Payment successful
                        this.handlePaymentSuccess(orderId, response.reference);
                    },
                    onClose: () => {
                        // Payment modal closed - reset everything
                        console.log('Paystack payment modal closed by user');
                        this.resetButtonState();
                        this.showNotification('Payment was cancelled. You can try again.', 'warning');
                    }
                });

                // Open Paystack payment modal
                handler.openIframe();

            } catch (error) {
                console.error('Paystack initialization error:', error);
                this.showError('Payment initialization failed: ' + error.message);
                this.resetButtonState();
            }
        }

        async handlePaymentSuccess(orderId, reference) {
            try {
                this.showLoading(true);

                // Verify payment with server
                const verifyResponse = await fetch('ajax/payment.php?action=verify_payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        reference: reference
                    })
                });

                const verifyResult = await verifyResponse.json();

                if (verifyResult.success) {
                    // Get order details to display in confirmation
                    const orderDetailsResponse = await fetch(`ajax/orders.php?action=get_order_details&order_id=${orderId}`);
                    const orderDetails = await orderDetailsResponse.json();

                    let orderNumber = null;
                    let totalAmount = null;

                    if (orderDetails.success && orderDetails.order) {
                        orderNumber = orderDetails.order.order_number; // This will now be the actual order number
                        totalAmount = orderDetails.order.total_amount;
                    }

                    // Payment verified successfully
                    await this.clearCart();
                    this.showLoading(false);
                    this.showSuccess(orderId, orderNumber, totalAmount);
                } else {
                    throw new Error(verifyResult.message || 'Payment verification failed');
                }

            } catch (error) {
                console.error('Payment verification error:', error);
                this.showError('Payment verification failed: ' + error.message);
                this.resetButtonState();
            }
        }

        async clearCart() {
            try {
                await fetch('ajax/cart.php?action=clear_cart', {
                    method: 'POST'
                });

                // Update header cart count
                const headerCount = document.getElementById('cart-count');
                if (headerCount) {
                    headerCount.textContent = '0';
                }
                localStorage.setItem('cartCount', '0');
            } catch (error) {
                console.error('Error clearing cart:', error);
            }
        }

        resetButtonState() {
            const button = document.getElementById('place-order-btn');
            const buttonText = document.getElementById('place-order-text');
            const buttonLoading = document.getElementById('place-order-loading');

            // Reset button to normal state
            button.disabled = false;
            buttonText.classList.remove('hidden');
            buttonLoading.classList.add('hidden');

            // Hide loading overlay
            this.hideLoading();

            // Re-validate form
            this.validateForm();
        }

        showLoading(show) {
            const overlay = document.getElementById('loading-overlay');
            const button = document.getElementById('place-order-btn');
            const buttonText = document.getElementById('place-order-text');
            const buttonLoading = document.getElementById('place-order-loading');

            if (show) {
                overlay.classList.remove('hidden');
                buttonText.classList.add('hidden');
                buttonLoading.classList.remove('hidden');
                button.disabled = true;
            } else {
                overlay.classList.add('hidden');
                buttonText.classList.remove('hidden');
                buttonLoading.classList.add('hidden');
                button.disabled = false;
            }
        }

        hideLoading() {
            this.showLoading(false);
        }

        showSuccess(orderId = null, orderNumber = null, totalAmount = null) {
            // Create enhanced success modal
            const successModal = document.createElement('div');
            successModal.id = 'success-modal-enhanced';
            successModal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            successModal.innerHTML = `
                <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">Payment Successful!</h3>
                        <p class="text-green-600 font-semibold mb-2">Your payment has been confirmed</p>
                        <p class="text-gray-600 mb-6">Your order is now being processed and will be delivered soon.</p>
                        
                        ${orderId ? `<p class="text-sm text-gray-500 mb-4">Order ID: ${orderId}</p>` : ''}
                        ${orderNumber ? `<p class="text-sm text-gray-500 mb-2">Order Number: ${orderNumber}</p>` : ''}
                        ${totalAmount ? `<p class="text-sm text-gray-500 mb-2">Total Amount: GHS ${parseFloat(totalAmount).toFixed(2)}</p>` : ''}
                        
                        <div class="space-y-3">
                            <button id="view-order-btn" class="w-full bg-purple-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                                View Order Details
                            </button>
                            <button id="continue-shopping-btn" class="w-full border border-gray-300 text-gray-700 py-3 px-6 rounded-lg font-semibold hover:bg-gray-50 transition-colors">
                                Continue Shopping
                            </button>
                        </div>
                        
                        <div class="mt-4 text-xs text-gray-500">
                            <p>Redirecting to order confirmation in <span id="countdown">5</span> seconds...</p>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(successModal);

            // Start countdown for automatic redirection
            let countdown = 5;
            const countdownElement = document.getElementById('countdown');
            const countdownInterval = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;

                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    this.redirectToConfirmation(orderId, orderNumber, totalAmount);
                }
            }, 1000);

            // Add event listeners for buttons
            document.getElementById('view-order-btn').addEventListener('click', () => {
                clearInterval(countdownInterval);
                this.redirectToConfirmation(orderId, orderNumber, totalAmount);
            });

            document.getElementById('continue-shopping-btn').addEventListener('click', () => {
                clearInterval(countdownInterval);
                window.location.href = 'index.php';
            });
        }

        redirectToConfirmation(orderId = null, orderNumber = null, totalAmount = null) {
            let url = 'confirmation.php';
            const params = new URLSearchParams();

            if (orderId) params.append('order_id', orderId);
            if (orderNumber) params.append('order_number', orderNumber);
            if (totalAmount) params.append('total_amount', totalAmount);

            if (params.toString()) {
                url += '?' + params.toString();
            }

            window.location.href = url;
        }

        showError(message) {
            this.showNotification(message, 'error');
        }

        showNotification(message, type = 'info') {
            if (typeof showNotification === 'function') {
                showNotification(message, type);
            } else {
                // Fallback notification
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                    type === 'error' ? 'bg-red-500 text-white' :
                    type === 'success' ? 'bg-green-500 text-white' :
                    type === 'warning' ? 'bg-yellow-500 text-white' :
                    'bg-blue-500 text-white'
                }`;
                notification.textContent = message;
                document.body.appendChild(notification);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }
        }

        escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return String(unsafe)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    }

    // Initialize checkout manager when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        new CheckoutManager();
    });
</script>

<script>
    (function(){
        function applyMaintenanceToCheckout() {
            try {
                var btn = document.getElementById('place-order-btn');
                if (!btn) return;

                if (window.APP_MAINTENANCE) {
                    btn.disabled = true;
                    btn.title = 'Ordering is temporarily disabled due to maintenance.' + (window.MAINTENANCE_END ? (' Scheduled to end: ' + window.MAINTENANCE_END) : '');
                    if (!document.getElementById('maintenance-checkout-note')) {
                        var note = document.createElement('div');
                        note.id = 'maintenance-checkout-note';
                        note.style.marginTop = '8px';
                        note.style.color = '#a00';
                        note.textContent = 'Ordering is temporarily disabled due to maintenance.' + (window.MAINTENANCE_END ? (' Scheduled to end: ' + window.MAINTENANCE_END) : '');
                        btn.parentNode.insertBefore(note, btn.nextSibling);
                    }
                } else {
                    btn.disabled = false;
                    btn.title = 'Place your order';
                    var note = document.getElementById('maintenance-checkout-note');
                    if (note) note.parentNode.removeChild(note);
                }
            } catch (e) {
                // ignore
            }
        }

        document.addEventListener('DOMContentLoaded', applyMaintenanceToCheckout);
        window.addEventListener('load', applyMaintenanceToCheckout);

        // Poll for changes to the window flag (in case header script runs later)
        var last = typeof window.APP_MAINTENANCE !== 'undefined' ? window.APP_MAINTENANCE : null;
        setInterval(function(){
            if (typeof window.APP_MAINTENANCE !== 'undefined' && window.APP_MAINTENANCE !== last) {
                last = window.APP_MAINTENANCE;
                applyMaintenanceToCheckout();
            }
        }, 500);
    })();
</script>

<style>
    .discount-line {
        color: #10b981;
        font-weight: 600;
    }

    .coupon-success {
        border-color: #10b981 !important;
        background-color: #f0fdf4 !important;
    }

    .address-card {
        transition: all 0.2s ease-in-out;
    }

    .address-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .address-card.selected {
        border-color: #8b5cf6;
        background-color: #faf5ff;
    }

    /* Smooth animations for address cards */
    .address-card {
        animation: fadeInUp 0.3s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Custom scrollbar for order items */
    #order-items::-webkit-scrollbar {
        width: 4px;
    }

    #order-items::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    #order-items::-webkit-scrollbar-thumb {
        background: #c4b5fd;
        border-radius: 10px;
    }

    #order-items::-webkit-scrollbar-thumb:hover {
        background: #a78bfa;
    }
</style>

<?php
require_once 'includes/footer.php';
?>