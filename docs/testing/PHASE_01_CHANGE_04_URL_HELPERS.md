# Phase 1 — Báo cáo Change 4: URL Helper và Layout dùng chung

## Trạng thái

```text
CHANGE: Change 4 — URL helper và layout dùng chung
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## Thay đổi theo file

### `includes/functions.php`

- **Mục đích:** Tạo helper configuration, application URL, asset URL và
  redirect nội bộ.
- **Thay đổi:** Thêm `projectConfig()`, `appUrl()`, `assetUrl()`,
  `redirectTo()`; avatar local dùng asset URL cấu hình.
- **Lý do:** Loại bỏ hard-code base path theo từng nhóm an toàn.
- **Vai trò:** URL abstraction dùng chung.
- **Rủi ro:** Double slash, double-prefix hoặc mất query string.
- **Cách kiểm thử:** `/aws-ecommerce-platform`, base path rỗng, `/shop`, query string và
  absolute URL.
- **Kết quả:** `PASS`.

### Shared customer/admin layouts

Các file `includes/header.php`, `includes/footer.php`,
`admin/admin_header.php`, `admin/admin_footer.php` đã chuyển navigation,
assets và auth redirect sang helper; đồng thời truyền base path an toàn cho
JavaScript.

- **Rủi ro:** CSS/JS/navigation không tải hoặc admin redirect sai.
- **Cách kiểm thử:** HTTP smoke test, asset request và unauthenticated admin.
- **Kết quả:** `PASS`.

### `assets/js/main.js`

- **Mục đích:** Tạo cart AJAX URL theo configuration.
- **Thay đổi:** Thêm browser `appUrl()` và thay ba cart endpoint hard-code.
- **Rủi ro:** JavaScript syntax hoặc endpoint sai.
- **Cách kiểm thử:** Node syntax check và HTTP JS asset test; cart regression
  sâu được chạy ở Change 7.
- **Kết quả:** `PASS`.

## Test evidence

| Test | Kết quả |
|---|---|
| PHP lint | PASS |
| JavaScript syntax | PASS |
| URL helper với ba base path | PASS |
| Homepage `/aws-ecommerce-platform` | PASS — HTTP 200 |
| Products `/aws-ecommerce-platform` | PASS — HTTP 200 |
| Admin authorization redirect | PASS — HTTP 302 |
| CSS asset | PASS — HTTP 200 |
| JavaScript asset | PASS — HTTP 200 |
| Response không có PHP runtime error | PASS |

## File xóa

Không có.

