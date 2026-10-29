# Phase 3 — Change 1: Migration foundation

## Trạng thái

```text
PHASE: Phase 3 — Database & Order Integrity
CHANGE: 1 — Migration foundation và schema inventory
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## Phạm vi đã thực hiện

- Tạo `database/migrate.php` làm CLI runner cho migration dạng versioned.
- Tạo bảng `schema_migrations` bằng migration `000`.
- Runner hỗ trợ `status` và `up`, sắp xếp migration theo prefix số, ghi nhận
  version đã áp dụng và dùng MySQL advisory lock để tránh hai runner chạy đồng
  thời.
- Tạo `database/migrations/README.md` mô tả quy ước, backup, implicit commit
  của DDL và quy trình rollback an toàn.

## Evidence

- Inventory baseline trước migration: 2 users, 6 categories, 10 brands,
  17 products, 0 orders, 0 order details, 3 banners.
- Foreign key baseline được ghi nhận: order detail → order/product với
  `CASCADE`, order → user với `SET NULL`, product → category với `CASCADE`.
- Clone database được tạo bằng `mysqldump --set-gtid-purged=OFF
  --single-transaction`; lỗi GTID ở lần thử đầu đã được xử lý mà không sửa
  database phát triển.
- Trên clone: `status` báo `PENDING`, `up` áp dụng migration 000 và 001,
  chạy `up` lần hai báo `SKIP`, chứng minh idempotency của runner.
- Clone được xóa sau rehearsal; không còn database `webbanhang_phase3_test`.
- Database phát triển hiện ghi nhận migration 000 và 001 ở
  `schema_migrations`; không có migration phụ hoặc fixture còn lại.

## Kiểm tra kỹ thuật

- `php -l database/migrate.php`: PASS.
- Production guard yêu cầu đủ `DB_HOST`, `DB_NAME`, `DB_USER`,
  `DB_PASSWORD` khi `APP_ENV=production`.
- Migration runner chỉ chạy từ CLI và không tự động nạp `.env`.

## Giới hạn còn lại

Change này chưa thay đổi checkout, catalog behavior hoặc foreign key cascade.
Các thay đổi đó thuộc Change 3–6 theo kế hoạch Phase 3.

