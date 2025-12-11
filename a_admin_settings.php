<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/settings_helper.php';

// Admin only
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    header('Location: signin.php');
    exit;
}

$database = new Database();
$pdo = $database->getConnection();
SettingsHelper::init($pdo);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // Collect and sanitize
    $site_name = trim($_POST['site_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';

    $shipping_enabled = isset($_POST['shipping_enabled']) ? '1' : '0';
    $shipping_cost = $_POST['shipping_cost'] !== '' ? floatval($_POST['shipping_cost']) : '0';
    $free_shipping_threshold = $_POST['free_shipping_threshold'] !== '' ? floatval($_POST['free_shipping_threshold']) : '0';

    $rating_enabled = isset($_POST['rating_enabled']) ? '1' : '0';
    $rating_threshold = isset($_POST['rating_threshold']) ? intval($_POST['rating_threshold']) : 0;

    // SMTP settings
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = intval($_POST['smtp_port'] ?? 587);
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = trim($_POST['smtp_pass'] ?? '');
    $smtp_from = trim($_POST['smtp_from'] ?? $contact_email);

    // Maintenance schedule (optional)
    $maintenance_start = trim($_POST['maintenance_start'] ?? '');
    $maintenance_end = trim($_POST['maintenance_end'] ?? '');

    // Store
    SettingsHelper::set($pdo, 'site_name', $site_name, 'string', 'Site title');
    SettingsHelper::set($pdo, 'contact_email', $contact_email, 'string', 'Primary contact email');
    SettingsHelper::set($pdo, 'maintenance_mode', $maintenance_mode, 'boolean', 'Maintenance mode on/off');
    // Store maintenance schedule if provided (store as ISO datetime strings)
    if ($maintenance_start !== '') {
        $dt = date('Y-m-d H:i:s', strtotime($maintenance_start));
        SettingsHelper::set($pdo, 'maintenance_start', $dt, 'datetime', 'Maintenance window start (Y-m-d H:i:s)');
    } else {
        SettingsHelper::set($pdo, 'maintenance_start', '', 'datetime', 'Maintenance window start (Y-m-d H:i:s)');
    }
    if ($maintenance_end !== '') {
        $dt2 = date('Y-m-d H:i:s', strtotime($maintenance_end));
        SettingsHelper::set($pdo, 'maintenance_end', $dt2, 'datetime', 'Maintenance window end (Y-m-d H:i:s)');
    } else {
        SettingsHelper::set($pdo, 'maintenance_end', '', 'datetime', 'Maintenance window end (Y-m-d H:i:s)');
    }

    SettingsHelper::set($pdo, 'shipping_enabled', $shipping_enabled, 'boolean', 'Is shipping enabled');
    SettingsHelper::set($pdo, 'shipping_cost', $shipping_cost, 'decimal', 'Default shipping cost');
    SettingsHelper::set($pdo, 'free_shipping_threshold', $free_shipping_threshold, 'decimal', 'Free shipping threshold amount');

    SettingsHelper::set($pdo, 'rating_enabled', $rating_enabled, 'boolean', 'Enable product ratings');
    SettingsHelper::set($pdo, 'rating_threshold', $rating_threshold, 'int', 'Min rating to show');

    SettingsHelper::set($pdo, 'smtp_host', $smtp_host, 'string', 'SMTP host');
    SettingsHelper::set($pdo, 'smtp_port', $smtp_port, 'int', 'SMTP port');
    SettingsHelper::set($pdo, 'smtp_user', $smtp_user, 'string', 'SMTP username');
    if ($smtp_pass !== '') {
        SettingsHelper::set($pdo, 'smtp_pass', $smtp_pass, 'string', 'SMTP password');
    }
    SettingsHelper::set($pdo, 'smtp_from', $smtp_from, 'string', 'Email from address');

    $message = 'Settings saved successfully.';
}

$settings = SettingsHelper::getAll($pdo);

// Debug: Check what settings are being retrieved
// echo '<pre>'; print_r($settings); echo '</pre>';

// Ensure all expected settings have default values if not present
$default_settings = [
    'site_name' => 'Cartella',
    'contact_email' => 'cartella@gmail.com',
    'maintenance_mode' => '0',
    'shipping_enabled' => '1',
    'shipping_cost' => '0',
    'free_shipping_threshold' => '0',
    'rating_enabled' => '1',
    'rating_threshold' => '0',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_user' => '',
    'smtp_from' => ''
];

// Ensure maintenance schedule defaults exist
$default_settings['maintenance_start'] = '';
$default_settings['maintenance_end'] = '';

// Merge defaults with actual settings
foreach ($default_settings as $key => $default_value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $default_value;
    }
}

$page_title = 'Admin Settings';
$meta_description = 'Manage site settings';
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-6 lg:py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Header -->
        <div class="mb-6 lg:mb-8">
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Site Settings</h1>
            <p class="text-gray-600 mt-2">Configure your website preferences and features</p>
        </div>

        <!-- Success Message -->
        <?php if ($message): ?>
            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 animate-fade-in">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-green-800 font-medium"><?php echo htmlspecialchars($message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Settings Form -->
        <form method="POST" class="space-y-6">
            <input type="hidden" name="save_settings" value="1">

            <!-- Basic Settings Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Basic Settings</h3>
                        <p class="text-sm text-gray-600">Core website configuration</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Site Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                            name="site_name"
                            value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                            placeholder="Your website name">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Contact Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                            name="contact_email"
                            value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                            placeholder="contact@example.com">
                    </div>
                </div>

                <div class="mt-6">
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                        <div class="flex items-center h-5">
                            <input type="checkbox"
                                name="maintenance_mode"
                                value="1"
                                <?php echo (!empty($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'checked' : ''; ?>
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-900">Maintenance Mode</span>
                            <p class="text-xs text-gray-500 mt-1">Enable to temporarily disable the website for maintenance</p>
                        </div>
                        <div class="ml-auto">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo (!empty($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo (!empty($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'ON' : 'OFF'; ?>
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Maintenance Schedule Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 bg-red-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Maintenance Schedule (optional)</h3>
                        <p class="text-sm text-gray-600">Set a start and end time for automatic maintenance window.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start (local)</label>
                        <input type="datetime-local" name="maintenance_start" value="<?php echo isset($settings['maintenance_start']) && $settings['maintenance_start'] ? date('Y-m-d\TH:i', strtotime($settings['maintenance_start'])) : ''; ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End (local)</label>
                        <input type="datetime-local" name="maintenance_end" value="<?php echo isset($settings['maintenance_end']) && $settings['maintenance_end'] ? date('Y-m-d\TH:i', strtotime($settings['maintenance_end'])) : ''; ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">Leave empty to manage maintenance manually through the Maintenance Mode toggle.</p>
            </div>

            <!-- Shipping Settings Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Shipping Settings</h3>
                        <p class="text-sm text-gray-600">Configure shipping options and costs</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                        <div class="flex items-center h-5">
                            <input type="checkbox"
                                name="shipping_enabled"
                                value="1"
                                id="shipping_enabled"
                                <?php echo (!empty($settings['shipping_enabled']) && $settings['shipping_enabled'] == '1') ? 'checked' : ''; ?>
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-900">Enable Shipping</span>
                            <p class="text-xs text-gray-500 mt-1">Allow shipping charges for orders</p>
                        </div>
                        <div class="ml-auto">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo (!empty($settings['shipping_enabled']) && $settings['shipping_enabled'] == '1') ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo (!empty($settings['shipping_enabled']) && $settings['shipping_enabled'] == '1') ? 'ENABLED' : 'DISABLED'; ?>
                            </span>
                        </div>
                    </label>

                    <div id="shipping_details" style="<?php echo (!empty($settings['shipping_enabled']) && $settings['shipping_enabled'] == '1') ? '' : 'display: none;'; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pl-3 border-l-2 border-green-200 ml-1">

                            <!-- Default Shipping Rate -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Default Shipping Rate
                                </label>

                                <div class="relative">

                                    <input type="number"
                                        name="shipping_cost"
                                        step="0.01"
                                        min="0"
                                        value="<?php echo htmlspecialchars($settings['shipping_cost'] ?? '0'); ?>"
                                        class="pl-2 w-full px-4 py-2.5 border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                       text-base leading-normal"
                                        placeholder="0.00">

                                    <div class="mt-1 text-xs text-gray-500 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Applied to all orders unless free shipping threshold is met
                                    </div>
                                </div>
                            </div>

                            <!-- Free Shipping Threshold -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Free Shipping Threshold
                                </label>

                                <div class="relative">


                                    <input type="number"
                                        name="free_shipping_threshold"
                                        step="0.01"
                                        min="0"
                                        value="<?php echo htmlspecialchars($settings['free_shipping_threshold'] ?? '0'); ?>"
                                        class="pl-2 w-full px-4 py-2.5 border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                       text-base leading-normal"
                                        placeholder="0.00">

                                    <div class="mt-2">
                                        <?php
                                        $shipping_cost  = floatval($settings['shipping_cost'] ?? 0);
                                        $threshold      = floatval($settings['free_shipping_threshold'] ?? 0);
                                        ?>

                                        <?php if ($threshold > 0): ?>
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-600">Shipping calculation:</span>
                                                <span class="font-medium text-green-600">
                                                    Free shipping on orders over GHS<?php echo number_format($threshold, 2); ?>
                                                </span>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500 flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Set to 0 for no free shipping
                                            </div>
                                        <?php else: ?>
                                            <div class="text-xs text-amber-600 flex items-center mt-1">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                </svg>
                                                No free shipping threshold set
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                        </div>


                        <!-- Shipping Summary -->
                        <div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-lg">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm font-medium text-blue-900">Shipping Summary</span>
                            </div>
                            <div class="text-sm text-blue-800 space-y-2">

                                <!-- SHIPPING COST -->
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>

                                    <?php if ($shipping_cost > 0): ?>
                                        <span>Default shipping rate:</span>
                                        <span class="font-semibold ml-1 flex items-center">
                                            <span class="mr-1">GHS</span>
                                            <span><?php echo number_format($shipping_cost, 2); ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span>Default shipping rate:</span>
                                        <span class="font-semibold ml-1">FREE</span>
                                    <?php endif; ?>
                                </div>


                                <!-- FREE SHIPPING THRESHOLD -->
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>

                                    <?php if ($threshold > 0): ?>
                                        <span>Free shipping on orders over:</span>
                                        <span class="font-semibold ml-1 flex items-center">
                                            <span class="mr-1">GHS</span>
                                            <span><?php echo number_format($threshold, 2); ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span>No free shipping threshold set</span>
                                    <?php endif; ?>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Ratings Settings Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Product Ratings</h3>
                        <p class="text-sm text-gray-600">Configure product review settings</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                        <div class="flex items-center h-5">
                            <input type="checkbox"
                                name="rating_enabled"
                                value="1"
                                id="rating_enabled"
                                <?php echo (!empty($settings['rating_enabled']) && $settings['rating_enabled'] == '1') ? 'checked' : ''; ?>
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-900">Enable Product Ratings</span>
                            <p class="text-xs text-gray-500 mt-1">Allow customers to rate products</p>
                        </div>
                    </label>

                    <div id="rating_details" style="<?php echo (!empty($settings['rating_enabled']) && $settings['rating_enabled'] == '1') ? '' : 'display: none;'; ?>">
                        <div class="pl-3 border-l-2 border-yellow-200 ml-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Minimum Rating to Display
                            </label>
                            <div class="flex items-center gap-4">
                                <input type="range"
                                    name="rating_threshold"
                                    min="0"
                                    max="5"
                                    step="0.5"
                                    value="<?php echo htmlspecialchars($settings['rating_threshold'] ?? '0'); ?>"
                                    class="w-48 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                    oninput="this.nextElementSibling.value = this.value">
                                <output class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($settings['rating_threshold'] ?? '0'); ?></output>
                                <span class="text-sm text-gray-500">stars</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Only show products with ratings above this threshold</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Settings Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Email Settings (SMTP)</h3>
                        <p class="text-sm text-gray-600">Configure email server settings</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                SMTP Host
                            </label>
                            <input type="text"
                                name="smtp_host"
                                value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                placeholder="smtp.gmail.com">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                SMTP Port
                            </label>
                            <input type="number"
                                name="smtp_port"
                                value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                placeholder="587">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                SMTP Username
                            </label>
                            <input type="text"
                                name="smtp_user"
                                value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                placeholder="your-email@gmail.com">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                SMTP Password
                            </label>
                            <input type="password"
                                name="smtp_pass"
                                value=""
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                placeholder="••••••••">
                            <p class="text-xs text-gray-500 mt-2">Leave blank to keep current password</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email From Address
                        </label>
                        <input type="email"
                            name="smtp_from"
                            value="<?php echo htmlspecialchars($settings['smtp_from'] ?? $settings['contact_email'] ?? ''); ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                            placeholder="noreply@example.com">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex flex-col sm:flex-row gap-4 justify-end">
                    <a href="a_index.php"
                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save All Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Toggle dependent fields based on checkbox state
    document.addEventListener('DOMContentLoaded', function() {
        const shippingEnabled = document.getElementById('shipping_enabled');
        const shippingDetails = document.getElementById('shipping_details');
        const ratingEnabled = document.getElementById('rating_enabled');
        const ratingDetails = document.getElementById('rating_details');

        // Shipping toggle
        if (shippingEnabled && shippingDetails) {
            shippingEnabled.addEventListener('change', function() {
                if (this.checked) {
                    shippingDetails.style.display = 'block';
                    setTimeout(() => {
                        shippingDetails.style.opacity = '1';
                    }, 10);
                } else {
                    shippingDetails.style.opacity = '0';
                    setTimeout(() => {
                        shippingDetails.style.display = 'none';
                    }, 300);
                }
            });
        }

        // Rating toggle
        if (ratingEnabled && ratingDetails) {
            ratingEnabled.addEventListener('change', function() {
                if (this.checked) {
                    ratingDetails.style.display = 'block';
                    setTimeout(() => {
                        ratingDetails.style.opacity = '1';
                    }, 10);
                } else {
                    ratingDetails.style.opacity = '0';
                    setTimeout(() => {
                        ratingDetails.style.display = 'none';
                    }, 300);
                }
            });
        }

        // Real-time slider value display
        const ratingSlider = document.querySelector('input[name="rating_threshold"]');
        if (ratingSlider) {
            ratingSlider.addEventListener('input', function() {
                const output = this.nextElementSibling;
                if (output) {
                    output.value = this.value;
                }
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>