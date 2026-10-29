# Báo cáo Phase 1 — Cấu hình và tính di động

## Kết luận

```text
PHASE: Phase 1 — Configuration & Portability
TRẠNG THÁI: PASS WITH KNOWN LIMITATIONS — CHỜ HUMAN REVIEW
NGÀY: 2026-10-27
```

Phase 1 đạt gate kỹ thuật: cùng một codebase chạy được ở `/aws-ecommerce-platform` và
document root, database dùng environment configuration, lỗi production không
lộ chi tiết kỹ thuật và hard-code base path đã được loại khỏi application.

## Mục tiêu đã hoàn thành

- Tạo configuration contract cho application, database và logging.
- Tạo bootstrap dùng chung cho timezone, session và error mode.
- Chuyển database connection sang environment configuration.
- Tạo helper URL/asset/redirect cho PHP và base-path helper cho JavaScript.
- Chuyển customer/admin workflow khỏi hard-code `/aws-ecommerce-platform`.
- Chuẩn hóa URL banner và ba seed URL mà không đổi schema.
- Xác minh portability bằng hai deployment path.
- Tạo hướng dẫn cấu hình môi trường bằng tiếng Việt.

## File tạo mới

- `.env.example`, `.gitignore`.
- `config/app.php`, `config/database.php`, `config/logging.php`.
- `includes/bootstrap.php`.
- `storage/logs/.gitignore`.
- `docs/configuration/CAU_HINH_MOI_TRUONG.md`.
- Các báo cáo Change 1–6 và báo cáo tổng Phase 1 trong `docs/testing/`.

## File chỉnh sửa

- Hạ tầng dùng chung: `includes/db.php`, `includes/functions.php`,
  `includes/header.php`, `includes/footer.php`, `assets/js/main.js`.
- Customer workflow: `index.php`, `products.php`, `product_detail.php`,
  `cart.php`, `checkout.php`, `success.php`, `login.php`, `register.php`,
  `logout.php`, `account.php`.
- Admin workflow: `admin/admin_header.php`, `admin/admin_footer.php`,
  `admin/categories.php`, `admin/brands.php`, `admin/products.php`,
  `admin/banners.php`, `admin/orders.php`.
- Seed data: `database/schema.sql` — chỉ ba banner URL.

Không có file bị xóa hoặc di chuyển.

## Bằng chứng kiểm thử

| Nhóm kiểm thử | `/aws-ecommerce-platform` | Document root |
|---|---:|---:|
| Application start, homepage, CSS, JavaScript | PASS | PASS |
| Register, login, logout | PASS | PASS |
| Browse, filter, detail | PASS | PASS |
| Cart add, update, remove | PASS | PASS |
| Checkout và trang xác nhận | PASS | PASS |
| Cập nhật tài khoản | PASS | PASS |
| Admin login và dashboard | PASS | PASS |
| Admin category/brand/product/banner CRUD | PASS | PASS |
| Xem đơn và cập nhật trạng thái | PASS | PASS |
| Dọn dữ liệu thử nghiệm | PASS | PASS |

Kiểm tra bổ sung:

- PHP lint toàn repository: `PASS`.
- JavaScript syntax check: `PASS`.
- Production thiếu/sai DB config chỉ trả thông báo chung, chi tiết vào log:
  `PASS`.
- Search `/aws-ecommerce-platform`: chỉ còn local default được phép trong
  `config/app.php`: `PASS`.
- Secrets scan: `PASS`.
- `git diff --check`: `PASS`.
- Database sau cleanup: 2 user gốc, 17 sản phẩm, 0 order, 0 order detail;
  tồn kho sản phẩm kiểm tra trở về 50.
- Không ghi nhận PHP fatal, warning, deprecated hoặc SQLSTATE trong response
  của vòng regression cuối.

## Vấn đề đã xử lý

- Database credential không còn nằm trực tiếp trong connection logic.
- URL không còn phụ thuộc cố định vào tên thư mục `/aws-ecommerce-platform`.
- Production không hiển thị technical database error cho user.
- Session, timezone và error mode có một bootstrap chung.
- Runtime log và `.env` được loại khỏi Git.

## Giới hạn và rủi ro còn lại

- Regression hiện là kiểm thử HTTP/CLI thủ công, chưa có automated test suite
  được commit.
- Chưa thực hiện visual browser QA tự động; xác minh tập trung vào HTTP,
  redirect, response marker, asset và workflow dữ liệu.
- Các finding về IDOR, CSRF, state-changing GET, session hardening, upload và
  checkout race condition vẫn giữ nguyên, thuộc Phase 2–3.
- `APP_URL` là metadata cấu hình; internal URL hiện dùng relative base path.
- Project không tự đọc `.env`; runtime phải cung cấp environment variables.

Không có blocker kỹ thuật cho gate Phase 1.

## Gate tiếp theo

Phase 1 đang chờ human review. Không bắt đầu Phase 2 trước khi người dùng duyệt
báo cáo và diff của Phase 1.
