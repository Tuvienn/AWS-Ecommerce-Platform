# Hướng dẫn đóng góp

Tài liệu này quy định cách nhóm phát triển `aws-ecommerce-platform`. Mọi thành
viên phải đọc `docs/project/PROJECT_RULES.md` và tài liệu này trước khi code.

## 1. Chuẩn bị môi trường

```bash
git clone https://github.com/Tuvienn/AWS-Ecommerce-Platform.git
cd AWS-Ecommerce-Platform
cp .env.example .env
composer install
```

Tạo database local từ `database/schema.sql`, nạp `.env`, sau đó chạy:

```bash
php -S 127.0.0.1:8000 -t public
tests/run_regression.sh
```

Không dùng database, `.env`, credential hoặc dữ liệu production cho local.

## 2. Branch

Không push trực tiếp vào `main`. Mỗi nhiệm vụ dùng một branch riêng:

```text
frontend/customer-redesign
frontend/admin-redesign
backend/order-tests
test/feature-regression
docs/final-report
infra/cloudwatch
```

Tạo branch từ `main` mới nhất:

```bash
git switch main
git pull --ff-only origin main
git switch -c <ten-branch>
```

## 3. Commit

Format commit:

```text
type: mô tả ngắn
```

Type hợp lệ: `feat`, `fix`, `refactor`, `test`, `docs`, `style`, `chore`,
`infra`.

## 4. Kiểm tra trước khi push

```bash
tests/run_regression.sh
git diff --check
git status
```

Nếu thay database schema phải có migration. Nếu thay UI phải có screenshot.

## 5. Pull Request

- Một PR chỉ giải quyết một nhiệm vụ.
- Phải mô tả mục tiêu, file thay đổi, cách test, rủi ro và rollback.
- Cần ít nhất một approval và giải quyết hết conversation.
- Không merge khi lint/test fail hoặc có thay đổi ngoài phạm vi.

## 6. Bảo mật

Không commit hoặc chia sẻ:

```text
.env
vendor/
AWS access key/session token
RDS password
production database dump
private key
```

Chỉ Cloud/DevOps Owner được deploy, chạy production migration và thay đổi AWS.

## 7. Definition of Done

- Đúng phạm vi và owner module.
- Không chứa secret.
- Test liên quan và regression PASS.
- Có migration/screenshot/tài liệu khi cần.
- Pull Request được review.
- Không conflict với `main`.
