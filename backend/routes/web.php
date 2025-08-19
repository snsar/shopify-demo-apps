<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Shopify\Context;
use Shopify\Utils;

Route::fallback(function (Request $request) {
    Log::info('Fallback call');
    Log::info("[Fallback] Query: " . json_encode($request->query()));

    if (Context::$IS_EMBEDDED_APP &&  $request->query("embedded", false) === "1") {

        if (env('APP_ENV') === 'production') {
            return redirect('https://b2b.qoutesnap.local/frontend/');
        } else {
            return redirect('https://b2b.quotesnap.local/frontend/');
        }
    } else {
        return redirect(Utils::getEmbeddedAppUrl($request->query("host", null)) . "/" . $request->path());
    }
})->middleware('shopify.installed');
