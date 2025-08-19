<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Shopify\Utils;
use App\Models\Session;
use App\Lib\AuthRedirection;
use Illuminate\Support\Facades\Config;
use Shopify\Auth\OAuth;
use Shopify\Webhooks\Registry;
use Shopify\Webhooks\Topics;
use App\Lib\EnsureBilling;
use App\Http\Controllers\AuthController;





Route::get('/webhook', function (Request $request) {
    Log::info("[Webhook] " . json_encode($request->all()));

    return response()->json([
        'message' => 'Webhook',
        'request' => $request->all(),
    ]);
});

Route::get('/auth', function (Request $request) {
    Log::info("[Auth] " . json_encode($request->all()));

    return response()->json([
        'message' => 'Auth',
        'request' => $request->all(),
    ]);
});

Route::get('/auth/callback', [AuthController::class, 'callback']);
