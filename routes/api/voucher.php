<?php

use App\Modules\Voucher\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->prefix('vouchers')->group(function () {
    Route::post('/preview', [VoucherController::class, 'preview']);
});
