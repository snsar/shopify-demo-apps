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
        Schema::create('draft_order_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_order_id')->constrained()->onDelete('cascade');
            $table->string('shopify_id')->unique(); // Shopify line item ID
            $table->string('title');
            $table->integer('quantity');
            $table->decimal('original_unit_price', 10, 2);
            $table->decimal('discounted_unit_price', 10, 2);
            $table->decimal('total_discount', 10, 2)->default(0);
            $table->json('weight')->nullable(); // {unit, value}
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('taxable')->default(true);
            $table->string('sku')->nullable();
            $table->string('vendor')->nullable();
            $table->string('product_shopify_id')->nullable();
            $table->string('product_title')->nullable();
            $table->string('product_handle')->nullable();
            $table->string('variant_shopify_id')->nullable();
            $table->string('variant_title')->nullable();
            $table->string('variant_sku')->nullable();
            $table->string('image_shopify_id')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_alt_text')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('draft_order_id');
            $table->index('product_shopify_id');
            $table->index('variant_shopify_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_order_line_items');
    }
};
