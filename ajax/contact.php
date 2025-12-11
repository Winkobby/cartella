<?php
// ajax/contact.php - Handle contact form submissions
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/settings_helper.php';
require_once '../includes/Mailer.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Initialize database
    $database = new Database();
    $pdo = $database->getConnection();
    SettingsHelper::init($pdo);
    
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters';
    } elseif (strlen($name) > 255) {
        $errors[] = 'Name is too long';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (!empty($phone) && strlen($phone) > 50) {
        $errors[] = 'Phone number is too long';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    } elseif (strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters';
    } elseif (strlen($message) > 5000) {
        $errors[] = 'Message is too long (maximum 5000 characters)';
    }
    
    // Check for errors
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode('<br>', $errors)
        ]);
        exit;
    }
    
    // Get IP address and user agent
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Save to database
    $stmt = $pdo->prepare("
        INSERT INTO contacts (name, email, phone, subject, message, ip_address, user_agent, status, created_at)
        VALUES (:name, :email, :phone, :subject, :message, :ip_address, :user_agent, 'new', NOW())
    ");
    
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':subject' => $subject,
        ':message' => $message,
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent
    ]);
    
    $contact_id = $pdo->lastInsertId();
    
    // Get site settings
    $site_name = SettingsHelper::get($pdo, 'site_name', SITE_NAME);
    $contact_email = SettingsHelper::get($pdo, 'contact_email', 'contact@' . $_SERVER['HTTP_HOST']);
    $site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    
    // Initialize mailer
    $mailer = new Mailer();
    
    // 1. Send notification email to admin
    $adminSubject = "New Contact Form Submission - {$subject}";
    $adminHtml = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .info-row { margin: 15px 0; padding: 12px; background: white; border-left: 4px solid #667eea; }
            .label { font-weight: bold; color: #667eea; display: block; margin-bottom: 5px; }
            .message-box { background: white; padding: 20px; border-radius: 8px; margin-top: 20px; border: 1px solid #e0e0e0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>📬 New Contact Form Submission</h1>
            </div>
            <div class='content'>
                <p><strong>You have received a new message from your contact form.</strong></p>
                
                <div class='info-row'>
                    <span class='label'>From:</span>
                    <span>{$name}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Email:</span>
                    <span><a href='mailto:{$email}'>{$email}</a></span>
                </div>
                
                " . (!empty($phone) ? "
                <div class='info-row'>
                    <span class='label'>Phone:</span>
                    <span>{$phone}</span>
                </div>
                " : "") . "
                
                <div class='info-row'>
                    <span class='label'>Subject:</span>
                    <span>{$subject}</span>
                </div>
                
                <div class='message-box'>
                    <span class='label'>Message:</span>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
                
                <p style='margin-top: 20px; color: #666; font-size: 13px;'>
                    <strong>Submission Details:</strong><br>
                    Date: " . date('F j, Y, g:i a') . "<br>
                    IP Address: {$ip_address}<br>
                    Contact ID: #{$contact_id}
                </p>
            </div>
            <div class='footer'>
                <p>This is an automated notification from {$site_name}</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $adminEmailSent = $mailer->sendHtml($contact_email, $adminSubject, $adminHtml);
    
    // 2. Send auto-response to user
    $userSubject = "We received your message - {$site_name}";
    $userHtml = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0; font-size: 28px;'>✅ Thank You for Contacting Us!</h1>
            </div>
            <div class='content'>
                <p>Hi <strong>{$name}</strong>,</p>
                
                <p>Thank you for reaching out to us. We've received your message and our team will review it shortly.</p>
                
                <div class='info-box'>
                    <h3 style='margin-top: 0; color: #667eea;'>📝 Your Message Summary</h3>
                    <p><strong>Subject:</strong> {$subject}</p>
                    <p><strong>Date:</strong> " . date('F j, Y, g:i a') . "</p>
                    <p><strong>Reference ID:</strong> #" . str_pad($contact_id, 6, '0', STR_PAD_LEFT) . "</p>
                </div>
                
                <p><strong>What happens next?</strong></p>
                <ul>
                    <li>Our support team will review your message</li>
                    <li>We typically respond within 24-48 hours</li>
                    <li>You'll receive a reply at this email address: <strong>{$email}</strong></li>
                </ul>
                
                <p>If your inquiry is urgent, please feel free to call us or check our FAQ page for immediate answers.</p>
                
                <div style='text-align: center;'>
                    <a href='{$site_url}/faq.php' class='button' style='color: white;'>Visit FAQ</a>
                </div>
                
                <p style='margin-top: 30px;'>Thank you for your patience!</p>
                
                <p>Best regards,<br>
                <strong>The {$site_name} Team</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated response from {$site_name}</p>
                <p>Please do not reply to this email. We'll contact you at {$email}</p>
                <p><a href='{$site_url}' style='color: #667eea;'>Visit our website</a></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $userEmailSent = $mailer->sendHtml($email, $userSubject, $userHtml);
    
    // Log email results
    error_log("Contact form submission #{$contact_id}: Admin notification " . ($adminEmailSent ? 'sent' : 'failed') . ", User auto-response " . ($userEmailSent ? 'sent' : 'failed'));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We\'ll get back to you soon.',
        'contact_id' => $contact_id
    ]);
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request. Please try again later.'
    ]);
}
