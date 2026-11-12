@extends('layouts.app')

@section('title', 'Thanh toán đơn hàng - TechShop')

@push('styles')
<link rel="stylesheet" href="/assets/css/customer-pages.css">
@endpush

@section('content')
<div class="customer-page-wrapper py-4">
    <!-- Breadcrumb chuẩn TechShop -->
    <div class="breadcrumb">
        <a href="/"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <a href="/cart">Giỏ hàng</a>
        <span>/</span>
        <strong>Thanh toán</strong>
    </div>

    <div class="mb-4">
        <h1 class="h3 mb-1 fw-bold text-dark">Đặt hàng & Thanh toán</h1>
        <p class="text-muted small mb-0">Vui lòng kiểm tra lại thông tin nhận hàng và chọn phương thức thanh toán phù hợp</p>
    </div>

    <!-- Loading State -->
    <div id="checkout-loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Đang tải...</span>
        </div>
        <p class="text-muted mt-2 small">Đang chuẩn bị thông tin thanh toán...</p>
    </div>

    <!-- Checkout Form & Content -->
    <div id="checkout-content" class="row g-4 d-none">
        <!-- Cột trái: Thông tin giao hàng & Phương thức thanh toán -->
        <div class="col-lg-7">
            <form id="checkout-form" novalidate>
                <!-- Khối 1: Thông tin người nhận -->
                <div class="ts-card mb-4">
                    <div class="ts-card-header">
                        <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center">
                            <i class="fa-solid fa-location-dot text-primary me-2"></i> 1. Thông tin giao hàng
                        </h5>
                    </div>
                    <div class="ts-card-body">
                        <div class="mb-3">
                            <label for="fullname" class="form-label fw-semibold small text-dark">Họ và tên người nhận <span class="text-danger">*</span></label>
                            <input type="text" class="form-control ts-form-control" id="fullname" name="fullname" placeholder="Nguyễn Văn A" required maxlength="150">
                            <div class="invalid-feedback">Vui lòng nhập họ và tên người nhận.</div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold small text-dark">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control ts-form-control" id="phone" name="phone" placeholder="0912345678" required maxlength="20">
                            <div class="invalid-feedback">Vui lòng nhập số điện thoại hợp lệ.</div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label fw-semibold small text-dark">Địa chỉ nhận hàng chi tiết <span class="text-danger">*</span></label>
                            <textarea class="form-control ts-form-control" id="address" name="address" rows="3" placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố" required maxlength="500"></textarea>
                            <div class="invalid-feedback">Vui lòng nhập địa chỉ nhận hàng chi tiết.</div>
                        </div>

                        <div class="mb-0">
                            <label for="note" class="form-label fw-semibold small text-dark">Ghi chú cho đơn hàng (tùy chọn)</label>
                            <textarea class="form-control ts-form-control" id="note" name="note" rows="2" placeholder="Ví dụ: Giao vào giờ hành chính, gọi trước khi giao..." maxlength="500"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Khối 2: Phương thức thanh toán -->
                <div class="ts-card mb-4">
                    <div class="ts-card-header">
                        <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center">
                            <i class="fa-solid fa-credit-card text-primary me-2"></i> 2. Phương thức thanh toán
                        </h5>
                    </div>
                    <div class="ts-card-body">
                        <!-- Option COD -->
                        <label class="payment-method-card" for="payment-cod">
                            <input type="radio" name="payment_method" id="payment-cod" value="COD" checked>
                            <div class="payment-method-info">
                                <div class="payment-method-title">
                                    <span>Thanh toán khi nhận hàng (COD)</span>
                                    <span class="badge bg-secondary">Tiền mặt</span>
                                </div>
                                <p class="payment-method-desc">
                                    Quý khách chỉ thanh toán tiền mặt cho nhân viên chuyển phát khi nhận hàng tận nơi.
                                </p>
                            </div>
                        </label>

                        <!-- Option VietQR -->
                        <label class="payment-method-card" for="payment-vietqr">
                            <input type="radio" name="payment_method" id="payment-vietqr" value="VietQR">
                            <div class="payment-method-info">
                                <div class="payment-method-title">
                                    <span><i class="fa-solid fa-qrcode text-primary me-1"></i> Chuyển khoản ngân hàng VietQR</span>
                                    <span class="badge bg-primary">Quét mã QR</span>
                                </div>
                                <p class="payment-method-desc">
                                    Sau khi đặt hàng, quét mã QR và chuyển đúng số tiền với nội dung được cung cấp. Đơn được xác nhận sau khi TechShop đối soát.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
            </form>
        </div>

        <!-- Cột phải: Tóm tắt đơn hàng -->
        <div class="col-lg-5">
            <div class="order-summary-card p-4 sticky-top" style="top: 90px;">
                <div class="checkout-summary-header d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
                    <h5 class="fw-bold text-dark mb-0">Đơn hàng (<span id="checkout-item-count">0</span>)</h5>
                    <a href="/cart" class="small text-primary text-decoration-none fw-semibold">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Sửa giỏ
                    </a>
                </div>

                <!-- Danh sách rút gọn sản phẩm -->
                <div id="checkout-items-list" class="mb-3" style="max-height: 300px; overflow-y: auto;">
                    <!-- Render bằng JavaScript -->
                </div>

                <hr class="my-3" style="border-color: #e2e8f0;">

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Tạm tính</span>
                    <span id="checkout-subtotal" class="fw-semibold text-dark">0 ₫</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted small">Phí vận chuyển</span>
                    <span class="text-success fw-semibold small">Miễn phí toàn quốc</span>
                </div>

                <div class="voucher-box border rounded-3 p-3 mb-3 bg-light-subtle">
                    <label for="voucher-code" class="form-label fw-semibold small mb-2">
                        <i class="fa-solid fa-ticket text-primary me-1"></i> Mã giảm giá
                    </label>
                    <div class="input-group">
                        <input type="text" class="form-control text-uppercase" id="voucher-code" maxlength="50" autocomplete="off" placeholder="Ví dụ: TECH10" aria-describedby="voucher-help">
                        <button class="btn btn-outline-primary fw-semibold" type="button" id="btn-apply-voucher">Áp dụng</button>
                    </div>
                    <div id="voucher-help" class="form-text">Dùng thử: <strong>TECH10</strong> hoặc <strong>GIAM500K</strong>.</div>
                    <div id="voucher-result" class="small mt-2 d-none" aria-live="polite"></div>
                </div>

                <div id="checkout-discount-row" class="d-flex justify-content-between mb-3 d-none">
                    <span class="text-success small">Giảm giá <span id="checkout-voucher-label"></span></span>
                    <span id="checkout-discount-amount" class="text-success fw-semibold small">-0 ₫</span>
                </div>

                <hr class="my-3" style="border-color: #e2e8f0;">

                <div class="d-flex justify-content-between align-items-baseline mb-4">
                    <span class="fs-6 fw-bold text-dark">Tổng thanh toán</span>
                    <span id="checkout-total-amount" class="fs-4 fw-bold text-danger">0 ₫</span>
                </div>

                <button id="btn-submit-order" type="button" class="btn btn-place-order w-100">
                    <i class="fa-solid fa-check me-2"></i> Xác nhận đặt hàng
                </button>

                <div class="mt-3 text-center">
                    <small class="text-muted d-inline-flex align-items-center gap-1">
                        <i class="fa-solid fa-shield-halved text-success"></i> Thông tin được mã hóa bảo mật tuyệt đối
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/pages/checkout.js"></script>
@endpush
