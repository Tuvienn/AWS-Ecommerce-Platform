<?php

/**
 * Public route registry.
 *
 * The application currently keeps direct PHP entrypoints for compatibility;
 * this registry documents the canonical mapping before a future router is
 * introduced.
 *
 * @return array<string,string>
 */
return [
    '/' => 'public/index.php',
    '/products.php' => 'public/products.php',
    '/product_detail.php' => 'public/product_detail.php',
    '/cart.php' => 'public/cart.php',
    '/checkout.php' => 'public/checkout.php',
    '/account.php' => 'public/account.php',
    '/login.php' => 'public/login.php',
    '/register.php' => 'public/register.php',
    '/logout.php' => 'public/logout.php',
    '/success.php' => 'public/success.php',
    '/storage_file.php' => 'public/storage_file.php',
];
