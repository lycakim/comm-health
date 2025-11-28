<?php

namespace App\Providers;

use App\Models\Barangay;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use App\Listeners\SetFreshLoginFlag;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\Auth\Register;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\Http\Responses\Auth\LoginResponse;
use App\Http\Responses\Auth\CustomLoginResponse;
use Filament\Pages\Auth\Register as FilamentRegister;
use App\Http\Responses\Auth\CustomRegistrationResponse;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FilamentRegister::class, Register::class);
        $this->app->bind(RegistrationResponse::class, CustomRegistrationResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_START,
            fn (): View => view('filament.hooks.chat-widget'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_END,
            fn (): string => view('filament.forms.components.footer')->render()
        );

        // Name and Role
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_END,
            fn (): View => view('filament.navbar'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_START,
            fn (): View => view('top-nav-header', [
                'barangay' => ($user = Auth::user()) && $user->barangay_id && ($barangay = \App\Models\Barangay::find($user->barangay_id))
                    ? 'Barangay ' . $barangay->name
                    : '',
            ]),
        );

        // Set fresh login flag on login
        Event::listen(Login::class, SetFreshLoginFlag::class);

        // Clear the session flag on logout
        Event::listen(Logout::class, function () {
            session()->forget('privacy_accepted_this_session');
        });
    }
}