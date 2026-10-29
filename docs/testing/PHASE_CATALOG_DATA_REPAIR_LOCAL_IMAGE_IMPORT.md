# Báo cáo import ảnh sản phẩm local

## Trạng thái

```text
KẾT QUẢ: PASS
NGÀY: 2026-10-29
DATABASE: ĐÃ CẬP NHẬT 17/17 PRODUCT
```

## Thay đổi

Đã dùng ảnh nguồn trong folder `/images`, giữ nguyên folder này làm bản gốc,
đồng thời import bản runtime vào `storage/uploads/products/2026/10/`. Database
được cập nhật bằng storage key, không lưu đường dẫn tuyệt đối.

Script thực hiện:

```text
database/import_local_product_images.php
```

Ảnh được validate MIME, nội dung, kích thước và SHA-256 trước khi lưu. Database
được cập nhật trong transaction; nếu lỗi sẽ rollback và xóa object batch mới.

## Mapping đặc biệt đã xác nhận

- OPPO dùng tạm ảnh Vivo theo yêu cầu.
- Dell và ThinkPad dùng chung ảnh laptop `1777637925_lap.jpg`.
- iPad dùng ảnh Apple tablet.
- Samsung Galaxy Tab dùng ảnh Samsung tablet.
- AirPods dùng ảnh tai nghe không dây.
- Sony WH-1000XM5 dùng ảnh tai nghe chụp tai.
- JBL Charge 5 dùng ảnh loa JBL.

## Kiểm tra sau import

- 17/17 product image: valid.
- 0 missing, 0 invalid.
- Catalog health check: PASS.
- Trang `/`, `/products.php`, `/product_detail.php?id=1`: HTTP 200.
- Storage gateway trả product image: HTTP 200, `image/jpeg`.
- Không xóa hoặc đổi tên file nguồn trong `/images`.

## Backup

Database backup trước import:

```text
/private/tmp/aws-ecommerce-platform-before-local-images-20261029234724.sql
```

