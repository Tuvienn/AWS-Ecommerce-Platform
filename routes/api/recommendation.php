<?php

use App\Modules\Recommendation\Controllers\ProductRecommendationController;
use Illuminate\Support\Facades\Route;

Route::get('/recommendations/products', [ProductRecommendationController::class, 'index']);
