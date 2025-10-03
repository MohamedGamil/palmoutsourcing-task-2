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
            $table->string('title')->index();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0)->index();
            $table->decimal('list_price', 10, 2)->default(0)->index();
            $table->decimal('rating', 3, 2)->default(0)->index();
            $table->unsignedInteger('rating_count')->default(0)->index();
            $table->string('vendor_name')->nullable()->index();
            $table->text('image_url')->nullable();
            $table->string('source_store')->nullable()->index();
            $table->string('store_category')->nullable()->index();
            $table->text('store_url')->nullable();
            $table->timestamps();
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
