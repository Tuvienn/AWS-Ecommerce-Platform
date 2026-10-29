# Hướng dẫn migration database

## Nguyên tắc

- Chỉ chạy migration bằng PHP CLI.
- File được đặt tên `<version>_<mô_tả>_up.sql` và áp dụng theo thứ tự tên.
- Version đã chạy được ghi trong `schema_migrations` và không chạy lại.
- Không sửa một migration đã áp dụng; tạo version mới để sửa tiếp.
- Luôn dump/backup database trước migration có `ALTER TABLE`.
- Không chạy migration production trong Phase 3.

MySQL DDL có thể implicit commit. Migration runner không tuyên bố rằng nhiều
`ALTER TABLE` được rollback nguyên tử. Nếu migration dừng giữa chừng, phải đọc
schema thực tế, khôi phục backup hoặc hoàn tất recovery có kiểm soát trước khi
chạy lại.

## Cấu hình

Runner đọc cùng các biến `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` và
`DB_PASSWORD` như application. File `.env` không được tự động load.

Ví dụ kiểm tra database clone:

```bash
DB_NAME=webbanhang_phase3_test php database/migrate.php status
DB_NAME=webbanhang_phase3_test php database/migrate.php up
```

Khi `APP_ENV=production`, toàn bộ biến database bắt buộc phải được cung cấp và
password không được rỗng.

## Quy trình bắt buộc

1. Ghi schema inventory và row count.
2. Tạo backup hoặc database clone.
3. Chạy preflight query của migration.
4. Chạy `status` và review danh sách `PENDING`.
5. Chạy `up` trên clone.
6. Kiểm tra column, index, foreign key, row count và dữ liệu backfill.
7. Chạy `up` lần hai để xác minh mọi version được `SKIP`.
8. Chỉ đề xuất áp dụng lên môi trường thật sau khi report được duyệt.

## Rollback

File `_down.sql` chỉ dùng cho rehearsal trên clone khi tài liệu của migration
xác nhận chưa có dữ liệu mới phụ thuộc schema đó. Runner không tự chạy down để
tránh thao tác phá hủy ngoài ý muốn.

Với migration đã nhận dữ liệu mới hoặc thay đổi constraint, rollback chính
thức là:

1. Dừng application version mới.
2. Khôi phục database backup đã xác minh.
3. Khôi phục application version tương thích.
4. Chạy lại smoke/regression test.

Không dùng down migration trên production chỉ vì file đó tồn tại.
