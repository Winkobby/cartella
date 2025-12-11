<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Load store name and contact email from settings (fall back to constants/defaults)
$store_name = SettingsHelper::get($pdo, 'site_name', defined('SITE_NAME') ? SITE_NAME : 'Cartella');
$contact_email = SettingsHelper::get($pdo, 'contact_email', 'support@' . ($_SERVER['HTTP_HOST'] ?? 'example.com'));
$store_phone = SettingsHelper::get($pdo, 'store_phone', '+233 (24) 131-1105');

$page_title = 'FAQ - ' . $store_name;
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">Frequently Asked Questions</h1>
            <p class="text-gray-600 max-w-2xl mx-auto">Find quick answers to common questions about ordering, shipping, returns, and more.</p>
            
            <!-- Quick Search -->
            <div class="mt-6 max-w-md mx-auto relative">
                <div class="relative">
                    <input type="text" 
                           id="faqSearch" 
                           placeholder="Search for answers..." 
                           class="w-full px-4 py-3 pl-12 pr-10 text-gray-700 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                    <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <button id="clearSearch" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2 text-center">Type keywords like "shipping", "returns", or "payment"</p>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 sticky top-4 z-10">
            <div class="flex flex-wrap gap-2 justify-center">
                <a href="#ordering" class="inline-flex items-center px-3 py-1.5 bg-purple-50 text-purple-700 rounded-full text-sm font-medium hover:bg-purple-100 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    Ordering
                </a>
                <a href="#shipping" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-full text-sm font-medium hover:bg-blue-100 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                    Shipping
                </a>
                <a href="#returns" class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-700 rounded-full text-sm font-medium hover:bg-green-100 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path>
                    </svg>
                    Returns
                </a>
                <a href="#products" class="inline-flex items-center px-3 py-1.5 bg-yellow-50 text-yellow-700 rounded-full text-sm font-medium hover:bg-yellow-100 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Products
                </a>
                <a href="#payments" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 rounded-full text-sm font-medium hover:bg-red-100 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    Payments
                </a>
            </div>
        </div>

        <!-- FAQ Sections -->
        <div class="space-y-6">
            <!-- Ordering Section -->
            <section id="ordering" class="faq-section">
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Ordering</h2>
                            <p class="text-gray-600 text-sm">How to place and manage your orders</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- FAQ Item 1 -->
                        <div class="faq-item bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-sm transition-shadow duration-200">
                            <button class="faq-question w-full px-5 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                                <span class="font-semibold text-gray-800 text-lg">How do I place an order?</span>
                                <svg class="w-5 h-5 text-purple-600 faq-icon transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer px-5 pb-4 hidden">
                                <div class="pt-2 border-t border-gray-100">
                                    <div class="bg-purple-50 rounded-lg p-4 mb-4">
                                        <h4 class="font-medium text-gray-800 mb-2">Simple steps to order:</h4>
                                        <ol class="space-y-2 text-gray-700 text-sm">
                                            <li class="flex items-start">
                                                <span class="bg-purple-100 text-purple-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">1</span>
                                                <span>Browse products and select your desired item</span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="bg-purple-100 text-purple-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">2</span>
                                                <span>Choose options (size, color, quantity)</span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="bg-purple-100 text-purple-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">3</span>
                                                <span>Click "Add to Cart"</span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="bg-purple-100 text-purple-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">4</span>
                                                <span>Go to cart and click "Proceed to Checkout"</span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="bg-purple-100 text-purple-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">5</span>
                                                <span>Enter shipping/billing details and payment information</span>
                                            </li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 2 -->
                        <div class="faq-item bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-sm transition-shadow duration-200">
                            <button class="faq-question w-full px-5 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                                <span class="font-semibold text-gray-800 text-lg">Can I modify or cancel my order after placing it?</span>
                                <svg class="w-5 h-5 text-purple-600 faq-icon transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer px-5 pb-4 hidden">
                                <div class="pt-2 border-t border-gray-100">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-gray-700">Orders are processed quickly to ensure fast delivery. If you need to make changes or cancel your order, please contact us immediately at <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="text-purple-600 font-medium"><?php echo htmlspecialchars($contact_email); ?></a> with your order number.</p>
                                            <div class="mt-3 p-3 bg-yellow-50 rounded-lg">
                                                <p class="text-sm text-yellow-800"><strong>Note:</strong> Once an order has shipped, modifications or cancellations may not be possible. In such cases, you may need to follow our return process after receiving the package.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Shipping & Delivery Section -->
            <section id="shipping" class="faq-section">
                <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Shipping & Delivery</h2>
                            <p class="text-gray-600 text-sm">Information about shipping options and delivery times</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- FAQ Item 1 -->
                        <div class="faq-item bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-sm transition-shadow duration-200">
                            <button class="faq-question w-full px-5 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                                <span class="font-semibold text-gray-800 text-lg">What shipping options are available?</span>
                                <svg class="w-5 h-5 text-blue-600 faq-icon transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer px-5 pb-4 hidden">
                                <div class="pt-2 border-t border-gray-100">
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div class="bg-blue-50 rounded-lg p-4">
                                            <h4 class="font-medium text-gray-800 mb-2 flex items-center">
                                                <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Standard Shipping
                                            </h4>
                                            <p class="text-sm text-gray-700">Economical option with tracking. Delivery typically takes 5-10 business days.</p>
                                        </div>
                                        <div class="bg-blue-50 rounded-lg p-4">
                                            <h4 class="font-medium text-gray-800 mb-2 flex items-center">
                                                <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Express Shipping
                                            </h4>
                                            <p class="text-sm text-gray-700">Faster delivery option with priority handling. Usually arrives in 2-5 business days.</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 p-3 bg-gradient-to-r from-blue-100 to-cyan-100 rounded-lg">
                                        <p class="text-sm text-blue-800"><strong>Free Shipping:</strong> We offer free shipping on orders above a certain amount. The threshold and availability are automatically applied during checkout.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 2 -->
                        <div class="faq-item bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-sm transition-shadow duration-200">
                            <button class="faq-question w-full px-5 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                                <span class="font-semibold text-gray-800 text-lg">How long will delivery take?</span>
                                <svg class="w-5 h-5 text-blue-600 faq-icon transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer px-5 pb-4 hidden">
                                <div class="pt-2 border-t border-gray-100">
                                    <p class="text-gray-700 mb-4">Delivery times vary based on your location and the shipping method selected:</p>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                            <span class="font-medium text-gray-800">Domestic Orders</span>
                                            <span class="text-sm font-medium text-blue-600">2-7 business days</span>
                                        </div>
                                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                            <span class="font-medium text-gray-800">International Orders</span>
                                            <span class="text-sm font-medium text-blue-600">7-21 business days</span>
                                        </div>
                                    </div>
                                    <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                                        <p class="text-sm text-yellow-800"><strong>Note:</strong> These are estimated delivery times. Actual delivery may vary due to customs, weather conditions, or carrier delays. You'll receive tracking information once your order ships.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Returns & Refunds Section -->
            <section id="returns" class="faq-section">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Returns & Refunds</h2>
                            <p class="text-gray-600 text-sm">Our return policy and refund process</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- FAQ Item 1 -->
                        <div class="faq-item bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-sm transition-shadow duration-200">
                            <button class="faq-question w-full px-5 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                                <span class="font-semibold text-gray-800 text-lg">What is your returns policy?</span>
                                <svg class="w-5 h-5 text-green-600 faq-icon transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer px-5 pb-4 hidden">
                                <div class="pt-2 border-t border-gray-100">
                                    <div class="space-y-4">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0 w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-gray-700">If you're not satisfied with your purchase, contact us within the timeframe stated in our Returns Policy (usually 30 days from delivery).</p>
                                            </div>
                                        </div>
                                        <div class="bg-green-50 rounded-lg p-4">
                                            <h4 class="font-medium text-gray-800 mb-2">Return Process:</h4>
                                            <ol class="space-y-2 text-gray-700 text-sm">
                                                <li class="flex items-start">
                                                    <span class="bg-green-100 text-green-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">1</span>
                                                    <span>Contact customer service for a Return Authorization</span>
                                                </li>
                                                <li class="flex items-start">
                                                    <span class="bg-green-100 text-green-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">2</span>
                                                    <span>Pack item securely with original packaging</span>
                                                </li>
                                                <li class="flex items-start">
                                                    <span class="bg-green-100 text-green-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">3</span>
                                                    <span>Include all accessories and documentation</span>
                                                </li>
                                                <li class="flex items-start">
                                                    <span class="bg-green-100 text-green-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">4</span>
                                                    <span>Ship to our returns address</span>
                                                </li>
                                            </ol>
                                        </div>
                                        <div class="p-3 bg-yellow-50 rounded-lg">
                                            <p class="text-sm text-yellow-800"><strong>Important:</strong> Some items like perishable goods, intimate apparel, or personalized products may be non-returnable. Check individual product pages for specific restrictions.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 2 -->
                        <div class="faq-item bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-sm transition-shadow duration-200">
                            <button class="faq-question w-full px-5 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                                <span class="font-semibold text-gray-800 text-lg">When will I receive my refund?</span>
                                <svg class="w-5 h-5 text-green-600 faq-icon transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer px-5 pb-4 hidden">
                                <div class="pt-2 border-t border-gray-100">
                                    <div class="space-y-3">
                                        <p class="text-gray-700">Refund timing depends on several factors:</p>
                                        <div class="grid md:grid-cols-3 gap-3">
                                            <div class="text-center p-3 bg-green-50 rounded-lg">
                                                <div class="text-lg font-bold text-green-600 mb-1">1-3 days</div>
                                                <p class="text-xs text-gray-700">Processing time after we receive return</p>
                                            </div>
                                            <div class="text-center p-3 bg-green-50 rounded-lg">
                                                <div class="text-lg font-bold text-green-600 mb-1">3-5 days</div>
                                                <p class="text-xs text-gray-700">Credit card refund processing</p>
                                            </div>
                                            <div class="text-center p-3 bg-green-50 rounded-lg">
                                                <div class="text-lg font-bold text-green-600 mb-1">5-10 days</div>
                                                <p class="text-xs text-gray-700">Bank transfer refunds</p>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-blue-50 rounded-lg">
                                            <p class="text-sm text-blue-800"><strong>Note:</strong> The refund method will match your original payment method. Once we issue the refund, it may take a few business days to appear in your account depending on your bank or payment provider.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Products & Availability Section -->
            <section id="products" class="faq-section">
                <div class="bg-gradient-to-r from-yellow-50 to-amber-50 rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-amber-500 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Products & Availability</h2>
                            <p class="text-gray-600 text-sm">Information about products, sizing, and stock</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- FAQ Item 1 -->
                        <div class="faq-item bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-sm transition-shadow duration-200">
                            <button class="faq-question w-full px-5 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                                <span class="font-semibold text-gray-800 text-lg">How do I choose the correct size?</span>
                                <svg class="w-5 h-5 text-yellow-600 faq-icon transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer px-5 pb-4 hidden">
                                <div class="pt-2 border-t border-gray-100">
                                    <div class="space-y-4">
                                        <p class="text-gray-700">We provide detailed sizing information to help you choose the right fit:</p>
                                        <div class="grid md:grid-cols-2 gap-4">
                                            <div class="bg-yellow-50 rounded-lg p-4">
                                                <h4 class="font-medium text-gray-800 mb-2">📏 Size Charts</h4>
                                                <p class="text-sm text-gray-700">Most clothing items include detailed size charts with measurements in inches/cm.</p>
                                            </div>
                                            <div class="bg-yellow-50 rounded-lg p-4">
                                                <h4 class="font-medium text-gray-800 mb-2">👕 Fit Guides</h4>
                                                <p class="text-sm text-gray-700">Product descriptions often include fit information (slim, regular, relaxed).</p>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-purple-50 rounded-lg">
                                            <p class="text-sm text-purple-800"><strong>Tip:</strong> If you're between sizes, we recommend sizing up. You can also check customer reviews for sizing feedback from other shoppers.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 2 -->
                        <div class="faq-item bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-sm transition-shadow duration-200">
                            <button class="faq-question w-full px-5 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                                <span class="font-semibold text-gray-800 text-lg">What if an item is out of stock?</span>
                                <svg class="w-5 h-5 text-yellow-600 faq-icon transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer px-5 pb-4 hidden">
                                <div class="pt-2 border-t border-gray-100">
                                    <div class="space-y-4">
                                        <p class="text-gray-700">If an item is out of stock, here are your options:</p>
                                        <div class="space-y-2">
                                            <div class="flex items-start space-x-2 p-3 bg-yellow-50 rounded-lg">
                                                <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                                </svg>
                                                <div>
                                                    <h4 class="font-medium text-gray-800">Back in Stock Alerts</h4>
                                                    <p class="text-sm text-gray-700">Contact us to be notified when the item is restocked.</p>
                                                </div>
                                            </div>
                                            <div class="flex items-start space-x-2 p-3 bg-yellow-50 rounded-lg">
                                                <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                                <div>
                                                    <h4 class="font-medium text-gray-800">Similar Items</h4>
                                                    <p class="text-sm text-gray-700">Browse our collection for similar products that are in stock.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Payments & Security Section -->
            <section id="payments" class="faq-section">
                <div class="bg-gradient-to-r from-red-50 to-pink-50 rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-pink-500 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Payments & Security</h2>
                            <p class="text-gray-600 text-sm">Secure payment methods and data protection</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- FAQ Item 1 -->
                        <div class="faq-item bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-sm transition-shadow duration-200">
                            <button class="faq-question w-full px-5 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                                <span class="font-semibold text-gray-800 text-lg">Which payment methods do you accept?</span>
                                <svg class="w-5 h-5 text-red-600 faq-icon transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer px-5 pb-4 hidden">
                                <div class="pt-2 border-t border-gray-100">
                                    <div class="space-y-4">
                                        <p class="text-gray-700">We accept all major payment methods for your convenience:</p>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                            <div class="text-center p-3 bg-red-50 rounded-lg">
                                                <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center mx-auto mb-2 shadow-sm">
                                                    <span class="text-lg font-bold text-red-600">💳</span>
                                                </div>
                                                <span class="text-xs font-medium text-gray-800">Credit Cards</span>
                                            </div>
                                            <div class="text-center p-3 bg-red-50 rounded-lg">
                                                <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center mx-auto mb-2 shadow-sm">
                                                    <span class="text-lg font-bold text-red-600">🏦</span>
                                                </div>
                                                <span class="text-xs font-medium text-gray-800">Debit Cards</span>
                                            </div>
                                            <div class="text-center p-3 bg-red-50 rounded-lg">
                                                <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center mx-auto mb-2 shadow-sm">
                                                    <span class="text-lg font-bold text-red-600">📱</span>
                                                </div>
                                                <span class="text-xs font-medium text-gray-800">Mobile Wallets</span>
                                            </div>
                                            <div class="text-center p-3 bg-red-50 rounded-lg">
                                                <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center mx-auto mb-2 shadow-sm">
                                                    <span class="text-lg font-bold text-red-600">💲</span>
                                                </div>
                                                <span class="text-xs font-medium text-gray-800">Bank Transfer</span>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-green-50 rounded-lg">
                                            <p class="text-sm text-green-800"><strong>Security:</strong> All payments are processed through secure gateways with SSL encryption. We do not store full card details on our servers.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Still Have Questions Section -->
        <div class="mt-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-8 text-white">
            <div class="text-center max-w-2xl mx-auto">
                <h2 class="text-2xl font-bold mb-4">Still Have Questions?</h2>
                <p class="text-indigo-100 mb-6">Can't find the answer you're looking for? Our customer support team is here to help!</p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="contact.php" 
                       class="bg-white text-indigo-600 hover:bg-indigo-50 px-6 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg inline-flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <span>Contact Support</span>
                    </a>
                    
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" 
                       class="bg-transparent border-2 border-white hover:bg-white hover:text-indigo-600 px-6 py-3 rounded-lg font-semibold transition-all duration-300 inline-flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>Email Us</span>
                    </a>
                </div>
                
                <div class="mt-8 pt-6 border-t border-indigo-400">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="text-center">
                            <div class="inline-flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <span class="font-medium">Call Us</span>
                            </div>
                            <p class="text-lg font-semibold mt-1"><?php echo htmlspecialchars($store_phone); ?></p>
                        </div>
                        <div class="text-center">
                            <div class="inline-flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span class="font-medium">Email Us</span>
                            </div>
                            <p class="text-lg font-semibold mt-1"><?php echo htmlspecialchars($contact_email); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mt-8 text-center">
            <div class="inline-flex space-x-4">
                <a href="terms.php" class="text-sm text-gray-600 hover:text-purple-600 transition-colors">
                    Terms & Conditions
                </a>
                <span class="text-gray-400">•</span>
                <a href="privacy.php" class="text-sm text-gray-600 hover:text-purple-600 transition-colors">
                    Privacy Policy
                </a>
                <span class="text-gray-400">•</span>
                <a href="shipping-policy.php" class="text-sm text-gray-600 hover:text-purple-600 transition-colors">
                    Shipping Policy
                </a>
                <span class="text-gray-400">•</span>
                <a href="index.php" class="text-sm text-gray-600 hover:text-purple-600 transition-colors">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Functionality Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // FAQ Accordion Functionality
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                const faqItem = button.closest('.faq-item');
                const answer = faqItem.querySelector('.faq-answer');
                const icon = faqItem.querySelector('.faq-icon');
                
                // Close other open FAQs
                document.querySelectorAll('.faq-answer').forEach(otherAnswer => {
                    if (otherAnswer !== answer && !otherAnswer.classList.contains('hidden')) {
                        otherAnswer.classList.add('hidden');
                        otherAnswer.closest('.faq-item').querySelector('.faq-icon').classList.remove('rotate-180');
                    }
                });
                
                // Toggle current FAQ
                answer.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            });
        });
        
        // Search Functionality
        const searchInput = document.getElementById('faqSearch');
        const clearButton = document.getElementById('clearSearch');
        const faqSections = document.querySelectorAll('.faq-section');
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                if (searchTerm.length > 0) {
                    clearButton.classList.remove('hidden');
                    
                    // Search through FAQ content
                    let hasResults = false;
                    
                    faqSections.forEach(section => {
                        const questions = section.querySelectorAll('.faq-question span');
                        let sectionHasMatch = false;
                        
                        questions.forEach(question => {
                            const answer = question.closest('.faq-item').querySelector('.faq-answer');
                            const questionText = question.textContent.toLowerCase();
                            const answerText = answer.textContent.toLowerCase();
                            
                            if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                                // Show matching FAQ
                                question.closest('.faq-item').classList.remove('hidden');
                                question.closest('.faq-item').style.opacity = '1';
                                sectionHasMatch = true;
                                hasResults = true;
                                
                                // Auto-open matching FAQ
                                if (answer.classList.contains('hidden')) {
                                    answer.classList.remove('hidden');
                                    question.closest('.faq-item').querySelector('.faq-icon').classList.add('rotate-180');
                                }
                            } else {
                                // Hide non-matching FAQ
                                question.closest('.faq-item').classList.add('hidden');
                            }
                        });
                        
                        // Show/hide entire section based on matches
                        section.style.display = sectionHasMatch ? 'block' : 'none';
                    });
                    
                    // Show no results message
                    if (!hasResults) {
                        showNoResults(searchTerm);
                    }
                } else {
                    clearButton.classList.add('hidden');
                    // Reset view
                    resetSearch();
                }
            });
            
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                clearButton.classList.add('hidden');
                resetSearch();
            });
        }
        
        function resetSearch() {
            // Show all FAQ items and sections
            faqSections.forEach(section => {
                section.style.display = 'block';
                section.querySelectorAll('.faq-item').forEach(item => {
                    item.classList.remove('hidden');
                    item.style.opacity = '1';
                });
            });
        }
        
        function showNoResults(searchTerm) {
            // You could add a "no results" message here
            console.log(`No results found for: ${searchTerm}`);
        }
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });
        });
    });
</script>

<style>
    /* Custom animations */
    .faq-answer {
        animation: fadeIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .rotate-180 {
        transform: rotate(180deg);
    }
    
  
    
    /* Hover effects */
    .faq-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    /* Gradient text for emphasis */
    .gradient-text {
        background: linear-gradient(135deg, #8b5cf6, #6366f1, #3b82f6);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    
    /* Responsive adjustments */
    @media (max-width: 640px) {
        .sticky {
            position: static !important;
        }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>