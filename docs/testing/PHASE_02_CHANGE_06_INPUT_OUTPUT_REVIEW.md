# Phase 2 — Báo cáo Change 6: Input, output và authentication review

## Trạng thái

```text
CHANGE: Change 6 — Input, output và authentication review
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## Thay đổi chính

- `includes/security.php`: thêm integer range validator và kiểm tra URL nội
  dung chỉ cho phép internal path, HTTP(S), protocol-relative hoặc `#`.
- `ajax/cart_action.php`: allowlist action; ID/số lượng phải hợp lệ; số lượng
  từ 1–99 và không vượt tồn kho; lỗi trả HTTP 400 JSON.
- `login.php`, `register.php`, `account.php`, `checkout.php`: kiểm tra email,
  độ dài field quan trọng, password length và payment method enum.
- `admin/products.php`: kiểm tra range giá/tồn kho, category và brand tồn tại.
- `admin/categories.php`, `admin/brands.php`: kiểm tra ID và độ dài field.
- `admin/banners.php`: kiểm tra ID, sort order, độ dài và URL scheme.
- `admin/orders.php`: ID dương và status enum strict.

## Bằng chứng kiểm thử

| Probe | Kết quả |
|---|---|
| Cart quantity `-5` và `999` | PASS — HTTP 400 |
| Cart action không thuộc allowlist | PASS — HTTP 400 |
| Cart quantity vượt stock | PASS — HTTP 400 |
| Cart hợp lệ | PASS — HTTP 200 |
| Payment method `javascript:alert(1)` | PASS — không tạo order/giảm stock |
| Product sale price > price, stock âm | PASS — không tạo record |
| Category/brand reference không tồn tại | PASS — không tạo record |
| Banner link `javascript:` | PASS — không tạo record |
| Reflected XSS trong search | PASS — raw script 0, escaped value 2 |
| Login email tồn tại và không tồn tại | PASS — cùng thông báo chung |
| Test records sau kiểm thử | PASS — 0 |
| PHP lint, JavaScript syntax, diff check | PASS |

## Login throttling

Không triển khai throttling chỉ dựa trên session vì có thể bỏ qua bằng cách xóa
cookie và tạo session mới. Throttling bền vững cần datastore/schema hoặc lớp
edge phù hợp; việc này không được giả vờ là đã bảo vệ và sẽ được ghi vào rủi ro
vận hành/deployment sau Phase 2.

## Giới hạn phạm vi

Checkout transaction, stock race condition và việc đọc lại authoritative price
trong transaction thuộc Phase 3, không được thay đổi ở Change 6.

## File xóa

Không có.
