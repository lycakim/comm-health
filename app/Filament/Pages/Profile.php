<?php

namespace App\Filament\Pages;

use Closure;
use App\Models\User;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Filament\Widgets\PatientChart;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class Profile extends Page
{
    use HasDashboardBreadcrumb;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static string $view = 'filament.pages.profile';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public static function canAccess(): bool
    {
        return Auth::user()->isResident();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'View your profile information';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Account Information')
                    ->description("Update your account's profile information and email address.")
                    ->collapsible(true)
                    ->schema([
                        TextInput::make('name')->required(),
                        TextInput::make('email')->disabled(),
                    ]),
                Section::make('Update Password')
                    ->description('Ensure your account is using a long, random password to stay secure.')
                    ->schema([
                        TextInput::make('old_password')
                            ->label('Current Password')
                            ->password()
                            ->minLength(8)
                            ->revealable()
                            ->required(
                                fn (Get $get): bool => filled($get('new_password')) || filled($get('confirm_password'))
                            )
                            ->rules([
                                fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                    if (! Hash::check($value, Auth::user()->password)) {
                                        $fail('The :attribute is incorrect.');
                                    }
                                },
                            ])
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state)),
                        TextInput::make('new_password')
                            ->password()
                            ->minLength(8)
                            ->required(
                                fn (Get $get): bool => filled($get('old_password')) || filled($get('confirm_password'))
                            )
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    if ($value !== $get('confirm_password')) {
                                        $fail('The :attribute confirmation does not match.');
                                    }
                                },
                            ])
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state)),
                        TextInput::make('confirm_password')
                            ->label('Confirm Password')
                            ->password()
                            ->required(
                                fn (Get $get): bool => filled($get('old_password')) || filled($get('new_password'))
                            )
                            ->minLength(8)
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state)),
                    ]),
            ])
            ->statePath('data')
            ->model(User::class);
    }

    public function save()
    {
        $data = $this->form->getState();

        $user = User::find(Auth::user()->id);

        $user->name = $data['name'];

        if (filled($data['new_password'] ?? null)) {
            $user->password = $data['new_password'];
        }

        $user->save();

        session()->put([
            'password_hash_'.Auth::getDefaultDriver() => $user->getAuthPassword(),
        ]);

        redirect('/commhealth/profile');

        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->send();
    }
}