# Báo cáo Change 6 — AWS Readiness Handoff

## Trạng thái

```text
CHANGE 6: LOCAL PREFLIGHT PASS
TRẠNG THÁI: CHỜ HUMAN REVIEW TRƯỚC AWS PROVISIONING
NGÀY: 2026-10-29
```

## Phạm vi

Change này chỉ xác nhận codebase và runtime đã sẵn sàng cho bước AWS tiếp theo.
Không tạo EC2, RDS, S3, VPC, IAM hoặc bất kỳ AWS resource nào; không phát sinh
chi phí cloud.

## Công cụ

`deploy/aws/application_readiness.sh` kiểm tra:

- Entrypoint và config bắt buộc.
- PHP >= 8.1 và extensions `pdo_mysql`, `fileinfo`, `curl`, `mbstring`.
- Quyền ghi runtime cho `storage/logs`, `storage/uploads`, `uploads/avatars`.
- Internal directories không nằm trong DocumentRoot `public/`.
- `.env` không được git track.
- PHP lint toàn bộ source.
- Catalog asset health check.

## Cách chạy local

```bash
cd "/Users/vientu/Cloud Computing/aws-ecommerce-platform"
brew services start mysql
bash deploy/aws/application_readiness.sh
brew services stop mysql
```

Script chỉ đọc cấu trúc/config, chạy lint và gọi health check; không sửa database
hay provision AWS.

## Điều kiện chuyển Phase 5 AWS

- Readiness trả `READINESS: PASS`.
- Người dùng xác nhận region, ngân sách demo, phương thức quản trị EC2 (SSH/SSM)
  và AWS account/permissions.
- Chạy riêng `deploy/aws/preflight.sh` bằng AWS CLI đã đăng nhập; script này
  cũng chỉ read-only.

## Kết luận

Kết quả chạy local: `READINESS: PASS`. Sau khi AWS account preflight và các
quyết định region/ngân sách/phương thức quản trị cùng được xác nhận, có thể lập
kế hoạch provision Phase 5. Chưa triển khai AWS trong Change 6 này.
