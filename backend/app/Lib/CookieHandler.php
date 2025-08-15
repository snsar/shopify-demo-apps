<?php

namespace App\Lib;

use Illuminate\Support\Facades\Cookie;
use Shopify\Context;
use Shopify\Auth\OAuthCookie;
use Illuminate\Support\Facades\Log;

class CookieHandler
{
    public static function saveShopifyCookie(OAuthCookie $cookie)
    {
        Log::info("[CookieHandler] Saving cookie: " . $cookie->getName());
        Log::info("[CookieHandler] Cookie: " . json_encode($cookie));

        Cookie::queue(
            $cookie->getName(),
            $cookie->getValue(),
            $cookie->getExpire() ? ceil(($cookie->getExpire() - time()) / 60) : null,
            '/',
            parse_url(Context::$HOST_SCHEME . "://" . Context::$HOST_NAME, PHP_URL_HOST),
            $cookie->isSecure(),
            $cookie->isHttpOnly(),
            false,
            'Lax'
        );

        return true;
    }
}
