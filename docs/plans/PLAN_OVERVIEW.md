đã# PLAN OVERVIEW

## 1. Project

**Project:** Xây dựng và triển khai hệ thống thương mại điện tử trên nền
tảng AWS

**Application:** PHP + MySQL e-commerce system

**Cloud Platform:** Amazon Web Services (AWS)

Tài liệu này định nghĩa kế hoạch triển khai tổng thể của project từ
trạng thái application hiện tại đến khi hệ thống được triển khai, kiểm
thử và trình bày hoàn chỉnh trên AWS.

------------------------------------------------------------------------

## 2. Planning Principles

Mọi phase phải tuân theo:

-   `docs/project/PROJECT_OVERVIEW.md`
-   `docs/project/PROJECT_RULES.md`
-   Current phase scope

Workflow:

``` text
UNDERSTAND
    ↓
PLAN
    ↓
IMPLEMENT SMALL CHANGE
    ↓
TEST
    ↓
VERIFY
    ↓
REPORT
    ↓
HUMAN APPROVAL
    ↓
NEXT PHASE
```

Không được tự ý nhảy phase.

Không được coi một phase là hoàn thành nếu chưa có test evidence.

------------------------------------------------------------------------

## 3. Current Project Status

  Item                              Status
  --------------------------------- -------------
  Existing PHP/MySQL application    IMPLEMENTED
  docs/project/PROJECT_OVERVIEW.md   DONE
  Static Repository Audit           DONE
  docs/project/PROJECT_RULES.md     DONE
  PLAN_OVERVIEW.md                  CURRENT
  Runtime Baseline                  PASS WITH KNOWN LIMITATIONS
  Configuration Hardening           PASS
  Security Hardening                PASS
  Database Integrity Improvements   PASS — APPROVED
  Storage Abstraction               PASS WITH KNOWN LIMITATION — APPROVED
  AWS Infrastructure                IN PROGRESS — ACCOUNT PREFLIGHT PENDING
  AWS Deployment                    NOT STARTED
  Monitoring                        NOT STARTED
  Final QA                          PHASE 3 PASS — APPROVED

Static review, Runtime Baseline và Phase 1 đã hoàn thành; Phase 2 đã được người
dùng duyệt `PASS`. Phase 3 đã hoàn thành `PASS` và được human review duyệt.
Kế hoạch chi tiết và evidence nằm tại
`docs/plans/PHASE_03_DATABASE_ORDER_INTEGRITY.md` và thư mục
`docs/testing/`. Phase 4 implementation đã được human review duyệt. Kế hoạch
Phase 5 nằm tại `docs/plans/PHASE_05_AWS_INFRASTRUCTURE.md`; Change 1 đã tạo
local preflight nhưng chưa tạo AWS resource.

Rename audit đã xác định `aws-ecommerce-platform` là technical project name;
kế hoạch và báo cáo tại `docs/plans/PROJECT_RENAME_AWS_ECOMMERCE_PLATFORM.md`
và `docs/testing/PROJECT_RENAME_AWS_ECOMMERCE_PLATFORM.md` đã `PASS`. Git
remote cũ của bên thứ ba được giữ nguyên, không push. Phase 5 AWS provision có
thể tiếp tục sau khi AWS account preflight được chuẩn bị.

Catalog/Data Repair plan nằm tại `docs/plans/PHASE_CATALOG_DATA_REPAIR.md` và
đang chờ duyệt trước khi sửa image/data. AWS provisioning và redesign UI tạm
dừng cho đến khi catalog health check đạt `PASS`.

------------------------------------------------------------------------

## 4. Known Findings Before Implementation

Static audit đã phát hiện các nhóm vấn đề chính:

### Critical / High Priority

-   Order ownership/IDOR risk tại trang xác nhận đơn hàng.
-   Chưa có CSRF protection.
-   Upload validation chưa an toàn.
-   Một số state-changing actions sử dụng GET.
-   Checkout có nguy cơ race condition về tồn kho.
-   Foreign key hiện tại có thể ảnh hưởng lịch sử đơn hàng.

### Production Readiness

-   Database configuration hard-coded cho local.
-   URL `/aws-ecommerce-platform` hard-coded.
-   Chưa có production logging.
-   Chưa có automated testing.
-   Chưa có deployment configuration.
-   Runtime uploads đang phụ thuộc local filesystem.
-   Chưa có AWS infrastructure.

Các finding này chưa được coi là runtime-confirmed cho đến khi Phase 0
kiểm chứng được những phần có thể kiểm chứng.

------------------------------------------------------------------------

# 5. Target Architecture

Target application structure:

``` text
aws-ecommerce-platform/
├── public/
├── app/
├── config/
├── templates/
├── database/
├── storage/
├── tests/
├── deploy/
├── docs/
├── docs/project/
└── README.md
```

Đây là **target structure**, không phải yêu cầu migrate toàn bộ ngay lập
tức.

Migration phải thực hiện theo từng phase và chỉ khi có test baseline.

Target AWS architecture:

``` text
                         Internet
                            |
                      HTTP / HTTPS
                            |
                         EC2
                  PHP + Web Server
                     /         \
                    /           \
             MySQL 3306       AWS API
                 |               |
              RDS MySQL       Amazon S3
                 |
          Automated Backups

Supporting Services:
- VPC
- Security Groups
- IAM Role
- CloudWatch
- EBS
- Internet Gateway
```

MVP không yêu cầu microservices, Kubernetes, ALB hoặc Auto Scaling.

------------------------------------------------------------------------

# 6. Phase Overview

``` text
PHASE 0
Runtime Baseline
      ↓
PHASE 1
Configuration & Portability
      ↓
PHASE 2
Security Hardening
      ↓
PHASE 3
Database & Order Integrity
      ↓
PHASE 4
Storage Abstraction
      ↓
──────── AWS READINESS GATE ────────
      ↓
PHASE 5
AWS Infrastructure
      ↓
PHASE 6
Database Migration & Deployment
      ↓
PHASE 7
Monitoring, Backup & Operations
      ↓
PHASE 8
Final QA, Documentation & Demo
```

------------------------------------------------------------------------

# 7. Phase 0 --- Runtime Baseline

## Objective

Xác minh trạng thái thực tế của application hiện tại trước khi sửa code.

## Scope

-   Xác nhận/cài đặt PHP runtime phù hợp.
-   Xác nhận MySQL runtime.
-   Kiểm tra PHP extensions cần thiết.
-   Import `database/schema.sql`.
-   Chạy PHP syntax/lint.
-   Start application local.
-   Kiểm thử customer workflow.
-   Kiểm thử admin workflow.
-   Ghi nhận runtime errors.
-   Kiểm chứng các static security findings có thể kiểm chứng an toàn.
-   Tạo baseline report.

## Minimum Functional Tests

``` text
Register
→ Login
→ Browse Products
→ Search / Filter
→ Product Detail
→ Add to Cart
→ Update Cart
→ Checkout
→ Order Created
→ Order History
→ Admin Login
→ Admin CRUD
→ Admin Order Management
```

## Restrictions

Trong Phase 0:

-   Không sửa business logic.
-   Không sửa security findings.
-   Không thay database schema.
-   Không refactor architecture.
-   Không triển khai AWS.

Environment blocker và application defect phải được phân biệt rõ.

## Deliverable

`docs/testing/PHASE_00_BASELINE.md`

## Gate

**PASS:** Application baseline được chạy và documented.

**BLOCKED:** Runtime/database không thể thiết lập hoặc blocker chưa được
giải quyết.

**FAIL:** Application có lỗi runtime nghiêm trọng khiến baseline flow
không thể hoàn thành.

Human review bắt buộc trước Phase 1.

------------------------------------------------------------------------

# 8. Phase 1 --- Configuration & Portability

## Objective

Loại bỏ dependency vào local machine và chuẩn bị application chạy cùng
codebase ở local và AWS.

## Scope

-   Environment-based configuration.
-   Database configuration abstraction.
-   `APP_ENV`.
-   `APP_URL`.
-   `APP_BASE_PATH`.
-   Production-safe error configuration.
-   Logging foundation.
-   `.env.example`.
-   `.gitignore`.
-   Loại bỏ hard-coded `/aws-ecommerce-platform`.
-   Chuẩn hóa timezone.
-   Chuẩn bị bootstrap/config structure.

## Important Rule

Không di chuyển toàn bộ entry points sang `public/` cho đến khi
URL/configuration đã ổn định và migration được approve.

## Gate

-   Local application vẫn chạy.
-   Không còn production credentials hard-coded.
-   URL portability hoạt động.
-   Regression tests PASS.

Human review trước Phase 2.

------------------------------------------------------------------------

# 9. Phase 2 --- Security Hardening

## Objective

Sửa các security findings có mức độ ưu tiên cao trước cloud deployment.

## Scope

### Authorization

-   Fix order ownership/IDOR.
-   Verify admin authorization server-side.

### CSRF

-   CSRF token generation.
-   CSRF validation.
-   Protect all state-changing requests.

### HTTP Method Safety

Chuyển Delete / Toggle / Update và các state-changing GET sang POST phù
hợp.

### Session

-   Regenerate session ID sau login.
-   Cookie flags.
-   Session handling phù hợp production.

### Upload Security

-   Actual MIME/content validation.
-   File size limits.
-   Server-generated filenames.
-   Safe extension handling.
-   Prevent PHP execution.
-   Authorization checks.

### Input / Output

-   Validate payment method.
-   Validate important form values.
-   Review output escaping.
-   Review banner URLs.

### Authentication

-   Basic login throttling nếu phù hợp scope.
-   Review password policy.

## Gate

Security tests cho các finding Critical/High phải PASS.

Human review trước Phase 3.

------------------------------------------------------------------------

# 10. Phase 3 --- Database & Order Integrity

## Objective

Đảm bảo dữ liệu đơn hàng, tồn kho và lịch sử giao dịch không bị sai hoặc
mất.

## Scope

-   Introduce versioned SQL migrations.
-   Review foreign keys.
-   Protect order history.
-   Review category/product deletion behavior.
-   Consider order detail snapshots.
-   Add appropriate indexes.
-   Fix checkout stock race condition.
-   Recalculate authoritative price/stock trong transaction.
-   Rollback nếu stock update thất bại.
-   Define valid order status transitions.
-   Define stock-restoration behavior khi hủy đơn.
-   Review brand normalization.

## Checkout Target Flow

``` text
BEGIN TRANSACTION
       ↓
Read authoritative products
       ↓
Validate availability
       ↓
Validate current prices
       ↓
Lock / atomic stock operation
       ↓
Create order
       ↓
Create order details
       ↓
Update stock
       ↓
COMMIT
```

Nếu bước quan trọng FAIL:

``` text
ROLLBACK
```

## Gate

-   Concurrent checkout không tạo stock inconsistency.
-   Order history được bảo vệ.
-   Migration tests PASS.
-   Regression PASS.

Human review trước Phase 4.

------------------------------------------------------------------------

# 11. Phase 4 --- Storage Abstraction

## Objective

Tách runtime uploads khỏi local public filesystem và chuẩn bị Amazon S3.

## Target

``` text
Application
     ↓
StorageInterface
   /            \
LocalStorage   S3Storage
```

## Scope

-   Storage contract/helper.
-   Local storage driver.
-   S3 storage driver hoặc S3-ready implementation theo approved scope.
-   Tách static assets và runtime uploads.
-   Product images.
-   Banner images.
-   Avatar images.
-   Object-key storage strategy.
-   Upload/read/delete behavior.
-   Migration strategy cho existing files.

## Local

``` text
storage/uploads/
```

## AWS

``` text
Amazon S3
```

Production S3 mặc định private với Block Public Access.

EC2 phải sử dụng IAM role thay vì access key trong source.

## Gate

-   Local storage workflow PASS.
-   S3-ready behavior được xác định rõ.
-   Existing uploads không bị mất.
-   Regression PASS.

------------------------------------------------------------------------

# 12. AWS Readiness Gate

Không bắt đầu production/demo AWS deployment trước khi:

-   Phase 0 PASS.
-   Phase 1 PASS.
-   Critical/High security scope của Phase 2 PASS.
-   Database/order integrity scope của Phase 3 PASS.
-   Storage strategy của Phase 4 PASS.
-   Không có secrets trong repository.
-   Local regression PASS.

Khi các điều kiện trên đạt:

``` text
APPLICATION STATUS = AWS READY
```

------------------------------------------------------------------------

# 13. Phase 5 --- AWS Infrastructure

## Objective

Tạo infrastructure tối thiểu để chạy application an toàn trên AWS.

## Required Services

-   EC2
-   RDS MySQL
-   S3
-   VPC
-   Security Groups
-   IAM
-   CloudWatch
-   EBS
-   Internet Gateway

## Scope

-   VPC/network design.
-   Public subnet cho EC2.
-   Private DB subnets cho RDS.
-   DB subnet group.
-   EC2 Security Group.
-   RDS Security Group.
-   Private RDS.
-   Private S3 bucket.
-   IAM role cho EC2.
-   EC2 instance.
-   Web server + PHP runtime.
-   CloudWatch preparation.
-   Cost review.

## Network Principle

``` text
Internet
   ↓ 80/443
EC2
   ↓ 3306
RDS
```

RDS không public.

## Gate

-   EC2 hoạt động.
-   EC2 kết nối RDS.
-   EC2 truy cập S3 bằng IAM role.
-   External host không truy cập trực tiếp RDS.
-   Security Groups được documented.
-   Cost/resources được documented.

Human review trước production application deployment.

------------------------------------------------------------------------

# 14. Phase 6 --- Database Migration & Application Deployment

## Objective

Đưa application và dữ liệu lên AWS.

## Scope

-   Backup local database.
-   Export schema/data.
-   Import vào RDS.
-   Apply migrations.
-   Deploy application lên EC2.
-   Configure production environment.
-   Configure web server.
-   Configure filesystem permissions.
-   Configure document root.
-   Migrate runtime images sang S3.
-   Smoke-test production.
-   Prepare rollback.

## Target Production Flow

``` text
User
 ↓
EC2 Application
 ├──→ RDS
 └──→ S3
```

## Gate

Customer và Admin end-to-end workflows phải PASS trên AWS.

------------------------------------------------------------------------

# 15. Phase 7 --- Monitoring, Backup & Operations

## Objective

Đảm bảo hệ thống có khả năng quan sát và phục hồi cơ bản.

## Scope

-   Application log.
-   Web server access/error log.
-   PHP error log.
-   CloudWatch Agent.
-   EC2 metrics.
-   RDS monitoring.
-   Basic alarms.
-   Log retention.
-   RDS automated backup.
-   Restore procedure.
-   Operational checklist.

## Minimum Monitoring

-   EC2 status.
-   CPU.
-   Disk usage.
-   Application errors.
-   HTTP 5xx nếu có thể thu thập phù hợp.
-   RDS CPU.
-   RDS storage.
-   RDS connections.

## Gate

Một lỗi có kiểm soát phải xuất hiện trong monitoring/logging system.

Backup/restore procedure phải được documented.

------------------------------------------------------------------------

# 16. Phase 8 --- Final QA, Documentation & Demo

## Objective

Hoàn thiện project để nộp và trình bày.

## Scope

### QA

-   Functional regression.
-   Security regression.
-   Database integrity.
-   AWS infrastructure validation.
-   Storage validation.
-   Monitoring validation.

### Documentation

-   Architecture diagram.
-   Deployment guide.
-   Database documentation.
-   Security controls.
-   Testing evidence.
-   Rollback guide.
-   Backup/restore guide.
-   Cost explanation.
-   Known limitations.

### Demo

Demo scenario đề xuất:

``` text
Customer Register
      ↓
Login
      ↓
Browse Product
      ↓
Add to Cart
      ↓
Checkout
      ↓
Order saved in RDS
      ↓
Images served through storage strategy
      ↓
Admin Login
      ↓
Admin manages order
      ↓
CloudWatch shows system activity
```

## Gate

Definition of Done được kiểm tra từng mục và final review PASS.

------------------------------------------------------------------------

# 17. Testing Strategy

## Functional

-   Register.
-   Login/logout.
-   Product browsing.
-   Search/filter.
-   Product detail.
-   Cart.
-   Checkout.
-   Order history.
-   Account update.
-   Admin CRUD.
-   Order status management.

## Security

-   SQL injection.
-   XSS.
-   CSRF.
-   IDOR.
-   Admin authorization.
-   Malicious upload.
-   MIME spoofing.
-   Double extension.
-   Oversized upload.
-   Session fixation.
-   Credential/secrets scan.

## Database

-   Transaction rollback.
-   Concurrent stock update.
-   Foreign-key behavior.
-   Order history preservation.
-   Unicode Vietnamese.
-   Backup/restore.

## AWS

-   RDS private.
-   Security Group isolation.
-   IAM role access.
-   S3 Block Public Access.
-   EC2 restart behavior.
-   CloudWatch logs.
-   CloudWatch alarms.
-   RDS backup.

## Regression

Sau mỗi phase tối thiểu:

``` text
Application starts
Login works
Products work
Cart works
Checkout works
Admin works
```

------------------------------------------------------------------------

# 18. Documentation Structure

Target documentation:

``` text
docs/
├── plans/
│   └── PLAN_OVERVIEW.md
├── testing/
│   └── PHASE_00_BASELINE.md
├── architecture/
├── deployment/
└── security/
```

Không cần tạo tất cả file tài liệu ngay từ đầu.

Tài liệu được tạo khi phase tương ứng cần.

------------------------------------------------------------------------

# 19. Deployment Scope Control

MVP không yêu cầu:

-   Microservices.
-   Kubernetes.
-   ECS/EKS.
-   Multi-region.
-   Multi-AZ application servers.
-   Application Load Balancer.
-   Auto Scaling.
-   Complex CI/CD.
-   Real payment gateway.

Các thành phần này chỉ được thêm khi requirement thay đổi và được
approve.

------------------------------------------------------------------------

# 20. AWS Cost Control

Trước khi tạo resource phải xác định:

-   Purpose.
-   Expected cost.
-   Whether it can be stopped.
-   Whether it can be deleted after demo.
-   Dependencies.

Không tạo AWS resources chỉ để thử nghiệm nếu chưa có kế hoạch sử dụng.

Không thêm NAT Gateway nếu không cần thiết.

------------------------------------------------------------------------

# 21. Phase Status Values

Mỗi phase chỉ sử dụng:

``` text
NOT STARTED
IN PROGRESS
PASS
PASS WITH KNOWN LIMITATIONS
FAIL
BLOCKED
```

Không sử dụng các kết luận mơ hồ như:

``` text
Probably done
Looks good
Should work
```

------------------------------------------------------------------------

# 22. Phase Completion Format

Cuối mỗi phase phải báo cáo:

``` text
PHASE:
STATUS:

OBJECTIVES:

FILES CREATED:
FILES MODIFIED:
FILES DELETED:

TESTS:
- Test:
  Result:
  Evidence:

ISSUES FOUND:

ISSUES FIXED:

REMAINING RISKS:

BLOCKERS:

DOCUMENTATION UPDATED:

NEXT RECOMMENDED PHASE:
```

------------------------------------------------------------------------

# 23. Project Definition of Done

Project chỉ hoàn thành khi:

-   Core e-commerce workflows hoạt động.
-   Customer workflow PASS.
-   Admin workflow PASS.
-   Critical/High security issues trong scope được xử lý.
-   Order ownership được bảo vệ.
-   CSRF được áp dụng cho state-changing requests.
-   Upload workflow an toàn.
-   Checkout đảm bảo price/stock integrity.
-   Order history không bị phá bởi product deletion.
-   Không có production/AWS credentials trong repository.
-   Local và production sử dụng cùng codebase với configuration khác
    nhau.
-   Application chạy trên EC2.
-   Production database chạy trên private RDS.
-   Runtime image storage sử dụng approved S3 strategy.
-   EC2 truy cập AWS services bằng IAM role.
-   CloudWatch nhận được monitoring/logging cần thiết.
-   Backup/restore procedure được documented.
-   End-to-end AWS test PASS.
-   Architecture diagram hoàn chỉnh.
-   Deployment guide hoàn chỉnh.
-   Testing evidence hoàn chỉnh.
-   Cost explanation hoàn chỉnh.
-   Demo scenario hoàn chỉnh.

------------------------------------------------------------------------

# 24. Immediate Next Step

Trạng thái gate hiện tại:

``` text
PHASE 3 — PASS — APPROVED
        ↓
PHASE 4 — PASS WITH KNOWN LIMITATION — APPROVED
        ↓
AWS READINESS GATE
        ↓
PHASE 5 — AWS INFRASTRUCTURE PLAN
        ↓
HUMAN REVIEW + AWS SCOPE CONFIRMATION
```

**Không bắt đầu AWS trước AWS Readiness Gate.**
