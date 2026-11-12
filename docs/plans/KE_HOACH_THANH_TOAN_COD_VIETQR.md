# Kế hoạch triển khai thanh toán COD và VietQR/manual

## 1. Mục tiêu

Hoàn thiện luồng đặt hàng và thanh toán cho bản Laravel của dự án theo hướng phù hợp với bài tập Cloud Computing:

- Giữ phương thức **COD** (thanh toán khi nhận hàng).
- Thêm **VietQR/chuyển khoản ngân hàng thủ công**.
- Cho phép quản trị viên xác nhận đã nhận tiền chuyển khoản.
- Không yêu cầu HTTPS, merchant account hoặc webhook từ cổng thanh toán ở giai đoạn này.

Luồng này giúp website có chức năng thanh toán thực tế ở mức demo mà không mô phỏng sai việc tích hợp MoMo hoặc VNPay thật.

## 2. Phạm vi triển khai

### Có thực hiện

1. Chốt mốc source catalog hiện tại trên nhánh Laravel riêng.
2. Chuẩn hóa phương thức thanh toán `COD` và `VietQR`.
3. Tạo đơn hàng VietQR với số tiền, nội dung chuyển khoản và URL/ảnh QR.
4. Admin xác nhận hoặc hủy thanh toán VietQR.
5. Hoàn tồn kho chính xác khi hủy đơn chưa thanh toán.
6. Cập nhật API, giao diện checkout hiện có và test tự động.

### Không thực hiện

- Không gọi API MoMo/VNPay thật.
- Không dùng webhook hoặc callback thanh toán từ bên thứ ba.
- Không triển khai HTTPS trong change này.
- Không deploy EC2/RDS/S3.
- Không redesign toàn bộ giao diện.
- Không xử lý các sản phẩm còn thiếu ảnh nguồn.

## 3. Quy ước nghiệp vụ

| Phương thức | Khi checkout | Khi admin xác nhận | Khi hủy/từ chối |
|---|---|---|---|
| COD | Đơn `Pending`, payment `Pending` | Sau khi giao hàng/thu tiền: payment `Paid` | Đơn `Cancelled`, hoàn tồn kho một lần |
| VietQR | Đơn `Pending`, payment `Pending`, trả dữ liệu QR | Payment `Paid`, đơn `Confirmed` | Payment `Cancelled` hoặc `Failed`, đơn `Cancelled`, hoàn tồn kho một lần |

Quy tắc bắt buộc:

- Tồn kho giảm đúng một lần khi tạo đơn thành công.
- Tồn kho chỉ hoàn đúng một lần khi đơn bị hủy.
- Không thể xác nhận lại payment đã `Paid`.
- Không thể xác nhận payment của đơn đã `Cancelled`.
- Thao tác xác nhận/hủy chỉ dành cho admin.

## 4. Các change triển khai

### Change 1 — Chốt mốc catalog

- Chạy PHP lint và toàn bộ test Laravel.
- Kiểm tra dữ liệu catalog: sản phẩm, biến thể, tồn kho, ảnh chính.
- Commit và push phần catalog vào nhánh `vientu-07-11-2026`.
- Không merge vào `main`.

Tiêu chí pass:

- `php artisan test` pass.
- Source sạch sau commit.
- `main` PHP không thay đổi.

### Change 2 — Loại bỏ luồng VNPay chưa phù hợp

- Gỡ route, service, request, cấu hình và test dành riêng cho VNPay sandbox.
- Cập nhật migration/schema để payment method chỉ nhận `COD` hoặc `VietQR`.
- Bảo toàn dữ liệu payment chung: trạng thái, số tiền, mã đơn, thời điểm thanh toán.
- Không để secret VNPay trong `.env.example` hoặc source.

Tiêu chí pass:

- Không còn endpoint VNPay có thể gọi được.
- Không còn cấu hình VNPay trong file mẫu.
- Migration chạy được trên MySQL.

### Change 3 — Tạo luồng VietQR khi checkout

- Thêm `VietQR` vào request validation của checkout.
- Khi checkout, backend tạo order/payment `Pending` như COD.
- Backend trả về dữ liệu chuyển khoản gồm:
  - ngân hàng;
  - số tài khoản;
  - tên chủ tài khoản;
  - số tiền;
  - nội dung chuyển khoản chứa mã đơn;
  - URL QR.
- QR được tạo từ dữ liệu cấu hình, không lưu ảnh QR tạm vào Git hoặc database.

Biến môi trường dự kiến:

```dotenv
VIETQR_BANK_CODE=
VIETQR_ACCOUNT_NO=
VIETQR_ACCOUNT_NAME=
VIETQR_TEMPLATE=compact2
```

Tiêu chí pass:

- Checkout VietQR trả dữ liệu QR hợp lệ.
- Không cần credential bên thứ ba.
- Không làm hỏng checkout COD.

### Change 4 — Admin xác nhận thanh toán VietQR

- Thêm thao tác admin xác nhận tiền đã vào tài khoản.
- Thêm thao tác admin từ chối/hủy đơn VietQR chưa thanh toán.
- Cập nhật `payments`, `orders` và `inventory` trong database transaction.
- Bảo vệ bằng middleware xác thực và quyền admin.

Tiêu chí pass:

- Xác nhận hợp lệ: `Pending` → payment `Paid`, order `Confirmed`.
- Hủy hợp lệ: order `Cancelled`, tồn kho được hoàn một lần.
- Gọi lặp không làm trạng thái/tồn kho sai.

### Change 5 — Cập nhật API và giao diện hiện có

- Checkout hiển thị COD và VietQR.
- Trang kết quả đơn VietQR hiển thị QR, số tiền và nội dung chuyển khoản.
- Admin có hành động xác nhận/từ chối rõ ràng.
- Chỉ thay đổi UI phục vụ payment; chưa redesign website.

Tiêu chí pass:

- Người dùng có thể đi trọn luồng checkout trong browser local.
- Thông điệp trạng thái dễ hiểu, không lộ thông tin cấu hình nhạy cảm.

### Change 6 — Test và nghiệm thu

Bổ sung hoặc điều chỉnh test cho:

1. Checkout COD thành công.
2. Checkout VietQR tạo order/payment `Pending`.
3. Admin xác nhận VietQR cập nhật đúng trạng thái.
4. Hủy VietQR hoàn tồn kho đúng một lần.
5. Không xác nhận lại payment đã `Paid`.
6. Không còn route/cấu hình VNPay.
7. Toàn bộ test Laravel pass.

Lệnh nghiệm thu dự kiến:

```bash
php artisan migrate:fresh --seed
php artisan catalog:import
php artisan test
php artisan route:list
```

## 5. An toàn và rollback

- Làm việc tại worktree `aws-ecommerce-platform-laravel`, nhánh `vientu-07-11-2026`.
- Không thay đổi nhánh `main` hoặc website PHP đang chạy AWS.
- Mọi thay đổi database chỉ áp dụng cho `aws_ecommerce_laravel` local trong quá trình phát triển.
- Commit Change 1 là điểm rollback trước khi chỉnh payment.
- Nếu Change 2–6 có lỗi nghiệp vụ, dừng triển khai, báo lỗi và thảo luận trước khi tiếp tục.

## 6. Điều kiện cần từ người duyệt

Trước Change 3, người duyệt cần cung cấp hoặc xác nhận thông tin VietQR dùng cho bản local/demo:

- mã ngân hàng;
- số tài khoản;
- tên chủ tài khoản.

Thông tin production sẽ không commit vào Git; chỉ đặt trên môi trường EC2/Secrets khi triển khai sau.
