# Phân công nhiệm vụ nhóm

## Thành viên 1 — Cloud/DevOps Owner

- IAM, EC2, RDS, S3, CloudWatch và deploy.
- Production environment, backup, rollback và release.
- Chạy acceptance checklist trên AWS cùng QA.

## Thành viên 2 — Backend Lead

- PHP services/repositories, database và security.
- Checkout, order, session, authorization và backend unit test.
- Review backend của Admin/Feature Developer.

## Thành viên 3 — Customer Frontend

- Trang chủ, sản phẩm, cart, checkout, login và account.
- Responsive, component UI, loading/error/empty state.
- Không tự thay SQL hoặc backend contract.

## Thành viên 4 — Admin/Feature Developer

- Admin dashboard, CRUD, order management và admin responsive.
- Feature mở rộng sau khi các mục bắt buộc hoàn thành.
- Phối hợp Backend Lead nếu thay nghiệp vụ/database.

## Thành viên 5 — QA/Documentation

- Feature test, regression, acceptance checklist và bug report.
- README, screenshot, kiến trúc, báo cáo, slide và demo script.
- Không cần credential hoặc quyền AWS production.

## Trạng thái task

Mỗi task cần ghi:

```text
Owner:
Reviewer:
Branch:
Mục tiêu:
Tiêu chí PASS:
Rủi ro:
Rollback:
```
