# Phase 2 — Báo cáo Change 5: Upload security

## Trạng thái

```text
CHANGE: Change 5 — Upload security
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## File và thay đổi

- `includes/security.php`: thêm upload helper dùng `is_uploaded_file()`,
  `finfo`, `getimagesize()`, allowlist MIME, size limit, random filename và
  safe-delete guard.
- `account.php`: avatar tối đa 2 MB, kiểm tra nội dung thật, filename server.
- `admin/products.php`: product image tối đa 5 MB, không dùng original filename;
  xóa file runtime cũ/an toàn khi thay hoặc xóa product.
- `admin/banners.php`: banner image tối đa 5 MB; bảo vệ ba seed image khi thay
  hoặc xóa banner.
- `uploads/.htaccess`, `assets/images/.htaccess`: vô hiệu CGI và chặn extension
  PHP/PHTML/PHAR trên Apache như một lớp phòng vệ bổ sung.

## Bằng chứng kiểm thử

| Test | Kết quả |
|---|---|
| PHP file giả tên `.jpg`, client MIME `image/jpeg` | PASS — bị từ chối |
| PHP file giả tên `.png`, client MIME `image/png` | PASS — bị từ chối |
| Ảnh JPEG thật mang tên `../../shell.php` | PASS — lưu thành `avatar_<random>.jpg` |
| Ảnh JPEG thật mang tên `../../payload.php` | PASS — lưu thành `product_<random>.jpg` |
| Ảnh JPEG thật mang tên `payload.phtml` | PASS — lưu thành `banner_<random>.jpg` |
| File avatar vượt PHP upload limit | PASS — bị từ chối |
| Product/banner giả mạo không tạo database record | PASS |
| Xóa fixture qua application xóa cả runtime file | PASS |
| Filename traversal không được dùng làm path đích | PASS |
| Ba banner seed còn nguyên | PASS |
| PHP lint và `git diff --check` | PASS |

User, product, banner và toàn bộ file fixture đã được xóa. Không còn file có
prefix test sau cleanup.

## Giới hạn có chủ đích

Phase 2 giữ vị trí upload hiện tại để không lấn sang Storage Abstraction của
Phase 4. `.htaccess` là lớp bảo vệ cho Apache; MIME/content validation và
filename do server sinh là lớp bảo vệ không phụ thuộc web server.

## File xóa

Không có file project bị xóa; chỉ xóa test artifact đã tạo trong vòng test.
