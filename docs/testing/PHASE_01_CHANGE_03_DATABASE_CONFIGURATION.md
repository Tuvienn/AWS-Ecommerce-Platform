# Phase 1 — Báo cáo Change 3: Database Configuration

## Trạng thái

```text
CHANGE: Change 3 — Database configuration
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## Thay đổi theo file

### `includes/db.php`

- **Mục đích:** Kết nối MySQL thông qua configuration.
- **Thay đổi:** Dùng config `DB_*`, bổ sung port/charset trong DSN, validate
  biến bắt buộc ở production, ghi technical error vào log và trả generic error
  cho user production.
- **Lý do:** Cùng codebase phải kết nối được local MySQL hoặc Amazon RDS.
- **Vai trò:** Database connection dùng chung của application.
- **Rủi ro:** DSN sai, production dùng thiếu biến hoặc lộ exception.
- **Cách kiểm thử:** Local default, explicit environment, production thiếu
  config và production sai credential.
- **Kết quả:** `PASS`.

## Test evidence

| Test | Kết quả |
|---|---|
| PHP lint | PASS |
| Local default PDO connection | PASS |
| Explicit `DB_*` connection | PASS |
| Production thiếu config fail-fast | PASS |
| Production user output không chứa technical detail | PASS |
| Production technical detail được ghi log | PASS |
| Production sai credential vẫn trả generic error | PASS |
| `git diff --check` | PASS |

## File xóa

Không có.

