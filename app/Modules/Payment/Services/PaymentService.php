<?php

namespace App\Modules\Payment\Services;

use App\Modules\Catalog\Repositories\InventoryRepository;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Order\Repositories\OrderStatusHistoryRepository;
use App\Modules\Payment\Repositories\PaymentRepository;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected PaymentRepository $paymentRepository,
        protected OrderRepository $orderRepository,
        protected InventoryRepository $inventoryRepository,
        protected OrderStatusHistoryRepository $orderStatusHistoryRepository
    ) {}

    /**
     * Admin xác nhận đã thu tiền COD sau khi đơn đã giao hoặc đang giao.
     */
    public function confirmCodPayment(string $orderNumber, ?int $actorUserId = null): array
    {
        return DB::transaction(function () use ($orderNumber, $actorUserId) {
            $order = DB::table('orders')
                ->where('order_number', $orderNumber)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy đơn hàng', 404);
            }

            $payment = $this->paymentRepository->findPaymentByOrderIdForUpdate((int) $order->id);
            if (!$payment) {
                throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy thông tin thanh toán', 404);
            }

            if (
                $payment->method !== 'COD'
                || $payment->status !== 'Pending'
                || !in_array($order->status, ['Shipped', 'Delivered'], true)
            ) {
                throw new BusinessException(
                    'INVALID_PAYMENT_STATUS',
                    'Chỉ có thể xác nhận thanh toán COD cho đơn hàng đã giao hoặc đang giao và đang chờ thanh toán',
                    409
                );
            }

            $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
            $this->paymentRepository->updatePayment((int) $payment->id, [
                'status' => 'Paid',
                'paid_at' => $now,
                'updated_at' => $now,
            ]);
            $this->orderStatusHistoryRepository->create([
                'order_id' => (int) $order->id,
                'actor_user_id' => $actorUserId,
                'from_status' => $order->status,
                'to_status' => $order->status,
                'action' => 'cod_payment_confirmed',
                'created_at' => $now,
            ]);

            return [
                'order_number' => $order->order_number,
                'payment' => $this->formatPayment((array) $this->paymentRepository->findPaymentById((int) $payment->id)),
            ];
        });
    }

    /**
     * Admin xác nhận đã nhận được tiền chuyển khoản VietQR.
     *
     * Order luôn được khóa trước payment để đồng bộ với các luồng hủy đơn,
     * tránh trường hợp một đơn vừa được xác nhận vừa được hoàn kho.
     */
    public function confirmVietQrPayment(string $orderNumber, ?int $actorUserId = null): array
    {
        return DB::transaction(function () use ($orderNumber, $actorUserId) {
            [$order, $payment] = $this->getPendingVietQrOrderAndPayment($orderNumber);
            $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

            $this->paymentRepository->updatePayment((int) $payment->id, [
                'status' => 'Paid',
                'paid_at' => $now,
                'updated_at' => $now,
            ]);
            $this->orderRepository->updateOrderStatus((int) $order->id, 'Confirmed');
            $this->orderStatusHistoryRepository->create([
                'order_id' => (int) $order->id,
                'actor_user_id' => $actorUserId,
                'from_status' => 'Pending',
                'to_status' => 'Confirmed',
                'action' => 'vietqr_confirmed',
                'created_at' => $now,
            ]);

            return [
                'order_number' => $order->order_number,
                'order_status' => 'Confirmed',
                'payment' => $this->formatPayment((array) $this->paymentRepository->findPaymentById((int) $payment->id)),
            ];
        });
    }

    /**
     * Admin từ chối/hủy một đơn VietQR chưa thanh toán và hoàn tồn kho đúng một lần.
     */
    public function cancelVietQrPayment(string $orderNumber, ?string $reason = null, ?int $actorUserId = null): array
    {
        return DB::transaction(function () use ($orderNumber, $reason, $actorUserId) {
            [$order, $payment] = $this->getPendingVietQrOrderAndPayment($orderNumber);
            $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

            $note = $order->note;
            if ($reason !== null && trim($reason) !== '') {
                $cancelNote = 'Admin hủy VietQR: ' . trim($reason);
                $note = $note ? "{$note} | {$cancelNote}" : $cancelNote;
            }

            $this->orderRepository->updateOrderStatus((int) $order->id, 'Cancelled', $note);
            $this->paymentRepository->updatePayment((int) $payment->id, [
                'status' => 'Cancelled',
                'updated_at' => $now,
            ]);
            $this->paymentRepository->cancelPendingAttempts((int) $payment->id);

            foreach ($this->orderRepository->getOrderItems((int) $order->id) as $item) {
                if ($item->variant_id !== null) {
                    $this->inventoryRepository->increment((int) $item->variant_id, (int) $item->quantity);
                }
            }

            $this->orderStatusHistoryRepository->create([
                'order_id' => (int) $order->id,
                'actor_user_id' => $actorUserId,
                'from_status' => 'Pending',
                'to_status' => 'Cancelled',
                'action' => 'vietqr_cancelled',
                'reason' => $reason !== null ? trim($reason) : null,
                'created_at' => $now,
            ]);

            return [
                'order_number' => $order->order_number,
                'order_status' => 'Cancelled',
                'payment' => $this->formatPayment((array) $this->paymentRepository->findPaymentById((int) $payment->id)),
                'cancelled_at' => $now,
            ];
        });
    }

    /**
     * Danh sách giao dịch cho Admin.
     */
    public function listAdminPayments(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['page_size'] ?? 15)));
        $total = $this->paymentRepository->countAdminPayments($filters);
        $items = $this->paymentRepository->listAdminPayments($filters, $page, $pageSize)
            ->map(fn ($item) => $this->formatPayment((array) $item))
            ->all();

        return [
            'items' => $items,
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
        ];
    }

    /** @param array<string, mixed> $payment */
    private function formatPayment(array $payment): array
    {
        foreach (['id', 'order_id', 'user_id'] as $field) {
            if (isset($payment[$field]) && $payment[$field] !== null) {
                $payment[$field] = (string) $payment[$field];
            }
        }

        return $payment;
    }

    /**
     * @return array{0: object, 1: object}
     */
    private function getPendingVietQrOrderAndPayment(string $orderNumber): array
    {
        $order = $this->orderRepository->findOrderByNumberForUpdate($orderNumber);
        if (!$order) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy đơn hàng', 404);
        }

        $payment = $this->paymentRepository->findPaymentByOrderIdForUpdate((int) $order->id);
        if (!$payment) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy thông tin thanh toán', 404);
        }

        if (
            $payment->method !== 'VietQR'
            || $payment->status !== 'Pending'
            || $order->status !== 'Pending'
        ) {
            throw new BusinessException(
                'INVALID_VIETQR_PAYMENT_STATE',
                'Chỉ có thể xử lý thanh toán VietQR khi đơn hàng và payment đang ở trạng thái chờ xử lý',
                409
            );
        }

        return [$order, $payment];
    }
}
