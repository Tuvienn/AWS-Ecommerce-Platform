# Báo cáo đổi tên project — aws-ecommerce-platform

## Trạng thái

```text
MỤC TIÊU: Đồng bộ tên kỹ thuật thành aws-ecommerce-platform
KẾT QUẢ: PASS
NGÀY HOÀN TẤT: 2026-10-29
```

## Kết quả

- Folder project hiện tại: `aws-ecommerce-platform`.
- `APP_BASE_PATH` default là rỗng; application không phụ thuộc folder name.
- Custom subpath `/aws-ecommerce-platform` hoạt động qua environment config.
- JavaScript runtime config đã đổi thành
  `AWS_ECOMMERCE_PLATFORM_CONFIG`.
- AWS preflight parameter dùng `PROJECT_NAME`, `PROJECT_ENVIRONMENT`,
  `ADMIN_CIDR`, `COST_APPROVED` thay vì identifier cũ.
- Tài liệu technical/current plan đã đồng bộ tên mới.

## Kiểm tra

| Kiểm tra | Kết quả |
| --- | --- |
| PHP lint toàn bộ source | PASS |
| LocalStorage integration | PASS |
| Document root: home/products/storage gateway | HTTP 200 |
| `/aws-ecommerce-platform`: home/products/storage gateway | HTTP 200 |
| Tìm `WebBanHang` và identifier config cũ | Không còn kết quả runtime/configuration |

## Chủ động giữ nguyên

- Database name `webbanhang`, schema và migration history giữ nguyên để không
  phát sinh data migration ngoài scope.
- Git remote cũ không thuộc người dùng vẫn giữ nguyên và không push/không đổi.
- UI brand `TechShop` giữ nguyên.

