<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The hub is always served over HTTPS in production — make every
        // generated URL (and the Turnstile / API links) use it.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
