<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        $vouchers = [
            [
                'code' => 'TECH10',
                'name' => 'Giảm 10% cho đơn TechShop',
                'discount_type' => 'Percentage',
                'discount_value' => 10,
                'max_discount_amount' => 500000,
                'min_order_amount' => 0,
                'usage_limit' => null,
                'per_user_limit' => 1,
            ],
            [
                'code' => 'GIAM500K',
                'name' => 'Giảm 500.000 đ cho đơn từ 5.000.000 đ',
                'discount_type' => 'FixedAmount',
                'discount_value' => 500000,
                'max_discount_amount' => null,
                'min_order_amount' => 5000000,
                'usage_limit' => null,
                'per_user_limit' => 1,
            ],
        ];

        foreach ($vouchers as $voucher) {
            DB::table('vouchers')->updateOrInsert(
                ['code' => $voucher['code']],
                array_merge($voucher, [
                    'status' => 'Active',
                    'used_count' => 0,
                    'starts_at' => null,
                    'ends_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
