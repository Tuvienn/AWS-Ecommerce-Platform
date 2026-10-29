# Kế hoạch đổi tên project — aws-ecommerce-platform

## 1. Trạng thái

```text
MỤC TIÊU: Đồng bộ tên kỹ thuật thành aws-ecommerce-platform
TRẠNG THÁI KẾ HOẠCH: ĐÃ PHÊ DUYỆT
TRẠNG THÁI IMPLEMENTATION: PASS — GIT REMOTE CŨ ĐƯỢC GIỮ NGUYÊN, KHÔNG PUSH
NGÀY LẬP: 2026-10-28
```

Kế hoạch này tách tên kỹ thuật project khỏi tên thương hiệu trên giao diện.
Change 1–3 đã hoàn tất. Folder đã được người dùng đổi tên; Git remote cũ của
bên thứ ba được giữ nguyên, không push. Database name và AWS resource không đổi.

## 2. Quy ước tên được đề xuất

| Lớp | Giá trị sau rename | Ghi chú |
| --- | --- | --- |
| Project/repository kỹ thuật | `aws-ecommerce-platform` | Tên folder và GitHub repository mới. |
| Thương hiệu UI | `TechShop` | Giữ nguyên để không đổi trải nghiệm customer. |
| AWS tag `Project` | `aws-ecommerce-platform` | Áp dụng từ Phase 5 provision. |
| AWS resource prefix | `aws-ecommerce-platform-demo` | Dùng cho VPC, EC2, RDS, IAM role. |
| S3 bucket | `aws-ecommerce-platform-assets-<account-id>-<region>` | Globally unique; không hard-code trước provision. |
| Database local | `webbanhang` | Giữ nguyên ở scope này để tránh migration dữ liệu không cần thiết. |
| `APP_BASE_PATH` default | `''` | Không gắn với folder name; `/aws-ecommerce-platform` chỉ dùng nếu app thực sự deploy dưới subdirectory đó. |

## 3. Inventory hiện tại

- Folder hiện tại: `/Users/vientu/Cloud Computing/aws-ecommerce-platform`.
- Git remote vẫn là repository cũ của bên thứ ba; không dùng để push.
- `config/app.php` default `APP_BASE_PATH=''`.
- `.env.example` cũng để `APP_BASE_PATH` rỗng.
- Có references lịch sử trong project overview, phase plan, test report và script.
- Database name `webbanhang` xuất hiện trong config/schema/migration; không đổi ở
  rename kỹ thuật này.

## 4. Phạm vi Change 1 — Configuration và entrypoint

- Đổi default `APP_BASE_PATH` thành chuỗi rỗng để folder name không chi phối URL.
- Cập nhật `.env.example`, configuration docs và command local dùng folder mới.
- Kiểm tra document root và subdirectory custom vẫn chạy bằng `APP_BASE_PATH`.
- Không đổi `DB_NAME`, schema name, session key hoặc runtime storage data.

## 5. Phạm vi Change 2 — Filesystem và Git metadata

- Xác minh worktree trước khi rename; không reset hoặc discard thay đổi đang có.
- Đổi folder theo một thao tác rename recoverable từ parent directory.
- Cập nhật `origin` sang GitHub repository mới chỉ sau khi repository đích tồn tại
  và user xác nhận URL chính xác.
- Không tự tạo/rename GitHub repository vì đây là external-state change.

## 6. Phạm vi Change 3 — Documentation và AWS naming

- Cập nhật overview/rules/current operational docs dùng tên kỹ thuật mới.
- Giữ historical report nguyên nghĩa; chỉ chú thích alias nếu việc sửa lịch sử làm
  evidence khó đọc.
- Cập nhật Phase 5 naming/tagging/parameter example dùng prefix mới.

## 7. Trình tự an toàn

1. Dừng PHP/MySQL server đang chạy và kiểm tra clean process.
2. Ghi `git status --short`, current path, remote URL và config baseline.
3. Apply Change 1, chạy PHP lint và two-base-path smoke test.
4. Human checkpoint: xác nhận folder target và GitHub remote target.
5. Rename folder từ parent directory.
6. Mở project từ path mới, chạy lint/config/HTTP smoke test.
7. Chỉ sau đó cập nhật Git remote khi người dùng xác nhận repository mới.
8. Cập nhật evidence và giữ rollback instruction.

## 8. Rollback

- Nếu code/config lỗi trước folder rename: revert đúng file Change 1 bằng patch.
- Nếu folder rename lỗi: rename lại directory về tên folder trước đó; không reset Git.
- Nếu remote URL sai: `git remote set-url origin <old-url>`; không force push.
- Không đổi database name nên không cần restore database trong rename scope.

## 9. Tiêu chí hoàn thành

- App chạy ở document root với `APP_BASE_PATH=''`.
- App chạy ở subdirectory với `APP_BASE_PATH` custom.
- Không còn runtime dependency vào folder name cũ; `APP_BASE_PATH` có thể cấu hình custom.
- Folder/repository technical name mới nhất quán.
- UI vẫn hiển thị `TechShop`.
- DB `webbanhang` và migration history không đổi.
- Human review trước khi quay lại Phase 5 AWS provision.

## 10. Approval gate

Folder rename đã do người dùng thực hiện và implementation đã đồng bộ. Không
đổi Git remote hoặc push trước khi người dùng sở hữu/xác nhận repository đích.

