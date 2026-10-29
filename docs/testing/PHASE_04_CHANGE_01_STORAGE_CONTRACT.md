# Phase 4 — Change 1: Storage configuration và contract

## Trạng thái

```text
PHASE: Phase 4 — Storage Abstraction
CHANGE: 1
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## Đã triển khai

- Tạo `config/storage.php` với cấu hình local và S3 từ environment.
- Mặc định driver là `local`, root là `storage/uploads/`.
- Tạo `StorageInterface` cho put/read/url/exists/delete.
- Tạo `StoredObject` value object chứa key, size và MIME type.
- Bootstrap nạp storage configuration cùng app/database/logging config.

## An toàn và phạm vi

- Chưa di chuyển file hiện có.
- Chưa sửa product/banner/avatar upload workflow.
- Không có AWS credential hoặc secret trong source.
- S3 mới ở configuration boundary; adapter thuộc Change 5.

## Kiểm tra

- PHP lint toàn bộ source: PASS.
- Existing uploads không bị thay đổi.

