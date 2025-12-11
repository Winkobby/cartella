<?php
// Simple SMTP connectivity tester using mail settings from includes/config.php
// Run from project root: php tools\\test_smtp.php

require_once __DIR__ . '/../includes/config.php';

echo "Testing SMTP connection using settings:\n";
echo "  MAIL_HOST=" . (defined('MAIL_HOST') ? MAIL_HOST : ($_ENV['MAIL_HOST'] ?? '')) . "\n";
echo "  MAIL_PORT=" . (defined('MAIL_PORT') ? MAIL_PORT : ($_ENV['MAIL_PORT'] ?? '')) . "\n";
echo "  MAIL_ENCRYPTION=" . (defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : ($_ENV['MAIL_ENCRYPTION'] ?? '')) . "\n";

$host = defined('MAIL_HOST') ? MAIL_HOST : ($_ENV['MAIL_HOST'] ?? '');
$port = defined('MAIL_PORT') ? MAIL_PORT : ($_ENV['MAIL_PORT'] ?? 0);
$enc = defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : ($_ENV['MAIL_ENCRYPTION'] ?? '');

if (empty($host) || empty($port)) {
    echo "MAIL_HOST or MAIL_PORT not configured. Check your .env.\n";
    exit(2);
}

$transport = (strtolower($enc) === 'ssl' || intval($port) === 465) ? 'ssl' : 'tcp';
$target = sprintf('%s://%s:%s', $transport, $host, $port);

echo "Attempting connection to: $target\n";

$ctx = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
]);

$errno = 0; $errstr = '';
$fp = @stream_socket_client($target, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);

if (!$fp) {
    echo "Connection failed: ($errno) $errstr\n";
    exit(1);
}

stream_set_timeout($fp, 8);
$banner = fgets($fp);
if ($banner !== false) echo "Server banner: " . trim($banner) . "\n";

// Try a simple EHLO/HELO
fwrite($fp, "EHLO localhost\r\n");
// read a few lines
$count = 0;
while (($line = fgets($fp)) !== false && $count < 8) {
    echo "<- " . trim($line) . "\n";
    $count++;
    // stop when last line of response (e.g., '250 OK')
    if (preg_match('/^[0-9]{3} /', $line)) break;
}

fwrite($fp, "QUIT\r\n");
fclose($fp);

echo "Connection test finished. If this fails, check firewall or SMTP credentials.\n";

// Hint for next steps
echo "\nNext: reproduce a completed payment in the app, then run:\n";
echo "  PowerShell: Select-String -Path 'C:\\xampp\\apache\\logs\\error.log' -Pattern 'Attempting to send email','PHPMailer Debug','PHPMailer Exception','Email sent successfully' -SimpleMatch\n";

exit(0);
