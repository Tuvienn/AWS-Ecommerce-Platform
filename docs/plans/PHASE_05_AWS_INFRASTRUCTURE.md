# Kế hoạch Phase 5 — AWS Infrastructure

## 1. Trạng thái

```text
PHASE: Phase 5 — AWS Infrastructure
TRẠNG THÁI KẾ HOẠCH: ĐÃ NHẬN LỆNH BẮT ĐẦU — CẦN AWS SCOPE CONFIRMATION
TRẠNG THÁI IMPLEMENTATION: CHANGE 1 LOCAL PREPARATION PASS — AWS ACCOUNT PREFLIGHT PENDING
NGÀY LẬP: 2026-10-28
```

Phase 4 đã được human review duyệt. Change 1 đã tạo local preflight/cost guard;
chưa tạo AWS resource, chưa đăng nhập AWS Console, chưa cài AWS CLI và chưa
phát sinh chi phí cloud.

## 2. Mục tiêu

Tạo nền infrastructure tối thiểu, an toàn và có thể kiểm tra để Phase 6 deploy
application PHP cùng codebase hiện tại lên AWS.

```text
Internet
   │ HTTPS/HTTP
   ▼
EC2 (public subnet)
   ├── IAM role ─────────► S3 private bucket
   └── MySQL 3306 ───────► RDS MySQL (private DB subnet)
                              │
                          Automated backup
```

## 3. Quyết định cần người dùng duyệt

| Quyết định | Đề xuất MVP | Lý do |
| --- | --- | --- |
| AWS Region | `ap-southeast-1` | Gần Việt Nam; chỉ áp dụng sau khi account xác nhận region hỗ trợ/dung lượng. |
| EC2 | Một instance nhỏ, EBS gp3 20 GiB | Đủ demo, giảm chi phí; không có HA. |
| RDS | MySQL private, single-AZ, dung lượng nhỏ | Phù hợp demo; không dùng public DB. |
| Domain/HTTPS | Tạm dùng EC2 public DNS/IP; HTTPS ở phase sau khi có domain | Không tạo domain/ACM/ALB khi chưa có requirement. |
| SSH | Chỉ CIDR IP quản trị cụ thể hoặc SSM nếu được duyệt | Không mở SSH cho toàn Internet. |
| S3 | Bucket private, Block Public Access ON | Runtime upload không public mặc định. |

Không provision trước khi người dùng xác nhận region, ngân sách, cách quản trị
EC2 (SSH/SSM) và AWS account có quyền phù hợp.

## 4. Service justification

| Service | Phân loại | Vai trò | Nếu bỏ đi |
| --- | --- | --- | --- |
| VPC, subnet, route table, IGW | REQUIRED | Tách network public/private | Không thể kiểm soát network an toàn. |
| EC2, EBS | REQUIRED | Chạy PHP/web server | Không có runtime application. |
| RDS MySQL | REQUIRED | Database production private | Không có database managed tách khỏi EC2. |
| S3 | REQUIRED | Runtime uploads bền vững | Không đạt storage strategy Phase 4 trên AWS. |
| IAM role | REQUIRED | EC2 truy cập S3 không dùng key | Phải dùng credential tĩnh, không chấp nhận. |
| CloudWatch cơ bản | REQUIRED | Metrics/log baseline | Khó quan sát lỗi/hết disk. |
| NAT Gateway | NOT NEEDED | Không cần cho MVP | Tăng chi phí cố định. |
| ALB, Auto Scaling | NOT NEEDED | Không cần HA MVP | Tăng độ phức tạp/chi phí. |
| Route 53, ACM | OPTIONAL | Chỉ cần khi có domain/HTTPS | Không có domain vẫn demo được. |

## 5. Thiết kế network

- Một VPC CIDR đề xuất `10.0.0.0/16`.
- Một public subnet cho EC2 và Internet Gateway route.
- Hai private DB subnets ở hai Availability Zone cho RDS DB subnet group.
- RDS `PubliclyAccessible = false`.
- Không NAT Gateway trong MVP; private DB subnet không cần outbound Internet.

### Security Group

| Security Group | Inbound | Outbound |
| --- | --- | --- |
| `webbanhang-ec2-sg` | 80/443 từ Internet; 22 chỉ từ admin CIDR nếu chọn SSH | 443 Internet cho package/S3/CloudWatch; 3306 tới RDS SG |
| `webbanhang-rds-sg` | 3306 chỉ từ `webbanhang-ec2-sg` | Default outbound theo managed service |

Không mở port 3306 ra Internet. Không mở SSH `0.0.0.0/0`.

## 6. IAM và S3

- EC2 instance profile có least-privilege policy chỉ với bucket Phase 5:
  `s3:GetObject`, `s3:PutObject`, `s3:DeleteObject`, `s3:ListBucket` trên
  prefix cần thiết.
- Không cấp `AdministratorAccess` cho application role.
- S3 Block Public Access ON, Object Ownership bucket-owner enforced,
  encryption mặc định SSE-S3 hoặc KMS theo account policy.
- Bucket policy không public; application đọc private object qua S3 driver/gateway
  strategy đã thiết kế ở Phase 4.

## 7. Trình tự implementation

### Change 1 — Account preflight và cost guard

- Xác minh region, billing alert/budget, MFA và quota cần thiết.
- Xác minh danh tính provisioner không phải access key hard-code trong repo.
- Ghi resource naming/tagging convention.

### Change 2 — Network foundation

- Tạo VPC, IGW, public subnet, hai private DB subnets, route table và DB subnet group.
- Không tạo NAT Gateway.

### Change 3 — Security groups và IAM

- Tạo EC2/RDS SG với source-reference.
- Tạo EC2 IAM role/instance profile và S3 least-privilege policy.

### Change 4 — S3 private storage

- Tạo unique private bucket, Block Public Access, encryption, lifecycle tối thiểu.
- Verify không public list/read từ external principal.

### Change 5 — RDS private MySQL

- Tạo RDS trong DB subnet group, private only, backup retention, deletion protection
  theo quyết định cost/demo.
- Chỉ lưu endpoint/password trong secret manager hoặc host environment, không repo.

### Change 6 — EC2 baseline

- Launch EC2 trong public subnet với EBS encryption và security group.
- Cài web/PHP runtime; dùng IAM role để xác minh S3 access.
- Chưa deploy application/data production trong Phase 5.

### Change 7 — Verification, cleanup và handoff

- Xác minh EC2 → RDS, EC2 → S3 IAM role, external → RDS blocked.
- Inventory resource ID, tags, region, cost risk và rollback/destroy order.
- Tạo Phase 5 evidence bằng tiếng Việt.

## 8. Rollback và cost control

- Provision theo tag `Project=aws-ecommerce-platform`, `Environment=demo`, `Phase=5` để
  inventory/destroy có scope rõ ràng.
- Không xóa RDS snapshot/data khi chưa có approval riêng.
- Thứ tự cleanup dự kiến: EC2 → EBS snapshot không cần thiết → RDS (final
  snapshot theo policy) → S3 chỉ sau inventory/backup → network/IAM policy.
- Sau mỗi buổi demo, kiểm tra instance/RDS/bucket còn hoạt động và billing.

## 9. Tiêu chí hoàn thành

- EC2 reachable qua phương thức quản trị được duyệt.
- RDS private, EC2 kết nối được RDS; external host không kết nối RDS.
- EC2 truy cập được S3 qua IAM role; không có AWS key trong repository.
- Security Group, resource tags, region và cost risk được document.
- Chưa deploy application/database production trong Phase 5.
- Human review trước Phase 6.

## 10. Approval gate

Chỉ sau khi người dùng duyệt kế hoạch và cung cấp/xác nhận các quyết định ở
mục 3, mới bắt đầu provision AWS. Việc user duyệt kế hoạch là authorization cho
setup; tạo resource thực tế vẫn chỉ được thực hiện trong AWS account/scope mà
người dùng cung cấp.

