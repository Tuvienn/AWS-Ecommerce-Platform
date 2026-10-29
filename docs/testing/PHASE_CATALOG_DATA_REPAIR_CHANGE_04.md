# Báo cáo Change 4 — Image Health Check

## Trạng thái

```text
CHANGE 4: PASS
TRẠNG THÁI: CHỜ HUMAN REVIEW
NGÀY: 2026-10-29
```

## Nội dung triển khai

Đã nâng cấp `database/check_catalog_assets.php` thành health check CLI dùng cho
local, CI và preflight trước deploy. Script kiểm tra riêng ba namespace:

- `products`: image của từng product.
- `banners`: image của từng banner.
- `avatars`: avatar có reference trong user database.

Với mỗi asset, script phân biệt legacy filename, storage object key và external
URL; kiểm tra file có tồn tại, đọc được, MIME có phải ảnh JPEG/PNG/GIF/WebP,
kích thước lớn hơn 0 và tạo SHA-256 để đối chiếu inventory.

## Kết quả chạy thực tế

```text
status: PASS
products: 17 valid, 0 missing, 0 invalid
banners: 3 valid, 0 missing, 0 invalid
avatars: 0 reference, 0 missing, 0 invalid
exit code: 0
```

Toàn bộ 17 product hiện dùng placeholder JPEG hợp lệ với cùng checksum:

```text
sha256: 1375ef52a4c5585e96f936a43987a2ede9cfc220654c1955833a01b00d53e996
```

## Cách sử dụng

```bash
php database/check_catalog_assets.php
```

Exit code `0` nghĩa là PASS. Exit code `1` nghĩa là có asset missing hoặc
invalid; JSON output chứa danh sách `issues` và thông tin từng asset để xử lý.

## Kết luận

Change 4 PASS. Health check không sửa database hoặc file asset. Change 5
regression và Change 6 AWS handoff vẫn chờ human review.
