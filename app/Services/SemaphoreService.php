<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SemaphoreService
{
    protected $apiKey;
    protected $senderName;
    protected $baseUrl;
    protected $maxRequestsPerMinute;
    protected $delayBetweenRequests;
    protected $maxConcurrentRequests;
    protected $lockKey = 'semaphore_sms_lock';
    protected $rateLimitKey = 'semaphore_sms_rate_limit';

    public function __construct()
    {
        $this->apiKey = config('services.semaphore.api_key');
        $this->senderName = config('services.semaphore.sender_name');
        $this->baseUrl = config('services.semaphore.base_url');
        $this->maxRequestsPerMinute = config('services.semaphore.max_requests_per_minute', 30);
        $this->delayBetweenRequests = config('services.semaphore.delay_between_requests', 2000);
        $this->maxConcurrentRequests = config('services.semaphore.max_concurrent_requests', 1);
        
        // Validate configuration
        if (empty($this->apiKey)) {
            Log::error('Semaphore SMS: API key is not configured');
        }
        if (empty($this->baseUrl)) {
            Log::error('Semaphore SMS: Base URL is not configured');
        }
    }

    /**
     * Send SMS message
     *
     * @param string|array $number Phone number(s) - format: 09xxxxxxxxx or +639xxxxxxxxx
     * @param string $message Message content
     * @return array Response from API
     */
    public function sendSMS(string $number, string $message): array
    {
        try {
            // Validate API key
            if (empty($this->apiKey)) {
                Log::error('Semaphore SMS: API key is missing');
                return [
                    'success' => false,
                    'message' => 'API key is not configured. Please set SEMAPHORE_API_KEY in your .env file.',
                    'data' => null
                ];
            }

            // Validate base URL
            if (empty($this->baseUrl)) {
                Log::error('Semaphore SMS: Base URL is missing');
                return [
                    'success' => false,
                    'message' => 'Base URL is not configured. Please set SEMAPHORE_BASE_URL in your .env file.',
                    'data' => null
                ];
            }

            // Validate phone number before sending
            $formattedNumber = $this->formatPhoneNumber($number);
            
            if (!$this->validatePhoneNumber($formattedNumber)) {
                Log::warning('Semaphore SMS: Invalid phone number format', [
                    'original_number' => $number,
                    'formatted_number' => $formattedNumber
                ]);

                return [
                    'success' => false,
                    'message' => 'Invalid phone number format',
                    'data' => null
                ];
            }

            // Build request payload
            $payload = [
                'apikey' => $this->apiKey,
                'number' => $formattedNumber,
                'message' => $message,
            ];
            
            // Include sendername if configured (required for some Semaphore accounts)
            if (!empty($this->senderName)) {
                $payload['sendername'] = $this->senderName;
            }

            // Log request details (without API key for security)
            Log::info('Semaphore SMS: Sending request', [
                'base_url' => $this->baseUrl,
                'endpoint' => '/messages',
                'number' => $formattedNumber,
                'message_length' => strlen($message),
                'sender_name' => $this->senderName,
                'has_api_key' => !empty($this->apiKey)
            ]);

            // Make HTTP request with timeout
            $response = Http::timeout(30)
                ->retry(2, 1000) // Retry twice with 1 second delay
                ->post("{$this->baseUrl}/messages", $payload);

            $result = $response->json();
            $statusCode = $response->status();
            $responseBody = $response->body();

            // Log detailed response for debugging
            Log::info('Semaphore SMS API Response', [
                'original_number' => $number,
                'formatted_number' => $formattedNumber,
                'status_code' => $statusCode,
                'response_body' => $result,
                'response_body_raw' => $responseBody,
                'http_successful' => $response->successful(),
                'response_type' => gettype($result)
            ]);

            // STRICT SUCCESS DETECTION - Only mark as successful if we have clear confirmation
            $isSuccessful = false;
            $statusMessage = 'Unknown status';

            // Only proceed if HTTP request was successful
            if (!$response->successful()) {
                $statusMessage = $result['message'] ?? "HTTP Error: {$statusCode}";
                Log::error('Semaphore SMS HTTP Error', [
                    'status_code' => $statusCode,
                    'response' => $result,
                    'number' => $formattedNumber
                ]);

                return [
                    'success' => false,
                    'data' => $result,
                    'message' => $statusMessage,
                    'formatted_number' => $formattedNumber
                ];
            }

            // Handle array response (Semaphore API v4 standard format)
            if (is_array($result)) {
                // PRIORITY 1: Check for explicit error indicators FIRST
                if (isset($result['error'])) {
                    $statusMessage = $result['error'];
                    Log::error('Semaphore SMS API Error (error key)', [
                        'error' => $result['error'],
                        'number' => $formattedNumber,
                        'full_response' => $result
                    ]);
                }
                // Check for error with code and message
                elseif (isset($result['code']) && isset($result['message'])) {
                    // Check if code indicates an error (non-zero codes are usually errors)
                    if ($result['code'] != 0 && $result['code'] != '0') {
                        $statusMessage = $result['message'];
                        Log::error('Semaphore SMS API Error', [
                            'error_code' => $result['code'],
                            'error_message' => $result['message'],
                            'number' => $formattedNumber,
                            'full_response' => $result
                        ]);
                    } else {
                        // Code is 0, which might indicate success, but we need message_id to be sure
                        // Don't mark as successful yet - wait for message_id check
                        $statusMessage = $result['message'] ?? 'Request processed';
                    }
                }
                // PRIORITY 2: Check if it's an array of message results (most common format)
                elseif (isset($result[0]) && is_array($result[0])) {
                    $firstResult = $result[0];
                    
                    // Check for message_id - this is the STRONGEST indicator of success
                    if (isset($firstResult['message_id']) && !empty($firstResult['message_id'])) {
                        $isSuccessful = true;
                        $statusMessage = 'Queued successfully';
                        Log::info('Semaphore SMS: Message queued successfully', [
                            'message_id' => $firstResult['message_id'],
                            'number' => $formattedNumber
                        ]);
                    }
                    // Check for status field
                    elseif (isset($firstResult['status'])) {
                        $status = $firstResult['status'];
                        // Only accept specific success statuses
                        if (in_array(strtolower($status), ['queued', 'pending', 'sent'])) {
                            $isSuccessful = true;
                            $statusMessage = $status;
                        } else {
                            $statusMessage = $firstResult['message'] ?? $firstResult['error'] ?? $status;
                            Log::warning('Semaphore SMS: Non-success status', [
                                'status' => $status,
                                'number' => $formattedNumber,
                                'full_response' => $firstResult
                            ]);
                        }
                    }
                    // Check for error in first result
                    elseif (isset($firstResult['error'])) {
                        $statusMessage = $firstResult['error'];
                        Log::error('Semaphore SMS API Error in result array', [
                            'error' => $firstResult['error'],
                            'number' => $formattedNumber,
                            'full_response' => $firstResult
                        ]);
                    }
                    // Check for message field (might be error message)
                    elseif (isset($firstResult['message'])) {
                        $statusMessage = $firstResult['message'];
                        Log::warning('Semaphore SMS: Response contains message field', [
                            'message' => $firstResult['message'],
                            'number' => $formattedNumber,
                            'full_response' => $firstResult
                        ]);
                    }
                } 
                // PRIORITY 3: Handle single result object (not in array)
                elseif (isset($result['message_id']) && !empty($result['message_id'])) {
                    $isSuccessful = true;
                    $statusMessage = 'Queued successfully';
                    Log::info('Semaphore SMS: Message queued successfully (single object)', [
                        'message_id' => $result['message_id'],
                        'number' => $formattedNumber
                    ]);
                }
                elseif (isset($result['status'])) {
                    $status = $result['status'];
                    // Only accept specific success statuses
                    if (in_array(strtolower($status), ['queued', 'pending', 'sent'])) {
                        $isSuccessful = true;
                        $statusMessage = $status;
                    } else {
                        $statusMessage = $result['message'] ?? $status;
                        Log::warning('Semaphore SMS: Non-success status (single object)', [
                            'status' => $status,
                            'number' => $formattedNumber,
                            'full_response' => $result
                        ]);
                    }
                }
            }
            // Handle string response - be very cautious
            elseif (is_string($result)) {
                $lowercaseResult = strtolower(trim($result));
                // Only mark as successful if we have explicit success indicators
                if (in_array($lowercaseResult, ['success', 'queued', 'sent', 'ok'])) {
                    $isSuccessful = true;
                }
                $statusMessage = $result;
                Log::info('Semaphore SMS: String response received', [
                    'response' => $result,
                    'number' => $formattedNumber,
                    'marked_successful' => $isSuccessful
                ]);
            }

            // FINAL VALIDATION: If we got a 200 response but couldn't confirm success,
            // mark as failed and log warning
            if (!$isSuccessful && $statusCode === 200) {
                // Check if result is null or empty
                if ($result === null || (is_array($result) && empty($result))) {
                    $statusMessage = 'Empty response from API';
                    Log::warning('Semaphore SMS: HTTP 200 but empty response', [
                        'status_code' => $statusCode,
                        'response_body' => $responseBody,
                        'number' => $formattedNumber
                    ]);
                } else {
                    Log::warning('Semaphore SMS: HTTP 200 but no success confirmation', [
                        'status_code' => $statusCode,
                        'response' => $result,
                        'response_type' => gettype($result),
                        'response_keys' => is_array($result) ? array_keys($result) : null,
                        'number' => $formattedNumber,
                        'message' => 'Got HTTP 200 but could not confirm SMS was queued - marking as failed'
                    ]);
                    $statusMessage = 'Unable to confirm message was queued';
                }
            }

            return [
                'success' => $isSuccessful,
                'data' => $result,
                'message' => $statusMessage,
                'formatted_number' => $formattedNumber
            ];

        } catch (\Exception $e) {
            Log::error('Semaphore SMS Exception', [
                'number' => $number ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Send SMS with rate limiting and semaphore control
     *
     * @param string $number Phone number
     * @param string $message Message content
     * @return array Response from API
     */
    public function sendSMSWithRateLimit(string $number, string $message): array
    {
        // Acquire semaphore lock
        $lock = Cache::lock($this->lockKey, 60); // 60 second timeout
        
        try {
            // Wait for semaphore lock (max 60 seconds)
            $lock->block(60);
            
            // Wait for rate limit if needed
            $this->waitForRateLimit();
            
            // Send SMS
            $result = $this->sendSMS($number, $message);
            
            // Record request timestamp for rate limiting
            $this->recordRequest();
            
            // Add delay between requests
            if ($this->delayBetweenRequests > 0) {
                usleep($this->delayBetweenRequests * 1000); // Convert ms to microseconds
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Semaphore SMS Rate Limit Exception', [
                'number' => $number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Rate limit error: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            // Always release the lock
            $lock->release();
        }
    }

    /**
     * Acquire semaphore lock
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    protected function acquireSemaphore(): \Illuminate\Contracts\Cache\Lock
    {
        return Cache::lock($this->lockKey, 60);
    }

    /**
     * Wait for rate limit if needed
     */
    protected function waitForRateLimit(): void
    {
        $timestamps = Cache::get($this->rateLimitKey, []);
        $now = time();
        
        // Remove timestamps older than 1 minute
        $timestamps = array_filter($timestamps, function($timestamp) use ($now) {
            return ($now - $timestamp) < 60;
        });
        
        // If we're at the limit, wait until we can make another request
        if (count($timestamps) >= $this->maxRequestsPerMinute) {
            $oldestTimestamp = min($timestamps);
            $waitTime = 60 - ($now - $oldestTimestamp) + 1; // Add 1 second buffer
            
            if ($waitTime > 0) {
                Log::info('Semaphore SMS: Rate limit reached, waiting', [
                    'wait_seconds' => $waitTime,
                    'requests_in_window' => count($timestamps),
                    'max_requests' => $this->maxRequestsPerMinute
                ]);
                
                sleep($waitTime);
                
                // Re-filter after waiting
                $now = time();
                $timestamps = array_filter($timestamps, function($timestamp) use ($now) {
                    return ($now - $timestamp) < 60;
                });
            }
        }
    }

    /**
     * Record request timestamp for rate limiting
     */
    protected function recordRequest(): void
    {
        $timestamps = Cache::get($this->rateLimitKey, []);
        $now = time();
        
        // Remove timestamps older than 1 minute
        $timestamps = array_filter($timestamps, function($timestamp) use ($now) {
            return ($now - $timestamp) < 60;
        });
        
        // Add current timestamp
        $timestamps[] = $now;
        
        // Store back in cache (expires in 2 minutes to be safe)
        Cache::put($this->rateLimitKey, $timestamps, 120);
    }

    /**
     * Send bulk SMS to multiple numbers with rate limiting
     *
     * @param array $numbers Array of phone numbers
     * @param string $message Message content
     * @return array Array of results
     */
    public function sendBulkSMS(array $numbers, string $message): array
    {
        $results = [];
        $total = count($numbers);
        
        Log::info('Semaphore SMS: Starting bulk send', [
            'total_recipients' => $total,
            'max_requests_per_minute' => $this->maxRequestsPerMinute,
            'delay_between_requests_ms' => $this->delayBetweenRequests
        ]);
        
        foreach ($numbers as $index => $number) {
            $result = $this->sendSMSWithRateLimit($number, $message);
            $results[] = $result;
            
            // Log progress for large batches
            if ($total > 10 && ($index + 1) % 10 === 0) {
                Log::info('Semaphore SMS: Bulk send progress', [
                    'sent' => $index + 1,
                    'total' => $total,
                    'progress_percent' => round((($index + 1) / $total) * 100, 1)
                ]);
            }
        }
        
        $successCount = count(array_filter($results, fn($r) => $r['success'] ?? false));
        $failCount = $total - $successCount;
        
        Log::info('Semaphore SMS: Bulk send completed', [
            'total' => $total,
            'success' => $successCount,
            'failed' => $failCount
        ]);

        return $results;
    }

    /**
     * Send OTP via priority route
     */
    public function sendOTP(string $number, string $code): array
    {
        $message = "Your OTP code is: {$code}. Valid for 5 minutes. Do not share this code.";
        
        return $this->sendSMSWithRateLimit($number, $message);
    }

    /**
     * Check account balance
     */
    public function getBalance(): array
    {
        try {
            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'message' => 'API key is not configured',
                    'data' => null
                ];
            }

            if (empty($this->baseUrl)) {
                return [
                    'success' => false,
                    'message' => 'Base URL is not configured',
                    'data' => null
                ];
            }

            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/account", [
                    'apikey' => $this->apiKey,
                ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Semaphore Get Balance Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Test API connection and configuration
     *
     * @return array Test result
     */
    public function testConnection(): array
    {
        $results = [
            'api_key_configured' => !empty($this->apiKey),
            'base_url_configured' => !empty($this->baseUrl),
            'sender_name_configured' => !empty($this->senderName),
            'balance_check' => null,
            'errors' => []
        ];

        if (!$results['api_key_configured']) {
            $results['errors'][] = 'SEMAPHORE_API_KEY is not set in .env file';
        }

        if (!$results['base_url_configured']) {
            $results['errors'][] = 'SEMAPHORE_BASE_URL is not set in .env file';
        }

        // Try to get balance if API key is configured
        if ($results['api_key_configured'] && $results['base_url_configured']) {
            $balanceResult = $this->getBalance();
            $results['balance_check'] = $balanceResult;
            
            if (!$balanceResult['success']) {
                $results['errors'][] = 'Failed to connect to Semaphore API: ' . ($balanceResult['message'] ?? 'Unknown error');
            }
        }

        $results['connection_ok'] = empty($results['errors']);

        return $results;
    }

    /**
     * Retrieve outgoing messages from Semaphore
     */
    public function getMessages(int $limit = 50, int $page = 1): array
    {
        try {
            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'message' => 'API key is not configured',
                    'data' => []
                ];
            }

            if (empty($this->baseUrl)) {
                return [
                    'success' => false,
                    'message' => 'Base URL is not configured',
                    'data' => []
                ];
            }

            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/messages", [
                    'apikey' => $this->apiKey,
                    'limit' => $limit,
                    'page' => $page,
                ]);

            if (!$response->successful()) {
                Log::error('Semaphore Get Messages Failed', [
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to retrieve messages: HTTP ' . $response->status(),
                    'data' => []
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('Semaphore Get Messages Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Format phone number to Philippine format (639XXXXXXXXX)
     */
    protected function formatPhoneNumber(string $number): string
    {
        if (empty($number)) {
            return '';
        }

        // Remove any non-digit characters (spaces, dashes, plus signs, etc.)
        $number = preg_replace('/[^0-9]/', '', $number);

        if (empty($number)) {
            return '';
        }

        // Handle different input formats:
        // +639XXXXXXXXX or 639XXXXXXXXX -> 639XXXXXXXXX
        // 09XXXXXXXXX -> 639XXXXXXXXX
        // 9XXXXXXXXX -> 639XXXXXXXXX
        // 0XXXXXXXXX -> 639XXXXXXXXX (if 11 digits)

        // If already starts with 63, return as is (but ensure it's 12 digits)
        if (substr($number, 0, 2) === '63') {
            // If it's exactly 12 digits, return it
            if (strlen($number) === 12) {
                return $number;
            }
            // If it's longer, truncate or handle error
            if (strlen($number) > 12) {
                Log::warning('Semaphore SMS: Number too long after 63 prefix', [
                    'original' => $number,
                    'length' => strlen($number)
                ]);
                return substr($number, 0, 12);
            }
            // If shorter, it's invalid
            return '';
        }

        // Remove leading zero if present (for 09XXXXXXXXX or 0XXXXXXXXX)
        if (substr($number, 0, 1) === '0') {
            $number = substr($number, 1);
        }

        // At this point, number should be 9XXXXXXXXX or 10 digits
        // Add country code 63
        $formatted = '63' . $number;

        // Ensure it's exactly 12 digits
        if (strlen($formatted) === 12) {
            return $formatted;
        }

        // Log warning if format is unexpected
        Log::warning('Semaphore SMS: Unexpected number format', [
            'original' => $number,
            'formatted' => $formatted,
            'length' => strlen($formatted)
        ]);

        return $formatted;
    }

    /**
     * Validate Philippine phone number format
     * Accepts numbers in format 639XXXXXXXXX (12 digits total)
     */
    protected function validatePhoneNumber(string $number): bool
    {
        if (empty($number)) {
            return false;
        }

        // Must start with 63
        if (substr($number, 0, 2) !== '63') {
            return false;
        }

        // Must be exactly 12 digits
        if (strlen($number) !== 12) {
            return false;
        }

        // Extract the mobile number part (after country code 63)
        $mobileNumber = substr($number, 2);
        
        if (strlen($mobileNumber) !== 10) {
            return false;
        }

        // Philippine mobile numbers must start with 9
        if (substr($mobileNumber, 0, 1) !== '9') {
            return false;
        }

        // Get the first 3 digits (mobile prefix)
        $prefix = substr($mobileNumber, 0, 3);

        // Valid Philippine mobile prefixes (expanded list)
        $validPrefixes = [
            // Globe/TM
            '817', '905', '906', '915', '916', '925', '926', '927', 
            '935', '936', '937', '945', '955', '956', '965', '966', '967',
            '975', '976', '977', '978', '979', '995', '996', '997',
            '991', '992', '993', '994', // Additional Globe prefixes
            // Smart/TNT
            '813', '900', '907', '908', '909', '910', '911', '912', '913', 
            '914', '918', '919', '920', '921', '928', '929', '930', '938', 
            '939', '940', '946', '947', '948', '949', '950', '951', '961', 
            '970', '981', '989', '998', '999',
            // Sun
            '922', '923', '924', '931', '932', '933', '934', '941', 
            '942', '943', '944',
            // DITO
            '895', '896', '897', '898',
            // Additional common prefixes
            '917', '918', '919', '920', '921', '922', '923', '924',
            '930', '931', '932', '933', '934', '935', '936', '937',
            '940', '941', '942', '943', '944', '945', '946', '947', '948', '949',
            '950', '951', '955', '956', '961', '965', '966', '967',
            '970', '975', '976', '977', '978', '979', '981', '989',
            '991', '992', '993', '994', '995', '996', '997', '998', '999',
        ];

        // Check if prefix is in the list, or if it's a valid 9XX pattern
        // Accept any 3-digit prefix starting with 9 as valid (more lenient)
        // This allows for new prefixes that may not be in our list
        if (in_array($prefix, $validPrefixes)) {
            return true;
        }

        // Fallback: Accept any 3-digit prefix starting with 9XX where XX are digits
        // This is more lenient and will accept valid Philippine mobile numbers
        // even if the prefix is not in our predefined list
        if (preg_match('/^9\d{2}$/', $prefix)) {
            Log::info('Semaphore SMS: Accepted number with prefix not in predefined list', [
                'prefix' => $prefix,
                'number' => $number
            ]);
            return true;
        }

        return false;
    }
}