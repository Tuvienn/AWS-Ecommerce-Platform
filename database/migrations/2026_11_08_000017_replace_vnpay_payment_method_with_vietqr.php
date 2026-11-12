<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (
            DB::table('orders')->where('payment_method', 'VNPay')->exists()
            || DB::table('payments')->where('method', 'VNPay')->exists()
        ) {
            throw new \RuntimeException(
                'Không thể tự đổi dữ liệu VNPay lịch sử sang VietQR. Hãy xử lý dữ liệu lịch sử trước khi chạy migration.'
            );
        }

        DB::statement('ALTER TABLE orders DROP CHECK chk_orders_payment_method');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_payment_method CHECK (payment_method IN ('COD','VietQR'))");
        DB::statement('ALTER TABLE payments DROP CHECK chk_payments_method');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT chk_payments_method CHECK (method IN ('COD','VietQR'))");
    }

    public function down(): void
    {
        if (
            DB::table('orders')->where('payment_method', 'VietQR')->exists()
            || DB::table('payments')->where('method', 'VietQR')->exists()
        ) {
            throw new \RuntimeException(
                'Không thể rollback khi đã có dữ liệu VietQR. Hãy xử lý dữ liệu lịch sử trước khi rollback.'
            );
        }

        DB::statement('ALTER TABLE orders DROP CHECK chk_orders_payment_method');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_payment_method CHECK (payment_method IN ('COD','VNPay'))");
        DB::statement('ALTER TABLE payments DROP CHECK chk_payments_method');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT chk_payments_method CHECK (method IN ('COD','VNPay'))");
    }
};
