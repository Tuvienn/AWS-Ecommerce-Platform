# AWS preflight — Phase 5 Change 1

## Mục đích

Các file trong thư mục này chỉ chuẩn bị preflight AWS. Chúng không chứa AWS
access key, database password hoặc resource ID và không tự tạo resource.

## Chuẩn bị ngoài repository

- AWS account có MFA và quyền phù hợp theo least privilege.
- AWS CLI được cài và đăng nhập bằng SSO/profile hoặc temporary credential.
- Xác nhận region, ngân sách demo và CIDR IP quản trị.

## Chạy preflight

Từ thư mục project, copy giá trị placeholder sang shell environment, không tạo
file `.env` có secret trong repository:

```bash
export AWS_REGION=ap-southeast-1
export PROJECT_NAME=aws-ecommerce-platform
export PROJECT_ENVIRONMENT=demo
export ADMIN_CIDR=<IP_CONG_KHAI_CUA_BAN>/32
bash deploy/aws/preflight.sh
```

Script chỉ gọi `sts get-caller-identity` và `ec2 describe-availability-zones`.
Hai lệnh đều read-only; provision chỉ được bắt đầu sau khi preflight pass và
người dùng duyệt scope/cost.

## Cost guard

- Không tạo NAT Gateway trong MVP.
- Không dùng ALB, Auto Scaling, Route 53 hoặc ACM khi chưa có requirement.
- Trước resource có chi phí, xác nhận `COST_APPROVED=true` ở phiên
  shell tương ứng; không lưu giá trị nhạy cảm trong repository.

