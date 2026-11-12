<?php

namespace App\Modules\Payment\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Requests\CancelVietQrPaymentRequest;
use App\Modules\Payment\Services\PaymentService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * GET /api/admin/payments
     * Lấy danh sách thanh toán phân trang cho Admin
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'method', 'q', 'page', 'page_size']);
        $result = $this->paymentService->listAdminPayments($filters);

        return ApiResponse::paginated(
            $result['items'],
            $result['page'],
            $result['page_size'],
            $result['total']
        );
    }

    /**
     * POST /api/admin/payments/{orderNumber}/confirm-cod
     * Xác nhận đã thu tiền COD
     */
    public function confirmCod(Request $request, string $orderNumber): JsonResponse
    {
        $result = $this->paymentService->confirmCodPayment($orderNumber, (int) $request->user()->id);

        return ApiResponse::success($result);
    }

    /**
     * POST /api/admin/payments/{orderNumber}/confirm-vietqr
     * Xác nhận admin đã đối soát tiền chuyển khoản VietQR.
     */
    public function confirmVietQr(Request $request, string $orderNumber): JsonResponse
    {
        $result = $this->paymentService->confirmVietQrPayment($orderNumber, (int) $request->user()->id);

        return ApiResponse::success($result);
    }

    /**
     * POST /api/admin/payments/{orderNumber}/cancel-vietqr
     * Từ chối/hủy đơn VietQR chưa thanh toán và hoàn tồn kho.
     */
    public function cancelVietQr(CancelVietQrPaymentRequest $request, string $orderNumber): JsonResponse
    {
        $result = $this->paymentService->cancelVietQrPayment(
            $orderNumber,
            $request->input('reason'),
            (int) $request->user()->id
        );

        return ApiResponse::success($result);
    }
}
