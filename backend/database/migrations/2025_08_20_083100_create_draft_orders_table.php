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
        Schema::create('draft_orders', function (Blueprint $table) {
            $table->id();
            $table->string('shop'); // Shop domain
            $table->string('shopify_id')->unique(); // Shopify draft order ID
            $table->string('name'); // Draft order name/number
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('note')->nullable();
            $table->json('tags')->nullable(); // Array of tags
            $table->string('status'); // open, invoice_sent, completed
            $table->string('invoice_url')->nullable();
            $table->timestamp('invoice_sent_at')->nullable();
            $table->timestamp('shopify_created_at')->nullable();
            $table->timestamp('shopify_updated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('tax_exempt')->default(false);
            $table->boolean('taxes_included')->default(false);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('subtotal_price', 10, 2)->default(0);
            $table->decimal('total_tax', 10, 2)->default(0);
            $table->decimal('total_shipping_price', 10, 2)->default(0);

            // Customer information
            $table->string('customer_shopify_id')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_first_name')->nullable();
            $table->string('customer_last_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_display_name')->nullable();

            // Shipping address
            $table->json('shipping_address')->nullable();

            // Billing address
            $table->json('billing_address')->nullable();

            // Applied discount
            $table->json('applied_discount')->nullable();

            // Shipping line
            $table->json('shipping_line')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['shop', 'shopify_id']);
            $table->index('shop');
            $table->index('status');
            $table->index('customer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_orders');
    }
};
