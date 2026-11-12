<?php

namespace App\Modules\Voucher\Services;

use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminVoucherService
{
    public function list(array $filters = []): array
    {
        $query = DB::table('vouchers');

        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $query->where(function ($builder) use ($q) {
                $builder->where('code', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%');
            });
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('id')->get()
            ->map(fn ($voucher) => $this->format($voucher))
            ->all();
    }

    public function create(array $data): array
    {
        $code = mb_strtoupper(trim($data['code']));
        if (DB::table('vouchers')->where('code', $code)->exists()) {
            throw new BusinessException('VOUCHER_CODE_EXISTS', 'Mã giảm giá này đã tồn tại', 422);
        }

        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $id = DB::table('vouchers')->insertGetId($this->payload($data, $now, $code));

        return $this->format(DB::table('vouchers')->where('id', $id)->first());
    }

    public function update(int $id, array $data): array
    {
        $voucher = DB::table('vouchers')->where('id', $id)->first();
        if (!$voucher) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy mã giảm giá', 404);
        }

        if (array_key_exists('code', $data) && mb_strtoupper(trim($data['code'])) !== $voucher->code) {
            throw new BusinessException('VOUCHER_CODE_IMMUTABLE', 'Không thể đổi mã voucher sau khi đã tạo', 422);
        }

        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $payload = [];
        foreach (['name', 'discount_type', 'discount_value', 'max_discount_amount', 'min_order_amount', 'usage_limit', 'per_user_limit', 'status', 'starts_at', 'ends_at'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }
        $payload['updated_at'] = $now;
        DB::table('vouchers')->where('id', $id)->update($payload);

        return $this->format(DB::table('vouchers')->where('id', $id)->first());
    }

    protected function payload(array $data, string $now, string $code): array
    {
        return [
            'code' => $code,
            'name' => trim($data['name']),
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'max_discount_amount' => $data['max_discount_amount'] ?? null,
            'min_order_amount' => $data['min_order_amount'] ?? 0,
            'usage_limit' => $data['usage_limit'] ?? null,
            'used_count' => 0,
            'per_user_limit' => $data['per_user_limit'] ?? 1,
            'status' => $data['status'] ?? 'Active',
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    protected function format(object $voucher): array
    {
        return [
            'id' => (string) $voucher->id,
            'code' => $voucher->code,
            'name' => $voucher->name,
            'discount_type' => $voucher->discount_type,
            'discount_value' => (float) $voucher->discount_value,
            'max_discount_amount' => $voucher->max_discount_amount !== null ? (float) $voucher->max_discount_amount : null,
            'min_order_amount' => (float) $voucher->min_order_amount,
            'usage_limit' => $voucher->usage_limit !== null ? (int) $voucher->usage_limit : null,
            'used_count' => (int) $voucher->used_count,
            'per_user_limit' => (int) $voucher->per_user_limit,
            'status' => $voucher->status,
            'starts_at' => $voucher->starts_at,
            'ends_at' => $voucher->ends_at,
            'created_at' => $voucher->created_at,
            'updated_at' => $voucher->updated_at,
        ];
    }
}
