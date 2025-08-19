<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Session;

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

        if (!$response->successful() || !isset($data['access_token'])) {
            Log::error("[Auth callback] Failed to get access token", ['response' => $data]);
            abort(500, 'Failed to get access token from Shopify');
        }

        // Lưu session vào database
        $sessionId = 'offline_' . $request->shop;
        $session = Session::updateOrCreate(
            [
                'session_id' => $sessionId,
            ],
            [
                'shop' => $request->shop,
                'is_online' => false, // offline token
                'state' => $request->state ?? '',
                'scope' => $data['scope'] ?? null,
                'access_token' => $data['access_token'],
                'expires_at' => null, // offline token không hết hạn
            ]
        );

        Log::info("[Auth callback] Session saved", ['session_id' => $session->id]);

        return response()->json([
            'message' => 'App installed successfully!',
            'shop' => $request->shop,
            'session_id' => $session->session_id,
        ]);
    }
}
