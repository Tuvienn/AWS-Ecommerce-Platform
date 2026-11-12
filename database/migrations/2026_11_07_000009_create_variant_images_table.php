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
        Schema::create('variant_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('image_path', 500);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->dateTime('created_at', 6);
            $table->unique(['variant_id', 'image_path'], 'uq_variant_images_variant_path');
            $table->index(['variant_id', 'sort_order'], 'idx_variant_images_variant_sort');
        });

        // MySQL không hỗ trợ filtered index. Service phải đảm bảo đúng một ảnh chính.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_images');
    }
};
