<?php
class Mailer {
    private $fromEmail;
    private $fromName;
    private $debugLog = [];

    public function __construct($fromEmail = null, $fromName = null) {
        $this->fromEmail = $fromEmail ?: ($_ENV['MAIL_USER'] ?? 'no-reply@cartella.local');
        $this->fromName = $fromName ?: (defined('APP_NAME') ? APP_NAME : 'Cartella');
        if (stripos(PHP_OS, 'WIN') !== false) {
            if (!empty($_ENV['MAIL_HOST'])) {
                ini_set('SMTP', $_ENV['MAIL_HOST']);
            }
            if (!empty($_ENV['MAIL_PORT'])) {
                ini_set('smtp_port', $_ENV['MAIL_PORT']);
            }
            ini_set('sendmail_from', $this->fromEmail);
        }
    }

    public function sendHtml($to, $subject, $html) {
        $this->debugLog = [];
        $this->debug("Starting email send to: " . $to);
        
        $useSmtp = !empty($_ENV['MAIL_HOST']) && !empty($_ENV['MAIL_USER']) && !empty($_ENV['MAIL_PASS']);
        if ($useSmtp) {
            $this->debug("Attempting SMTP send");
            $ok = $this->sendSmtp($to, $subject, $html);
            if ($ok) {
                $this->debug("SMTP send successful");
                return true;
            }
            $this->debug("SMTP send failed, falling back to mail()");
        }
        
        $this->debug("Using mail() function");
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $this->fromName . ' <' . $this->fromEmail . '>';
        $headers[] = 'Reply-To: ' . $this->fromEmail;
        $headersStr = implode("\r\n", $headers);
        
        $sent = @mail($to, $subject, $html, $headersStr);
        
        if (!$sent) {
            $this->debug("mail() function also failed");
            $this->saveToFile($to, $subject, $html);
        }
        
        $this->writeDebugLog();
        return $sent;
    }

    private function sendSmtp($to, $subject, $html) {
        $host = $_ENV['MAIL_HOST'];
        $port = intval($_ENV['MAIL_PORT'] ?? 587);
        $user = $_ENV['MAIL_USER'];
        $pass = $_ENV['MAIL_PASS'];
        $enc = strtolower($_ENV['MAIL_ENCRYPTION'] ?? 'tls');
        $domain = parse_url($_ENV['APP_URL'] ?? 'http://localhost', PHP_URL_HOST) ?: 'localhost';

        $this->debug("Connecting to $host:$port with encryption: $enc");

        $transport = ($enc === 'ssl') ? 'ssl://' . $host : $host;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $fp = @stream_socket_client($transport . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
        
        if (!$fp) {
            $this->debug("Connection failed: $errstr ($errno)");
            return false;
        }
        $this->debug("Connected successfully");

        stream_set_timeout($fp, 20);

        $read = function() use ($fp) { 
            $line = fgets($fp, 512); 
            $this->debug("SERVER: " . trim($line));
            return $line;
        };
        $write = function($cmd) use ($fp) { 
            $this->debug("CLIENT: " . trim($cmd));
            fwrite($fp, $cmd . "\r\n"); 
        };

        $banner = $read();
        if (strpos($banner, '220') !== 0) { 
            $this->debug("Invalid banner: $banner");
            fclose($fp); 
            return false; 
        }

        $write('EHLO ' . $domain);
        for ($i = 0; $i < 10; $i++) {
            $line = $read();
            if ($line === false) break;
            if (substr($line, 3, 1) !== '-') break;
        }

        if ($enc === 'tls') {
            $write('STARTTLS');
            $resp = $read();
            if (strpos($resp, '220') !== 0) { 
                $this->debug("STARTTLS failed: $resp");
                fclose($fp); 
                return false; 
            }
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { 
                $this->debug("TLS handshake failed");
                fclose($fp); 
                return false; 
            }
            $this->debug("TLS enabled");
            $write('EHLO ' . $domain);
            for ($i = 0; $i < 10; $i++) {
                $line = $read();
                if ($line === false) break;
                if (substr($line, 3, 1) !== '-') break;
            }
        }

        $write('AUTH LOGIN');
        $resp = $read();
        if (strpos($resp, '334') !== 0) { 
            $this->debug("AUTH LOGIN failed: $resp");
            fclose($fp); 
            return false; 
        }
        
        $write(base64_encode($user));
        $resp = $read();
        if (strpos($resp, '334') !== 0) { 
            $this->debug("Username auth failed: $resp");
            fclose($fp); 
            return false; 
        }
        
        $write(base64_encode($pass));
        $resp = $read();
        if (strpos($resp, '235') !== 0) { 
            $this->debug("Password auth failed: $resp");
            fclose($fp); 
            return false; 
        }
        $this->debug("Authentication successful");

        $write('MAIL FROM:<' . $this->fromEmail . '>');
        if (strpos($read(), '250') !== 0) { 
            $this->debug("MAIL FROM failed");
            fclose($fp); 
            return false; 
        }
        
        $write('RCPT TO:<' . $to . '>');
        if (strpos($read(), '250') !== 0) { 
            $this->debug("RCPT TO failed");
            fclose($fp); 
            return false; 
        }
        
        $write('DATA');
        if (strpos($read(), '354') !== 0) { 
            $this->debug("DATA failed");
            fclose($fp); 
            return false; 
        }

        $headers = [];
        $headers[] = 'From: ' . $this->fromName . ' <' . $this->fromEmail . '>';
        $headers[] = 'To: ' . $to;
        $headers[] = 'Subject: ' . $subject;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Reply-To: ' . $this->fromEmail;
        $msg = implode("\r\n", $headers) . "\r\n\r\n" . $html . "\r\n.";
        
        $write($msg);
        if (strpos($read(), '250') !== 0) { 
            $this->debug("Message send failed");
            fclose($fp); 
            return false; 
        }
        
        $write('QUIT');
        fclose($fp);
        $this->debug("Email sent successfully via SMTP");
        return true;
    }

    private function debug($message) {
        $this->debugLog[] = date('Y-m-d H:i:s') . ' - ' . $message;
        error_log("Mailer Debug: " . $message);
    }

    private function writeDebugLog() {
        $logFile = __DIR__ . '/../logs/mailer_debug.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logFile, implode("\n", $this->debugLog) . "\n\n", FILE_APPEND | LOCK_EX);
    }

    private function saveToFile($to, $subject, $html) {
        $fn = __DIR__ . '/../logs/emails';
        if (!is_dir($fn)) {
            @mkdir($fn, 0755, true);
        }
        $name = preg_replace('/[^a-zA-Z0-9\-_]/', '_', substr($subject, 0, 40));
        $file = $fn . '/' . date('Ymd_His') . '_' . $name . '.eml';
        $raw = 'To: ' . $to . "\r\n" . 'Subject: ' . $subject . "\r\n\r\n" . $html;
        @file_put_contents($file, $raw);
        $this->debug("Email saved to file: " . $file);
    }
}
?>