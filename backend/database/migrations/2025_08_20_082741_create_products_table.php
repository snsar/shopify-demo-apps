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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('shop'); // Shop domain
            $table->string('shopify_id')->unique(); // Shopify product ID (gid://shopify/Product/123)
            $table->string('title');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->longText('description_html')->nullable();
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->json('tags')->nullable(); // Array of tags
            $table->string('status'); // ACTIVE, ARCHIVED, DRAFT
            $table->timestamp('shopify_created_at')->nullable();
            $table->timestamp('shopify_updated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->integer('total_inventory')->default(0);
            $table->string('online_store_url')->nullable();
            $table->string('online_store_preview_url')->nullable();

            // SEO fields
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            // Options (stored as JSON)
            $table->json('options')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['shop', 'shopify_id']);
            $table->index('shop');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
