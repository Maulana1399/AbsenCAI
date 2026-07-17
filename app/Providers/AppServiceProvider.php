<?php

namespace App\Providers;

use App\Support\ActiveEventContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActiveEventContext::class, function () {
            return new ActiveEventContext;
        });
    }

    public function boot(): void
    {
        //
    }
}
