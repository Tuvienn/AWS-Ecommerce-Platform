# Kế hoạch Cloud-Ready: EC2 + RDS + S3

## Mục tiêu

Triển khai bản Laravel của TechShop trên AWS theo kiến trúc phù hợp với đồ án:

```text
Người dùng
    |
    v
EC2: Nginx + PHP-FPM + Laravel (DocumentRoot: public/)
    |                         \
    v                          v
RDS MySQL riêng              S3 lưu ảnh catalog
```

Website PHP cũ vẫn giữ nguyên cho đến khi Laravel được kiểm thử đầy đủ trên EC2
và có xác nhận cutover của con người.

## Nguyên tắc bắt buộc

- Staging đang dùng database Laravel riêng `aws_ecommerce_laravel_staging`; không ghi đè database PHP cũ.
- Production sẽ dùng database Laravel riêng với tên được chốt khi tạo môi trường Production.
- RDS không public; Security Group RDS chỉ nhận MySQL từ Security Group EC2.
- EC2 dùng IAM role để đọc/ghi đúng bucket S3; không đặt AWS access key vào `.env`.
- Nginx chỉ trỏ DocumentRoot tới thư mục `public/` của Laravel.
- Không đổi website cũ, DNS hoặc Nginx vhost cũ trước khi smoke test pass.
- Không xóa source/database PHP cũ nếu chưa backup và chưa có xác nhận cuối cùng.

## Thứ tự triển khai

### Change 1 — Baseline local

Chạy test, kiểm tra migration, kiểm tra các chức năng storefront/admin. Hoàn tất.

### Change 2 — Công cụ release

- `.env.production.example`: mẫu cấu hình production không chứa secret.
- `deploy/ec2/application_readiness.sh`: kiểm tra môi trường PHP, cấu hình, quyền ghi,
  database và S3 adapter.
- `deploy/ec2/deploy_application.sh`: deploy có `--dry-run`, không clone/pull Git,
  không đổi Nginx và không tác động website cũ.

### Change 3 — RDS database mới

Tạo RDS MySQL riêng cho Production, private subnet/Security Group và backup database cũ.
Staging hiện dùng `aws_ecommerce_laravel_staging`; migration và seed chỉ được chạy trên
database Laravel đã xác minh, không chạy trên database PHP cũ.

### Change 4 — S3 và IAM

Tạo bucket, gắn policy tối thiểu cho EC2 role, cài S3 Flysystem adapter, chuyển
`CATALOG_DISK=s3`, đồng bộ ảnh và xác minh URL ảnh.

### Change 5 — Deploy thử nghiệm trên EC2

Đặt source tại `/var/www/aws-ecommerce-platform-laravel`, tạo `.env` từ mẫu,
cài dependency, chạy migration/seed có kiểm soát, cache Laravel và readiness check.

### Change 6 — Smoke test AWS

Kiểm tra storefront, ảnh S3, tìm kiếm gợi ý, yêu thích, voucher, checkout COD/VietQR,
admin và log ứng dụng. Mọi lỗi giữ website cũ đang hoạt động.

### Change 7 — Phát hành GitHub

Push nhánh release, tạo backup `main`, merge nhánh Laravel vào `main` bằng merge commit,
sau đó EC2 chỉ pull đúng commit đã kiểm thử.

### Change 8 — Cutover Nginx

Sau khi Change 6 và 7 pass, thêm/cập nhật vhost Laravel trỏ `public/`, kiểm tra HTTP.
HTTPS là hạng mục riêng, chưa thực hiện trong kế hoạch này.

### Change 9 — Dọn website PHP cũ

Chỉ làm sau backup source + database và có xác nhận cuối cùng. Có rollback rõ ràng.

## Lệnh kiểm tra dự kiến trên EC2

```bash
cd /var/www/aws-ecommerce-platform-laravel
bash deploy/ec2/application_readiness.sh
bash deploy/ec2/deploy_application.sh --dry-run
```

Không chạy lệnh deploy thật, migration, seed hoặc import catalog cho đến khi RDS mới,
S3/IAM và `.env` production đã được cấu hình ở các Change tiếp theo.
