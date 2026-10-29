# Refactor public Document Root

## Trạng thái

```text
MỤC TIÊU: Tách public/ khỏi internal source
KẾT QUẢ: PASS — CHỜ HUMAN REVIEW
NGÀY KIỂM TRA: 2026-10-29
```

## Đã thực hiện

- Chuyển root HTTP entrypoints vào `public/`.
- Chuyển `admin/`, `ajax/`, `assets/` vào `public/`.
- Chuyển `storage_file.php` vào `public/`.
- Giữ `includes/`, `app/`, `config/`, `database/`, `storage/`, `uploads/`,
  `tests/`, `deploy/`, `docs/` bên ngoài `public/`.
- Thêm `public/legacy_file.php` để đọc avatar legacy ở ngoài public.
- Sửa include path từ public entrypoints tới internal source.
- Giữ `tests/manual/test_unset.php` ngoài public và không đưa vào production Document Root.

## Kiểm tra

- PHP lint toàn bộ source: `PASS`.
- Document Root `public/`:
  - `/` → HTTP 200.
  - `/products.php` → HTTP 200.
  - `/product_detail.php?id=1` → HTTP 200.
  - `/storage_file.php?...` → image response HTTP 200.
  - `/legacy_file.php?type=avatar&file=...` → image response HTTP 200.
  - `/admin/` → redirect HTTP 302 tới login khi chưa authentication.
- Runtime path và include dependency đều chạy qua `public/`.

## Bố cục sau refactor

```text
aws-ecommerce-platform/
├── public/                 # Document Root
│   ├── *.php               # Customer entrypoints
│   ├── admin/
│   ├── ajax/
│   ├── assets/
│   ├── storage_file.php
│   └── legacy_file.php
├── app/                    # Internal storage abstraction
├── includes/               # Internal bootstrap/services/helpers
├── config/                 # Internal configuration
├── database/               # Internal schema/migrations/CLI
├── storage/                # Runtime logs/uploads, private
├── uploads/                # Legacy compatibility, private
├── tests/
├── deploy/
└── docs/
```

## Known limitation

Các authenticated upload workflows cần được chạy lại trên EC2-style web server
trước production. `tests/manual/test_unset.php` vẫn tồn tại ngoài public như
development artifact và không được đưa vào deployment artifact.

