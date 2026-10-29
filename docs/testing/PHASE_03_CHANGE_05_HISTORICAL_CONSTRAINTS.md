# Phase 3 — Change 5: Historical constraints

## Trạng thái

```text
CHANGE: 5
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## Đã triển khai

- Migration `002_finalize_historical_constraints_up.sql` đổi
  `order_details.product_name` thành `NOT NULL`.
- Đổi FK product → order detail sang `ON DELETE RESTRICT`.
- Đổi FK category → product sang `ON DELETE RESTRICT`.
- Cập nhật `database/schema.sql` để fresh schema phản ánh cột, index và FK
  cuối cùng.
- Down script chỉ phục vụ clone/backup rehearsal.

## Evidence

- Migration 002 áp dụng thành công trên database phát triển.
- FK đang hiện diện:
  - `fk_products_brand`: `SET NULL`.
  - `fk_products_category`: `RESTRICT`.
  - `fk_order_details_product`: `RESTRICT`.
- Counts không đổi: 2 users, 17 products, 0 orders, 0 order details.

