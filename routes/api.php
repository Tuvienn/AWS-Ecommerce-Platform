<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Base path: /api
| Mỗi module có route file riêng, được include bên dưới.
|--------------------------------------------------------------------------
*/

// Identity & Auth routes
require __DIR__ . '/api/auth.php';

// Catalog routes (products, categories)
require __DIR__ . '/api/catalog.php';

// Catalog-based intelligent product recommendations
require __DIR__ . '/api/recommendation.php';

// Cart routes
require __DIR__ . '/api/cart.php';

// Favorites routes
require __DIR__ . '/api/favorite.php';

// Voucher routes
require __DIR__ . '/api/voucher.php';

// Order routes
require __DIR__ . '/api/order.php';

// Admin routes
require __DIR__ . '/api/admin.php';
