<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('image_path', 500);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->dateTime('created_at', 6);
            $table->unique(['product_id', 'image_path'], 'uq_product_images_product_path');
            $table->index(['product_id', 'sort_order'], 'idx_product_images_product_sort');
        });

        // MySQL không hỗ trợ filtered index. Service phải đảm bảo đúng một ảnh chính.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
