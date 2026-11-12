# Kế hoạch redesign trang chủ TechShop theo hướng Editorial

## 1. Mục tiêu

Redesign trang chủ TechShop theo phong cách kể chuyện sản phẩm tối giản, giàu hình ảnh và có điểm nhấn như các website công nghệ cao cấp.

Nguồn cảm hứng là nguyên tắc thiết kế: một thông điệp chính, ảnh sản phẩm rõ ràng, khoảng trắng hợp lý và lời kêu gọi hành động ngắn gọn. Không sao chép logo, ảnh, câu chữ, bố cục chi tiết hoặc nhận diện của Apple.

## 2. Phạm vi

Trong phạm vi:

- Trang chủ khách hàng (`/`).
- Hero quảng cáo, campaign card, danh mục, các dải sản phẩm đã có API và các khối tạo niềm tin.
- Responsive cho mobile/tablet/desktop.
- Animation nhẹ có tôn trọng thiết lập giảm chuyển động của người dùng.

Ngoài phạm vi:

- Thay đổi database, API catalog, giỏ hàng, checkout, COD hoặc VietQR.
- CMS/banner management trong Admin.
- Redesign toàn bộ trang catalog, chi tiết sản phẩm và dark mode. Các mục đó là phase riêng.
- Sử dụng ảnh, logo hay nội dung thuộc Apple hoặc bên thứ ba khi chưa có quyền dùng.

## 3. Hiện trạng cần thay đổi

- Hero hiện là carousel 3 slide tự chuyển sau 4 giây.
- Nội dung hero đang ghi cứng theo một số sản phẩm và có fallback đến ảnh URL ngoài.
- Không có thư mục asset banner marketing cục bộ trong `public/assets/images`.
- Các dải sản phẩm hiện hữu đã lấy đúng từ Catalog API: giảm giá, mới nhất và cao cấp.

## 4. Hướng thiết kế được chọn

### Nguyên tắc

- Một hero chỉ tập trung một chiến dịch hoặc một sản phẩm tiêu biểu.
- Mỗi block có một mục đích: khám phá, xem sản phẩm, hoặc mua hàng.
- CTA chuẩn: `Xem chi tiết` và `Mua ngay`; không đưa quá hai CTA cấp chính trong cùng một hero.
- Dùng ảnh sản phẩm thật của catalog hoặc asset do nhóm sở hữu/quyền sử dụng.
- Không làm carousel tự chạy. Người dùng chủ động chuyển nội dung nếu có nhiều campaign.
- Không hiển thị số liệu, ưu đãi, bảo hành, tốc độ giao hàng hay tồn kho không được hệ thống/policy chứng minh.

### Cấu trúc trang chủ sau redesign

1. **Hero spotlight**
   - Một sản phẩm/chương trình được chọn.
   - Nhãn ngắn, headline, mô tả một câu, giá từ (nếu lấy được từ catalog), hai CTA.
   - Ảnh nền sạch, ưu tiên bố cục desktop và mobile riêng.

2. **Campaign card phụ**
   - Hai card nhỏ dưới hero cho danh mục có dữ liệu thật, ví dụ Laptop và Phụ kiện.
   - Mỗi card chỉ có một CTA dẫn về trang lọc sản phẩm tương ứng.

3. **Khám phá theo danh mục**
   - Giữ dữ liệu danh mục từ API hiện tại.
   - Rút gọn card, tăng khoảng trắng và trạng thái hover/focus rõ ràng.

4. **Bộ sưu tập sản phẩm**
   - Giữ ba nguồn dữ liệu hiện có: Giảm giá sốc, Mới nhất, Cao cấp nổi bật.
   - Đồng bộ heading, link “Xem tất cả”, skeleton và empty state.

5. **Mua sắm an tâm tại TechShop**
   - Bốn quyền lợi dựa trên khả năng có thật: COD/VietQR, theo dõi đơn, quản lý tồn kho và hỗ trợ khách hàng.
   - Chỉ đưa chính sách giao hàng/bảo hành sau khi nhóm đã xác nhận nội dung đó.

6. **Hướng dẫn mua hàng ngắn**
   - Chọn sản phẩm → thêm giỏ → chọn COD/VietQR → theo dõi đơn.
   - Không tuyên bố đối soát tự động hoặc thanh toán tức thì vì VietQR hiện được Admin xác nhận thủ công.

7. **FAQ tối giản**
   - Ba đến năm câu hỏi bám theo chức năng thật: COD, VietQR, hủy đơn, theo dõi đơn, hỗ trợ.

## 5. Asset và nội dung cần chuẩn bị

### Asset bắt buộc trước Change 2

- Một ảnh hero desktop tối thiểu 1600 × 900 px.
- Một ảnh hero mobile tối thiểu 900 × 1200 px, hoặc xác nhận ảnh desktop có thể crop an toàn.
- Hai ảnh campaign phụ tối thiểu 900 × 600 px.
- Bảng mapping nội bộ: tên file → sản phẩm/danh mục → nguồn/quyền sử dụng.

### Quy tắc asset

- Ảnh banner marketing đặt tại `public/assets/images/marketing/` khi triển khai.
- Ảnh sản phẩm bán hàng vẫn dùng qua storage/catalog hiện tại, không copy sang thư mục marketing.
- Không dùng fallback ảnh ngẫu nhiên từ Unsplash trong bản bàn giao.
- Nếu chưa có asset phù hợp, dùng layout nền màu/gradient của TechShop kết hợp ảnh sản phẩm thật, không tạo quảng cáo sai sản phẩm.

## 6. Kế hoạch triển khai

### Change 1 — Chuẩn hóa contract campaign và nội dung thật

Mục tiêu: tách nội dung marketing khỏi phần layout trước khi viết giao diện.

- Chọn tối đa ba campaign, sản phẩm hoặc danh mục có thật trong database.
- Chốt headline, mô tả, CTA và URL đích cho từng campaign.
- Tạo mapping asset/source; xác nhận hình ảnh được phép dùng.
- Loại bỏ fallback ảnh bên ngoài trong hero cũ khi campaign mới sẵn sàng.
- Quy định rõ campaign thiếu ảnh sẽ không được bật.

Tệp dự kiến sửa/tạo:

- `resources/views/customer/home.blade.php`
- `public/assets/js/pages/home.js`
- `public/assets/images/marketing/` (chỉ sau khi có ảnh được duyệt)
- Tệp cấu hình campaign cục bộ nếu cần giữ dữ liệu tách khỏi Blade.

Điều kiện đạt:

- Mỗi banner dẫn tới một URL hợp lệ trong TechShop.
- Không dùng asset Apple/Unsplash hoặc thông điệp không đúng thực tế.

### Change 2 — Hero spotlight và campaign card

Mục tiêu: thay carousel cũ bằng khu vực quảng cáo tập trung, hiện đại và có thể đọc nhanh.

- Thay hero auto-slide bằng một spotlight tĩnh có CTA rõ ràng.
- Xây dựng card campaign phụ cho hai danh mục.
- Dùng responsive image/crop phù hợp desktop và mobile.
- Thêm focus state cho liên kết/nút; không làm text đè lên vùng ảnh khó đọc.
- Không dùng animation tự chạy; animation chỉ xảy ra khi người dùng tương tác hoặc block đi vào viewport.

Tệp dự kiến sửa:

- `resources/views/customer/home.blade.php`
- `public/assets/css/storefront-pages.css`
- `public/assets/js/pages/home.js`

Điều kiện đạt:

- Hero hiển thị đúng tại 320px, 375px, 768px, 992px và desktop.
- CTA mở đúng trang catalog hoặc trang sản phẩm.
- Không phát sinh horizontal overflow.

### Change 3 — Biên tập lại các dải catalog và trust section

Mục tiêu: nối trải nghiệm quảng cáo với danh mục và sản phẩm thật.

- Đồng nhất heading, khoảng cách section và link “Xem tất cả”.
- Giữ nguyên ba lời gọi Catalog API hiện có; không tạo API mới.
- Làm mới section danh mục và trust section theo visual system của hero.
- Thêm hướng dẫn mua hàng ngắn và FAQ bám theo COD/VietQR hiện tại.
- Kiểm tra empty state để trang chủ không có khoảng trống xấu khi dữ liệu ít.

Tệp dự kiến sửa:

- `resources/views/customer/home.blade.php`
- `public/assets/css/storefront-pages.css`
- `public/assets/js/pages/home.js`

Điều kiện đạt:

- Catalog vẫn nạp đúng dữ liệu giảm giá, mới nhất và cao cấp.
- Nội dung trust/FAQ không hứa hẹn vượt quá chức năng thực tế.

### Change 4 — Motion, accessibility và kiểm thử

Mục tiêu: hoàn thiện chất lượng trình bày mà không hy sinh hiệu năng hoặc khả năng truy cập.

- Thêm transition nhẹ cho image/card/CTA; tắt hoặc giảm khi `prefers-reduced-motion` được bật.
- Bảo đảm contrast, focus keyboard, alt text và thứ tự heading hợp lý.
- Lazy-load ảnh bên dưới màn hình đầu tiên; ảnh hero có kích thước và loading strategy phù hợp.
- Kiểm tra Lighthouse thủ công nếu môi trường có trình duyệt.
- Kiểm tra liên kết CTA, responsive và lỗi ảnh 404.

Tệp dự kiến sửa:

- `public/assets/css/storefront-pages.css`
- `resources/views/customer/home.blade.php`
- `public/assets/js/pages/home.js`

Điều kiện đạt:

- Không có auto-rotation gây khó chịu.
- Không có ảnh ngoài không kiểm soát hay 404 trong trang chủ.
- `php artisan view:cache`, JavaScript syntax check và `php artisan test` đều pass.

## 7. Quyết định chưa chốt

Trước Change 1, người duyệt cần chọn một trong hai hướng asset:

1. Dùng ảnh marketing do nhóm tự thiết kế/chụp và đặt vào `public/assets/images/marketing/`.
2. Dùng ảnh sản phẩm thật từ catalog trên nền CSS của TechShop; không cần banner ảnh riêng.

Khuyến nghị: chọn hướng 2 trước để hoàn thành giao diện bằng dữ liệu đã có; sau đó thay asset hero khi nhóm có ảnh marketing chất lượng cao.

## 8. Liên hệ với các phase UI khác

- Dark mode là phase độc lập; các class mới ở trang chủ phải ưu tiên token CSS để dễ hỗ trợ theme sau này.
- Redesign trang chi tiết sản phẩm được thực hiện sau khi gallery 2–10 ảnh và asset catalog ổn định.
- So sánh sản phẩm chỉ bắt đầu khi specification giữa các danh mục đã được chuẩn hóa.
