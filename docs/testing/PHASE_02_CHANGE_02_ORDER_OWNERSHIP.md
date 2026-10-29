# Phase 2 — Báo cáo Change 2: Order ownership

## Trạng thái

```text
CHANGE: Change 2 — Order ownership và authorization regression
TRẠNG THÁI: PASS
NGÀY: 2026-10-27
```

## Thay đổi theo file

### `success.php`

- **Mục đích:** Chỉ cho phép customer đọc trang xác nhận đơn hàng thuộc chính
  họ.
- **Thay đổi:** Load database/security trước khi render HTML; yêu cầu login;
  query đơn bằng cả `id` và `user_id`; redirect non-owner và ID không tồn tại về
  lịch sử đơn với cùng behavior.
- **Lý do:** Phase 0 chứng minh anonymous có thể đọc dữ liệu đơn chỉ bằng
  `order_id`.
- **Vai trò:** Trang xác nhận đơn hàng customer.
- **Rủi ro:** Chặn nhầm owner, tiết lộ sự tồn tại của đơn hoặc gửi body trước
  redirect.
- **Cách kiểm thử:** Anonymous, owner, user khác, ID không tồn tại; đo response
  body khi bị từ chối; regression admin authorization.
- **Kết quả:** `PASS`.

## Bằng chứng kiểm thử

| Trường hợp | Kết quả |
|---|---|
| Anonymous đọc đơn hợp lệ | PASS — HTTP 302 tới login, body 0 byte |
| Owner đọc đơn của mình | PASS — HTTP 200, đúng mã/tổng tiền/phương thức |
| Customer đọc đơn của user khác | PASS — HTTP 302, body 0 byte |
| Customer đọc ID không tồn tại | PASS — cùng redirect như non-owner |
| Response non-owner không chứa dữ liệu đơn | PASS |
| Customer truy cập admin | PASS — HTTP 302 |
| Admin đăng nhập và mở dashboard | PASS — HTTP 200 |
| PHP lint `success.php` | PASS |
| `git diff --check` | PASS |

Hai order fixture có nhãn `PHASE2_CHANGE2_*` đã được xóa. Kiểm tra sau cleanup
trả về `0` record.

## Vấn đề đã sửa

Finding `HIGH — Order ownership / IDOR` được tái hiện trong Phase 0 hiện đã bị
chặn tại database query. Application không còn fetch order chỉ theo ID cho
customer route này.

## File xóa

Không có.
