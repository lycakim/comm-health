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

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return in_array(Auth::user()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
            RoleEnum::BHW,
        ]);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Manage your account settings';
    }

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);
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

        redirect('/commhealth/settings');

        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->send();
    }
}