<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 18, 2);
            $table->decimal('max_discount_amount', 18, 2)->nullable();
            $table->decimal('min_order_amount', 18, 2)->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('per_user_limit')->default(1);
            $table->string('status', 20)->default('Active');
            $table->dateTime('starts_at', 6)->nullable();
            $table->dateTime('ends_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
        });

        DB::statement("ALTER TABLE vouchers ADD CONSTRAINT chk_vouchers_discount_type CHECK (discount_type IN ('Percentage','FixedAmount'))");
        DB::statement("ALTER TABLE vouchers ADD CONSTRAINT chk_vouchers_discount_value CHECK (discount_value > 0)");
        DB::statement("ALTER TABLE vouchers ADD CONSTRAINT chk_vouchers_min_order_amount CHECK (min_order_amount >= 0)");
        DB::statement("ALTER TABLE vouchers ADD CONSTRAINT chk_vouchers_status CHECK (status IN ('Active','Inactive'))");

        Schema::table('orders', function (Blueprint $table) {
            $table->string('voucher_code', 50)->nullable()->after('subtotal');
            $table->decimal('discount_amount', 18, 2)->default(0)->after('voucher_code');
        });

        DB::statement('ALTER TABLE orders ADD CONSTRAINT chk_orders_discount_amount CHECK (discount_amount >= 0)');

        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('order_id')->unique()->constrained('orders');
            $table->decimal('discount_amount', 18, 2);
            $table->dateTime('created_at', 6);

            $table->index(['voucher_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_redemptions');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['voucher_code', 'discount_amount']);
        });
        Schema::dropIfExists('vouchers');
    }
};
