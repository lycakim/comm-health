<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SemaphoreService;

class TestSemaphoreConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'semaphore:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Semaphore SMS API connection and configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Semaphore SMS API Configuration...');
        $this->newLine();

        $service = new SemaphoreService();
        $result = $service->testConnection();

        // Display configuration status
        $this->info('Configuration Status:');
        $this->line('  API Key: ' . ($result['api_key_configured'] ? '✓ Configured' : '✗ Missing'));
        $this->line('  Base URL: ' . ($result['base_url_configured'] ? '✓ Configured' : '✗ Missing'));
        $this->line('  Sender Name: ' . ($result['sender_name_configured'] ? '✓ Configured' : '✗ Missing'));
        $this->newLine();

        // Display connection test
        if ($result['balance_check']) {
            $this->info('API Connection Test:');
            if ($result['balance_check']['success']) {
                $this->line('  ✓ Successfully connected to Semaphore API');
                $data = $result['balance_check']['data'];
                if (isset($data['credit_balance'])) {
                    $this->line('  Credit Balance: ' . $data['credit_balance']);
                }
                if (isset($data['account_name'])) {
                    $this->line('  Account Name: ' . $data['account_name']);
                }
            } else {
                $this->line('  ✗ Failed to connect: ' . ($result['balance_check']['message'] ?? 'Unknown error'));
                if (isset($result['balance_check']['status_code'])) {
                    $this->line('  HTTP Status: ' . $result['balance_check']['status_code']);
                }
            }
            $this->newLine();
        }

        // Display errors if any
        if (!empty($result['errors'])) {
            $this->error('Errors Found:');
            foreach ($result['errors'] as $error) {
                $this->line('  - ' . $error);
            }
            $this->newLine();
        }

        // Final status
        if ($result['connection_ok']) {
            $this->info('✓ All checks passed! Semaphore SMS is configured correctly.');
            return 0;
        } else {
            $this->error('✗ Configuration issues found. Please check the errors above.');
            return 1;
        }
    }
}
