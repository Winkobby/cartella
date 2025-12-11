<?php
require_once 'includes/config.php';
// Start session so links to signin work properly
if (session_status() === PHP_SESSION_NONE) session_start();

$page_title = 'Site Under Maintenance';
require_once 'includes/header.php';
?>

<div class="flex items-center justify-center py-12">
  <div class="max-w-2xl w-full bg-white shadow-lg rounded-lg p-8 text-center">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">We&rsquo;ll be back soon</h1>
    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars(SITE_NAME); ?> is currently undergoing scheduled maintenance. Purchases are temporarily disabled while we perform improvements.</p>

    <div class="mb-6">
      <p class="text-sm text-gray-500">If you are an administrator, <a href="a_signin.php" class="text-purple-600 font-medium">sign in here</a> to access the admin dashboard.</p>
    </div>

    <div class="space-y-3">
      <a href="index.php" class="inline-block px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Return to Home</a>
      <a href="contact.php" class="inline-block px-6 py-2 border border-gray-200 text-gray-700 rounded-md hover:bg-gray-50">Contact Support</a>
    </div>

    <?php
    // Determine maintenance start/end timestamps if available
    $maintenance_start_ts = (defined('MAINTENANCE_START') && MAINTENANCE_START) ? strtotime(MAINTENANCE_START) : null;
    $maintenance_end_ts = (defined('MAINTENANCE_END') && MAINTENANCE_END) ? strtotime(MAINTENANCE_END) : null;

    $start_display = $maintenance_start_ts ? date('Y-m-d H:i', $maintenance_start_ts) : null;
    $end_display = $maintenance_end_ts ? date('Y-m-d H:i', $maintenance_end_ts) : null;
    ?>

    <div class="mt-6 text-sm text-gray-700">
      <?php if ($start_display && $end_display): ?>
        <div class="mb-2">Scheduled maintenance window:</div>
        <div class="mb-1"><strong>Starts:</strong> <?php echo htmlspecialchars($start_display); ?></div>
        <div class="mb-3"><strong>Ends:</strong> <?php echo htmlspecialchars($end_display); ?></div>
      <?php elseif ($end_display): ?>
        <div class="mb-3"><strong>Scheduled to end:</strong> <?php echo htmlspecialchars($end_display); ?></div>
      <?php else: ?>
        <div class="mb-3">Estimated downtime: a few minutes. Thank you for your patience.</div>
      <?php endif; ?>

      <div id="maintenance-countdown" class="text-lg font-medium text-gray-800 mt-3"></div>
    </div>

    <script>
      (function(){
        // Prefer server-provided timestamps, but fall back to window.MAINTENANCE_END/START if present
        var startTs = <?php echo $maintenance_start_ts ? ($maintenance_start_ts * 1000) : 'null'; ?>;
        var endTs = <?php echo $maintenance_end_ts ? ($maintenance_end_ts * 1000) : 'null'; ?>;

        if (!startTs && window.MAINTENANCE_START) {
          try { startTs = new Date(window.MAINTENANCE_START).getTime(); } catch(e) {}
        }
        if (!endTs && window.MAINTENANCE_END) {
          try { endTs = new Date(window.MAINTENANCE_END).getTime(); } catch(e) {}
        }

        var el = document.getElementById('maintenance-countdown');
        if (!el) return;

        function formatDuration(ms) {
          if (ms <= 0) return '00:00:00';
          var total = Math.floor(ms / 1000);
          var hours = Math.floor(total / 3600);
          var mins = Math.floor((total % 3600) / 60);
          var secs = total % 60;
          return String(hours).padStart(2,'0') + ':' + String(mins).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
        }

        function update() {
          var now = Date.now();

          if (startTs && now < startTs) {
            // Counting down to start
            var remaining = startTs - now;
            el.textContent = 'Maintenance starts in: ' + formatDuration(remaining);
            return true; // still active countdown
          }

          if (endTs && now < endTs) {
            // Counting down to end
            var remaining = endTs - now;
            el.textContent = 'Maintenance ends in: ' + formatDuration(remaining);
            return true;
          }

          // No active countdown or maintenance has ended
          el.textContent = 'We are working — thank you for your patience.';
          return false;
        }

        // Update immediately and then every second while countdown active
        var active = update();
        var interval = null;
        if (active) {
          interval = setInterval(function(){
            if (!update()) {
              clearInterval(interval);
            }
          }, 1000);
        }
      })();
    </script>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
