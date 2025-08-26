<?php

namespace App\Http\Controllers;

use App\Models\QuoteConfiguration;
use App\Models\Session;
// Remove this line - ShopifyMetafieldController is not a service
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class QuoteConfigurationController extends Controller
{

    /**
     * Get quote configuration for a shop
     */
    public function getConfiguration(Request $request): JsonResponse
    {
        try {
            $shop = $request->get('shop');

            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shop parameter is required'
                ], 400);
            }

            $config = QuoteConfiguration::getForShop($shop);

            if (!$config) {
                // Return default configuration if none exists
                return response()->json([
                    'success' => true,
                    'data' => [
                        'displayRule' => 'all',
                        'position' => 'under-button',
                        'isActive' => true,
                        'buttonLabel' => 'Request for quote',
                        'alignment' => 'center',
                        'fontSize' => 15,
                        'cornerRadius' => 15,
                        'textColor' => ['hue' => 0, 'brightness' => 1, 'saturation' => 0],
                        'buttonColor' => ['hue' => 39, 'brightness' => 1, 'saturation' => 1],
                    ],
                    'from_database' => false
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $config->toFrontendFormat(),
                'from_database' => true,
                'last_synced' => $config->synced_at?->toISOString()
            ]);
        } catch (Exception $e) {
            Log::error('Error getting quote configuration: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save quote configuration
     */
    public function saveConfiguration(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'shop' => 'required|string',
                'displayRule' => 'string|in:all,specific',
                'position' => 'string|in:under-button,above-button,replace-button',
                'isActive' => 'boolean',
                'buttonLabel' => 'string|max:255',
                'alignment' => 'string|in:flex-start,center,flex-end',
                'fontSize' => 'integer|min:10|max:30',
                'cornerRadius' => 'integer|min:0|max:50',
                'textColor' => 'array',
                'buttonColor' => 'array',
            ]);

            $shop = $validatedData['shop'];

            // Verify shop has valid session
            $session = Session::where('shop', $shop)
                ->where('access_token', '<>', null)
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid session found for shop'
                ], 401);
            }

            // Save to database
            $config = QuoteConfiguration::updateOrCreateForShop($shop, $validatedData);

            // Sync to Shopify metafield in background (async)
            $this->syncToShopifyMetafield($shop, $config);

            return response()->json([
                'success' => true,
                'message' => 'Configuration saved successfully',
                'data' => $config->toFrontendFormat()
            ]);
        } catch (Exception $e) {
            Log::error('Error saving quote configuration: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync configuration to Shopify metafield
     */
    private function syncToShopifyMetafield(string $shop, QuoteConfiguration $config): void
    {
        try {
            // Call metafield API directly
            $metafieldController = app(ShopifyMetafieldController::class);

            $metafieldRequest = new Request([
                'shop' => $shop,
                'namespace' => 'quote_snap',
                'key' => 'configuration',
                'value' => json_encode($config->toMetafieldFormat()),
                'type' => 'json'
            ]);

            $result = $metafieldController->createOrUpdateShopMetafield($metafieldRequest);

            if ($result->getStatusCode() === 200) {
                $config->update(['synced_at' => now()]);
                Log::info("[QuoteConfig] Synced to Shopify metafield", [
                    'shop' => $shop,
                    'config_id' => $config->id
                ]);
            } else {
                Log::warning("[QuoteConfig] Failed to sync to Shopify metafield", [
                    'shop' => $shop,
                    'response' => $result->getContent()
                ]);
            }
        } catch (Exception $e) {
            Log::error("[QuoteConfig] Exception syncing to metafield: " . $e->getMessage(), [
                'shop' => $shop,
                'config_id' => $config->id
            ]);
        }
    }

    /**
     * Force sync configuration to Shopify
     */
    public function syncToShopify(Request $request): JsonResponse
    {
        try {
            $shop = $request->get('shop');

            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shop parameter is required'
                ], 400);
            }

            $config = QuoteConfiguration::getForShop($shop);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'No configuration found for shop'
                ], 404);
            }

            $this->syncToShopifyMetafield($shop, $config);

            return response()->json([
                'success' => true,
                'message' => 'Configuration synced to Shopify successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Error syncing to Shopify: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import configuration from Shopify metafield
     */
    public function importFromShopify(Request $request): JsonResponse
    {
        try {
            $shop = $request->get('shop');

            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shop parameter is required'
                ], 400);
            }

            // Get metafield from Shopify
            $metafieldController = app(ShopifyMetafieldController::class);
            $metafieldRequest = new Request(['shop' => $shop]);
            $metafieldsResponse = $metafieldController->getShopMetafields($metafieldRequest);

            if ($metafieldsResponse->getStatusCode() !== 200) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get metafields from Shopify'
                ], 400);
            }

            $metafieldsData = json_decode($metafieldsResponse->getContent(), true);

            if (!$metafieldsData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get metafields: ' . $metafieldsData['message']
                ], 400);
            }

            // Find quote_snap configuration metafield
            $configMetafield = null;
            foreach ($metafieldsData['data']['metafields'] as $metafield) {
                if ($metafield['namespace'] === 'quote_snap' && $metafield['key'] === 'configuration') {
                    $configMetafield = $metafield;
                    break;
                }
            }

            if (!$configMetafield) {
                return response()->json([
                    'success' => false,
                    'message' => 'No quote configuration found in Shopify metafields'
                ], 404);
            }

            // Parse and save configuration
            $shopifyConfig = json_decode($configMetafield['value'], true);
            $config = QuoteConfiguration::updateOrCreateForShop($shop, $shopifyConfig);

            return response()->json([
                'success' => true,
                'message' => 'Configuration imported from Shopify successfully',
                'data' => $config->toFrontendFormat()
            ]);
        } catch (Exception $e) {
            Log::error('Error importing from Shopify: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
