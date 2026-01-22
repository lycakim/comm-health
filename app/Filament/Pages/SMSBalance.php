<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Enums\RoleEnum;
use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\SemaphoreMessage;
use Filament\Actions\ViewAction;
use App\Services\SemaphoreService;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use App\Models\SMSBalance as SMSBalanceModel;
use Filament\Tables\Concerns\InteractsWithTable;

class SMSBalance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static string $view = 'filament.pages.sms-balance';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $navigationLabel = 'SMS Balance';

    protected static ?string $title = 'SMS Balance';

    protected static ?string $slug = 'sms-balance';

    public ?array $balanceData = [];
    public ?string $lastRetrievedAt = null;
    public ?string $messagesLastRetrievedAt = null;

    public static function canAccess(): bool
    {
        return in_array(self::currentUser()->role, [
            RoleEnum::BHW,
            RoleEnum::MIDWIFE,
            RoleEnum::ADMIN,
        ]);
    }

    public function mount(): void
    {
        $this->loadLatestBalance();
        $this->loadMessagesLastRetrievedAt();
    }

    public function loadLatestBalance()
    {
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

                $this->fetchMessages();

                Notification::make()
                    ->title('Balance updated successfully!')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Failed to retrieve balance from API')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            logger()->error('Error retrieving SMS balance: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function loadMessagesLastRetrievedAt()
    {
        $latestMessage = SemaphoreMessage::orderBy('retrieved_at', 'desc')->first();
        if ($latestMessage && $latestMessage->retrieved_at) {
            $this->messagesLastRetrievedAt = $latestMessage->retrieved_at->diffForHumans();
        } else {
            $this->messagesLastRetrievedAt = null;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Outgoing Messages')
            ->description(function () {
                $statuses = [
                    'Sent' => 'Sent',
                    'Queued' => 'Queued',
                    'Pending' => 'Pending',
                    'Failed' => 'Failed',
                    'Refunded' => 'Refunded',
                ];

                $parts = [];
                $counts = \App\Models\SemaphoreMessage::selectRaw("status, COUNT(*) as count")
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray();

                foreach ($statuses as $statusKey => $statusLabel) {
                    $count = isset($counts[$statusKey]) ? (int)$counts[$statusKey] : 0;
                    $parts[] = "<span style=\"white-space:nowrap;\"><strong>{$statusLabel}:</strong> {$count}</span>";
                }

                $html = implode(' <span class="mx-2 text-gray-300">|</span> ', $parts);

                $total = array_sum($counts);

                $summary = '<span class="text-gray-600 text-sm">Message status overview (Total: <strong>' 
                        . $total . '</strong>)</span><br>';

                return new \Illuminate\Support\HtmlString($summary . $html);
            })
            ->query(SemaphoreMessage::query()->latest('sent_at'))
            ->actions([
                Action::make('viewDetails')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Message Details')
                    ->modalWidth('2xl')
                    ->modalContent(fn ($record) => view('filament.modals.message-details', [
                        'record' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->columns([
                TextColumn::make('number')
                    ->label('Recipient')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->message)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state ?? '')) {
                        'sent' => 'success',
                        'queued' => 'info',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'Unknown'))
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->default('N/A'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'sent' => 'Sent',
                        'queued' => 'Queued',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->preload(),
            ])
            ->headerActions([
                Action::make('fetchMessages')
                    ->label('Fetch Messages')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Fetch Messages from Semaphore')
                    ->modalDescription('This will retrieve all outgoing messages from your Semaphore account. Existing messages will be updated.')
                    ->modalSubmitActionLabel('Fetch Messages')
                    ->action(function () {
                        try {
                            $this->fetchMessages();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to fetch messages')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('sent_at', 'desc')
            ->emptyStateHeading('No messages available')
            ->emptyStateDescription('Click "Fetch Messages" to retrieve outgoing messages from Semaphore.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }

    public function fetchMessages()
    {
        try {
            $semaphore = new SemaphoreService();
            $result = $semaphore->getMessages(50, 1);

            if ($result['success'] && isset($result['data'])) {
                $messagesData = $result['data'];

                $messagesArray = [];
                if (isset($messagesData['data']) && is_array($messagesData['data'])) {
                    $messagesArray = $messagesData['data'];
                } elseif (is_array($messagesData) && isset($messagesData[0])) {
                    $messagesArray = $messagesData;
                }

                $savedCount = 0;
                foreach ($messagesArray as $messageData) {
                    $messageId = $messageData['message_id'] ?? $messageData['id'] ?? null;
                    
                    if ($messageId) {
                        SemaphoreMessage::updateOrCreate([
                            'message_id' => $messageId,
                        ], [
                            'number' => $messageData['number'] ?? $messageData['recipient'] ?? 'Unknown',
                            'message' => $messageData['message'] ?? $messageData['text'] ?? '',
                            'status' => $messageData['status'] ?? null,
                            'sender_name' => $messageData['sender_name'] ?? $messageData['sendername'] ?? null,
                            'sent_at' => isset($messageData['created_at']) ? $messageData['created_at'] : (isset($messageData['sent_at']) ? $messageData['sent_at'] : now()),
                            'retrieved_at' => now(),
                        ]);
                        $savedCount++;
                    }
                }

                $this->loadMessagesLastRetrievedAt();
                $this->resetTable();

                Notification::make()
                    ->title("Fetched {$savedCount} new messages successfully!")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Failed to retrieve messages from API')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            logger()->error('Error retrieving Semaphore messages: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}