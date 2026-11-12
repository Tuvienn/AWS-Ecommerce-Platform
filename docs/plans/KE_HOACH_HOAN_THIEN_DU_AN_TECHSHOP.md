# KẾ HOẠCH HOÀN THIỆN DỰ ÁN TECHSHOP

## 1. Mục tiêu

Hoàn thiện **AWS E-commerce Platform – TechShop** bằng Laravel để phục vụ báo cáo, thuyết trình và demo môn Cloud Computing.

Kết quả cuối cùng cần đạt:

- Laravel trở thành source chính thức trên nhánh `main`.
- Website hoạt động ổn định qua HTTPS.
- Các luồng Customer, Admin, RDS và S3 được kiểm thử.
- Hạ tầng AWS được kiểm chứng về kiến trúc, bảo mật, giám sát, backup và chi phí.
- Website PHP cũ được bảo toàn rồi ngừng sử dụng an toàn.
- Có stable release, báo cáo, slides và evidence package phục vụ demo.

Trọng tâm của bài là **Cloud Architecture, AWS Services, Deployment, Security, Monitoring và sự tương tác giữa các dịch vụ**. Không mở rộng thêm chức năng ngoài phạm vi cần thiết.

## 2. Trạng thái đã xác minh

### 2.1. AWS Laravel Staging

- Region: `ap-southeast-1`.
- EC2: `i-0e1515c3536b1a0f3`.
- URL hiện tại: `http://47.128.144.220`.
- Application path: `/var/www/aws-ecommerce-laravel-staging`.
- Web server: Apache `2.4.69` và PHP-FPM.
- Laravel đã kết nối RDS MySQL và Amazon S3.
- Change 5 – CSRF HTTP 419 Fix đã được triển khai thành công.
- Homepage, health check và public APIs cốt lõi đang hoạt động.

### 2.2. HTTPS

- Chưa có Elastic IP.
- Chưa mở inbound port `443` trên Security Group Staging.
- Chưa cài `mod_ssl`.
- Apache chỉ lắng nghe port `80`.
- Chưa có HTTPS VirtualHost và certificate.
- Phương án dự kiến: `Domain → DNS → Elastic IP → EC2 → Apache HTTPS → Laravel`.

### 2.3. Git

- Branch Local: `vientu-07-11-2026`.
- Local có các commit Laravel đang chờ push lên remote branch cùng tên.
- Branch Laravel diverge với `origin/main`; `main` vẫn là baseline PHP cũ và phải được bảo toàn trước khi tích hợp.
- Change 5, bảo vệ Admin, S3 catalog image và regression tests đã được đưa vào Git history Local.
- Phải kiểm tra working tree sạch và test PASS trước Change 1.5.

### 2.4. Legacy PHP

- Website PHP cũ: `13.229.217.163`.
- Chưa kiểm kê đầy đủ resource dùng riêng và resource dùng chung với Laravel.
- Chưa được phép terminate hoặc xóa resource.

## 3. Nguyên tắc triển khai

1. Thực hiện theo từng Phase và từng Change.
2. Trước mỗi Phase phải có kế hoạch chi tiết và được Human phê duyệt.
3. Hoàn thành một Change phải tự review và kiểm thử trước khi chuyển Change tiếp theo.
4. Nếu phát hiện lỗi, conflict hoặc rủi ro ngoài kế hoạch thì dừng và báo cáo.
5. Không force-push hoặc ghi đè `main`.
6. Không sửa Production, RDS, S3 hoặc dữ liệu thật nếu chưa có phê duyệt riêng.
7. Không terminate EC2 hoặc xóa AWS resource trong quá trình audit.
8. Không hiển thị `.env`, credentials, token, cookie hoặc dữ liệu bí mật.
9. Mọi thao tác deploy phải có backup, validation và rollback.
10. Evidence phải được thu thập ngay trong từng Phase.

## 4. Mức độ ưu tiên

- **P0 – Bắt buộc:** Blocker của release, deploy, bảo mật hoặc demo.
- **P1 – Quan trọng:** Cần hoàn thành để project ổn định và có bằng chứng đầy đủ.
- **P2 – Tùy chọn:** Chỉ thực hiện nếu còn thời gian và không ảnh hưởng mục tiêu chính.

## 5. Phase 0 — Release Inventory & Scope Freeze

### 5.1. Mục tiêu

Xác định chính xác source, feature, Git state và AWS resource thuộc phiên bản Laravel cuối cùng trước khi tiếp tục thay đổi.

### 5.2. Priority

`P0`

### 5.3. Dependencies

Không có.

### 5.4. Changes

#### Change 0.1 — Kiểm kê working tree

- Phân loại toàn bộ file modified/untracked theo feature.
- Xác định file thuộc Change 5, Admin Web Access, S3, catalog image, frontend và deployment.
- Phát hiện file sinh tự động, file tạm hoặc secret không được commit.

#### Change 0.2 — Chốt phạm vi chức năng demo

- Chốt các luồng Customer và Admin bắt buộc.
- Chốt COD và VietQR trong phạm vi demo.
- Không phát triển thêm payment gateway thật, chatbot hoặc feature ngoài yêu cầu.

#### Change 0.3 — Kiểm kê AWS resource

- Lập danh sách EC2, RDS, S3, IAM, Security Groups, VPC và subnet.
- Đánh dấu resource Laravel, resource PHP cũ và resource dùng chung.
- Không thay đổi hoặc xóa resource.

### 5.5. Deliverables

- Source Inventory.
- Feature Scope.
- AWS Resource Inventory.
- Danh sách file cần commit, loại trừ hoặc xử lý riêng.

### 5.6. Acceptance Criteria

- Mọi thay đổi trong working tree đều được phân loại.
- Không còn file không rõ nguồn gốc trong release scope.
- Biết rõ AWS resource nào không được phép dừng hoặc xóa.

## 6. Phase 1 — Code Stabilization & Git Main Migration

### 6.1. Mục tiêu

Ổn định code Laravel, hoàn tất regression và đưa Laravel thành source chính thức trên `main` mà không làm mất lịch sử PHP cũ.

### 6.2. Priority

`P0`

### 6.3. Dependencies

- Phase 0 PASS.
- Feature scope đã được chốt.

### 6.4. Changes

#### Change 1.1 — Regression source audit

- Audit Authentication, Authorization và CSRF.
- Audit Admin Products và Product Images.
- Kiểm tra Upload, Delete và Set Primary Image.
- Kiểm tra middleware và permission `admin.products`.

#### Change 1.2 — Automated tests

- Chạy PHP lint.
- Chạy targeted tests cho CSRF, Admin và Product Images.
- Chạy toàn bộ test suite Local.
- Không kết nối hoặc thay đổi database Production.

#### Change 1.3 — Log audit

- Kiểm tra Laravel, Apache và PHP-FPM logs sau Change 5.
- Chỉ tổng hợp exception, HTTP 5xx, permission error, RDS/S3 error.
- Không hiển thị dữ liệu nhạy cảm.

#### Change 1.4 — Chuẩn hóa commit

- Tách thay đổi theo feature.
- Tạo commit riêng cho CSRF HTTP 419 và regression tests.
- Không dùng `git add .` khi working tree còn nhiều feature trộn lẫn.
- Review diff trước mỗi commit.

#### Change 1.5 — Đồng bộ Laravel branch

- Kiểm tra toàn bộ commit Local chưa push.
- Push Laravel branch sau khi test PASS và Human duyệt.
- Không push trực tiếp vào `main` ở bước này.

#### Change 1.6 — Bảo toàn PHP cũ

- Tạo nhánh backup `legacy-php` từ đúng `origin/main` hiện tại.
- Xác minh branch backup có thể checkout và khôi phục.

#### Change 1.7 — Tích hợp Laravel thành main

- Review commit riêng của `origin/main`.
- Tạo integration branch từ Laravel đã ổn định.
- Xử lý divergence mà không đưa source PHP cũ trở lại cây Laravel.
- Chạy test và smoke test trên integration branch.
- Chỉ chuyển `main` bằng fast-forward hoặc branch migration đã duyệt.
- Không force-push.

### 6.5. Deliverables

- Regression Report.
- Test Matrix.
- Clean Laravel integration branch.
- Git Migration Report.
- Nhánh `legacy-php`.
- GitHub `main` chứa source Laravel đã kiểm chứng.

### 6.6. Acceptance Criteria

- Không còn lỗi P0 chưa xử lý.
- Full tests PASS hoặc các test bị block được giải thích rõ.
- Change 5 đã nằm trong Git history.
- GitHub `main` chứa đúng Laravel.
- PHP cũ vẫn được bảo toàn trong branch backup.
- Các thành viên được hướng dẫn đồng bộ branch mới.

## 7. Phase 2 — Domain, HTTPS & Runtime Hardening

### 7.1. Mục tiêu

Đưa Staging từ Public IP HTTP sang hostname/domain HTTPS và bảo đảm Laravel session/Sanctum hoạt động đúng.

### 7.2. Priority

`P0`

### 7.3. Dependencies

- Phase 1 PASS.
- Có quyền quản lý domain/DNS.
- Có phê duyệt thay đổi EC2 và Security Group.

### 7.4. Architecture

```text
User
  ↓ HTTPS
Domain / DNS
  ↓
Elastic IP
  ↓
EC2 Security Group :443
  ↓
Apache HTTPS VirtualHost
  ↓
Laravel
  ├── RDS MySQL
  └── Amazon S3
```

### 7.5. Changes

#### Change 2.1 — Domain và Elastic IP

- Chọn domain hoặc hostname phù hợp.
- Allocate và associate Elastic IP với EC2 Staging.
- Trỏ DNS A record về Elastic IP.
- Kiểm tra tác động khi Public IP thay đổi.

#### Change 2.2 — Security Group và Apache TLS

- Mở inbound HTTPS `443`.
- Cài `mod_ssl` và Certbot.
- Tạo HTTPS VirtualHost.
- Phát hành certificate Let's Encrypt.
- Không đóng port `80` trước khi HTTP-01 challenge hoàn tất.

#### Change 2.3 — Laravel HTTPS configuration

- Cập nhật `APP_URL` sang HTTPS.
- Cập nhật Sanctum stateful domain.
- Bật secure session cookie.
- Xác minh SameSite và session domain.
- Backup `.env` an toàn trước khi thay đổi.

#### Change 2.4 — HTTPS verification

- Kiểm tra certificate chain và thời hạn.
- Kiểm tra HTTP redirect sang HTTPS.
- Kiểm tra `/`, `/up`, public API, login và logout.
- Kiểm tra HTTP 401, 403 và CSRF 419.
- Kiểm tra cơ chế certificate renewal.

### 7.6. Deliverables

- HTTPS Deployment Report.
- Certificate Evidence.
- Network/SSL Diagram.
- Backup và Rollback Procedure.

### 7.7. Acceptance Criteria

- Website truy cập được bằng HTTPS hợp lệ.
- HTTP redirect đúng và không tạo redirect loop.
- Authentication, session và CSRF hoạt động.
- Certificate renewal có thể kiểm chứng.
- Có rollback nếu HTTPS deployment thất bại.

## 8. Phase 3 — E2E Testing & AWS Validation

### 8.1. Mục tiêu

Kiểm chứng toàn bộ luồng thương mại điện tử cần thiết và các thành phần AWS phục vụ demo.

### 8.2. Priority

`P0`

### 8.3. Dependencies

- Phase 2 PASS.
- Có tài khoản và dữ liệu test cô lập.

### 8.4. Changes

#### Change 3.1 — Customer E2E

- Đăng ký, đăng nhập và đăng xuất.
- Xem, tìm kiếm và lọc sản phẩm.
- Yêu thích và giỏ hàng.
- Voucher nếu nằm trong release scope.
- Checkout COD và VietQR.
- Xem và quản lý đơn hàng.

#### Change 3.2 — Admin E2E

- Đăng nhập và phân quyền Admin.
- Quản lý sản phẩm.
- Upload ảnh test cô lập.
- Set Primary Image.
- Delete ảnh test cô lập.
- Quản lý đơn hàng và thanh toán.

#### Change 3.3 — Data consistency

- Đối chiếu product, variant, stock, order và order item trên RDS.
- Đối chiếu `product_images.image_path` với object S3.
- Kiểm tra ảnh tại catalog, product detail, cart và checkout.
- Không xóa object hoặc record hiện hữu trong lúc audit.

#### Change 3.4 — AWS security

- Kiểm chứng VPC, subnet và Security Groups.
- Xác minh RDS không public và chỉ nhận kết nối cần thiết.
- Kiểm tra S3 Block Public Access và encryption.
- Review IAM theo least privilege.
- Kiểm tra backup retention và restore readiness.

#### Change 3.5 — Monitoring và health

- Kiểm tra CloudWatch Agent và log collection.
- Kiểm tra Apache, PHP-FPM và Laravel logs.
- Kiểm tra `/up` và HTTP monitoring.
- Xác định alarm tối thiểu cho EC2, RDS và application health.

#### Change 3.6 — Cost review

- Kiểm tra Billing/Budget.
- Ước tính chi phí EC2, RDS, S3, Elastic IP và DNS.
- Phát hiện resource thừa hoặc đang chạy không cần thiết.

### 8.5. Deliverables

- End-to-End Test Report.
- Test Evidence Package.
- AWS Security Checklist.
- Monitoring Evidence.
- Cost Report.
- Architecture và Data Flow Diagram.

### 8.6. Acceptance Criteria

- Các core flows PASS từ đầu đến cuối.
- Không có lỗi P0.
- Lỗi P1 còn lại có workaround và được ghi rõ.
- Không có sai lệch nghiêm trọng giữa RDS và S3.
- Có bằng chứng monitoring, backup và security.
- Nhóm giải thích được vai trò của từng AWS service.

## 9. Phase 4 — Cutover & Legacy PHP Retirement

### 9.1. Mục tiêu

Chuyển Laravel thành website chính và ngừng website PHP cũ mà không làm mất khả năng khôi phục.

### 9.2. Priority

`P1`

### 9.3. Dependencies

- Phase 3 PASS.
- Laravel chạy ổn định qua HTTPS.
- Resource dùng chung đã được xác minh.
- Backup và rollback đã sẵn sàng.

### 9.4. Changes

#### Change 4.1 — Observation window

- Theo dõi Laravel tối thiểu 48–72 giờ hoặc qua một vòng demo thử.
- Theo dõi health, logs, RDS, S3 và lỗi giao diện.

#### Change 4.2 — Legacy resource audit

- Kiểm kê EC2, EBS, RDS, S3, IAM và Security Groups của PHP cũ.
- Đánh dấu resource dùng riêng, dùng chung và không còn cần thiết.

#### Change 4.3 — Backup legacy

- Bảo toàn source bằng branch `legacy-php`.
- Backup dữ liệu cần thiết.
- Cân nhắc AMI hoặc EBS snapshot nếu phù hợp chi phí.

#### Change 4.4 — Stop PHP EC2

- Stop EC2 cũ trước, không terminate.
- Kiểm tra Laravel, RDS và S3 sau khi stop.
- Theo dõi tác động và chi phí.

#### Change 4.5 — Đề xuất cleanup sau demo

- Chỉ đề xuất terminate/delete resource sau khi demo hoàn tất.
- Mỗi thao tác destructive phải có phê duyệt riêng.

### 9.5. Deliverables

- Legacy Infrastructure Audit.
- Cutover Report.
- Backup/Restore Evidence.
- Retirement Plan.
- Cost Impact Report.

### 9.6. Acceptance Criteria

- Laravel không phụ thuộc resource PHP cũ.
- PHP EC2 có thể stop mà Laravel vẫn hoạt động.
- Không xóa resource dùng chung.
- Có khả năng phục hồi website cũ nếu cần trước ngày demo.

## 10. Phase 5 — Demo Release & Final Documentation

### 10.1. Mục tiêu

Chốt phiên bản demo, hoàn thiện báo cáo và chuẩn bị thuyết trình.

### 10.2. Priority

`P0` trước ngày bảo vệ.

### 10.3. Dependencies

- Phase 3 PASS.
- Phase 4 có thể ở trạng thái `STOPPED/PENDING DELETE`.

### 10.4. Changes

#### Change 5.1 — Stable release

- Chốt commit và tạo release tag.
- Ghi nhận version đang chạy trên AWS.
- Không thay đổi feature sát ngày demo nếu không phải lỗi P0.

#### Change 5.2 — Demo data và accounts

- Chuẩn bị tài khoản demo Customer/Admin.
- Chuẩn bị dữ liệu sản phẩm, voucher và đơn hàng mẫu.
- Không sử dụng credentials thật trong slides hoặc source.

#### Change 5.3 — Evidence package

- Screenshots EC2, RDS, S3, IAM, VPC và CloudWatch.
- Test output, health check và HTTPS certificate.
- Deployment, backup và rollback evidence.

#### Change 5.4 — Báo cáo

- Chương 1: Tổng quan đề tài.
- Chương 2: Cơ sở lý thuyết và công nghệ.
- Chương 3: Phân tích và thiết kế hệ thống.
- Chương 4: Xây dựng và triển khai AWS.
- Chương 5: Kiểm thử và đánh giá.
- Chương 6: Kết luận và hướng phát triển.

#### Change 5.5 — Slides và tổng duyệt

- Tập trung vào AWS Architecture, Deployment, Security, Monitoring và Demo.
- Phân công phần trình bày cho từng thành viên.
- Chuẩn bị demo script và phương án dự phòng.
- Tổng duyệt trên mạng khác và thiết bị khác.

### 10.5. Deliverables

- Stable Demo Release.
- Release Tag và Version Record.
- Final Report.
- Presentation Slides.
- Demo Script.
- Evidence Package.

### 10.6. Acceptance Criteria

- Website demo ổn định qua HTTPS.
- Có phương án dự phòng nếu mạng hoặc dịch vụ phụ gặp lỗi.
- Mỗi thành viên giải thích được phần mình phụ trách.
- Report, slides và source cùng mô tả đúng một kiến trúc thực tế.

## 11. Workstream xuyên suốt — Documentation & Evidence

Workstream này thực hiện song song từ Phase 0 đến Phase 5:

- Ghi lại SSM Command ID và thời điểm triển khai.
- Lưu test result và phân loại PASS/FAIL/BLOCKED.
- Chụp evidence ngay khi kiểm tra AWS.
- Cập nhật architecture diagram khi hạ tầng thay đổi.
- Ghi lại backup path, rollback result và release version.
- Không đưa secret, token, password hoặc cookie vào tài liệu.

## 12. Phân loại lỗi

### P0 — Blocker

- Website không truy cập được.
- Login/Admin không hoạt động.
- Checkout hoặc tạo đơn làm sai dữ liệu.
- Mất dữ liệu hoặc ảnh hàng loạt.
- Lộ secret hoặc bypass phân quyền.
- Deploy không có rollback.

### P1 — Quan trọng

- Một luồng phụ không hoạt động.
- Responsive lỗi trên thiết bị phổ biến.
- Monitoring hoặc backup chưa có đủ evidence.
- Lỗi ảnh ở một số sản phẩm.

### P2 — Có thể hoãn

- UI chưa đẹp tối ưu.
- Animation hoặc dark mode chưa hoàn chỉnh.
- AI recommendation nâng cao.
- Feature không ảnh hưởng demo Cloud Computing.

## 13. MUST HAVE và OPTIONAL

### 13.1. MUST HAVE

- Regression Testing.
- Git synchronization và Laravel `main`.
- HTTPS.
- Customer/Admin core flows.
- RDS và S3 hoạt động.
- IAM, Security Groups và backup.
- Monitoring và health checks cơ bản.
- Architecture diagram.
- Deployment/rollback procedure.
- Test report và evidence.
- Stable demo release.
- Legacy PHP được stop an toàn hoặc có retirement plan được duyệt.

### 13.2. OPTIONAL

- Application Load Balancer.
- CloudFront.
- Route 53 nếu dùng DNS provider khác.
- Auto Scaling.
- AWS WAF.
- CI/CD hoàn chỉnh.
- Payment gateway thật.
- Chatbot.
- AI recommendation nâng cao.
- UI redesign bổ sung.
- Terminate hoàn toàn PHP infrastructure trước buổi demo.

## 14. Phân công nhóm

### Cloud/Release Lead

- AWS, HTTPS, Git main migration, deploy, rollback và release.
- Là người duy nhất thực hiện thay đổi có kiểm soát trên `main` và Staging.

### Backend

- Laravel API, Authentication, Authorization, Admin và automated tests.

### Frontend

- Customer/Admin UI, responsive và manual UI evidence.

### Data & Storage

- RDS, S3, catalog, image consistency và backup verification.

### QA & Documentation

- Test Matrix, bug classification, screenshots, report, slides và acceptance review.

## 15. Quy tắc phối hợp nhóm

- Mỗi nhiệm vụ làm trên branch riêng.
- Không push trực tiếp vào `main` khi chưa review.
- Không để hai người cùng sửa một file nếu chưa phân chia rõ.
- Commit theo feature, không gom toàn bộ thay đổi vào một commit.
- QA xác minh acceptance criteria bằng evidence.
- Documentation bắt đầu ngay, không chờ code hoàn tất.

## 16. Thứ tự bắt đầu

1. Phase 0 — Release Inventory & Scope Freeze.
2. Phase 1 — Code Stabilization & Git Main Migration.
3. Phase 2 — Domain, HTTPS & Runtime Hardening.
4. Phase 3 — E2E Testing & AWS Validation.
5. Phase 4 — Cutover & Legacy PHP Retirement.
6. Phase 5 — Demo Release & Final Documentation.

Không bắt đầu Phase tiếp theo nếu acceptance criteria bắt buộc của Phase trước chưa đạt hoặc chưa được Human phê duyệt ngoại lệ.
