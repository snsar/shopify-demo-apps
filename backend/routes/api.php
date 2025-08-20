<?php

declare(strict_types=1);

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
use App\Services\ShopifyService;
use App\Services\TokenValidationService;
use App\Http\Controllers\ShopifyWebhookController;
use App\Http\Controllers\ShopifyImportController;

// Routes không cần xác thực
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

// Routes cần xác thực token Shopify (offline mode - default)
Route::middleware(['validate.shopify.token'])->group(function () {

    // Kiểm tra trạng thái kết nối
    Route::get('/connection/status', function (Request $request, ShopifyService $shopifyService) {
        $shop = $request->input('shopify_shop');
        $connectionStatus = $shopifyService->checkConnection($shop);

        return response()->json([
            'success' => true,
            'data' => $connectionStatus
        ]);
    });

    // Lấy thông tin shop
    Route::get('/shop/info', function (Request $request, ShopifyService $shopifyService) {
        $shop = $request->input('shopify_shop');
        $shopInfo = $shopifyService->getShopInfo($shop);

        if (!$shopInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy thông tin shop'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $shopInfo
        ]);
    });

    // Lấy danh sách sản phẩm
    Route::get('/products', function (Request $request, ShopifyService $shopifyService) {
        $shop = $request->input('shopify_shop');
        $params = $request->only(['limit', 'page', 'status', 'vendor', 'product_type']);

        $products = $shopifyService->getProducts($shop, $params);

        if ($products === null) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách sản phẩm'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    });

    // Tạo sản phẩm mới (yêu cầu scope write_products)
    Route::post('/products', function (Request $request, ShopifyService $shopifyService) {
        $shop = $request->input('shopify_shop');
        $productData = $request->validate([
            'title' => 'required|string',
            'body_html' => 'nullable|string',
            'vendor' => 'nullable|string',
            'product_type' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        $product = $shopifyService->createProduct($shop, $productData);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo sản phẩm'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ], 201);
    })->middleware('validate.shopify.token:offline:write_products');

    // Cập nhật sản phẩm (yêu cầu scope write_products)
    Route::put('/products/{productId}', function (Request $request, ShopifyService $shopifyService, int $productId) {
        $shop = $request->input('shopify_shop');
        $productData = $request->validate([
            'title' => 'nullable|string',
            'body_html' => 'nullable|string',
            'vendor' => 'nullable|string',
            'product_type' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        $product = $shopifyService->updateProduct($shop, $productId, $productData);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật sản phẩm'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    })->middleware('validate.shopify.token:offline:write_products');

    // Xóa sản phẩm (yêu cầu scope write_products)
    Route::delete('/products/{productId}', function (Request $request, ShopifyService $shopifyService, int $productId) {
        $shop = $request->input('shopify_shop');

        $success = $shopifyService->deleteProduct($shop, $productId);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa sản phẩm'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sản phẩm đã được xóa thành công'
        ]);
    })->middleware('validate.shopify.token:offline:write_products');

    // Validate token endpoint
    Route::post('/validate-token', function (Request $request, TokenValidationService $tokenValidationService) {
        $shop = $request->input('shopify_shop');
        $session = $request->input('shopify_session');

        return response()->json([
            'success' => true,
            'message' => 'Token hợp lệ',
            'data' => [
                'shop' => $shop,
                'session_id' => $session->session_id,
                'is_online' => $session->is_online,
                'scope' => $session->scope,
                'expires_at' => $session->expires_at,
            ]
        ]);
    });

    // Shopify Import Routes
    Route::prefix('import')->group(function () {
        Route::post('/products', [ShopifyImportController::class, 'importProducts']);
        Route::post('/draft-orders', [ShopifyImportController::class, 'importDraftOrders']);
        Route::post('/all', [ShopifyImportController::class, 'importAll']);
        Route::get('/stats', [ShopifyImportController::class, 'getStats']);
        Route::delete('/clear', [ShopifyImportController::class, 'clearData']);
    });

    // Force upgrade permissions
    Route::get('/upgrade-permissions', function (Request $request, ShopifyService $shopifyService) {
        $shop = $request->input('shopify_shop');

        // Tạo auth URL với scopes mới
        $authUrl = $shopifyService->getAuthUrl($shop);

        return response()->json([
            'success' => true,
            'message' => 'Redirect to upgrade permissions',
            'auth_url' => $authUrl
        ]);
    });
});

// Routes cần xác thực token Shopify (online mode - per-user)
Route::middleware(['validate.shopify.token:online'])->group(function () {

    // Lấy thông tin user hiện tại
    Route::get('/user/info', function (Request $request) {
        $session = $request->attributes->get('shopifySession');

        return response()->json([
            'success' => true,
            'data' => [
                'shop' => $session->shop,
                'user_id' => $session->user_id,
                'user_first_name' => $session->user_first_name,
                'user_last_name' => $session->user_last_name,
                'user_email' => $session->user_email,
                'account_owner' => $session->account_owner,
                'locale' => $session->locale,
                'collaborator' => $session->collaborator,
            ]
        ]);
    });

    // Endpoint chỉ dành cho account owner
    Route::post('/admin/settings', function (Request $request) {
        $session = $request->attributes->get('shopifySession');

        if (!$session->account_owner) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'Chỉ chủ sở hữu tài khoản mới có thể thực hiện hành động này'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cài đặt đã được cập nhật'
        ]);
    });
});


Route::post('/webhook/uninstalled', [ShopifyWebhookController::class, 'handleUninstalled']);
