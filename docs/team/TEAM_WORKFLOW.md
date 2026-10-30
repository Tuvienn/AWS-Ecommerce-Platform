# Quy trình làm việc nhóm

## 1. Nguyên tắc

- `main` luôn là phiên bản ổn định và được bảo vệ.
- Mọi thay đổi đi qua branch và Pull Request.
- Một nhiệm vụ có một owner chính, reviewer và tiêu chí hoàn thành.
- Không chia sẻ AWS root, `.env` production hoặc credential.

## 2. Ownership

| Khu vực | Owner chính |
|---|---|
| AWS, `deploy/`, production `.env` | Cloud/DevOps Owner |
| `app/`, `database/`, `config/` | Backend Lead |
| Customer UI và public assets | Customer Frontend |
| Admin UI và admin feature | Admin/Feature Developer |
| `tests/`, `docs/`, evidence | QA/Documentation |

Các file dễ conflict như `README.md`, `style.css`, `main.js`, `schema.sql`,
`composer.json` và `composer.lock` phải thông báo owner trước khi sửa.

## 3. Luồng công việc

```text
Nhận task → tạo branch → code local → self-test → push → Pull Request
→ review → sửa feedback → merge main → Cloud Owner deploy → QA acceptance
```

## 4. Database

- Không chỉnh production database bằng thao tác thủ công không có migration.
- Không commit database dump production.
- Migration phải có mục đích, thứ tự, kiểm thử và rollback được xem xét.
- Chỉ Cloud Owner chạy migration production.

## 5. AWS

- Chỉ Cloud Owner thay EC2, RDS, S3, IAM, CloudWatch và Security Group.
- Mọi thay đổi phải ghi resource, region, mục đích, chi phí và rollback.
- Thành viên khác cung cấp code/checklist; không cần quyền AWS.

## 6. Review gate

Không merge khi:

- Test/lint thất bại.
- Có secret hoặc file runtime.
- Thay schema nhưng không có migration.
- Thay UI nhưng không có evidence.
- PR chứa nhiều nhiệm vụ không liên quan.
