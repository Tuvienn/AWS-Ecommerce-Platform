<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TechShop - Siêu thị điện thoại & laptop chính hãng')</title>

    <!-- FontAwesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap Icons 1.11.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Font: Be Vietnam Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Storefront Design System CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">

    @stack('styles')
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="container header-main">
        <a href="/" class="logo">
            <i class="fa-solid fa-bolt"></i> Tech<span>Shop</span>
        </a>

        <button type="button" class="header-category-toggle" id="headerCategoryToggle" aria-expanded="false" aria-controls="headerCategoryPanel">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
            <span>Danh mục</span>
        </button>

        <!-- Form tìm kiếm chuẩn có nút bấm submit -->
        <form class="search-bar" action="/products" method="GET">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input id="header-search-input" type="text" name="q" placeholder="Tìm kiếm điện thoại, laptop, phụ kiện..." value="{{ request('q') }}" autocomplete="off" aria-autocomplete="list" aria-controls="header-ai-suggestions">
            <button type="submit" class="search-submit-btn" aria-label="Tìm kiếm">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            <div id="header-ai-suggestions" class="header-ai-suggestions" hidden aria-label="Gợi ý sản phẩm thông minh"></div>
        </form>

        <div class="header-actions">
            <!-- Nút Giỏ hàng sang trọng -->
            <a href="/cart" class="action-cart-btn" title="Xem giỏ hàng">
                <span class="cart-icon-wrap">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span id="headerCartBadge" class="badge-cart-count" style="display: none;">0</span>
                </span>
                <span class="d-none d-sm-inline">Giỏ hàng</span>
            </a>

            <!-- Khu vực User do window.AppAuth render -->
            <div id="headerUserArea" class="header-user-wrapper"></div>
        </div>
    </div>

    <!-- Bảng danh mục chỉ mở khi khách cần điều hướng. -->
    <div class="header-category-panel" id="headerCategoryPanel" hidden>
        <div class="container">
            <div class="category-mega-surface">
                <section class="category-mega-main" aria-labelledby="headerCategoryTitle">
                    <div class="category-mega-heading">
                        <p class="category-mega-eyebrow">TechShop</p>
                        <h2 id="headerCategoryTitle">Danh mục sản phẩm</h2>
                    </div>
                    <div class="category-menu-list" id="headerCategoryMenu" aria-live="polite">
                        <a class="category-menu-link" href="/products">
                            <span class="category-menu-icon"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></span>
                            <span>Tất cả sản phẩm</span>
                            <i class="fa-solid fa-arrow-right category-menu-arrow" aria-hidden="true"></i>
                        </a>
                    </div>
                </section>

                <aside class="category-mega-explore" aria-labelledby="headerExploreTitle">
                    <p class="category-mega-eyebrow">Khám phá nhanh</p>
                    <h2 id="headerExploreTitle">Mua sắm theo nhu cầu</h2>
                    <a href="/products?on_sale=1" class="category-feature-link category-feature-sale">
                        <i class="fa-solid fa-fire" aria-hidden="true"></i>
                        <span><strong>Ưu đãi đang diễn ra</strong><small>Xem sản phẩm có giảm giá</small></span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                    <a href="/products" class="category-feature-link">
                        <i class="fa-solid fa-sparkles" aria-hidden="true"></i>
                        <span><strong>Khám phá sản phẩm</strong><small>Xem toàn bộ catalog hiện có</small></span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                    <a href="/cart" class="category-feature-link">
                        <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                        <span><strong>Giỏ hàng của bạn</strong><small>Tiếp tục đơn hàng đang chọn</small></span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </aside>
            </div>
        </div>
    </div>
</header>

<!-- Main content -->
<main class="main-content py-4">
    <div class="container">
        @yield('content')
    </div>
</main>

<!-- Toast container cho thông báo toàn cục (ts-toast không xung đột với Bootstrap 5) -->
<div id="tsToastContainer" class="ts-toast-container"></div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h3><i class="fa-solid fa-bolt text-warning"></i> TechShop</h3>
                <p>Siêu thị điện thoại, laptop & phụ kiện chính hãng uy tín hàng đầu Việt Nam. Cam kết hàng chính hãng 100%, bảo hành chuẩn hãng, giá cạnh tranh nhất thị trường.</p>
                <div class="footer-socials">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="Youtube"><i class="fa-brands fa-youtube"></i></a>
                    <a href="#" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h3>Hỗ trợ khách hàng</h3>
                <ul>
                    <li><a href="#">Hướng dẫn mua hàng online</a></li>
                    <li><a href="#">Chính sách đổi trả & hoàn tiền</a></li>
                    <li><a href="#">Chính sách bảo hành</a></li>
                    <li><a href="#">Phương thức thanh toán COD / VietQR</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Về TechShop</h3>
                <ul>
                    <li><a href="#">Giới thiệu công ty</a></li>
                    <li><a href="#">Tuyển dụng</a></li>
                    <li><a href="#">Chính sách bảo mật thông tin</a></li>
                    <li><a href="#">Điều khoản sử dụng dịch vụ</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Thông tin liên hệ</h3>
                <ul>
                    <li><i class="fa-solid fa-location-dot me-2 text-primary"></i> 123 Nguyễn Huệ, Quận 1, TP.HCM</li>
                    <li><i class="fa-solid fa-phone me-2 text-primary"></i> 1900 1234 (8:00 - 21:00)</li>
                    <li><i class="fa-solid fa-envelope me-2 text-primary"></i> support@techshop.vn</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 TechShop. Nền tảng thương mại điện tử thiết bị công nghệ hàng đầu.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Core JavaScript -->
<script src="/assets/js/core/utils.js"></script>
<script src="/assets/js/core/api.js"></script>

<!-- Domain Services -->
<script src="/assets/js/services/authService.js"></script>
<script src="/assets/js/services/catalogService.js"></script>
<script src="/assets/js/services/cartService.js"></script>
<script src="/assets/js/services/favoriteService.js"></script>
<script src="/assets/js/services/orderService.js"></script>

<!-- App Auth & Header Init -->
<script src="/assets/js/core/auth.js"></script>
<script src="/assets/js/core/search-recommendations.js"></script>

@stack('scripts')
</body>
</html>
