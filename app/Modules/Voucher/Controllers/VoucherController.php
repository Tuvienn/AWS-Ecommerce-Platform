<?php

namespace App\Modules\Voucher\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Voucher\Requests\PreviewVoucherRequest;
use App\Modules\Voucher\Services\VoucherService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherService $voucherService
    ) {}

    public function preview(PreviewVoucherRequest $request): JsonResponse
    {
        $result = $this->voucherService->previewForCart(
            (int) $request->user()->id,
            $request->validated('code')
        );

        return ApiResponse::success($result);
    }
}
