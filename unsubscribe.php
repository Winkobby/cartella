<?php
// unsubscribe.php
require_once 'includes/config.php';
require_once 'includes/db.php';

$database = new Database();
$pdo = $database->getConnection();

$message = '';
$message_type = '';

$token = $_GET['token'] ?? '';

if ($token) {
    try {
        $stmt = $pdo->prepare("SELECT email FROM newsletter_subscribers WHERE token = ? AND subscription_status = 'active'");
        $stmt->execute([$token]);
        $subscriber = $stmt->fetch();
        
        if ($subscriber) {
            $updateStmt = $pdo->prepare("UPDATE newsletter_subscribers SET subscription_status = 'inactive', unsubscribed_at = NOW() WHERE token = ?");
            $updateStmt->execute([$token]);
            
            $message = 'You have been successfully unsubscribed from our newsletter.';
            $message_type = 'success';
        } else {
            $message = 'Invalid or expired unsubscribe link.';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred while processing your request.';
        $message_type = 'error';
        error_log("Unsubscribe error: " . $e->getMessage());
    }
} else {
    $message = 'Invalid unsubscribe link.';
    $message_type = 'error';
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Newsletter Unsubscribe</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="p-4 rounded-lg <?php 
                echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 
                     'bg-red-100 text-red-700 border border-red-200';
            ?>">
                <div class="flex items-center">
                    <i class="fas <?php 
                        echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
                    ?> mr-2"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <a href="index.php" class="text-purple-600 hover:text-purple-700 font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back to Homepage
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>