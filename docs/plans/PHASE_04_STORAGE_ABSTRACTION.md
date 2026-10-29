# Kế hoạch Phase 4 — Storage Abstraction

## 1. Trạng thái

```text
PHASE: Phase 4 — Storage Abstraction
TRẠNG THÁI KẾ HOẠCH: ĐÃ PHÊ DUYỆT
TRẠNG THÁI IMPLEMENTATION: PASS WITH KNOWN LIMITATION — HUMAN REVIEW APPROVED
NGÀY LẬP: 2026-10-28
```

Phase 3 đã được người dùng duyệt. Change 1–5 đã hoàn tất; Change 6 đã migrate
an toàn mọi legacy file còn tồn tại và ghi nhận seed image bị thiếu từ trước.
Evidence nằm trong `docs/testing/`.

## 2. Mục tiêu

Tách runtime uploads khỏi public filesystem, giữ local workflow ổn định và tạo
contract để local storage và Amazon S3 dùng cùng application code.

```text
Application
     ↓
StorageInterface
   /            \
LocalStorage   S3Storage
```

## 3. Ràng buộc bắt buộc

- Static assets giữ trong `assets/` trong giai đoạn hiện tại; runtime uploads
  mới dùng `storage/uploads/` ở local.
- Không làm mất avatar, product image hoặc banner hiện có.
- Không lưu URL environment-specific trong business logic.
- Database ưu tiên lưu object key, không lưu URL cố định.
- Production S3 private, Block Public Access bật, dùng IAM role thay vì access
  key trong source.
- Upload không được phép thực thi PHP.
- Mọi file Markdown mới viết bằng tiếng Việt.

## 4. Thiết kế đề xuất

### 4.1 Contract

Tạo interface với các thao tác tối thiểu:

- `put(source, key, metadata): StoredObject`.
- `read(key): Readable resource/string`.
- `url(key): string` hoặc response strategy phù hợp driver.
- `delete(key): void`.
- `exists(key): bool`.

Application chỉ gọi contract; không gọi `move_uploaded_file`, `unlink` hoặc
S3 SDK trực tiếp trong page/controller.

### 4.2 Object key

Key chuẩn:

```text
products/YYYY/MM/<uuid>.<extension>
banners/YYYY/MM/<uuid>.<extension>
avatars/YYYY/MM/<uuid>.<extension>
```

Filename client không được dùng làm key cuối cùng. Metadata phải giữ MIME type,
size và extension đã kiểm tra.

### 4.3 Driver

- `LocalStorage`: root lấy từ config, mặc định `storage/uploads/`.
- `S3Storage`: adapter S3-ready, đọc `AWS_REGION`, `S3_BUCKET`, prefix và
  visibility từ config; không commit credentials.
- Nếu dependency AWS SDK chưa được approve, trước mắt tạo adapter contract và
  fail-safe message rõ ràng khi driver S3 chưa được cấu hình.

## 5. Trình tự implementation

### Change 1 — Storage configuration và contract

**WHAT**

- Thêm `STORAGE_DRIVER`, `STORAGE_LOCAL_ROOT`, `STORAGE_PUBLIC_BASE_URL`.
- Thêm interface/value object/helper dùng chung.
- Giữ default local để không đổi behavior khi chưa migrate file.

**TEST**

- Config contract local/S3.
- Driver không hợp lệ bị từ chối rõ ràng.
- Không có secret trong repository.

### Change 2 — LocalStorage driver

**WHAT**

- Tạo thư mục key an toàn trong `storage/uploads/`.
- Hỗ trợ put/read/url/delete/exists.
- Chặn path traversal và executable extension.

**TEST**

- Upload/read/delete fixture.
- Key có ký tự bất thường và `../` bị từ chối.
- Cleanup fixture thành công.

### Change 3 — Product image workflow

**WHAT**

- Refactor `admin/products.php` sang storage contract.
- Database lưu object key mới.
- Giữ đọc được image legacy trong `assets/images/`.
- Update/delete image không làm mất file cũ trước khi DB update thành công.

**TEST**

- Thêm/sửa/xóa product image local.
- Product cũ vẫn hiển thị.
- Không có PHP/script upload được lưu hoặc thực thi.

### Change 4 — Banner và avatar workflow

**WHAT**

- Refactor banner upload và avatar upload sang contract.
- Tách key namespace `banners/` và `avatars/`.
- Giữ fallback cho dữ liệu legacy.

**TEST**

- Upload/read/delete banner và avatar.
- Người dùng khác không đọc được file private ngoài authorization path.
- Legacy URL không bị hỏng.

### Change 5 — S3-ready adapter

**WHAT**

- Chọn strategy dependency và version được approve.
- Implement adapter S3 hoặc boundary rõ ràng nếu chưa cài SDK.
- Không public bucket mặc định; URL dùng presigned/read gateway theo strategy.

**TEST**

- Config thiếu bucket/region báo lỗi an toàn.
- Local driver vẫn chạy khi S3 chưa bật.
- Không có AWS access key trong code hoặc logs.

### Change 6 — Existing-file migration và regression

**WHAT**

- Inventory file legacy trước khi di chuyển.
- Dry-run migration và checksum/count.
- Migrate có thể resume, không ghi đè key đã tồn tại.
- Chỉ xóa legacy sau khi verify read và rollback checkpoint.

**TEST**

- Existing uploads không mất.
- Product/banner/avatar regression hai base path.
- Local storage workflow PASS.
- S3-ready behavior được document rõ.

## 6. Rollback và an toàn dữ liệu

- Không xóa legacy file trong cùng bước upload mới.
- Migration file chạy theo copy → verify → switch key → cleanup.
- Nếu verify lỗi, giữ nguyên legacy và rollback DB key.
- Trước migration phải có inventory và backup.
- Không chạy S3 production migration trong Phase 4 local implementation.

## 7. File dự kiến

- `config/storage.php`.
- `app/Storage/StorageInterface.php` hoặc cấu trúc tương đương phù hợp codebase
  hiện tại.
- Local/S3 adapter.
- `docs/testing/PHASE_04_CHANGE_*.md`.
- Test integration storage và migration dry-run.

## 8. Tiêu chí hoàn thành Phase 4

- Contract và local driver `PASS`.
- Product/banner/avatar workflow dùng storage abstraction.
- Legacy files đọc được hoặc migration có checksum verified.
- S3-ready behavior được xác định và không có secret.
- Local regression hai base path `PASS`.
- Báo cáo tiếng Việt hoàn chỉnh.
- Human review trước AWS Readiness Gate.

## 9. Approval gate

Kế hoạch Phase 4 đã được người dùng phê duyệt. Implementation đã được human
review duyệt để chuyển sang AWS Readiness Gate. Không tự động xóa legacy file
trong phase này.

