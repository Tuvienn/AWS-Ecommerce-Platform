# Phase 2 — Báo cáo Change 3: CSRF customer và cart AJAX

## Trạng thái

```text
CHANGE: Change 3 — CSRF cho customer workflow và cart AJAX
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## Thay đổi theo file

### `includes/header.php`

- **Mục đích:** Cung cấp CSRF token cho JavaScript cùng base path hiện tại.
- **Thay đổi:** Thêm `csrfToken` vào `window.AWS_ECOMMERCE_PLATFORM_CONFIG` bằng
  `json_encode()`.
- **Vai trò:** Cầu nối session token từ PHP sang cart AJAX.
- **Rủi ro:** Encode sai hoặc thiếu token ở một deployment path.
- **Cách kiểm thử:** Kiểm tra config ở `/aws-ecommerce-platform` và document root.
- **Kết quả:** `PASS`.

### `assets/js/main.js`

- **Mục đích:** Gửi token với mọi cart mutation.
- **Thay đổi:** Thêm `cartRequestBody()` dùng `URLSearchParams`; add, update và
  remove đều gửi `_csrf_token`.
- **Vai trò:** Client của cart AJAX.
- **Rủi ro:** Sai encoding hoặc làm hỏng endpoint.
- **Cách kiểm thử:** JavaScript syntax; add/remove hợp lệ ở hai base path.
- **Kết quả:** `PASS`.

### `ajax/cart_action.php`

- **Mục đích:** Bảo vệ server-side thay vì chỉ tin client.
- **Thay đổi:** Bắt buộc POST và CSRF token trước khi đọc action hoặc thay đổi
  session cart; lỗi trả JSON với HTTP 403/405.
- **Vai trò:** Cart mutation endpoint.
- **Rủi ro:** Request thiếu token vẫn thay đổi cart hoặc lỗi không phải JSON.
- **Cách kiểm thử:** GET, POST thiếu/sai/đúng token; kiểm tra cart count.
- **Kết quả:** `PASS`.

### `login.php` và `register.php`

- **Mục đích:** Bảo vệ authentication form và account creation.
- **Thay đổi:** Validate CSRF trước xử lý; thêm hidden field; tách bootstrap khỏi
  render header để response từ chối không xuất HTML/token trước đó.
- **Vai trò:** Authentication workflow.
- **Rủi ro:** Login/register hợp lệ bị chặn hoặc CSRF tạo user.
- **Cách kiểm thử:** Token thiếu/sai trả 403; token đúng login/register 302;
  kiểm tra database và dọn user fixture.
- **Kết quả:** `PASS`.

### `account.php`

- **Mục đích:** Bảo vệ profile, password và avatar mutation.
- **Thay đổi:** Validate CSRF cho mọi POST; thêm hidden field vào ba form; chỉ
  render header sau phần xử lý request.
- **Vai trò:** Customer account management.
- **Rủi ro:** Request giả mạo sửa profile/password/avatar.
- **Cách kiểm thử:** POST thiếu token trả 403 và profile database không đổi;
  token hợp lệ vẫn cập nhật/redirect đúng.
- **Kết quả:** `PASS`.

### `checkout.php`

- **Mục đích:** Ngăn request giả mạo tạo đơn.
- **Thay đổi:** Validate CSRF trước tạo transaction; thêm hidden field; render
  header sau request processing.
- **Vai trò:** Customer order creation.
- **Rủi ro:** Token thiếu vẫn tạo order hoặc giảm stock.
- **Cách kiểm thử:** Request thiếu token trả 403, order/stock không đổi; request
  hợp lệ tạo order và redirect success.
- **Kết quả:** `PASS`.

## Bằng chứng kiểm thử

| Test | Kết quả |
|---|---|
| PHP lint toàn repository | PASS |
| JavaScript syntax | PASS |
| Login token thiếu/sai | PASS — HTTP 403 |
| Login token đúng | PASS — HTTP 302 |
| Register thiếu token không tạo user | PASS |
| Register đúng token tạo user | PASS |
| Profile thiếu token không đổi database | PASS |
| Profile đúng token | PASS — HTTP 302 |
| Cart GET | PASS — HTTP 405 JSON |
| Cart POST thiếu/sai token | PASS — HTTP 403 JSON |
| Cart add/remove token đúng | PASS — HTTP 200 |
| Checkout thiếu token | PASS — order 0, stock 50 |
| Checkout token đúng | PASS — tạo order, stock giảm đúng |
| `/aws-ecommerce-platform` regression | PASS |
| Document root regression | PASS |
| `git diff --check` | PASS |

Order/user fixture đã được xóa; stock sản phẩm kiểm tra đã trở về `50`, số
order trở về `0` và không còn user email test.

## Vấn đề đã sửa

Các state-changing customer request trong scope Change 3 không còn được chấp
nhận nếu thiếu hoặc sai CSRF token. Admin CSRF và state-changing GET thuộc
Change 4, chưa được sửa trong change này.

## File xóa

Không có.
