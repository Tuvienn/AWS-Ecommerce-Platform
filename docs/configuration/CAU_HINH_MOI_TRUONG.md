# Hướng dẫn cấu hình môi trường

## 1. Nguyên tắc

Application đọc biến môi trường bằng `getenv()` và dùng giá trị mặc định an
toàn cho local. `.env.example` là mẫu tham khảo; project **không tự động đọc
file `.env`** vì Phase 1 không thêm thư viện dotenv. Môi trường chạy (shell,
Apache, PHP-FPM hoặc dịch vụ triển khai) phải cung cấp các biến cần thiết.

Không commit `.env`, mật khẩu database, token hoặc AWS credential.

## 2. Danh sách biến

| Biến | Mặc định local | Yêu cầu production | Ý nghĩa |
|---|---|---|---|
| `APP_ENV` | `local` | Có | `local` hoặc `production` |
| `APP_URL` | `http://127.0.0.1:8000` | Có | Origin chính của application |
| `APP_BASE_PATH` | rỗng | Không | Prefix URL; để rỗng khi chạy ở document root |
| `APP_TIMEZONE` | `Asia/Ho_Chi_Minh` | Không | Timezone của PHP |
| `DB_HOST` | `localhost` | Có | MySQL/RDS host |
| `DB_PORT` | `3306` | Không | MySQL port |
| `DB_NAME` | `webbanhang` | Có | Tên database |
| `DB_USER` | `root` | Có | Database user |
| `DB_PASSWORD` | rỗng | Có, không được rỗng | Database password |
| `LOG_PATH` | `storage/logs/application.log` | Không | File nhận technical error |

## 3. Chạy local với public/ làm Document Root

Từ repository, dùng `public/` làm document root:

```bash
APP_BASE_PATH='' APP_URL=http://127.0.0.1:8000 php -S 127.0.0.1:8000 -t "/duong-dan/aws-ecommerce-platform/public"
```

Mở `http://127.0.0.1:8000/`.

Khi deploy dưới subdirectory `/aws-ecommerce-platform`, web server/reverse
proxy phải map prefix đó tới thư mục `public/` và cung cấp
`APP_BASE_PATH=/aws-ecommerce-platform`. Không dùng project root làm
DocumentRoot.

## 4. Chạy local ở document root

```bash
APP_BASE_PATH='' APP_URL=http://127.0.0.1:8001 php -S 127.0.0.1:8001 -t "/duong-dan/aws-ecommerce-platform/public"
```

Mở `http://127.0.0.1:8001/`.

## 5. Cấu hình production

Production phải cung cấp tối thiểu:

```text
APP_ENV=production
APP_URL=https://ten-mien-cua-ban.example
APP_BASE_PATH=
DB_HOST=<rds-endpoint>
DB_NAME=<database-name>
DB_USER=<database-user>
DB_PASSWORD=<secret-value>
```

Nếu thiếu database configuration bắt buộc, application dừng với thông báo
chung và ghi chi tiết kỹ thuật vào log. Web server/PHP process phải có quyền
ghi vào thư mục chứa `LOG_PATH`. Không đặt file log trong vùng được public
trực tiếp khi triển khai thực tế.

## 6. Kiểm tra nhanh sau cấu hình

- Trang chủ, CSS và JavaScript trả HTTP 200.
- Đăng nhập/đăng xuất redirect đúng base path.
- Thêm/xóa giỏ hàng và checkout hoạt động.
- Admin dashboard và CRUD dùng đúng URL.
- User không nhìn thấy exception, SQLSTATE hoặc đường dẫn nội bộ ở production.
- Runtime log không xuất hiện trong Git.
