<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Shopify order information
            $table->unsignedBigInteger('shopify_order_id')->unique();
            $table->string('shop');
            $table->string('order_number')->nullable();
            $table->string('name')->nullable(); // Order name (e.g., #1001)

            // Customer information
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->json('customer_data')->nullable(); // Full customer object

            // Order details
            $table->string('financial_status')->nullable(); // pending, paid, refunded, etc.
            $table->string('fulfillment_status')->nullable(); // fulfilled, partial, etc.
            $table->decimal('total_price', 10, 2);
            $table->decimal('subtotal_price', 10, 2)->nullable();
            $table->decimal('total_tax', 10, 2)->nullable();
            $table->decimal('total_discounts', 10, 2)->nullable();
            $table->decimal('shipping_price', 10, 2)->nullable();
            $table->string('currency', 3);

            // Addresses
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();

            // Order metadata
            $table->json('line_items')->nullable(); // Order line items
            $table->json('discount_codes')->nullable();
            $table->json('shipping_lines')->nullable();
            $table->json('tax_lines')->nullable();
            $table->text('note')->nullable();
            $table->json('note_attributes')->nullable();

            // Shopify timestamps
            $table->timestamp('shopify_created_at')->nullable();
            $table->timestamp('shopify_updated_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Additional data
            $table->string('gateway')->nullable(); // Payment gateway
            $table->string('source_name')->nullable(); // web, pos, etc.
            $table->json('tags')->nullable();
            $table->json('raw_data')->nullable(); // Full webhook payload

            $table->timestamps();

            // Indexes
            $table->index(['shop', 'shopify_created_at']);
            $table->index(['financial_status']);
            $table->index(['fulfillment_status']);
            $table->index(['customer_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
