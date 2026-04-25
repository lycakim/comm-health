<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;
use Closure;
use App\Models\User;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class Settings extends Page implements HasForms
{
    use HasDashboardBreadcrumb;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?int $navigationSort = 4;

    public ?array $profileData = [];
    public ?array $passwordData = [];

    public static function canAccess(): bool
    {
        return in_array(Auth::user()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,
            RoleEnum::MIDWIFE,
        ]);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Manage your account settings';
    }

    public function mount(): void
    {
        $user = Auth::user();

        $this->profileForm->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $this->passwordForm->fill();
    }

    protected function getForms(): array
    {
        return [
            'profileForm',
            'passwordForm',
        ];
    }

    public function profileForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Profile Information')
                    ->description("Update your account's profile information and email address.")
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->disabled(),
                    ]),
            ])
            ->statePath('profileData')
            ->model(User::class);
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
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
                            ->label('New Password')
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
            ->statePath('passwordData')
            ->model(User::class);
    }

    public function saveProfile()
    {
        $data = $this->profileForm->getState();

        $user = User::find(Auth::user()->id);
        $user->name = $data['name'];
        $user->save();

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }

    public function savePassword()
    {
        $data = $this->passwordForm->getState();

        if (filled($data['new_password'] ?? null)) {
            $user = User::find(Auth::user()->id);
            $user->password = $data['new_password'];
            $user->save();

            session()->put([
                'password_hash_'.Auth::getDefaultDriver() => $user->getAuthPassword(),
            ]);

            $this->passwordForm->fill();

            Notification::make()
                ->title('Password updated successfully')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Please enter a new password')
                ->warning()
                ->send();
        }
    }
}