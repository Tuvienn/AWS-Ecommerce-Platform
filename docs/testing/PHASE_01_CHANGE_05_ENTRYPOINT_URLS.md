# Phase 1 — Báo cáo Change 5: URL trong các entry point

## Trạng thái

```text
CHANGE: Change 5 — Chuyển URL và redirect của các workflow sang helper
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## Thay đổi theo file

| File | Mục đích và thay đổi | Vai trò | Rủi ro | Cách kiểm thử | Kết quả |
|---|---|---|---|---|---|
| `login.php` | Dùng `appUrl()` cho link và redirect sau đăng nhập | Xác thực khách hàng/admin | Redirect sai hoặc lặp | Đăng nhập hai vai trò, kiểm tra HTTP 302 và `Location` | PASS |
| `register.php` | Dùng helper cho redirect và link đăng nhập | Đăng ký khách hàng | Mất luồng sau đăng ký | Đăng ký tài khoản thử nghiệm và theo dõi redirect | PASS |
| `logout.php` | Load helper và dùng `redirectTo()` | Kết thúc phiên | Không hủy phiên hoặc sai đích | Đăng nhập, đăng xuất ở hai base path | PASS |
| `account.php` | Chuyển navigation, avatar và redirect sang helper | Hồ sơ và lịch sử đơn | Link/ảnh sai prefix | Cập nhật hồ sơ và kiểm tra tài nguyên | PASS |
| `index.php` | Chuyển link sản phẩm, danh mục và ảnh sang helper | Trang chủ | Link hoặc ảnh bị double-prefix | Smoke test trang chủ, link và asset | PASS |
| `products.php` | Chuyển filter, detail và ảnh sang helper | Danh sách sản phẩm | Mất query filter | Test danh mục, thương hiệu và trang chi tiết | PASS |
| `product_detail.php` | Chuyển breadcrumb, form và ảnh sang helper | Chi tiết sản phẩm | Form giỏ hàng sai endpoint | Mở chi tiết và thêm vào giỏ | PASS |
| `cart.php` | Chuyển link tiếp tục mua và checkout sang helper | Giỏ hàng | Sai luồng mua hàng | Thêm, cập nhật, xóa và đi checkout | PASS |
| `checkout.php` | Chuyển redirect/link sang helper | Tạo đơn hàng | Redirect xác nhận sai | Checkout và kiểm tra HTTP 302 | PASS |
| `success.php` | Chuyển link quay lại và link đơn hàng sang helper | Xác nhận đơn | Không mở được kết quả | Mở đúng URL sau checkout | PASS |
| `admin/categories.php` | Chuyển action/link admin sang helper | CRUD danh mục | Action sai base path | Tạo rồi dọn dữ liệu thử nghiệm | PASS |
| `admin/brands.php` | Chuyển action/link admin sang helper | CRUD thương hiệu | Action sai base path | Tạo rồi dọn dữ liệu thử nghiệm | PASS |
| `admin/orders.php` | Chuyển action và link chi tiết sang helper | Quản lý đơn | Sai link hoặc status action | Mở đơn và cập nhật trạng thái | PASS |
| `admin/products.php` | Chuyển action, ảnh và endpoint inline JavaScript sang helper | CRUD sản phẩm | Upload/fetch sai endpoint | Tạo sản phẩm, kiểm tra fetch và dọn dữ liệu | PASS |
| `admin/banners.php` | Chuyển action và ảnh sang helper | CRUD banner | Preview hoặc action sai | Tạo banner thử nghiệm và dọn dữ liệu | PASS |

## Bằng chứng kiểm thử

- PHP lint toàn bộ file thay đổi: `PASS`.
- Hai cấu hình `APP_BASE_PATH=/aws-ecommerce-platform` và `APP_BASE_PATH=`: `PASS`.
- Customer workflow từ đăng ký đến checkout và cập nhật hồ sơ: `PASS`.
- Admin login, dashboard, CRUD và cập nhật đơn: `PASS`.
- Tìm kiếm hard-code `/aws-ecommerce-platform` trong entry point: không còn kết quả.
- Dữ liệu thử nghiệm đã được dọn sau kiểm thử.

## File xóa

Không có.
