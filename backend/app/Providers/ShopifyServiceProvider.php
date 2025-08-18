<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Services\ShopifyService;

class ShopifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ShopifyService::class, function () {
            return new ShopifyService(
                config('shopify.api_key'),
                config('shopify.api_secret'),
                config('shopify.scopes'),
                config('shopify.redirect_uri'),
                config('shopify.host_name'),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->setupShopifyConfig();
    }

    private function setupShopifyConfig()
    {
        $host = str_replace('https://', '', env('HOST', 'not_defined'));

        URL::forceRootUrl("https://$host");
        URL::forceScheme('https');
    }

}
