<?php

namespace App\Providers;

use App\Lib\DbSessionStorage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Shopify\ApiVersion;
use Shopify\Context;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
