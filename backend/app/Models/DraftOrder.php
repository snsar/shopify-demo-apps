<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DraftOrder extends Model
{
    protected $fillable = [
        'shop',
        'shopify_id',
        'name',
        'email',
        'phone',
        'note',
        'tags',
        'status',
        'invoice_url',
        'invoice_sent_at',
        'shopify_created_at',
        'shopify_updated_at',
        'completed_at',
        'tax_exempt',
        'taxes_included',
        'currency_code',
        'total_price',
        'subtotal_price',
        'total_tax',
        'total_shipping_price',
        'customer_shopify_id',
        'customer_email',
        'customer_first_name',
        'customer_last_name',
        'customer_phone',
        'customer_display_name',
        'shipping_address',
        'billing_address',
        'applied_discount',
        'shipping_line',
    ];

    protected $casts = [
        'tags' => 'array',
        'tax_exempt' => 'boolean',
        'taxes_included' => 'boolean',
        'total_price' => 'decimal:2',
        'subtotal_price' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_shipping_price' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'applied_discount' => 'array',
        'shipping_line' => 'array',
        'invoice_sent_at' => 'datetime',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Lấy line items của draft order
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(DraftOrderLineItem::class);
    }

    /**
     * Tìm draft order theo Shopify ID
     */
    public static function findByShopifyId(string $shopifyId): ?self
    {
        return static::where('shopify_id', $shopifyId)->first();
    }

    /**
     * Tạo hoặc cập nhật draft order từ Shopify data
     */
    public static function createOrUpdateFromShopify(string $shop, array $shopifyData): self
    {
        $draftOrder = static::updateOrCreate(
            ['shopify_id' => $shopifyData['id']],
            [
                'shop' => $shop,
                'name' => $shopifyData['name'],
                'email' => $shopifyData['email'] ?? null,
                'phone' => $shopifyData['phone'] ?? null,
                'note' => null, // Field không có trong GraphQL response
                'tags' => $shopifyData['tags'] ?? [],
                'status' => $shopifyData['status'] ?? 'open',
                'invoice_url' => null, // Field không có trong GraphQL response
                'invoice_sent_at' => null, // Field không có trong GraphQL response
                'shopify_created_at' => $shopifyData['createdAt'] ?? null,
                'shopify_updated_at' => $shopifyData['updatedAt'] ?? null,
                'completed_at' => null, // Field không có trong GraphQL response
                'tax_exempt' => false, // Field không có trong GraphQL response
                'taxes_included' => false, // Field không có trong GraphQL response
                'currency_code' => $shopifyData['currencyCode'] ?? 'USD',
                'total_price' => $shopifyData['totalPrice'] ?? 0,
                'subtotal_price' => $shopifyData['subtotalPrice'] ?? 0,
                'total_tax' => $shopifyData['totalTax'] ?? 0,
                'total_shipping_price' => 0, // Field không có trong GraphQL response
                'customer_shopify_id' => $shopifyData['customer']['id'] ?? null,
                'customer_email' => $shopifyData['customer']['email'] ?? null,
                'customer_first_name' => $shopifyData['customer']['firstName'] ?? null,
                'customer_last_name' => $shopifyData['customer']['lastName'] ?? null,
                'customer_phone' => $shopifyData['customer']['phone'] ?? null,
                'customer_display_name' => null, // Field không có trong GraphQL response
                'shipping_address' => null, // Field không có trong GraphQL response
                'billing_address' => null, // Field không có trong GraphQL response
                'applied_discount' => null, // Field không có trong GraphQL response
                'shipping_line' => null, // Field không có trong GraphQL response
            ]
        );

        return $draftOrder;
    }
}
