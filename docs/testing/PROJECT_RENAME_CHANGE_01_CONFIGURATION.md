# Rename project — Change 1: Configuration và entrypoint

## Trạng thái

```text
MỤC TIÊU: aws-ecommerce-platform
CHANGE: 1
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-28
```

## File thay đổi

### `config/app.php`, `.env.example`

```text
PURPOSE: Tách URL base path khỏi tên folder cũ.
WHAT CHANGED: APP_BASE_PATH mặc định và example đổi sang chuỗi rỗng.
WHY: Folder/repository rename không được làm thay đổi routing application.
ROLE IN SYSTEM: Application URL configuration.
RISK: Deployment dưới subdirectory phải cung cấp APP_BASE_PATH rõ ràng.
HOW TO TEST: Test document root và custom subdirectory environment.
RESULT: PASS.
```

### `docs/configuration/CAU_HINH_MOI_TRUONG.md`, `docs/project/PROJECT_OVERVIEW.md`

```text
PURPOSE: Đồng bộ hướng dẫn hiện hành với technical name mới.
WHAT CHANGED: Command/example dùng aws-ecommerce-platform; UI brand không đổi.
WHY: Tránh hướng dẫn local phụ thuộc vào tên folder cũ.
ROLE IN SYSTEM: Operational documentation.
RISK: Folder thực tế chưa rename nên command subdirectory chỉ dùng sau Change 2.
HOW TO TEST: Review command và config default.
RESULT: PASS.
```

## Giới hạn

Folder và Git remote chưa đổi. Change 2 cần checkpoint xác nhận trước khi
filesystem rename.

