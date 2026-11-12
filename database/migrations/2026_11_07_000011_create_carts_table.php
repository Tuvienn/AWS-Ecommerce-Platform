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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('guest_token_hash', 64)->nullable();
            $table->string('status', 20)->default('Active');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['user_id', 'status'], 'idx_carts_user_status');
            $table->index(['guest_token_hash', 'status'], 'idx_carts_guest_status');
        });

        DB::statement("ALTER TABLE carts ADD CONSTRAINT chk_carts_status CHECK (status IN ('Active', 'Merged', 'Abandoned'))");
        // MySQL không cho CHECK này dùng cột có foreign key nullOnDelete.
        // CartService kiểm tra mỗi cart phải có user hoặc guest token trước khi ghi.
        // Active cart uniqueness được đảm bảo trong CartService transaction.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
