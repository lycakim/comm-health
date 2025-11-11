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

    public ?int $balance = 0;

    public function mount(): void
    {
        $this->checkBalance();
    }

    public function checkBalance()
    {
        $semaphore = new SemaphoreService();
        $result = $semaphore->getBalance();

        if ($result['success']) {
            $this->balance = $result['data'];
        }
    }
}