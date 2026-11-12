# Kế hoạch Responsive UI: Khách hàng và Admin

## 1. Mục tiêu

Đảm bảo giao diện TechShop sử dụng được trên desktop, tablet và điện thoại mà không bị tràn ngang, cắt nút hoặc che nội dung quan trọng.

Phạm vi chỉ là giao diện. Không thay đổi API, database, nghiệp vụ giỏ hàng, đơn hàng, COD hoặc VietQR.

## 2. Kết quả audit hiện tại

### Giao diện khách hàng

- Có `meta viewport` và các breakpoint cho trang chủ, catalog, chi tiết sản phẩm, checkout.
- Catalog, product detail và checkout đã dùng grid Bootstrap/CSS có khả năng xếp dọc ở kích thước nhỏ.
- Header chưa có breakpoint: logo, ô tìm kiếm, giỏ hàng và tài khoản đang nằm chung một hàng nên có thể tràn ở điện thoại.
- Toast có chiều rộng tối thiểu 310px nhưng đang cách hai mép 24px, không phù hợp với viewport 320px.
- Bảng giỏ hàng và chi tiết đơn đã được bọc bằng `table-responsive`; đây là nền tảng đúng.

### Giao diện Admin

- Sidebar cố định rộng 260px ở mọi kích thước.
- Nội dung luôn chừa khoảng 260px bên trái.
- `public/assets/css/admin.css` chưa có breakpoint `@media` nào.
- Sáu màn hình Admin (dashboard, danh mục, sản phẩm, kho, đơn hàng, thanh toán/người dùng) có bảng nhiều cột; một số cột còn có chiều rộng cố định.
- Modal chi tiết đơn dùng `modal-xl` và có bảng 6 cột; chưa có quy tắc hiển thị cho điện thoại.

## 3. Phạm vi kích thước cần hỗ trợ

- Điện thoại nhỏ: 320px.
- Điện thoại phổ biến: 375px và 390px.
- Tablet dọc: 768px.
- Tablet ngang/laptop nhỏ: 992px.
- Desktop: từ 1200px.

## 4. Thứ tự triển khai

### Change 1 — Khung Admin đáp ứng

Mục tiêu: loại bỏ nguyên nhân tràn toàn trang Admin.

- Bổ sung breakpoint cho `admin.css`.
- Desktop từ 993px: giữ sidebar cố định 260px và bố cục hiện tại.
- Từ 992px trở xuống: sidebar không chiếm không gian nội dung mặc định; `admin-content` dùng toàn bộ chiều rộng và bỏ `margin-left`.
- Bổ sung nút mở/đóng menu Admin và lớp nền phủ khi menu mở trên mobile/tablet.
- Topbar, tiêu đề trang và các action header được phép xuống hàng, không che tài khoản hay nút thao tác.

Tệp dự kiến sửa:

- `resources/views/layouts/admin.blade.php`
- `public/assets/css/admin.css`
- `public/assets/js/admin/layout.js` (chỉ tạo nếu cần tách logic menu)

Điều kiện đạt:

- Không có cuộn ngang cấp trang tại 320px, 375px, 768px.
- Menu có thể mở, đóng và vẫn truy cập được đủ các trang Admin.

### Change 2 — Bảng, bộ lọc và modal Admin

Mục tiêu: mọi thao tác quản trị đơn/sản phẩm/thanh toán vẫn thực hiện được trên màn hình hẹp.

- Chuẩn hoá vùng `.table-responsive`: chỉ bảng cuộn ngang, không làm cả trang tràn ngang.
- Bộ lọc và nút hành động chuyển sang xếp dọc hoặc wrap tại breakpoint phù hợp.
- Footer phân trang và các hàng tiêu đề có thể wrap thay vì cắt chữ.
- Modal chi tiết đơn chuyển sang full-screen hoặc gần full-screen khi dưới 992px.
- Hai khối thông tin đơn xếp dọc trên mobile; bảng món hàng giữ cuộn ngang an toàn.
- Nút chuyển trạng thái, nút đóng và modal hủy đơn có kích thước chạm được trên mobile.

Tệp dự kiến sửa:

- `public/assets/css/admin.css`
- `resources/views/admin/orders.blade.php`
- `resources/views/admin/products.blade.php`
- `resources/views/admin/payments.blade.php`
- `resources/views/admin/categories.blade.php`
- `resources/views/admin/inventory.blade.php`
- `resources/views/admin/users.blade.php`
- `public/assets/js/admin/orders.js` (chỉ khi cần class trạng thái modal)

Điều kiện đạt:

- Modal đơn không bị cắt nội dung tại 320px và 375px.
- Các bảng có thanh cuộn ngang nội bộ khi cần; nút hành động vẫn nhìn thấy và bấm được.

### Change 3 — Header và điều hướng storefront

Mục tiêu: khách hàng tìm kiếm, vào giỏ và tài khoản được trên điện thoại mà không thu nhỏ toàn trang.

- Ở mobile, header chuyển thành layout nhiều hàng: logo và action ở hàng trên, ô tìm kiếm ở hàng riêng.
- Giảm khoảng cách/padding hợp lý; ẩn nhãn chữ không cần thiết nhưng giữ icon, badge giỏ hàng và khả năng truy cập.
- Điều hướng danh mục tiếp tục cuộn ngang trong chính thanh nav, không làm tràn trang.
- Điều chỉnh vị trí/kích thước toast để vừa viewport 320px.

Tệp dự kiến sửa:

- `public/assets/css/style.css`
- `resources/views/layouts/app.blade.php` (chỉ khi cần hook/nút điều hướng)

Điều kiện đạt:

- Header không chồng lấn tại 320px và 375px.
- Tìm kiếm, giỏ hàng, menu tài khoản, điều hướng danh mục và toast đều thao tác được.

### Change 4 — Rà soát trải nghiệm storefront và kiểm thử responsive

Mục tiêu: hoàn thiện tính nhất quán cho luồng mua hàng sau các thay đổi khung.

- Kiểm tra trang chủ, danh sách sản phẩm, chi tiết sản phẩm, giỏ hàng, checkout, trang đơn hàng và tài khoản ở năm kích thước đã nêu.
- Chỉ xử lý những điểm vỡ thực tế: padding quá lớn, nút không wrap, bảng/copy QR bị che hoặc sticky summary không phù hợp mobile.
- Không redesign màu sắc, typography hay thay đổi nội dung marketing trong change này.
- Cập nhật test giao diện thủ công bằng checklist thay vì thêm test backend không liên quan.

Tệp dự kiến sửa:

- `public/assets/css/storefront-pages.css`
- `public/assets/css/customer-pages.css`
- Các Blade customer bị phát hiện vỡ trong lúc kiểm thử
- `tests/manual/` (checklist tiếng Việt nếu thư mục đã có convention phù hợp)

Điều kiện đạt:

- Luồng xem sản phẩm → giỏ → checkout → VietQR/COD → xem đơn không có lỗi layout ở 320px, 375px, 768px, 992px và desktop.

## 5. Nguyên tắc triển khai

- Ưu tiên responsive trước redesign thẩm mỹ.
- Không dùng `transform: scale()` để “thu nhỏ cả trang”; đó chỉ che lỗi và làm chữ/nút khó bấm.
- Không thay đổi contract API, route, database hoặc logic thanh toán.
- Bảng dữ liệu được cuộn ngang trong vùng riêng thay vì ép quá nhiều cột thành chữ nhỏ.
- Dùng breakpoint nhất quán: 992px cho tablet/laptop nhỏ, 768px cho tablet/điện thoại và 576px cho điện thoại nhỏ.

## 6. Xác minh sau mỗi change

1. Mở DevTools, kiểm tra viewport 320px, 375px, 768px, 992px và desktop.
2. Xác nhận không có horizontal overflow trên `body`.
3. Kiểm tra keyboard focus, nút đóng modal, menu Admin và các nút mua hàng/thanh toán.
4. Chạy `php artisan test` để chắc chắn thay đổi Blade/asset không ảnh hưởng luồng hiện có.
5. Chạy `php artisan view:cache` để phát hiện Blade lỗi cú pháp.

## 7. Ngoài phạm vi kế hoạch này

- Redesign thương hiệu, màu sắc và banner.
- Tích hợp cổng thanh toán thật hoặc webhook VietQR.
- Thay đổi sản phẩm, ảnh, danh mục hoặc dữ liệu seed.
- Thay đổi hạ tầng AWS.
