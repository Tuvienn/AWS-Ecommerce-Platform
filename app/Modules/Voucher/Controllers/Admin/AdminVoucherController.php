<?php

namespace App\Modules\Voucher\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Voucher\Requests\AdminVoucherRequest;
use App\Modules\Voucher\Services\AdminVoucherService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVoucherController extends Controller
{
    public function __construct(
        protected AdminVoucherService $voucherService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->voucherService->list($request->only(['q', 'status'])));
    }

    public function store(AdminVoucherRequest $request): JsonResponse
    {
        return ApiResponse::success($this->voucherService->create($request->validated()), 201);
    }

    public function update(int $id, AdminVoucherRequest $request): JsonResponse
    {
        return ApiResponse::success($this->voucherService->update($id, $request->validated()));
    }
}
