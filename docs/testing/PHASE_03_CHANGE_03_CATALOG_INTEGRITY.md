# Phase 3 — Change 3: Catalog integrity và soft delete

## Trạng thái

```text
CHANGE: 3
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## Đã triển khai

- Customer pages chỉ truy vấn `products.is_active = 1`.
- Product detail, cart và AJAX cart đều từ chối product đã archive.
- Admin product delete chuyển thành archive (`is_active = 0`, `archived_at`).
- Admin có thao tác restore product.
- Product form dùng `brand_id`, đồng bộ cột legacy `brand` để giữ khả năng
  rollback và tương thích dữ liệu cũ.
- Brand listing/count và product listing dùng relation `brand_id`.
- Brand rename chỉ cập nhật legacy label, không cần tìm kiếm theo chuỗi để giữ
  relation.
- Category delete hiển thị lỗi rõ ràng khi còn product liên quan.

## Kiểm tra

- Archive product fixture làm product biến mất khỏi truy vấn active.
- Restore fixture trả lại `is_active = 1`, `archived_at = NULL`.
- Không xóa image, product row hoặc order history khi archive.
- PHP lint toàn bộ file PHP: PASS.

