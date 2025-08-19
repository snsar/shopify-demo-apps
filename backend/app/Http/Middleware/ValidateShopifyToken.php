<?php

namespace App\Http\Middleware;

use App\Services\TokenValidationService;
use App\Lib\AuthRedirection;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Shopify\Utils;
use Shopify\Clients\Graphql;
use Shopify\Context;

class ValidateShopifyToken
{
    public const ACCESS_MODE_ONLINE = 'online';
    public const ACCESS_MODE_OFFLINE = 'offline';

    public const TEST_GRAPHQL_QUERY = <<<QUERY
    {
        shop {
            name
            id
            myshopifyDomain
            plan {
                displayName
            }
        }
    }
    QUERY;

    protected TokenValidationService $tokenValidationService;

    public function __construct(TokenValidationService $tokenValidationService)
    {
        $this->tokenValidationService = $tokenValidationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $accessMode
     * @param  string|null  $requiredScopes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $accessMode = self::ACCESS_MODE_OFFLINE, ?string $requiredScopes = null)
    {
        // Validate access mode
        switch ($accessMode) {
            case self::ACCESS_MODE_ONLINE:
                $isOnline = true;
                break;
            case self::ACCESS_MODE_OFFLINE:
                $isOnline = false;
                break;
            default:
                throw new Exception(
                    "Unrecognized access mode '$accessMode', accepted values are 'online' and 'offline'"
                );
        }

        Log::info("[ValidateShopifyToken] Bắt đầu validate token", [
            'access_mode' => $accessMode,
            'required_scopes' => $requiredScopes
        ]);

        // Lấy shop từ request
        $shop = $this->getShopFromRequest($request);

        if (!$shop) {
            Log::warning("[ValidateShopifyToken] Không tìm thấy shop trong request");
            return $this->handleMissingShop($request);
        }

        // Validate session và token
        $validationResult = $this->tokenValidationService->validateSession($shop);

        if (!$validationResult['valid']) {
            Log::warning("[ValidateShopifyToken] Session không hợp lệ", [
                'shop' => $shop,
                'reason' => $validationResult['reason']
            ]);

            return $this->handleInvalidSession($request, $shop, $validationResult['reason']);
        }

        $session = $validationResult['session'];

        // Kiểm tra access mode khớp với session
        if ($session->is_online !== $isOnline) {
            Log::warning("[ValidateShopifyToken] Access mode không khớp", [
                'shop' => $shop,
                'required_mode' => $accessMode,
                'session_mode' => $session->is_online ? 'online' : 'offline'
            ]);

            return $this->handleInvalidSession($request, $shop, 'Access mode mismatch');
        }

        // Kiểm tra token với Shopify bằng GraphQL (giống như official middleware)
        if (!$this->validateTokenWithShopify($session)) {
            Log::warning("[ValidateShopifyToken] Token không hợp lệ với Shopify", [
                'shop' => $shop,
                'session_id' => $session->session_id
            ]);

            // Xóa session không hợp lệ
            $this->tokenValidationService->removeInvalidSession($shop);

            return $this->handleInvalidSession($request, $shop, 'Token invalid with Shopify API');
        }

        // Kiểm tra scopes nếu được yêu cầu
        if ($requiredScopes) {
            $scopesArray = array_map('trim', explode(',', $requiredScopes));
            $scopesValid = $this->tokenValidationService->validateScopes(
                $shop,
                $session->access_token,
                $scopesArray
            );

            if (!$scopesValid) {
                Log::warning("[ValidateShopifyToken] Scopes không đủ", [
                    'shop' => $shop,
                    'required_scopes' => $scopesArray,
                    'session_scopes' => $session->scope
                ]);

                return $this->handleInsufficientPermissions($request, $scopesArray);
            }
        }

        // Thêm session vào request attributes (giống như official middleware)
        $request->attributes->set('shopifySession', $session);
        $request->attributes->set('shopifyShop', $shop);

        // Backward compatibility - vẫn merge vào request input
        $request->merge([
            'shopify_session' => $session,
            'shopify_shop' => $shop,
            'shopify_token' => $session->access_token
        ]);

        Log::info("[ValidateShopifyToken] Token hợp lệ, tiếp tục request", [
            'shop' => $shop,
            'session_id' => $session->session_id,
            'access_mode' => $accessMode
        ]);

        return $next($request);
    }

    /**
     * Validate token với Shopify bằng GraphQL query
     *
     * @param mixed $session
     * @return bool
     */
    protected function validateTokenWithShopify($session): bool
    {
        try {
            $client = new Graphql($session->shop, $session->access_token);
            $response = $client->query(self::TEST_GRAPHQL_QUERY);

            $isValid = $response->getStatusCode() === 200;

            if ($isValid) {
                Log::info("[ValidateShopifyToken] GraphQL validation thành công", [
                    'shop' => $session->shop,
                    'session_id' => $session->session_id
                ]);
            } else {
                Log::warning("[ValidateShopifyToken] GraphQL validation thất bại", [
                    'shop' => $session->shop,
                    'status_code' => $response->getStatusCode(),
                    'response' => $response->getBody()
                ]);
            }

            return $isValid;
        } catch (Exception $e) {
            Log::error("[ValidateShopifyToken] Exception trong GraphQL validation", [
                'shop' => $session->shop,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy shop từ request (giống như official middleware)
     *
     * @param Request $request
     * @return string|null
     */
    protected function getShopFromRequest(Request $request): ?string
    {
        $shop = Utils::sanitizeShopDomain($request->query('shop', ''));

        if (!$shop) {
            // Thử lấy từ header
            $shop = $request->header('X-Shopify-Shop-Domain');
        }

        if (!$shop) {
            // Thử lấy từ body
            $shop = $request->input('shop');
        }

        if (!$shop) {
            // Thử lấy từ Bearer token (cho embedded apps)
            $bearerPresent = preg_match("/Bearer (.*)/", $request->header('Authorization', ''), $bearerMatches);
            if ($bearerPresent !== false && Context::$IS_EMBEDDED_APP) {
                try {
                    $payload = Utils::decodeSessionToken($bearerMatches[1]);
                    $shop = parse_url($payload['dest'], PHP_URL_HOST);
                } catch (Exception $e) {
                    Log::warning("[ValidateShopifyToken] Không thể decode session token", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $shop ? Utils::sanitizeShopDomain($shop) : null;
    }

    /**
     * Xử lý khi thiếu shop parameter
     *
     * @param Request $request
     * @return mixed
     */
    protected function handleMissingShop(Request $request)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'Shop parameter is required',
                'message' => 'Tham số shop là bắt buộc'
            ], 400);
        }

        // Redirect đến auth với shop rỗng (sẽ được xử lý bởi auth flow)
        return redirect("/api/auth");
    }

    /**
     * Xử lý khi session không hợp lệ
     *
     * @param Request $request
     * @param string $shop
     * @param string $reason
     * @return mixed
     */
    protected function handleInvalidSession(Request $request, string $shop, string $reason)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'Invalid or expired session',
                'message' => 'Session không hợp lệ hoặc đã hết hạn',
                'reason' => $reason,
                'shop' => $shop,
                'auth_url' => "/api/auth?shop=$shop"
            ], 401);
        }

        // Redirect để xác thực lại (giống như official middleware)
        return redirect("/api/auth?shop=$shop");
    }

    /**
     * Xử lý khi không đủ permissions
     *
     * @param Request $request
     * @param array $requiredScopes
     * @return mixed
     */
    protected function handleInsufficientPermissions(Request $request, array $requiredScopes)
    {
        return response()->json([
            'error' => 'Insufficient permissions',
            'message' => 'Ứng dụng không có đủ quyền để thực hiện hành động này',
            'required_scopes' => $requiredScopes
        ], 403);
    }
}
