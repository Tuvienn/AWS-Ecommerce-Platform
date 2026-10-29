# Phase 3 — Change 2: Additive integrity migration

## Trạng thái

```text
PHASE: Phase 3 — Database & Order Integrity
CHANGE: 2 — Additive integrity migration
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## Phạm vi đã thực hiện

- Thêm migration `001_additive_order_catalog_integrity_up.sql` theo hướng
  additive, không xóa cột legacy và không đổi cascade hiện tại.
- Thêm `products.brand_id`, `products.is_active`, `products.archived_at`.
- Thêm `order_details.product_name` để chuẩn bị snapshot tên sản phẩm.
- Backfill `brand_id` theo tên brand và backfill `product_name` theo product
  hiện hữu.
- Thêm foreign key `products.brand_id → brands.id ON DELETE SET NULL`.
- Thêm index cho catalog active và truy vấn orders theo user/status.
- Tạo down script chỉ dùng cho clone rehearsal; không dùng để rollback DDL trên
  production.

## Evidence trên clone

- Preflight mismatch: product có brand nhưng không map được brand = 0; orphan
  order detail = 0.
- Down rehearsal xóa đúng phần additive; chạy lại `up` áp dụng thành công.
- Fixture order/detail được tạo để kiểm tra backfill; `product_name` nhận đúng
  `iPhone 15 Pro Max 256GB`, price giữ nguyên 32.990.000.
- Sau backfill: product có brand nhưng `brand_id IS NULL` = 0; product inactive
  hoặc archived = 0.
- Foreign key có `DELETE_RULE = SET NULL`; các index mới hiện diện đúng cột.
- Fixture đã xóa và clone đã được drop hoàn toàn.

## Evidence trên database phát triển

- Migration 000 và 001 ở trạng thái `APPLIED`; chạy lại runner báo `SKIP`.
- Counts giữ nguyên: 2 users, 17 products, 0 orders, 0 order details.
- Stock sản phẩm kiểm tra vẫn là 50.
- Truy vấn PDO hiện tại vẫn đọc được 17 products.
- Không sửa code business hoặc checkout trong Change 2.

## Ghi chú thiết kế

`database/schema.sql` chưa được đổi thành schema cuối cùng; việc đồng bộ fresh
schema với upgraded schema thuộc Change 5 sau khi các quyết định constraint
được duyệt. Change 2 chỉ cung cấp nền additive an toàn cho Change 3 trở đi.

