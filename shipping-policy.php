<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Set page metadata
$page_title = 'Shipping Policy - ' . APP_NAME;
$meta_description = 'Learn about our shipping methods, delivery times, costs, and tracking information. We deliver nationwide in Ghana.';
?>

<?php require_once 'includes/header.php'; ?>

<!-- Breadcrumb -->
<div class="container mx-auto px-2 max-w-6xl py-8 md:py-2 lg:py-2 pb-2">
    <nav class="flex text-xs">
        <a href="index.php" class="text-purple-600 hover:text-purple-700">Home</a>
        <span class="mx-2 text-gray-400">></span>
        <a href="help.php" class="text-purple-600 hover:text-purple-700">Help Center</a>
        <span class="mx-2 text-gray-400">></span>
        <span class="text-gray-600">Shipping Policy</span>
    </nav>
</div>

<!-- Main Content -->
<section class="py-4">
    <div class="container mx-auto px-2 max-w-6xl">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Left Sidebar (Table of Contents) -->
            <div class="lg:w-1/4">
                <div class="lg:sticky lg:top-6 bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">On This Page</h3>
                    <nav class="space-y-2">
                        <a href="#overview" class="block text-sm text-gray-600 hover:text-purple-600 hover:font-medium py-2 border-l-4 border-transparent hover:border-purple-500 pl-3 transition-all">
                            Overview
                        </a>
                        <a href="#delivery-areas" class="block text-sm text-gray-600 hover:text-purple-600 hover:font-medium py-2 border-l-4 border-transparent hover:border-purple-500 pl-3 transition-all">
                            Delivery Areas
                        </a>
                        <a href="#shipping-methods" class="block text-sm text-gray-600 hover:text-purple-600 hover:font-medium py-2 border-l-4 border-transparent hover:border-purple-500 pl-3 transition-all">
                            Shipping Methods
                        </a>
                        <a href="#delivery-times" class="block text-sm text-gray-600 hover:text-purple-600 hover:font-medium py-2 border-l-4 border-transparent hover:border-purple-500 pl-3 transition-all">
                            Delivery Times
                        </a>
                        <a href="#shipping-costs" class="block text-sm text-gray-600 hover:text-purple-600 hover:font-medium py-2 border-l-4 border-transparent hover:border-purple-500 pl-3 transition-all">
                            Shipping Costs
                        </a>
                        <a href="#order-tracking" class="block text-sm text-gray-600 hover:text-purple-600 hover:font-medium py-2 border-l-4 border-transparent hover:border-purple-500 pl-3 transition-all">
                            Order Tracking
                        </a>
                        <a href="#failed-deliveries" class="block text-sm text-gray-600 hover:text-purple-600 hover:font-medium py-2 border-l-4 border-transparent hover:border-purple-500 pl-3 transition-all">
                            Failed Deliveries
                        </a>
                        <a href="#contact-info" class="block text-sm text-gray-600 hover:text-purple-600 hover:font-medium py-2 border-l-4 border-transparent hover:border-purple-500 pl-3 transition-all">
                            Contact Information
                        </a>
                    </nav>

                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Need Help?</h4>
                        <div class="space-y-3">
                            <a href="contact.php" class="flex items-center text-sm text-purple-600 hover:text-purple-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Contact Support
                            </a>
                            <a href="faq.php" class="flex items-center text-sm text-purple-600 hover:text-purple-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                View FAQs
                            </a>
                            <a href="return-policy.php" class="flex items-center text-sm text-purple-600 hover:text-purple-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                                </svg>
                                Return Policy
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:w-3/4">
                <!-- Header -->
                <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-2xl p-8 mb-8 text-white">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-3xl lg:text-4xl font-bold mb-4">Shipping Policy</h1>
                            <p class="text-lg text-purple-100 max-w-3xl">
                                Fast, reliable delivery across Ghana with multiple shipping options to suit your needs
                            </p>
                        </div>
                        <div class="hidden lg:block">
                            <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-xl p-4">
                                <div class="text-center">
                                    <div class="text-3xl font-bold">98%</div>
                                    <div class="text-sm opacity-90">On-time Delivery</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <span class="text-lg">🚚</span>
                            </div>
                            <div>
                                <div class="font-semibold">Nationwide</div>
                                <div class="text-sm opacity-90">Delivery</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <span class="text-lg">⚡</span>
                            </div>
                            <div>
                                <div class="font-semibold">2-5 Days</div>
                                <div class="text-sm opacity-90">Delivery Time</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <span class="text-lg">🎯</span>
                            </div>
                            <div>
                                <div class="font-semibold">Real-time</div>
                                <div class="text-sm opacity-90">Tracking</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <span class="text-lg">🔄</span>
                            </div>
                            <div>
                                <div class="font-semibold">15 Days</div>
                                <div class="text-sm opacity-90">Easy Returns</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Policy Content -->
                <div class="space-y-8">
                    <!-- Overview Section -->
                    <section id="overview" class="scroll-mt-20">
                        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-blue-100 to-blue-50 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-800">Overview</h2>
                            </div>
                            <div class="prose max-w-none">
                                <p class="text-gray-600 mb-4">
                                    At <?php echo APP_NAME; ?>, we are committed to providing fast, reliable, and affordable shipping services across Ghana. Our shipping policy outlines the procedures, costs, and timelines associated with delivering your orders.
                                </p>
                                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 my-4">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-blue-800 text-sm">
                                            <strong>Important:</strong> All orders are processed within 24-48 hours after payment confirmation. Shipping times are calculated from the date of dispatch, not from the order date.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Delivery Areas Section -->
                    <section id="delivery-areas" class="scroll-mt-20">
                        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-green-100 to-green-50 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-800">Delivery Areas</h2>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6 mb-6">
                                <div class="bg-green-50 rounded-xl p-5">
                                    <h3 class="font-bold text-lg text-gray-800 mb-3 flex items-center">
                                        <span class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center mr-2">✓</span>
                                        Covered Areas
                                    </h3>
                                    <ul class="space-y-2">
                                        <li class="flex items-center text-gray-700">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Greater Accra Region
                                        </li>
                                        <li class="flex items-center text-gray-700">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Ashanti Region
                                        </li>
                                        <li class="flex items-center text-gray-700">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Western Region
                                        </li>
                                        <li class="flex items-center text-gray-700">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Eastern Region
                                        </li>
                                        <li class="flex items-center text-gray-700">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Central Region
                                        </li>
                                        <li class="flex items-center text-gray-700">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Volta Region
                                        </li>
                                    </ul>
                                </div>

                                <div class="bg-yellow-50 rounded-xl p-5">
                                    <h3 class="font-bold text-lg text-gray-800 mb-3 flex items-center">
                                        <span class="w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center mr-2">⏱️</span>
                                        Extended Delivery Areas
                                    </h3>
                                    <ul class="space-y-2">
                                        <li class="flex items-center text-gray-700">
                                            <svg class="w-4 h-4 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Northern Region (5-7 days)
                                        </li>
                                        <li class="flex items-center text-gray-700">
                                            <svg class="w-4 h-4 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Upper East Region (5-7 days)
                                        </li>
                                        <li class="flex items-center text-gray-700">
                                            <svg class="w-4 h-4 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Upper West Region (5-7 days)
                                        </li>
                                        <li class="flex items-center text-gray-700">
                                            <svg class="w-4 h-4 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Brong Ahafo Region (4-6 days)
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="text-gray-700 text-sm">
                                    <strong>Note:</strong> Delivery to remote areas may take longer than estimated. We recommend providing accurate and complete addresses with landmarks to ensure smooth delivery.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Shipping Methods Section -->
                    <section id="shipping-methods" class="scroll-mt-20">
                        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-purple-100 to-purple-50 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-800">Shipping Methods</h2>
                            </div>

                            <div class="grid md:grid-cols-3 gap-4 mb-6">
                                <div class="border border-gray-200 rounded-xl p-5 hover:border-purple-300 hover:shadow-md transition-all duration-300">
                                    <div class="text-center mb-4">
                                        <div class="w-16 h-16 bg-gradient-to-r from-blue-100 to-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-2xl">🚚</span>
                                        </div>
                                        <h3 class="font-bold text-lg text-gray-800">Standard Shipping</h3>
                                        <div class="text-sm text-gray-600 mt-1">Most Popular</div>
                                    </div>
                                    <ul class="space-y-2">
                                        <li class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            2-5 business days
                                        </li>
                                        <li class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Door-to-door delivery
                                        </li>
                                        <li class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Real-time tracking
                                        </li>
                                    </ul>
                                </div>

                                <div class="border border-gray-200 rounded-xl p-5 hover:border-orange-300 hover:shadow-md transition-all duration-300">
                                    <div class="text-center mb-4">
                                        <div class="w-16 h-16 bg-gradient-to-r from-orange-100 to-orange-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-2xl">⚡</span>
                                        </div>
                                        <h3 class="font-bold text-lg text-gray-800">Express Shipping</h3>
                                        <div class="text-sm text-gray-600 mt-1">Faster Delivery</div>
                                    </div>
                                    <ul class="space-y-2">
                                        <li class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            1-2 business days
                                        </li>
                                        <li class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Priority processing
                                        </li>
                                        <li class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Same-day in Accra
                                        </li>
                                    </ul>
                                </div>

                                <div class="border border-gray-200 rounded-xl p-5 hover:border-green-300 hover:shadow-md transition-all duration-300">
                                    <div class="text-center mb-4">
                                        <div class="w-16 h-16 bg-gradient-to-r from-green-100 to-green-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-2xl">🏪</span>
                                        </div>
                                        <h3 class="font-bold text-lg text-gray-800">Pickup Stations</h3>
                                        <div class="text-sm text-gray-600 mt-1">Save on Shipping</div>
                                    </div>
                                    <ul class="space-y-2">
                                        <li class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            2-4 business days
                                        </li>
                                        <li class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            50+ locations nationwide
                                        </li>
                                        <li class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            7-day pickup window
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Delivery Times Section -->
                    <section id="delivery-times" class="scroll-mt-20">
                        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-yellow-100 to-yellow-50 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-800">Delivery Times</h2>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full min-w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 border-b">Region</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 border-b">Standard Shipping</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 border-b">Express Shipping</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 border-b">Working Days</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">Greater Accra</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">1-2 days</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">Same day</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">Mon - Sat</td>
                                        </tr>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">Ashanti</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">2-3 days</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">1-2 days</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">Mon - Fri</td>
                                        </tr>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">Western/Eastern/Central</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">3-4 days</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">2 days</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">Mon - Fri</td>
                                        </tr>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">Other Regions</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">4-7 days</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">3-5 days</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">Mon - Fri</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 bg-yellow-50 border border-yellow-100 rounded-lg p-4">
                                <div class="flex">
                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.276 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-1">Important Notes:</h4>
                                        <ul class="text-sm text-gray-700 space-y-1">
                                            <li>• Delivery times are estimates and may vary due to weather, traffic, or other unforeseen circumstances</li>
                                            <li>• Orders placed on weekends or public holidays will be processed the next business day</li>
                                            <li>• During peak seasons (Christmas, Valentine's, etc.), delivery times may be extended by 1-2 days</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Shipping Costs Section -->
                    <section id="shipping-costs" class="scroll-mt-20">
                        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-red-100 to-red-50 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-800">Shipping Costs</h2>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6 mb-6">
                                <div class="space-y-4">
                                    <div class="bg-gray-50 rounded-xl p-5">
                                        <h3 class="font-bold text-lg text-gray-800 mb-3">Free Shipping</h3>
                                        <div class="flex items-baseline mb-3">
                                            <span class="text-3xl font-bold text-green-600">GHS 0</span>
                                            <span class="text-gray-600 ml-2">on eligible orders</span>
                                        </div>
                                        <ul class="space-y-2">
                                            <li class="flex items-center text-sm text-gray-600">
                                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Orders above GHS 500
                                            </li>
                                            <li class="flex items-center text-sm text-gray-600">
                                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Standard shipping only
                                            </li>
                                            <li class="flex items-center text-sm text-gray-600">
                                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Nationwide coverage
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="bg-blue-50 rounded-xl p-5">
                                        <h3 class="font-bold text-lg text-gray-800 mb-3">Standard Rates</h3>
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-700">Greater Accra</span>
                                                <span class="font-bold text-blue-600">GHS 15-25</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-700">Ashanti Region</span>
                                                <span class="font-bold text-blue-600">GHS 20-35</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-700">Other Regions</span>
                                                <span class="font-bold text-blue-600">GHS 25-50</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-orange-50 rounded-xl p-5">
                                        <h3 class="font-bold text-lg text-gray-800 mb-3">Express Shipping</h3>
                                        <div class="flex items-baseline mb-2">
                                            <span class="text-xl font-bold text-yellow-600">+50%</span>
                                            <span class="text-gray-600 ml-2">added to standard rates</span>
                                        </div>
                                        <p class="text-sm text-gray-600">Minimum: GHS 30</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-purple-50 border border-purple-100 rounded-xl p-4">
                                <div class="flex">
                                    <svg class="w-5 h-5 text-purple-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-1">Shipping Cost Calculator</h4>
                                        <p class="text-sm text-gray-700">
                                            Exact shipping costs are calculated at checkout based on your location, package weight, and shipping method. You can see the exact shipping cost before completing your purchase.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Order Tracking Section -->
                    <section id="order-tracking" class="scroll-mt-20">
                        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-indigo-100 to-indigo-50 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-800">Order Tracking</h2>
                            </div>

                            <div class="space-y-6">
                                <div class="grid md:grid-cols-3 gap-4">
                                    <div class="bg-indigo-50 rounded-xl p-5 text-center">
                                        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-xl">📧</span>
                                        </div>
                                        <h4 class="font-bold text-gray-800 mb-2">Email Notification</h4>
                                        <p class="text-sm text-gray-600">
                                            Receive tracking link via email once order is dispatched
                                        </p>
                                    </div>

                                    <div class="bg-indigo-50 rounded-xl p-5 text-center">
                                        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-xl">📱</span>
                                        </div>
                                        <h4 class="font-bold text-gray-800 mb-2">SMS Updates</h4>
                                        <p class="text-sm text-gray-600">
                                            Get SMS notifications for important delivery milestones
                                        </p>
                                    </div>

                                    <div class="bg-indigo-50 rounded-xl p-5 text-center">
                                        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-xl">🔍</span>
                                        </div>
                                        <h4 class="font-bold text-gray-800 mb-2">Online Tracking</h4>
                                        <p class="text-sm text-gray-600">
                                            Track your order in real-time on our website
                                        </p>
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-xl p-5">
                                    <h4 class="font-bold text-lg text-gray-800 mb-3">How to Track Your Order</h4>
                                    <ol class="space-y-4">
                                        <li class="flex items-start">
                                            <span class="flex-shrink-0 w-8 h-8 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mr-3 font-bold">1</span>
                                            <div>
                                                <h5 class="font-semibold text-gray-800 mb-1">Check Your Email</h5>
                                                <p class="text-sm text-gray-600">Look for the shipping confirmation email containing your tracking number and link</p>
                                            </div>
                                        </li>
                                        <li class="flex items-start">
                                            <span class="flex-shrink-0 w-8 h-8 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mr-3 font-bold">2</span>
                                            <div>
                                                <h5 class="font-semibold text-gray-800 mb-1">Visit Your Account</h5>
                                                <p class="text-sm text-gray-600">Go to "My Orders" in your account dashboard and click "Track Order"</p>
                                            </div>
                                        </li>
                                        <li class="flex items-start">
                                            <span class="flex-shrink-0 w-8 h-8 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mr-3 font-bold">3</span>
                                            <div>
                                                <h5 class="font-semibold text-gray-800 mb-1">Use Tracking Number</h5>
                                                <p class="text-sm text-gray-600">Enter your tracking number on our tracking page for real-time updates</p>
                                            </div>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Failed Deliveries Section -->
                    <section id="failed-deliveries" class="scroll-mt-20">
                        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-gray-100 to-gray-50 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-800">Failed Deliveries</h2>
                            </div>

                            <div class="space-y-6">
                                <div class="bg-red-50 border border-red-100 rounded-xl p-5">
                                    <h4 class="font-bold text-lg text-gray-800 mb-3 flex items-center">
                                        <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.276 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                        What Happens When Delivery Fails?
                                    </h4>
                                    <div class="space-y-3">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-6 h-6 bg-red-100 text-red-600 rounded-full flex items-center justify-center mr-3 mt-0.5">1</div>
                                            <p class="text-sm text-gray-700">Delivery agent will attempt to contact you 3 times</p>
                                        </div>
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-6 h-6 bg-red-100 text-red-600 rounded-full flex items-center justify-center mr-3 mt-0.5">2</div>
                                            <p class="text-sm text-gray-700">Package will be held at nearest pickup station for 5 days</p>
                                        </div>
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-6 h-6 bg-red-100 text-red-600 rounded-full flex items-center justify-center mr-3 mt-0.5">3</div>
                                            <p class="text-sm text-gray-700">After 5 days, order will be returned and refund processed</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-5">
                                    <h4 class="font-bold text-lg text-gray-800 mb-3">Re-delivery Options</h4>
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div>
                                            <h5 class="font-semibold text-gray-800 mb-2">Free Re-delivery</h5>
                                            <p class="text-sm text-gray-600">If delivery failed due to our error, we'll re-deliver at no extra cost</p>
                                        </div>
                                        <div>
                                            <h5 class="font-semibold text-gray-800 mb-2">Pickup Station</h5>
                                            <p class="text-sm text-gray-600">Collect your package from nearest pickup station within 5 days</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Contact Information Section -->
                    <section id="contact-info" class="scroll-mt-20">
                        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-blue-100 to-blue-50 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-800">Contact Information</h2>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-800">Email Support</h4>
                                            <p class="text-gray-600">support@<?php echo strtolower(APP_NAME); ?>.com</p>
                                            <p class="text-sm text-gray-500 mt-1">Response time: 24 hours</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-800">Phone Support</h4>
                                            <p class="text-gray-600">+233 24 131 1105</p>
                                            <p class="text-sm text-gray-500 mt-1">Mon-Fri: 8AM-6PM, Sat: 9AM-4PM</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-800">Visit Us</h4>
                                            <p class="text-gray-600">Accra, Ghana</p>
                                            <p class="text-sm text-gray-500 mt-1">By appointment only</p>
                                        </div>
                                    </div>

                                    <!-- <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-800">Live Chat</h4>
                                            <p class="text-gray-600">Available on website</p>
                                            <p class="text-sm text-gray-500 mt-1">24/7 automated, human: 8AM-10PM</p>
                                        </div>
                                    </div> -->
                                </div>
                            </div>

                            <div class="mt-6 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-5 border border-blue-100">
                                <h4 class="font-bold text-lg text-gray-800 mb-2">Need Immediate Assistance?</h4>
                                <p class="text-gray-700 mb-4">
                                    For urgent delivery issues, please call our delivery hotline or use the live chat feature on our website.
                                </p>
                                <a href="contact.php" class="inline-flex items-center bg-blue-600 text-white px-5 py-2.5 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    Contact Support Now
                                </a>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-8 bg-gray-50">
    <div class="container mx-auto px-2 max-w-4xl">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-gray-800 mb-3">Frequently Asked Questions</h2>
            <p class="text-gray-600">Quick answers to common shipping questions</p>
        </div>

        <div class="space-y-4">
            <!-- FAQ Item 1 -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <button class="faq-question w-full text-left px-6 py-4 flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-800">How long does it take to process my order?</span>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="faq-answer px-6 pb-4 hidden">
                    <p class="text-gray-600">Orders are typically processed within 24-48 hours after payment confirmation. During weekends and holidays, processing may take an additional day. You'll receive an email notification once your order has been shipped.</p>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <button class="faq-question w-full text-left px-6 py-4 flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-800">Can I change my shipping address after placing an order?</span>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="faq-answer px-6 pb-4 hidden">
                    <p class="text-gray-600">Yes, you can change your shipping address if your order hasn't been shipped yet. Please contact our customer support team immediately. If the order has already been shipped, we cannot change the delivery address, but you may be able to redirect it through the carrier for an additional fee.</p>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <button class="faq-question w-full text-left px-6 py-4 flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-800">Do you deliver on weekends?</span>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="faq-answer px-6 pb-4 hidden">
                    <p class="text-gray-600">Yes, we deliver on Saturdays in major cities like Accra and Kumasi. Sunday deliveries are not available. Express shipping options may include Saturday delivery at an additional cost.</p>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <button class="faq-question w-full text-left px-6 py-4 flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-800">What if I'm not home when delivery is attempted?</span>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="faq-answer px-6 pb-4 hidden">
                    <p class="text-gray-600">Our delivery agent will attempt to contact you 3 times. If unsuccessful, the package will be taken to the nearest pickup station where you can collect it within 5 business days. You'll receive an SMS with pickup location details.</p>
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <button class="faq-question w-full text-left px-6 py-4 flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-800">How can I track my international order?</span>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="faq-answer px-6 pb-4 hidden">
                    <p class="text-gray-600">International orders are tracked through our partnered international carriers. You'll receive a tracking number via email once your order leaves Ghana. You can use this tracking number on the carrier's website or through our tracking portal.</p>
                </div>
            </div>
        </div>

        <div class="text-center mt-8">
            <a href="faq.php" class="inline-flex items-center text-purple-600 hover:text-purple-700 font-medium">
                View All FAQs
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</section>

<script>
    // Smooth scroll for table of contents
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });

    // FAQ Accordion
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', () => {
            const answer = question.nextElementSibling;
            const icon = question.querySelector('svg');

            // Toggle current answer
            answer.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');

            // Close other answers
            document.querySelectorAll('.faq-question').forEach(otherQuestion => {
                if (otherQuestion !== question) {
                    const otherAnswer = otherQuestion.nextElementSibling;
                    const otherIcon = otherQuestion.querySelector('svg');
                    otherAnswer.classList.add('hidden');
                    otherIcon.classList.remove('rotate-180');
                }
            });
        });
    });
</script>

<style>
    /* Custom sticky sidebar with boundaries */
    .sticky-sidebar-container {
        position: relative;
        height: fit-content;
    }

    .sticky-sidebar {
        position: sticky;
        top: 24px;
        max-height: calc(100vh - 48px);
        overflow-y: auto;
    }

    /* Custom scrollbar for sidebar */
    .sticky-sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .sticky-sidebar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .sticky-sidebar::-webkit-scrollbar-thumb {
        background: #c4b5fd;
        border-radius: 4px;
    }

    .sticky-sidebar::-webkit-scrollbar-thumb:hover {
        background: #a78bfa;
    }

    /* Hide scrollbar on mobile */
    @media (max-width: 1024px) {
        .sticky-sidebar {
            position: relative;
            top: auto;
            max-height: none;
            overflow-y: visible;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>