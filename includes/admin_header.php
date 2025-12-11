<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/bootstrap.php';

// Get user data if logged in
$user_avatar = null;
$user_name = 'Account';
$user_role = 'Customer'; // Default role

if (isset($_SESSION['user_id'])) {
  // You'll need to fetch user data from your database here
  $user_avatar = $_SESSION['user_avatar'] ?? null;
  $user_name = $_SESSION['user_name'] ?? 'My Account';
  $user_role = $_SESSION['user_role'] ?? 'Customer';
  $user_email = $_SESSION['user_email'] ?? 'Customer';
}

// Get categories for dropdown
require_once 'includes/db.php';
require_once 'includes/functions.php';
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);
$categories = $functions->getAllCategories();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars(SITE_NAME); ?> Admin - Manage Your Store</title>
  <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/3081/3081559.png">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
  <link rel="stylesheet" href="assets/css/toast.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <!-- <script src="https://cdn.tiny.cloud/1/2c3nf8u65nkl0x1mf28mlexocxlm8n3grp3w6pl3gegkpo5u/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script> -->
  <style>
    /* Modern Page Loader */
    .page-loader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      transition: opacity 0.5s ease-out, visibility 0.5s ease-out;
    }

    .page-loader.hidden {
      opacity: 0;
      visibility: hidden;
    }

    .loader-content {
      text-align: center;
      color: white;
    }

    /* Animated Logo */
    .loader-logo {
      width: 80px;
      height: 80px;
      margin-bottom: 20px;
      animation: float 3s ease-in-out infinite;
    }

    .loader-logo img {
      width: 100%;
      height: 100%;
      filter: brightness(0) invert(1);
    }

    /* Progress Bar */
    .loader-progress {
      width: 200px;
      height: 4px;
      background: rgba(255, 255, 255, 0.3);
      border-radius: 10px;
      overflow: hidden;
      margin: 20px 0;
    }

    .loader-progress-bar {
      height: 100%;
      background: linear-gradient(90deg, #ffd89b, #19547b);
      border-radius: 10px;
      width: 0%;
      transition: width 0.3s ease;
      animation: progressAnimation 2s ease-in-out infinite;
    }

    /* Loading Text */
    .loader-text {
      font-size: 14px;
      font-weight: 500;
      margin-top: 10px;
      opacity: 0.9;
    }

    /* Loading Dots */
    .loading-dots {
      display: inline-flex;
      gap: 4px;
    }

    .loading-dots span {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: white;
      animation: dotPulse 1.5s ease-in-out infinite;
    }

    .loading-dots span:nth-child(2) {
      animation-delay: 0.2s;
    }

    .loading-dots span:nth-child(3) {
      animation-delay: 0.4s;
    }

    /* Animations */
    @keyframes float {

      0%,
      100% {
        transform: translateY(0px);
      }

      50% {
        transform: translateY(-10px);
      }
    }

    @keyframes progressAnimation {
      0% {
        transform: translateX(-100%);
      }

      50% {
        transform: translateX(0%);
      }

      100% {
        transform: translateX(100%);
      }
    }

    @keyframes dotPulse {

      0%,
      80%,
      100% {
        transform: scale(0.8);
        opacity: 0.5;
      }

      40% {
        transform: scale(1.2);
        opacity: 1;
      }
    }

    /* Modern Confirmation Modal */
    .confirmation-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .confirmation-modal.active {
      opacity: 1;
      visibility: visible;
    }

    .modal-content {
      background: white;
      border-radius: 16px;
      padding: 0;
      max-width: 400px;
      width: 90%;
      transform: scale(0.9);
      opacity: 0;
      transition: all 0.3s ease;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
      overflow: hidden;
    }

    .confirmation-modal.active .modal-content {
      transform: scale(1);
      opacity: 1;
    }

    .modal-header {
      padding: 24px 24px 16px;
      border-bottom: 1px solid #f1f5f9;
      text-align: center;
    }

    .modal-icon {
      width: 64px;
      height: 64px;
      margin: 0 auto 16px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
    }

    .modal-icon.warning {
      background: #fef3c7;
      color: #d97706;
    }

    .modal-icon.info {
      background: #dbeafe;
      color: #2563eb;
    }

    .modal-icon.success {
      background: #d1fae5;
      color: #059669;
    }

    .modal-icon.error {
      background: #fee2e2;
      color: #dc2626;
    }

    .modal-title {
      font-size: 20px;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 8px;
    }

    .modal-message {
      color: #6b7280;
      font-size: 14px;
      line-height: 1.5;
    }

    .modal-actions {
      padding: 16px 24px 24px;
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }

    .modal-btn {
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 500;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s ease;
      border: none;
      outline: none;
    }

    .modal-btn-confirm {
      background: #2563eb;
      color: white;
    }

    .modal-btn-confirm:hover {
      background: #1d4ed8;
      transform: translateY(-1px);
    }

    /* Add specific styles for different modal types */
    .modal-btn-confirm.info {
      background: #2563eb;
    }

    .modal-btn-confirm.info:hover {
      background: #1d4ed8;
    }

    .modal-btn-confirm.warning {
      background: #d97706;
    }

    .modal-btn-confirm.warning:hover {
      background: #b45309;
    }

    .modal-btn-confirm.success {
      background: #059669;
    }

    .modal-btn-confirm.success:hover {
      background: #047857;
    }

    .modal-btn-confirm.error {
      background: #dc2626;
    }

    .modal-btn-confirm.error:hover {
      background: #b91c1c;
    }

    /* Admin-specific styles */
    .admin-header {
      background: linear-gradient(135deg, #1e3a8a 0%, #2d3748 100%);
    }

    .admin-badge {
      background: #ef4444;
      color: white;
      font-size: 10px;
      padding: 2px 6px;
      border-radius: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .admin-menu-item {
      position: relative;
    }

    .admin-menu-item.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 4px;
      height: 24px;
      background: #8b5cf6;
      border-radius: 0 4px 4px 0;
    }

    .admin-dropdown {
      background: white;
      border-radius: 12px;
      box-shadow: 0 20px 60px rgba(44, 43, 43, 0.15);
      padding: 8px 0;
      z-index: 1000;
      width: 220px;
    }

    .admin-dropdown-item {
      display: flex;
      align-items: center;
      padding: 10px 16px;
      color: #374151;
      text-decoration: none;
      transition: all 0.2s ease;
      border-left: 3px solid transparent;
    }

    .admin-dropdown-item:hover {
      background: #f8fafc;
      color: #8b5cf6;
      border-left-color: #8b5cf6;
    }

    .admin-dropdown-item svg {
      margin-right: 12px;
      color: #6b7280;
    }

    .admin-dropdown-item:hover svg {
      color: #8b5cf6;
    }

    .admin-quick-stats {
      display: flex;
      gap: 16px;
      margin-left: auto;
    }

    .admin-stat-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 8px 12px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      transition: all 0.2s ease;
    }

    .admin-stat-item:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    .admin-stat-value {
      font-size: 18px;
      font-weight: 700;
      color: #e1e1e2ff;
    }

    .admin-stat-label {
      font-size: 11px;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Mobile Bottom Navigation */
    .mobile-bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: white;
      border-top: 1px solid #e5e7eb;
      display: none;
      z-index: 40;
      padding-bottom: env(safe-area-inset-bottom);
      box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    }

    .mobile-bottom-nav.active {
      display: flex;
    }

    .mobile-nav-item {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 10px 4px;
      text-decoration: none;
      color: #6b7280;
      transition: all 0.2s ease;
      position: relative;
      min-height: 56px;
    }

    .mobile-nav-item:hover {
      background-color: #f9fafb;
    }

    .mobile-nav-item.active {
      color: #8b5cf6;
    }

    .mobile-nav-item.active::before {
      content: '';
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 40px;
      height: 3px;
      background: #8b5cf6;
      border-radius: 0 0 4px 4px;
    }

    .mobile-nav-icon {
      width: 22px;
      height: 22px;
      margin-bottom: 4px;
      stroke-width: 2;
    }

    .mobile-nav-label {
      font-size: 10px;
      font-weight: 500;
      text-align: center;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
      .admin-quick-stats {
        gap: 8px;
      }

      .admin-stat-item {
        padding: 6px 8px;
        min-width: 60px;
      }

      .admin-stat-value {
        font-size: 14px;
        color: white;
      }

      .admin-stat-label {
        font-size: 9px;
      }

      .admin-badge {
        font-size: 8px;
        padding: 1px 4px;
      }

      .mobile-bottom-nav {
        display: flex;
      }

      /* Add padding to main content to account for bottom nav */
      main {
        padding-bottom: 70px;
      }
    }

    @media (max-width: 480px) {
      .admin-quick-stats {
        gap: 4px;
      }

      .admin-stat-item {
        padding: 4px 6px;
        min-width: 50px;
      }

      .admin-stat-value {
        font-size: 12px;
        color: white;
      }

      .admin-stat-label {
        font-size: 8px;
      }

      .mobile-nav-icon {
        width: 20px;
        height: 20px;
      }

      .mobile-nav-label {
        font-size: 9px;
      }
    }

    /* Show third stat on larger mobile screens */
    @media (min-width: 380px) {
      .admin-stat-item.hidden-mobile {
        display: flex !important;
      }
    }

    /* Mobile responsive for modal */
    @media (max-width: 640px) {
      .modal-content {
        width: 95%;
        margin: 20px;
      }

      .modal-actions {
        flex-direction: column-reverse;
      }

      .modal-btn {
        width: 100%;
      }
    }

    /* Fallback button styles */
    button {
      background: none;
      border: none;
      cursor: pointer;
      font-family: inherit;
    }

    button:focus {
      outline: 2px solid #8b5cf6;
      outline-offset: 2px;
    }

    ::-webkit-scrollbar {
      width: 4px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f1f1;
    }

    ::-webkit-scrollbar-thumb {
      background: linear-gradient(to bottom, #ba12e4ff, #6366f1);
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(to bottom, #2563eb, #4f46e5);
    }

    /* Smooth transitions */
    * {
      scroll-behavior: smooth;
    }
  </style>
</head>

<body x-data="{
  page: 'admin',
  darkMode: false,
  stickyMenu: false,
  navigationOpen: false,
  scrollTop: false,
  accountDropdown: false,
  productsDropdown: false
}" x-init="
  // Initialize dark mode
  darkMode = JSON.parse(localStorage.getItem('darkMode')) || false;
  
  // Watch for dark mode changes
  $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)));
  
  // Initialize scroll listener for scroll-to-top button
  window.addEventListener('scroll', () => {
    this.scrollTop = window.pageYOffset > 300;
  });
  
  // Load quick stats
  loadQuickStats();
" :class="{'bg-gray-900 text-white': darkMode === true}">

  <!-- Modern Page Loader -->
  <div id="pageLoader" class="page-loader">
    <div class="loader-content">
      <div class="loader-logo">
        <img src="https://cdn-icons-png.flaticon.com/512/3081/3081559.png" alt="<?php echo htmlspecialchars(SITE_NAME); ?> Logo">
      </div>
      <div class="loader-progress">
        <div class="loader-progress-bar"></div>
      </div>
      <div class="loader-text">
        Loading admin dashboard
        <div class="loading-dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Modern Confirmation Modal -->
  <div id="confirmationModal" class="confirmation-modal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-icon warning">
          <i class="mdi mdi-alert-circle-outline"></i>
        </div>
        <h3 class="modal-title" id="modalTitle">Confirm Action</h3>
        <p class="modal-message" id="modalMessage">Are you sure you want to proceed with this action?</p>
      </div>
      <div class="modal-actions">
        <button class="modal-btn modal-btn-cancel" id="modalCancelBtn">Cancel</button>
        <button class="modal-btn modal-btn-confirm" id="modalConfirmBtn">Confirm</button>
      </div>
    </div>
  </div>

  <div class="container max-auto-4xl">
    <!-- ===== Header Start ===== -->
    <header class="fixed top-0 left-0 right-0 z-50 header-transition admin-header"
      :class="{ 'bg-gray-800 shadow-lg': stickyMenu, 'bg-gray-900': !stickyMenu }"
      @scroll.window="stickyMenu = (window.pageYOffset > 20) ? true : false">
      <div class="container mx-auto px-4">
        <!-- Top Bar - Admin Info -->
        <div class="bg-gray-800 text-white text-center py-2 text-sm flex items-center justify-between px-4" :class="{ 'bg-gray-900': stickyMenu }">
          <div class="flex items-center">
            <span class="admin-badge mr-2">ADMIN</span>
            <span class="hidden sm:inline">Admin Dashboard</span>
          </div>

          <!-- Quick Stats - Responsive -->
          <div class="admin-quick-stats flex items-center space-x-2 sm:space-x-4">
            <div class="admin-stat-item">
              <span class="admin-stat-value text-xs sm:text-base" id="quick-orders">0</span>
              <span class="admin-stat-label text-xs">Orders</span>
            </div>
            <div class="admin-stat-item">
              <span class="admin-stat-value text-xs sm:text-base" id="quick-products">0</span>
              <span class="admin-stat-label text-xs">Products</span>
            </div>
            <div class="admin-stat-item hidden-mobile">
              <span class="admin-stat-value text-xs sm:text-base" id="quick-users">0</span>
              <span class="admin-stat-label text-xs">Users</span>
            </div>
          </div>
        </div>

        <!-- Main Header -->
        <div class="flex items-center justify-between h-16">
          <!-- Logo -->
          <div class="flex items-center">
            <a href="a_index.php" class="flex items-center">
              <img class="h-8 w-auto" src="https://cdn-icons-png.flaticon.com/512/3081/3081559.png" alt="<?php echo htmlspecialchars(SITE_NAME); ?> Logo" />
              <span class="ml-2 text-xl lg:text-2xl font-bold text-white"><?php echo htmlspecialchars(SITE_NAME); ?></span>
            </a>
          </div>

          <!-- Desktop Navigation -->
          <nav class="hidden lg:flex items-center space-x-6">
            <a href="a_index.php" class="font-semibold text-sm transition text-white hover:text-purple-200 admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin-dashboard.php' ? 'active' : ''; ?>">
              Dashboard
            </a>

            <a href="a_products.php" class="font-medium text-sm transition text-white hover:text-purple-200">
              Store
            </a>
            <a href="a_categories.php" class="font-medium text-sm transition text-white hover:text-purple-200 admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin-orders.php' ? 'active' : ''; ?>">
              Categories
            </a>
            <a href="a_banners.php" class="font-medium text-sm transition text-white hover:text-purple-200 admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'a_banners.php' ? 'active' : ''; ?>">
              Banners
            </a>
            <a href="a_orders.php" class="font-medium text-sm transition text-white hover:text-purple-200 admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin-orders.php' ? 'active' : ''; ?>">
              Orders
            </a>
            <a href="a_users.php" class="font-medium text-sm transition text-white hover:text-purple-200 admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin-users.php' ? 'active' : ''; ?>">
              Users
            </a>

            <a href="a_coupons.php" class="font-medium text-sm transition text-white hover:text-purple-200 admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin-coupons.php' ? 'active' : ''; ?>">
              Coupons
            </a>

            <a href="a_reports.php" class="font-medium text-sm transition text-white hover:text-purple-200 admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin-reports.php' ? 'active' : ''; ?>">
              Reports
            </a>
          </nav>

          <!-- Right Side Icons -->
          <div class="flex items-center space-x-2 lg:space-x-4">
            <!-- Messages (unread) -->
            <button class="relative p-2 text-white hover:bg-gray-700 rounded-full transition" onclick="window.location.href='a_messages.php'" aria-label="View messages">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2h5l4 3v-3h5a2 2 0 002-2v-0.79"></path>
              </svg>
              <span id="messageCountBadge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 px-1 flex items-center justify-center min-w-[1.25rem]"></span>
            </button>

            <!-- Account Dropdown - Desktop Only -->
            <div class="hidden lg:block relative" x-data="{ accountDropdown: false }">
              <button @click="accountDropdown = !accountDropdown"
                class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-700 transition">
                <?php if (isset($_SESSION['user_id']) && $user_avatar): ?>
                  <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="User Avatar" class="w-8 h-8 rounded-full object-cover">
                <?php else: ?>
                  <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                  </div>
                <?php endif; ?>
                <span class="text-white text-sm font-medium"><?php echo htmlspecialchars($user_name); ?></span>
                <svg class="w-4 h-4 text-white"
                  :class="{ 'rotate-180': accountDropdown }"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>

              <!-- Dropdown Menu -->
              <div x-show="accountDropdown"
                @click.away="accountDropdown = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl py-2 z-50">
                <div class="px-4 py-2 border-b">
                  <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user_name); ?></p>
                  <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user_email); ?></p>
                  <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user_role); ?></p>
                </div>
                <a href="a_account_settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  Account Settings
                </a>
                <a href="a_admin_settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.591 1.066c1.52-.878 3.31.912 2.432 2.432a1.724 1.724 0 001.066 2.591c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.591c.878 1.52-.912 3.31-2.432 2.432a1.724 1.724 0 00-2.591 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.591-1.066c-1.52.878-3.31-.912-2.432-2.432a1.724 1.724 0 00-1.066-2.591c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.591c-.878-1.52.912-3.31 2.432-2.432a1.724 1.724 0 002.591-1.066z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                  Admin Settings
                </a>
                <div class="border-t mt-2 pt-2">
                  <button onclick="showConfirmationModal('Sign Out', 'Are you sure you want to sign out?', performLogout)" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                  </button>
                </div>
              </div>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="lg:hidden p-2 text-white" @click="navigationOpen = !navigationOpen">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!navigationOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                <path x-show="navigationOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div x-show="navigationOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform -translate-y-full"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-full"
        class="lg:hidden bg-gray-900 border-t">
        <div class="container mx-auto px-4 py-4 space-y-3">
          <!-- Mobile Navigation Links -->
          <a href="a_index.php" class="block py-2 text-white hover:text-purple-200 font-medium">Dashboard</a>
          <a href="a_products.php" class="block py-2 text-white hover:text-purple-200 font-medium">Store</a>
          <a href="a_categories.php" class="block py-2 text-white hover:text-purple-200 font-medium">Categories</a>
          <a href="a_banners.php" class="block py-2 text-white hover:text-purple-200 font-medium">Banners</a>
          <a href="a_orders.php" class="block py-2 text-white hover:text-purple-200 font-medium">Orders</a>
          <a href="a_users.php" class="block py-2 text-white hover:text-purple-200 font-medium">Users</a>
          <a href="a_coupons.php" class="block py-2 text-white hover:text-purple-200 font-medium">Coupons</a>

          <!-- Mobile Account Links -->
          <div class="border-t pt-3 space-y-2">
            <a href="a_account_settings.php" class="block w-full text-center py-2 text-purple-200 border border-purple-600 rounded-lg">My Account</a>
            <button onclick="showConfirmationModal('Sign Out', 'Are you sure you want to sign out?', performLogout)" class="block w-full text-center py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Logout</button>
          </div>
        </div>
      </div>
    </header>
  </div>

  <!-- Mobile Bottom Navigation -->
  <nav class="mobile-bottom-nav">
    <?php
    $currentPage = basename($_SERVER['PHP_SELF']);

    // Function to check if a page is active
    function isActive($pagePatterns, $currentPage)
    {
      foreach ($pagePatterns as $pattern) {
        if (strpos($currentPage, $pattern) !== false) {
          return true;
        }
      }
      return false;
    }
    ?>

    <a href="a_index.php"
      class="mobile-nav-item <?php echo isActive(['a_index'], $currentPage) ? 'active' : ''; ?>">
      <svg class="mobile-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
      </svg>
      <span class="mobile-nav-label">Dashboard</span>
    </a>

    <a href="a_products.php"
      class="mobile-nav-item <?php echo isActive(['a_products', 'a_categories', 'a_pro'], $currentPage) ? 'active' : ''; ?>">
      <svg class="mobile-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
      </svg>
      <span class="mobile-nav-label">Store</span>
    </a>

    <a href="a_orders.php"
      class="mobile-nav-item <?php echo isActive(['a_orders'], $currentPage) ? 'active' : ''; ?>">
      <svg class="mobile-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
      </svg>
      <span class="mobile-nav-label">Orders</span>
    </a>

    <a href="a_admin_settings.php"
      class="mobile-nav-item <?php echo isActive(['a_account_settings', 'a_admin_settings'], $currentPage) ? 'active' : ''; ?>">
      <svg class="mobile-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.591 1.066c1.52-.878 3.31.912 2.432 2.432a1.724 1.724 0 001.066 2.591c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.591c.878 1.52-.912 3.31-2.432 2.432a1.724 1.724 0 00-2.591 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.591-1.066c-1.52.878-3.31-.912-2.432-2.432a1.724 1.724 0 00-1.066-2.591c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.591c-.878-1.52.912-3.31 2.432-2.432a1.724 1.724 0 002.591-1.066z" />
        <circle cx="12" cy="12" r="3" />
      </svg>
      <span class="mobile-nav-label">Settings</span>
    </a>
  </nav>

  <!-- ===== Main Content Start ===== -->
  <main class="pt-28 lg:pt-32 bg-gray-100">

    <!-- Toast container (used by assets/js/toast.js) -->
    <div id="toastContainer" class="toast-container"></div>

    <script src="assets/js/toast.js"></script>
    <script>
      // Modern Confirmation Modal Functionality
      let currentConfirmCallback = null;

      function showConfirmationModal(title, message, confirmCallback, options = {}) {
        const modal = document.getElementById('confirmationModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalConfirmBtn = document.getElementById('modalConfirmBtn');
        const modalCancelBtn = document.getElementById('modalCancelBtn');
        const modalIcon = document.querySelector('.modal-icon');

        // Set modal content
        modalTitle.textContent = title;
        modalMessage.textContent = message;

        // Store callback
        currentConfirmCallback = confirmCallback;

        // Set modal type and styling - Default to 'info' for add/update actions
        const type = options.type || 'info';
        const confirmText = options.confirmText || 'Confirm';
        const cancelText = options.cancelText || 'Cancel';

        // Update button texts
        modalConfirmBtn.textContent = confirmText;
        modalCancelBtn.textContent = cancelText;

        // Update styling based on type
        modalIcon.className = 'modal-icon ' + type;
        modalConfirmBtn.className = 'modal-btn modal-btn-confirm ' + type;

        // Show modal
        modal.classList.add('active');

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
      }

      function hideConfirmationModal() {
        const modal = document.getElementById('confirmationModal');
        modal.classList.remove('active');
        document.body.style.overflow = '';
        currentConfirmCallback = null;
      }

      // Initialize modal event listeners
      document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('confirmationModal');
        const modalConfirmBtn = document.getElementById('modalConfirmBtn');
        const modalCancelBtn = document.getElementById('modalCancelBtn');

        // Confirm button click
        modalConfirmBtn.addEventListener('click', function() {
          if (currentConfirmCallback) {
            currentConfirmCallback();
          }
          hideConfirmationModal();
        });

        // Cancel button click
        modalCancelBtn.addEventListener('click', function() {
          hideConfirmationModal();
        });

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
          if (e.target === modal) {
            hideConfirmationModal();
          }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && modal.classList.contains('active')) {
            hideConfirmationModal();
          }
        });
      });

      // Modern Page Loader Functionality
      document.addEventListener('DOMContentLoaded', function() {
        const pageLoader = document.getElementById('pageLoader');

        // Show loader immediately
        pageLoader.classList.remove('hidden');

        // Hide loader when page is fully loaded
        window.addEventListener('load', function() {
          setTimeout(() => {
            pageLoader.classList.add('hidden');
          }, 500);
        });

        // Fallback: hide loader after 3 seconds max
        setTimeout(() => {
          pageLoader.classList.add('hidden');
        }, 3000);
      });

      // Function to load quick stats with better error handling
      async function loadQuickStats() {
        try {
          const response = await fetch('ajax/admin.php?action=get_quick_stats');

          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const text = await response.text();

          // Check if response is valid JSON
          let data;
          try {
            data = JSON.parse(text);
          } catch (parseError) {
            console.error('JSON parse error:', parseError);
            // Fallback to default values
            setDefaultStats();
            return;
          }

          if (data.success) {
            document.getElementById('quick-orders').textContent = data.orders || 0;
            document.getElementById('quick-products').textContent = data.products || 0;
            document.getElementById('quick-users').textContent = data.users || 0;

            // Optional: Also update with revenue if available
            if (data.total_revenue !== undefined) {
              // You can display revenue somewhere if needed
              console.log('Total Revenue:', data.total_revenue);
            }
          } else {
            setDefaultStats();
          }
        } catch (error) {
          console.error('Error loading quick stats:', error);
          setDefaultStats();
        }
      }

      // Fallback function for when stats can't be loaded
      function setDefaultStats() {
        document.getElementById('quick-orders').textContent = '0';
        document.getElementById('quick-products').textContent = '0';
        document.getElementById('quick-users').textContent = '0';
      }

      // Load unread message count for header badge
      async function loadMessageCount() {
        try {
          const response = await fetch('ajax/messages.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=get_message_count'
          });

          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const data = await response.json();
          const badge = document.getElementById('messageCountBadge');
          const count = data.success ? parseInt(data.count || 0, 10) : 0;

          if (!badge) return;

          if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
          } else {
            badge.textContent = '';
            badge.classList.add('hidden');
          }
        } catch (error) {
          console.error('Error loading message count:', error);
        }
      }

      // Load stats when page loads
      document.addEventListener('DOMContentLoaded', function() {
        loadQuickStats();

        // Load unread messages badge and refresh periodically
        loadMessageCount();
        setInterval(loadMessageCount, 30000);

        // Refresh stats every 30 seconds
        setInterval(loadQuickStats, 30000);
      });

      // Global logout function
      function logout() {
        showConfirmationModal(
          'Sign Out',
          'Are you sure you want to sign out?',
          performLogout, {
            type: 'warning',
            confirmText: 'Sign Out',
            cancelText: 'Stay'
          }
        );
      }

      function performLogout() {
        fetch('ajax/auth.php?action=logout')
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showNotification('Signed out successfully', 'success');
              setTimeout(() => {
                window.location.href = 'signin.php';
              }, 1000);
            } else {
              window.location.href = 'signin.php';
            }
          })
          .catch(error => {
            console.error('Error logging out:', error);
            window.location.href = 'signin.php';
          });
      }

      // Simple notification function
      function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 flex items-center transform translate-x-full transition-transform duration-300 max-w-sm`;

        if (type === 'error') {
          notification.classList.add('bg-red-50', 'border', 'border-red-200', 'text-red-800');
        } else if (type === 'success') {
          notification.classList.add('bg-green-50', 'border', 'border-green-200', 'text-green-800');
        } else {
          notification.classList.add('bg-blue-50', 'border', 'border-blue-200', 'text-blue-800');
        }

        notification.innerHTML = `
      <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'} mr-3"></i>
      <span>${message}</span>
    `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
          notification.classList.remove('translate-x-full');
        }, 100);

        // Remove after 5 seconds
        setTimeout(() => {
          notification.classList.add('translate-x-full');
          setTimeout(() => {
            document.body.removeChild(notification);
          }, 300);
        }, 5000);
      }

      // Additional admin functionality
      function loadDashboardStats() {
        fetch('ajax/admin.php?action=get_dashboard_stats')
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Update dashboard with comprehensive stats
              console.log('Dashboard Stats:', data.stats);
              // You can update your dashboard elements here
            }
          })
          .catch(error => {
            console.error('Error loading dashboard stats:', error);
          });
      }

      function loadRecentOrders() {
        fetch('ajax/admin.php?action=get_recent_orders')
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Update recent orders section
              console.log('Recent Orders:', data.orders);
            }
          })
          .catch(error => {
            console.error('Error loading recent orders:', error);
          });
      }

      function loadLowStockProducts() {
        fetch('ajax/admin.php?action=get_low_stock_products')
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Update low stock alerts
              console.log('Low Stock Products:', data.products);
            }
          })
          .catch(error => {
            console.error('Error loading low stock products:', error);
          });
      }

      // Initialize all dashboard data if on dashboard page
      document.addEventListener('DOMContentLoaded', function() {
        // Check if we're on the dashboard page
        if (window.location.pathname.includes('admin-dashboard.php')) {
          loadDashboardStats();
          loadRecentOrders();
          loadLowStockProducts();
        }
      });
    </script>