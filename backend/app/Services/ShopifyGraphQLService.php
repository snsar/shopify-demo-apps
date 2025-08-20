<?php

namespace App\Services;

use App\Services\TokenValidationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyGraphQLService
{
    protected TokenValidationService $tokenValidationService;

    public function __construct()
    {
        $this->tokenValidationService = app(TokenValidationService::class);
    }

    /**
     * Thực hiện GraphQL query đến Shopify
     *
     * @param string $shop
     * @param string $query
     * @param array $variables
     * @return array|null
     */
    public function query(string $shop, string $query, array $variables = []): ?array
    {
        // Validate session trước khi thực hiện request
        $validationResult = $this->tokenValidationService->validateSession($shop);

        if (!$validationResult['valid']) {
            Log::error("[ShopifyGraphQLService] Token không hợp lệ", [
                'shop' => $shop,
                'reason' => $validationResult['reason']
            ]);
            return null;
        }

        $session = $validationResult['session'];
        $accessToken = $session->access_token;

        try {
            $url = "https://{$shop}/admin/api/2024-01/graphql.json";

            $payload = [
                'query' => $query
            ];

            if (!empty($variables)) {
                $payload['variables'] = $variables;
            }

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Kiểm tra GraphQL errors
                if (isset($data['errors']) && !empty($data['errors'])) {
                    Log::error("[ShopifyGraphQLService] GraphQL errors", [
                        'shop' => $shop,
                        'errors' => $data['errors']
                    ]);
                    return null;
                }

                Log::info("[ShopifyGraphQLService] GraphQL query thành công", [
                    'shop' => $shop,
                    'query_type' => $this->extractQueryType($query)
                ]);

                return $data;
            }

            // Log lỗi API
            Log::error("[ShopifyGraphQLService] GraphQL query thất bại", [
                'shop' => $shop,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("[ShopifyGraphQLService] Exception trong GraphQL query", [
                'shop' => $shop,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Lấy tất cả products với pagination
     *
     * @param string $shop
     * @param int $first
     * @param string|null $after
     * @return array|null
     */
    public function getProducts(string $shop, int $first = 50, ?string $after = null): ?array
    {
        $query = '
            query getProducts($first: Int!, $after: String) {
                products(first: $first, after: $after) {
                    edges {
                        node {
                            id
                            title
                            handle
                            description
                            descriptionHtml
                            vendor
                            productType
                            tags
                            status
                            createdAt
                            updatedAt
                            publishedAt
                            totalInventory
                            onlineStoreUrl
                            onlineStorePreviewUrl
                            seo {
                                title
                                description
                            }
                            options {
                                id
                                name
                                values
                                position
                            }
                            variants(first: 250) {
                                edges {
                                    node {
                                        id
                                        title
                                        sku
                                        barcode
                                        price
                                        compareAtPrice
                                        inventoryQuantity
                                        inventoryPolicy
                                        taxable
                                        position
                                        selectedOptions {
                                            name
                                            value
                                        }
                                        image {
                                            id
                                            url
                                            altText
                                        }
                                    }
                                }
                            }
                            images(first: 250) {
                                edges {
                                    node {
                                        id
                                        url
                                        altText
                                        width
                                        height
                                    }
                                }
                            }
                        }
                        cursor
                    }
                    pageInfo {
                        hasNextPage
                        hasPreviousPage
                        startCursor
                        endCursor
                    }
                }
            }
        ';

        $variables = [
            'first' => $first
        ];

        if ($after) {
            $variables['after'] = $after;
        }

        return $this->query($shop, $query, $variables);
    }

    /**
     * Lấy tất cả draft orders với pagination
     *
     * @param string $shop
     * @param int $first
     * @param string|null $after
     * @return array|null
     */
    public function getDraftOrders(string $shop, int $first = 50, ?string $after = null): ?array
    {
        $query = '
            query getDraftOrders($first: Int!, $after: String) {
                draftOrders(first: $first, after: $after) {
                    edges {
                        node {
                            id
                            name
                            email
                            phone
                            note
                            tags
                            status
                            invoiceUrl
                            invoiceSentAt
                            createdAt
                            updatedAt
                            completedAt
                            taxExempt
                            taxesIncluded
                            currencyCode
                            totalPrice
                            subtotalPrice
                            totalTax
                            totalShippingPrice
                            customer {
                                id
                                email
                                firstName
                                lastName
                                phone
                                displayName
                            }
                            shippingAddress {
                                firstName
                                lastName
                                company
                                address1
                                address2
                                city
                                province
                                country
                                zip
                                phone
                            }
                            billingAddress {
                                firstName
                                lastName
                                company
                                address1
                                address2
                                city
                                province
                                country
                                zip
                                phone
                            }
                            lineItems(first: 250) {
                                edges {
                                    node {
                                        id
                                        title
                                        quantity
                                        originalUnitPrice
                                        discountedUnitPrice
                                        totalDiscount
                                        weight {
                                            unit
                                            value
                                        }
                                        requiresShipping
                                        taxable
                                        sku
                                        vendor
                                        product {
                                            id
                                            title
                                            handle
                                        }
                                        variant {
                                            id
                                            title
                                            sku
                                        }
                                        image {
                                            id
                                            url
                                            altText
                                        }
                                    }
                                }
                            }
                            appliedDiscount {
                                title
                                description
                                value
                                valueType
                                amount
                            }
                            shippingLine {
                                title
                                price
                                code
                                source
                            }
                        }
                        cursor
                    }
                    pageInfo {
                        hasNextPage
                        hasPreviousPage
                        startCursor
                        endCursor
                    }
                }
            }
        ';

        $variables = [
            'first' => $first
        ];

        if ($after) {
            $variables['after'] = $after;
        }

        return $this->query($shop, $query, $variables);
    }

    /**
     * Trích xuất loại query từ GraphQL query string
     *
     * @param string $query
     * @return string
     */
    private function extractQueryType(string $query): string
    {
        if (preg_match('/query\s+(\w+)/', $query, $matches)) {
            return $matches[1];
        }

        if (strpos($query, 'products') !== false) {
            return 'products';
        }

        if (strpos($query, 'draftOrders') !== false) {
            return 'draftOrders';
        }

        return 'unknown';
    }
}
