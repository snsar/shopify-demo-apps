<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'shopify_id',
        'title',
        'sku',
        'barcode',
        'price',
        'compare_at_price',
        'weight',
        'weight_unit',
        'inventory_quantity',
        'inventory_policy',
        'fulfillment_service',
        'inventory_management',
        'requires_shipping',
        'taxable',
        'tax_code',
        'position',
        'selected_options',
        'image_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'requires_shipping' => 'boolean',
        'taxable' => 'boolean',
        'selected_options' => 'array',
    ];

    /**
     * Lấy product của variant này
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Tìm variant theo Shopify ID
     */
    public static function findByShopifyId(string $shopifyId): ?self
    {
        return static::where('shopify_id', $shopifyId)->first();
    }

    /**
     * Tạo hoặc cập nhật variant từ Shopify data
     */
    public static function createOrUpdateFromShopify(int $productId, array $shopifyData): self
    {
        $variant = static::updateOrCreate(
            ['shopify_id' => $shopifyData['id']],
            [
                'product_id' => $productId,
                'title' => $shopifyData['title'],
                'sku' => $shopifyData['sku'] ?? null,
                'barcode' => $shopifyData['barcode'] ?? null,
                'price' => $shopifyData['price'],
                'compare_at_price' => $shopifyData['compareAtPrice'] ?? null,
                'weight' => null, // Không có trong GraphQL response
                'weight_unit' => null, // Không có trong GraphQL response
                'inventory_quantity' => $shopifyData['inventoryQuantity'] ?? 0,
                'inventory_policy' => $shopifyData['inventoryPolicy'] ?? null,
                'fulfillment_service' => null, // Không có trong GraphQL response
                'inventory_management' => null, // Không có trong GraphQL response
                'requires_shipping' => true, // Default value
                'taxable' => $shopifyData['taxable'] ?? true,
                'tax_code' => null, // Không có trong GraphQL response
                'position' => $shopifyData['position'] ?? 1,
                'selected_options' => $shopifyData['selectedOptions'] ?? [],
                'image_id' => $shopifyData['image']['id'] ?? null,
            ]
        );

        return $variant;
    }
}
