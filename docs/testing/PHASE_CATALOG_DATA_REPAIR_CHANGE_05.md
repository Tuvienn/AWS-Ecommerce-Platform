# Báo cáo Change 5 — Customer/Admin Regression

## Trạng thái

```text
CHANGE 5: PASS
TRẠNG THÁI: CHỜ HUMAN REVIEW
NGÀY: 2026-10-29
```

## Phạm vi kiểm tra

Regression được chạy với DocumentRoot `public/`, `APP_BASE_PATH=''`, PHP built-in
server và database local. Không tạo order thật, không upload file mới và không
thay đổi dữ liệu database.

## Route khách hàng

Các route sau trả HTTP 200 và render được:

- `/`
- `/products.php`
- `/products.php?search=iphone`
- `/products.php?category=1`
- `/product_detail.php?id=1`
- `/cart.php`
- `/login.php`
- `/register.php`
- `/assets/images/product-placeholder.jpg`

`/checkout.php` redirect đúng về login khi chưa đăng nhập; đây là hành vi bảo
mật mong đợi, không phải lỗi route.

## Admin access guard

Các route `/admin/`, `/admin/products.php`, `/admin/categories.php`,
`/admin/brands.php`, `/admin/orders.php` và `/admin/banners.php` đều redirect về
`/login.php` khi chưa có session admin. Không có route admin nào lộ nội dung
nội bộ cho anonymous request.

## Storage và AJAX gateway

- Storage banner object trả HTTP 200 và `image/jpeg`.
- Product placeholder trả HTTP 200 và `image/jpeg`.
- Legacy avatar không tồn tại trả HTTP 404.
- `ajax/cart_action.php` từ GET trả HTTP 405; endpoint vẫn bắt buộc POST/CSRF.

## Static và log validation

- PHP lint toàn bộ source trong `public`, `app`, `config`, `includes`,
  `database` và `tests`: PASS (45 file).
- Không phát sinh log application mới trong lần regression; các dòng cũ chỉ là
  fixture/test lỗi từ các phase trước, không liên quan catalog image.

## Kết luận

Change 5 PASS. Các luồng public, redirect bảo mật admin/checkout, placeholder,
storage gateway, legacy gateway và AJAX method guard đều hoạt động đúng. Có thể
chuyển sang Change 6 — AWS readiness handoff sau khi human review.
