<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureShopifyInstalled;
use App\Http\Middleware\ValidateShopifyToken;
use App\Http\Middleware\VerifyShopifyWebhook;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'shopify.installed' => EnsureShopifyInstalled::class,
            'validate.shopify.token' => ValidateShopifyToken::class,
            'verify.shopify.webhook' => VerifyShopifyWebhook::class,
        ]);

        // Exemption CSRF cho webhook routes
        $middleware->validateCsrfTokens(except: [
            'api/webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
