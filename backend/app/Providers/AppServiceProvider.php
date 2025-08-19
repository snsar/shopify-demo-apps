<?php

namespace App\Providers;

use App\Lib\DbSessionStorage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Shopify\ApiVersion;
use Shopify\Context;

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
    public function boot(): void {
            $host = str_replace('https://', '', env('HOST', 'not_defined'));
    
            $customDomain = env('SHOP_CUSTOM_DOMAIN', null);
            Context::initialize(
                env('SHOPIFY_API_KEY', 'not_defined'),
                env('SHOPIFY_API_SECRET', 'not_defined'),
                env('SCOPES', 'not_defined'),
                $host,
                new DbSessionStorage(),
                ApiVersion::LATEST,
                true,
                false,
                null,
                '',
                null,
                (array)$customDomain,
            );
    
            URL::forceRootUrl("https://$host");
            URL::forceScheme('https');
    
            // Registry::addHandler(Topics::APP_UNINSTALLED, new AppUninstalled());
    
            /*
             * This sets up the mandatory privacy webhooks. You’ll need to fill in the endpoint to be used by your app in
             * the “Privacy webhooks” section in the “App setup” tab, and customize the code when you store customer data
             * in the handlers being registered below.
             *
             * More details can be found on shopify.dev:
             * https://shopify.dev/docs/apps/webhooks/configuration/mandatory-webhooks
             *
             * Note that you'll only receive these webhooks if your app has the relevant scopes as detailed in the docs.
             */
            // Registry::addHandler('CUSTOMERS_DATA_REQUEST', new CustomersDataRequest());
            // Registry::addHandler('CUSTOMERS_REDACT', new CustomersRedact());
            // Registry::addHandler('SHOP_REDACT', new ShopRedact());
    
    }
}
