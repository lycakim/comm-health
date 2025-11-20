<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SemaphoreService
{
    protected $apiKey;
    protected $senderName;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.semaphore.api_key');
        $this->senderName = config('services.semaphore.sender_name');
        $this->baseUrl = config('services.semaphore.base_url');
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

            $response = Http::post("{$this->baseUrl}/messages", $payload);

            $result = $response->json();
            $statusCode = $response->status();

            // Log detailed response for debugging
            Log::info('Semaphore SMS API Response', [
                'original_number' => $number,
                'formatted_number' => $formattedNumber,
                'status_code' => $statusCode,
                'response_body' => $result,
                'http_successful' => $response->successful()
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
                Log::warning('Semaphore SMS: HTTP 200 but no success confirmation', [
                    'status_code' => $statusCode,
                    'response' => $result,
                    'number' => $formattedNumber,
                    'message' => 'Got HTTP 200 but could not confirm SMS was queued - marking as failed'
                ]);
                $statusMessage = 'Unable to confirm message was queued';
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
     * Send bulk SMS to multiple numbers
     */
    public function sendBulkSMS(array $numbers, string $message): array
    {
        $results = [];
        
        foreach ($numbers as $number) {
            $results[] = $this->sendSMS($number, $message);
        }

        return $results;
    }

    /**
     * Send OTP via priority route
     */
    public function sendOTP(string $number, string $code): array
    {
        $message = "Your OTP code is: {$code}. Valid for 5 minutes. Do not share this code.";
        
        return $this->sendSMS($number, $message);
    }

    /**
     * Check account balance
     */
    public function getBalance(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/account", [
                'apikey' => $this->apiKey,
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve outgoing messages from Semaphore
     */
    public function getMessages(int $limit = 50, int $page = 1): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/messages", [
                'apikey' => $this->apiKey,
                'limit' => $limit,
                'page' => $page,
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to retrieve messages',
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
     * Format phone number to Philippine format
     */
    protected function formatPhoneNumber(string $number): string
    {
        if (empty($number)) {
            return '';
        }

        // Remove any non-digit characters
        $number = preg_replace('/[^0-9]/', '', $number);

        if (empty($number)) {
            return '';
        }

        // Remove leading zeros
        if (strlen($number) > 1 && substr($number, 0, 1) === '0') {
            $number = substr($number, 1);
        }

        // If starts with 63, keep it
        if (substr($number, 0, 2) === '63') {
            return $number;
        }

        // Add country code
        return '63' . $number;
    }

    /**
     * Validate Philippine phone number format
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

        // Extract the mobile number part
        $mobileNumber = substr($number, 2);
        
        if (strlen($mobileNumber) !== 10) {
            return false;
        }

        // Get the first 3 digits (mobile prefix)
        $prefix = substr($mobileNumber, 0, 3);

        // Valid Philippine mobile prefixes
        $validPrefixes = [
            // Globe/TM
            '817', '905', '906', '915', '916', '925', '926', '927', 
            '935', '936', '937', '945', '955', '956', '965', '966', '967',
            '975', '976', '977', '978', '979', '995', '996', '997',
            // Smart/TNT
            '813', '900', '907', '908', '909', '910', '911', '912', '913', 
            '914', '918', '919', '920', '921', '928', '929', '930', '938', 
            '939', '940', '946', '947', '948', '949', '950', '951', '961', 
            '970', '981', '989', '992', '998', '999',
            // Sun
            '922', '923', '924', '931', '932', '933', '934', '941', 
            '942', '943', '944',
            // DITO
            '895', '896', '897', '898',
        ];

        return in_array($prefix, $validPrefixes);
    }
}