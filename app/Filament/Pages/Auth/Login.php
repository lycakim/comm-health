<?php

namespace App\Filament\Pages\Auth;

use Carbon\Carbon;
use App\Models\User;
use Filament\Forms\Form;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use App\Http\Responses\Auth\CustomLoginResponse;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class Login extends BaseLogin
{
    public $activeTab = 'tab1';
    public $showOtpForm = false;
    public $userEmail = '';

    protected static string $view = 'filament.pages.auth.login';
    
    public function form(Form $form): Form
    {
        if ($this->showOtpForm) {
            return $form->schema([
                $this->getOtpFormComponent(),
            ]);
        }

        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getOtpFormComponent(): Component
    {
        return Fieldset::make('Enter OTP Code')
            ->schema([
                ...collect(range(1, 6))->map(function ($i) {
                    return TextInput::make("digit_{$i}")
                        ->label(false)
                        ->extraInputAttributes([
                            'maxlength' => 1,
                            'inputmode' => 'numeric',
                            'class' => 'otp-input text-center text-xl font-mono',
                            'autocomplete' => 'one-time-code',
                            'oninput' => "moveToNext(this, {$i})",
                            'onpaste' => $i === 1 ? 'handlePaste(event)' : null,
                        ])
                        ->type('text');
                }),
            ])
            ->columns(6)
            ->extraAttributes([
                'x-data' => '',
                'x-init' => 'initOtpInput()',
            ]);
    }

    public function authenticate(): ?LoginResponse
    {
        if ($this->showOtpForm) {
            return $this->verifyOtp();
        }

        $data = $this->form->getState();
        
        if (!$this->attemptAuthentication($data)) {
            $this->throwFailureValidationException();
        }

        $user = Auth::user();
        
        // Check if user can login directly (bypass OTP)
        if (method_exists($user, 'canLoginDirectly') && $user->canLoginDirectly()) {
            // return app(LoginResponse::class);
            return $this->handleSuccessfulLogin($user);
        }

        // Logout temporarily and send OTP
        Auth::logout();
        $this->userEmail = $data['email'];
        $this->sendOtp($data['email']);
        $this->showOtpForm = true;
        
        Notification::make()
            ->title('OTP code resent!')
            ->body('OTP code resent to your email address.')
            ->success()
            ->send();
        
        return null;
    }

    protected function attemptAuthentication(array $data): bool
    {
        return Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'] ?? false);
    }

    protected function sendOtp(string $email): void
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            $otp = $this->generateOtpCode();
            $this->storeOtpCode($user, $otp);
            $this->sendOtpEmail($user, $otp);
        }
    }

    protected function generateOtpCode(): string
    {
        $length = config('filament-otp-login.code.length', 6);
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    protected function storeOtpCode(User $user, string $otp): void
    {
        $tableName = config('filament-otp-login.table_name', 'codes');
        $expiresIn = config('filament-otp-login.code.expires', 120);

        DB::table($tableName)->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'code' => $otp,
                'expires_at' => Carbon::now()->addSeconds($expiresIn),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );
    }

    protected function sendOtpEmail(User $user, string $otp): void
    {
        $expiresIn = config('filament-otp-login.code.expires', 120);
        $user->notify(new \App\Notifications\SendOtpNotification($otp, $expiresIn));
    }

    protected function verifyOtp(): ?LoginResponse
    {
        $data = $this->form->getState();

        // Combine the 6 digits into a single OTP code
        $code = $data['digit_1'] . $data['digit_2'] . $data['digit_3'] . $data['digit_4'] . $data['digit_5'] . $data['digit_6'];

        if ($this->validateOtpCode($this->userEmail, $code)) {
            $user = User::where('email', $this->userEmail)->first();
            Auth::login($user);

            $this->cleanupOtpCode($this->userEmail);
            session()->regenerate();

            return app(LoginResponse::class);
        }

        $this->addError('digit_1', 'Invalid or expired OTP code.');
        return null;
    }

    protected function validateOtpCode(string $email, string $otpCode): bool
    {
        $tableName = config('filament-otp-login.table_name', 'codes');

        $record = DB::table($tableName)
            ->where('email', $email)
            ->where('code', $otpCode)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        return $record !== null;
    }

    protected function cleanupOtpCode(string $email): void
    {
        $tableName = config('filament-otp-login.table_name', 'codes');
        
        DB::table($tableName)
            ->where('email', $email)
            ->delete();
    }

    public function resendOtp(): void
    {
        if ($this->userEmail) {
            $this->sendOtp($this->userEmail);
            Notification::make()
                ->title('OTP code resent!')
                ->body('OTP code resent to your email address.')
                ->success()
                ->send();
        }
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'showOtpForm' => $this->showOtpForm,
        ]);
    }

    protected function handleSuccessfulLogin($user): ?LoginResponse
    {
        if (!$user || !$user->user_type) {
            $this->redirect('/commhealth');
            return null;
        }

        // Redirect to user-type specific dashboard
        $redirectUrl = match($user->user_type) {
            'mho' => '/commhealth/mho/dashboard',
            'bhw' => '/commhealth/bhw/dashboard',
            'midwife' => '/commhealth/midwife/dashboard',
            'resident' => '/commhealth/resident/dashboard',
            'admin' => '/commhealth/admin/dashboard',
            default => '/commhealth/dashboard'
        };

        $this->redirect($redirectUrl);
        return null;
    }
}