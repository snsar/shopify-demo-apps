<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'shopify_id',
        'url',
        'alt_text',
        'width',
        'height',
        'position',
    ];

    /**
     * Lấy product của image này
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Tìm image theo Shopify ID
     */
    public static function findByShopifyId(string $shopifyId): ?self
    {
        return static::where('shopify_id', $shopifyId)->first();
    }

    /**
     * Tạo hoặc cập nhật image từ Shopify data
     */
    public static function createOrUpdateFromShopify(int $productId, array $shopifyData): self
    {
        $image = static::updateOrCreate(
            ['shopify_id' => $shopifyData['id']],
            [
                'product_id' => $productId,
                'url' => $shopifyData['url'],
                'alt_text' => $shopifyData['altText'] ?? null,
                'width' => $shopifyData['width'] ?? null,
                'height' => $shopifyData['height'] ?? null,
                'position' => 1, // Sẽ được cập nhật dựa trên thứ tự trong array
            ]
        );

        return $image;
    }
}
