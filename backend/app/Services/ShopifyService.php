<?php

namespace App\Services;

use App\Models\Session;
use App\Services\TokenValidationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShopifyService
{
    protected TokenValidationService $tokenValidationService;

    public function __construct(
        private readonly string $apiKey = '',
        private readonly string $apiSecret = '',
        private readonly string $scopes = '',
        private readonly string $redirectUri = '',
        private readonly string $hostName = '',
    ) {
        $this->tokenValidationService = app(TokenValidationService::class);
    }

    /**
     * Thực hiện API call đến Shopify với token validation
     *
     * @param string $shop
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return array|null
     */
    public function makeAuthenticatedRequest(string $shop, string $endpoint, string $method = 'GET', array $data = []): ?array
    {
        // Validate session trước khi thực hiện request
        $validationResult = $this->tokenValidationService->validateSession($shop);

        if (!$validationResult['valid']) {
            Log::error("[ShopifyService] Token không hợp lệ", [
                'shop' => $shop,
                'reason' => $validationResult['reason']
            ]);
            return null;
        }

        $session = $validationResult['session'];
        $accessToken = $session->access_token;

        try {
            $url = "https://{$shop}/admin/api/2024-01/{$endpoint}";

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->send($method, $url, [
                'json' => $data
            ]);

            if ($response->successful()) {
                Log::info("[ShopifyService] API call thành công", [
                    'shop' => $shop,
                    'endpoint' => $endpoint,
                    'method' => $method
                ]);
                return $response->json();
            }

            // Log lỗi API
            Log::error("[ShopifyService] API call thất bại", [
                'shop' => $shop,
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("[ShopifyService] Exception trong API call", [
                'shop' => $shop,
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Lấy thông tin shop
     *
     * @param string $shop
     * @return array|null
     */
    public function getShopInfo(string $shop): ?array
    {
        $result = $this->makeAuthenticatedRequest($shop, 'shop.json');
        return $result['shop'] ?? null;
    }

    /**
     * Lấy danh sách sản phẩm
     *
     * @param string $shop
     * @param array $params
     * @return array|null
     */
    public function getProducts(string $shop, array $params = []): ?array
    {
        $endpoint = 'products.json';
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }

        $result = $this->makeAuthenticatedRequest($shop, $endpoint);
        return $result['products'] ?? null;
    }

    /**
     * Tạo sản phẩm mới
     *
     * @param string $shop
     * @param array $productData
     * @return array|null
     */
    public function createProduct(string $shop, array $productData): ?array
    {
        $result = $this->makeAuthenticatedRequest($shop, 'products.json', 'POST', [
            'product' => $productData
        ]);
        return $result['product'] ?? null;
    }

    /**
     * Cập nhật sản phẩm
     *
     * @param string $shop
     * @param int $productId
     * @param array $productData
     * @return array|null
     */
    public function updateProduct(string $shop, int $productId, array $productData): ?array
    {
        $result = $this->makeAuthenticatedRequest($shop, "products/{$productId}.json", 'PUT', [
            'product' => $productData
        ]);
        return $result['product'] ?? null;
    }

    /**
     * Xóa sản phẩm
     *
     * @param string $shop
     * @param int $productId
     * @return bool
     */
    public function deleteProduct(string $shop, int $productId): bool
    {
        $result = $this->makeAuthenticatedRequest($shop, "products/{$productId}.json", 'DELETE');
        return $result !== null;
    }

    /**
     * Kiểm tra trạng thái kết nối với shop
     *
     * @param string $shop
     * @return array
     */
    public function checkConnection(string $shop): array
    {
        $validationResult = $this->tokenValidationService->validateSession($shop);

        if (!$validationResult['valid']) {
            return [
                'connected' => false,
                'reason' => $validationResult['reason'],
                'session' => null
            ];
        }

        // Thử gọi API để đảm bảo kết nối thực sự hoạt động
        $shopInfo = $this->getShopInfo($shop);

        if (!$shopInfo) {
            return [
                'connected' => false,
                'reason' => 'Không thể lấy thông tin shop',
                'session' => $validationResult['session']
            ];
        }

        return [
            'connected' => true,
            'reason' => 'Kết nối hoạt động bình thường',
            'session' => $validationResult['session'],
            'shop_info' => $shopInfo
        ];
    }

    /**
     * Lấy access token cho shop
     *
     * @param string $shop
     * @return string|null
     */
    public function getAccessToken(string $shop): ?string
    {
        $session = Session::where('shop', $shop)
            ->where('access_token', '<>', null)
            ->first();

        return $session?->access_token;
    }

    /**
     * Kiểm tra xem shop có được cài đặt không
     *
     * @param string $shop
     * @return bool
     */
    public function isInstalled(string $shop): bool
    {
        $validationResult = $this->tokenValidationService->validateSession($shop);
        return $validationResult['valid'];
    }

    /**
     * Tạo URL để xác thực OAuth
     *
     * @param string $shop
     * @param string|null $state
     * @return string
     */
    public function getAuthUrl(string $shop, ?string $state = null): string
    {
        $state = $state ?? bin2hex(random_bytes(16));

        $params = http_build_query([
            'client_id' => $this->apiKey,
            'scope' => $this->scopes,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'grant_options[]' => 'per-user'
        ]);

        return "https://{$shop}/admin/oauth/authorize?{$params}";
    }
}
