# Phase 2 — Báo cáo Change 4: Admin CSRF và HTTP method safety

## Trạng thái

```text
CHANGE: Change 4 — Admin CSRF và loại bỏ state-changing GET
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## File và thay đổi

- `admin/admin_header.php`: truyền CSRF token cho JavaScript admin.
- `admin/products.php`: bảo vệ create/update/delete và AJAX thêm brand; delete
  chuyển từ GET sang POST form.
- `admin/categories.php`: bảo vệ create/update/delete; delete chuyển sang POST.
- `admin/brands.php`: bảo vệ create/update/delete; delete chuyển sang POST.
- `admin/banners.php`: bảo vệ create/update/delete/toggle; delete và toggle
  chuyển sang POST form.
- `admin/orders.php`: bảo vệ cập nhật trạng thái đơn bằng CSRF.

Các trang xử lý authentication/action trước khi render header để response 403
và JSON error không bị trộn với HTML.

## Bằng chứng kiểm thử

| Test | Kết quả |
|---|---|
| Admin form token dài 64 ký tự | PASS |
| Create category thiếu token | PASS — HTTP 403, database không đổi |
| Create category token đúng | PASS — HTTP 302 |
| GET delete category/product | PASS — HTTP 200, không xóa record |
| Product delete thiếu token | PASS — HTTP 403, record còn nguyên |
| Product delete token đúng | PASS — HTTP 302, record bị xóa |
| AJAX add brand thiếu token | PASS — HTTP 403 JSON |
| AJAX add brand token đúng | PASS — HTTP 200 JSON |
| GET banner toggle | PASS — không đổi trạng thái |
| POST banner toggle thiếu token | PASS — HTTP 403, không đổi trạng thái |
| POST banner toggle token đúng | PASS — đổi trạng thái và redirect |
| Order status thiếu token | PASS — vẫn `pending` |
| Order status token đúng | PASS — thành `confirmed` |
| PHP lint và JavaScript syntax | PASS |
| `git diff --check` | PASS |

Banner gốc đã được khôi phục `is_active=1`. Category, brand, product và order
fixture đều đã được xóa; truy vấn cleanup trả về `0` cho từng nhóm.

## Vấn đề đã sửa

Finding `HIGH — State-changing GET` đã được loại bỏ khỏi các admin action đã
biết. Admin POST/AJAX trong scope đều yêu cầu CSRF token server-side.

## File xóa

Không có.
