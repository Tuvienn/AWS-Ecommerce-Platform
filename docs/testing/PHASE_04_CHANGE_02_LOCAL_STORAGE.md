# Phase 4 — Change 2: LocalStorage driver

## Trạng thái

```text
CHANGE: 2
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## File thay đổi

### `app/Storage/LocalStorage.php`

```text
PURPOSE: Lưu runtime object local ngoài public filesystem.
WHAT CHANGED: Implement put/read/url/exists/delete và copy file migration.
WHY: Thay thế thao tác filesystem trực tiếp trong page/controller.
ROLE IN SYSTEM: Local storage driver mặc định.
RISK: Sai object key có thể dẫn đến path traversal.
HOW TO TEST: tests/integration/phase4_local_storage.php.
RESULT: PASS; read/write/delete và path traversal protection đều pass.
```

### `includes/storage.php` và `storage_file.php`

```text
PURPOSE: Cung cấp helper và endpoint đọc object image.
WHAT CHANGED: Tạo key namespaced, validate ảnh và trả image với nosniff header.
WHY: File trong storage không còn public trực tiếp.
ROLE IN SYSTEM: Storage gateway cho local runtime upload.
RISK: Object key sai hoặc non-image bị lộ.
HOW TO TEST: Gọi object banner đã migrate qua HTTP.
RESULT: HTTP 200, image/jpeg, 501318 bytes.
```

