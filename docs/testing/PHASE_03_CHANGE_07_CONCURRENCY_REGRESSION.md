# Phase 3 — Change 7: Concurrency và regression

## Trạng thái

```text
CHANGE: 7
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## Kiểm thử concurrency

Harness: `tests/integration/phase3_concurrency.php`.

- Fixture stock = 1.
- Hai worker checkout đồng thời cùng một product.
- Kết quả: một order thành công, một worker nhận lỗi thiếu tồn kho.
- Final stock trước cleanup = 0.
- Committed orders = 1.
- Fixture được cleanup và stock trả về baseline.

## Regression đã chạy

- PHP lint toàn bộ source: PASS.
- Migration status: 000, 001, 002 đều `APPLIED`.
- Database counts và stock baseline: PASS.
- Checkout snapshot, cancel restore và archive/restore: PASS.

## Final QA

HTTP/browser regression đầy đủ ở cả hai base path đã được người dùng xác nhận
hoàn tất. Không có fixture hoặc database phụ còn tồn tại.

