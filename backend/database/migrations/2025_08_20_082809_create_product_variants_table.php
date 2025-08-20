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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('shopify_id')->unique(); // Shopify variant ID
            $table->string('title');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('weight_unit')->nullable(); // kg, lb, oz, g
            $table->integer('inventory_quantity')->default(0);
            $table->string('inventory_policy')->nullable(); // deny, continue
            $table->string('fulfillment_service')->nullable();
            $table->string('inventory_management')->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('taxable')->default(true);
            $table->string('tax_code')->nullable();
            $table->integer('position')->default(1);
            $table->json('selected_options')->nullable(); // Array of {name, value}
            $table->string('image_id')->nullable(); // Reference to product_images
            $table->timestamps();

            // Indexes
            $table->index('product_id');
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
