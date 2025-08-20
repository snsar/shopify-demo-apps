<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Models\DraftOrder;
use App\Models\DraftOrderLineItem;
use App\Services\ShopifyGraphQLService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ShopifyImportService
{
    protected ShopifyGraphQLService $graphQLService;

    public function __construct(ShopifyGraphQLService $graphQLService)
    {
        $this->graphQLService = $graphQLService;
    }

    /**
     * Import tất cả products từ Shopify
     *
     * @param string $shop
     * @return array
     */
    public function importProducts(string $shop): array
    {
        $stats = [
            'total_products' => 0,
            'total_variants' => 0,
            'total_images' => 0,
            'errors' => []
        ];

        try {
            $hasNextPage = true;
            $cursor = null;

            while ($hasNextPage) {
                Log::info("[ShopifyImportService] Fetching products", [
                    'shop' => $shop,
                    'cursor' => $cursor
                ]);

                $response = $this->graphQLService->getProducts($shop, 50, $cursor);

                if (!$response || !isset($response['data']['products'])) {
                    Log::error("[ShopifyImportService] Không thể lấy products từ Shopify", [
                        'shop' => $shop,
                        'response' => $response
                    ]);
                    $stats['errors'][] = 'Không thể lấy products từ Shopify';
                    break;
                }

                $products = $response['data']['products'];
                $pageInfo = $products['pageInfo'];

                foreach ($products['edges'] as $edge) {
                    $productData = $edge['node'];

                    try {
                        DB::transaction(function () use ($shop, $productData, &$stats) {
                            // Import product
                            $product = Product::createOrUpdateFromShopify($shop, $productData);
                            $stats['total_products']++;

                            // Import variants
                            if (isset($productData['variants']['edges'])) {
                                foreach ($productData['variants']['edges'] as $variantEdge) {
                                    $variantData = $variantEdge['node'];
                                    ProductVariant::createOrUpdateFromShopify($product->id, $variantData);
                                    $stats['total_variants']++;
                                }
                            }

                            // Import images
                            if (isset($productData['images']['edges'])) {
                                foreach ($productData['images']['edges'] as $imageEdge) {
                                    $imageData = $imageEdge['node'];
                                    ProductImage::createOrUpdateFromShopify($product->id, $imageData);
                                    $stats['total_images']++;
                                }
                            }

                            Log::info("[ShopifyImportService] Imported product", [
                                'shop' => $shop,
                                'product_id' => $productData['id'],
                                'product_title' => $productData['title']
                            ]);
                        });
                    } catch (\Exception $e) {
                        $error = "Lỗi import product {$productData['id']}: " . $e->getMessage();
                        Log::error("[ShopifyImportService] " . $error, [
                            'shop' => $shop,
                            'product_data' => $productData
                        ]);
                        $stats['errors'][] = $error;
                    }
                }

                $hasNextPage = $pageInfo['hasNextPage'];
                $cursor = $pageInfo['endCursor'];

                // Tránh rate limiting
                if ($hasNextPage) {
                    sleep(1);
                }
            }

            Log::info("[ShopifyImportService] Products import completed", [
                'shop' => $shop,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            $error = "Lỗi import products: " . $e->getMessage();
            Log::error("[ShopifyImportService] " . $error, [
                'shop' => $shop,
                'exception' => $e
            ]);
            $stats['errors'][] = $error;
        }

        return $stats;
    }

    /**
     * Import tất cả draft orders từ Shopify
     *
     * @param string $shop
     * @return array
     */
    public function importDraftOrders(string $shop): array
    {
        $stats = [
            'total_draft_orders' => 0,
            'total_line_items' => 0,
            'errors' => []
        ];

        try {
            $hasNextPage = true;
            $cursor = null;

            while ($hasNextPage) {
                Log::info("[ShopifyImportService] Fetching draft orders", [
                    'shop' => $shop,
                    'cursor' => $cursor
                ]);

                $response = $this->graphQLService->getDraftOrders($shop, 50, $cursor);

                if (!$response || !isset($response['data']['draftOrders'])) {
                    Log::error("[ShopifyImportService] Không thể lấy draft orders từ Shopify", [
                        'shop' => $shop,
                        'response' => $response
                    ]);
                    $stats['errors'][] = 'Không thể lấy draft orders từ Shopify';
                    break;
                }

                $draftOrders = $response['data']['draftOrders'];
                $pageInfo = $draftOrders['pageInfo'];

                foreach ($draftOrders['edges'] as $edge) {
                    $draftOrderData = $edge['node'];

                    try {
                        DB::transaction(function () use ($shop, $draftOrderData, &$stats) {
                            // Import draft order
                            $draftOrder = DraftOrder::createOrUpdateFromShopify($shop, $draftOrderData);
                            $stats['total_draft_orders']++;

                            // Import line items
                            if (isset($draftOrderData['lineItems']['edges'])) {
                                foreach ($draftOrderData['lineItems']['edges'] as $lineItemEdge) {
                                    $lineItemData = $lineItemEdge['node'];
                                    DraftOrderLineItem::createOrUpdateFromShopify($draftOrder->id, $lineItemData);
                                    $stats['total_line_items']++;
                                }
                            }

                            Log::info("[ShopifyImportService] Imported draft order", [
                                'shop' => $shop,
                                'draft_order_id' => $draftOrderData['id'],
                                'draft_order_name' => $draftOrderData['name']
                            ]);
                        });
                    } catch (\Exception $e) {
                        $error = "Lỗi import draft order {$draftOrderData['id']}: " . $e->getMessage();
                        Log::error("[ShopifyImportService] " . $error, [
                            'shop' => $shop,
                            'draft_order_data' => $draftOrderData
                        ]);
                        $stats['errors'][] = $error;
                    }
                }

                $hasNextPage = $pageInfo['hasNextPage'];
                $cursor = $pageInfo['endCursor'];

                // Tránh rate limiting
                if ($hasNextPage) {
                    sleep(1);
                }
            }

            Log::info("[ShopifyImportService] Draft orders import completed", [
                'shop' => $shop,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            $error = "Lỗi import draft orders: " . $e->getMessage();
            Log::error("[ShopifyImportService] " . $error, [
                'shop' => $shop,
                'exception' => $e
            ]);
            $stats['errors'][] = $error;
        }

        return $stats;
    }

    /**
     * Import tất cả dữ liệu (products và draft orders)
     *
     * @param string $shop
     * @return array
     */
    public function importAll(string $shop): array
    {
        Log::info("[ShopifyImportService] Starting full import", ['shop' => $shop]);

        $productStats = $this->importProducts($shop);
        $draftOrderStats = $this->importDraftOrders($shop);

        $combinedStats = [
            'products' => $productStats,
            'draft_orders' => $draftOrderStats,
            'total_errors' => count($productStats['errors']) + count($draftOrderStats['errors'])
        ];

        Log::info("[ShopifyImportService] Full import completed", [
            'shop' => $shop,
            'stats' => $combinedStats
        ]);

        return $combinedStats;
    }

    /**
     * Xóa tất cả dữ liệu của shop
     *
     * @param string $shop
     * @return bool
     */
    public function clearShopData(string $shop): bool
    {
        try {
            DB::transaction(function () use ($shop) {
                // Xóa products và related data
                $products = Product::where('shop', $shop)->get();
                foreach ($products as $product) {
                    $product->variants()->delete();
                    $product->images()->delete();
                    $product->delete();
                }

                // Xóa draft orders và related data
                $draftOrders = DraftOrder::where('shop', $shop)->get();
                foreach ($draftOrders as $draftOrder) {
                    $draftOrder->lineItems()->delete();
                    $draftOrder->delete();
                }
            });

            Log::info("[ShopifyImportService] Cleared all data for shop", ['shop' => $shop]);
            return true;
        } catch (\Exception $e) {
            Log::error("[ShopifyImportService] Failed to clear shop data", [
                'shop' => $shop,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
