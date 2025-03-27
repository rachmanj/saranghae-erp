<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('pdf', function() {
            return new Pdf();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
} 