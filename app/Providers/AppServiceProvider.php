<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator; // Add this line

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(UrlGenerator $url): void  // Add UrlGenerator parameter
    {
        if (env('APP_ENV') === 'production') {
            $url->forceScheme('https');
        }
    }
}