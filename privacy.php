<?php
// Privacy Policy page
$page_title = 'Privacy Policy';
$meta_description = 'Our Privacy Policy explains how we collect and use personal data.';

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/settings_helper.php';

$database = new Database();
$pdo = $database->getConnection();

// Try to read privacy/support emails from settings; fall back to defaults
try {
    SettingsHelper::init($pdo);
    $contact_email = SettingsHelper::get($pdo, 'contact_email', 'support@yourstore.com');
    $privacy_email = SettingsHelper::get($pdo, 'privacy_email', $contact_email);
    $store_name = SettingsHelper::get($pdo, 'store_name', defined('SITE_NAME') ? SITE_NAME : 'ShopHub');
    $last_updated = SettingsHelper::get($pdo, 'privacy_updated', date('F j, Y'));
} catch (Exception $e) {
    $contact_email = 'support@yourstore.com';
    $privacy_email = 'privacy@yourstore.com';
    $store_name = defined('SITE_NAME') ? SITE_NAME : 'Cartella';
    $last_updated = date('F j, Y');
}

require_once 'includes/header.php';
?>

<div class="min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Privacy Policy</h1>
            <p class="text-gray-600">Last updated: <?php echo htmlspecialchars($last_updated); ?></p>
            <div class="mt-4 inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Your Privacy Matters
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 sticky top-4 z-10">
            <div class="flex flex-wrap gap-2 justify-center">
                <a href="#introduction" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-full text-sm font-medium hover:bg-blue-100 transition-colors">
                    Introduction
                </a>
                <a href="#data-collection" class="inline-flex items-center px-3 py-1.5 bg-purple-50 text-purple-700 rounded-full text-sm font-medium hover:bg-purple-100 transition-colors">
                    Data Collection
                </a>
                <a href="#cookies" class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-700 rounded-full text-sm font-medium hover:bg-green-100 transition-colors">
                    Cookies
                </a>
                <a href="#security" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 rounded-full text-sm font-medium hover:bg-red-100 transition-colors">
                    Security
                </a>
                <a href="#rights" class="inline-flex items-center px-3 py-1.5 bg-yellow-50 text-yellow-700 rounded-full text-sm font-medium hover:bg-yellow-100 transition-colors">
                    Your Rights
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
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Introduction</h2>
                        <p class="text-gray-700">This Privacy Policy explains how <?php echo htmlspecialchars($store_name); ?> collects, uses, discloses, and protects your personal information when you use our website and services.</p>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Our Commitment
                    </h3>
                    <p class="text-gray-700 text-sm">We are committed to protecting your privacy and being transparent about our data practices. This policy outlines how we handle your personal information in compliance with applicable data protection laws.</p>
                </div>
            </div>

            <!-- Information We Collect -->
            <div id="data-collection" class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Information We Collect</h2>
                        <p class="text-gray-700">We collect different types of information to provide and improve our services.</p>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-5">
                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Personal Information
                        </h3>
                        <ul class="space-y-2 text-gray-700 text-sm">
                            <li class="flex items-start space-x-2">
                                <svg class="w-4 h-4 text-purple-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Name, email address, phone number</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <svg class="w-4 h-4 text-purple-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Shipping and billing addresses</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <svg class="w-4 h-4 text-purple-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Payment information (processed securely)</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-5">
                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                            Technical Information
                        </h3>
                        <ul class="space-y-2 text-gray-700 text-sm">
                            <li class="flex items-start space-x-2">
                                <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>IP address and device information</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Browser type and version</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Usage data and analytics</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- How We Use Information -->
            <div class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-green-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">How We Use Information</h2>
                        <p class="text-gray-700">We use your information for legitimate business purposes to provide you with the best possible service.</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="text-center p-3 bg-green-50 rounded-lg">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-800">Order Processing</span>
                    </div>
                    <div class="text-center p-3 bg-blue-50 rounded-lg">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-800">Customer Support</span>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded-lg">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-800">Service Improvement</span>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-lg">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-800">Fraud Prevention</span>
                    </div>
                </div>
            </div>

            <!-- Cookies & Tracking -->
            <div id="cookies" class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-emerald-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Cookies & Tracking</h2>
                        <p class="text-gray-700">We use cookies and similar technologies to enhance your browsing experience.</p>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-5">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-1">Cookie Usage</h3>
                            <p class="text-gray-700 text-sm">Essential cookies are required for site functionality. Analytics cookies help us improve our services. You can control cookie settings through your browser preferences.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Third-Party Services -->
            <div class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Third-Party Services</h2>
                        <p class="text-gray-700">We work with trusted partners to provide essential services.</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-start space-x-3 p-3 bg-yellow-50 rounded-lg">
                        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <div>
                            <h4 class="font-medium text-gray-900">Payment Processors</h4>
                            <p class="text-gray-700 text-sm">We share payment information with secure payment processors to complete transactions.</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3 p-3 bg-blue-50 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        <div>
                            <h4 class="font-medium text-gray-900">Shipping Partners</h4>
                            <p class="text-gray-700 text-sm">Shipping information is shared with delivery partners to fulfill orders.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Security -->
            <div id="security" class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-red-100 to-red-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Data Security</h2>
                        <p class="text-gray-700">We take data security seriously and implement industry-standard measures.</p>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-red-50 to-orange-50 rounded-xl p-5">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-1">Security Measures</h3>
                            <p class="text-gray-700 text-sm">We implement reasonable security measures including encryption, access controls, and regular security assessments. However, no system is completely secure. Please avoid sharing sensitive information in public areas of our site.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Your Rights -->
            <div id="rights" class="p-8 border-b border-gray-100">
                <div class="flex items-start space-x-4 mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-100 to-amber-200 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Your Rights</h2>
                        <p class="text-gray-700">You have rights regarding your personal data based on your jurisdiction.</p>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="border-l-4 border-yellow-500 pl-4 py-2">
                        <h3 class="font-semibold text-gray-900 mb-1">Access & Correction</h3>
                        <p class="text-gray-700 text-sm">You can request access to or correction of your personal data.</p>
                    </div>
                    <div class="border-l-4 border-red-500 pl-4 py-2">
                        <h3 class="font-semibold text-gray-900 mb-1">Deletion</h3>
                        <p class="text-gray-700 text-sm">You may request deletion of your personal data under certain conditions.</p>
                    </div>
                </div>
            </div>

            <!-- Data Retention & Children -->
            <div class="p-8">
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-5">
                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Data Retention
                        </h3>
                        <p class="text-gray-700 text-sm">We retain personal data as necessary for business operations, legal obligations, and as described in this policy.</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-pink-50 to-rose-50 rounded-xl p-5">
                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 text-pink-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Children's Privacy
                        </h3>
                        <p class="text-gray-700 text-sm">Our services are not intended for children under 13. We do not knowingly collect personal information from children under 13.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div id="contact" class="mt-8 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-8 text-white">
            <div class="text-center">
                <h2 class="text-2xl font-bold mb-4">Questions About Your Privacy?</h2>
                <p class="text-blue-100 mb-6">We're here to help you understand our privacy practices and exercise your rights.</p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="mailto:<?php echo htmlspecialchars($privacy_email); ?>" 
                       class="bg-white text-blue-600 hover:bg-blue-50 px-6 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg inline-flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>Email Privacy Team</span>
                    </a>
                    
                    <a href="contact.php" 
                       class="bg-transparent border-2 border-white hover:bg-white hover:text-blue-600 px-6 py-3 rounded-lg font-semibold transition-all duration-300 inline-flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <span>Contact Form</span>
                    </a>
                </div>
                
                <div class="mt-6 pt-6 border-t border-blue-500">
                    <p class="text-blue-200 text-sm">
                        <strong>Privacy Email:</strong> 
                        <a href="mailto:<?php echo htmlspecialchars($privacy_email); ?>" class="underline hover:text-white">
                            <?php echo htmlspecialchars($privacy_email); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Policy Updates -->
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-gray-900">Policy Updates</h4>
                    <p class="text-gray-700 text-sm">We may update this Privacy Policy. Changes will be posted on this page with an updated effective date. We encourage you to review this policy periodically.</p>
                </div>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="mt-8 text-center">
            <div class="inline-flex space-x-4">
                <a href="terms.php" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">
                    Terms & Conditions
                </a>
                <span class="text-gray-400">•</span>
                <a href="shipping-policy.php" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">
                    Shipping Policy
                </a>
                <!-- <span class="text-gray-400">•</span>
                <a href="cookies.php" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">
                    Cookie Policy
                </a> -->
                <span class="text-gray-400">•</span>
                <a href="index.php" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">
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
            link.classList.remove('bg-white', 'text-blue-600');
            if(link.getAttribute('href') === `#${currentSection}`) {
                link.classList.add('bg-white', 'text-blue-600');
            }
        });
    });
</script>

<style>
      
    /* Gradient text */
    .gradient-text {
        background: linear-gradient(135deg, #3b82f6, #6366f1, #8b5cf6);
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
    
    /* Privacy-focused animations */
    @keyframes fadeInPrivacy {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .privacy-section {
        animation: fadeInPrivacy 0.6s ease-out;
    }
</style>

<?php require_once 'includes/footer.php'; ?>