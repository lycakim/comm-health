<?php

namespace App\Console\Commands;

use App\Models\SMSBalance;
use Illuminate\Console\Command;
use App\Services\SemaphoreService;
use Illuminate\Support\Facades\Log;

class FetchSMSBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-sms-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching SMS balance...');

        try {
            $semaphore = new SemaphoreService();
            $result = $semaphore->getBalance();

            if ($result['success'] && isset($result['data']['credit_balance'])) {
                $data = $result['data'];

                // Save balance to database
                $balance = SMSBalance::create([
                    'account_name' => $data['account_name'] ?? 'Unknown',
                    'credit_balance' => $data['credit_balance'],
                    'retrieved_at' => now(),
                ]);

                $this->info('✓ Balance retrieved successfully!');
                $this->table(
                    ['Account Name', 'Balance', 'Retrieved At'],
                    [[
                        $balance->account_name,
                        '₱' . number_format($balance->credit_balance, 2),
                        $balance->retrieved_at->format('Y-m-d H:i:s')
                    ]]
                );

                Log::info('SMS balance fetched successfully', [
                    'account_name' => $balance->account_name,
                    'credit_balance' => $balance->credit_balance,
                    'retrieved_at' => $balance->retrieved_at,
                ]);

                return Command::SUCCESS;
            } else {
                $this->error('✗ Failed to retrieve balance from API');
                Log::error('Failed to fetch SMS balance', [
                    'result' => $result
                ]);

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            Log::error('Exception while fetching SMS balance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}