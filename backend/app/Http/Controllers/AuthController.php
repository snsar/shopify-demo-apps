<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function auth(Request $request)
    {
        $shop = $this->sanitizeShop($request->query('shop'));

        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        // Tạo state parameter để bảo mật (ngăn CSRF attacks)
        $state = Str::random(32);

        // Lưu state vào cookie để verify sau
        Cookie::queue('oauth_state', $state, 10); // 10 minutes
        Cookie::queue('oauth_shop', $shop, 10);

        // Tạo authorization URL
        $authUrl = $this->buildAuthUrl($shop, $state);

        Log::info("[Custom Auth] Redirecting to: $authUrl");

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {

        Log::info("[Auth callback]", $request->all());
        $hmac = $request->query('hmac');
        $params = $request->except('hmac');
        ksort($params);
        Log::info("[Auth callback] " . json_encode($params));
        // Verify state param



        // Verify HMAC
        $computedHmac = hash_hmac('sha256', urldecode(http_build_query($params)), env('SHOPIFY_API_SECRET'));
        Log::info("[Auth callback] " . json_encode($computedHmac));
        if (!hash_equals($hmac, $computedHmac)) {
            abort(403, 'HMAC validation failed');
        }

        // Exchange code -> Access token (offline token)
        $response = Http::post("https://{$request->shop}/admin/oauth/access_token", [
            'client_id' => env('SHOPIFY_API_KEY'),
            'client_secret' => env('SHOPIFY_API_SECRET'),
            'code' => $request->code,
        ]);
        Log::info("[Auth callback] " . json_encode($response));
        $data = $response->json();
        // Log::info("[Auth callback] " . json_encode($data));

        // Lưu shop + access token offline vào DB
        // Shop::updateOrCreate([...], ['token' => $data['access_token']]);

        return response()->json([
            'message' => 'App installed!',
            'token'   => $data,
        ]);
    }

    public function buildAuthUrl(string $shop, string $state): string
    {
        $params = [
            'client_id' => env('SHOPIFY_API_KEY'),
            'scope' => env('SHOPIFY_SCOPES'),
            'redirect_uri' => env('SHOPIFY_REDIRECT_URI'),
            'state' => $state,
            'grant_options[]' => 'offline',
        ];

        $query = http_build_query($params);
        return "https://{$shop}/admin/oauth/authorize?{$query}";
    }
}
