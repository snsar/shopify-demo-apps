<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ShopifyGraphQLService;
use App\Services\TokenValidationService;
use App\Models\Session;
use Illuminate\Support\Facades\Log;
use Exception;

class ShopifyMetafieldController extends Controller
{
    protected $shopifyGraphQL;
    protected $tokenValidation;

    public function __construct(
        ShopifyGraphQLService $shopifyGraphQL,
        TokenValidationService $tokenValidation
    ) {
        $this->shopifyGraphQL = $shopifyGraphQL;
        $this->tokenValidation = $tokenValidation;
    }

    /**
     * Create or update shop metafield
     */
    public function createOrUpdateShopMetafield(Request $request): JsonResponse
    {
        try {

            $validatedData = $request->validate([
                'shop' => 'required|string',
                'namespace' => 'required|string',
                'key' => 'required|string',
                'value' => 'required',
                'type' => 'string|in:boolean,color,date,date_time,dimension,json,money,multi_line_text_field,number_decimal,number_integer,rating,rich_text_field,single_line_text_field,url,weight,volume'
            ]);

            $shop = $validatedData['shop'];

            // Lấy access token từ database giống như ShopifyImportController
            $session = Session::where('shop', $shop)
                ->where('access_token', '<>', null)
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid session found for shop'
                ], 401);
            }

            $namespace = $validatedData['namespace'];
            $key = $validatedData['key'];
            $value = $validatedData['value'];
            $type = $validatedData['type'] ?? 'single_line_text_field';

            // Serialize value based on type
            $serializedValue = $this->serializeMetafieldValue($value, $type);

            // Sử dụng metafieldsSet mutation - không cần phân biệt create/update
            $result = $this->setShopMetafield($shop, $namespace, $key, $serializedValue, $type);

            // Kiểm tra xem metafield đã tồn tại trước đó chưa để xác định operation
            $existingMetafield = $this->getShopMetafield($shop, $namespace, $key);
            $operation = $existingMetafield ? 'updated' : 'created';

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Shop metafield {$operation} successfully",
                    'data' => [
                        'metafield' => $result['metafield'],
                        'operation' => $operation
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? "Failed to {$operation} shop metafield"
                ], 400);
            }
        } catch (Exception $e) {
            Log::error('Error in createOrUpdateShopMetafield: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shop metafield
     */
    public function getShopMetafield(string $shop, string $namespace, string $key): ?array
    {
        try {
            $query = '
                query getShopMetafield($namespace: String!, $key: String!) {
                    shop {
                        metafield(namespace: $namespace, key: $key) {
                            id
                            namespace
                            key
                            value
                            type
                            createdAt
                            updatedAt
                        }
                    }
                }
            ';

            $variables = [
                'namespace' => $namespace,
                'key' => $key
            ];

            $response = $this->shopifyGraphQL->query($shop, $query, $variables);

            if ($response['success'] && isset($response['data']['shop']['metafield'])) {
                return $response['data']['shop']['metafield'];
            }

            return null;
        } catch (Exception $e) {
            Log::error('Error getting shop metafield: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Set shop metafield using metafieldsSet mutation (handles both create and update)
     */
    private function setShopMetafield(string $shop, string $namespace, string $key, string $value, string $type): array
    {
        try {
            $mutation = '
                mutation metafieldsSet($metafields: [MetafieldsSetInput!]!) {
                    metafieldsSet(metafields: $metafields) {
                        metafields {
                            id
                            namespace
                            key
                            value
                            type
                            createdAt
                            updatedAt
                        }
                        userErrors {
                            field
                            message
                        }
                    }
                }
            ';

            $variables = [
                'metafields' => [
                    [
                        'ownerId' => 'gid://shopify/Shop/' . $this->getShopId($shop),
                        'namespace' => $namespace,
                        'key' => $key,
                        'value' => $value,
                        'type' => $type
                    ]
                ]
            ];

            $response = $this->shopifyGraphQL->query($shop, $mutation, $variables);

            if ($response['success']) {
                $data = $response['data']['metafieldsSet'];

                if (!empty($data['userErrors'])) {
                    return [
                        'success' => false,
                        'message' => implode(', ', array_column($data['userErrors'], 'message'))
                    ];
                }

                return [
                    'success' => true,
                    'metafield' => $data['metafields'][0] ?? null
                ];
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Failed to set metafield'
            ];
        } catch (Exception $e) {
            Log::error('Error setting shop metafield: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create shop metafield (deprecated - use setShopMetafield instead)
     */
    private function createShopMetafield(string $shop, string $namespace, string $key, string $value, string $type): array
    {
        try {
            $mutation = '
                mutation metafieldsSet($metafields: [MetafieldsSetInput!]!) {
                    metafieldsSet(metafields: $metafields) {
                        metafields {
                            id
                            namespace
                            key
                            value
                            type
                            createdAt
                            updatedAt
                        }
                        userErrors {
                            field
                            message
                        }
                    }
                }
            ';

            $variables = [
                'metafields' => [
                    [
                        'ownerId' => 'gid://shopify/Shop/' . $this->getShopId($shop),
                        'namespace' => $namespace,
                        'key' => $key,
                        'value' => $value,
                        'type' => $type
                    ]
                ]
            ];

            $response = $this->shopifyGraphQL->query($shop, $mutation, $variables);

            if ($response['success']) {
                $data = $response['data']['metafieldsSet'];

                if (!empty($data['userErrors'])) {
                    return [
                        'success' => false,
                        'message' => implode(', ', array_column($data['userErrors'], 'message'))
                    ];
                }

                return [
                    'success' => true,
                    'metafield' => $data['metafields'][0] ?? null
                ];
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Failed to create metafield'
            ];
        } catch (Exception $e) {
            Log::error('Error creating shop metafield: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update shop metafield
     */
    private function updateShopMetafield(string $shop, string $metafieldId, string $value, string $type): array
    {
        try {
            $mutation = '
                mutation metafieldsSet($metafields: [MetafieldsSetInput!]!) {
                    metafieldsSet(metafields: $metafields) {
                        metafields {
                            id
                            namespace
                            key
                            value
                            type
                            createdAt
                            updatedAt
                        }
                        userErrors {
                            field
                            message
                        }
                    }
                }
            ';

            $variables = [
                'metafields' => [
                    [
                        'id' => $metafieldId,
                        'value' => $value,
                        'type' => $type
                    ]
                ]
            ];

            $response = $this->shopifyGraphQL->query($shop, $mutation, $variables);

            if ($response['success']) {
                $data = $response['data']['metafieldsSet'];

                if (!empty($data['userErrors'])) {
                    return [
                        'success' => false,
                        'message' => implode(', ', array_column($data['userErrors'], 'message'))
                    ];
                }

                return [
                    'success' => true,
                    'metafield' => $data['metafields'][0] ?? null
                ];
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Failed to update metafield'
            ];
        } catch (Exception $e) {
            Log::error('Error updating shop metafield: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get shop ID from shop domain
     */
    private function getShopId(string $shop): string
    {
        try {
            $query = '
                query {
                    shop {
                        id
                    }
                }
            ';

            $response = $this->shopifyGraphQL->query($shop, $query);

            if ($response['success'] && isset($response['data']['shop']['id'])) {
                // Extract numeric ID from GraphQL ID
                $graphqlId = $response['data']['shop']['id'];
                return str_replace('gid://shopify/Shop/', '', $graphqlId);
            }

            throw new Exception('Could not get shop ID');
        } catch (Exception $e) {
            Log::error('Error getting shop ID: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Serialize metafield value based on type
     */
    private function serializeMetafieldValue($value, string $type): string
    {
        switch ($type) {
            case 'json':
                return is_string($value) ? $value : json_encode($value);
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'number_integer':
                return (string) intval($value);
            case 'number_decimal':
                return (string) floatval($value);
            default:
                return (string) $value;
        }
    }

    /**
     * Get all shop metafields
     */
    public function getShopMetafields(Request $request): JsonResponse
    {
        try {
            $shop = $request->get('shop');

            // Lấy access token từ database giống như ShopifyImportController
            $session = Session::where('shop', $shop)
                ->where('access_token', '<>', null)
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid session found for shop'
                ], 401);
            }

            $query = '
                query {
                    shop {
                        metafields(first: 250) {
                            edges {
                                node {
                                    id
                                    namespace
                                    key
                                    value
                                    type
                                    createdAt
                                    updatedAt
                                }
                            }
                        }
                    }
                }
            ';

            $response = $this->shopifyGraphQL->query($shop, $query);

            if ($response['success']) {
                $metafields = [];

                if (isset($response['data']['shop']['metafields']['edges'])) {
                    foreach ($response['data']['shop']['metafields']['edges'] as $edge) {
                        $metafields[] = $edge['node'];
                    }
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'metafields' => $metafields,
                        'total' => count($metafields)
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Failed to get shop metafields'
            ], 400);
        } catch (Exception $e) {
            Log::error('Error in getShopMetafields: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Development version - bypass token validation
     */
    public function createOrUpdateShopMetafieldDev(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'shop' => 'required|string',
                'namespace' => 'required|string',
                'key' => 'required|string',
                'value' => 'required',
                'type' => 'string|in:boolean,color,date,date_time,dimension,json,money,multi_line_text_field,number_decimal,number_integer,rating,rich_text_field,single_line_text_field,url,weight,volume'
            ]);

            $shop = $validatedData['shop'];
            $namespace = $validatedData['namespace'];
            $key = $validatedData['key'];
            $value = $validatedData['value'];
            $type = $validatedData['type'] ?? 'single_line_text_field';

            // For development - just return success with mock data
            return response()->json([
                'success' => true,
                'message' => "DEV: Shop metafield would be created/updated",
                'data' => [
                    'metafield' => [
                        'id' => 'gid://shopify/Metafield/dev-' . time(),
                        'namespace' => $namespace,
                        'key' => $key,
                        'value' => is_array($value) ? json_encode($value) : $value,
                        'type' => $type,
                        'createdAt' => now()->toISOString(),
                        'updatedAt' => now()->toISOString()
                    ],
                    'operation' => 'created'
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error in createOrUpdateShopMetafieldDev: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Development version - get mock metafields
     */
    public function getShopMetafieldsDev(Request $request): JsonResponse
    {
        try {
            $shop = $request->get('shop', 'dev-shop.myshopify.com');

            // Return mock data for development
            return response()->json([
                'success' => true,
                'data' => [
                    'metafields' => [
                        [
                            'id' => 'gid://shopify/Metafield/dev-1',
                            'namespace' => 'quote_snap',
                            'key' => 'configuration',
                            'value' => json_encode([
                                'displayRule' => 'all',
                                'position' => 'under-button',
                                'buttonLabel' => 'Request for quote',
                                'alignment' => 'center',
                                'fontSize' => 15,
                                'cornerRadius' => 15,
                                'textColor' => ['hue' => 0, 'brightness' => 1, 'saturation' => 0],
                                'buttonColor' => ['hue' => 39, 'brightness' => 1, 'saturation' => 1],
                                'isActive' => true
                            ]),
                            'type' => 'json',
                            'createdAt' => now()->subDays(1)->toISOString(),
                            'updatedAt' => now()->toISOString()
                        ],
                        [
                            'id' => 'gid://shopify/Metafield/dev-2',
                            'namespace' => 'quote_snap',
                            'key' => 'test_field',
                            'value' => 'Test value from UI',
                            'type' => 'single_line_text_field',
                            'createdAt' => now()->subHours(2)->toISOString(),
                            'updatedAt' => now()->subHours(1)->toISOString()
                        ]
                    ],
                    'total' => 2
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error in getShopMetafieldsDev: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
