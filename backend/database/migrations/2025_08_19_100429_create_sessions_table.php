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
        Schema::create('sessions', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('session_id')->unique(); // VARCHAR(255) NOT NULL với UNIQUE INDEX
            $table->string('shop'); // VARCHAR(255) NOT NULL
            $table->boolean('is_online'); // TINYINT(1) NOT NULL
            $table->string('state'); // VARCHAR(255) NOT NULL
            $table->timestamps(); // created_at và updated_at TIMESTAMP NULL DEFAULT NULL
            $table->string('scope')->nullable(); // VARCHAR(255) NULL DEFAULT NULL
            $table->string('access_token')->nullable(); // VARCHAR(255) NULL DEFAULT NULL
            $table->dateTime('expires_at')->nullable(); // DATETIME NULL DEFAULT NULL
            $table->bigInteger('user_id')->nullable(); // BIGINT NULL DEFAULT NULL
            $table->string('user_first_name')->nullable(); // VARCHAR(255) NULL DEFAULT NULL
            $table->string('user_last_name')->nullable(); // VARCHAR(255) NULL DEFAULT NULL
            $table->string('user_email')->nullable(); // VARCHAR(255) NULL DEFAULT NULL
            $table->boolean('user_email_verified')->nullable(); // TINYINT(1) NULL DEFAULT NULL
            $table->boolean('account_owner')->nullable(); // TINYINT(1) NULL DEFAULT NULL
            $table->string('locale')->nullable(); // VARCHAR(255) NULL DEFAULT NULL
            $table->boolean('collaborator')->nullable(); // TINYINT(1) NULL DEFAULT NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
