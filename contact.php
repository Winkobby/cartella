<?php
// contact.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/settings_helper.php';

// Initialize database and settings
$database = new Database();
$pdo = $database->getConnection();
SettingsHelper::init($pdo);

// Get site settings
$settings = SettingsHelper::getAll($pdo);

// Set page variables
$page_title = 'Contact Us - ' . ($settings['site_name'] ?? 'Cartella');
$meta_description = 'Get in touch with us. We\'d love to hear from you!';
$contact_email = $settings['contact_email'] ?? 'cartella@gmail.com';

// Form submission is now handled via AJAX
// See ajax/contact.php for the implementation
?>

<?php require_once 'includes/header.php'; ?>

<div class="">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
        <div class="container mx-auto px-4 py-12 lg:py-16">
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-3xl lg:text-4xl font-bold mb-4">Contact Us</h1>
                <p class="text-lg lg:text-xl text-blue-100 mb-6">We're here to help and answer any questions you might have</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M14.243 5.757a6 6 0 10-.986 9.284 1 1 0 111.087 1.678A8 8 0 1118 10a3 3 0 01-4.8 2.401A4 4 0 1114 10a1 1 0 102 0c0-1.537-.586-3.07-1.757-4.243zM12 10a2 2 0 10-4 0 2 2 0 004 0z" clip-rule="evenodd" />
                        </svg>
                        <span><?php echo htmlspecialchars($contact_email); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto max-w-6xl px-2 py-8 lg:py-12">
        <div class="mx-auto">
            <!-- Success/Error Messages -->
            <div id="formMessages" class="mb-8 hidden"></div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 lg:gap-6">
                <!-- Contact Information -->
                <div class="lg:col-span-1">
                    <div class="sticky top-8">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 lg:p-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-6">Get in Touch</h2>

                            <div class="space-y-6">
                                <!-- Email -->
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 bg-blue-50 rounded-lg p-3">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900">Email Us</h3>
                                        <p class="text-gray-600 mt-1">We'll respond within 24 hours</p>
                                        <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"
                                            class="text-blue-600 hover:text-blue-800 font-medium mt-2 inline-block">
                                            <?php echo htmlspecialchars($contact_email); ?>
                                        </a>
                                    </div>
                                </div>

                                <!-- Business Hours -->
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 bg-green-50 rounded-lg p-3">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900 mb-3">Business Hours</h3>
                                        <div class="space-y-3">
                                            <!-- Monday - Friday -->
                                            <div class="flex items-start">
                                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-800">Monday - Friday</div>
                                                    <div class="text-sm text-gray-600 mt-1">9:00 AM - 6:00 PM</div>
                                                </div>
                                            </div>

                                            <!-- Saturday -->
                                            <div class="flex items-start">
                                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-800">Saturday</div>
                                                    <div class="text-sm text-gray-600 mt-1">10:00 AM - 4:00 PM</div>
                                                </div>
                                            </div>

                                            <!-- Sunday -->
                                            <div class="flex items-start">
                                                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-800">Sunday</div>
                                                    <div class="text-sm text-gray-600 mt-1">Closed</div>
                                                    <div class="text-xs text-gray-500 italic mt-1">Enjoy your weekend!</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ -->
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 bg-purple-50 rounded-lg p-3">
                                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900">FAQ</h3>
                                        <p class="text-gray-600 mt-1">Check our frequently asked questions</p>
                                        <a href="faq.php" class="text-purple-600 hover:text-purple-800 font-medium mt-2 inline-block">
                                            View FAQ →
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Social Links (Optional) -->
                            <div class="mt-8 pt-8 border-t border-gray-200">
                                <h3 class="font-medium text-gray-900 mb-4">Follow Us</h3>
                                <div class="flex gap-4">
                                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                        </svg>
                                    </a>
                                    <a href="#" class="text-gray-400 hover:text-pink-600 transition-colors">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                                        </svg>
                                    </a>
                                    <a href="#" class="text-gray-400 hover:text-blue-400 transition-colors">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.213c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 lg:p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Send us a message</h2>
                        <p class="text-gray-600 mb-8">Fill out the form below and we'll get back to you as soon as possible</p>

                        <form id="contactForm" class="space-y-6">
                            <!-- Name & Email -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                        id="name"
                                        name="name"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                        placeholder="John Doe">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email"
                                        id="email"
                                        name="email"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                        placeholder="john@example.com">
                                </div>
                            </div>

                            <!-- Phone & Subject -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Phone Number <span class="text-gray-500">(Optional)</span>
                                    </label>
                                    <input type="tel"
                                        id="phone"
                                        name="phone"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                        placeholder="+1234567890">
                                </div>

                                <div>
                                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                                        Subject <span class="text-red-500">*</span>
                                    </label>
                                    <select id="subject"
                                        name="subject"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                                        <option value="" disabled selected>Select a subject</option>
                                        <option value="General Inquiry">General Inquiry</option>
                                        <option value="Order Support">Order Support</option>
                                        <option value="Product Information">Product Information</option>
                                        <option value="Technical Support">Technical Support</option>
                                        <option value="Partnership">Partnership</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Message -->
                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                    Your Message <span class="text-red-500">*</span>
                                </label>
                                <textarea id="message"
                                    name="message"
                                    rows="6"
                                    required
                                    maxlength="5000"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors resize-none"
                                    placeholder="How can we help you?"></textarea>
                                <div class="mt-2 flex justify-between items-center text-sm text-gray-500">
                                    <span>Please provide as much detail as possible</span>
                                    <span id="charCount">0/5000 characters</span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <button type="submit"
                                    id="submitBtn"
                                    class="w-full md:w-auto px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                                    <span class="flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" id="submitIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span id="submitText">Send Message</span>
                                    </span>
                                </button>
                                <p class="text-xs text-gray-500 mt-3">
                                    By submitting this form, you agree to our
                                    <a href="privacy.php" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
                                    and
                                    <a href="terms.php" class="text-blue-600 hover:text-blue-800">Terms of Service</a>.
                                </p>
                            </div>
                        </form>
                    </div>

                    <!-- FAQ Preview -->
                    <div class="mt-8 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-100 p-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Quick Answers</h3>
                                <p class="text-gray-600 mb-4">Before contacting us, you might find answers to these common questions:</p>
                                <div class="space-y-3">
                                    <details class="bg-white rounded-lg border border-gray-200 p-4">
                                        <summary class="font-medium text-gray-900 cursor-pointer hover:text-blue-600">
                                            How long does shipping take?
                                        </summary>
                                        <p class="mt-2 text-gray-600">Standard shipping takes 3-5 business days. Express shipping is available for next-day delivery.</p>
                                    </details>
                                    <details class="bg-white rounded-lg border border-gray-200 p-4">
                                        <summary class="font-medium text-gray-900 cursor-pointer hover:text-blue-600">
                                            What is your return policy?
                                        </summary>
                                        <p class="mt-2 text-gray-600">We offer a 30-day return policy for unused items in original packaging. Visit our Returns page for more details.</p>
                                    </details>
                                </div>
                                <a href="faq.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium mt-4">
                                    View all FAQ
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Character counter for message textarea
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');

    if (messageTextarea && charCount) {
        function updateCharCount() {
            const length = messageTextarea.value.length;
            charCount.textContent = `${length}/5000 characters`;

            if (length > 4500) {
                charCount.classList.add('text-red-600', 'font-medium');
                charCount.classList.remove('text-gray-500');
            } else {
                charCount.classList.remove('text-red-600', 'font-medium');
                charCount.classList.add('text-gray-500');
            }
        }

        messageTextarea.addEventListener('input', updateCharCount);
        updateCharCount(); // Initialize count
    }

    // AJAX form submission
    const contactForm = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitIcon = document.getElementById('submitIcon');
    const formMessages = document.getElementById('formMessages');

    if (contactForm) {
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Disable submit button
            submitBtn.disabled = true;
            submitText.textContent = 'Sending...';
            submitIcon.innerHTML = '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>';
            submitIcon.classList.add('animate-spin');
            
            // Clear previous messages
            formMessages.innerHTML = '';
            formMessages.classList.add('hidden');
            
            // Get form data
            const formData = new FormData(contactForm);
            
            try {
                const response = await fetch('ajax/contact.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                // Reset button
                submitBtn.disabled = false;
                submitText.textContent = 'Send Message';
                submitIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />';
                submitIcon.classList.remove('animate-spin');
                
                if (data.success) {
                    // Show success message
                    formMessages.innerHTML = `
                        <div class="bg-green-50 border border-green-200 rounded-xl p-6 animate-fade-in">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-green-800">Message Sent Successfully! ✅</h3>
                                    <div class="mt-2 text-green-700">
                                        <p>${data.message}</p>
                                        <p class="mt-2 text-sm">We've sent a confirmation email to your inbox.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    formMessages.classList.remove('hidden');
                    
                    // Reset form
                    contactForm.reset();
                    updateCharCount();
                    
                    // Scroll to message
                    formMessages.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    // Show error message
                    formMessages.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-xl p-6 animate-fade-in">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-red-800">Error</h3>
                                    <div class="mt-2 text-red-700">
                                        ${data.message}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    formMessages.classList.remove('hidden');
                    formMessages.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } catch (error) {
                // Handle network error
                submitBtn.disabled = false;
                submitText.textContent = 'Send Message';
                submitIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />';
                submitIcon.classList.remove('animate-spin');
                
                formMessages.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-xl p-6 animate-fade-in">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-red-800">Network Error</h3>
                                <div class="mt-2 text-red-700">
                                    Unable to send your message. Please check your internet connection and try again.
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                formMessages.classList.remove('hidden');
                formMessages.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    // Add animation to FAQ details
    document.addEventListener('DOMContentLoaded', function() {
        const detailsElements = document.querySelectorAll('details');
        detailsElements.forEach(details => {
            details.addEventListener('toggle', function() {
                if (this.open) {
                    this.style.animation = 'slideDown 0.3s ease-out';
                }
            });
        });
    });

    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
`;
    document.head.appendChild(style);
</script>

<?php require_once 'includes/footer.php'; ?>