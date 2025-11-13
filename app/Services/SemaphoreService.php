<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SemaphoreService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->apiKey = config('services.semaphore.api_key');
        $this->senderName = config('services.semaphore.sender_name');
        $this->baseUrl = config('services.semaphore.base_url');
    }

    /**
     * Send SMS to a single number
     */
    public function sendSMS(string $number, string $message): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/messages", [
                'apikey' => $this->apiKey,
                'number' => $this->formatPhoneNumber($number),
                'message' => $message,
                'sendername' => $this->senderName,
            ]);

            $result = $response->json();

            Log::info('Semaphore SMS sent', [
                'number' => $number,
                'response' => $result
            ]);

            return [
                'success' => $response->successful(),
                'data' => $result,
                'message' => $result['message'] ?? 'SMS sent successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Semaphore SMS error', [
                'number' => $number,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
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
     * Format phone number to Philippine format
     */
    protected function formatPhoneNumber(string $number): string
    {
        // Remove any non-digit characters
        $number = preg_replace('/[^0-9]/', '', $number);

        // If starts with 0, replace with 63
        if (substr($number, 0, 1) === '0') {
            $number = '63' . substr($number, 1);
        }

        // If doesn't start with 63, add it
        if (substr($number, 0, 2) !== '63') {
            $number = '63' . $number;
        }

        return $number;
    }
}