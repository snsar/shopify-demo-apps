<?php

namespace App\Services;

use App\Models\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TokenValidationService
{
    /**
     * Kiểm tra tính hợp lệ của token Shopify
     *
     * @param string $shop
     * @param string $accessToken
     * @return bool
     */
    public function validateToken(string $shop, string $accessToken): bool
    {
        try {
            // Gọi API shop info để kiểm tra token
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->get("https://{$shop}/admin/api/2024-01/shop.json");

            if ($response->successful()) {
                Log::info("[TokenValidation] Token hợp lệ cho shop: {$shop}");
                return true;
            }

            // Kiểm tra lỗi cụ thể
            $statusCode = $response->status();
            $responseBody = $response->json();

            Log::warning("[TokenValidation] Token không hợp lệ", [
                'shop' => $shop,
                'status_code' => $statusCode,
                'response' => $responseBody,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("[TokenValidation] Lỗi khi validate token", [
                'shop' => $shop,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Kiểm tra và validate session từ database
     *
     * @param string $shop
     * @return array
     */
    public function validateSession(string $shop): array
    {
        $session = Session::where('shop', $shop)
            ->where('access_token', '<>', null)
            ->first();

        if (!$session) {
            return [
                'valid' => false,
                'reason' => 'Session không tồn tại',
                'session' => null,
            ];
        }

        // Kiểm tra token hết hạn (đối với online tokens)
        if ($session->is_online && $session->expires_at && Carbon::now()->gt($session->expires_at)) {
            return [
                'valid' => false,
                'reason' => 'Token đã hết hạn',
                'session' => $session,
            ];
        }

        // Kiểm tra tính hợp lệ của token với Shopify
        $isValid = $this->validateToken($shop, $session->access_token);

        if (!$isValid) {
            return [
                'valid' => false,
                'reason' => 'Token không hợp lệ với Shopify',
                'session' => $session,
            ];
        }

        return [
            'valid' => true,
            'reason' => 'Token hợp lệ',
            'session' => $session,
        ];
    }

    /**
     * Kiểm tra scopes của token
     *
     * @param string $shop
     * @param string $accessToken
     * @param array $requiredScopes
     * @return bool
     */
    public function validateScopes(string $shop, string $accessToken, array $requiredScopes = []): bool
    {
        try {
            // Gọi API để lấy thông tin về access scopes
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Accept' => 'application/json',
            ])->get("https://{$shop}/admin/oauth/access_scopes.json");

            if (!$response->successful()) {
                Log::warning("[TokenValidation] Không thể lấy access scopes", [
                    'shop' => $shop,
                    'status' => $response->status(),
                ]);
                return false;
            }

            $data = $response->json();
            $accessScopes = collect($data['access_scopes'] ?? [])->pluck('handle')->toArray();

            // Kiểm tra từng scope yêu cầu
            foreach ($requiredScopes as $scope) {
                if (!in_array($scope, $accessScopes)) {
                    Log::warning("[TokenValidation] Thiếu scope: {$scope}", [
                        'shop' => $shop,
                        'available_scopes' => $accessScopes,
                        'required_scopes' => $requiredScopes,
                    ]);
                    return false;
                }
            }

            Log::info("[TokenValidation] Scopes hợp lệ", [
                'shop' => $shop,
                'scopes' => $accessScopes,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("[TokenValidation] Lỗi khi validate scopes", [
                'shop' => $shop,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Lấy thông tin shop để verify token
     *
     * @param string $shop
     * @param string $accessToken
     * @return array|null
     */
    public function getShopInfo(string $shop, string $accessToken): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Accept' => 'application/json',
            ])->get("https://{$shop}/admin/api/2024-01/shop.json");

            if ($response->successful()) {
                return $response->json()['shop'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("[TokenValidation] Lỗi khi lấy shop info", [
                'shop' => $shop,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Xóa session không hợp lệ
     *
     * @param string $shop
     * @return bool
     */
    public function removeInvalidSession(string $shop): bool
    {
        try {
            $deleted = Session::where('shop', $shop)->delete();
            Log::info("[TokenValidation] Đã xóa session không hợp lệ", [
                'shop' => $shop,
                'deleted_count' => $deleted,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("[TokenValidation] Lỗi khi xóa session", [
                'shop' => $shop,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
