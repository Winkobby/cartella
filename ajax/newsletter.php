<?php
// ajax/newsletter.php

// Turn off all error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Start output buffering to catch any stray output
ob_start();

try {
    // Start session if needed
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    
    // Initialize response
    $response = [
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ];
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        throw new Exception('Please provide a valid email address');
    }
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if email already exists
    $checkStmt = $pdo->prepare("SELECT subscriber_id, subscription_status FROM newsletter_subscribers WHERE email = ?");
    $checkStmt->execute([$email]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        if ($existing['subscription_status'] === 'active') {
            $response = [
                'success' => false,
                'message' => 'This email is already subscribed to our newsletter.'
            ];
        } else {
            // Reactivate existing subscription
            $updateStmt = $pdo->prepare("UPDATE newsletter_subscribers SET subscription_status = 'active', unsubscribed_at = NULL, updated_at = NOW() WHERE subscriber_id = ?");
            $updateStmt->execute([$existing['subscriber_id']]);
            
            $response = [
                'success' => true,
                'message' => 'Successfully resubscribed to our newsletter!'
            ];
        }
    } else {
        // Generate unique token for unsubscribe links
        $token = bin2hex(random_bytes(32));
        
        // Insert new subscriber
        $insertStmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, token, subscription_status) VALUES (?, ?, 'active')");
        $insertStmt->execute([$email, $token]);
        
        $response = [
            'success' => true,
            'message' => 'Thank you for subscribing to our newsletter!'
        ];
    }
    
} catch (Exception $e) {
    error_log("Newsletter subscription error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

// Clear any output that might have been generated
ob_end_clean();

// Output only JSON
echo json_encode($response);
exit;