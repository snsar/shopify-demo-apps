<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftOrderLineItem extends Model
{
    protected $fillable = [
        'draft_order_id',
        'shopify_id',
        'title',
        'quantity',
        'original_unit_price',
        'discounted_unit_price',
        'total_discount',
        'weight',
        'requires_shipping',
        'taxable',
        'sku',
        'vendor',
        'product_shopify_id',
        'product_title',
        'product_handle',
        'variant_shopify_id',
        'variant_title',
        'variant_sku',
        'image_shopify_id',
        'image_url',
        'image_alt_text',
    ];

    protected $casts = [
        'original_unit_price' => 'decimal:2',
        'discounted_unit_price' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'weight' => 'array',
        'requires_shipping' => 'boolean',
        'taxable' => 'boolean',
    ];

    /**
     * Lấy draft order của line item này
     */
    public function draftOrder(): BelongsTo
    {
        return $this->belongsTo(DraftOrder::class);
    }

    /**
     * Tìm line item theo Shopify ID
     */
    public static function findByShopifyId(string $shopifyId): ?self
    {
        return static::where('shopify_id', $shopifyId)->first();
    }

    /**
     * Tạo hoặc cập nhật line item từ Shopify data
     */
    public static function createOrUpdateFromShopify(int $draftOrderId, array $shopifyData): self
    {
        $lineItem = static::updateOrCreate(
            ['shopify_id' => $shopifyData['id']],
            [
                'draft_order_id' => $draftOrderId,
                'title' => $shopifyData['title'],
                'quantity' => $shopifyData['quantity'],
                'original_unit_price' => 0, // Field không có trong GraphQL response
                'discounted_unit_price' => 0, // Field không có trong GraphQL response
                'total_discount' => 0, // Field không có trong GraphQL response
                'weight' => null, // Field không có trong GraphQL response
                'requires_shipping' => true, // Field không có trong GraphQL response
                'taxable' => true, // Field không có trong GraphQL response
                'sku' => $shopifyData['sku'] ?? null,
                'vendor' => $shopifyData['vendor'] ?? null,
                'product_shopify_id' => $shopifyData['product']['id'] ?? null,
                'product_title' => $shopifyData['product']['title'] ?? null,
                'product_handle' => $shopifyData['product']['handle'] ?? null,
                'variant_shopify_id' => $shopifyData['variant']['id'] ?? null,
                'variant_title' => $shopifyData['variant']['title'] ?? null,
                'variant_sku' => $shopifyData['variant']['sku'] ?? null,
                'image_shopify_id' => null, // Field không có trong GraphQL response
                'image_url' => null, // Field không có trong GraphQL response
                'image_alt_text' => null, // Field không có trong GraphQL response
            ]
        );

        return $lineItem;
    }
}
