@extends('layouts.admin')

@section('title', 'Mã giảm giá - Admin TechShop')
@section('page_title', 'Mã giảm giá')

@section('content')
<div class="admin-page-container">
    <div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title h3 mb-1 fw-bold">Quản lý mã giảm giá</h1>
            <p class="text-muted mb-0">Tạo, điều chỉnh điều kiện và bật/tắt voucher cho khách hàng.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="voucher-form" class="row g-3" novalidate>
                <input type="hidden" id="voucher-id">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small" for="voucher-code">Mã voucher</label>
                    <input id="voucher-code" class="form-control text-uppercase" maxlength="50" placeholder="VD: TECH10" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold small" for="voucher-name">Tên hiển thị</label>
                    <input id="voucher-name" class="form-control" maxlength="150" placeholder="VD: Giảm 10% cuối tuần" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small" for="voucher-type">Loại giảm</label>
                    <select id="voucher-type" class="form-select">
                        <option value="Percentage">Phần trăm (%)</option>
                        <option value="FixedAmount">Số tiền (đ)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small" for="voucher-value">Giá trị giảm</label>
                    <input id="voucher-value" type="number" min="1" step="0.01" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small" for="voucher-min-order">Đơn tối thiểu (đ)</label>
                    <input id="voucher-min-order" type="number" min="0" step="1" class="form-control" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small" for="voucher-max-discount">Giảm tối đa (đ)</label>
                    <input id="voucher-max-discount" type="number" min="1" step="1" class="form-control" placeholder="Không giới hạn">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small" for="voucher-usage-limit">Tổng lượt dùng</label>
                    <input id="voucher-usage-limit" type="number" min="1" step="1" class="form-control" placeholder="Không giới hạn">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small" for="voucher-user-limit">Lượt/khách</label>
                    <input id="voucher-user-limit" type="number" min="1" step="1" class="form-control" value="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small" for="voucher-status">Trạng thái</label>
                    <select id="voucher-status" class="form-select">
                        <option value="Active">Đang bật</option>
                        <option value="Inactive">Tạm tắt</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small" for="voucher-starts-at">Bắt đầu</label>
                    <input id="voucher-starts-at" type="datetime-local" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small" for="voucher-ends-at">Kết thúc</label>
                    <input id="voucher-ends-at" type="datetime-local" class="form-control">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button id="btn-save-voucher" type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-1"></i> Tạo voucher
                    </button>
                    <button id="btn-reset-voucher" type="button" class="btn btn-outline-secondary d-none">Hủy sửa</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex gap-2 align-items-center">
            <input id="voucher-search" class="form-control form-control-sm" style="max-width: 330px;" placeholder="Tìm mã hoặc tên voucher">
            <select id="voucher-status-filter" class="form-select form-select-sm" style="max-width: 180px;">
                <option value="">Tất cả trạng thái</option>
                <option value="Active">Đang bật</option>
                <option value="Inactive">Tạm tắt</option>
            </select>
            <button id="btn-filter-voucher" type="button" class="btn btn-sm btn-outline-primary">Lọc</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã / tên</th>
                        <th>Ưu đãi</th>
                        <th>Điều kiện</th>
                        <th>Đã dùng</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="voucher-table-body">
                    <tr><td colspan="6" class="text-center py-4 text-muted">Đang tải voucher...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/admin/vouchers.js"></script>
@endpush
