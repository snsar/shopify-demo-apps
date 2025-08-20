<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'shopify_order_id',
        'shop',
        'order_number',
        'name',
        'customer_id',
        'customer_email',
        'customer_phone',
        'customer_data',
        'financial_status',
        'fulfillment_status',
        'total_price',
        'subtotal_price',
        'total_tax',
        'total_discounts',
        'shipping_price',
        'currency',
        'billing_address',
        'shipping_address',
        'line_items',
        'discount_codes',
        'shipping_lines',
        'tax_lines',
        'note',
        'note_attributes',
        'shopify_created_at',
        'shopify_updated_at',
        'processed_at',
        'cancelled_at',
        'closed_at',
        'gateway',
        'source_name',
        'tags',
        'raw_data',
    ];

    protected $casts = [
        'customer_data' => 'array',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'line_items' => 'array',
        'discount_codes' => 'array',
        'shipping_lines' => 'array',
        'tax_lines' => 'array',
        'note_attributes' => 'array',
        'tags' => 'array',
        'raw_data' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
        'processed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'closed_at' => 'datetime',
        'total_price' => 'decimal:2',
        'subtotal_price' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_discounts' => 'decimal:2',
        'shipping_price' => 'decimal:2',
    ];

    /**
     * Relationship với Session (shop)
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'shop', 'shop');
    }

    /**
     * Scope để lọc orders theo shop
     */
    public function scopeForShop($query, string $shop)
    {
        return $query->where('shop', $shop);
    }

    /**
     * Scope để lọc orders theo financial status
     */
    public function scopeFinancialStatus($query, string $status)
    {
        return $query->where('financial_status', $status);
    }

    /**
     * Scope để lọc orders theo fulfillment status
     */
    public function scopeFulfillmentStatus($query, string $status)
    {
        return $query->where('fulfillment_status', $status);
    }

    /**
     * Accessor để lấy total items count
     */
    public function getTotalItemsAttribute(): int
    {
        if (!$this->line_items) {
            return 0;
        }

        return collect($this->line_items)->sum('quantity');
    }

    /**
     * Accessor để kiểm tra order có được thanh toán chưa
     */
    public function getIsPaidAttribute(): bool
    {
        return in_array($this->financial_status, ['paid', 'partially_paid']);
    }

    /**
     * Accessor để kiểm tra order có được fulfill chưa
     */
    public function getIsFulfilledAttribute(): bool
    {
        return $this->fulfillment_status === 'fulfilled';
    }

    /**
     * Tạo order từ Shopify webhook payload
     */
    public static function createFromWebhook(array $orderData, string $shop): self
    {
        return self::create([
            'shopify_order_id' => $orderData['id'],
            'shop' => $shop,
            'order_number' => $orderData['order_number'] ?? null,
            'name' => $orderData['name'] ?? null,
            'customer_id' => $orderData['customer']['id'] ?? null,
            'customer_email' => $orderData['customer']['email'] ?? null,
            'customer_phone' => $orderData['customer']['phone'] ?? null,
            'customer_data' => $orderData['customer'] ?? null,
            'financial_status' => $orderData['financial_status'] ?? null,
            'fulfillment_status' => $orderData['fulfillment_status'] ?? null,
            'total_price' => $orderData['total_price'] ?? 0,
            'subtotal_price' => $orderData['subtotal_price'] ?? null,
            'total_tax' => $orderData['total_tax'] ?? null,
            'total_discounts' => $orderData['total_discounts'] ?? null,
            'shipping_price' => collect($orderData['shipping_lines'] ?? [])->sum('price'),
            'currency' => $orderData['currency'] ?? 'USD',
            'billing_address' => $orderData['billing_address'] ?? null,
            'shipping_address' => $orderData['shipping_address'] ?? null,
            'line_items' => $orderData['line_items'] ?? null,
            'discount_codes' => $orderData['discount_codes'] ?? null,
            'shipping_lines' => $orderData['shipping_lines'] ?? null,
            'tax_lines' => $orderData['tax_lines'] ?? null,
            'note' => $orderData['note'] ?? null,
            'note_attributes' => $orderData['note_attributes'] ?? null,
            'shopify_created_at' => $orderData['created_at'] ?? null,
            'shopify_updated_at' => $orderData['updated_at'] ?? null,
            'processed_at' => $orderData['processed_at'] ?? null,
            'cancelled_at' => $orderData['cancelled_at'] ?? null,
            'closed_at' => $orderData['closed_at'] ?? null,
            'gateway' => $orderData['gateway'] ?? null,
            'source_name' => $orderData['source_name'] ?? null,
            'tags' => !empty($orderData['tags']) ? explode(', ', $orderData['tags']) : null,
            'raw_data' => $orderData,
        ]);
    }
}
