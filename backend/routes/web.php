<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::fallback(function (Request $request) {
    Log::info('Fallback call');
    Log::info("[Fallback] Query: " . json_encode($request->query()));

    if (env('APP_ENV') === 'production') {
        return redirect('https://b2b.qoutesnap.local/frontend/');
    } else {
        return redirect('https://b2b.quotesnap.local/frontend/');
    }
})->middleware('shopify.installed');
