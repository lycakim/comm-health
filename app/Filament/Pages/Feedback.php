<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Enums\RoleEnum;
use Filament\Pages\Page;
use App\Models\SystemFeedback;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Contracts\HasInfolists;
use Parallax\FilamentComments\Actions\CommentsAction;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Parallax\FilamentComments\Infolists\Components\CommentsEntry;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class Feedback extends Page implements HasInfolists
{
    use HasDashboardBreadcrumb;
    use InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string $view = 'filament.pages.feedback';

    protected static ?string $navigationLabel = 'Feedback';

    protected static ?string $title = 'System Feedback & Comments';

    protected static ?int $navigationSort = 6;

    public ?SystemFeedback $record = null;

    public function mount(): void
    {
        $this->record = SystemFeedback::firstOrCreate(
            ['type' => 'system_feedback'],
            [
                'title' => 'System Feedback',
                'description' => 'General feedback about the system',
                'type' => 'system_feedback'
            ]
        );
    }

    public function feedbackInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                CommentsEntry::make('filament_comments')
                    ->columnSpanFull()
                    ->label('System Feedback & Comments'),
            ]);
    }

    // If you want to display the infolist in your view
    protected function getViewData(): array
    {
        return [
            'feedbackInfolist' => $this->feedbackInfolist($this->makeInfolist()),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function currentUser(): ?User
    {
        return Auth::user();
    }
}