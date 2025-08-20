<?php

namespace App\Http\Controllers;

use App\Services\ShopifyImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ShopifyImportController extends Controller
{
    protected ShopifyImportService $importService;

    public function __construct(ShopifyImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Import products từ Shopify
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importProducts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shop' => 'required|string',
            'clear' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $shop = $request->input('shop');
        $shouldClear = $request->input('clear', false);

        try {
            // Clear existing data if requested
            if ($shouldClear) {
                Log::info("[ShopifyImportController] Clearing existing data for shop: {$shop}");
                if (!$this->importService->clearShopData($shop)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể xóa dữ liệu cũ'
                    ], 500);
                }
            }

            // Import products
            $stats = $this->importService->importProducts($shop);

            return response()->json([
                'success' => true,
                'message' => 'Import products thành công',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error("[ShopifyImportController] Error importing products", [
                'shop' => $shop,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi import products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import draft orders từ Shopify
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importDraftOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shop' => 'required|string',
            'clear' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $shop = $request->input('shop');
        $shouldClear = $request->input('clear', false);

        try {
            // Clear existing data if requested
            if ($shouldClear) {
                Log::info("[ShopifyImportController] Clearing existing data for shop: {$shop}");
                if (!$this->importService->clearShopData($shop)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể xóa dữ liệu cũ'
                    ], 500);
                }
            }

            // Import draft orders
            $stats = $this->importService->importDraftOrders($shop);

            return response()->json([
                'success' => true,
                'message' => 'Import draft orders thành công',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error("[ShopifyImportController] Error importing draft orders", [
                'shop' => $shop,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi import draft orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import tất cả dữ liệu từ Shopify
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importAll(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shop' => 'required|string',
            'clear' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $shop = $request->input('shop');
        $shouldClear = $request->input('clear', false);

        try {
            // Clear existing data if requested
            if ($shouldClear) {
                Log::info("[ShopifyImportController] Clearing existing data for shop: {$shop}");
                if (!$this->importService->clearShopData($shop)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể xóa dữ liệu cũ'
                    ], 500);
                }
            }

            // Import all data
            $stats = $this->importService->importAll($shop);

            return response()->json([
                'success' => true,
                'message' => 'Import tất cả dữ liệu thành công',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error("[ShopifyImportController] Error importing all data", [
                'shop' => $shop,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi import dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thống kê dữ liệu đã import
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shop' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $shop = $request->input('shop');

        try {
            $stats = [
                'products' => [
                    'total' => \App\Models\Product::where('shop', $shop)->count(),
                    'variants' => \App\Models\ProductVariant::whereHas('product', function ($q) use ($shop) {
                        $q->where('shop', $shop);
                    })->count(),
                    'images' => \App\Models\ProductImage::whereHas('product', function ($q) use ($shop) {
                        $q->where('shop', $shop);
                    })->count(),
                ],
                'draft_orders' => [
                    'total' => \App\Models\DraftOrder::where('shop', $shop)->count(),
                    'line_items' => \App\Models\DraftOrderLineItem::whereHas('draftOrder', function ($q) use ($shop) {
                        $q->where('shop', $shop);
                    })->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Lấy thống kê thành công',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error("[ShopifyImportController] Error getting stats", [
                'shop' => $shop,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi lấy thống kê: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa tất cả dữ liệu của shop
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shop' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $shop = $request->input('shop');

        try {
            if ($this->importService->clearShopData($shop)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Xóa dữ liệu thành công'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa dữ liệu'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error("[ShopifyImportController] Error clearing data", [
                'shop' => $shop,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi xóa dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }
}
