<?php

namespace App\Providers;

use App\Filament\Pages\Auth\Register;
use Illuminate\Support\ServiceProvider;
use Filament\Pages\Auth\Register as FilamentRegister;
use App\Http\Responses\Auth\CustomRegistrationResponse;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

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
        // FilamentView::registerRenderHook(
        //     PanelsRenderHook::CONTENT_END,
        //     fn (): View => view('filament.forms.components.sampleHook'),
        // );
    }
}