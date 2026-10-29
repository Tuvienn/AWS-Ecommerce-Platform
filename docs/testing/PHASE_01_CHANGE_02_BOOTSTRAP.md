# Phase 1 — Báo cáo Change 2: Bootstrap, timezone và error mode

## Trạng thái

```text
CHANGE: Change 2 — Bootstrap, timezone và error mode
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## Kết quả

- Tạo `includes/bootstrap.php` làm điểm khởi tạo configuration, timezone,
  error mode, logging và session.
- `includes/db.php` load bootstrap và không còn trực tiếp gọi
  `session_start()`.
- `logout.php` dùng bootstrap để giữ session initialization nhất quán.
- Chưa thay database credentials hoặc URL hard-code trong change này.

## Báo cáo theo file

### `includes/bootstrap.php`

- **Mục đích:** Khởi tạo runtime dùng chung.
- **Thay đổi:** Load ba config array, đặt timezone, error log, local/production
  display mode và start session một lần.
- **Lý do:** Tách session/error configuration khỏi database connection.
- **Vai trò:** Bootstrap cho mọi HTTP entry point thông qua dependency hiện có.
- **Rủi ro:** Session start hai lần, output trước header hoặc log không ghi được.
- **Cách kiểm thử:** Require nhiều lần; local/production mode; timezone;
  session; safe log probe.
- **Kết quả:** `PASS`.

### `includes/db.php`

- **Mục đích:** Tiếp tục là database bootstrap tương thích code hiện tại.
- **Thay đổi:** Require bootstrap và bỏ `session_start()` trực tiếp.
- **Lý do:** Một trách nhiệm session duy nhất.
- **Vai trò:** Dependency hiện tại của customer/admin/API entry points.
- **Rủi ro:** Database hoặc session không khởi tạo.
- **Cách kiểm thử:** PDO connection và cart session manual script.
- **Kết quả:** `PASS`.

### `logout.php`

- **Mục đích:** Dùng cùng session bootstrap trước khi destroy session.
- **Thay đổi:** Thay `session_start()` bằng require bootstrap.
- **Lý do:** Runtime initialization nhất quán.
- **Vai trò:** Logout endpoint.
- **Rủi ro:** Không destroy đúng session.
- **Cách kiểm thử:** PHP lint; full HTTP logout nằm trong regression Change 7.
- **Kết quả:** `PASS` ở phạm vi Change 2.

## Test evidence

| Test | Kết quả |
|---|---|
| PHP lint | PASS |
| Bootstrap require nhiều lần | PASS |
| Local timezone `Asia/Ho_Chi_Minh` | PASS |
| Production timezone override | PASS |
| Local `display_errors=1` | PASS |
| Production `display_errors=0` | PASS |
| Session active | PASS |
| PDO connection sau bootstrap | PASS |
| Cart session manual test | PASS |
| Safe warning chỉ xuất hiện trong log production | PASS |
| Log sensitive-data scan | PASS |
| `git diff --check` | PASS |

## File xóa

Không có.

## Rủi ro còn lại

- Database connection vẫn hard-code cho tới Change 3.
- URL vẫn hard-code cho tới Change 4–6.
- Session security flags thuộc Phase 2, không sửa trong Phase 1.

