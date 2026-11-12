@extends('layouts.app')

@section('title', 'TechShop - Thiết bị công nghệ chính hãng')

@push('styles')
<link rel="stylesheet" href="/assets/css/storefront-pages.css">
@endpush

@section('content')
<div class="home-editorial">
    <section class="editorial-hero" id="editorialHero" aria-labelledby="editorialHeroTitle">
        <div class="editorial-hero-copy">
            <p class="editorial-eyebrow" id="editorialHeroEyebrow">Lựa chọn nổi bật</p>
            <h1 id="editorialHeroTitle">Công nghệ phù hợp với nhịp sống của bạn.</h1>
            <p class="editorial-hero-description" id="editorialHeroDescription">Khám phá những thiết bị đang có mặt tại TechShop.</p>
            <p class="editorial-price" id="editorialHeroPrice" aria-live="polite"></p>
            <div class="editorial-actions">
                <a class="editorial-button editorial-button-primary" id="editorialHeroPrimaryCta" href="/products">Khám phá sản phẩm</a>
                <a class="editorial-button editorial-button-secondary" id="editorialHeroSecondaryCta" href="/products">Xem tất cả sản phẩm</a>
            </div>
        </div>
        <div class="editorial-hero-visual" id="editorialHeroVisual" aria-hidden="true">
            <div class="editorial-image-placeholder"><i class="fa-solid fa-mobile-screen-button"></i></div>
        </div>
    </section>

    <section class="editorial-campaigns" aria-label="Khám phá nổi bật">
        <article class="editorial-campaign-card editorial-campaign-laptop" id="editorialCampaignLaptop">
            <div class="editorial-campaign-copy">
                <p class="editorial-eyebrow">Laptop</p>
                <h2>Hiệu năng cho nhịp làm việc của bạn.</h2>
                <p>Khám phá các mẫu laptop đang có tại TechShop.</p>
                <a class="editorial-text-link" href="/products?category=laptop">Khám phá laptop <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="editorial-campaign-image" aria-hidden="true"></div>
        </article>
        <article class="editorial-campaign-card editorial-campaign-audio" id="editorialCampaignAudio">
            <div class="editorial-campaign-copy">
                <p class="editorial-eyebrow">Âm thanh</p>
                <h2>Âm thanh cho từng khoảnh khắc.</h2>
                <p>Chọn tai nghe phù hợp cho công việc, giải trí và di chuyển.</p>
                <a class="editorial-text-link" href="/products?category=am-thanh">Khám phá âm thanh <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="editorial-campaign-image" aria-hidden="true"></div>
        </article>
    </section>

    <section class="editorial-section editorial-categories-section" aria-labelledby="categorySectionTitle">
        <div class="editorial-section-heading">
            <div>
                <p class="editorial-eyebrow">Khám phá</p>
                <h2 id="categorySectionTitle">Chọn theo nhu cầu của bạn.</h2>
            </div>
            <a class="editorial-text-link" href="/products">Xem tất cả <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="editorial-category-grid" id="categoryGrid" aria-live="polite">
            <div class="category-card skeleton-card"><div class="skeleton-icon"></div><span class="skeleton-text"></span></div>
            <div class="category-card skeleton-card"><div class="skeleton-icon"></div><span class="skeleton-text"></span></div>
            <div class="category-card skeleton-card"><div class="skeleton-icon"></div><span class="skeleton-text"></span></div>
            <div class="category-card skeleton-card"><div class="skeleton-icon"></div><span class="skeleton-text"></span></div>
        </div>
    </section>

    <section class="editorial-section" id="flashSaleSection" aria-labelledby="flashSaleTitle">
        <div class="editorial-section-heading">
            <div>
                <p class="editorial-eyebrow editorial-eyebrow-sale">Ưu đãi hiện có</p>
                <h2 id="flashSaleTitle">Giảm giá đáng chú ý.</h2>
            </div>
            <a class="editorial-text-link" href="/products?sort=price_asc">Xem tất cả <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="product-grid" id="flashSaleGrid" aria-live="polite"></div>
    </section>

    <section class="editorial-section" id="newProductsSection" aria-labelledby="newProductsTitle">
        <div class="editorial-section-heading">
            <div>
                <p class="editorial-eyebrow">Vừa cập nhật</p>
                <h2 id="newProductsTitle">Sản phẩm mới nhất.</h2>
            </div>
            <a class="editorial-text-link" href="/products?sort=newest">Xem tất cả <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="product-grid" id="newProductsGrid" aria-live="polite"></div>
    </section>

    <section class="editorial-section" id="premiumProductsSection" aria-labelledby="premiumProductsTitle">
        <div class="editorial-section-heading">
            <div>
                <p class="editorial-eyebrow">Lựa chọn cao cấp</p>
                <h2 id="premiumProductsTitle">Thiết bị dành cho điều bạn yêu thích.</h2>
            </div>
            <a class="editorial-text-link" href="/products?sort=price_desc">Xem tất cả <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="product-grid" id="premiumProductsGrid" aria-live="polite"></div>
    </section>

    <section class="editorial-assurance" aria-labelledby="assuranceTitle">
        <div class="editorial-assurance-heading">
            <p class="editorial-eyebrow">TechShop</p>
            <h2 id="assuranceTitle">Mua sắm rõ ràng, theo dõi dễ dàng.</h2>
        </div>
        <div class="editorial-assurance-grid">
            <article><i class="fa-solid fa-credit-card" aria-hidden="true"></i><h3>Thanh toán linh hoạt</h3><p>Chọn thanh toán khi nhận hàng hoặc chuyển khoản VietQR khi tạo đơn.</p></article>
            <article><i class="fa-solid fa-receipt" aria-hidden="true"></i><h3>Theo dõi đơn hàng</h3><p>Xem trạng thái đơn và thông tin thanh toán trong tài khoản của bạn.</p></article>
            <article><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i><h3>Thông tin còn hàng</h3><p>Sản phẩm hiển thị trạng thái khả dụng dựa trên dữ liệu tồn kho hiện có.</p></article>
            <article><i class="fa-solid fa-headset" aria-hidden="true"></i><h3>Hỗ trợ đơn hàng</h3><p>Liên hệ TechShop khi cần hỗ trợ về đơn hàng hoặc thanh toán.</p></article>
        </div>
    </section>

    <section class="editorial-how-it-works" aria-labelledby="howItWorksTitle">
        <div class="editorial-section-heading"><div><p class="editorial-eyebrow">Bắt đầu thật đơn giản</p><h2 id="howItWorksTitle">Từ lựa chọn đến theo dõi đơn hàng.</h2></div></div>
        <ol class="editorial-steps">
            <li><span>01</span><div><h3>Chọn sản phẩm</h3><p>Tìm theo danh mục, thương hiệu hoặc nhu cầu.</p></div></li>
            <li><span>02</span><div><h3>Thêm vào giỏ</h3><p>Kiểm tra biến thể, số lượng và tổng tiền.</p></div></li>
            <li><span>03</span><div><h3>Chọn thanh toán</h3><p>Hoàn tất đơn bằng COD hoặc VietQR.</p></div></li>
            <li><span>04</span><div><h3>Theo dõi đơn</h3><p>Xem tiến trình xử lý trong tài khoản.</p></div></li>
        </ol>
    </section>

    <section class="editorial-faq" aria-labelledby="faqTitle">
        <div class="editorial-section-heading"><div><p class="editorial-eyebrow">Giải đáp nhanh</p><h2 id="faqTitle">Câu hỏi thường gặp.</h2></div></div>
        <div class="editorial-faq-list">
            <details><summary>TechShop hỗ trợ những phương thức thanh toán nào?</summary><p>Bạn có thể chọn thanh toán khi nhận hàng (COD) hoặc chuyển khoản VietQR trong bước checkout.</p></details>
            <details><summary>Sau khi chuyển VietQR, đơn hàng được xử lý như thế nào?</summary><p>Đơn VietQR ở trạng thái chờ thanh toán sẽ được quản trị viên đối soát trước khi tiếp tục xử lý.</p></details>
            <details><summary>Tôi xem trạng thái đơn hàng ở đâu?</summary><p>Sau khi đăng nhập, vào Tài khoản để xem danh sách đơn và tiến trình của từng đơn hàng.</p></details>
            <details><summary>Tôi có thể hủy đơn hàng không?</summary><p>Bạn có thể gửi yêu cầu hủy khi đơn còn ở trạng thái cho phép; hệ thống sẽ hiển thị lựa chọn này trong chi tiết đơn.</p></details>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="/assets/js/config/home-campaigns.js"></script>
<script src="/assets/js/pages/home.js"></script>
@endpush
