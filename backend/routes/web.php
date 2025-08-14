<?php

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Shopify\Context;
use Shopify\Utils;

// Route::fallback(function (Request $request) {
//     if (Context::$IS_EMBEDDED_APP &&  $request->query("embedded", false) === "1") {
//         if (env('APP_ENV') === 'production') {
//             return file_get_contents(public_path('index.html'));
//         } else {
//             return file_get_contents(base_path('frontend/index.html'));
//         }
//     } else {
//         return redirect(Utils::getEmbeddedAppUrl($request->query("host", null)) . "/" . $request->path());
//     }
// })->middleware('shopify.installed');

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to the Shopify App API',
    ]);
});
