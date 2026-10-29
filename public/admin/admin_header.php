<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Kiểm tra quyền admin
if (!isAdmin()) {
    redirectTo('/login.php');
}

$flash = getFlash();

// Xác định trang hiện tại
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - TechShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= sanitize(assetUrl('/assets/css/style.css')) ?>?v=<?= time() ?>">
    <script>
        window.AWS_ECOMMERCE_PLATFORM_CONFIG = <?= json_encode([
            'basePath' => projectConfig('app')['base_path'] ?? '',
            'csrfToken' => csrfToken(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <i class="fa-solid fa-bolt"></i> TechShop
        </div>
        <nav>
            <a href="<?= sanitize(appUrl('/admin/')) ?>" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="<?= sanitize(appUrl('/admin/products.php')) ?>" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-box"></i> Sản phẩm
            </a>
            <a href="<?= sanitize(appUrl('/admin/categories.php')) ?>" class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-layer-group"></i> Danh mục
            </a>
            <a href="<?= sanitize(appUrl('/admin/brands.php')) ?>" class="<?= $currentPage === 'brands.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-tags"></i> Nhãn hàng
            </a>
            <a href="<?= sanitize(appUrl('/admin/orders.php')) ?>" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-receipt"></i> Đơn hàng
            </a>
            <a href="<?= sanitize(appUrl('/admin/banners.php')) ?>" class="<?= $currentPage === 'banners.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-images"></i> Banner
            </a>
            <a href="<?= sanitize(appUrl('/')) ?>" style="margin-top:20px; border-top:1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <i class="fa-solid fa-store"></i> Về trang chủ
            </a>
            <form method="POST" action="<?= sanitize(appUrl('/logout.php')) ?>">
                <?= csrfField() ?>
                <button type="submit" style="width:100%;border:0;background:none;color:inherit;text-align:left;cursor:pointer;padding:12px 16px;font:inherit;">
                    <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                </button>
            </form>
        </nav>
    </aside>
    <div class="admin-content">
        <?php if ($flash): ?>
        <div class="flash flash-<?= $flash['type'] ?>">
            <?= $flash['message'] ?>
            <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>
