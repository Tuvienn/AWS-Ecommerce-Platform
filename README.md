# TechShop AWS E Commerce Platform

TechShop là hệ thống thương mại điện tử thiết bị công nghệ được xây dựng cho môn Cloud Computing. Ứng dụng dùng Laravel, triển khai trên AWS và tập trung thể hiện luồng website, database, object storage, network security và monitoring.

> Đây là source chính thức của project. Không đưa credentials, file `.env`, dữ liệu khách hàng hoặc AWS identifiers nhạy cảm vào repository.

## Mục tiêu

- Cung cấp storefront cho khách hàng: catalog, tìm kiếm, lọc, giỏ hàng, checkout, đơn hàng và tài khoản.
- Cung cấp khu vực Admin để quản lý catalog, tồn kho, đơn hàng, thanh toán, voucher và người dùng.
- Triển khai Laravel trên AWS bằng EC2, RDS MySQL, S3, VPC, IAM và CloudWatch.
- Vận hành demo bằng DuckDNS và HTTPS với Apache cùng Let's Encrypt.

## Kiến trúc

```text
Customer / Admin
       |
       | HTTPS
       v
DuckDNS + Apache on Amazon EC2
       |
       | Laravel 13 · Sanctum · REST APIs
       +--------------------+---------------------+
       |                    |                     |
       v                    v                     v
Amazon RDS MySQL       Amazon S3              CloudWatch
private database       catalog images          logs and EC2 metrics
```

Application là một **Modular Monolith** theo hướng `Controller -> Service -> Repository`. Blade cung cấp layout/page shell; Vanilla JavaScript gọi REST APIs để nạp dữ liệu và xử lý tương tác.

## Chức năng chính

### Customer

- Đăng ký, đăng nhập, đăng xuất và quản lý tài khoản.
- Xem danh mục, tìm kiếm, lọc, xem chi tiết sản phẩm/biến thể.
- Giỏ hàng cho khách hoặc người dùng đã xác thực; tự đồng bộ sau đăng nhập.
- Checkout COD hoặc VietQR demo, xem chi tiết và lịch sử đơn hàng.
- Yêu thích sản phẩm và nhận gợi ý sản phẩm.

### Admin

- Dashboard tổng quan.
- Quản lý danh mục, sản phẩm, biến thể, ảnh catalog và tồn kho.
- Quản lý đơn hàng, trạng thái giao hàng và thanh toán COD/VietQR.
- Quản lý voucher, người dùng, role và permission.

## Công nghệ

| Nhóm | Công nghệ |
| --- | --- |
| Backend | PHP 8.3, Laravel 13, Laravel Sanctum |
| Database | MySQL 8 / Amazon RDS MySQL |
| Frontend | Blade, Vanilla JavaScript, CSS |
| Object storage | Amazon S3 qua Flysystem AWS S3 adapter |
| Runtime | Apache trên Amazon EC2, Docker cho local development |
| Cloud | VPC, Security Groups, IAM Role, CloudWatch, AWS Billing |
| Testing | PHPUnit 12 Feature và Unit tests |

## Database

Schema được quản lý bằng Laravel migrations. Các nhóm bảng nghiệp vụ gồm:

- Identity: `roles`, `permissions`, `role_permissions`, `users`.
- Catalog: `categories`, `products`, `product_variants`, `product_images`, `variant_images`, `inventory`.
- Cart: `carts`, `cart_items`.
- Order and payment: `orders`, `order_items`, `payments`, `payment_attempts`, `order_status_histories`.
- Supporting features: `favorites`, `vouchers`, `voucher_redemptions`.

Không thay đổi schema production trực tiếp. Mọi thay đổi schema phải qua migration, review và backup/rollback phù hợp.

## Chạy local bằng Docker

### Yêu cầu

- Docker Desktop và Docker Compose.
- Git.

### Khởi động

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

Mở storefront tại `http://localhost:8000` và Admin tại `http://localhost:8000/admin`.

Các giá trị trong `.env` chỉ dành cho local. Không commit `.env` hoặc sao chép credentials từ AWS vào source.

## Testing và quality checks

Static checks có thể chạy không thay đổi dữ liệu:

```bash
composer validate --no-check-publish
php artisan route:list
find app config routes database/migrations tests -name '*.php' -print0 | xargs -0 -n1 php -l
```

Repository có Feature tests cho authentication, CSRF, Admin access, cart, checkout/VietQR, catalog image và recommendation.

> `php artisan test` có thể tạo, sửa hoặc xóa dữ liệu trong database test. Chỉ chạy sau khi xác nhận `DB_DATABASE` là database test cô lập, không phải local data quan trọng, RDS staging hoặc production.

## AWS deployment status

Các cấu hình dưới đây đã được xác minh tại thời điểm thu thập evidence:

- Amazon EC2 chạy Laravel qua Apache.
- Website phục vụ tại `https://techshop-demo.duckdns.org`; HTTP chuyển hướng sang HTTPS và certificate do Let's Encrypt phát hành.
- Amazon RDS MySQL chạy private; automated backup retention là 1 ngày.
- Bucket S3 `aws-ecommerce-storage` có Block Public Access và SSE-S3 default encryption.
- S3 Versioning đang `Disabled`; chưa có Lifecycle hoặc Replication rule.
- EC2 sử dụng IAM Role; policy S3 được giới hạn theo prefix cần thiết.
- CloudWatch có Log Groups/Log Streams của Laravel và Apache, cùng evidence metric EC2 CPU/network.

Không có Application Load Balancer, CloudFront, Multi-AZ RDS hoặc Elastic IP trong phạm vi demo hiện tại. Không khẳng định CloudWatch dashboard/alarm, load testing hoặc cost forecast nếu chưa có evidence tương ứng.

## Security principles

- Không hard-code AWS keys, password, token hoặc private key.
- RDS không public; chỉ cho phép database access từ phạm vi đã kiểm soát.
- S3 giữ Block Public Access và encryption mặc định.
- Dùng IAM Role trên EC2 thay vì long-lived access key.
- Phân quyền Admin bằng middleware và permission; session/API authentication dùng Sanctum.
- Không đưa Account ID, endpoint RDS, public IP, customer data hoặc log nhạy cảm vào tài liệu public.

## Known limitations

- Customer Account hiện có lỗi UI: trạng thái một số đơn hàng hiển thị `undefined`; không xem đây là chức năng đã PASS.
- Chưa chạy PHPUnit trên database test cô lập được xác minh trong release hiện tại.
- Chưa kiểm thử end-to-end S3 upload/delete trên AWS, load/performance test hoặc disaster-recovery restore.
- S3 Versioning và Lifecycle chưa bật để tránh phát sinh chi phí ngoài phạm vi demo.
- VietQR là luồng chuyển khoản demo; không phải payment gateway tự động xác thực giao dịch ngân hàng.

## Repository workflow

- `main` là nhánh chính thức duy nhất.
- Mọi thay đổi source, database, AWS hoặc security phải tuân thủ [working_rule.md](working_rule.md) và [PROJECT_RULES.md](docs/project/PROJECT_RULES.md).
- Trước release/deploy: review diff, kiểm tra secrets, chạy validation phù hợp và xác nhận rollback.
- Evidence AWS và báo cáo học phần được quản lý ngoài Git source để tránh làm repository nặng hoặc chứa thông tin nhạy cảm.

## Project scope

Project ưu tiên demonstrable cloud architecture và các luồng e-commerce cốt lõi. Các hướng phát triển như CI/CD, CloudFront, ALB, Auto Scaling, dashboard/alarm hoàn chỉnh, Multi-AZ và backup/restore drills chỉ được thực hiện khi có yêu cầu, ngân sách và kế hoạch được duyệt.
