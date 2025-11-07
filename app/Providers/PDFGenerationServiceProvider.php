<?php

namespace App\Providers;

use App\Services\PDFGenerationService;
use Illuminate\Support\ServiceProvider;

class PDFGenerationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PDFGenerationService::class, function ($app) {
            return new PDFGenerationService();
        });

        // Register alias for easier access
        $this->app->alias(PDFGenerationService::class, 'pdf.generation');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}