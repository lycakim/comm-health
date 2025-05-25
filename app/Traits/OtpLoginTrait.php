<?php

namespace App\Traits;

use App\Models\User;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Validator;

trait OtpLoginTrait
{
    public $phoneNumber;
    public $otp;
    public $otpSent = false;

    public function sendOtp()
    {
        // Validate the phone number
        $validator = Validator::make(
            ['phone' => $this->phoneNumber],
            ['phone' => 'required|numeric']
        );

        if ($validator->fails()) {
            $this->addError('phoneNumber', 'Invalid phone number.');
            return;
        }

        // Generate and send OTP
        $otp = rand(100000, 999999);
        Session::put('otp', $otp);
        Session::put('otp_phone', $this->phoneNumber);

        // Here you would integrate with your SMS provider to send the OTP
        // For example: SmsService::send($this->phoneNumber, "Your OTP is: $otp");

        $this->otpSent = true;
    }

    public function verifyOtp()
    {
        // Validate the OTP
        if ($this->otp != Session::get('otp') || $this->phoneNumber != Session::get('otp_phone')) {
            $this->addError('otp', 'Invalid OTP.');
            return;
        }

        // Authenticate the user
        $user = User::where('phone', $this->phoneNumber)->first();

        if ($user) {
            Auth::login($user);
            Session::forget('otp');
            Session::forget('otp_phone');
            return redirect()->intended(Filament::getUrl());
        } else {
            $this->addError('phoneNumber', 'User not found.');
        }
    }

    protected function getOtpFormComponent(): TextInput
    {
        return TextInput::make('otp')
            ->label('One-Time Password')
            ->numeric()
            ->required();
    }

    protected function getPhoneNumberFormComponent(): TextInput
    {
        return TextInput::make('phoneNumber')
            ->label('Phone Number')
            ->tel()
            ->required();
    }
}