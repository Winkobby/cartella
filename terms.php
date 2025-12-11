<?php
// Terms & Conditions page
$page_title = 'Terms & Conditions';
$meta_description = 'Terms and conditions for using this site.';

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/settings_helper.php';

$database = new Database();
$pdo = $database->getConnection();

// Try to read contact email from settings; fallback to support@yourstore.com
try {
    SettingsHelper::init($pdo);
    $contact_email = SettingsHelper::get($pdo, 'contact_email', 'support@yourstore.com');
    $store_name = SettingsHelper::get($pdo, 'store_name', 'Cartella');
    $last_updated = SettingsHelper::get($pdo, 'terms_updated', date('F j, Y'));
} catch (Exception $e) {
    $contact_email = 'support@yourstore.com';
    $store_name = 'Cartella';
    $last_updated = date('F j, Y');
}

require_once 'includes/header.php';
?>

<div class="min-h-screen py-8">
    <div class="container mx-auto px-2 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Terms & Conditions</h1>
            <p class="text-gray-600">Last updated: <?php echo htmlspecialchars($last_updated); ?></p>
        </div>

        <!-- Navigation Menu -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 sticky top-4 z-10">
            <div class="flex flex-wrap gap-2 justify-center">
                <a href="#introduction" class="inline-flex items-center px-3 py-1.5 bg-purple-50 text-purple-700 rounded-full text-sm font-medium hover:bg-purple-100 transition-colors">
                    Introduction
                </a>
                <a href="#orders" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-full text-sm font-medium hover:bg-blue-100 transition-colors">
                    Orders & Pricing
                </a>
                <a href="#payment" class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-700 rounded-full text-sm font-medium hover:bg-green-100 transition-colors">
                    Payment & Taxes
                </a>
                <a href="#shipping" class="inline-flex items-center px-3 py-1.5 bg-yellow-50 text-yellow-700 rounded-full text-sm font-medium hover:bg-yellow-100 transition-colors">
                    Shipping & Returns
                </a>
                <a href="#conduct" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 rounded-full text-sm font-medium hover:bg-red-100 transition-colors">
                    User Conduct
                </a>
                <a href="#contact" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-full text-sm font-medium hover:bg-indigo-100 transition-colors">
                    Contact
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Introduction -->
            <div id="introduction" class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Welcome to <?php echo htmlspecialchars($store_name); ?></h2>
                        <p class="text-gray-700">These Terms & Conditions ("Terms") govern your access to and use of our website. By accessing or using the site, you agree to be bound by these Terms. Please read them carefully.</p>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Important Information
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <div class="flex items-start space-x-2">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <span class="font-medium text-gray-800">Age Requirement</span>
                                <p class="text-gray-600">You must be at least 15 years old to place orders</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-2">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <span class="font-medium text-gray-800">Account Information</span>
                                <p class="text-gray-600">Provide accurate and complete information</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders & Pricing -->
            <div id="orders" class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Orders & Pricing</h2>
                        <p class="text-gray-700">All orders are subject to availability and our acceptance. We reserve the right to refuse or cancel orders under certain circumstances.</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-white border border-gray-200 rounded-xl p-5">
                        <h3 class="font-semibold text-gray-900 mb-2">Pricing Policy</h3>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start space-x-2">
                                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Prices are displayed in local currency and may change without notice</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>In case of pricing errors, we reserve the right to refuse or cancel orders</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>All taxes and fees will be clearly displayed during checkout</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Payment & Taxes -->
            <div id="payment" class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-green-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Payment & Taxes</h2>
                        <p class="text-gray-700">Secure payment processing and transparent tax calculation for all transactions.</p>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5">
                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            Payment Processing
                        </h3>
                        <p class="text-gray-700 text-sm">Payment processing is handled by trusted third-party providers. You agree to comply with their terms and conditions.</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-5">
                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Taxes
                        </h3>
                        <p class="text-gray-700 text-sm">All applicable taxes will be calculated and displayed during checkout. Tax rates are based on your shipping destination.</p>
                    </div>
                </div>
            </div>

            <!-- Shipping & Returns -->
            <div id="shipping" class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Shipping & Returns</h2>
                        <p class="text-gray-700">Comprehensive shipping and return policies to ensure a smooth shopping experience.</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="border-l-4 border-yellow-500 pl-4 py-2">
                        <h3 class="font-semibold text-gray-900 mb-1">Shipping Policy</h3>
                        <p class="text-gray-700 text-sm">Delivery times, shipping costs, and policies are detailed on our Shipping & Returns page. Please review before ordering.</p>
                    </div>
                    
                    <div class="border-l-4 border-green-500 pl-4 py-2">
                        <h3 class="font-semibold text-gray-900 mb-1">Return Policy</h3>
                        <p class="text-gray-700 text-sm">We offer hassle-free returns within our specified return period. Conditions and procedures are available on our Returns page.</p>
                    </div>
                </div>
            </div>

            <!-- Intellectual Property -->
            <div class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-pink-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Intellectual Property</h2>
                        <p class="text-gray-700">All content on this site is protected by intellectual property laws.</p>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-900 mb-2">Content Protection</h3>
                    <p class="text-gray-700 text-sm">All text, graphics, logos, images, and software are the property of <?php echo htmlspecialchars($store_name); ?> or used with permission. Unauthorized copying, reproduction, or distribution of site content is prohibited.</p>
                </div>
            </div>

            <!-- User Conduct -->
            <div id="conduct" class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-red-100 to-orange-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">User Conduct</h2>
                        <p class="text-gray-700">To maintain a safe and respectful environment for all users.</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-start space-x-3 p-3 bg-red-50 rounded-lg">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                        <div>
                            <h4 class="font-medium text-gray-900">Prohibited Activities</h4>
                            <p class="text-gray-700 text-sm">Impersonation, posting unlawful content, unauthorized system access, or interfering with site operations.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Limitation of Liability -->
            <div class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Limitation of Liability</h2>
                        <p class="text-gray-700">To the fullest extent permitted by law, we are not liable for indirect, incidental, special or consequential damages arising from your use of the site or products purchased.</p>
                    </div>
                </div>
            </div>

            <!-- Governing Law & Changes -->
            <div class="p-8">
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl p-5">
                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                            </svg>
                            Governing Law
                        </h3>
                        <p class="text-gray-700 text-sm">These Terms are governed by the laws of the country where this business is based.</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-5">
                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Changes to Terms
                        </h3>
                        <p class="text-gray-700 text-sm">We may modify these Terms. Changes are effective when posted. Continued use indicates acceptance.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div id="contact" class="mt-8 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-8 text-white">
            <div class="text-center">
                <h2 class="text-2xl font-bold mb-4">Need Help Understanding Our Terms?</h2>
                <p class="text-purple-100 mb-6">If you have any questions about these Terms and Conditions, please don't hesitate to contact us.</p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" 
                       class="bg-white text-purple-600 hover:bg-purple-50 px-6 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg inline-flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>Email Us</span>
                    </a>
                    
                    <a href="contact.php" 
                       class="bg-transparent border-2 border-white hover:bg-white hover:text-purple-600 px-6 py-3 rounded-lg font-semibold transition-all duration-300 inline-flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <span>Contact Form</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="mt-8 text-center">
            <div class="inline-flex space-x-4">
                <a href="privacy.php" class="text-sm text-gray-600 hover:text-purple-600 transition-colors">
                    Privacy Policy
                </a>
                <span class="text-gray-400">•</span>
                <a href="shipping-policy.php" class="text-sm text-gray-600 hover:text-purple-600 transition-colors">
                    Shipping Policy
                </a>
                <span class="text-gray-400">•</span>
                <a href="returns.php" class="text-sm text-gray-600 hover:text-purple-600 transition-colors">
                    Return Policy
                </a>
                <span class="text-gray-400">•</span>
                <a href="index.php" class="text-sm text-gray-600 hover:text-purple-600 transition-colors">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Smooth Scroll Script -->
<script>
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
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

    // Highlight current section in navigation
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('div[id]');
        const navLinks = document.querySelectorAll('.sticky a');
        
        let currentSection = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if(window.scrollY >= (sectionTop - 150)) {
                currentSection = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('bg-white', 'text-purple-600');
            if(link.getAttribute('href') === `#${currentSection}`) {
                link.classList.add('bg-white', 'text-purple-600');
            }
        });
    });
</script>

<style>
       
    /* Gradient text */
    .gradient-text {
        background: linear-gradient(135deg, #8b5cf6, #6366f1, #3b82f6);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    
    /* Hover effects */
    .hover-lift {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
</style>

<?php require_once 'includes/footer.php'; ?>