<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/bootstrap.php';

// Get user data if logged in
$user_avatar = null;
$user_name = 'Account';
if (isset($_SESSION['user_id'])) {
  $user_avatar = $_SESSION['user_avatar'] ?? null;
  $user_name = $_SESSION['user_name'] ?? 'My Account';
}

// Get categories for dropdown
require_once 'includes/db.php';
require_once 'includes/functions.php';
$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);
$categories = $functions->getAllCategories();

// Fetch a single active coupon for promo modal
$active_coupon = null;
try {
  $pdo = $database->getConnection();
  $stmt = $pdo->prepare("SELECT * FROM coupons WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW() ORDER BY end_date ASC LIMIT 1");
  $active_coupon = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log('Error fetching active coupon for promo modal: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars(SITE_NAME); ?> - Your Premium Online Shopping Destination</title>
  <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/3081/3081559.png">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
  <link rel="stylesheet" href="assets/css/toast.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <style>
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

    /* WhatsApp FAB Lively Bounce Animation */
    @keyframes whatsappBounce {
      0% {
        transform: translateY(0px) scale(1);
      }

      50% {
        /* smaller vertical movement and gentler scale to reduce visual bounce */
        transform: translateY(-4px) scale(1.02);
      }

      100% {
        transform: translateY(0px) scale(1);
      }
    }

    @keyframes whatsappPulse {
      0% {
        box-shadow: 0 8px 18px rgba(34, 197, 94, 0.18);
      }

      50% {
        box-shadow: 0 8px 24px rgba(34, 197, 94, 0.32);
      }

      100% {
        box-shadow: 0 8px 18px rgba(34, 197, 94, 0.18);
      }
    }

    .whatsapp-fab {
      /* slower and subtler animation to reduce bounce impact */
      animation: whatsappBounce 3.8s ease-in-out infinite, whatsappPulse 3.8s ease-in-out infinite;
    }

    .whatsapp-fab:hover {
      animation-play-state: paused;
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

      /* Coupon modal tweaks */
      #couponPromoModal.show {
        display: flex;
      }

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

    .modal-btn-cancel {
      background: #f8fafc;
      color: #64748b;
      border: 1px solid #e2e8f0;
    }

    .modal-btn-cancel:hover {
      background: #f1f5f9;
      color: #475569;
    }

    .modal-btn-confirm {
      background: #ef4444;
      color: white;
    }

    .modal-btn-confirm:hover {
      background: #dc2626;
      transform: translateY(-1px);
    }

    .modal-btn-confirm.success {
      background: #10b981;
    }

    .modal-btn-confirm.success:hover {
      background: #059669;
    }

    .modal-btn-confirm.warning {
      background: #f59e0b;
    }

    .modal-btn-confirm.warning:hover {
      background: #d97706;
    }

    .modal-btn-confirm.info {
      background: #3b82f6;
    }

    .modal-btn-confirm.info:hover {
      background: #2563eb;
    }

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

    /* Product and Category Styles */
    .product-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .category-card {
      transition: all 0.3s ease;
    }

    .category-card:hover {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }

    .rating-stars {
      color: #ffd700;
    }

    .mobile-menu {
      transform: translateX(-100%);
      transition: transform 0.3s ease-in-out;
    }

    .mobile-menu.active {
      transform: translateX(0);
    }

    @media (max-width: 1023px) {
      .desktop-only {
        display: none !important;
      }
    }

    @media (min-width: 1024px) {
      .mobile-only {
        display: none !important;
      }
    }

    .header-transition {
      transition: all 0.3s ease;
    }

    .avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
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

    /* Navigation Dropdown Styles */
    .navigation-dropdown {
      position: relative;
    }

    .navigation-dropdown-content {
      position: absolute;
      top: 100%;
      left: 0;
      width: 230px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 20px 60px rgba(44, 43, 43, 0.15);
      padding: 16px 0;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1000;
    }

    .navigation-dropdown:hover .navigation-dropdown-content {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .nav-item {
      display: flex;
      align-items: center;
      padding: 3px 20px;
      color: #374151;
      text-decoration: none;
      transition: all 0.2s ease;
      border-left: 3px solid transparent;
    }

    .nav-item:hover {
      background: #f8fafc;
      color: #7c3aed;
      border-left-color: #7c3aed;
    }

    .nav-item i {
      width: 20px;
      margin-right: 12px;
      text-align: center;
    }

    /* Category Items in Navigation */
    .category-nav-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1px 20px 1px 32px;
      color: #374151;
      text-decoration: none;
      transition: all 0.2s ease;
      border-left: 3px solid transparent;
      font-size: 12px;
    }

    .category-nav-item:hover {
      background: #f8fafc;
      color: #7c3aed;
      border-left-color: #7c3aed;
    }

    .category-count {
      background: #f1f5f9;
      color: #64748b;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 500;
    }

    .category-nav-item:hover .category-count {
      background: #ede9fe;
      color: #7c3aed;
    }

    .nav-section-header {
      padding: 8px 20px;
      font-size: 12px;
      font-weight: 600;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-top: 8px;
    }

    /* Enhanced Search Styles */
    .search-container {
      position: relative;
      width: 100%;
      max-width: 500px;
    }

    .search-input {
      width: 100%;
      padding: 10px 50px 10px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 25px;
      font-size: 14px;
      transition: all 0.3s ease;
      background: white;
    }

    .search-input:focus {
      outline: none;
      border-color: #8b5cf6;
      box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .search-button {
      position: absolute;
      right: 6px;
      top: 50%;
      transform: translateY(-50%);
      background: #8b5cf6;
      color: white;
      border: none;
      border-radius: 20px;
      padding: 8px 16px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .search-button:hover {
      background: #7c3aed;
      /* transform: translateY(-50%) scale(1.05); */
    }

    /* Coupon Slider Styles */
    .coupon-slider {
      position: relative;
      height: 24px;
    }

    .coupon-slide {
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #coupon-announcement .flex {
      align-items: center;
    }

    @media (max-width: 768px) {
      .coupon-slider {
        height: 40px;
      }

      #coupon-announcement {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
      }
    }

    [class*="indicator-"] {
      transition: all 0.3s ease;
      cursor: pointer;
    }

    /* Header Shadow Fix */
    .header-shadow {
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Hamburger Menu Styles */
    .hamburger-menu {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .hamburger-btn {
      background: none;
      border: none;
      padding: 8px;
      cursor: pointer;
      border-radius: 6px;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .hamburger-btn:hover {
      background: #f8fafc;
    }

    .hamburger-icon {
      width: 20px;
      height: 20px;
      transition: all 0.3s ease;
    }

    @media (max-width: 1023px) {
      .hamburger-menu {
        display: none;
      }
    }
  </style>
</head>

<body x-data="{ page: 'home', 'stickyMenu': false, 'navigationOpen': false, 'scrollTop': false, 'accountDropdown': false }"
  x-init="loadCartCount();">

  <?php
  // NOTE: Maintenance banner moved into the top announcement bar below
  ?>

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
        Loading your shopping experience
        <div class="loading-dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($active_coupon)): ?>
    <!-- Coupon Promo Toast (Bottom-Left) -->
    <div id="couponPromoModal" class="fixed bottom-6 left-6 bg-white rounded-2xl shadow-2xl w-full max-w-sm relative overflow-hidden border border-gray-100 hidden z-[9999] transition-all duration-300">
      <div class="bg-white">
        <button id="couponPromoClose" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 z-10" aria-label="Close coupon promo">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-4">
          <p class="text-xs uppercase tracking-wide text-white/80">Limited Offer</p>
          <h3 class="text-lg font-bold mt-1">Save with this coupon</h3>
          <p class="text-xs text-white/90 mt-1">Valid until <?php echo date('M j, Y', strtotime($active_coupon['end_date'])); ?></p>
        </div>

        <div class="p-4 space-y-3">
          <div class="flex items-center justify-between bg-gray-50 border border-dashed border-purple-200 rounded-lg px-3 py-2">
            <div>
              <p class="text-xs text-gray-500">Use code</p>
              <p class="text-lg font-extrabold text-gray-900 tracking-wide" id="couponCodeValue"><?php echo htmlspecialchars($active_coupon['code']); ?></p>
            </div>
            <button id="copyCouponCode" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 transition whitespace-nowrap">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              Copy
            </button>
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs text-gray-700">
            <div class="p-2 rounded-lg bg-gray-50 border border-gray-100">
              <p class="text-xs text-gray-500">Discount</p>
              <p class="font-semibold text-gray-900">
                <?php if ($active_coupon['discount_type'] === 'percentage'): ?>
                  <?php echo intval($active_coupon['discount_value']); ?>% OFF
                <?php else: ?>
                  GHS <?php echo number_format($active_coupon['discount_value'], 2); ?>
                <?php endif; ?>
              </p>
            </div>
            <div class="p-2 rounded-lg bg-gray-50 border border-gray-100">
              <p class="text-xs text-gray-500">Min. order</p>
              <p class="font-semibold text-gray-900">GHS <?php echo number_format($active_coupon['min_order_amount'], 2); ?></p>
            </div>
          </div>

          {{#if description}}
            <?php if (!empty($active_coupon['description'])): ?>
              <p class="text-xs text-gray-600 leading-tight"><?php echo htmlspecialchars($active_coupon['description']); ?></p>
            <?php endif; ?>
          {{/if}}

          <div class="flex gap-2">
            <button id="applyCouponNow" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 rounded-lg transition text-sm">
              Apply Now
            </button>
            <button id="dismissCouponPromo" class="px-3 py-2 rounded-lg border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition text-sm">
              Dismiss
            </button>
          </div>
        </div>
      </div>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('couponPromoModal');
        if (!modal) return;

        const closeBtn = document.getElementById('couponPromoClose');
        const dismissBtn = document.getElementById('dismissCouponPromo');
        const applyBtn = document.getElementById('applyCouponNow');
        const copyBtn = document.getElementById('copyCouponCode');
        const codeText = document.getElementById('couponCodeValue');
        // Use coupon-aware storage key so a new code always shows even if a previous one was dismissed
        const codeValue = codeText ? codeText.textContent.trim() : '';
        const storageKey = `coupon_promo_seen_${codeValue || 'default'}`;
        const cooldownMs = 24 * 60 * 60 * 1000; // 24 hours

        const hideModal = () => {
          modal.classList.remove('flex');
          modal.classList.add('hidden');
          localStorage.setItem(storageKey, Date.now().toString());
        };

        const showModal = () => {
          const lastSeen = parseInt(localStorage.getItem(storageKey) || '0', 10);
          // If coupon changed, show immediately; otherwise respect cooldown
          if (lastSeen && Date.now() - lastSeen < cooldownMs) return;
          modal.classList.remove('hidden');
          modal.classList.add('flex');
        };

        // Show once per 24h, slight delay after load
        setTimeout(showModal, 800);

        [closeBtn, dismissBtn].forEach(btn => {
          if (btn) {
            btn.addEventListener('click', hideModal);
          }
        });

        // Copy coupon code
        if (copyBtn && codeText) {
          copyBtn.addEventListener('click', async () => {
            try {
              await navigator.clipboard.writeText(codeText.textContent.trim());
              copyBtn.textContent = 'Copied!';
              setTimeout(() => {
                copyBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>Copy';
              }, 1500);
            } catch (err) {
              console.warn('Clipboard copy failed', err);
            }
          });
        }

        // Apply coupon: copy and navigate to checkout with query param
        if (applyBtn && codeText) {
          applyBtn.addEventListener('click', async () => {
            try {
              await navigator.clipboard.writeText(codeText.textContent.trim());
            } catch (err) {}
            window.location.href = 'checkout.php?coupon=' + encodeURIComponent(codeText.textContent.trim());
          });
        }
      });
    </script>
  <?php endif; ?>

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

  <!-- ===== Header Start ===== -->
  <header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-sm"
    x-data="{ stickyMenu: false, navigationOpen: false, accountDropdown: false, showAnnouncement: true }"
    @scroll.window="stickyMenu = (window.pageYOffset > 20) ? true : false; showAnnouncement = (window.pageYOffset > 50) ? false : true"
    :class="{ 'shadow-lg': stickyMenu }">

    <!-- Top Bar - Announcements (Hidden on scroll) -->
    <div x-show="showAnnouncement"
      x-transition:enter="transition ease-out duration-300"
      x-transition:enter-start="opacity-0 transform -translate-y-2"
      x-transition:enter-end="opacity-100 transform translate-y-0"
      x-transition:leave="transition ease-in duration-200"
      x-transition:leave-start="opacity-100 transform translate-y-0"
      x-transition:leave-end="opacity-0 transform -translate-y-2"
      class="bg-purple-800 text-white text-center py-2 text-sm transition-all duration-300">
      <?php
      // If maintenance mode is active, display maintenance message here (top announcement bar)
      $user_role = $_SESSION['user_role'] ?? 'Customer';
      if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE && strtolower($user_role) !== 'admin') :
      ?>
        <div class="w-full bg-amber-500 text-white text-center py-2 text-sm font-medium">
          <?php echo htmlspecialchars(SITE_NAME); ?> is undergoing maintenance — purchases are temporarily disabled. We apologize for the inconvenience.
        </div>
      <?php else: ?>
        <div id="coupon-loader" class="flex items-center justify-center">
          <svg class="animate-spin h-4 w-4 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Loading special offers...
        </div>
        <div id="coupon-content" class="hidden"></div>
      <?php endif; ?>
    </div>

    <div class="container mx-auto max-w-6xl">
      <!-- Main Header -->
      <div class="flex items-center justify-between h-16">

        <!-- Left Section: Mobile Hamburger + Logo -->
        <div class="flex items-center gap-3">
          <!-- Mobile Hamburger Menu -->
          <button class="lg:hidden p-2 text-purple-700 hover:bg-purple-100 rounded-lg transition"
            @click="navigationOpen = !navigationOpen">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path x-show="!navigationOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              <path x-show="navigationOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>

          <!-- Desktop Hamburger Menu -->
          <div class="hamburger-menu navigation-dropdown hidden lg:block">
            <button class="hamburger-btn text-purple-700 hover:bg-purple-100">
              <svg class="hamburger-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
            </button>

            <!-- Navigation Dropdown -->
            <div class="navigation-dropdown-content text-sm">
              <!-- Main Navigation Links -->
              <a href="index.php" class="nav-item">
                <i class="mdi mdi-home"></i>
                <span>Home</span>
              </a>
              <a href="products.php" class="nav-item">
                <i class="mdi mdi-shopping"></i>
                <span>Shop All</span>
              </a>

              <!-- Categories Section -->
              <div class="nav-section-header">Categories</div>
              <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                  <a href="products.php?category=<?php echo $category['slug']; ?>" class="category-nav-item">
                    <span><?php echo htmlspecialchars($category['category_name']); ?></span>
                    <span class="category-count">
                      <?php echo $functions->getProductCountByCategory($category['category_id']); ?>
                    </span>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="category-nav-item">
                  <span>No categories available</span>
                </div>
              <?php endif; ?>

              <!-- Additional Navigation Links -->
              <div class="nav-section-header">More</div>
              <a href="deals.php" class="nav-item">
                <i class="mdi mdi-tag"></i>
                <span>Special Deals</span>
              </a>
              <a href="products.php?sort=newest" class="nav-item">
                <i class="mdi mdi-new-box"></i>
                <span>New Arrivals</span>
              </a>
            </div>
          </div>

          <!-- Logo -->
          <a href="index.php" class="flex items-center">
            <img class="h-8 w-auto" src="https://cdn-icons-png.flaticon.com/512/3081/3081559.png" alt="<?php echo htmlspecialchars(SITE_NAME); ?> Logo" />
            <span class="ml-2 text-xl lg:text-2xl font-bold text-purple-600"><?php echo htmlspecialchars(SITE_NAME); ?></span>
          </a>
        </div>

        <!-- Center Section: Search Bar (Desktop) -->
        <div class="hidden lg:flex flex-1 max-w-2xl mx-8">
          <form action="search.php" method="GET" class="search-container">
            <input type="text"
              name="search"
              placeholder="Search for products, brands, and more..."
              class="search-input border-gray-300">
            <button type="submit" class="search-button">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
              Search
            </button>
          </form>
        </div>

        <!-- Right Section: Icons -->
        <div class="flex items-center space-x-4">
          <!-- <a href="wishlist.php" class="relative p-2 hover:bg-gray-100 hidden md:block rounded-full transition">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
            </svg>
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" id="wishlist-count">0</span>
          </a> -->

          <!-- Cart Icon -->
          <a href="cart.php" class="relative flex items-center space-x-2 p-2 hover:bg-purple-50 rounded-full transition group">
            <div class="relative">
              <svg class="w-6 h-6 text-purple-600 group-hover:text-purple-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
              <span class="absolute -top-2 -right-3 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center group-hover:bg-red-600 transition-colors" id="cart-count">0</span>
            </div>
            <span class="hidden md:inline-block text-purple-600 font-medium group-hover:text-purple-700 transition-colors">Cart</span>
          </a>
          <!-- Account Dropdown (Desktop) -->
          <div class="relative hidden lg:block" x-data="{ accountDropdown: false }">
            <button @click="accountDropdown = !accountDropdown"
              class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-100 transition">
              <?php if (isset($_SESSION['user_id']) && $user_avatar): ?>
                <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="User Avatar" class="avatar">
              <?php else: ?>
                <div class="avatar bg-purple-500 flex items-center justify-center text-white">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
              <?php endif; ?>
              <span class="text-gray-800 text-sm font-medium hover:text-purple-600"><?php echo htmlspecialchars($user_name); ?></span>
              <svg class="w-4 h-4 text-purple-800"
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
              class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 z-50">
              <?php if (isset($_SESSION['user_id'])): ?>
                <div class="px-4 py-2 border-b">
                  <div class="flex items-center">
                    <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <div>
                      <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user_name); ?></p>
                      <p class="text-xs text-gray-500">Welcome back!</p>
                    </div>
                  </div>
                </div>

                <a href="account.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Account
                </a>

                <a href="orders.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  My Orders
                </a>

                <a href="wishlist.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                  Wishlist
                </a>

                <div class="border-t mt-2 pt-2">
                  <button onclick="logout()" class="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                  </button>
                </div>
              <?php else: ?>
                <a href="signin.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                  </svg>
                  Sign In
                </a>

                <a href="signup.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                  </svg>
                  Sign Up
                </a>
              <?php endif; ?>
            </div>
          </div>

          <!-- Mobile Account Icon (Visible on mobile) -->
          <div class="lg:hidden relative" x-data="{ accountDropdown: false }">
            <button @click="accountDropdown = !accountDropdown"
              class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-100 transition">
              <?php if (isset($_SESSION['user_id']) && $user_avatar): ?>
                <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="User Avatar" class="avatar">
              <?php else: ?>
                <div class="avatar bg-purple-500 flex items-center justify-center text-white">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
              <?php endif; ?>
            </button>

            <!-- Mobile Account Dropdown -->
            <div x-show="accountDropdown"
              @click.away="accountDropdown = false"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 transform scale-95"
              x-transition:enter-end="opacity-100 transform scale-100"
              x-transition:leave="transition ease-in duration-150"
              x-transition:leave-start="opacity-100 transform scale-100"
              x-transition:leave-end="opacity-0 transform scale-95"
              class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 z-50">
              <?php if (isset($_SESSION['user_id'])): ?>
                <div class="px-4 py-2 border-b">
                  <div class="flex items-center">
                    <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <div>
                      <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user_name); ?></p>
                      <p class="text-xs text-gray-500">Welcome back!</p>
                    </div>
                  </div>
                </div>

                <a href="account.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Account
                </a>

                <a href="orders.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  My Orders
                </a>

                <a href="wishlist.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                  Wishlist
                </a>

                <div class="border-t mt-2 pt-2">
                  <button onclick="logout()" class="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                  </button>
                </div>
              <?php else: ?>
                <a href="signin.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                  </svg>
                  Sign In
                </a>

                <a href="signup.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                  </svg>
                  Sign Up
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile Search Bar (Below navigation on mobile) -->
      <div class="lg:hidden pb-3 pl-3 pr-3">
        <form action="search.php" method="GET" class="search-container">
          <input type="text"
            name="search"
            placeholder="Search for products, brands, and more..."
            class="search-input border-gray-300 w-full">
          <button type="submit" class="search-button">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </button>
        </form>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="navigationOpen"
      x-transition:enter="transition ease-out duration-300"
      x-transition:enter-start="opacity-0 transform -translate-x-full"
      x-transition:enter-end="opacity-100 transform translate-x-0"
      x-transition:leave="transition ease-in duration-200"
      x-transition:leave-start="opacity-100 transform translate-x-0"
      x-transition:leave-end="opacity-0 transform -translate-x-full"
      class="lg:hidden fixed inset-0 z-[9999] bg-white"
      style="display: none;">

      <!-- Overlay Background -->
      <div class="absolute inset-0 bg-black bg-opacity-50" @click="navigationOpen = false"></div>

      <!-- Slide-in Menu Panel -->
      <div class="relative bg-white w-80 max-w-full h-full overflow-y-auto shadow-xl">

        <!-- Menu Header -->
        <div class="flex items-center justify-between p-4 border-b">
          <div class="flex items-center">
            <img class="h-6 w-auto" src="https://cdn-icons-png.flaticon.com/512/3081/3081559.png" alt="<?php echo htmlspecialchars(SITE_NAME); ?> Logo" />
            <span class="ml-2 text-lg font-bold text-purple-600"><?php echo htmlspecialchars(SITE_NAME); ?></span>
          </div>
          <button @click="navigationOpen = false" class="p-2 hover:bg-gray-100 rounded-full">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Navigation Links -->
        <div class="p-4 space-y-1">
          <a href="index.php" class="flex items-center py-2 px-4 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg transition" @click="navigationOpen = false">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Home
          </a>

          <a href="products.php" class="flex items-center py-2 px-4 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg transition" @click="navigationOpen = false">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
            </svg>
            Shop All
          </a>

          <!-- Categories Section -->
          <div class="pt-4">
            <h3 class="px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Categories</h3>
            <div class="mt-2 space-y-1">
              <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                  <a href="products.php?category=<?php echo $category['slug']; ?>"
                    class="flex items-center justify-between py-2 px-4 text-gray-600 hover:bg-purple-50 hover:text-purple-600 rounded-lg transition group" @click="navigationOpen = false">
                    <span class="text-sm"><?php echo htmlspecialchars($category['category_name']); ?></span>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded-full group-hover:bg-purple-100 group-hover:text-purple-600">
                      <?php echo $functions->getProductCountByCategory($category['category_id']); ?>
                    </span>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="px-4 text-sm text-gray-500">No categories available</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Additional Links -->
          <div class="pt-4">
            <h3 class="px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">More</h3>
            <div class="mt-2 space-y-1">
              <a href="deals.php" class="flex items-center py-2 px-4 text-gray-600 hover:bg-purple-50 hover:text-purple-600 rounded-lg transition" @click="navigationOpen = false">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Special Deals
              </a>

              <a href="products.php?sort=newest" class="flex items-center py-2 px-4 text-gray-600 hover:bg-purple-50 hover:text-purple-600 rounded-lg transition" @click="navigationOpen = false">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                New Arrivals
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
  <!-- WhatsApp FAB -->
  <?php
  // Load the WhatsApp number from environment
  $whatsappNumber = $_ENV['SHOP_WHATSAPP_NUMBER'] ?? '+1234567890';
  $whatsappLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $whatsappNumber);
  ?>
  <div class="fixed bottom-28 right-6 z-50">
    <!-- Desktop version with text -->
    <a href="<?php echo $whatsappLink; ?>"
      class="hidden md:flex items-center bg-gradient-to-r from-purple-600 to-pink-600 hover:bg-green-600 text-white rounded-full shadow-lg transition-all duration-300 px-3 py-1.5 hover:scale-105 whatsapp-fab"
      target="_blank" rel="noopener noreferrer">
      <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2zm.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 012.41 5.83c0 4.54-3.7 8.23-8.24 8.23-1.48 0-2.93-.39-4.19-1.15l-.3-.18-.31.08-1.26.33.33-1.22.09-.34-.2-.32a8.188 8.188 0 01-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24M8.53 7.33c-.16 0-.43.06-.66.31-.22.25-.87.86-.87 2.07 0 1.22.89 2.39 1 2.56.12.17 1.76 2.67 4.25 3.73.59.27 1.05.42 1.41.53.59.19 1.13.16 1.56.1.48-.07 1.46-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.07-.1-.23-.16-.48-.27-.25-.14-1.47-.74-1.69-.82-.23-.08-.37-.12-.56.12-.16.25-.64.81-.78.97-.15.17-.29.19-.53.07-.26-.13-1.06-.39-2-1.23-.74-.66-1.23-1.47-1.38-1.72-.12-.24-.01-.39.11-.5.11-.11.27-.29.37-.44.13-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.11-.56-1.35-.77-1.84-.2-.48-.4-.42-.56-.43-.14 0-.3-.01-.47-.01z" />
      </svg>
      <span class="ml-2 text-sm">Hi, how can I help?</span>
    </a>

    <!-- Mobile version (icon only) -->
    <a href="<?php echo $whatsappLink; ?>"
      class="md:hidden flex items-center justify-center bg-gradient-to-r from-purple-600 to-pink-600 hover:bg-green-600 text-white rounded-full shadow-lg transition-all duration-300 w-14 h-14 hover:scale-110 whatsapp-fab"
      target="_blank" rel="noopener noreferrer">
      <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2zm.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 012.41 5.83c0 4.54-3.7 8.23-8.24 8.23-1.48 0-2.93-.39-4.19-1.15l-.3-.18-.31.08-1.26.33.33-1.22.09-.34-.2-.32a8.188 8.188 0 01-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24M8.53 7.33c-.16 0-.43.06-.66.31-.22.25-.87.86-.87 2.07 0 1.22.89 2.39 1 2.56.12.17 1.76 2.67 4.25 3.73.59.27 1.05.42 1.41.53.59.19 1.13.16 1.56.1.48-.07 1.46-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.07-.1-.23-.16-.48-.27-.25-.14-1.47-.74-1.69-.82-.23-.08-.37-.12-.56.12-.16.25-.64.81-.78.97-.15.17-.29.19-.53.07-.26-.13-1.06-.39-2-1.23-.74-.66-1.23-1.47-1.38-1.72-.12-.24-.01-.39.11-.5.11-.11.27-.29.37-.44.13-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.11-.56-1.35-.77-1.84-.2-.48-.4-.42-.56-.43-.14 0-.3-.01-.47-.01z" />
      </svg>
    </a>
  </div>
  <!-- ===== Main Content Start ===== -->
  <main class="pt-40 lg:pt-32" style="background-color: #F1F1F2">

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

        modalTitle.textContent = title;
        modalMessage.textContent = message;
        currentConfirmCallback = confirmCallback;

        const type = options.type || 'warning';
        const confirmText = options.confirmText || 'Confirm';
        const cancelText = options.cancelText || 'Cancel';

        modalConfirmBtn.textContent = confirmText;
        modalCancelBtn.textContent = cancelText;

        modalIcon.className = 'modal-icon ' + type;
        modalConfirmBtn.className = 'modal-btn modal-btn-confirm ' + type;

        modal.classList.add('active');
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

        modalConfirmBtn.addEventListener('click', function() {
          if (currentConfirmCallback) {
            currentConfirmCallback();
          }
          hideConfirmationModal();
        });

        modalCancelBtn.addEventListener('click', function() {
          hideConfirmationModal();
        });

        modal.addEventListener('click', function(e) {
          if (e.target === modal) {
            hideConfirmationModal();
          }
        });

        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && modal.classList.contains('active')) {
            hideConfirmationModal();
          }
        });
      });

      // Modern Page Loader Functionality
      document.addEventListener('DOMContentLoaded', function() {
        const pageLoader = document.getElementById('pageLoader');
        pageLoader.classList.remove('hidden');

        window.addEventListener('load', function() {
          setTimeout(() => {
            pageLoader.classList.add('hidden');
          }, 500);
        });

        setTimeout(() => {
          pageLoader.classList.add('hidden');
        }, 3000);
      });

      function showPageLoader() {
        const pageLoader = document.getElementById('pageLoader');
        pageLoader.classList.remove('hidden');
      }

      function hidePageLoader() {
        const pageLoader = document.getElementById('pageLoader');
        pageLoader.classList.add('hidden');
      }

      // Function to load cart count from server
      async function loadCartCount() {
        try {
          const response = await fetch('ajax/cart.php?action=get_cart_count');
          const data = await response.json();

          if (data.success) {
            updateCartCountDisplay(data.count);
          } else {
            console.error('Failed to load cart count:', data);
            loadCartCountFromStorage();
          }
        } catch (error) {
          console.error('Error loading cart count:', error);
          loadCartCountFromStorage();
        }
      }

      function updateCartCountDisplay(count) {
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
          cartCountElement.textContent = count;
          localStorage.setItem('cartCount', count);
        }
      }

      function loadCartCountFromStorage() {
        const savedCount = localStorage.getItem('cartCount');
        if (savedCount !== null) {
          updateCartCountDisplay(parseInt(savedCount));
        }
      }

      async function addToCart(productId, quantity = 1) {
        try {
          const response = await fetch('ajax/cart.php?action=add_to_cart', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${quantity}`
          });

          const data = await response.json();

          if (data.success) {
            updateCartCountDisplay(data.cart_count);
            showNotification('Product added to cart!', 'success');
            return true;
          } else {
            showNotification(data.message || 'Failed to add product to cart', 'error');
            return false;
          }
        } catch (error) {
          console.error('Error adding to cart:', error);
          showNotification('Error adding product to cart', 'error');
          return false;
        }
      }

      document.addEventListener('alpine:init', function() {
        loadCartCount();
      });

      document.addEventListener('DOMContentLoaded', function() {
        loadCartCount();
      });

      window.addEventListener('storage', function(e) {
        if (e.key === 'cartCount') {
          updateCartCountDisplay(e.newValue);
        }
      });

      function showNotification(message, type = 'info') {
        try {
          if (window.toast && typeof window.toast[type] === 'function') {
            window.toast[type](message);
            return;
          }

          if (window.toast && typeof window.toast.show === 'function') {
            window.toast.show(message, type);
            return;
          }
        } catch (e) {
          console.error('Toast error:', e);
        }

        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
          type === 'success' ? 'bg-green-500 text-white' : 
          type === 'error' ? 'bg-red-500 text-white' : 
          'bg-blue-500 text-white'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
      }

      async function loadWishlistCount() {
        try {
          const response = await fetch('ajax/wishlist.php?action=get_wishlist_count');
          const data = await response.json();

          if (data.success) {
            updateWishlistCountDisplay(data.count);
          } else {
            loadWishlistCountFromStorage();
          }
        } catch (error) {
          loadWishlistCountFromStorage();
        }
      }

      function updateWishlistCountDisplay(count) {
        const wishlistCountElement = document.getElementById('wishlist-count');
        if (wishlistCountElement) {
          wishlistCountElement.textContent = count;
          localStorage.setItem('wishlistCount', count);
        }
      }

      function loadWishlistCountFromStorage() {
        const savedCount = localStorage.getItem('wishlistCount');
        if (savedCount !== null) {
          updateWishlistCountDisplay(parseInt(savedCount));
        }
      }

      window.addEventListener('storage', function(e) {
        if (e.key === 'wishlistCount') {
          updateWishlistCountDisplay(e.newValue);
        }
      });

      document.addEventListener('alpine:init', function() {
        loadWishlistCount();
      });

      document.addEventListener('DOMContentLoaded', function() {
        loadWishlistCount();
      });

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
                window.location.href = 'index.php';
              }, 1000);
            } else {
              window.location.href = 'index.php';
            }
          })
          .catch(error => {
            console.error('Error logging out:', error);
            window.location.href = 'index.php';
          });
      }

      // Function to load and display coupons
      async function loadCoupons() {
        try {
          const response = await fetch('ajax/coupons.php');

          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const text = await response.text();
          let data;
          try {
            data = JSON.parse(text);
          } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Invalid JSON response from server');
          }

          const loader = document.getElementById('coupon-loader');
          const content = document.getElementById('coupon-content');

          if (!loader || !content) {
            console.error('Coupon elements not found');
            return;
          }

          if (data.success && data.coupons && data.coupons.length > 0) {
            loader.classList.add('hidden');
            content.classList.remove('hidden');
            content.innerHTML = generateCouponHTML(data.coupons);

            if (data.coupons.length > 1) {
              initCouponSlider();
            }
          } else {
            showFallbackMessage(loader, content);
          }
        } catch (error) {
          console.error('Error loading coupons:', error);
          const loader = document.getElementById('coupon-loader');
          const content = document.getElementById('coupon-content');
          if (loader && content) {
            showFallbackMessage(loader, content);
          }
        }
      }

      function generateCouponHTML(coupons) {
        if (!coupons || coupons.length === 0) {
          return '';
        }

        if (coupons.length === 1) {
          const coupon = coupons[0];
          return `
            <div class="flex items-center justify-center">
              <span>🎉</span>
              <span class="ml-2">${generateCouponText(coupon)}</span>
            </div>
          `;
        } else {
          return `
            <div class="coupon-slider relative h-6 overflow-hidden">
              ${coupons.map((coupon, index) => `
                <div class="coupon-slide absolute top-0 left-0 w-full h-full flex items-center justify-center transition-all duration-500 ease-in-out ${
                  index === 0 ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-full'
                }">
                  <span>🎉</span>
                  <span class="ml-2 text-xs md:text-sm">${generateCouponText(coupon)}</span>
                </div>
              `).join('')}
              
              <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 flex space-x-1">
                ${coupons.map((_, index) => `
                  <div class="w-1 h-1 rounded-full bg-white/40 transition-all duration-300 indicator-${
                    index === 0 ? 'active' : 'inactive'
                  }"></div>
                `).join('')}
              </div>
            </div>
          `;
        }
      }

      function generateCouponText(coupon) {
        if (!coupon) return '';

        const minAmount = parseFloat(coupon.min_order_amount) || 0;
        const minAmountText = minAmount > 0 ? ` on Orders Over GHS${minAmount}` : '';

        switch (coupon.discount_type) {
          case 'shipping':
            return `Free Shipping${minAmountText} | Use Code: <strong class="font-bold">${coupon.code}</strong>`;

          case 'percentage':
            const maxDiscount = coupon.max_discount_amount ? ` (Max GHS${coupon.max_discount_amount})` : '';
            return `${coupon.discount_value}% Off${minAmountText}${maxDiscount} | Use Code: <strong class="font-bold">${coupon.code}</strong>`;

          case 'fixed':
            return `GHS${coupon.discount_value} Off${minAmountText} | Use Code: <strong class="font-bold">${coupon.code}</strong>`;

          default:
            return `Special Offer | Use Code: <strong class="font-bold">${coupon.code}</strong>`;
        }
      }

      function initCouponSlider() {
        const slides = document.querySelectorAll('.coupon-slide');
        const indicators = document.querySelectorAll('[class*="indicator-"]');
        let currentSlide = 0;

        if (slides.length <= 1) return;

        function showSlide(index) {
          slides.forEach(slide => {
            slide.classList.remove('opacity-100', 'translate-y-0');
            slide.classList.add('opacity-0', 'translate-y-full');
          });

          indicators.forEach((indicator, i) => {
            if (i === index) {
              indicator.classList.remove('bg-white/40');
              indicator.classList.add('bg-white');
              indicator.classList.remove('w-1', 'h-1');
              indicator.classList.add('w-2', 'h-2');
            } else {
              indicator.classList.remove('bg-white');
              indicator.classList.add('bg-white/40');
              indicator.classList.remove('w-2', 'h-2');
              indicator.classList.add('w-1', 'h-1');
            }
          });

          slides[index].classList.remove('opacity-0', 'translate-y-full');
          slides[index].classList.add('opacity-100', 'translate-y-0');
        }

        function nextSlide() {
          currentSlide = (currentSlide + 1) % slides.length;
          showSlide(currentSlide);
        }

        function randomSlide() {
          let newSlide;
          do {
            newSlide = Math.floor(Math.random() * slides.length);
          } while (newSlide === currentSlide && slides.length > 1);

          currentSlide = newSlide;
          showSlide(currentSlide);
        }

        showSlide(currentSlide);

        setInterval(() => {
          if (Math.random() > 0.5) {
            randomSlide();
          } else {
            nextSlide();
          }
        }, 4000);

        indicators.forEach((indicator, index) => {
          indicator.addEventListener('click', () => {
            currentSlide = index;
            showSlide(currentSlide);
          });
        });
      }

      function showFallbackMessage(loader, content) {
        if (!loader || !content) return;

        loader.classList.add('hidden');
        content.classList.remove('hidden');
        content.innerHTML = `
          <div class="flex items-center justify-center">
            <span>🎉</span>
            <span class="ml-2">Free Shipping on Orders Over GHS500 | Use Code: <strong class="font-bold">CART2025</strong></span>
          </div>
        `;
      }

      document.addEventListener('DOMContentLoaded', function() {
        loadCoupons();
      });

      document.addEventListener('alpine:init', function() {
        loadCoupons();
      });
    </script>