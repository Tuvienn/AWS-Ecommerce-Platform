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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('category_id');
            $table->string('name', 255);
            $table->string('slug', 300)->unique();
            $table->string('brand', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('specifications')->nullable();
            $table->string('status', 20)->default('Active');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);

            $table->foreign('category_id')->references('id')->on('categories');
        });

        DB::statement("ALTER TABLE products ADD CONSTRAINT chk_products_status CHECK (status IN ('Active', 'Inactive'))");
        DB::statement("ALTER TABLE products ADD CONSTRAINT chk_products_specifications_json CHECK (specifications IS NULL OR JSON_VALID(specifications))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
