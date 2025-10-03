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
            $table->string('title', 500)->index();
            $table->decimal('price', 10, 2)->default(0)->index();
            $table->string('price_currency', 3)->default('USD');
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->string('image_url', 500)->nullable();
            $table->string('product_url', 500)->index();
            $table->string('platform')->index();
            $table->string('platform_id', 100)->nullable()->index();
            $table->string('platform_category', 255)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('scrape_count')->default(0);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();

            $table->unique(['product_url', 'platform'], 'unique_product_per_platform');
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
