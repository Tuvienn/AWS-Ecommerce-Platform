# Phase 1 — Báo cáo Change 6: URL banner portable

## Trạng thái

```text
CHANGE: Change 6 — Chuẩn hóa URL banner và seed data
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## Thay đổi theo file

### `includes/functions.php`

- **Mục đích:** Chuẩn hóa URL lấy từ nội dung database.
- **Thay đổi:** Thêm `contentUrl()`; giữ nguyên URL ngoài, hỗ trợ `#`, query,
  fragment, path portable và dữ liệu legacy có prefix `/aws-ecommerce-platform`.
- **Vai trò:** Cầu nối giữa dữ liệu nội dung và cấu hình base path.
- **Rủi ro:** Double-prefix hoặc sửa nhầm external URL.
- **Cách kiểm thử:** Test path tương đối, legacy path, root path, URL tuyệt đối,
  `#`, query và fragment.
- **Kết quả:** `PASS`.

### `index.php`

- **Mục đích:** Render nút banner theo deployment path hiện tại.
- **Thay đổi:** Dùng `contentUrl()` cho `banners.button_link`.
- **Vai trò:** Hiển thị banner trang chủ.
- **Rủi ro:** Nút banner không điều hướng được.
- **Cách kiểm thử:** Kiểm tra HTML trang chủ ở subpath và document root.
- **Kết quả:** `PASS`.

### `database/schema.sql`

- **Mục đích:** Seed mới không phụ thuộc tên thư mục project.
- **Thay đổi:** Chỉ đổi ba `button_link` seed từ `/aws-ecommerce-platform/products.php...`
  thành `/products.php...`; không đổi schema.
- **Vai trò:** Khởi tạo dữ liệu mẫu.
- **Rủi ro:** Thay đổi ngoài ý muốn đối với schema hoặc dữ liệu khác.
- **Cách kiểm thử:** Review diff và xác nhận chỉ ba giá trị URL thay đổi.
- **Kết quả:** `PASS`.

## Bằng chứng kiểm thử

| Trường hợp | Kết quả |
|---|---|
| Path portable ở `/aws-ecommerce-platform` | PASS |
| Path portable ở document root | PASS |
| Dữ liệu legacy `/aws-ecommerce-platform/products.php` | PASS |
| URL `https://...` và protocol-relative | PASS — giữ nguyên |
| `#`, query string và fragment | PASS |
| Diff schema | PASS — chỉ ba seed URL thay đổi |

## File xóa

Không có.
