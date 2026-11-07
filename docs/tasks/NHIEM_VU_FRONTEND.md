# Nhiệm vụ thành viên Frontend

## 1. Trạng thái và mục tiêu

**Trạng thái:** `PLANNED`

Mục tiêu của nhiệm vụ là hoàn thiện giao diện khách hàng dựa trên dữ liệu và
nghiệp vụ Backend thực sự hỗ trợ. Frontend không hiển thị chức năng giả, không
tự tạo dữ liệu thanh toán và không hard-code URL S3.

Các nhóm công việc chính:

1. Gallery từ một đến mười ảnh cho sản phẩm.
2. Chế độ sáng, tối và theo hệ thống.
3. Giao diện COD và chuyển khoản VietQR.
4. Đồng bộ filter giảm giá và bestseller.
5. Redesign customer UI sau khi hợp đồng Backend ổn định.

MoMo chưa được kích hoạt trong giao diện cho đến khi Backend sandbox, HTTPS và
IPN đã được duyệt và kiểm thử.

## 2. Quy tắc bắt buộc trước khi code

Quy ước tên nhánh:

```text
ten-thanh-vien-dd-mm-yyyy
```

- Viết tên không dấu, không có khoảng trắng.
- Ngày là ngày bắt đầu nhiệm vụ.
- Ví dụ thành viên tên Bình bắt đầu ngày 31/10/2026: `binh-31-10-2026`.
- Nếu cùng người tạo thêm nhánh trong cùng ngày, thêm hậu tố:
  `binh-31-10-2026-2`.

Thành viên phải thay tên và ngày trong lệnh mẫu dưới đây bằng thông tin thật:

```bash
git switch main
git pull origin main
git switch -c binh-31-10-2026
```

- Không sửa database, migration, repository, service hoặc AWS configuration.
- Có thể dùng dữ liệu mẫu trên máy để dựng giao diện nhưng không commit dữ liệu
  giả trong bản cuối.
- Không thay đổi tên field/hợp đồng dữ liệu nếu chưa thống nhất với Backend.
- Không hard-code bank account, QR URL, S3 URL hoặc credential.
- Mọi giao diện phải có desktop và mobile verification.

## 3. Hợp đồng dữ liệu dùng chung với Backend

### 3.1. Ảnh sản phẩm

Frontend nhận:

```php
[
    [
        'id' => 1,
        'image_key' => 'products/2026/10/example.jpg',
        'is_primary' => 1,
        'sort_order' => 0,
    ],
]
```

URL ảnh phải tạo bằng helper hiện tại:

```php
runtimeImageUrl($image['image_key'], 'assets/images')
```

Không tạo URL S3 trực tiếp.

### 3.2. Thanh toán

```text
payment_method: cod | bank_qr | momo
payment_status: unpaid | pending | paid | failed | expired | refunded
```

Trong phạm vi hiện tại chỉ hiển thị lựa chọn hoạt động:

```text
cod
bank_qr
```

Không hiển thị MoMo như một lựa chọn có thể thanh toán cho đến khi Backend báo
`TESTED`.

### 3.3. Bộ lọc và trạng thái đơn

```text
Sale filter: sale=shock

Order status:
pending | confirmed | shipping | completed | cancelled
```

Frontend không tự đặt tên hoặc giá trị trạng thái khác.

### 3.4. AJAX response

```json
{"success": true, "message": "Thao tác thành công"}
```

hoặc:

```json
{"success": false, "message": "Nội dung lỗi an toàn cho người dùng"}
```

## 4. Ranh giới file

Frontend được phép sửa chủ yếu:

```text
public/index.php
public/products.php
public/product_detail.php
public/cart.php
public/checkout.php
public/success.php
public/account.php
resources/views/layouts/
public/assets/css/style.css
public/assets/js/main.js
```

Trong lúc hai thành viên làm song song, Frontend không sửa:

```text
app/
config/
database/
includes/
public/ajax/
public/admin/
tests/integration/
.env.example
composer.json
composer.lock
```

`public/login.php` và `public/register.php` tạm thuộc Backend trong change bảo
mật. Chỉ redesign hai trang sau khi nhánh Backend đã merge.

## 5. Change F1 — Gallery ảnh sản phẩm

Trong `public/product_detail.php`:

- Hiển thị một ảnh lớn.
- Có nút trái/phải khi sản phẩm có từ hai ảnh.
- Dùng danh sách thumbnail cuộn ngang để giao diện vẫn gọn khi có mười ảnh.
- Có thể dùng dots khi ít ảnh, nhưng không để mười dots làm vỡ layout mobile.
- Bấm thumbnail/dot để đổi ảnh.
- Hỗ trợ bàn phím và `aria-label` cho button.
- Responsive trên mobile; hỗ trợ swipe nếu không làm tăng dependency.
- Một ảnh thì ẩn controls.
- Ảnh lỗi dùng placeholder hiện có.
- Alt text lấy từ tên sản phẩm.
- Không tải lại trang khi đổi ảnh.

Trong lúc Backend chưa merge, dùng fallback:

```php
$productImages = [];

if (method_exists($productRepository, 'findImages')) {
    $productImages = $productRepository->findImages($id);
}

if (!$productImages && !empty($product['image'])) {
    $productImages[] = [
        'id' => 0,
        'image_key' => $product['image'],
        'is_primary' => 1,
        'sort_order' => 0,
    ];
}
```

Không commit array ảnh giả vào bản cuối.

## 6. Change F2 — Chế độ sáng, tối và theo hệ thống

### Yêu cầu

- Nút đổi theme đặt ở header.
- Ba lựa chọn: `light`, `dark`, `system`.
- Lưu lựa chọn bằng `localStorage`.
- Nếu chưa chọn, dùng `prefers-color-scheme`.
- Áp dụng theme sớm để tránh nháy nền sáng khi tải trang.
- Dùng CSS variables; không thêm một bộ CSS copy riêng cho dark mode.
- Kiểm tra độ tương phản của chữ, button, form, bảng và flash message.
- Theme hoạt động trên customer pages; admin theme là nhiệm vụ riêng sau này.

Các key dự kiến:

```text
localStorage key: aws-ecommerce-theme
value: light | dark | system
HTML attribute: data-theme
```

Backend không cần lưu theme vào database ở giai đoạn này.

## 7. Change F3 — Checkout COD và VietQR

### Checkout

- Hiển thị đúng hai lựa chọn hoạt động: COD và chuyển khoản VietQR.
- Giá trị gửi về server phải là `cod` hoặc `bank_qr`.
- Không gửi giá/tổng tiền từ browser như nguồn authoritative.
- Không hiển thị Bank Card, chuyển khoản thẻ hoặc MoMo hoạt động giả.
- Giữ dữ liệu form khi Backend trả validation error.

### Trang chờ chuyển khoản

Hiển thị dữ liệu Backend cung cấp:

- QR.
- Ngân hàng.
- Tên tài khoản.
- Số tài khoản.
- Số tiền.
- Nội dung chuyển khoản.
- Hạn thanh toán.
- Nút sao chép từng trường.
- Nút “Tôi đã chuyển khoản”.

Quy tắc quan trọng:

- Nút “Tôi đã chuyển khoản” chỉ gửi request chuyển trạng thái sang chờ xác nhận.
- Frontend không tự hiển thị `paid` khi chưa nhận kết quả Backend.
- Nếu hết hạn, hiển thị trạng thái `expired` theo dữ liệu server.
- Không đưa số tài khoản thật vào JavaScript/source code.

## 8. Change F4 — Catalog và nội dung nhất quán

- Tất cả link giảm giá dùng `sale=shock`.
- Bestseller render từ dữ liệu Backend cung cấp; không dùng tồn kho thấp.
- Giữ filter khi chuyển trang.
- Có trạng thái không tìm thấy sản phẩm.
- Hiển thị tồn kho rõ ràng: còn hàng, sắp hết, hết hàng.
- Vô hiệu hóa thêm giỏ khi hết hàng.
- Xóa các nội dung quảng cáo không đúng chức năng, ví dụ thanh toán thẻ khi hệ
  thống chưa hỗ trợ.
- Không quảng cáo điều kiện phí vận chuyển nếu Backend chưa tính phí tương ứng.

## 9. Change F5 — Order/account UI

- Hiển thị trạng thái đơn đúng năm giá trị đã thống nhất.
- Không dựng timeline với timestamp giả nếu Backend chỉ có `created_at`.
- Có empty state khi chưa có đơn.
- Chỉ hiển thị nút hành động khi Backend thực sự hỗ trợ.
- Không tự cho phép customer xác nhận thanh toán hoặc chuyển trạng thái giao
  hàng.

Nếu sau này có trang chi tiết đơn, frontend phải dùng dữ liệu đã kiểm tra
ownership từ Backend; không chỉ gửi `order_id` rồi tin kết quả browser.

## 10. Change F6 — Redesign customer UI

Chỉ bắt đầu hoàn thiện toàn bộ visual sau khi gallery và payment contract đã ổn
định.

Phạm vi:

- Header, footer và navigation.
- Trang chủ.
- Product card và product detail.
- Cart và checkout.
- Account và order state.
- Form, button, notification, loading và empty state.
- Responsive desktop/tablet/mobile.

Không thay đổi:

- Route.
- Tên field form.
- Tên action AJAX.
- Payment/status value.
- Database schema.
- Session structure.

## 11. Kiểm thử bắt buộc

Chạy:

```bash
php -l <cac-file-php-da-sua>
tests/run_regression.sh
bash deploy/aws/application_readiness.sh
```

Manual verification tối thiểu:

1. Gallery có một ảnh.
2. Gallery có hai hoặc ba ảnh.
3. Gallery có mười ảnh và thumbnail cuộn được.
4. Không hiển thị ảnh thứ mười một nếu Backend từ chối upload.
5. Ảnh lỗi dùng placeholder.
6. Theme sáng, tối và system giữ nguyên khi đổi trang.
7. COD gửi đúng `cod`.
8. VietQR hiển thị đúng dữ liệu Backend.
9. Frontend không tự chuyển payment thành `paid`.
10. Filter sale dùng đúng `sale=shock`.
11. Desktop và mobile không vỡ layout.

Chụp evidence tối thiểu:

- Product gallery desktop.
- Product gallery mobile.
- Light mode.
- Dark mode.
- Checkout COD.
- Checkout VietQR pending.

## 12. Điều kiện hoàn thành

- Không có dữ liệu/URL/credential thật được hard-code.
- Không sửa file ngoài phạm vi khi chưa trao đổi.
- Giao diện dùng đúng hợp đồng Backend.
- Không có chức năng thanh toán giả.
- Regression/readiness `PASS`.
- Có danh sách screenshot và giới hạn còn lại.

Push nhánh:

```bash
git push -u origin binh-31-10-2026
```

Không tự merge hoặc push trực tiếp vào `main`.

## 13. Đồng bộ sau khi Backend merge

Backend được merge vào `main` trước. Sau đó Frontend chạy:

```bash
git switch binh-31-10-2026
git fetch origin
git merge origin/main
```

Sau khi resolve và test với dữ liệu thật mới bàn giao nhánh cho người tích hợp.

## 14. Thông tin cần gửi khi bàn giao

```text
BRANCH:
COMMITS:
FILES CHANGED:
DATA CONTRACT USED:
SCREENSHOTS:
TESTS:
RESULT: PASS | FAIL | BLOCKED
KNOWN LIMITATIONS:
```
