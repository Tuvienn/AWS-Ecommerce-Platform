# Phase 3 — Change 4: Authoritative transactional checkout

## Trạng thái

```text
CHANGE: 4
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## Đã triển khai

- Tạo `includes/order_service.php` làm service duy nhất tạo order.
- Lock product theo ID tăng dần bằng `SELECT ... FOR UPDATE`.
- Đọc lại active state, price và stock trong transaction.
- Ghi `order_details.product_name` và `price` snapshot.
- Kiểm tra `rowCount()` sau mỗi stock update.
- Rollback toàn bộ nếu một item thiếu stock hoặc lỗi kỹ thuật.
- Chỉ xóa cart sau khi commit thành công.
- Technical error được log; user chỉ nhận thông báo an toàn.

## Evidence

- Fixture order: stock 50 → 49, detail snapshot đúng tên và giá, cancel trả
  stock về 50.
- Order/detail fixture được dọn sau kiểm tra; database trở lại 0 order và 0
  order detail.
- PHP lint toàn bộ file PHP: PASS.

