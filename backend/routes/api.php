<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Shopify\Utils;
use App\Models\Session;
use App\Lib\AuthRedirection;

// Route::get('/auth/callback', function (Request $request) {
//     $shop = $request->query('shop');
//     Log::info('Shop: ' . $shop);

//     return response()->json([
//         'shop' => $shop,
//         'message' => 'Hello World',
//     ]);
// });

Route::get('/auth', function (Request $request) {
    $shop = Utils::sanitizeShopDomain($request->query('shop'));

    // Delete any previously created OAuth sessions that were not completed (don't have an access token)
    Session::where('shop', $shop)->where('access_token', null)->delete();

    return AuthRedirection::redirect($request);
});
