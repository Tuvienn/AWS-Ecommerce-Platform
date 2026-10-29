# Kế hoạch triển khai — Catalog và Data Repair

## 1. Trạng thái

```text
MỤC TIÊU: Khôi phục dữ liệu catalog và xử lý product image bị thiếu
TRẠNG THÁI KẾ HOẠCH: ĐÃ PHÊ DUYỆT
TRẠNG THÁI IMPLEMENTATION: CHANGE 1–6 LOCAL PREFLIGHT PASS — CHỜ AWS SCOPE REVIEW
NGÀY LẬP: 2026-10-29
```

Đây là bước chuẩn bị trước AWS deployment và redesign giao diện. Change 1–3 đã
hoàn tất với placeholder trung tính cho các sản phẩm chưa có ảnh thật; chưa
redesign UI và chưa xóa legacy file.

## 2. Hiện trạng đã xác nhận

- 17 products trong database.
- 6 categories, 10 brands, 3 banners, 2 users.
- Cả 17 product image filename đều không có file tương ứng trong
  `public/assets/images/`.
- 3 banner object đã tồn tại trong `storage/uploads/banners/`.
- Legacy avatar files tồn tại nhưng hiện không có user database tham chiếu.
- Database hiện không có order/order detail cần giữ lại trong repair fixture.

## 3. Mục tiêu hoàn thành

- Mỗi product active có image hợp lệ hoặc image fallback được xác định rõ.
- Fresh schema/seed không tái tạo filename trỏ tới file không tồn tại.
- Có health check phát hiện image missing/invalid trước deployment.
- Product listing, detail, cart và admin product workflow không còn broken image.
- Không mất dữ liệu sản phẩm, giá, stock, brand, category hoặc order history.
- Chưa redesign UI trong scope này.

## 4. Quyết định dữ liệu

### Placeholder tạm thời

Dùng một bộ placeholder trung tính, ghi nhận rõ là dữ liệu demo. Không dùng ảnh
ngẫu nhiên không khớp sản phẩm.

Đây là quyết định áp dụng trong scope hiện tại. Ảnh local do người dùng cung cấp
được import vào storage; không gọi API ảnh bên ngoài và không hotlink ảnh.

## 5. Trình tự implementation

### Change 1 — Catalog asset inventory — ĐÃ HOÀN TẤT

- Tạo inventory report map `product.id`, `name`, `image`, object key/path,
  file existence, MIME, size và checksum.
- Backup database trước mọi update.
- Phân biệt missing file, invalid image, legacy filename và storage object key.

**Kết quả:** report trước sửa, không thay đổi dữ liệu.

Đã kiểm tra bằng `database/check_catalog_assets.php`: 17/17 product image
legacy bị thiếu, 3/3 banner hợp lệ, user không có avatar DB reference bị thiếu.
Đã tạo backup tạm thời trước khi sửa database.

### Change 2 — Asset naming và storage mapping — ĐÃ HOÀN TẤT

- Chốt key chuẩn `products/YYYY/MM/<uuid>.<extension>`.
- Map asset theo product ID/SKU, không map chỉ theo tên hiển thị.
- Xác định image fallback dùng cho product chưa có asset.
- Không xóa legacy file trong bước này.

Đã dùng `product-placeholder.jpg` làm fallback legacy filename cho sản phẩm
chưa có asset thật. Key storage chuẩn hiện tại vẫn được giữ cho asset upload
mới; không tự tạo ảnh sản phẩm giả.

### Change 3 — Data/seed repair — ĐÃ HOÀN TẤT

- Bổ sung/cập nhật product image reference sau khi asset đã được verify.
- Cập nhật seed/schema để fresh install không trỏ tới file thiếu.
- Giữ nguyên product name, price, sale price, stock, category và brand.
- Nếu dùng placeholder, ghi metadata/report để dễ thay thế sau.

Đã cập nhật 17 product reference trong database hiện tại và cập nhật
`database/schema.sql`. Tên, giá, sale price, stock, category, brand và order
data không bị thay đổi.

### Change 4 — Image health check — ĐÃ HOÀN TẤT

- Tạo CLI check catalog assets.
- Có exit code khác 0 khi product active thiếu image hoặc object không đọc được.
- Báo cáo JSON/text để dùng trong local và CI/deployment preflight.
- Kiểm tra banner, avatar và product image theo từng namespace.

`database/check_catalog_assets.php` hiện trả JSON có trạng thái PASS/FAIL,
loại asset (legacy/storage/external), MIME, kích thước và SHA-256. Exit code là
`0` khi không có lỗi và `1` khi có missing/invalid asset, phù hợp cho CI hoặc
deployment preflight.

### Change 5 — Customer/admin regression — ĐÃ HOÀN TẤT

- Test home, product listing, filter/search, detail, related products.
- Test cart hiển thị ảnh và fallback.
- Test admin product create/edit/upload/restore.
- Test storage gateway và legacy image gateway.
- Test document root `public/` và configuration subpath.

Đã smoke test route khách hàng, route admin chưa đăng nhập, storage gateway,
legacy gateway, AJAX method guard và PHP lint. Không tạo order thật, không upload
file mới và không thay đổi database trong regression.

### Change 6 — AWS readiness handoff

- Xác nhận asset health check pass.
- Xác nhận storage driver local/S3 mapping.
- Chụp inventory/count/checksum trước migration AWS.
- Chỉ sau đó tiếp tục Phase 5 AWS provisioning.

Đã import ảnh local bằng `database/import_local_product_images.php`, sau đó tạo
`deploy/aws/application_readiness.sh` và báo cáo
`docs/testing/PHASE_CATALOG_DATA_REPAIR_CHANGE_06_AWS_HANDOFF.md`. Change này
chỉ chạy local preflight/read-only; không provision AWS resource.

## 6. Rollback và an toàn

- Backup database trước Change 3.
- Không update database nếu asset copy/checksum verify thất bại.
- Không xóa legacy file tự động.
- Có mapping manifest để rollback image key.
- Giữ placeholder/report; không tạo dữ liệu ảnh sản phẩm giả mạo.

## 7. File dự kiến tạo/sửa

- `database/check_catalog_assets.php` — CLI health check.
- `database/import_local_product_images.php` — import ảnh local đã được người dùng cung cấp.
- `database/catalog_asset_report.json` — chỉ tạo nếu người dùng duyệt artifact
  report; không commit runtime output.
- `database/schema.sql` hoặc seed liên quan nếu cần sửa reference.
- `docs/testing/PHASE_CATALOG_DATA_REPAIR_*.md`.
- Product asset mapping và placeholder fallback đã được kiểm tra.

## 8. Tiêu chí hoàn thành

- Active catalog không còn broken image.
- Health check trả `PASS`.
- DB/seed/asset mapping reproducible.
- Customer/admin regression `PASS`.
- Không có fixture/database phụ.
- Báo cáo tiếng Việt hoàn chỉnh.
- Human review trước AWS deployment và trước redesign UI.

## 9. Approval gate

Chiến lược placeholder đã được chốt. Change 1–6 local readiness đã pass và
đang chờ human review trước AWS provisioning.

