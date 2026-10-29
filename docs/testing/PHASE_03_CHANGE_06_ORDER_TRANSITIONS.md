# Phase 3 — Change 6: Order transition và hoàn tồn kho

## Trạng thái

```text
CHANGE: 6
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## Đã triển khai

- Thêm transition map server-side:
  - `pending → confirmed | cancelled`
  - `confirmed → shipping | cancelled`
  - `shipping → completed`
  - `completed`, `cancelled` là terminal.
- Admin order update dùng `updateOrderStatus()` trong transaction.
- Lock order trước khi kiểm tra transition.
- Cancel lock order detail theo product ID và hoàn tồn kho đúng một lần.
- Transition không hợp lệ bị từ chối, không update trạng thái.

## Evidence

- Fixture cancel đã hoàn stock từ 49 về 50.
- Order/detail fixture đã được xóa sau kiểm tra.
- Không còn logic `UPDATE orders SET status` trực tiếp ngoài service.

