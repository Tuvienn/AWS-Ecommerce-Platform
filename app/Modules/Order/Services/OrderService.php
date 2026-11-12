<?php

namespace App\Modules\Order\Services;

use App\Modules\Catalog\Repositories\InventoryRepository;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Order\Repositories\OrderStatusHistoryRepository;
use App\Modules\Payment\Repositories\PaymentRepository;
use App\Modules\Payment\Services\VietQrService;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Định nghĩa State Machine cho trạng thái đơn hàng
     */
    protected const STATUS_TRANSITIONS = [
        'Pending' => ['Confirmed', 'Cancelled'],
        'Confirmed' => ['Processing', 'Cancelled'],
        'Processing' => ['Shipped', 'Cancelled'],
        'Shipped' => ['Delivered'],
        'Delivered' => [],
        'Cancelled' => [],
    ];

    public function __construct(
        protected OrderRepository $orderRepository,
        protected InventoryRepository $inventoryRepository,
        protected PaymentRepository $paymentRepository,
        protected OrderStatusHistoryRepository $orderStatusHistoryRepository,
        protected VietQrService $vietQrService
    ) {}

    /**
     * Lấy danh sách đơn hàng của một người dùng (phân trang, lọc status)
     */
    public function listUserOrders(int $userId, array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['page_size'] ?? 10)));

        $total = $this->orderRepository->countUserOrders($userId, $filters);
        $rawItems = $this->orderRepository->listUserOrders($userId, $filters, $page, $pageSize)->toArray();

        $items = array_map(function ($item) {
            $arr = (array) $item;
            if (isset($arr['id'])) {
                $arr['id'] = (string) $arr['id'];
            }
            return $arr;
        }, $rawItems);

        return [
            'items' => $items,
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
        ];
    }

    /**
     * Lấy chi tiết đơn hàng của người dùng theo mã đơn
     */
    public function getUserOrderDetail(int $userId, string $orderNumber): array
    {
        $order = $this->orderRepository->findUserOrderByNumber($userId, $orderNumber);
        if (!$order) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy đơn hàng', 404);
        }

        $items = $this->orderRepository->getOrderItems((int) $order->id)->toArray();
        $formattedItems = array_map(function ($item) {
            $itemArr = (array) $item;
            if (isset($itemArr['id'])) $itemArr['id'] = (string) $itemArr['id'];
            if (isset($itemArr['order_id'])) $itemArr['order_id'] = (string) $itemArr['order_id'];
            if (isset($itemArr['variant_id']) && $itemArr['variant_id'] !== null) $itemArr['variant_id'] = (string) $itemArr['variant_id'];
            return $itemArr;
        }, $items);

        $payment = $this->paymentRepository->findPaymentByOrderId((int) $order->id);
        $paymentInstructions = $payment
            && $payment->method === 'VietQR'
            && $payment->status === 'Pending'
            && $this->vietQrService->isConfigured()
            ? $this->vietQrService->buildInstructions($order->order_number, (float) $payment->amount)
            : null;

        return [
            'id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'subtotal' => (float) $order->subtotal,
            'voucher_code' => $order->voucher_code,
            'discount_amount' => (float) $order->discount_amount,
            'shipping_fee' => (float) $order->shipping_fee,
            'total_amount' => (float) $order->total_amount,
            'shipping_address' => json_decode($order->shipping_address_snapshot, true),
            'payment_method' => $order->payment_method,
            'payment_expires_at' => $order->payment_expires_at,
            'note' => $order->note,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'items' => $formattedItems,
            'payment' => $payment ? [
                'id' => (string) $payment->id,
                'order_id' => (string) $payment->order_id,
                'method' => $payment->method,
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
                'paid_at' => $payment->paid_at,
            ] : null,
            'payment_instructions' => $paymentInstructions,
        ];
    }

    /**
     * Khách hàng tự hủy đơn hàng
     */
    public function cancelUserOrder(int $userId, string $orderNumber, ?string $reason = null): array
    {
        return DB::transaction(function () use ($userId, $orderNumber, $reason) {
            // Khóa dòng order chống race condition với IPN hoặc cron Expiry
            $order = $this->orderRepository->findUserOrderByNumberForUpdate($userId, $orderNumber);
            if (!$order) {
                throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy đơn hàng', 404);
            }

            // Chỉ được hủy khi đơn ở trạng thái Pending
            if ($order->status !== 'Pending') {
                throw new BusinessException(
                    'ORDER_CANNOT_BE_CANCELLED',
                    'Chỉ có thể hủy đơn hàng đang ở trạng thái chờ xác nhận',
                    409
                );
            }

            // Khóa dòng payment
            $payment = $this->paymentRepository->findPaymentByOrderIdForUpdate((int) $order->id);
            if ($payment && $payment->status === 'Paid') {
                throw new BusinessException(
                    'ORDER_ALREADY_PAID',
                    'Đơn hàng đã thanh toán không thể tự hủy',
                    409
                );
            }

            $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

            // Cập nhật lý do hủy vào note nếu có
            $note = $order->note;
            if ($reason) {
                $cancelNote = "Lý do hủy: " . trim($reason);
                $note = $note ? "{$note} | {$cancelNote}" : $cancelNote;
            }

            // 1. Cập nhật orders sang Cancelled
            $this->orderRepository->updateOrderStatus((int) $order->id, 'Cancelled', $note);

            // 2. Cập nhật payments sang Cancelled
            if ($payment) {
                $this->paymentRepository->updatePayment((int) $payment->id, [
                    'status' => 'Cancelled',
                    'updated_at' => $now,
                ]);

                // 3. Cập nhật payment_attempts đang Pending/Processing sang Cancelled
                $this->paymentRepository->cancelPendingAttempts((int) $payment->id);
            }

            // 4. Hoàn lại tồn kho cho từng item
            $items = $this->orderRepository->getOrderItems((int) $order->id);
            foreach ($items as $item) {
                if ($item->variant_id !== null) {
                    $this->inventoryRepository->increment((int) $item->variant_id, (int) $item->quantity);
                }
            }

            $this->orderStatusHistoryRepository->create([
                'order_id' => (int) $order->id,
                'actor_user_id' => $userId,
                'from_status' => 'Pending',
                'to_status' => 'Cancelled',
                'action' => 'customer_cancelled',
                'reason' => $reason !== null ? trim($reason) : null,
                'created_at' => $now,
            ]);

            return [
                'order_number' => $order->order_number,
                'status' => 'Cancelled',
                'cancelled_at' => $now,
            ];
        });
    }

    /**
     * Admin xem danh sách đơn hàng
     */
    public function listAdminOrders(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['page_size'] ?? 15)));

        $total = $this->orderRepository->countAdminOrders($filters);
        $rawItems = $this->orderRepository->listAdminOrders($filters, $page, $pageSize)->toArray();

        $items = array_map(function ($item) {
            $arr = (array) $item;
            if (isset($arr['id'])) $arr['id'] = (string) $arr['id'];
            if (isset($arr['user_id']) && $arr['user_id'] !== null) $arr['user_id'] = (string) $arr['user_id'];
            return $arr;
        }, $rawItems);

        return [
            'items' => $items,
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
        ];
    }

    /**
     * Admin xem chi tiết đơn hàng
     */
    public function getAdminOrderDetail(string $orderNumber): array
    {
        $order = $this->orderRepository->findOrderByNumber($orderNumber);
        if (!$order) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy đơn hàng', 404);
        }

        $user = DB::table('users')->where('id', $order->user_id)->select(['id', 'fullname', 'email', 'phone'])->first();
        $items = $this->orderRepository->getOrderItems((int) $order->id)->toArray();
        $formattedItems = array_map(function ($item) {
            $itemArr = (array) $item;
            if (isset($itemArr['id'])) $itemArr['id'] = (string) $itemArr['id'];
            if (isset($itemArr['order_id'])) $itemArr['order_id'] = (string) $itemArr['order_id'];
            if (isset($itemArr['variant_id']) && $itemArr['variant_id'] !== null) $itemArr['variant_id'] = (string) $itemArr['variant_id'];
            return $itemArr;
        }, $items);

        $payment = $this->paymentRepository->findPaymentByOrderId((int) $order->id);
        $rawAttempts = $payment ? $this->paymentRepository->getAttemptsByPaymentId((int) $payment->id)->toArray() : [];
        $formattedAttempts = array_map(function ($attempt) {
            $attArr = (array) $attempt;
            if (isset($attArr['id'])) $attArr['id'] = (string) $attArr['id'];
            if (isset($attArr['payment_id'])) $attArr['payment_id'] = (string) $attArr['payment_id'];
            return $attArr;
        }, $rawAttempts);
        $statusHistory = $this->orderStatusHistoryRepository
            ->getByOrderId((int) $order->id)
            ->map(function ($event) {
                return [
                    'id' => (string) $event->id,
                    'order_id' => (string) $event->order_id,
                    'from_status' => $event->from_status,
                    'to_status' => $event->to_status,
                    'action' => $event->action,
                    'reason' => $event->reason,
                    'created_at' => $event->created_at,
                    'actor' => $event->actor_user_id !== null ? [
                        'id' => (string) $event->actor_user_id,
                        'fullname' => $event->actor_fullname,
                        'email' => $event->actor_email,
                    ] : null,
                ];
            })
            ->all();

        return [
            'id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'subtotal' => (float) $order->subtotal,
            'voucher_code' => $order->voucher_code,
            'discount_amount' => (float) $order->discount_amount,
            'shipping_fee' => (float) $order->shipping_fee,
            'total_amount' => (float) $order->total_amount,
            'shipping_address' => json_decode($order->shipping_address_snapshot, true),
            'payment_method' => $order->payment_method,
            'payment_expires_at' => $order->payment_expires_at,
            'note' => $order->note,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'user' => $user ? [
                'id' => (string) $user->id,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->phone,
            ] : null,
            'items' => $formattedItems,
            'payment' => $payment ? [
                'id' => (string) $payment->id,
                'order_id' => (string) $payment->order_id,
                'method' => $payment->method,
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
                'paid_at' => $payment->paid_at,
                'attempts' => $formattedAttempts,
            ] : null,
            'status_history' => $statusHistory,
        ];
    }

    /**
     * Admin cập nhật trạng thái đơn hàng theo state machine
     */
    public function updateOrderStatus(
        string $orderNumber,
        string $newStatus,
        ?int $actorUserId = null,
        ?string $reason = null
    ): array
    {
        return DB::transaction(function () use ($orderNumber, $newStatus, $actorUserId, $reason) {
            $order = $this->orderRepository->findOrderByNumberForUpdate($orderNumber);
            if (!$order) {
                throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy đơn hàng', 404);
            }

            // Luôn khóa payment ngay sau order để đồng bộ lock order -> payment
            // với các thao tác đối soát VietQR và tránh hai luồng xử lý cùng một đơn.
            $payment = $this->paymentRepository->findPaymentByOrderIdForUpdate((int) $order->id);
            $currentStatus = $order->status;
            $allowedNext = self::STATUS_TRANSITIONS[$currentStatus] ?? [];

            if (!in_array($newStatus, $allowedNext, true)) {
                throw new BusinessException(
                    'INVALID_STATUS_TRANSITION',
                    "Không thể chuyển trạng thái từ {$currentStatus} sang {$newStatus}",
                    409
                );
            }

            // VietQR chỉ được xác nhận hoặc hủy qua PaymentService. API trạng thái
            // chung không được bỏ qua bước đối soát và nghiệp vụ hoàn kho của payment.
            if ($payment && $payment->method === 'VietQR' && $payment->status === 'Pending') {
                if ($newStatus === 'Confirmed') {
                    throw new BusinessException(
                        'VIETQR_REQUIRES_PAYMENT_CONFIRMATION',
                        'Đơn VietQR đang chờ thanh toán phải được xác nhận từ màn hình quản lý thanh toán',
                        409
                    );
                }

                if ($newStatus === 'Cancelled') {
                    throw new BusinessException(
                        'VIETQR_REQUIRES_PAYMENT_ACTION',
                        'Đơn VietQR đang chờ thanh toán phải được hủy từ màn hình quản lý thanh toán',
                        409
                    );
                }
            }

            // Chưa có nghiệp vụ refund, vì vậy tuyệt đối không cho hủy đơn đã thanh toán
            // qua luồng trạng thái thông thường để tránh tồn kho và dòng tiền bị lệch nhau.
            if ($newStatus === 'Cancelled' && $payment && $payment->status === 'Paid') {
                throw new BusinessException(
                    'ORDER_ALREADY_PAID',
                    'Đơn hàng đã thanh toán không thể hủy qua luồng quản lý đơn hàng',
                    409
                );
            }

            $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
            $cancelReason = $newStatus === 'Cancelled' && $reason !== null && trim($reason) !== ''
                ? trim($reason)
                : null;
            $note = null;

            if ($cancelReason !== null) {
                $cancelNote = "Admin hủy đơn: {$cancelReason}";
                $note = $order->note ? "{$order->note} | {$cancelNote}" : $cancelNote;
            }

            // Cập nhật trạng thái order
            $this->orderRepository->updateOrderStatus((int) $order->id, $newStatus, $note);

            // Nếu hủy đơn: hoàn lại tồn kho và cập nhật payment
            if ($newStatus === 'Cancelled') {
                $items = $this->orderRepository->getOrderItems((int) $order->id);
                foreach ($items as $item) {
                    if ($item->variant_id !== null) {
                        $this->inventoryRepository->increment((int) $item->variant_id, (int) $item->quantity);
                    }
                }

                if ($payment && $payment->status !== 'Paid') {
                    $this->paymentRepository->updatePayment((int) $payment->id, [
                        'status' => 'Cancelled',
                        'updated_at' => $now,
                    ]);

                    $this->paymentRepository->cancelPendingAttempts((int) $payment->id);
                }
            }

            $this->orderStatusHistoryRepository->create([
                'order_id' => (int) $order->id,
                'actor_user_id' => $actorUserId,
                'from_status' => $currentStatus,
                'to_status' => $newStatus,
                'action' => 'admin_status_updated',
                'reason' => $cancelReason,
                'created_at' => $now,
            ]);

            return [
                'order_number' => $order->order_number,
                'previous_status' => $currentStatus,
                'status' => $newStatus,
                'reason' => $cancelReason,
                'updated_at' => $now,
            ];
        });
    }

}
