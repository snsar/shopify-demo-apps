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
        Schema::create('quote_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('shop')->index(); // Shop domain
            $table->string('display_rule')->default('all'); // 'all', 'specific'
            $table->string('position')->default('under-button'); // 'under-button', 'above-button', 'replace-button'
            $table->tinyInteger('type')->default(1); // 1 = all, 2 = specific
            $table->boolean('is_active')->default(true);
            $table->json('style_config'); // Lưu tất cả style settings: buttonLabel, alignment, fontSize, cornerRadius, textColor, buttonColor
            $table->json('additional_config')->nullable(); // Các config khác nếu cần mở rộng
            $table->timestamp('synced_at')->nullable(); // Lần cuối sync với Shopify metafield
            $table->timestamps();

            // Unique constraint cho mỗi shop chỉ có 1 config
            $table->unique('shop');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_configurations');
    }
};
