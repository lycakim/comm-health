<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PhilSMSService
{
    protected $apiToken;
    protected $baseUrl;
    protected $senderId;

    public function __construct()
    {
        $this->apiToken = config('services.philsms.api_token');
        $this->baseUrl = config('services.philsms.base_url', 'https://app.philsms.com/api/v3');
        $this->senderId = config('services.philsms.sender_id', 'Philsms');
    }

    /**
     * Send SMS message via PhilSMS API
     *
     * @param string|array $recipient Phone number(s) - format: 639xxxxxxxxx
     * @param string $message Message content
     * @param string|null $senderId Optional sender ID (max 11 characters)
     * @param string|null $scheduleTime Optional schedule time (Y-m-d H:i format)
     * @return array Response from API
     */
    public function sendSMS(
        string|array $recipient, 
        string $message, 
        ?string $senderId = null,
        ?string $scheduleTime = null
    ): array {
        try {
            // Format recipient(s)
            if (is_array($recipient)) {
                $formattedRecipients = array_map([$this, 'formatPhoneNumber'], $recipient);
                $recipient = implode(',', $formattedRecipients);
            } else {
                $recipient = $this->formatPhoneNumber($recipient);
            }

            // Validate at least one recipient
            if (empty($recipient)) {
                return [
                    'success' => false,
                    'message' => 'No valid recipients provided',
                    'data' => null
                ];
            }

            // Build request payload according to PhilSMS API v3 specs
            $payload = [
                'recipient' => $recipient,
                'sender_id' => $senderId ?? $this->senderId,
                'type' => 'plain', // Required: plain, unicode, voice, mms, whatsapp
                'message' => $message,
            ];

            // Add optional schedule time
            if ($scheduleTime) {
                $payload['schedule_time'] = $scheduleTime;
            }

            Log::info('PhilSMS: Sending SMS', [
                'recipient' => $recipient,
                'sender_id' => $payload['sender_id'],
                'scheduled' => !empty($scheduleTime)
            ]);

            // Make API request with Bearer token authentication
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->post("{$this->baseUrl}/sms/send", $payload);

            $result = $response->json();
            $statusCode = $response->status();

            // Log API response
            Log::info('PhilSMS API Response', [
                'status_code' => $statusCode,
                'response' => $result,
                'http_successful' => $response->successful()
            ]);

            // Parse response according to PhilSMS API format
            if (!$response->successful()) {
                $errorMessage = $result['message'] ?? "HTTP Error: {$statusCode}";
                
                Log::error('PhilSMS HTTP Error', [
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'response' => $result
                ]);

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'data' => $result
                ];
            }

            // PhilSMS returns: {"status": "success|error", "data": {...}, "message": "..."}
            $isSuccess = isset($result['status']) && $result['status'] === 'success';
            $message = $result['message'] ?? ($isSuccess ? 'Message sent successfully' : 'Unknown response');

            if ($isSuccess) {
                Log::info('PhilSMS: Message sent successfully', [
                    'recipient' => $recipient,
                    'data' => $result['data'] ?? null
                ]);
            } else {
                Log::error('PhilSMS: API returned error status', [
                    'status' => $result['status'] ?? 'unknown',
                    'message' => $message,
                    'response' => $result
                ]);
            }

            return [
                'success' => $isSuccess,
                'message' => $message,
                'data' => $result['data'] ?? null,
                'raw_response' => $result
            ];

        } catch (\Exception $e) {
            Log::error('PhilSMS Exception', [
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
     *
     * @param array $recipients Array of phone numbers
     * @param string $message Message content
     * @param string|null $senderId Optional sender ID
     * @return array Response from API
     */
    public function sendBulkSMS(array $recipients, string $message, ?string $senderId = null): array
    {
        // PhilSMS supports comma-separated recipients in a single API call
        return $this->sendSMS($recipients, $message, $senderId);
    }

    /**
     * Send OTP via SMS
     *
     * @param string $recipient Phone number
     * @param string $code OTP code
     * @param int $validMinutes Validity period in minutes
     * @return array Response from API
     */
    public function sendOTP(string $recipient, string $code, int $validMinutes = 5): array
    {
        $message = "Your OTP code is: {$code}. Valid for {$validMinutes} minutes. Do not share this code.";
        
        return $this->sendSMS($recipient, $message);
    }

    /**
     * Send scheduled SMS
     *
     * @param string|array $recipient Phone number(s)
     * @param string $message Message content
     * @param string $scheduleTime Schedule time in Y-m-d H:i format
     * @param string|null $senderId Optional sender ID
     * @return array Response from API
     */
    public function sendScheduledSMS(
        string|array $recipient, 
        string $message, 
        string $scheduleTime,
        ?string $senderId = null
    ): array {
        return $this->sendSMS($recipient, $message, $senderId, $scheduleTime);
    }

    /**
     * Send campaign to contact lists
     *
     * @param string|array $contactListId Contact list ID(s) - comma-separated for multiple
     * @param string $message Message content
     * @param string|null $senderId Optional sender ID
     * @param string|null $scheduleTime Optional schedule time
     * @return array Response from API
     */
    public function sendCampaign(
        string|array $contactListId,
        string $message,
        ?string $senderId = null,
        ?string $scheduleTime = null
    ): array {
        try {
            if (is_array($contactListId)) {
                $contactListId = implode(',', $contactListId);
            }

            $payload = [
                'contact_list_id' => $contactListId,
                'sender_id' => $senderId ?? $this->senderId,
                'type' => 'plain',
                'message' => $message,
            ];

            if ($scheduleTime) {
                $payload['schedule_time'] = $scheduleTime;
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->post("{$this->baseUrl}/sms/campaign", $payload);

            $result = $response->json();

            $isSuccess = isset($result['status']) && $result['status'] === 'success';

            return [
                'success' => $isSuccess,
                'message' => $result['message'] ?? ($isSuccess ? 'Campaign sent successfully' : 'Unknown error'),
                'data' => $result['data'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('PhilSMS Campaign Exception', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get SMS message details by UID
     *
     * @param string $uid Message UID
     * @return array Response from API
     */
    public function getMessage(string $uid): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("{$this->baseUrl}/sms/{$uid}");

            $result = $response->json();
            $isSuccess = isset($result['status']) && $result['status'] === 'success';

            return [
                'success' => $isSuccess,
                'message' => $result['message'] ?? '',
                'data' => $result['data'] ?? null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get all SMS messages (with pagination)
     *
     * @return array Response from API
     */
    public function getMessages(): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("{$this->baseUrl}/sms");

            $result = $response->json();
            $isSuccess = isset($result['status']) && $result['status'] === 'success';

            return [
                'success' => $isSuccess,
                'data' => $result['data'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('PhilSMS Get Messages Exception', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Check account balance
     *
     * @return array Response from API
     */
    public function getBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("{$this->baseUrl}/balance");

            $result = $response->json();
            $isSuccess = isset($result['status']) && $result['status'] === 'success';

            return [
                'success' => $isSuccess,
                'data' => $result['data'] ?? null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get profile information
     *
     * @return array Response from API
     */
    public function getProfile(): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("{$this->baseUrl}/me");

            $result = $response->json();
            $isSuccess = isset($result['status']) && $result['status'] === 'success';

            return [
                'success' => $isSuccess,
                'data' => $result['data'] ?? null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Format phone number to Philippine format (639xxxxxxxxx)
     *
     * @param string $number Phone number
     * @return string Formatted phone number
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
        $number = ltrim($number, '0');

        // If starts with 63, keep it
        if (substr($number, 0, 2) === '63') {
            return $number;
        }

        // If starts with +63, remove the +
        if (substr($number, 0, 3) === '+63') {
            return substr($number, 1);
        }

        // Add country code 63
        return '63' . $number;
    }

    /**
     * Validate Philippine phone number format
     *
     * @param string $number Phone number
     * @return bool
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

        // Must be exactly 12 digits (63 + 10 digits)
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

        // Valid Philippine mobile prefixes (updated 2025)
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