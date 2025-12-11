<?php
// 1. First load config
require_once __DIR__ . '/config.php';

// 2. Then initialize database
require_once __DIR__ . '/db.php';
$database = new Database();

// 3. Initialize functions and set database
require_once __DIR__ . '/functions.php';
$functions = new Functions();
$functions->setDatabase($database);

// 4. Initialize auth (now it will have proper dependencies)
require_once __DIR__ . '/auth.php';

require_once __DIR__ . '/Mailer.php';

// 5. Make database available globally if needed
$GLOBALS['database'] = $database;

// Global maintenance-mode redirect for non-admin users.
// Runs after auth is initialized so session and user role are available.
try {
	if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE) {
		// Allow admins to access admin area and maintenance page itself
		$requestUri = $_SERVER['REQUEST_URI'] ?? '';
		$scriptName = basename(parse_url($requestUri, PHP_URL_PATH) ?? '');

		$isAdminArea = (strpos($requestUri, '/admin/') !== false) || (strpos($scriptName, 'a_') === 0) || (strpos($scriptName, 'admin') !== false);
		$isMaintenancePage = ($scriptName === 'maintenance.php');

		$user_role = $_SESSION['user_role'] ?? 'Customer';
		$isAdminUser = strtolower($user_role) === 'admin';

		if (!$isAdminUser && !$isAdminArea && !$isMaintenancePage) {
			// Build a redirect path relative to the current application directory
			$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/';
			$appBase = rtrim(dirname($scriptPath), '/\\');
			if ($appBase === '/' || $appBase === '.') $appBase = '';
			$redirectPath = ($appBase === '') ? '/maintenance.php' : $appBase . '/maintenance.php';

			// Use a relative/absolute path on the same host to avoid wrong APP_URL/port issues
			header('Location: ' . $redirectPath);
			exit;
		}
	}
} catch (Exception $e) {
	// If anything goes wrong, don't block the site; fail open
}
?>