# Phase 4 — Change 5: S3-ready adapter

## Trạng thái

```text
CHANGE: 5
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## File thay đổi

### `config/storage.php`, `.env.example`

```text
PURPOSE: Tách S3 configuration khỏi source code.
WHAT CHANGED: Thêm STORAGE_DRIVER, AWS_REGION, S3_BUCKET, S3_PREFIX, S3_PRIVATE.
WHY: Local và AWS dùng cùng codebase nhưng khác environment config.
ROLE IN SYSTEM: Storage configuration boundary.
RISK: Thiếu config S3 có thể làm upload thất bại.
HOW TO TEST: Khởi tạo STORAGE_DRIVER=s3 không có AWS SDK.
RESULT: Fail-safe message rõ ràng, không có credential trong repository.
```

### `app/Storage/S3Storage.php`, `app/Storage/StorageFactory.php`

```text
PURPOSE: Boundary cho S3 driver.
WHAT CHANGED: Factory chọn local/s3; S3 xác nhận region/bucket và AWS SDK trước khi hoạt động.
WHY: Không cài dependency AWS hoặc public bucket khi chưa được phê duyệt.
ROLE IN SYSTEM: S3-ready adapter.
RISK: S3 chưa hoạt động nếu chưa cài SDK được phê duyệt.
HOW TO TEST: STORAGE_DRIVER=s3 với bucket/region test.
RESULT: Driver từ chối an toàn khi AWS SDK chưa được cài.
```

