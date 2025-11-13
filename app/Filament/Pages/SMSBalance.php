<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\SemaphoreService;

class SMSBalance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static string $view = 'filament.pages.sms-balance';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $navigationLabel = 'SMS Balance';

    protected static ?string $title = 'SMS Balance';

    protected static ?string $slug = 'sms-balance';

    public ?array $balanceData = [];

    public function mount(): void
    {
        $this->checkBalance();
    }

    public function checkBalance()
    {
        try {
            $semaphore = new SemaphoreService();
            $result = $semaphore->getBalance();
            
            if ($result['success'] && $result['data']['credit_balance'] > 0) {
                $this->balanceData = $result['data'];
            }

            logger($this->balanceData);
            logger($result);
        } catch (\Exception $e) {
            logger()->error('Error retrieving SMS balance: ' . $e->getMessage());
            $this->balanceData = [];
        }
    }
}