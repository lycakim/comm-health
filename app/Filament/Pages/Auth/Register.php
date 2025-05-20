<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Models\Patient;
use Filament\Forms\Get;
use App\Models\Barangay;
use App\Models\Category;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Auth\Register as BaseRegister;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use App\Http\Responses\Auth\CustomRegistrationResponse;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

class Register extends BaseRegister
{
    use WithRateLimiting;

    protected static string $view = 'filament.pages.auth.register';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                    ]),
                // TextInput::make('email')
                //     ->hidden(fn (Get $get): bool => $get('role') === 'resident')
                //     ->email()
                //     ->required()
                //     ->maxLength(255)
                //     ->unique(table: 'users'),

                DatePicker::make('birth_date')
                    ->label('Date of Birth')
                    ->required(),

                Select::make('gender')
                    ->required()
                    ->options([
                        'male' => 'Male', 
                        'female' => 'Female',
                    ]),
                Select::make('category_id')
                    ->label('Category')
                    ->preload()
                    ->searchable()
                    ->options(Category::query()->get()->pluck('name', 'id')->toArray()),
                
                // Password fields
                // TextInput::make('password')
                //     ->hidden(fn (Get $get): bool => $get('role') === 'resident')
                //     ->password()
                //     ->required()
                //     ->minLength(8)
                //     ->revealable()
                //     ->same('passwordConfirmation'),
                // TextInput::make('passwordConfirmation')
                //     ->hidden(fn (Get $get): bool => $get('role') === 'resident')
                //     ->password()
                //     ->required()
                //     ->revealable()
                //     ->minLength(8)
                //     ->label('Confirm Password'),
                
                Select::make('role')
                    ->label('Account Type')
                    ->options([
                        'resident' => 'Patient',
                        'midwife' => 'Midwife',
                        'mho' => 'Municipal Health Officer',
                        'bhw' => 'Barangay Health Worker',
                    ])
                    ->default('resident')
                    ->required(),
                Hidden::make('role')->default('resident'), 
                Select::make('barangay_id')
                    ->label('Barangay')
                    ->options(Barangay::query()->get()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(__('filament-panels::pages/auth/register.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        Patient::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birth_date' => $data['birth_date'],
            'sex' => $data['gender'],
            'barangay_id' => $data['barangay_id'],
        ]);
        
        $admins = User::where('role', 'mho')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\DatabaseNotification(
                'New Patient Registration',
                'A new patient named ' . $data['first_name'] . ' ' . $data['last_name'] . ' has registered.',
                'heroicon-o-user-plus',
                'success'
            ));
        }

        // Optional: Send broadcast notification to admins if live updates are needed
        // This will require setting up Laravel Echo and Pusher/Laravel WebSockets
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\BroadcastNotification(
                'New Patient Registration',
                'A new patient named ' . $data['first_name'] . ' ' . $data['last_name'] . ' has registered.',
                'heroicon-o-user-plus',
                'success'
            ));
        }

        // if ($data['role'] == 'resident') {
        //     Patient::create([
        //         'first_name' => $data['first_name'],
        //         'last_name' => $data['last_name'],
        //         'birth_date' => $data['birth_date'],
        //         'sex' => $data['gender'],
        //         'barangay_id' => $data['barangay_id'],
        //     ]);
        // }
        // else {
        //     User::create([
        //         'name' => $data['first_name'] . ' ' . $data['last_name'],
        //         'email' => $data['email'],
        //         'password' => Hash::make($data['password']),
        //         'role' => $data['role'],
        //     ]);
        // }


        Notification::make()
            ->title('Registration successful')
            ->body('You are now registered and your account is under verification')
            ->success()
            ->send();

        // Reset the form fields
        $this->form->fill([]);
        
        return null;
    }
}