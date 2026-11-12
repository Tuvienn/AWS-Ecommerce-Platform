<?php

use App\Modules\Favorite\Controllers\FavoriteController;
use Illuminate\Support\Facades\Route;

Route::prefix('favorites')->middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/', [FavoriteController::class, 'index']);
    Route::post('/{productId}', [FavoriteController::class, 'store'])->whereNumber('productId');
    Route::delete('/{productId}', [FavoriteController::class, 'destroy'])->whereNumber('productId');
});
