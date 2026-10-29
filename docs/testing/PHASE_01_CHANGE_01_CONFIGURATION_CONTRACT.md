# Phase 1 — Báo cáo Change 1: Configuration Contract và Git Hygiene

## Trạng thái

```text
PHASE: Phase 1 — Configuration & Portability
CHANGE: Change 1 — Configuration contract và Git hygiene
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## Mục tiêu

- Định nghĩa các environment variable dùng trong Phase 1.
- Tạo configuration contract nhưng chưa thay đổi runtime behavior.
- Ngăn secrets, logs, cache và temporary files bị commit.
- Không thêm external dependency.
- Không sửa application code hoặc business logic.

## File đã tạo

### `.env.example`

- **Mục đích:** Làm template các biến môi trường cho local và production.
- **Thay đổi:** Khai báo `APP_ENV`, `APP_URL`, `APP_BASE_PATH`,
  `APP_TIMEZONE`, các biến `DB_*` và `LOG_PATH` bằng development value
  không nhạy cảm hoặc giá trị rỗng.
- **Lý do:** Local và AWS production cần dùng cùng codebase nhưng khác
  configuration.
- **Vai trò trong hệ thống:** Tài liệu contract; application chưa tự đọc file
  này.
- **Rủi ro:** Người dùng có thể nhầm đây là production configuration.
- **Cách kiểm thử:** Xác minh file không bị `.gitignore` loại bỏ và secrets scan
  không phát hiện credential.
- **Kết quả:** `PASS`.

### `.gitignore`

- **Mục đích:** Ngăn file nhạy cảm và runtime artifacts vào Git.
- **Thay đổi:** Ignore `.env`, environment variants, `vendor`, runtime
  logs/cache/tmp, `.DS_Store` và IDE noise; giữ `.env.example`.
- **Lý do:** Tuân thủ Secrets Rule và Git Rule.
- **Vai trò trong hệ thống:** Bảo vệ repository; không ảnh hưởng HTTP runtime.
- **Rủi ro:** Pattern quá rộng có thể ignore file cần thiết.
- **Cách kiểm thử:** `git check-ignore` cho từng nhóm và kiểm tra riêng
  `.env.example`.
- **Kết quả:** `PASS`.

### `config/app.php`

- **Mục đích:** Định nghĩa application environment, URL, base path, timezone
  và debug mode.
- **Thay đổi:** Đọc environment bằng `getenv()`, cung cấp local defaults và
  chuẩn hóa `APP_BASE_PATH`.
- **Lý do:** Loại bỏ dependency vào URL/local machine trong các change sau.
- **Vai trò trong hệ thống:** Configuration source cho bootstrap; chưa được
  application load trong Change 1.
- **Rủi ro:** Chuẩn hóa base path sai hoặc production bị nhận nhầm là local.
- **Cách kiểm thử:** PHP lint; local defaults; production override;
  `/aws-ecommerce-platform`, `/shop`, chuỗi rỗng và `/`.
- **Kết quả:** `PASS`.

### `config/database.php`

- **Mục đích:** Định nghĩa MySQL connection contract.
- **Thay đổi:** Đọc `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
  và cố định charset `utf8mb4`.
- **Lý do:** Chuẩn bị cùng codebase kết nối local MySQL hoặc Amazon RDS.
- **Vai trò trong hệ thống:** Database configuration source; chưa thay thế
  `includes/db.php` trong Change 1.
- **Rủi ro:** Local default bị sử dụng nhầm ở production; validation production
  sẽ được thực hiện ở Change 3.
- **Cách kiểm thử:** PHP lint; kiểm tra local defaults; kiểm tra toàn bộ
  environment overrides.
- **Kết quả:** `PASS`.

### `config/logging.php`

- **Mục đích:** Định nghĩa vị trí application log.
- **Thay đổi:** Đọc `LOG_PATH`; nếu không có thì dùng absolute path tới
  `storage/logs/application.log`.
- **Lý do:** Chuẩn bị production-safe error handling ở Change 2.
- **Vai trò trong hệ thống:** Logging configuration source; chưa thay đổi PHP
  error handling trong Change 1.
- **Rủi ro:** Production log path không ghi được; sẽ được test khi bootstrap sử
  dụng config.
- **Cách kiểm thử:** PHP lint; default path; absolute path override.
- **Kết quả:** `PASS`.

### `storage/logs/.gitignore`

- **Mục đích:** Giữ thư mục log trong repository nhưng không track runtime
  logs.
- **Thay đổi:** Ignore mọi file trong thư mục ngoại trừ chính `.gitignore`.
- **Lý do:** Logging foundation cần vị trí ổn định mà không commit dữ liệu
  runtime.
- **Vai trò trong hệ thống:** Git hygiene cho application logs.
- **Rủi ro:** Không đáng kể; log vẫn cần permission phù hợp ở Change 2.
- **Cách kiểm thử:** `git check-ignore storage/logs/application.log`.
- **Kết quả:** `PASS`.

## File đã chỉnh sửa

### `docs/plans/PLAN_OVERVIEW.md`

- **Mục đích:** Phản ánh Phase 1 đã bắt đầu.
- **Thay đổi:** `Configuration Hardening` chuyển từ `NOT STARTED` sang
  `IN PROGRESS`.
- **Lý do:** Documentation phải phản ánh trạng thái thực tế.
- **Vai trò trong hệ thống:** Tài liệu quản lý phase.
- **Rủi ro:** Không ảnh hưởng runtime.
- **Cách kiểm thử:** Kiểm tra status table.
- **Kết quả:** `PASS`.

### `docs/plans/PHASE_01_CONFIGURATION_PORTABILITY.md`

- **Mục đích:** Ghi nhận approval và tiến độ Change 1.
- **Thay đổi:** Kế hoạch chuyển sang `ĐÃ PHÊ DUYỆT`, implementation bắt đầu ở
  Change 1.
- **Lý do:** Tuân thủ approval gate.
- **Vai trò trong hệ thống:** Current phase plan.
- **Rủi ro:** Không ảnh hưởng runtime.
- **Cách kiểm thử:** Kiểm tra phần trạng thái kế hoạch.
- **Kết quả:** `PASS`.

## File đã xóa

Không có.

## Test evidence

| Test | Trạng thái | Evidence |
|---|---|---|
| PHP lint ba config file | PASS | Không có syntax error |
| Local application defaults | PASS | Tất cả giá trị đúng contract |
| Environment overrides | PASS | Production, URL, DB và log override đúng |
| `APP_BASE_PATH=''` | PASS | Chuẩn hóa thành chuỗi rỗng |
| `APP_BASE_PATH='/'` | PASS | Chuẩn hóa thành chuỗi rỗng |
| Git ignore `.env` variants | PASS | `git check-ignore` khớp rule |
| Git ignore log/cache/tmp | PASS | Tất cả runtime path bị ignore |
| `.env.example` trackable | PASS | Không bị ignore |
| Secrets scan | PASS | Không phát hiện AWS key/private key |
| Existing application code | PASS | Không có tracked PHP/JS/CSS/SQL diff |
| `git diff --check` | PASS | Không có whitespace error |

## Behavior impact

Không có. Application chưa load các file configuration mới. Database,
session, error handling, URL và workflow hiện tại vẫn giữ nguyên như Phase 0.

## Vấn đề phát hiện

Không có blocker trong phạm vi Change 1.

## Rủi ro còn lại

- Production-required variable chưa được validate; thuộc Change 3.
- Bootstrap chưa load app/logging configuration; thuộc Change 2.
- Application vẫn dùng DB và URL hard-code; thuộc Change 3–6.
- Các security finding của Phase 0 vẫn giữ nguyên và thuộc Phase 2.

## Kết luận

Change 1 đạt `PASS`. Dừng tại review gate trước Change 2 — Bootstrap,
timezone và error mode.

