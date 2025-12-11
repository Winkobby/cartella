<?php
require_once __DIR__ . '/config.php'; // Added __DIR__
require_once __DIR__ . '/db.php';

class PaymentProcessor {
    private $db;
    private $provider;

    public function __construct($provider = null) {
        $this->db = new Database();
        $this->provider = $provider ?: PAYMENT_PROVIDER;
    }

    /**
     * Initialize mobile money payment
     */
    public function initializeMobileMoneyPayment($order_id, $amount, $phone, $provider, $email = null, $voucher = null) {
        try {
            switch ($this->provider) {
                case 'paystack':
                    return $this->initializePaystackPayment($order_id, $amount, $phone, $provider, $email, $voucher);
                case 'flutterwave':
                    return $this->initializeFlutterwavePayment($order_id, $amount, $phone, $provider, $email);
                case 'custom':
                    return $this->initializeCustomPayment($order_id, $amount, $phone, $provider, $email);
                default:
                    throw new Exception('Unsupported payment provider: ' . $this->provider);
            }
        } catch (Exception $e) {
            error_log("Payment initialization error: " . $e->getMessage());
            if ($this->provider === 'paystack') {
                try {
                    error_log("Attempting fallback provider: flutterwave");
                    return $this->initializeFlutterwavePayment($order_id, $amount, $phone, $provider, $email);
                } catch (Exception $fe) {
                    error_log("Fallback provider failed: " . $fe->getMessage());
                }
            }
            throw $e;
        }
    }

    /**
     * Initialize Paystack mobile money payment - COMPLETELY REVISED
     */
    private function initializePaystackPayment($order_id, $amount, $phone, $momo_provider, $email = null, $voucher = null) {
        // For Paystack mobile money in Ghana, we need to use the dedicated endpoint
        $url = PAYSTACK_BASE_URL . '/charge';
        
        // Map mobile money providers to Paystack codes
        $provider_codes = [
            'mtn' => 'mtn',
            'vodafone' => 'vodafone',
            'airteltigo' => 'tigo'
        ];

        // Check if we're in test mode - only use test numbers when PAYSTACK_TEST_MODE is enabled
        // and the configured secret key appears to be a test key to avoid overriding live flows
        $use_test_numbers = (defined('PAYSTACK_TEST_MODE') && PAYSTACK_TEST_MODE === true)
            && (is_string(PAYSTACK_SECRET_KEY) && stripos(PAYSTACK_SECRET_KEY, 'sk_test') !== false);

        if ($use_test_numbers) {
            // Paystack REQUIRES specific test phone numbers in test mode
            $test_numbers = [
                'mtn' => '0551234987',
                'vodafone' => '0501234987', 
                'airteltigo' => '0271234987'
            ];
            
            $formatted_phone = $this->formatPhoneNumber($test_numbers[$momo_provider] ?? $test_numbers['mtn']);
            error_log("Using Paystack test number for $momo_provider: $formatted_phone (Original: $phone)");
        } else {
            // Use the actual customer's phone number
            $formatted_phone = $this->formatPhoneNumber($phone);
            error_log("Using customer's phone number: $formatted_phone");
        }

        // Infer provider from phone prefix if possible
        $inferred = $this->inferProviderFromPhone($formatted_phone);
        if ($inferred && $inferred !== $momo_provider) {
            error_log("Overriding provider based on phone prefix: $momo_provider -> $inferred");
            $momo_provider = $inferred;
        }
        
        // Vodafone requires voucher
        if ($momo_provider === 'vodafone' && empty($voucher)) {
            throw new Exception('Vodafone Cash requires a voucher. Please generate a voucher on your phone and enter it.');
        }
        
        // Create a proper email if not provided
        $customer_email = $email ?: 'customer@example.com';
        
        // Create the payload for Paystack mobile money
        $payload = [
            'email' => $customer_email,
            'amount' => intval($amount * 100), // Paystack expects amount in kobo
            'currency' => 'GHS',
            'mobile_money' => [
                'phone' => $formatted_phone,
                'provider' => $provider_codes[$momo_provider] ?? $momo_provider
            ],
            'metadata' => [
                'order_id' => $order_id,
                'custom_fields' => [
                    [
                        'display_name' => 'Order ID',
                        'variable_name' => 'order_id',
                        'value' => $order_id
                    ]
                ]
            ],
            // callback_url set conditionally below
        ];

        if ($momo_provider === 'vodafone' && !empty($voucher)) {
            $payload['mobile_money']['voucher'] = $voucher;
        }

        $callbackUrl = PAYMENT_WEBHOOK_URL . '?provider=paystack';
        if (APP_ENV === 'production' && strpos($callbackUrl, 'localhost') === false) {
            $payload['callback_url'] = $callbackUrl;
        }

        error_log("Paystack API Request: " . json_encode($payload, JSON_PRETTY_PRINT));

        try {
            // Make the API request
            $response = $this->makeApiRequest($url, $payload, PAYSTACK_SECRET_KEY);
            
            error_log("Paystack API Response: " . json_encode($response, JSON_PRETTY_PRINT));
            
            // Check if the response structure is what we expect
            if (!isset($response['status'])) {
                throw new Exception('Invalid response structure from Paystack');
            }
            
            // Treat presence of data as a valid charge attempt regardless of top-level status
            if (!isset($response['data'])) {
                throw new Exception('Missing data field in Paystack response');
            }
            
            $data = $response['data'];
            
            // Check if OTP is required
            $requires_otp = (isset($data['status']) && $data['status'] === 'send_otp') || 
                           (isset($data['display_text']) && stripos($data['display_text'], 'otp') !== false) ||
                           (isset($data['message']) && stripos($data['message'], 'otp') !== false);
            
            // Determine the appropriate message
            $message = $response['message'] ?? 'Charge attempted';
            
            if ($requires_otp) {
                $message = 'OTP sent to your phone. Please check and enter the OTP to complete payment.';
            } elseif (isset($data['status']) && $data['status'] === 'pending') {
                $message = 'Payment prompt sent to your phone. Please check and approve the payment.';
            } elseif (isset($data['status']) && $data['status'] === 'success') {
                $message = 'Payment completed successfully';
            } elseif (isset($data['status'])) {
                $message = 'Payment is being processed: ' . $data['status'];
            }
            
            // Log the transaction for debugging
            error_log("Paystack Transaction: " . json_encode([
                'reference' => $data['reference'] ?? null,
                'status' => $data['status'] ?? null,
                'requires_otp' => $requires_otp,
                'message' => $message
            ]));
            
            $status = $data['status'] ?? null;
            $init_success = in_array($status, ['send_otp','pay_offline','pending','success']);
            if (!$init_success && $status) {
                $message = $data['display_text'] ?? ($data['message'] ?? ('Payment initialization failed: ' . $status));
            }
            return [
                'success' => $init_success,
                'payment_reference' => $data['reference'] ?? null,
                'authorization_url' => $data['authorization_url'] ?? null,
                'message' => $message,
                'provider_data' => $data,
                'requires_otp' => $requires_otp,
                'status' => $status
            ];
            
        } catch (Exception $e) {
            error_log("Paystack initialization error: " . $e->getMessage());
            
            // Only fallback to demo mode if explicitly enabled
            if (defined('ENABLE_DEMO_MODE') && ENABLE_DEMO_MODE === true) {
                error_log("Falling back to demo mode");
                return $this->initializeDemoPayment($order_id, $amount, $phone, $momo_provider, $email);
            }
            
            // Return a more detailed error message
            throw new Exception('Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Demo payment fallback for testing
     */
    private function initializeDemoPayment($order_id, $amount, $phone, $momo_provider, $email = null) {
        $reference = 'DEMO_' . $order_id . '_' . time();
        
        error_log("Using DEMO payment mode for order: $order_id");
        
        return [
            'success' => true,
            'payment_reference' => $reference,
            'authorization_url' => null,
            'message' => 'DEMO MODE: Payment initialized. Please enter OTP: 123456',
            'provider_data' => [
                'reference' => $reference,
                'status' => 'send_otp'
            ],
            'requires_otp' => true,
            'status' => 'send_otp',
            'is_demo' => true
        ];
    }

    /**
     * Initialize Flutterwave mobile money payment
     */
    private function initializeFlutterwavePayment($order_id, $amount, $phone, $momo_provider, $email = null) {
        $url = FLUTTERWAVE_BASE_URL . '/charges?type=mobile_money_ghana';
        
        // Use the actual customer's phone number
        $formatted_phone = $this->formatPhoneNumber($phone);
        
        $payload = [
            'phone_number' => $formatted_phone,
            'amount' => $amount,
            'currency' => 'GHS',
            'email' => $email ?: 'customer@example.com',
            'tx_ref' => 'ORDER_' . $order_id . '_' . time(),
            'network' => strtoupper($momo_provider),
            'meta' => [
                'order_id' => $order_id
            ],
            'redirect_url' => PAYMENT_WEBHOOK_URL . '?provider=flutterwave'
        ];

        $response = $this->makeApiRequest($url, $payload, FLUTTERWAVE_SECRET_KEY);
        
        if ($response['status'] === 'success') {
            return [
                'success' => true,
                'payment_reference' => $response['data']['tx_ref'],
                'authorization_url' => $response['data']['meta']['authorization']['redirect'] ?? null,
                'message' => $response['message'],
                'provider_data' => $response['data'],
                'requires_otp' => isset($response['data']['meta']['authorization']['mode']) && $response['data']['meta']['authorization']['mode'] === 'otp'
            ];
        } else {
            throw new Exception($response['message'] ?? 'Payment initialization failed');
        }
    }

    /**
     * Custom mobile money implementation (for testing/demo)
     */
    private function initializeCustomPayment($order_id, $amount, $phone, $momo_provider, $email = null) {
        // For demo purposes - simulates payment initialization
        $reference = 'CUSTOM_' . $order_id . '_' . time();
        
        // Use the actual customer's phone number
        $formatted_phone = $this->formatPhoneNumber($phone);
        
        // Simulate API call delay
        usleep(500000); // 0.5 seconds
        
        return [
            'success' => true,
            'payment_reference' => $reference,
            'authorization_url' => null,
            'message' => 'Payment request sent to your mobile number: ' . $formatted_phone,
            'provider_data' => [
                'reference' => $reference,
                'status' => 'pending'
            ],
            'requires_otp' => true // Custom provider always requires OTP for demo
        ];
    }

    /**
     * Verify payment status
     */
    public function verifyPayment($payment_reference) {
        try {
            switch ($this->provider) {
                case 'paystack':
                    return $this->verifyPaystackPayment($payment_reference);
                case 'flutterwave':
                    return $this->verifyFlutterwavePayment($payment_reference);
                case 'custom':
                    return $this->verifyCustomPayment($payment_reference);
                default:
                    throw new Exception('Unsupported payment provider: ' . $this->provider);
            }
        } catch (Exception $e) {
            error_log("Payment verification error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify Paystack payment - REVISED
     */
    private function verifyPaystackPayment($reference) {
        $url = PAYSTACK_BASE_URL . '/transaction/verify/' . $reference;
        
        try {
            $response = $this->makeApiRequest($url, [], PAYSTACK_SECRET_KEY, 'GET');
            
            if ($response['status'] === true) {
                $data = $response['data'];
                $paid = $data['status'] === 'success';
                
                return [
                    'success' => true,
                    'status' => $data['status'],
                    'paid' => $paid,
                    'amount' => $data['amount'] / 100, // Convert back from kobo
                    'currency' => $data['currency'],
                    'paid_at' => $data['paid_at'],
                    'message' => $paid ? 'Payment completed successfully' : 'Payment is still pending',
                    'provider_data' => $data
                ];
            } else {
                throw new Exception($response['message'] ?? 'Payment verification failed');
            }
        } catch (Exception $e) {
            error_log("Paystack verification error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify Flutterwave payment
     */
    private function verifyFlutterwavePayment($reference) {
        $url = FLUTTERWAVE_BASE_URL . '/transactions/' . $reference . '/verify';
        
        $response = $this->makeApiRequest($url, [], FLUTTERWAVE_SECRET_KEY, 'GET');
        
        if ($response['status'] === 'success') {
            $data = $response['data'];
            $paid = $data['status'] === 'successful';
            
            return [
                'success' => true,
                'status' => $data['status'],
                'paid' => $paid,
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'paid_at' => $data['created_at'],
                'message' => $paid ? 'Payment completed successfully' : 'Payment is still pending',
                'provider_data' => $data
            ];
        } else {
            throw new Exception($response['message'] ?? 'Payment verification failed');
        }
    }

    /**
     * Verify custom payment (for testing/demo)
     */
    private function verifyCustomPayment($reference) {
        // For demo purposes - simulates payment verification
        usleep(300000); // 0.3 seconds
        
        // Get payment from database to track verification attempts
        $payment = $this->getPaymentByReference($reference);
        
        if (!$payment) {
            throw new Exception('Payment not found');
        }
        
        // Increment verification attempts in database
        $this->db->execute(
            "UPDATE payments SET verification_attempts = verification_attempts + 1, last_verification_attempt = NOW() WHERE payment_id = ?",
            [$payment['payment_id']]
        );
        
        // For demo: succeed after 2 attempts
        if ($payment['verification_attempts'] >= 2) {
            // Mark as paid in database
            $this->updatePaymentStatus(
                $payment['payment_id'],
                'Completed',
                ['reference' => $reference, 'status' => 'success']
            );
            
            return [
                'success' => true,
                'status' => 'success',
                'paid' => true,
                'amount' => $payment['amount'],
                'currency' => 'GHS',
                'paid_at' => date('Y-m-d H:i:s'),
                'message' => 'Payment completed successfully',
                'provider_data' => ['status' => 'success']
            ];
        } else {
            return [
                'success' => true,
                'status' => 'pending',
                'paid' => false,
                'amount' => $payment['amount'],
                'currency' => 'GHS',
                'paid_at' => null,
                'message' => 'Payment is still pending. Please complete the transaction.',
                'provider_data' => ['status' => 'pending']
            ];
        }
    }

    /**
     * Submit OTP and verify mobile money payment - REVISED
     */
    public function submitOtpAndVerifyPayment($reference, $otp_code) {
        try {
            switch ($this->provider) {
                case 'paystack':
                    return $this->submitPaystackOtp($reference, $otp_code);
                case 'flutterwave':
                    return $this->submitFlutterwaveOtp($reference, $otp_code);
                case 'custom':
                    return $this->submitCustomOtp($reference, $otp_code);
                default:
                    throw new Exception('Unsupported payment provider: ' . $this->provider);
            }
        } catch (Exception $e) {
            error_log("OTP submission error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Submit OTP to Paystack - REVISED
     */
    private function submitPaystackOtp($reference, $otp_code) {
        $url = PAYSTACK_BASE_URL . '/charge/submit_otp';
        
        $payload = [
            'otp' => $otp_code,
            'reference' => $reference
        ];

        try {
            $response = $this->makeApiRequest($url, $payload, PAYSTACK_SECRET_KEY);
            
            if ($response['status'] === true) {
                // OTP submitted successfully, wait a moment and verify
                sleep(2);
                
                // Verify payment status after OTP submission
                $verification = $this->verifyPaystackPayment($reference);
                
                // If still pending, wait a bit longer and check again
                if (!$verification['paid']) {
                    sleep(3);
                    $verification = $this->verifyPaystackPayment($reference);
                }
                
                return $verification;
            } else {
                throw new Exception($response['message'] ?? 'OTP submission failed');
            }
        } catch (Exception $e) {
            error_log("Paystack OTP submission error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Submit OTP to Flutterwave
     */
    private function submitFlutterwaveOtp($reference, $otp_code) {
        $url = FLUTTERWAVE_BASE_URL . '/validate-charge';
        
        $payload = [
            'otp' => $otp_code,
            'flw_ref' => $reference
        ];

        $response = $this->makeApiRequest($url, $payload, FLUTTERWAVE_SECRET_KEY);
        
        if ($response['status'] === 'success') {
            // Wait for payment to process
            sleep(3);
            return $this->verifyFlutterwavePayment($reference);
        } else {
            throw new Exception($response['message'] ?? 'OTP submission failed');
        }
    }

    /**
     * Submit OTP for custom provider (demo)
     */
    private function submitCustomOtp($reference, $otp_code) {
        // For demo purposes
        usleep(500000); // 0.5 seconds
        
        // Get payment details
        $payment = $this->getPaymentByReference($reference);
        
        if (!$payment) {
            throw new Exception('Payment not found');
        }
        
        // Simulate OTP verification - accept any 6-digit OTP
        if (strlen($otp_code) === 6 && is_numeric($otp_code)) {
            // Mark as paid in database
            $this->updatePaymentStatus(
                $payment['payment_id'],
                'Completed',
                ['reference' => $reference, 'status' => 'success']
            );
            
            return [
                'success' => true,
                'paid' => true,
                'status' => 'success',
                'amount' => $payment['amount'],
                'currency' => 'GHS',
                'paid_at' => date('Y-m-d H:i:s'),
                'message' => 'Payment verified successfully',
                'provider_data' => ['status' => 'success']
            ];
        } else {
            throw new Exception('Invalid OTP code. Please enter a valid 6-digit code.');
        }
    }

    /**
     * Resend OTP - REVISED
     */
    public function resendOtp($reference) {
        try {
            switch ($this->provider) {
                case 'paystack':
                    return $this->resendPaystackOtp($reference);
                case 'flutterwave':
                    return $this->resendFlutterwaveOtp($reference);
                case 'custom':
                    return $this->resendCustomOtp($reference);
                default:
                    throw new Exception('Unsupported payment provider: ' . $this->provider);
            }
        } catch (Exception $e) {
            error_log("OTP resend error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Resend OTP via Paystack - REVISED
     */
    private function resendPaystackOtp($reference) {
        $url = PAYSTACK_BASE_URL . '/charge/resend_otp';
        
        $payload = [
            'reference' => $reference,
            'reason' => 'token'
        ];

        try {
            $response = $this->makeApiRequest($url, $payload, PAYSTACK_SECRET_KEY);
            
            if ($response['status'] === true) {
                return [
                    'success' => true,
                    'message' => $response['message'] ?? 'OTP resent successfully'
                ];
            } else {
                throw new Exception($response['message'] ?? 'Failed to resend OTP');
            }
        } catch (Exception $e) {
            error_log("Paystack OTP resend error: " . $e->getMessage());
            if (strpos($e->getMessage(), 'HTTP 404') !== false) {
                throw new Exception('OTP resend is not available for this transaction. Please approve the prompt on your phone or use "Resend Payment Request".');
            }
            throw $e;
        }
    }

    /**
     * Resend OTP via Flutterwave
     */
    private function resendFlutterwaveOtp($reference) {
        // Flutterwave typically handles OTP resend automatically
        // This is a placeholder implementation
        return [
            'success' => true,
            'message' => 'OTP has been resent to your mobile number'
        ];
    }

    /**
     * Resend OTP for custom provider (demo)
     */
    private function resendCustomOtp($reference) {
        // For demo purposes
        usleep(300000); // 0.3 seconds
        
        return [
            'success' => true,
            'message' => 'OTP has been resent to your mobile number'
        ];
    }

    /**
     * Retry mobile money payment
     */
    public function retryMobileMoneyPayment($reference, $voucher = null) {
        try {
            switch ($this->provider) {
                case 'paystack':
                    return $this->retryPaystackPayment($reference, $voucher);
                case 'flutterwave':
                    return $this->retryFlutterwavePayment($reference);
                case 'custom':
                    return $this->retryCustomPayment($reference);
                default:
                    throw new Exception('Unsupported payment provider: ' . $this->provider);
            }
        } catch (Exception $e) {
            error_log("Payment retry error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retry Paystack payment
     */
    private function retryPaystackPayment($reference, $voucher = null) {
        $payment = $this->getPaymentByReference($reference);
        if (!$payment) {
            throw new Exception('Payment not found');
        }
        return [
            'success' => false,
            'message' => 'Retry not supported for inline payments. Please initiate payment again.',
        ];
    }

    /**
     * Retry Flutterwave payment
     */
    private function retryFlutterwavePayment($reference) {
        // Verify current payment status
        return $this->verifyFlutterwavePayment($reference);
    }

    /**
     * Retry custom payment
     */
    private function retryCustomPayment($reference) {
        // For demo purposes
        usleep(300000); // 0.3 seconds
        
        return [
            'success' => true,
            'message' => 'Payment request has been resent'
        ];
    }

    /**
     * Make API request to payment provider - COMPLETELY REVISED
     */
    private function makeApiRequest($url, $data = [], $secret_key = '', $method = 'POST') {
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $secret_key,
            'Content-Type: application/json',
            'Cache-Control: no-cache',
        ];

        if (empty($secret_key) || stripos($secret_key, 'your_secret_key_here') !== false) {
            throw new Exception('Payment provider API key is not configured. Set proper keys in .env');
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60, // Increased timeout
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => (APP_ENV === 'production'),
            CURLOPT_SSL_VERIFYHOST => (APP_ENV === 'production') ? 2 : 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ]);

        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $error_no = curl_errno($ch);
        curl_close($ch);

        if ($error) {
            error_log("cURL Error ($error_no): $error");
            throw new Exception('API request failed: ' . $error);
        }

        // Log the raw response for debugging
        error_log("Raw API Response: $response");
        if (defined('LOG_PATH')) {
            @file_put_contents(LOG_PATH . '/paystack_http.log', date('Y-m-d H:i:s') . " | $method $url | HTTP $http_code | " . json_encode($data) . " | " . $response . "\n", FILE_APPEND);
        }

        // Try to parse JSON regardless of HTTP code
        $result = json_decode($response, true);

        // If provider returned JSON with a status flag, prefer provider semantics over HTTP code
        if (is_array($result) && array_key_exists('status', $result)) {
            if ($result['status'] === true) {
                return $result;
            }
            // Some Paystack flows may return HTTP 4xx with a body containing data/reference/status
            if (isset($result['data']) && is_array($result['data']) && (isset($result['data']['reference']) || isset($result['data']['status']))) {
                return $result;
            }
            $detail = $result['message'] ?? ($result['data']['message'] ?? null);
            if (!$detail && isset($result['data']) && is_array($result['data'])) {
                $detail = json_encode($result['data']);
            }
            throw new Exception('Provider error: ' . ($detail ?: 'Unknown error'));
        }

        // If JSON parsing failed, report error with raw snippet
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Parse Error: " . json_last_error_msg());
            throw new Exception('Invalid JSON response from payment provider. Raw response: ' . $response);
        }

        // Fall back to HTTP code handling when no status is present
        if ($http_code >= 400) {
            $msg = "API request failed with HTTP $http_code";
            $detail = $result['message'] ?? ($result['data']['message'] ?? null);
            if (!$detail && isset($result['data']) && is_array($result['data'])) {
                $detail = json_encode($result['data']);
            }
            if ($detail) {
                $msg .= ": " . $detail;
            } else {
                $snippet = substr($response, 0, 200);
                $msg .= ": " . $snippet;
            }
            throw new Exception($msg);
        }

        return $result;
    }

    /**
     * Format phone number for payment providers - REVISED
     */
    private function formatPhoneNumber($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // Handle Ghana phone numbers
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            // Local format: 0501234567 -> 233501234567
            $phone = '233' . substr($phone, 1);
        } elseif (strlen($phone) === 9) {
            // Partial format: 501234567 -> 233501234567
            $phone = '233' . $phone;
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '233') {
            // Already in international format: 233501234567
            // No change needed
        } else {
            // Invalid format, but we'll try to use it anyway
            error_log("Unusual phone number format: $phone");
        }
        
        return $phone;
    }

    private function inferProviderFromPhone($phone) {
        // phone is in 233XXXXXXXXX format
        $digits = preg_replace('/\D/', '', $phone);
        $prefix = substr($digits, 3, 3); // first 3 digits after 233
        $mtn = ['024','054','055','059'];
        $vodafone = ['020','050'];
        $tigo = ['027','057','026'];
        if (in_array('0'.$prefix, $mtn)) return 'mtn';
        if (in_array('0'.$prefix, $vodafone)) return 'vodafone';
        if (in_array('0'.$prefix, $tigo)) return 'airteltigo';
        return null;
    }

    private function logToFile($file, $data) {
        if (!defined('LOG_PATH')) return;
        @file_put_contents(LOG_PATH . '/' . $file . '.log', date('Y-m-d H:i:s') . ' | ' . json_encode($data) . "\n", FILE_APPEND);
    }

    /**
     * Get payment by reference
     */
    public function getPaymentByReference($reference) {
        $sql = "SELECT * FROM payments WHERE provider_reference = ? OR transaction_id = ?";
        return $this->db->fetchSingle($sql, [$reference, $reference]);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($payment_id, $status, $provider_data = null) {
        $sql = "UPDATE payments 
                SET payment_status = ?, 
                    transaction_id = COALESCE(?, transaction_id),
                    provider_reference = COALESCE(?, provider_reference),
                    verification_attempts = verification_attempts + 1,
                    last_verification_attempt = NOW(),
                    updated_at = NOW()
                WHERE payment_id = ?";
        
        $provider_ref = $provider_data['reference'] ?? null;
        $transaction_id = $provider_data['id'] ?? $provider_data['transaction_id'] ?? null;
        
        return $this->db->execute($sql, [$status, $transaction_id, $provider_ref, $payment_id]);
    }

    /**
     * Log webhook/API response
     */
    public function logWebhook($payment_id, $event_type, $payload) {
        $sql = "INSERT INTO payment_webhooks (payment_id, provider, event_type, payload) 
                VALUES (?, ?, ?, ?)";
        
        return $this->db->insert($sql, [
            $payment_id, 
            $this->provider, 
            $event_type, 
            json_encode($payload)
        ]);
    }

    /**
     * Check if payment requires OTP
     */
    public function checkOtpRequirement($reference) {
        try {
            $payment = $this->getPaymentByReference($reference);
            if (!$payment) {
                return false;
            }

            switch ($this->provider) {
                case 'paystack':
                    $verification = $this->verifyPaystackPayment($reference);
                    return $verification['status'] === 'send_otp' || isset($verification['provider_data']['otp_sent']);
                case 'flutterwave':
                    $verification = $this->verifyFlutterwavePayment($reference);
                    return isset($verification['provider_data']['auth_model']) && $verification['provider_data']['auth_model'] === 'OTP';
                case 'custom':
                    return true; // Custom provider always requires OTP for demo
                default:
                    return false;
            }
        } catch (Exception $e) {
            error_log("OTP requirement check error: " . $e->getMessage());
            return false;
        }
    }
}
?>