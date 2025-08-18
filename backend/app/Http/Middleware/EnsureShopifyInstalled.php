<?php

namespace App\Http\Middleware;

use App\Lib\AuthRedirection;
use App\Models\Session;
use Closure;
use Illuminate\Http\Request;
use Shopify\Utils;
use Illuminate\Support\Facades\Log;

class EnsureShopifyInstalled
{
    /**
     * Checks if the shop in the query arguments is currently installed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info("[Middleware EnsureShopifyInstalled] request all:" . json_encode($request->all()));
        $shop = $request->query('shop') ? Utils::sanitizeShopDomain($request->query('shop')) : null;

        Log::info("[Middleware EnsureShopifyInstalled] shop: " . json_encode($shop));
        Log::info("[Middleware EnsureShopifyInstalled] appInstalled: " . json_encode(Session::where('shop', $shop)->where('access_token', '<>', null)->exists()));
        $appInstalled = $shop && Session::where('shop', $shop)->where('access_token', '<>', null)->exists();
        Log::info("[Middleware EnsureShopifyInstalled] appInstalled: " . json_encode($appInstalled));
        $isExitingIframe = preg_match("/^ExitIframe/i", $request->path());
        Log::info("[Middleware EnsureShopifyInstalled] isExitingIframe: " . json_encode($isExitingIframe));

        return ($appInstalled || $isExitingIframe) ? $next($request) : AuthRedirection::redirect($request);
    }
}
