# Layout tài liệu project và test manual

## Trạng thái

```text
KẾT QUẢ: PASS
NGÀY KIỂM TRA: 2026-10-29
```

## Thay đổi

- `PROJECT_OVERVIEW.md` và `PROJECT_RULES.md` đã chuyển vào
  `docs/project/`.
- `test_unset.php` đã chuyển vào `tests/manual/`.
- Không file nào bị xóa.
- Không file nào được đưa vào `public/`.

## Lý do

- `docs/project/` chứa tài liệu cấp project, tách khỏi plan, testing và
  configuration docs.
- `tests/manual/` chứa test/debug thủ công không thuộc runtime application.
- Document Root `public/` chỉ còn HTTP entrypoints và public assets.

## Kiểm tra

- Không còn hai file project Markdown ở root.
- Không còn `test_unset.php` ở root hoặc trong `public/`.
- Các reference nội bộ đã cập nhật sang path mới.
- `test_unset.php` vẫn được giữ lại để audit/lịch sử nhưng không deploy.

