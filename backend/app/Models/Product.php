<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'shop',
        'shopify_id',
        'title',
        'handle',
        'description',
        'description_html',
        'vendor',
        'product_type',
        'tags',
        'status',
        'shopify_created_at',
        'shopify_updated_at',
        'published_at',
        'total_inventory',
        'online_store_url',
        'online_store_preview_url',
        'seo_title',
        'seo_description',
        'options',
    ];

    protected $casts = [
        'tags' => 'array',
        'options' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Lấy variants của product
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Lấy images của product
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Tìm product theo Shopify ID
     */
    public static function findByShopifyId(string $shopifyId): ?self
    {
        return static::where('shopify_id', $shopifyId)->first();
    }

    /**
     * Tạo hoặc cập nhật product từ Shopify data
     */
    public static function createOrUpdateFromShopify(string $shop, array $shopifyData): self
    {
        $product = static::updateOrCreate(
            ['shopify_id' => $shopifyData['id']],
            [
                'shop' => $shop,
                'title' => $shopifyData['title'],
                'handle' => $shopifyData['handle'],
                'description' => $shopifyData['description'] ?? null,
                'description_html' => $shopifyData['descriptionHtml'] ?? null,
                'vendor' => $shopifyData['vendor'] ?? null,
                'product_type' => $shopifyData['productType'] ?? null,
                'tags' => $shopifyData['tags'] ?? [],
                'status' => $shopifyData['status'] ?? 'ACTIVE',
                'shopify_created_at' => $shopifyData['createdAt'] ?? null,
                'shopify_updated_at' => $shopifyData['updatedAt'] ?? null,
                'published_at' => $shopifyData['publishedAt'] ?? null,
                'total_inventory' => $shopifyData['totalInventory'] ?? 0,
                'online_store_url' => $shopifyData['onlineStoreUrl'] ?? null,
                'online_store_preview_url' => $shopifyData['onlineStorePreviewUrl'] ?? null,
                'seo_title' => $shopifyData['seo']['title'] ?? null,
                'seo_description' => $shopifyData['seo']['description'] ?? null,
                'options' => $shopifyData['options'] ?? [],
            ]
        );

        return $product;
    }
}
