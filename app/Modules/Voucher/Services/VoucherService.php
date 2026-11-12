<?php

namespace App\Modules\Voucher\Services;

use App\Modules\Cart\Repositories\CartRepository;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VoucherService
{
    public function __construct(
        protected CartRepository $cartRepository
    ) {}

    /**
     * Kiểm tra mã theo giỏ hàng hiện tại. Đây chỉ là báo giá;
     * checkout luôn gọi lại cùng quy tắc trong transaction.
     */
    public function previewForCart(int $userId, string $code): array
    {
        $cart = $this->cartRepository->findActiveByUserId($userId);
        if (!$cart) {
            throw new BusinessException('EMPTY_CART', 'Giỏ hàng của bạn đang trống', 422);
        }

        $items = $this->cartRepository->getItemsWithDetails((int) $cart->id);
        if ($items->isEmpty()) {
            throw new BusinessException('EMPTY_CART', 'Giỏ hàng của bạn đang trống', 422);
        }

        $subtotal = 0.0;
        foreach ($items as $item) {
            if (
                $item->product_status !== 'Active'
                || $item->category_status !== 'Active'
                || $item->variant_status !== 'Active'
            ) {
                throw new BusinessException('ITEM_UNAVAILABLE', 'Giỏ hàng có sản phẩm không còn khả dụng', 409);
            }

            $price = $item->sale_price !== null ? (float) $item->sale_price : (float) $item->price;
            $subtotal += $price * (int) $item->quantity;
        }

        return $this->resolve($userId, $code, $subtotal);
    }

    /**
     * Xác thực và tính tiền giảm. Được CheckoutService gọi trong transaction
     * với $lockVoucher=true để chống vượt giới hạn khi nhiều người đặt cùng lúc.
     */
    public function resolve(int $userId, ?string $code, float $subtotal, bool $lockVoucher = false): array
    {
        $normalizedCode = $this->normalizeCode($code);
        if ($normalizedCode === null) {
            return $this->noVoucher($subtotal);
        }

        $query = DB::table('vouchers')->where('code', $normalizedCode);
        if ($lockVoucher) {
            $query->lockForUpdate();
        }

        $voucher = $query->first();
        if (!$voucher) {
            throw new BusinessException('VOUCHER_NOT_FOUND', 'Mã giảm giá không tồn tại', 422);
        }

        if ($voucher->status !== 'Active') {
            throw new BusinessException('VOUCHER_INACTIVE', 'Mã giảm giá hiện không hoạt động', 422);
        }

        $now = Carbon::now('UTC');
        if ($voucher->starts_at !== null && Carbon::parse($voucher->starts_at, 'UTC')->gt($now)) {
            throw new BusinessException('VOUCHER_NOT_STARTED', 'Mã giảm giá chưa đến thời gian áp dụng', 422);
        }
        if ($voucher->ends_at !== null && Carbon::parse($voucher->ends_at, 'UTC')->lt($now)) {
            throw new BusinessException('VOUCHER_EXPIRED', 'Mã giảm giá đã hết hạn', 422);
        }
        if ($voucher->usage_limit !== null && (int) $voucher->used_count >= (int) $voucher->usage_limit) {
            throw new BusinessException('VOUCHER_USAGE_LIMIT_REACHED', 'Mã giảm giá đã hết lượt sử dụng', 422);
        }

        $userUseCount = DB::table('voucher_redemptions')
            ->where('voucher_id', $voucher->id)
            ->where('user_id', $userId)
            ->count();
        if ($userUseCount >= (int) $voucher->per_user_limit) {
            throw new BusinessException('VOUCHER_USER_LIMIT_REACHED', 'Bạn đã dùng hết lượt áp dụng của mã này', 422);
        }

        $minimum = (float) $voucher->min_order_amount;
        if ($subtotal < $minimum) {
            throw new BusinessException(
                'VOUCHER_MIN_ORDER_NOT_REACHED',
                'Đơn hàng cần đạt tối thiểu ' . number_format($minimum, 0, ',', '.') . ' đ để áp dụng mã này',
                422
            );
        }

        $discount = $voucher->discount_type === 'Percentage'
            ? $subtotal * ((float) $voucher->discount_value / 100)
            : (float) $voucher->discount_value;

        if ($voucher->max_discount_amount !== null) {
            $discount = min($discount, (float) $voucher->max_discount_amount);
        }

        $discount = round(min($discount, $subtotal), 2);

        return [
            'voucher_id' => (int) $voucher->id,
            'code' => $voucher->code,
            'name' => $voucher->name,
            'discount_type' => $voucher->discount_type,
            'discount_value' => (float) $voucher->discount_value,
            'discount_amount' => $discount,
            'subtotal' => round($subtotal, 2),
            'total_amount' => round($subtotal - $discount, 2),
        ];
    }

    /**
     * Ghi nhận chỉ sau khi order được tạo thành công trong cùng transaction.
     */
    public function redeem(array $voucherResult, int $userId, int $orderId, string $createdAt): void
    {
        if (empty($voucherResult['voucher_id'])) {
            return;
        }

        DB::table('voucher_redemptions')->insert([
            'voucher_id' => $voucherResult['voucher_id'],
            'user_id' => $userId,
            'order_id' => $orderId,
            'discount_amount' => $voucherResult['discount_amount'],
            'created_at' => $createdAt,
        ]);

        DB::table('vouchers')
            ->where('id', $voucherResult['voucher_id'])
            ->increment('used_count', 1, ['updated_at' => $createdAt]);
    }

    protected function normalizeCode(?string $code): ?string
    {
        $code = trim((string) $code);
        return $code === '' ? null : mb_strtoupper($code);
    }

    protected function noVoucher(float $subtotal): array
    {
        return [
            'voucher_id' => null,
            'code' => null,
            'name' => null,
            'discount_type' => null,
            'discount_value' => 0.0,
            'discount_amount' => 0.0,
            'subtotal' => round($subtotal, 2),
            'total_amount' => round($subtotal, 2),
        ];
    }
}
