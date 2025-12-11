<?php
// Fix the path to vendor/autoload.php
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    // Try alternative paths
    $vendorPath = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($vendorPath)) {
        // If vendor directory doesn't exist, create a fallback
        error_log("PHPMailer vendor/autoload.php not found. Email functionality disabled.");
        
        // Create a dummy class to prevent fatal errors
        class PHPMailerWrapper {
            public function __construct() {}
            public function sendHtml($to, $subject, $html) {
                error_log("Email disabled: vendor/autoload.php not found. Attempted to send to: $to");
                return false;
            }
        }
        return;
    }
}

require_once $vendorPath;

class PHPMailerWrapper {
    private $mailer;
    private $debug = true; // Set to true for debugging

    public function __construct() {
        $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Enable debugging if needed
        if ($this->debug) {
            $this->mailer->SMTPDebug = 2; // Enable verbose debug output
            $this->mailer->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug: $str");
            };
        } else {
            $this->mailer->SMTPDebug = 0;
        }
        
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['MAIL_USER'] ?? '';
        $this->mailer->Password = $_ENV['MAIL_PASS'] ?? '';
        $this->mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $this->mailer->Port = $_ENV['MAIL_PORT'] ?? 465;
        
        // SSL options - be careful with this in production
        $this->mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ];
        
        // Increase timeout
        $this->mailer->Timeout = 30;
        
        // Sender
        $sender_email = $_ENV['MAIL_USER'] ?? 'no-reply@cartella.local';
        $this->mailer->setFrom($sender_email, 'Cartella Store');
        $this->mailer->addReplyTo($sender_email, 'Cartella Store');
        
        // Enable exceptions
        $this->mailer->isHTML(true);
    }

    public function sendHtml($to, $subject, $html) {
        try {
            // Clear all addresses
            $this->mailer->clearAllRecipients();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();
            
            // Set recipient
            $this->mailer->addAddress($to);
            
            // Set subject and body
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $html;
            $this->mailer->AltBody = strip_tags($html);
            
            error_log("Attempting to send email to: $to with subject: $subject");
            
            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Email sent successfully to: $to");
            } else {
                error_log("Email sending failed to: $to. Error: " . $this->mailer->ErrorInfo);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("PHPMailer Exception for $to: " . $e->getMessage());
            error_log("PHPMailer Error Info: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
}
?>