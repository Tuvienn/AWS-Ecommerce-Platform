# Phase 4 — Change 3–4: Product, banner và avatar workflow

## Trạng thái

```text
CHANGE: 3–4
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## File thay đổi

### `admin/products.php`

```text
PURPOSE: Quản lý product image.
WHAT CHANGED: Upload mới dùng namespace products; preview/read dùng storage helper.
WHY: Tách runtime image khỏi assets public.
ROLE IN SYSTEM: Admin product workflow.
RISK: File mới có thể thành orphan nếu database lỗi giữa thao tác.
HOW TO TEST: Upload/sửa product image qua admin trong final browser regression.
RESULT: Code path đã chuyển sang storage abstraction.
```

### `admin/banners.php`, `index.php`

```text
PURPOSE: Quản lý và hiển thị banner.
WHAT CHANGED: Upload banner dùng namespace banners; render hỗ trợ object key và legacy filename.
WHY: Banner hiện có cần tiếp tục đọc được trong lúc migration dần.
ROLE IN SYSTEM: Banner workflow customer/admin.
RISK: Legacy filename và object key phải được phân biệt đúng.
HOW TO TEST: Migration banner và HTTP read endpoint.
RESULT: 3 banner legacy đã copy checksum-verified sang local storage.
```

### `account.php`, `includes/functions.php`, `includes/header.php`

```text
PURPOSE: Avatar upload/read.
WHAT CHANGED: Avatar mới dùng namespace avatars; helper hỗ trợ avatar object key và legacy avatar.
WHY: Giữ tương thích dữ liệu user cũ.
ROLE IN SYSTEM: Customer profile/avatar workflow.
RISK: Avatar legacy không được xóa trước khi key mới đã ghi vào database.
HOW TO TEST: Upload avatar qua account trong final browser regression.
RESULT: Code path đã chuyển sang storage abstraction; không có avatar DB legacy cần migrate.
```

