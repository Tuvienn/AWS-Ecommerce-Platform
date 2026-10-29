# PROJECT RULES

## 1. Purpose

File này định nghĩa các quy tắc bắt buộc khi con người hoặc AI Agent làm
việc với project:

**Xây dựng và triển khai hệ thống thương mại điện tử trên nền tảng
AWS.**

Mọi thay đổi đối với repository phải tuân theo các rule trong tài liệu
này.

Thứ tự ưu tiên:

``` text
Project Requirements
        ↓
docs/project/PROJECT_OVERVIEW.md
        ↓
docs/project/PROJECT_RULES.md
        ↓
PLAN_OVERVIEW.md
        ↓
Current Phase Plan
        ↓
Implementation
```

Nếu implementation hoặc đề xuất mới mâu thuẫn với tài liệu cấp cao hơn,
phải **STOP và báo cáo conflict**.

## 2. Core Development Principles

Project sử dụng workflow:

``` text
UNDERSTAND
    ↓
PLAN
    ↓
SMALL CHANGE
    ↓
RUN
    ↓
TEST
    ↓
VERIFY
    ↓
REPORT
    ↓
HUMAN REVIEW
    ↓
NEXT CHANGE / PHASE
```

Không được bỏ qua bước verification.

Code chạy được không đồng nghĩa code đúng.

AI-generated code không được mặc định là đúng.

## 3. Read Before Edit

Trước khi sửa bất kỳ file nào, Agent phải:

1.  Đọc `docs/project/PROJECT_OVERVIEW.md`.
2.  Đọc `docs/project/PROJECT_RULES.md`.
3.  Đọc `PLAN_OVERVIEW.md` nếu đã tồn tại.
4.  Xác định current phase.
5.  Đọc các file liên quan trực tiếp.
6.  Hiểu dependency giữa các file.
7.  Xác định expected behavior hiện tại.
8.  Xác định ảnh hưởng của thay đổi.

Không được sửa file chỉ dựa trên filename hoặc assumption.

## 4. Plan Before Implementation

Trước mỗi thay đổi có ảnh hưởng đến architecture, database, security,
deployment hoặc nhiều file, Agent phải trình bày:

``` text
WHAT
WHY
FILES AFFECTED
DEPENDENCIES
RISKS
TEST PLAN
EXPECTED RESULT
```

Nếu thay đổi vượt khỏi scope của current phase: **STOP và yêu cầu
approval.**

## 5. Small Changes Only

Mỗi lần implementation phải ưu tiên thay đổi nhỏ nhất có thể kiểm chứng.

Không thực hiện Security + Database + Storage + AWS + Folder migration
trong cùng một change.

Ưu tiên:

``` text
Change
   ↓
Test
   ↓
Verify
   ↓
Next Change
```

## 6. Phase Isolation Rule

Project được triển khai theo phase. Không được tự ý thực hiện task thuộc
phase sau.

Nếu phát hiện vấn đề thuộc phase khác:

``` text
DISCOVER
   ↓
DOCUMENT
   ↓
ADD TO BACKLOG / PLAN
   ↓
CONTINUE CURRENT PHASE
```

Trừ khi vấn đề là blocker hoặc security critical.

## 7. Human Approval Gates

Human approval bắt buộc trước:

-   Bắt đầu implementation sau planning.
-   Thay đổi architecture lớn.
-   Thay đổi folder structure lớn.
-   Database schema migration có tính phá vỡ.
-   Thêm AWS service mới.
-   Thêm external dependency lớn.
-   Thay framework.
-   Xóa file hoặc chức năng quan trọng.
-   Chuyển sang phase lớn tiếp theo.

Agent phải STOP tại approval gate. Không được tự hiểu im lặng là
approval.

## 8. Preserve Existing Behavior

Mục tiêu là: **Improve the existing system, not rewrite it.**

Các chức năng đang hoạt động phải được giữ nguyên trừ khi requirement
yêu cầu thay đổi.

Trước refactor:

``` text
Existing Behavior
       ↓
Record Baseline
       ↓
Refactor
       ↓
Regression Test
       ↓
Compare
```

Nếu behavior thay đổi ngoài dự kiến: **FAIL → investigate before
continuing.**

## 9. No Unnecessary Rewrite

Không được tự:

-   Rewrite toàn bộ project.
-   Chuyển framework.
-   Chuyển PHP sang ngôn ngữ khác.
-   Áp dụng full MVC nếu không cần.
-   Tạo microservices.
-   Thêm Kubernetes.
-   Thêm container orchestration.
-   Tách service chỉ để architecture trông phức tạp hơn.

Architecture phải phù hợp với quy mô đồ án.

## 10. Target Folder Structure Rule

Target architecture có thể bao gồm:

``` text
public/
app/
config/
templates/
database/
storage/
tests/
deploy/
docs/
```

Nhưng target structure không đồng nghĩa phải tạo hoặc migrate toàn bộ
ngay lập tức.

Folder/file chỉ được tạo khi phase hiện tại thực sự cần. Không tạo hàng
loạt empty classes hoặc placeholder files không có vai trò hiện tại.

## 11. Public Directory Migration Rule

`public/` là target document root.

Không được di chuyển toàn bộ web entry points sang `public/` trước khi:

1.  Baseline runtime đã PASS.
2.  URL configuration đã được chuẩn hóa.
3.  Hard-coded `/aws-ecommerce-platform` dependencies đã được xử lý.
4.  Regression test tồn tại.
5.  Migration plan được approve.

Sau migration, web server DocumentRoot phải trỏ vào `public/`. Các thành
phần ngoài `public/` không được Internet truy cập trực tiếp.

## 12. File Responsibility Rule

Target separation:

-   `public/` → HTTP entry points + public static assets.
-   `app/Core/` → shared infrastructure.
-   `app/Services/` → business logic.
-   `app/Storage/` → storage abstraction.
-   `app/Support/` → shared helpers.
-   `config/` → configuration.
-   `templates/` → presentation/layout.
-   `database/` → schema/migrations/seeds.
-   `storage/` → runtime files.
-   `tests/` → automated tests.
-   `deploy/` → machine/deployment configuration.
-   `docs/` → human-readable documentation.

Không di chuyển logic chỉ để phù hợp folder nếu không có lợi ích thực
tế.

## 13. File Change Explanation Rule

Mỗi file được tạo hoặc sửa phải được báo cáo theo format:

``` text
FILE:
PURPOSE:
WHAT CHANGED:
WHY:
ROLE IN SYSTEM:
RISK:
HOW TO TEST:
RESULT:
```

## 14. Configuration Rule

Không hard-code environment-specific values trong business logic.

Các giá trị như `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`,
`DB_PASSWORD`, `APP_ENV`, `APP_URL`, `APP_BASE_PATH`, `STORAGE_DRIVER`,
`AWS_REGION`, `S3_BUCKET` phải đến từ configuration/environment phù hợp.

Local và AWS production phải ưu tiên sử dụng **cùng codebase**, khác
configuration.

## 15. Secrets Rule

TUYỆT ĐỐI KHÔNG commit:

-   AWS Access Key.
-   AWS Secret Access Key.
-   Database production password.
-   Session secrets.
-   API secrets.
-   Private keys.
-   Real `.env`.
-   Credentials.

Repository chỉ được chứa `.env.example` với placeholder hoặc giá trị
development không nhạy cảm.

Nếu Agent phát hiện secret:

1.  STOP nếu secret đang active.
2.  Không copy secret vào report.
3.  Báo loại secret và file.
4.  Recommend revoke/rotate.
5.  Remove from tracked source theo approval.

## 16. AWS Credential Rule

Application chạy trên EC2 không được sử dụng hard-coded AWS Access Key.

Ưu tiên:

``` text
EC2
 ↓
IAM Role
 ↓
Temporary Credentials
 ↓
S3 / CloudWatch
```

Áp dụng least privilege. Không sử dụng `AdministratorAccess` cho
application chỉ vì cấu hình nhanh.

## 17. AWS Service Justification Rule

Không được thêm AWS service nếu không trả lời được:

``` text
WHAT problem does it solve?
WHY is it needed?
HOW will it be used?
WHAT does it cost?
WHAT happens if we remove it?
```

AWS services phải được phân loại `REQUIRED`, `OPTIONAL`, hoặc
`NOT NEEDED`.

MVP ưu tiên architecture đơn giản.

## 18. AWS MVP Rule

MVP dự kiến ưu tiên:

-   EC2
-   RDS MySQL
-   S3
-   VPC
-   Security Groups
-   IAM
-   CloudWatch
-   EBS
-   Internet Gateway

CloudFront, Route 53, ACM, ALB, Auto Scaling, SNS, AWS Backup và SSM chỉ
thêm khi có requirement hoặc justification rõ ràng.

Không thêm NAT Gateway nếu architecture không cần vì có thể phát sinh
chi phí đáng kể.

## 19. Cost Awareness Rule

Mọi AWS resource phải xem xét:

-   Có thực sự cần không?
-   Có Free Tier/credit phù hợp không?
-   Có resource rẻ hơn không?
-   Resource có tiếp tục tính phí khi không dùng không?
-   Có cần delete/stop sau demo không?

Không tạo resource thử nghiệm rồi bỏ quên. Các resource có khả năng phát
sinh chi phí phải được document.

## 20. Database Rule

Không chỉnh production database thủ công nếu thay đổi có thể được
version hóa.

Database changes phải đi qua `database/migrations/`.

Migration phải có mục đích rõ, có thứ tự, có test, xem xét rollback và
không làm mất dữ liệu ngoài dự kiến.

## 21. Database History Rule

Dữ liệu lịch sử thương mại điện tử, đặc biệt `Orders` và
`Order Details`, không được biến mất chỉ vì Product bị xóa, Category bị
xóa hoặc User thay đổi.

Mọi foreign key liên quan dữ liệu lịch sử phải được review trước
migration.

## 22. Transaction Rule

Các nghiệp vụ gồm nhiều database operations liên quan phải dùng
transaction khi cần.

Checkout:

``` text
Validate Cart
    ↓
Validate Price
    ↓
Validate Stock
    ↓
Create Order
    ↓
Create Order Details
    ↓
Update Stock
    ↓
COMMIT
```

Nếu bất kỳ bước quan trọng nào FAIL → `ROLLBACK`.

Không được commit partial order.

## 23. Client Data Trust Rule

Không tin dữ liệu quan trọng từ browser/session nếu có thể lấy lại từ
authoritative source.

Checkout phải xác minh lại Product exists, Current price, Current stock
và Availability từ database.

Không tin giá do client gửi.

## 24. Security First Rule

Mọi state-changing operation phải được review về:

-   Authentication.
-   Authorization.
-   CSRF.
-   Input validation.
-   Output escaping.
-   Ownership.
-   Transaction integrity.

Security finding phải được phân loại `CRITICAL`, `HIGH`, `MEDIUM`,
`LOW`.

Critical/High phải được ưu tiên theo plan.

## 25. Authentication Rule

Authentication phải đảm bảo:

-   Password hashing bằng secure password API.
-   Không lưu plain-text password.
-   Regenerate session ID sau successful login.
-   Logout invalidates session phù hợp.
-   Cookie flags được cấu hình phù hợp production.
-   Admin authorization được kiểm tra server-side.

Ẩn menu Admin không được coi là authorization.

## 26. Authorization and Ownership Rule

Mọi resource thuộc user phải kiểm tra ownership, ví dụ Order, Profile và
Uploaded files.

Không được chỉ kiểm tra `order_id` mà bỏ qua `user_id`.

Admin access phải được kiểm tra bằng role/permission server-side.

## 27. CSRF Rule

Mọi request thay đổi state phải dùng phương thức phù hợp và CSRF
protection.

Không dùng GET cho Delete, Toggle, Update, Checkout, Profile update,
Password change hoặc Admin state change.

GET chỉ dùng cho read/navigation.

## 28. Upload Security Rule

Không tin Original filename, Client MIME hoặc File extension.

Upload phải kiểm tra:

-   Actual content/type.
-   Allowed MIME.
-   Allowed extension generated server-side.
-   File size.
-   Random/server-generated filename.
-   Storage location.
-   Authorization.

Uploaded files không được thực thi như PHP.

## 29. Storage Rule

Phân biệt Static assets và Runtime uploads.

Static assets:

``` text
public/assets/
```

Runtime uploads:

``` text
Local Development → storage/uploads/
AWS Production    → Amazon S3
```

Application nên sử dụng storage abstraction khi Phase Storage được triển
khai.

## 30. S3 Rule

Production S3 mặc định:

``` text
Block Public Access = ON
```

Application truy cập S3 bằng IAM role.

Database ưu tiên lưu object key thay vì URL cố định, ví dụ:

``` text
products/2026/10/<uuid>.webp
```

## 31. Logging Rule

Không sử dụng production error output để debug trực tiếp cho user.

``` text
USER → Generic error
LOG  → Technical details
```

Logs không được chứa Password, AWS credentials, Database password,
Session secrets hoặc Sensitive tokens.

## 32. Testing Rule

Không có change nào được coi là hoàn thành chỉ vì code đã được viết.

``` text
Implementation
     ↓
Test
     ↓
Evidence
     ↓
PASS / FAIL / BLOCKED
```

## 33. Test Status Rule

Mọi test phải sử dụng một trong ba trạng thái:

-   `PASS`
-   `FAIL`
-   `BLOCKED`

Không dùng "Probably works", "Should work", hoặc "Looks correct" làm kết
luận cuối.

## 34. Baseline Rule

Trước refactor lớn phải có runtime baseline.

Phase 0 phải xác định:

-   PHP runtime.
-   MySQL runtime.
-   Schema import.
-   Current customer workflow.
-   Current admin workflow.
-   Current errors.
-   Existing security findings có thể reproduce.

Không sửa application defect trong baseline phase nếu không phải
environment blocker đã được approve.

## 35. Regression Rule

Sau mỗi phase phải kiểm tra tối thiểu:

-   Application starts.
-   Login works.
-   Products work.
-   Cart works.
-   Checkout works.
-   Admin works.

Các chức năng liên quan trực tiếp đến change phải được test sâu hơn.

## 36. Environment Blocker Rule

Phải phân biệt:

### Environment Blocker

Ví dụ PHP chưa cài, MySQL chưa chạy hoặc Required extension thiếu.

### Application Defect

Ví dụ Checkout exception, Incorrect authorization hoặc Broken SQL.

Không sửa application chỉ để che environment problem.

## 37. Dependency Rule

Không thêm dependency chỉ vì tiện.

Trước dependency mới phải xác định:

``` text
WHY needed?
WHAT does it replace?
SECURITY implications?
MAINTENANCE implications?
```

Nếu Composer được sử dụng:

-   `composer.json` được version control.
-   `composer.lock` được Composer generate.
-   Không chỉnh `composer.lock` thủ công.

## 38. Documentation Rule

Documentation phải phản ánh trạng thái thực tế.

Không document service AWS là `IMPLEMENTED` nếu mới chỉ planned.

Sử dụng trạng thái:

-   `PLANNED`
-   `IN PROGRESS`
-   `IMPLEMENTED`
-   `TESTED`
-   `BLOCKED`

## 39. Evidence Rule

Các kết luận quan trọng phải có evidence khi có thể.

Ví dụ `RDS is private`, `CloudWatch logging works`, hoặc
`Checkout race condition fixed` phải có configuration/test evidence phù
hợp.

## 40. Git Rule

Trước mỗi nhóm thay đổi chạy `git status`.

Sau khi thay đổi:

``` text
review diff
    ↓
test
    ↓
commit
```

Không commit Secrets, `.env`, Runtime logs, Temporary files, Cache,
Production uploads hoặc IDE noise.

Commit nên nhỏ và mô tả đúng thay đổi.

## 41. Delete Rule

Không xóa file chỉ vì "có vẻ không dùng".

Trước khi xóa:

1.  Search references.
2.  Xác định purpose.
3.  Kiểm tra runtime dependency.
4.  Explain why removal is safe.
5.  Approval nếu file quan trọng.

## 42. Refactor Rule

Refactor không được đồng thời thay đổi behavior nếu không cần.

Ưu tiên:

``` text
Refactor
→ Same behavior
→ Regression PASS
```

Sau đó mới:

``` text
Feature/Security Change
→ New behavior
→ Tests
```

## 43. No Silent Fix Rule

Agent không được phát hiện lỗi rồi âm thầm sửa ngoài scope.

Nếu phát hiện issue phải báo:

``` text
FINDING
SEVERITY
EVIDENCE
IMPACT
RECOMMENDATION
TARGET PHASE
```

Sau đó tiếp tục current scope nếu issue không phải blocker.

## 44. Stop Condition Rule

Agent phải STOP nếu:

-   Requirement không rõ và có thể dẫn đến architecture khác nhau.
-   Phát hiện active secret.
-   Migration có nguy cơ mất dữ liệu.
-   Test quan trọng FAIL.
-   Current phase gate chưa PASS.
-   Thay đổi cần architecture approval.
-   AWS cost vượt ngoài scope dự kiến.
-   Có conflict giữa documentation và implementation.
-   Không đủ evidence để kết luận an toàn.

Không được "code tiếp rồi sửa sau".

## 45. Phase Completion Report

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

Status được phép:

-   `PASS`
-   `FAIL`
-   `BLOCKED`
-   `PASS WITH KNOWN LIMITATIONS`

## 46. Definition of Phase Done

Một phase chỉ DONE khi:

1.  Scope hoàn thành.
2.  Tests được chạy.
3.  Không còn blocker trong scope.
4.  Regression phù hợp PASS.
5.  Documentation được cập nhật.
6.  Known limitations được ghi rõ.
7.  Human review hoàn tất nếu phase yêu cầu approval.

## 47. Final Project Principle

Mục tiêu không phải tạo architecture phức tạp nhất.

Mục tiêu là:

> **Xây dựng một hệ thống thương mại điện tử hoạt động đúng, sau đó
> triển khai nó lên AWS bằng kiến trúc đơn giản, bảo mật, có thể kiểm
> thử, có thể giải thích và phù hợp với phạm vi đồ án Cloud Computing.**

Ưu tiên:

``` text
Correctness
    ↓
Security
    ↓
Testability
    ↓
Cloud Architecture
    ↓
Maintainability
    ↓
Complexity only when justified
```

## 48. Final Agent Instruction

Khi bắt đầu bất kỳ task nào:

``` text
1. READ CONTEXT
2. IDENTIFY CURRENT PHASE
3. DEFINE SCOPE
4. INSPECT RELEVANT FILES
5. PLAN
6. IMPLEMENT SMALL CHANGE
7. TEST
8. VERIFY
9. REPORT
10. STOP AT APPROVAL GATE
```

Nếu không chắc:

> **Do not guess. Inspect first.**

Nếu chưa test:

> **Do not claim PASS.**

Nếu không thuộc current phase:

> **Document it; do not silently implement it.**

Nếu một giải pháp đơn giản đáp ứng requirement:

> **Prefer the simpler solution.**
