# Mapping sản phẩm và ảnh catalog

## Mục đích và phạm vi

Tài liệu này là mapping để con người duyệt trước khi import catalog. Nó không
thay đổi database, S3, ảnh runtime hay sản phẩm đang hiển thị trên website.

Nguồn dữ liệu:

- `resources/catalog/import-source/2026-11-04/chi-tiet-san-pham-full-5-brands.xlsx`
- `resources/catalog/import-source/2026-11-04/thong-tin-san-pham-da-co-full-5-brands.xlsx`
- `resources/images/catalog-source/products/incoming/new/` — 65 ảnh.
- `resources/images/catalog-source/products/incoming/existing/` — 36 ảnh.

Quy ước khi import:

- Mỗi sản phẩm có từ 1 đến tối đa 10 ảnh.
- Ảnh đầu tiên trong mỗi nhóm là ảnh chính.
- `sale_price` không có trong Excel nên mặc định bằng `0`; không tự tạo giá khuyến mãi.
- `Ready` được hiểu là sản phẩm active. Riêng Dell 15 trong Excel thiếu Status, cần xác nhận active trước khi import.
- Không xóa, archive hoặc ghi đè sản phẩm hiện có nếu chưa có mapping được duyệt.

## Sản phẩm mới: mapping đề xuất

| # | Sản phẩm | Hãng | Danh mục đề xuất | Ảnh | Trạng thái |
| --- | --- | --- | --- | --- | --- |
| 1 | iPhone 18 Promax | Apple | Điện thoại | `iphone_18_pro_max(1..3).jpg` | Sẵn sàng tạo mới |
| 2 | iPhone Duo | Apple | Điện thoại | `iphone_duo(1..3).jpg` | Sẵn sàng tạo mới |
| 3 | iPhone Air | Apple | Điện thoại | `iphone_air(1..3).jpg` | Sẵn sàng tạo mới |
| 4 | Samsung Galaxy Z Fold8 Ultra 5G 12GB 256GB | Samsung | Điện thoại | `samsung_galaxy_z_fold8_ultra(1..3).jpg` | Sẵn sàng tạo mới |
| 5 | Samsung Galaxy Tab S11 WiFi 12GB 128GB | Samsung | Máy tính bảng | `samsung_galaxy_tab_s11(1..3).jpg` | Sẵn sàng tạo mới |
| 6 | Samsung Galaxy Watch Ultra 2025 | Samsung | Đồng hồ thông minh | `samsung_galaxy_watch_ultra_2025(1..3).jpg` | Sẵn sàng tạo mới |
| 7 | Loa Bluetooth Xiaomi Sound Outdoor | Xiaomi | Âm thanh | `loa_bluetooth_xiaomi_sound_outdoor(1..3).jpg` | Sẵn sàng tạo mới |
| 8 | Loa thanh Xiaomi Soundbar Pro 2.0 | Xiaomi | Âm thanh | `loa_thanh_xiaomi_soundbar_pro(1..3).jpg` | Sẵn sàng tạo mới |
| 9 | Màn hình Gaming Xiaomi G Pro 27i Mini Led 27 inch 2K/IPS/180Hz | Xiaomi | Màn hình | `xiaomi_g_pro_27i(1..3).jpg` | Chờ duyệt category Màn hình |
| 10 | OPPO Find X10 Pro Max | OPPO | Điện thoại | `Oppo-Find-X10-Pro-Max(1..3).jpg` | Sẵn sàng tạo mới |
| 11 | OPPO Reno16 F 5G 8GB 256GB | OPPO | Điện thoại | `Oppo-Reno16-F-5G(1..3).jpg` | Sẵn sàng tạo mới |
| 12 | OPPO Find N6 16GB 512GB | OPPO | Điện thoại | `Oppo-Find-N6-16GB(1..3).jpg` | Sẵn sàng tạo mới |
| 13 | Laptop Dell 15 DC15250 CPH99 | Dell | Laptop | `Laptop-Dell-15-DC15250-CPH99(1..3).jpg` | Chờ xác nhận active vì thiếu Status |
| 14 | Màn hình Dell UltraSharp U3225QE 120Hz 32 inch | Dell | Màn hình | `ManHinh-Dell-UltraSharp-U3225QE(1..3).jpg` | Chờ duyệt category Màn hình |
| 15 | Màn hình văn phòng Dell U2424H 24 inch | Dell | Màn hình | `ManHinh-vanphong-Dell-U2424H(1..3).jpg` | Chờ duyệt category Màn hình |
| 16 | Laptop ASUS ROG Strix G16 G614JU | ASUS | Laptop | `14730014-9B03-4369-83EE-B668F348B5AA.png`, `57743_laptop_asus_gaming_rog_rog_strix_g16_9.jpg` | Sẵn sàng tạo mới; không gộp với Zephyrus hiện có |
| 17 | Laptop ASUS Zenbook 14 OLED UX3405 | ASUS | Laptop | `Laptop-Asus-Zenbook-14-OLED-UX3405MA-PP151W-6.jpg`, `zenbook_14_oled_ux3405 (2).png` | Sẵn sàng tạo mới |
| 18 | Laptop Lenovo Legion 5 15ARP8 | Lenovo | Laptop | `Legion-5-15ARP8-AMD-Ryzen-7-7735H-7.webp`, `Legion-5-15ARP8-AMD-Ryzen-7-7735H-8.webp` | Sẵn sàng tạo mới |
| 19 | Laptop Lenovo IdeaPad Slim 3 14IAH8 | Lenovo | Laptop | `25784_lenovo_ideapad_slim_3_14iah8_83eq0005vn_1.jpg`, `unnamed.webp` | Chờ duyệt ảnh `unnamed.webp` |
| 20 | Chuột không dây Logitech MX Master 3S | Logitech | Phụ kiện | `images.jpg`, `mx-master-3s-bluetooth-edition-top-view-graphite-new-1.webp` | Khả năng cập nhật sản phẩm hiện có |
| 21 | Bàn phím cơ không dây Logitech MX Mechanical Mini | Logitech | Phụ kiện | `mx-mechanical-mini-keyboard-top-side-view-graphite-us.png`, `mx-mechanical-mini-mini-keyboard-top-view-graphite-us.png` | Sẵn sàng tạo mới |
| 22 | Tai nghe chụp tai Sony WH-1000XM5 | Sony | Âm thanh | `tai-nghe-chup-tai-sony-wh-1000xm5.webp`, `tai-nghe-sony-wh1000xm5-blue-1.png` | Khả năng cập nhật sản phẩm hiện có |
| 23 | Loa Bluetooth Sony ULT FIELD 1 | Sony | Âm thanh | `SRS-ULT10-Primary_image_black.webp`, `images.jpg` | Sẵn sàng tạo mới |
| 24 | Loa Bluetooth JBL Charge 5 | JBL | Âm thanh | `JBL_CHARGE5_HERO_GREEN_0025_x2.png`, `bluetooth-jbl-charge-5-xanh-la-1.jpg` | Khả năng cập nhật sản phẩm hiện có |
| 25 | Tai nghe True Wireless JBL Tour Pro 2 | JBL | Âm thanh | `1.JBL_Tour_Pro_2_ProductImage_Hero_Black.png`, `11.JBL_Tour_Pro_2_ProductImage_Hero_2_Black.png` | Sẵn sàng tạo mới |

## Sản phẩm hiện có: mapping ảnh đề xuất

Các ảnh dưới đây nằm trong `incoming/existing/`. Tên file khớp trực tiếp với
tên catalog seed hiện tại nên có thể dùng để nâng cấp gallery của sản phẩm cũ.

| Sản phẩm hiện có | Ảnh gallery | Hành động |
| --- | --- | --- |
| iPhone 15 Pro Max 256GB | `Apple/iPhone-15-Pro-max(1..3).jpg` | Giữ sản phẩm, bổ sung 3 ảnh |
| MacBook Air M3 15 inch | `Apple/Macbook-Air-M3(1..3).jpg` | Giữ sản phẩm, bổ sung 3 ảnh |
| iPad Pro M4 11 inch | `Apple/iPad-Pro-M4(1..3)..jpg` | Giữ sản phẩm, bổ sung 3 ảnh |
| AirPods Pro 2 USB-C | `Apple/Airpods-Pro-2-USB-C(1..3).jpg` | Giữ sản phẩm, bổ sung 3 ảnh |
| Apple Watch Ultra 2 | `Apple/Apple-Watch-Ultra-2(1..3).jpg` | Giữ sản phẩm, bổ sung 3 ảnh |
| Dell XPS 14 2024 | `Dell/Dell-XPS-14-2024(1..3).jpg` | Giữ sản phẩm, bổ sung 3 ảnh |
| OPPO Find X7 Ultra | `OPPO/OPPO-Find-X7-Ultra(1..3).jpg` | Giữ sản phẩm, bổ sung 3 ảnh |
| Samsung Galaxy S24 Ultra | `Samsung/Samsung-Galaxy-S24-Ultra(1..3).jpg` | Giữ sản phẩm, bổ sung 3 ảnh |
| Samsung Galaxy Tab S9 Ultra | `Samsung/Samsung-Galaxy-Tab-S9-Ultra(1..3).jpg` | Giữ sản phẩm, bổ sung 3 ảnh |
| Samsung Galaxy Watch 6 Classic | `Samsung/Samsung-Galaxy-Watch-6-Classic(1..3).jpg` | Giữ sản phẩm, bổ sung 3 ảnh |
| Sạc nhanh Samsung 45W | `Samsung/Sac-nhanh-Samsung-45W(1..3).jpg` | Giữ sản phẩm, bổ sung 3 ảnh |
| Xiaomi 14 Ultra | `Xiaomi/Xiaomi-14-Ultra(1..3).jpg` | Giữ sản phẩm, bổ sung 3 ảnh |

## Trùng tên cần xử lý có chủ đích

Không tạo sản phẩm trùng chỉ vì tên hiển thị có thêm hoặc bớt tiền tố:

| Dòng Excel | Product hiện có | Đề xuất |
| --- | --- | --- |
| Chuột không dây Logitech MX Master 3S | Chuột Logitech MX Master 3S | Cập nhật tên, mô tả và gallery trên bản ghi hiện có |
| Tai nghe chụp tai Sony WH-1000XM5 | Sony WH-1000XM5 | Cập nhật tên, mô tả và gallery trên bản ghi hiện có |
| Loa Bluetooth JBL Charge 5 | JBL Charge 5 | Cập nhật tên, mô tả và gallery trên bản ghi hiện có |

ASUS ROG Strix G16 không phải ASUS ROG Zephyrus G16. Lenovo Legion 5 không phải
Lenovo ThinkPad X1 Carbon Gen 12. Hai cặp này phải là sản phẩm mới, không update
sản phẩm cũ.

Nếu ba cặp Logitech, Sony và JBL được duyệt là update, kết quả import dự kiến là
22 sản phẩm mới và 3 sản phẩm hiện có được cập nhật, thay vì tạo 25 bản ghi mới.

## Quyết định đã duyệt

1. Tạo category mới `Màn hình` cho ba sản phẩm màn hình Xiaomi/Dell.
2. Dùng `unnamed.webp` làm ảnh thứ hai của Lenovo IdeaPad Slim 3.
3. Update ba sản phẩm trùng tên Logitech, Sony và JBL thay vì tạo duplicate.
4. Dell 15 DC15250 CPH99 được active dù ô Status trong Excel trống.

## Kết quả Change 1

- Mapping đã được chốt và có manifest máy đọc được tại
  `resources/catalog/import-source/2026-11-04/catalog-image-manifest.json`.
- Giữ nguyên filename ảnh nguồn để bảo toàn dấu vết với dữ liệu nhóm cung cấp.
- Manifest quy định đường dẫn ảnh, thứ tự gallery và ảnh chính cho 37 thao tác
  catalog: 22 sản phẩm tạo mới, 15 sản phẩm hiện có được cập nhật ảnh.
- Chưa thay đổi database, storage runtime hoặc S3.

Change 2 đã hoàn tất trên local: category `Màn hình` và schema `product_images`
đã tồn tại. Chưa import catalog mới vào database khi backend/frontend gallery
chưa được merge.

## Chất lượng ảnh nguồn

Đã kiểm tra 101/101 ảnh trong manifest đều đọc được và có mapping. Có 57 ảnh có
cạnh ngắn dưới 600px, chủ yếu thuộc bộ `incoming/existing/` và một số ảnh OPPO,
Dell, Sony/JBL trong bộ mới. Chúng vẫn có thể import để hiển thị, nhưng có khả
năng mờ khi phóng lớn ở gallery.

Không tự phóng to bằng phần mềm vì không tạo thêm chi tiết thật. Khi nhóm có
nguồn ảnh nét hơn, chỉ cần thay đúng source mapping trước Change 4; không cần
đổi database hay sửa frontend.
