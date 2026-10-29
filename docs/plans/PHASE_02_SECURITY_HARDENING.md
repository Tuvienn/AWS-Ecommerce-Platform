# Kế hoạch Phase 2 — Tăng cường bảo mật

## 1. Trạng thái

```text
PHASE: Phase 2 — Security Hardening
TRẠNG THÁI KẾ HOẠCH: ĐÃ PHÊ DUYỆT
TRẠNG THÁI IMPLEMENTATION: PASS WITH KNOWN LIMITATIONS — CHỜ HUMAN REVIEW
NGÀY LẬP: 2026-10-27
```

Implementation Change 1–7 đã hoàn thành. Bằng chứng tổng hợp được ghi tại
`docs/testing/PHASE_02_SECURITY_HARDENING.md`.

## 2. Điều kiện đầu vào

- Phase 0: `PASS WITH KNOWN LIMITATIONS`.
- Phase 1: `PASS`, đã được người dùng duyệt.
- Cùng codebase đã chạy thành công ở `/aws-ecommerce-platform` và document root.
- Các finding bảo mật từ Phase 0 đã có runtime evidence.

## 3. Mục tiêu

Xử lý các finding `HIGH` và `MEDIUM` thuộc phạm vi Phase 2 mà không thay đổi
nghiệp vụ bán hàng, database schema hoặc kiến trúc storage:

1. Chặn truy cập đơn hàng không thuộc user hiện tại.
2. Bảo vệ mọi state-changing request bằng CSRF token.
3. Chuyển các thao tác thay đổi dữ liệu từ GET sang POST.
4. Tăng cường session và quy trình login/logout.
5. Kiểm tra upload dựa trên nội dung thật, giới hạn kích thước và tên do
   server sinh.
6. Siết input validation và output escaping tại các bề mặt quan trọng.
7. Giữ nguyên customer/admin workflow và URL portability của Phase 1.

## 4. Phạm vi được phép

- Authentication, authorization và ownership.
- CSRF helper, token field và validation.
- HTTP method safety.
- Session cookie configuration, session regeneration và logout invalidation.
- Validation cho avatar, product image và banner image.
- Validation các enum/ID/số lượng/giá trị quan trọng.
- Review output escaping và URL nội dung.
- Basic login throttling nếu có thể thực hiện an toàn mà không đổi schema.
- Security regression và tài liệu bằng tiếng Việt.

## 5. Ngoài phạm vi

- Không sửa checkout transaction/race condition; thuộc Phase 3.
- Không thay đổi foreign key hoặc database schema; thuộc Phase 3.
- Không di chuyển runtime upload sang storage abstraction/S3; thuộc Phase 4.
- Không di chuyển entry point vào `public/`.
- Không tạo AWS resource hoặc deployment configuration.
- Không thay đổi UI/CSS ngoài phần bắt buộc để đổi link hành động thành form
  POST và thêm CSRF hidden field.
- Không thêm framework hoặc package ngoài.

## 6. Security baseline cần giữ

| Finding | Mức độ | Evidence Phase 0 | Target Phase 2 |
|---|---|---|---|
| Order ownership/IDOR ở `success.php` | HIGH | Request không xác thực đọc được đơn theo ID | Bắt buộc login và query theo `id + user_id` |
| Thiếu CSRF | HIGH | Profile/admin POST không token vẫn thành công | Mọi state-changing request cần token hợp lệ |
| State-changing GET | HIGH | Delete/toggle admin chạy qua GET | GET không còn thay đổi dữ liệu |
| Session fixation | MEDIUM | Session ID không đổi sau login | Regenerate ID sau login |
| Upload validation yếu | HIGH | Static audit; chưa thử executable payload | Validate nội dung thật, size, tên và extension server-side |

## 7. Quyết định thiết kế

### 7.1 CSRF dùng session

Thêm helper dùng token ngẫu nhiên từ `random_bytes()`:

```text
csrfToken()
csrfField()
verifyCsrfToken()
requireValidCsrfToken()
```

- Token lưu trong session, so sánh bằng `hash_equals()`.
- Token chỉ xuất hiện trong hidden field hoặc AJAX request body/header.
- Validation chạy trước mọi thay đổi dữ liệu.
- Request sai token trả HTTP 403 hoặc response JSON tương ứng.
- Không log token.

### 7.2 Ownership ở query

Customer chỉ đọc resource bằng điều kiện ownership ở database:

```sql
WHERE id = ? AND user_id = ?
```

Không fetch theo ID rồi mới tin dữ liệu từ browser. Admin vẫn được xem toàn bộ
đơn qua route admin đã có server-side role check.

### 7.3 HTTP method safety

- GET chỉ dùng để xem danh sách, xem chi tiết và mở form edit.
- Delete/toggle/update/create dùng POST.
- Nút delete/toggle hiện tại được đổi từ link sang form POST nhỏ.
- Server từ chối GET cũ; không duy trì compatibility làm thay đổi state.

### 7.4 Session hardening

Trước `session_start()`:

- Bật `session.use_strict_mode`.
- Cookie `HttpOnly`, `SameSite=Lax` và path phù hợp `APP_BASE_PATH`.
- `Secure` khi production URL dùng HTTPS.
- Regenerate session ID sau login thành công.
- Logout xóa session data và session cookie trước redirect.

### 7.5 Upload validation dùng chung

Tạo helper tối thiểu dùng `finfo`, `getimagesize()` và `random_bytes()`:

- Xác minh upload error và `is_uploaded_file()`.
- Giới hạn kích thước theo từng loại.
- Chỉ cho phép MIME ảnh đã duyệt; extension suy ra từ MIME server-side.
- Tạo filename ngẫu nhiên, không dùng original filename.
- Không cho phép path traversal hoặc xóa file ngoài thư mục cho phép.
- Chặn PHP execution trong thư mục upload bằng cấu hình phù hợp với Apache;
  validation nội dung vẫn là lớp bảo vệ chính.

Phase 2 giữ location hiện tại để tránh lấn sang Phase 4. Việc tách runtime
uploads khỏi public filesystem sẽ được xử lý ở Storage Abstraction.

## 8. Trình tự implementation

### Change 1 — Security helpers và session hardening

**WHAT**

- Thêm CSRF helper, method guard và helper upload dùng chung.
- Cấu hình session cookie trước khi khởi tạo session.
- Regenerate session ID khi login; logout hủy session/cookie đầy đủ.

**FILES AFFECTED**

- `includes/bootstrap.php`.
- `includes/functions.php`.
- `login.php`, `logout.php`.
- Có thể tạo `includes/security.php` nếu giúp tách trách nhiệm rõ ràng.

**RISKS**

- Mất cart/session khi regenerate không đúng cách.
- Cookie path làm login không hoạt động ở một trong hai deployment path.

**TEST PLAN**

- Session ID phải đổi sau login nhưng cart/session data còn đúng.
- Logout làm session cũ không dùng lại được.
- Cookie flags ở local và production HTTPS configuration.
- CSRF helper sinh token ổn định trong session và từ chối token sai.

### Change 2 — Order ownership và authorization regression

**WHAT**

- `success.php` yêu cầu authenticated user.
- Query đơn hàng theo cả `order_id` và `user_id`.
- Response khi không sở hữu không tiết lộ đơn có tồn tại hay không.
- Re-test server-side admin authorization.

**FILES AFFECTED**

- `success.php`.
- Chỉ chỉnh file khác nếu audit phát hiện resource ownership tương tự.

**TEST PLAN**

- Owner đọc được đơn của mình.
- User khác và anonymous không đọc được dữ liệu đơn.
- ID không tồn tại có behavior chung, không leak dữ liệu.
- Admin dashboard vẫn được bảo vệ server-side.

### Change 3 — CSRF cho customer workflow và cart AJAX

**WHAT**

- Thêm token và validation cho login, register, account update, password,
  avatar, checkout và cart AJAX.
- JavaScript gửi CSRF token cùng add/update/remove cart.
- Request thiếu/sai token không thay đổi state.

**FILES AFFECTED**

- `includes/header.php`.
- `assets/js/main.js`.
- `login.php`, `register.php`, `account.php`, `checkout.php`.
- `product_detail.php`, `cart.php`, `ajax/cart_action.php`.

**TEST PLAN**

- Token hợp lệ giữ nguyên toàn bộ workflow.
- Thiếu/sai token trả 403 hoặc JSON failure và dữ liệu không đổi.
- Token không xuất hiện trong URL hoặc log.
- Test ở cả subpath và document root.

### Change 4 — Admin CSRF và loại bỏ state-changing GET

**WHAT**

- Bảo vệ toàn bộ create/update/delete/toggle/status action bằng CSRF.
- Chuyển delete/toggle từ GET sang POST.
- Giữ GET `edit`/`view` chỉ để đọc.
- Bảo vệ AJAX thêm brand trong trang product.

**FILES AFFECTED**

- `admin/admin_header.php` nếu cần token chung.
- `admin/products.php`, `admin/categories.php`, `admin/brands.php`.
- `admin/banners.php`, `admin/orders.php`.
- `assets/js/main.js` hoặc inline JavaScript admin liên quan.

**TEST PLAN**

- GET delete/toggle cũ không thay đổi dữ liệu.
- POST thiếu/sai token bị từ chối.
- Admin CRUD/status với token hợp lệ vẫn PASS.
- Customer không thể gọi admin action.

### Change 5 — Upload security

**WHAT**

- Áp dụng upload helper cho avatar, product và banner.
- Validate actual MIME/content, size và upload error.
- Sinh filename/extension server-side.
- Xóa file cũ chỉ sau khi xác minh basename và phạm vi thư mục.
- Thêm lớp chặn thực thi tại các thư mục nhận upload phù hợp runtime hiện tại.

**FILES AFFECTED**

- `account.php`, `admin/products.php`, `admin/banners.php`.
- `includes/security.php` hoặc `includes/functions.php`.
- File cấu hình bảo vệ thư mục upload nếu cần.

**TEST PLAN**

- JPG/PNG/WEBP/GIF hợp lệ theo allowlist.
- File PHP đổi đuôi ảnh, MIME giả, ảnh lỗi và file vượt size bị từ chối.
- Original filename chứa path traversal không ảnh hưởng path đích.
- Filename lưu database do server sinh.
- Không ghi/xóa file ngoài thư mục được phép.
- Upload hợp lệ vẫn hiển thị ở hai base path.

### Change 6 — Input, output và authentication review

**WHAT**

- Audit enum, ID, quantity, numeric range, email, payment method và banner URL.
- Bảo đảm output động được escape theo context HTML/attribute/JavaScript.
- Đánh giá basic login throttling không đổi schema; chỉ triển khai nếu behavior
  xác định được và có test ổn định.

**FILES AFFECTED**

- Các entry point nhận input tương ứng; chỉ sửa khi có finding cụ thể.
- Không sửa transaction checkout thuộc Phase 3.

**TEST PLAN**

- Invalid enum/negative ID/quantity/range không thay đổi state.
- XSS probes được escape.
- URL banner nguy hiểm không tạo executable scheme.
- Login error không tiết lộ user tồn tại.

### Change 7 — Security regression và báo cáo

**WHAT**

- Chạy lại Phase 1 regression ở hai base path.
- Chạy negative security tests cho từng finding.
- Dọn toàn bộ test record/file và lập báo cáo Phase 2.

**TEST PLAN**

- PHP lint, JavaScript syntax và `git diff --check`.
- Customer/admin workflow đầy đủ ở `/aws-ecommerce-platform` và document root.
- IDOR, CSRF, method safety, session fixation và malicious upload tests.
- Scan response/log cho secret, warning, fatal, deprecated và SQLSTATE.
- Xác minh database/file counts sau cleanup.

## 9. File dự kiến tạo mới

- `includes/security.php` nếu review Change 1 xác nhận cần tách helper.
- File bảo vệ thư mục upload phù hợp với web server hiện tại nếu cần.
- `docs/testing/PHASE_02_CHANGE_XX_*.md` cho từng change.
- `docs/testing/PHASE_02_SECURITY_HARDENING.md`.

Mọi file Markdown mới đều viết bằng tiếng Việt.

## 10. Rủi ro tổng thể

| Rủi ro | Mức độ | Giảm thiểu |
|---|---|---|
| CSRF làm hỏng form/AJAX hiện tại | HIGH | Triển khai theo nhóm, test positive và negative |
| Session cookie path làm mất login/cart | HIGH | Test cả hai base path sau Change 1 |
| Xóa nhầm file upload | HIGH | Canonical path/basename guard, chỉ xóa trong allowlisted directory |
| Upload giả mạo vượt validation | HIGH | `finfo` + image decode check + server filename |
| Đổi GET sang POST làm hỏng UI admin | MEDIUM | Form POST nhỏ, giữ nguyên style và regression CRUD |
| Lấn sang Phase 3/4 | MEDIUM | Không đổi schema, transaction hoặc storage architecture |

## 11. Tiêu chí hoàn thành

- IDOR order ownership test `PASS`.
- Mọi state-changing request trong scope có CSRF protection.
- GET không còn tạo/cập nhật/xóa/toggle dữ liệu.
- Session ID được regenerate sau login; logout invalidates session.
- Cookie flags phù hợp environment và base path.
- Upload giả mạo/executable/oversized/path traversal bị từ chối.
- Admin authorization server-side vẫn `PASS`.
- Customer/admin regression ở hai base path `PASS`.
- Không đổi database schema, checkout integrity hoặc storage architecture.
- Báo cáo tiếng Việt hoàn chỉnh và không còn test artifact.
- Human review hoàn tất trước Phase 3.

## 12. Approval gate

Kế hoạch đã được người dùng phê duyệt và Change 1–7 đã hoàn thành. Phase 2 đang
chờ human review trước khi bắt đầu Phase 3.
