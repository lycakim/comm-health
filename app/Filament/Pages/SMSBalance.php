<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\SemaphoreService;
use App\Models\SMSBalance as SMSBalanceModel;

class SMSBalance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static string $view = 'filament.pages.sms-balance';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $navigationLabel = 'SMS Balance';

    protected static ?string $title = 'SMS Balance';

    protected static ?string $slug = 'sms-balance';

    public ?array $balanceData = [];
    public ?string $lastRetrievedAt = null;

    public function mount(): void
    {
        $this->loadLatestBalance();
    }

    public function loadLatestBalance()
    {
        // Load from database
        $latestBalance = SMSBalanceModel::getLatest();

        if ($latestBalance) {
            $this->balanceData = [
                'account_name' => $latestBalance->account_name,
                'credit_balance' => $latestBalance->credit_balance,
            ];
            $this->lastRetrievedAt = $latestBalance->retrieved_at->diffForHumans();
        } else {
            $this->balanceData = [];
            $this->lastRetrievedAt = null;
        }
    }

    public function checkBalance()
    {
        try {
            $semaphore = new SemaphoreService();
            $result = $semaphore->getBalance();
            
            if ($result['success'] && isset($result['data']['credit_balance'])) {
                // Save to database
                $balance = SMSBalanceModel::create([
                    'account_name' => $result['data']['account_name'] ?? 'Unknown',
                    'credit_balance' => $result['data']['credit_balance'],
                    'retrieved_at' => now(),
                ]);

                $this->balanceData = [
                    'account_name' => $balance->account_name,
                    'credit_balance' => $balance->credit_balance,
                ];
                $this->lastRetrievedAt = $balance->retrieved_at->diffForHumans();

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Balance updated successfully!'
                ]);
            } else {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Failed to retrieve balance from API'
                ]);
            }
        } catch (\Exception $e) {
            logger()->error('Error retrieving SMS balance: ' . $e->getMessage());
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}