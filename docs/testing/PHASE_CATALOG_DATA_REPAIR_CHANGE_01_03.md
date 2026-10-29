# Báo cáo Change 1–3 — Sửa catalog và dữ liệu ảnh

## Trạng thái

```text
CHANGE 1: PASS
CHANGE 2: PASS
CHANGE 3: PASS
TRẠNG THÁI: CHỜ HUMAN REVIEW
NGÀY: 2026-10-29
```

## Phạm vi

Đợt này chỉ xử lý inventory, mapping và dữ liệu ảnh catalog. Không redesign UI,
không xóa legacy file, không thay đổi tên sản phẩm, giá, sale price, stock,
category, brand hoặc order history.

## Phát hiện trước khi sửa

- Database có 17 product, 3 banner và 2 user.
- Cả 17 product image reference đều là legacy filename nhưng file tương ứng
  không tồn tại trong `public/assets/images/`.
- 3 banner hiện tại đều tồn tại và đọc được.
- Không có user avatar reference không hợp lệ trong database.

## Thay đổi đã thực hiện

### `database/check_catalog_assets.php`

Script CLI kiểm tra product, banner và avatar theo legacy path hoặc storage
object key. Script trả JSON và exit code `1` khi còn missing asset. Kết quả trước
sửa: products `17 missing`, banners `0 missing`, avatars `0 missing`.

### `database/repair_catalog_data.php`

Script CLI transaction-safe cập nhật chỉ cột `products.image` khi asset không
tồn tại. Đã cập nhật 17 record từ filename cũ sang `product-placeholder.jpg`;
các cột dữ liệu khác giữ nguyên.

### `database/schema.sql`

Đã thay 17 product seed image reference bị thiếu bằng
`product-placeholder.jpg`, để fresh install không tái tạo broken image.

### `public/assets/images/product-placeholder.jpg`

Đã bổ sung một ảnh placeholder trung tính từ asset generic có sẵn. Đây không
phải ảnh giả đại diện cho sản phẩm; nó chỉ là fallback tạm thời cho đến khi có
ảnh thật theo product ID/SKU.

## Kết quả sau sửa

- Catalog health check: products `17 valid / 0 missing`.
- Banner health check: `3 valid / 0 missing`.
- Avatar health check: `0 reference / 0 missing`.
- `placeholder_products = 17`.
- `orders = 0`, `order_details = 0`; không có order data bị chạm tới.
- PHP lint toàn bộ source: PASS.
- HTTP smoke với DocumentRoot `public/`: `/`, `/products.php`,
  `/product_detail.php?id=1` và placeholder image đều trả HTTP 200.

## Backup và rollback

Đã tạo database backup tạm thời trước repair tại:
`/private/tmp/aws-ecommerce-platform-catalog-20261029153039.sql`.

Không có file legacy nào bị xóa. Có thể rollback database bằng backup này nếu
human review yêu cầu quay lại trạng thái trước Change 3.

## Việc còn lại

Human review cần xác nhận placeholder hiển thị đúng với mục tiêu demo. Sau khi
được duyệt mới tiếp tục AWS handoff; scope hiện tại không thu thập hoặc import
ảnh sản phẩm bên ngoài.
