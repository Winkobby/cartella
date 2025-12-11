<?php
// Start session at the VERY BEGINNING if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration - MUST be called before session_start()
    ini_set('session.gc_maxlifetime', 86400); // 24 hours
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// Simple .env parser that doesn't rely on parse_ini_file()
if (!function_exists('loadEnv')) {
    function loadEnv($filePath) {
        if (!file_exists($filePath)) {
            die("Environment file not found at: $filePath");
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }
}

// Load environment variables
$envFile = __DIR__ . '/../.env';
loadEnv($envFile);

// Database configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'ecommerce_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Application configuration
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Cartella');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/cartella');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('UPLOAD_PATH', $_ENV['UPLOAD_PATH'] ?? 'assets/images/uploads/');

// Session configuration - use these constants for session settings
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? '86400');

// Security
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'default_secret_key_change_in_production');
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'default_encryption_key_change_in_prod');

// Payment configuration
define('PAYSTACK_SECRET_KEY', $_ENV['PAYSTACK_SECRET_KEY'] ?? 'sk_test_your_secret_key_here');
define('PAYSTACK_PUBLIC_KEY', $_ENV['PAYSTACK_PUBLIC_KEY'] ?? 'pk_test_your_public_key_here');
define('PAYSTACK_BASE_URL', 'https://api.paystack.co');

// Payment testing configuration - Convert string to boolean
define('PAYSTACK_TEST_MODE', ($_ENV['PAYSTACK_TEST_MODE'] ?? 'true') === 'true');
define('ENABLE_DEMO_MODE', ($_ENV['ENABLE_DEMO_MODE'] ?? 'false') === 'true');

// Flutterwave configuration
define('FLUTTERWAVE_SECRET_KEY', $_ENV['FLUTTERWAVE_SECRET_KEY'] ?? 'FLWSECK_TEST-your-secret-key');
define('FLUTTERWAVE_PUBLIC_KEY', $_ENV['FLUTTERWAVE_PUBLIC_KEY'] ?? 'FLWPUBK_TEST-your-public-key');
define('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3');

// Payment provider selection
define('PAYMENT_PROVIDER', $_ENV['PAYMENT_PROVIDER'] ?? 'paystack');

// Webhook configuration
define('PAYMENT_WEBHOOK_URL', $_ENV['PAYMENT_WEBHOOK_URL'] ?? APP_URL . '/payment-webhook.php');

// Email configuration
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? '');
define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? 587);
define('MAIL_USER', $_ENV['MAIL_USER'] ?? '');
define('MAIL_PASS', $_ENV['MAIL_PASS'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set('Africa/Accra');

// Create upload directory if it doesn't exist
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Logs directory
if (!defined('LOG_PATH')) {
    define('LOG_PATH', __DIR__ . '/../logs');
}
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Try to load settings from the database (optional) and expose SITE_NAME
// We do this here so templates can use SITE_NAME and it falls back to APP_NAME
try {
    // Require DB and settings helper if available
    if (file_exists(__DIR__ . '/db.php')) {
        require_once __DIR__ . '/db.php';
        require_once __DIR__ . '/settings_helper.php';

        $database = new Database();
        $pdo = $database->getConnection();

        // Ensure settings table exists (no-op if already present)
        SettingsHelper::init($pdo);

        $site_name = SettingsHelper::get($pdo, 'site_name', null);
        if ($site_name === null || $site_name === '') {
            $site_name = (defined('APP_NAME') ? APP_NAME : 'Cartella');
        }
        if (!defined('SITE_NAME')) {
            define('SITE_NAME', $site_name);
        }
        // Load maintenance mode flag (stored as '1' or '0') and optional schedule
        $maintenance = SettingsHelper::get($pdo, 'maintenance_mode', '0');
        $maintenance_start = SettingsHelper::get($pdo, 'maintenance_start', '');
        $maintenance_end = SettingsHelper::get($pdo, 'maintenance_end', '');

        $is_maintenance = ($maintenance === '1' || $maintenance === 1 || $maintenance === true);

        // If schedule values are present, evaluate the current time against the window
        if (!$is_maintenance && !empty($maintenance_start) && !empty($maintenance_end)) {
            try {
                $tz = date_default_timezone_get() ?: 'UTC';
                $now = new DateTime('now', new DateTimeZone($tz));
                $start = new DateTime($maintenance_start, new DateTimeZone($tz));
                $end = new DateTime($maintenance_end, new DateTimeZone($tz));

                if ($start <= $now && $now <= $end) {
                    $is_maintenance = true;
                }
            } catch (Exception $e) {
                // If parsing fails, ignore schedule and rely on explicit flag
            }
        }

        if (!defined('MAINTENANCE_MODE')) {
            define('MAINTENANCE_MODE', $is_maintenance);
        }

        if (!defined('MAINTENANCE_START')) {
            define('MAINTENANCE_START', !empty($maintenance_start) ? $maintenance_start : null);
        }
        if (!defined('MAINTENANCE_END')) {
            define('MAINTENANCE_END', !empty($maintenance_end) ? $maintenance_end : null);
        }
    } else {
        if (!defined('SITE_NAME')) define('SITE_NAME', (defined('APP_NAME') ? APP_NAME : 'Cartella'));
        if (!defined('MAINTENANCE_MODE')) define('MAINTENANCE_MODE', false);
    }
} catch (Exception $e) {
    // If DB isn't available yet, fall back to APP_NAME
    if (!defined('SITE_NAME')) define('SITE_NAME', (defined('APP_NAME') ? APP_NAME : 'Cartella'));
    if (!defined('MAINTENANCE_MODE')) define('MAINTENANCE_MODE', false);
}
?>