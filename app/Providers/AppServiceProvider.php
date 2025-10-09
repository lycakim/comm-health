<?php

namespace App\Providers;

use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use App\Filament\Pages\Auth\Register;
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

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_END,
            fn (): View => view('filament.navbar'),
        );
    }
}