# Phase 2 — Báo cáo Change 7: Security regression

## Trạng thái

```text
CHANGE: Change 7 — Security regression và báo cáo
TRẠNG THÁI: PASS WITH KNOWN LIMITATIONS
NGÀY: 2026-10-27
```

## Regression ở hai deployment path

| Workflow | `/aws-ecommerce-platform` | Document root |
|---|---:|---:|
| Homepage, CSS, JavaScript | PASS | PASS |
| Browse/filter/product detail | PASS | PASS |
| Register và customer login | PASS | PASS |
| Cart add/update/remove | PASS | PASS |
| Account update | PASS | PASS |
| Checkout và owned success page | PASS | PASS |
| Admin login và dashboard | PASS | PASS |
| Admin category/brand CRUD | PASS | PASS |
| Admin product/banner CRUD có upload | PASS | PASS |
| Admin order status update | PASS | PASS |
| Customer/admin logout POST | PASS | PASS |
| Cleanup database và uploaded files | PASS | PASS |

## Negative security regression

| Finding/probe | Kết quả |
|---|---|
| Anonymous đọc order | PASS — redirect login, không có body dữ liệu |
| Customer đọc order user khác | PASS — bị từ chối như ID không tồn tại |
| State-changing GET delete/toggle | PASS — không thay đổi dữ liệu |
| POST/AJAX thiếu hoặc sai CSRF | PASS — HTTP 403 |
| Cart GET và logout GET | PASS — HTTP 405 |
| Logout POST thiếu token | PASS — HTTP 403, session vẫn còn |
| Logout POST token đúng | PASS — session bị hủy |
| Session fixation | PASS — ID đổi, ID cũ mất quyền |
| PHP giả ảnh/client MIME giả | PASS — bị từ chối |
| Filename traversal/executable extension | PASS — server sinh tên + extension ảnh |
| Quantity/range/enum/reference sai | PASS — bị từ chối, dữ liệu không đổi |
| Reflected XSS search | PASS — output được escape |
| Login account enumeration qua message | PASS — cùng thông báo chung |

## Kiểm tra kỹ thuật cuối

- PHP lint toàn repository: `PASS`.
- JavaScript syntax: `PASS`.
- `git diff --check`: `PASS`.
- Không còn `?delete` hoặc `?toggle` trong PHP: `PASS`.
- Không còn code tin client MIME/original extension: `PASS`.
- Secrets scan: `PASS`.
- Không ghi nhận fatal/warning/deprecated/SQLSTATE trong log server regression.
- Runtime log directory chỉ còn `.gitignore`.

## Cleanup cuối

```text
Users: 2
Products: 17
Orders: 0
Order details: 0
Product #1 stock: 50
Phase 2 category fixtures: 0
Phase 2 brand fixtures: 0
Phase 2 banner fixtures: 0
```

Không còn product/banner/avatar file được tạo bởi Phase 2 regression.

## Known limitations

- Chưa có automated test suite được commit; evidence đến từ HTTP/CLI regression
  có kiểm soát.
- Chưa có visual browser QA tự động.
- Login throttling chưa triển khai vì phương án session-only dễ bị né; cần
  datastore hoặc edge control phù hợp ở phase triển khai/vận hành.
- Runtime uploads vẫn ở local public filesystem; việc tách storage thuộc Phase 4.
- Checkout concurrency, authoritative price/stock revalidation và foreign key
  integrity thuộc Phase 3.
