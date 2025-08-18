<?php

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


// Route::get('/auth/callback', function (Request $request) {
//     $shop = $request->query('shop');
//     Log::info('Shop: ' . $shop);

//     return response()->json([
//         'shop' => $shop,
//         'message' => 'Hello World',
//     ]);
// });

// Route::get('/auth', function (Request $request) {
//     Log::info('Auth');
//     $shop = Utils::sanitizeShopDomain($request->query('shop'));

//     // Delete any previously created OAuth sessions that were not completed (don't have an access token)
//     Session::where('shop', $shop)->where('access_token', null)->delete();

//     return AuthRedirection::redirect($request);
// });


// Route::get('/auth/callback', function (Request $request) {
//     Log::info(
//         "[Auth callback] " . json_encode($request->all())
//     );

//     Log::info("[Auth callback] Cookie: " . json_encode($request->cookie()));
//     Log::info("[Auth callback] Query: " . json_encode($request->query()));

//     $session = OAuth::callback(
//         $request->cookie(),
//         $request->query(),
//         ['App\Lib\CookieHandler', 'saveShopifyCookie']
//     );

//     $host = $request->query('host');
//     $shop = Utils::sanitizeShopDomain($request->query('shop'));

//     $response = Registry::register('/api/webhooks', Topics::APP_UNINSTALLED, $shop, $session->getAccessToken());
//     if ($response->isSuccess()) {
//         Log::debug("Registered APP_UNINSTALLED webhook for shop $shop");
//     } else {
//         Log::error(
//             "Failed to register APP_UNINSTALLED webhook for shop $shop with response body: " .
//                 print_r($response->getBody(), true)
//         );
//     }

//     $redirectUrl = Utils::getEmbeddedAppUrl($host);
//     if (Config::get('shopify.billing.required')) {
//         list($hasPayment, $confirmationUrl) = EnsureBilling::check($session, Config::get('shopify.billing'));

//         if (!$hasPayment) {
//             $redirectUrl = $confirmationUrl;
//         }
//     }

//     return redirect($redirectUrl);
// });


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
