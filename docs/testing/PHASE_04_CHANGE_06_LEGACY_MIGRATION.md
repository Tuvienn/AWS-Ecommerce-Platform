# Phase 4 — Change 6: Existing-file migration và regression

## Trạng thái

```text
CHANGE: 6
KẾT QUẢ: PASS WITH KNOWN LIMITATION
NGÀY KIỂM TRA: 2026-10-28
```

## File thay đổi

### `database/migrate_runtime_uploads.php`

```text
PURPOSE: Migrate legacy runtime file bằng CLI an toàn.
WHAT CHANGED: Thêm dry-run mặc định và --apply copy → checksum verify → update database.
WHY: Không được xóa/mất file legacy khi chuyển object key.
ROLE IN SYSTEM: Migration tool có thể resume; file đã đổi key sẽ tự skip.
RISK: File legacy không tồn tại không thể migrate tự động.
HOW TO TEST: Dry-run, apply, dry-run lần hai và DB inventory.
RESULT: 3 banner được migrate; legacy banner giữ nguyên.
```

## Kết quả inventory

- 3 banner tồn tại đã được copy checksum-verified và database đổi sang object
  key `banners/YYYY/MM/<uuid>.jpg`.
- Không có avatar database legacy cần migrate.
- 17 product image seed được database tham chiếu nhưng file gốc không tồn tại
  trong `assets/images/` từ trước Phase 4; script chỉ báo `MISSING`, không sửa
  database hoặc tạo placeholder giả.
- Không có file legacy nào bị xóa tự động.
- Smoke regression public ở document root và `/aws-ecommerce-platform` đều trả HTTP 200
  cho home, product listing, product detail và banner storage gateway.

