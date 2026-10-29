# Phase 2 — Báo cáo Change 1: Security helper và session hardening

## Trạng thái

```text
CHANGE: Change 1 — Security helper và session hardening
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## Thay đổi theo file

### `includes/security.php`

- **Mục đích:** Tạo nền tảng dùng chung cho CSRF và HTTP method guard.
- **Thay đổi:** Thêm `csrfToken()`, `csrfField()`, `isValidCsrfToken()`,
  `requireValidCsrfToken()`, `requirePostRequest()` và response lỗi bảo mật.
- **Lý do:** Các change tiếp theo cần cùng một cơ chế sinh/kiểm tra token và từ
  chối method không hợp lệ.
- **Vai trò:** Security helper dùng chung cho customer, AJAX và admin.
- **Rủi ro:** Token không ổn định trong session hoặc lộ chi tiết không cần thiết.
- **Cách kiểm thử:** Token dài 64 ký tự hex, ổn định trong một session, token
  đúng được chấp nhận và token sai bị từ chối.
- **Kết quả:** `PASS`.

### `includes/bootstrap.php`

- **Mục đích:** Cấu hình session an toàn trước `session_start()`.
- **Thay đổi:** Bật strict mode, cookie-only session, `HttpOnly`,
  `SameSite=Lax`, cookie path theo `APP_BASE_PATH`; bật `Secure` khi production
  URL dùng HTTPS.
- **Lý do:** Giảm nguy cơ session fixation/cookie misuse và giữ portability.
- **Vai trò:** Điểm cấu hình session tập trung.
- **Rủi ro:** Cookie path sai làm mất login/cart ở subpath hoặc document root.
- **Cách kiểm thử:** Quan sát `Set-Cookie` ở hai base path và đọc production
  cookie parameters bằng PHP CLI.
- **Kết quả:** `PASS`.

### `includes/functions.php`

- **Mục đích:** Nạp security helper và bảo vệ quy trình login.
- **Thay đổi:** Require `security.php`; `loginUser()` regenerate session ID và
  hủy ID cũ trước khi ghi identity.
- **Lý do:** Sửa finding session fixation của Phase 0.
- **Vai trò:** Authentication state dùng chung.
- **Rủi ro:** Mất cart hoặc session data khi đổi ID.
- **Cách kiểm thử:** So sánh ID trước/sau login; dùng ID cũ truy cập account;
  thêm giỏ hàng trước login rồi xác minh giỏ vẫn tồn tại sau login.
- **Kết quả:** `PASS`.

### `logout.php`

- **Mục đích:** Vô hiệu hóa phiên đầy đủ.
- **Thay đổi:** Xóa session data, xóa cookie với đúng parameters, destroy
  session rồi redirect.
- **Lý do:** Chỉ `session_destroy()` chưa xóa cookie phía client.
- **Vai trò:** Kết thúc authentication session.
- **Rủi ro:** Cookie không bị xóa do path khác lúc tạo.
- **Cách kiểm thử:** Quan sát expired cookie và truy cập lại account sau logout.
- **Kết quả:** `PASS`.

## Bằng chứng kiểm thử

| Test | Kết quả |
|---|---|
| PHP lint bốn file liên quan | PASS |
| CSRF token 64 ký tự, ổn định trong session | PASS |
| Token đúng/sai | PASS |
| Session ID trước và sau login khác nhau | PASS |
| Session ID cũ không còn authenticated | PASS |
| Cart tồn tại qua session regeneration | PASS |
| Cookie path `/aws-ecommerce-platform/` ở subpath | PASS |
| Cookie path `/` ở document root | PASS |
| `HttpOnly` và `SameSite=Lax` | PASS |
| Production HTTPS đặt `Secure=true` | PASS |
| Logout gửi expired cookie và account redirect login | PASS |
| `git diff --check` | PASS |

## Lưu ý phạm vi

Change 1 mới cung cấp CSRF helper. Việc gắn token và bắt buộc validation trên
từng customer/admin action thuộc Change 3–4; do đó behavior các form chưa bị
thay đổi ở bước này.

Upload helper sẽ được thiết kế cùng nơi sử dụng ở Change 5 để validation và
file lifecycle có test evidence trực tiếp, tránh tạo API chưa được tích hợp.

## File xóa

Không có.
