<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
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

    
}
