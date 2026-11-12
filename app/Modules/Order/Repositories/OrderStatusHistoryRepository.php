<?php

namespace App\Modules\Order\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderStatusHistoryRepository
{
    /**
     * Ghi một sự kiện không thể sửa ngược của vòng đời đơn hàng.
     * Hàm này phải được gọi bên trong transaction của nghiệp vụ chính.
     */
    public function create(array $data): int
    {
        $now = $data['created_at'] ?? Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        return (int) DB::table('order_status_histories')->insertGetId([
            'order_id' => $data['order_id'],
            'actor_user_id' => $data['actor_user_id'] ?? null,
            'from_status' => $data['from_status'] ?? null,
            'to_status' => $data['to_status'],
            'action' => $data['action'],
            'reason' => $data['reason'] ?? null,
            'created_at' => $now,
        ]);
    }

    /**
     * Lấy audit trail của một đơn, kèm người thực hiện nếu tài khoản vẫn tồn tại.
     */
    public function getByOrderId(int $orderId): Collection
    {
        return DB::table('order_status_histories as history')
            ->leftJoin('users as actor', 'history.actor_user_id', '=', 'actor.id')
            ->where('history.order_id', $orderId)
            ->orderBy('history.id', 'asc')
            ->select([
                'history.id',
                'history.order_id',
                'history.actor_user_id',
                'history.from_status',
                'history.to_status',
                'history.action',
                'history.reason',
                'history.created_at',
                'actor.fullname as actor_fullname',
                'actor.email as actor_email',
            ])
            ->get();
    }
}
