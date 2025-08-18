<?php

namespace App\Lib;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Shopify\Auth\OAuth;
use Shopify\Context;
use Shopify\Utils;
use Illuminate\Support\Facades\Log;

class AuthRedirection
{
    public static function redirect(Request $request, bool $isOnline = false): RedirectResponse
    {
        Log::info("[AuthRedirection] request all:" . json_encode($request->all()));
        $shop = Utils::sanitizeShopDomain($request->query("shop"));
        Log::info("[AuthRedirection] shop: " . json_encode($shop));
        Log::info("[AuthRedirection] isEmbeddedApp: " . json_encode(Context::$IS_EMBEDDED_APP));
        Log::info("[AuthRedirection] embedded: " . json_encode($request->query("embedded", false)));
        if (Context::$IS_EMBEDDED_APP && $request->query("embedded", false) === "1") {
            $redirectUrl = self::clientSideRedirectUrl($shop, $request->query());
            Log::info("[AuthRedirection] redirectUrl: " . json_encode($redirectUrl));
        } else {
            $redirectUrl = self::serverSideRedirectUrl($shop, $isOnline);
            Log::info("[AuthRedirection] redirectUrl: " . json_encode($redirectUrl));
        }

        Log::info("[AuthRedirection] redirecting to: " . $redirectUrl);
        return redirect($redirectUrl);
    }

    private static function serverSideRedirectUrl(string $shop, bool $isOnline): string
    {
        return OAuth::begin(
            $shop,
            '/api/auth/callback',
            $isOnline,
            ['App\Lib\CookieHandler', 'saveShopifyCookie'],
        );
    }

    private static function clientSideRedirectUrl($shop, array $query): string
    {
        $appHost = Context::$HOST_NAME;
        $redirectUri = urlencode("https://$appHost/api/auth?shop=$shop");

        $queryString = http_build_query(array_merge($query, ["redirectUri" => $redirectUri]));
        return "/ExitIframe?$queryString";
    }
}
