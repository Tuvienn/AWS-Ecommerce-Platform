# Kế hoạch Phase 1 — Cấu hình và tính di động

## 1. Trạng thái kế hoạch

```text
PHASE: Phase 1 — Configuration & Portability
TRẠNG THÁI KẾ HOẠCH: ĐÃ PHÊ DUYỆT
TRẠNG THÁI IMPLEMENTATION: PASS WITH KNOWN LIMITATIONS — CHỜ HUMAN REVIEW
NGÀY LẬP: 2026-10-27
```

Implementation Change 1–7 đã hoàn thành. Bằng chứng tổng hợp được ghi tại
`docs/testing/PHASE_01_CONFIGURATION_PORTABILITY.md`.

## 2. Bối cảnh

Phase 0 đã hoàn thành với trạng thái `PASS WITH KNOWN LIMITATIONS`.
Application hiện chạy được bằng PHP và MySQL local, nhưng vẫn phụ thuộc
vào cấu hình của máy phát triển:

- Database host, database name, username và password đang được viết trực
  tiếp trong `includes/db.php`.
- Có 119 tham chiếu `/aws-ecommerce-platform` trong 22 file PHP, JavaScript và SQL.
- Local và production chưa có cơ chế dùng chung codebase nhưng khác cấu
  hình.
- Chưa phân biệt cách hiển thị lỗi giữa local và production.
- Chưa có nền tảng application logging.
- Timezone chưa được cấu hình tập trung.

## 3. Mục tiêu

Chuẩn bị application chạy bằng cùng một codebase ở local và AWS thông
qua configuration khác nhau, đồng thời giữ nguyên nghiệp vụ và giao diện
hiện tại.

Kết quả cần đạt:

1. Các giá trị phụ thuộc môi trường được đọc từ environment/configuration.
2. Application tiếp tục chạy ở `/aws-ecommerce-platform` mà không thay đổi behavior.
3. Application có thể chạy ở document root với `APP_BASE_PATH` rỗng.
4. Database configuration không còn nằm trong business logic.
5. Production không hiển thị technical error trực tiếp cho user.
6. Có vị trí log application rõ ràng và không commit runtime logs.
7. Không có secrets trong repository.

## 4. Phạm vi được phép

- Environment-based configuration.
- Application bootstrap dùng chung.
- Database configuration abstraction ở mức tối thiểu.
- `APP_ENV`, `APP_URL`, `APP_BASE_PATH` và `APP_TIMEZONE`.
- Production-safe error configuration.
- Logging foundation.
- `.env.example` và `.gitignore`.
- URL/path helper dùng cho link, asset, redirect và AJAX endpoint.
- Loại bỏ hard-code `/aws-ecommerce-platform` khỏi source code.
- Cập nhật dữ liệu seed URL theo định dạng portable, không thay đổi schema.
- Tài liệu cấu hình local/production.
- Regression testing sau từng thay đổi nhỏ.

## 5. Ngoài phạm vi

Phase 1 không thực hiện:

- Không di chuyển entry point vào `public/`.
- Không thay đổi folder structure lớn.
- Không sửa IDOR, CSRF hoặc session fixation.
- Không chuyển state-changing GET sang POST.
- Không sửa upload security.
- Không thay đổi database schema hoặc foreign key.
- Không sửa checkout race condition.
- Không triển khai S3 hoặc storage abstraction.
- Không tạo AWS resources.
- Không thêm framework, MVC, Composer package hoặc dotenv dependency.
- Không thay đổi UI, CSS hoặc nghiệp vụ bán hàng.

Các vấn đề ngoài phạm vi chỉ được ghi vào backlog của phase tương ứng.

## 6. Quyết định thiết kế đề xuất

### 6.1 Không thêm dependency ngoài

Configuration sẽ dùng `getenv()` và các file PHP trả về array. Không cài
Composer package chỉ để đọc environment.

Lý do:

- Project nhỏ và đang dùng PHP thuần.
- Giảm dependency và rủi ro bảo trì.
- EC2, Apache, PHP-FPM và shell đều có thể cung cấp environment variables.
- `.env.example` đóng vai trò tài liệu/template; file `.env` thật không
  được commit.

### 6.2 Giữ cấu trúc hiện tại trong Phase 1

Các entry point tiếp tục nằm ở repository root. Chỉ tạo những file/thư
mục Phase 1 thực sự cần:

```text
config/
├── app.php
├── database.php
└── logging.php

includes/
└── bootstrap.php

storage/
└── logs/
```

Không tạo trước `public/`, `app/Core/`, `Services/`, `Storage/` hoặc các
placeholder của phase sau.

### 6.3 Local defaults và production fail-fast

Đề xuất behavior:

- `APP_ENV` mặc định là `local`.
- Local có thể dùng các giá trị development hiện tại làm default để giữ
  khả năng chạy ngay sau clone.
- Khi `APP_ENV=production`, các biến database bắt buộc phải tồn tại.
- Nếu production thiếu configuration, application dừng với thông báo
  chung và ghi technical detail vào log.
- Không có production password hoặc AWS credential trong source.

### 6.4 URL helper tập trung

Đề xuất helper:

```php
appUrl('/products.php')
assetUrl('/assets/css/style.css')
redirectTo('/login.php')
```

Helper phải chuẩn hóa dấu `/` và hoạt động với:

```text
APP_BASE_PATH=/aws-ecommerce-platform
APP_BASE_PATH=
APP_BASE_PATH=/shop
```

Không thực hiện global string replacement thiếu kiểm soát. Mỗi nhóm URL
phải được thay đổi và kiểm thử riêng.

### 6.5 JavaScript nhận base path từ server

PHP layout sẽ cung cấp base path đã escape cho JavaScript. `main.js` sử
dụng một helper nhỏ để tạo endpoint cart thay vì hard-code
`/aws-ecommerce-platform/ajax/cart_action.php`.

Không đưa database credential hoặc secret vào browser.

### 6.6 Logging foundation

Phase 1 chỉ tạo nền tảng log local:

```text
storage/logs/application.log
```

- Runtime log bị loại khỏi Git.
- User nhận thông báo lỗi chung.
- Technical detail được ghi log.
- Không log password, DB password, session ID hoặc token.
- CloudWatch integration thuộc Phase 7, không thuộc Phase 1.

## 7. Environment variables đề xuất

| Biến | Bắt buộc production | Local default đề xuất | Mục đích |
|---|---|---|---|
| `APP_ENV` | Có | `local` | Chọn local/production behavior |
| `APP_URL` | Có | `http://127.0.0.1:8000` | Origin chính của application |
| `APP_BASE_PATH` | Không | `/aws-ecommerce-platform` | Prefix URL khi chạy trong subdirectory |
| `APP_TIMEZONE` | Không | `Asia/Ho_Chi_Minh` | Timezone tập trung |
| `DB_HOST` | Có | `localhost` | MySQL host/RDS endpoint |
| `DB_PORT` | Không | `3306` | MySQL port |
| `DB_NAME` | Có | `webbanhang` | Database name |
| `DB_USER` | Có | `root` | Database user |
| `DB_PASSWORD` | Có | rỗng | Database password local |
| `LOG_PATH` | Không | `storage/logs/application.log` | Application log path |

`AWS_REGION`, `S3_BUCKET` và `STORAGE_DRIVER` chưa được sử dụng trong
Phase 1; chúng sẽ được đưa vào configuration khi Phase 4 thực sự cần.

## 8. Trình tự implementation

### Change 1 — Configuration contract và Git hygiene

#### WHAT

- Tạo `.env.example` chỉ chứa placeholder hoặc development value không
  nhạy cảm.
- Tạo `.gitignore` cho `.env`, logs, cache, temporary files và IDE noise
  phù hợp.
- Tạo `config/app.php`, `config/database.php`, `config/logging.php`.

#### WHY

Xác định contract cấu hình trước khi thay đổi cách application khởi
động.

#### FILES AFFECTED

- `.env.example` — tạo mới.
- `.gitignore` — tạo mới.
- `config/app.php` — tạo mới.
- `config/database.php` — tạo mới.
- `config/logging.php` — tạo mới.
- `storage/logs/.gitignore` — tạo mới khi logging cần thư mục này.

#### DEPENDENCIES

- PHP `getenv()`.
- Không có external package.

#### RISKS

- Default value không nhất quán với baseline.
- Vô tình commit secret hoặc `.env`.
- Đặt config production quá lỏng hoặc quá chặt.

#### TEST PLAN

- PHP lint từng file config.
- CLI test đọc local default.
- CLI test override từng biến bằng environment.
- Test `.env` và runtime log không xuất hiện trong `git status`.
- Secrets scan repository.

#### EXPECTED RESULT

Configuration contract tồn tại nhưng behavior application chưa thay đổi.

---

### Change 2 — Bootstrap, timezone và error mode

#### WHAT

- Tạo `includes/bootstrap.php`.
- Load configuration một lần.
- Cấu hình timezone tập trung.
- Local hiển thị lỗi phục vụ development.
- Production tắt `display_errors` và ghi lỗi vào log.
- Khởi tạo session tại bootstrap để các entry point dùng chung behavior.

#### WHY

Hiện `session_start()` nằm trong `includes/db.php`, khiến database và
session bị ràng buộc không cần thiết. Bootstrap tạo một điểm khởi động
thống nhất mà chưa cần refactor architecture.

#### FILES AFFECTED

- `includes/bootstrap.php` — tạo mới.
- `includes/db.php` — load bootstrap; bỏ trách nhiệm cấu hình session.
- `logout.php` — load bootstrap thay vì tự khởi tạo session riêng nếu cần
  để giữ behavior nhất quán.
- `ajax/cart_action.php`, `includes/header.php`,
  `admin/admin_header.php` — review dependency chain, chỉ sửa nếu cần.

#### DEPENDENCIES

- Change 1.
- PHP session và filesystem permission cho log directory.

#### RISKS

- Session bị start hai lần.
- Output trước session/header.
- Production log path không ghi được.
- Error mode làm lộ technical detail ngoài local.

#### TEST PLAN

- PHP lint.
- Test bootstrap được require nhiều lần mà không warning.
- Test session/cart hoạt động.
- Test timezone bằng timestamp xác định.
- Test local error mode và production error mode bằng probe an toàn.
- Xác minh log không chứa secret.

#### EXPECTED RESULT

Mọi request có bootstrap/config/error/session behavior thống nhất.

---

### Change 3 — Database configuration

#### WHAT

- Thay các giá trị DB hard-code trong `includes/db.php` bằng
  `config/database.php`.
- Bổ sung `DB_PORT` vào DSN.
- Giữ PDO attributes hiện tại.
- User nhận generic connection error; technical detail được log.

#### WHY

Cho phép cùng codebase kết nối MySQL local hoặc RDS thông qua environment
variables.

#### FILES AFFECTED

- `includes/db.php`.
- `config/database.php` nếu test cho thấy cần điều chỉnh contract.
- `.env.example` nếu cần sửa mô tả biến.

#### DEPENDENCIES

- Change 1 và Change 2.
- MySQL local baseline.

#### RISKS

- DSN sai hoặc thiếu charset.
- Production thiếu biến nhưng application vẫn dùng local fallback.
- Technical DB exception bị hiển thị cho user.

#### TEST PLAN

- Kết nối database bằng local defaults.
- Kết nối bằng explicit environment variables.
- Test sai credential ở local: lỗi rõ cho developer.
- Test thiếu production credential: fail-fast và user không thấy secret.
- Customer/admin smoke test.

#### EXPECTED RESULT

Application kết nối database qua configuration và sẵn sàng dùng RDS
endpoint ở Phase 6.

---

### Change 4 — URL helper và layout dùng chung

#### WHAT

- Thêm helper tạo application URL và asset URL.
- Thay URL hard-code trong shared header/footer trước.
- Inject `APP_BASE_PATH` an toàn cho JavaScript.
- Chuyển cart AJAX endpoints sang JavaScript URL helper.

#### WHY

Shared layout và JavaScript là nơi ảnh hưởng rộng nhất; xử lý chúng trước
giúp giảm thay đổi lặp lại ở entry points.

#### FILES AFFECTED

- `includes/functions.php`.
- `includes/header.php`.
- `includes/footer.php`.
- `admin/admin_header.php`.
- `admin/admin_footer.php`.
- `assets/js/main.js`.

#### DEPENDENCIES

- `APP_BASE_PATH` từ Change 1/2.

#### RISKS

- Double slash hoặc thiếu slash.
- HTML/JavaScript escaping sai.
- Asset, avatar hoặc AJAX endpoint bị hỏng.

#### TEST PLAN

- Unit-style CLI checks cho `appUrl()` với ba base path.
- HTTP smoke test CSS, JS và avatar URL.
- Cart add/update/remove qua AJAX.
- Admin navigation và inline AJAX thêm brand.

#### EXPECTED RESULT

Layout, asset và cart API không còn phụ thuộc trực tiếp vào
`/aws-ecommerce-platform`.

---

### Change 5 — Chuyển từng nhóm entry point sang URL helper

#### WHAT

Thay URL/redirect hard-code theo từng nhóm nhỏ:

1. Authentication và account.
2. Product browsing và cart.
3. Checkout và success.
4. Admin categories và brands.
5. Admin products và banners.
6. Admin orders.

Mỗi nhóm được test trước khi chuyển nhóm tiếp theo.

#### WHY

Repository hiện có 119 tham chiếu `/aws-ecommerce-platform`. Chia theo workflow giúp
xác định regression chính xác và tuân thủ Small Changes Rule.

#### FILES AFFECTED

- `login.php`, `register.php`, `logout.php`, `account.php`.
- `index.php`, `products.php`, `product_detail.php`, `cart.php`.
- `checkout.php`, `success.php`.
- `admin/categories.php`, `admin/brands.php`.
- `admin/products.php`, `admin/banners.php`.
- `admin/orders.php`.

#### DEPENDENCIES

- Change 4.

#### RISKS

- Redirect loop.
- Query string hoặc fragment bị mất.
- Link HTML đúng nhưng form/AJAX endpoint sai.
- Một URL ít dùng bị bỏ sót.

#### TEST PLAN

Sau mỗi nhóm:

- PHP lint.
- Search không còn `/aws-ecommerce-platform` trong nhóm đã xử lý.
- HTTP status và redirect location.
- Workflow trực tiếp của nhóm.
- Regression tối thiểu: login, products, cart, checkout, admin.

#### EXPECTED RESULT

Application PHP không còn hard-code base path trong entry points.

---

### Change 6 — Portable banner URLs và seed data

#### WHAT

- Chuẩn hóa cách render `banners.button_link` qua URL helper.
- Hỗ trợ dữ liệu local hiện có đang chứa `/aws-ecommerce-platform/...` trong giai
  đoạn chuyển tiếp.
- Đổi URL trong seed data sang path portable.
- Không thay đổi database schema và không tạo migration ở Phase 1.

#### WHY

Banner URL được lưu trong database nên không thể chỉ thay source-code
links. Cần giữ tương thích dữ liệu đã import và làm seed mới portable.

#### FILES AFFECTED

- `index.php`.
- `admin/banners.php`.
- `database/schema.sql` — chỉ seed URL, không đổi schema.
- `includes/functions.php` — helper chuẩn hóa stored internal path nếu
  cần.

#### DEPENDENCIES

- Change 4 và Change 5.

#### RISKS

- External banner URL bị prefix sai.
- Existing `/aws-ecommerce-platform/...` bị double-prefix.
- Thay đổi ngoài ý muốn đối với `#`, query string hoặc URL tuyệt đối.

#### TEST PLAN

- Relative internal path.
- Existing `/aws-ecommerce-platform/...` path.
- Absolute `https://...` path.
- `#` và query string.
- Trang chủ và admin banner form.
- Xác minh phần schema ngoài ba seed URL không thay đổi.

#### EXPECTED RESULT

Banner links hoạt động với base path cấu hình mà không cần schema
migration.

---

### Change 7 — Portability regression ở hai deployment path

#### WHAT

Chạy application với hai cấu hình:

```text
Trường hợp A: APP_BASE_PATH=/aws-ecommerce-platform
Trường hợp B: APP_BASE_PATH=
```

#### WHY

Đây là bằng chứng trực tiếp cho mục tiêu portability và là prerequisite
trước khi đề xuất public-directory migration sau này.

#### FILES AFFECTED

- Không sửa application trong bước test.
- Tạo `docs/testing/PHASE_01_CONFIGURATION_PORTABILITY.md` sau khi test.
- Tạo tài liệu cấu hình bằng tiếng Việt nếu implementation được duyệt.

#### DEPENDENCIES

- Change 1–6 hoàn thành.
- PHP/MySQL runtime từ Phase 0.

#### RISKS

- Chỉ test HTTP 200 nhưng bỏ sót link/redirect/asset.
- Test data làm bẩn database hoặc uploads.

#### TEST PLAN

Với cả hai base path:

- Application starts.
- Homepage/CSS/JS tải được.
- Register/login/logout.
- Browse/search/filter/detail.
- Cart add/update/remove.
- Checkout và order history.
- Account update.
- Admin login/dashboard.
- Admin CRUD và order status.
- Cleanup test data/upload.
- Scan runtime response/log cho fatal, warning, deprecated và SQLSTATE.

Ngoài ra:

- PHP lint toàn repository.
- `rg '/aws-ecommerce-platform'` phải không còn hard-code không được phép.
- Secrets scan.
- `git diff --check`.
- Review toàn bộ diff để bảo đảm không đổi business behavior.

#### EXPECTED RESULT

Hai configuration đều `PASS` bằng cùng codebase.

## 9. Danh sách file dự kiến

### File tạo mới

- `.env.example`
- `.gitignore`
- `config/app.php`
- `config/database.php`
- `config/logging.php`
- `includes/bootstrap.php`
- `storage/logs/.gitignore`
- `docs/configuration/CAU_HINH_MOI_TRUONG.md`
- `docs/testing/PHASE_01_CONFIGURATION_PORTABILITY.md`

Chỉ tạo từng file khi change tương ứng bắt đầu; không tạo placeholder
hàng loạt.

### File dự kiến chỉnh sửa

- `includes/db.php`
- `includes/functions.php`
- `includes/header.php`
- `includes/footer.php`
- `assets/js/main.js`
- `index.php`
- `products.php`
- `product_detail.php`
- `cart.php`
- `checkout.php`
- `success.php`
- `login.php`
- `register.php`
- `logout.php`
- `account.php`
- `ajax/cart_action.php` nếu dependency chain cần thay đổi.
- `admin/admin_header.php`
- `admin/admin_footer.php`
- `admin/categories.php`
- `admin/brands.php`
- `admin/products.php`
- `admin/banners.php`
- `admin/orders.php`
- `database/schema.sql` — chỉ portable banner seed URLs.
- `docs/plans/PLAN_OVERVIEW.md` — chỉ cập nhật trạng thái Phase 1 sau
  khi có evidence.

### File không được di chuyển hoặc xóa

- Toàn bộ entry point PHP hiện tại.
- `admin/`, `ajax/`, `assets/`, `includes/`, `uploads/`.
- `database/schema.sql` không được đổi schema trong Phase 1.
- Các file ảnh và upload hiện có.

## 10. Dependency review

Không thêm dependency ngoài.

| Nhu cầu | Giải pháp | Lý do |
|---|---|---|
| Environment values | PHP `getenv()` | Có sẵn, không cần package |
| Configuration | PHP array files | Đơn giản, dễ kiểm thử |
| Logging | PHP `error_log()` | Đủ cho logging foundation |
| URL building | Helper nội bộ nhỏ | Phù hợp quy mô project |
| Tests | PHP CLI + HTTP + MySQL | Tiếp tục baseline hiện có |

## 11. Rủi ro tổng thể

| Rủi ro | Mức độ | Giảm thiểu |
|---|---|---|
| Thay 119 URL làm hỏng navigation | HIGH | Chia nhóm nhỏ và regression sau từng nhóm |
| Production dùng nhầm local DB default | HIGH | Production validation/fail-fast |
| Lộ DB exception cho user | HIGH | Generic response + internal log |
| Session behavior thay đổi khi thêm bootstrap | MEDIUM | Giữ nguyên session timing và test cart/auth |
| Base path escaping gây XSS | MEDIUM | Server-side encode và JS-safe serialization |
| Banner stored path bị double-prefix | MEDIUM | Test legacy, relative và absolute URL |
| Runtime log bị commit | MEDIUM | `.gitignore` và status test |
| Phase 1 vô tình sửa security/business logic | MEDIUM | Diff review và phase isolation |

## 12. Rollback strategy

- Mỗi change được thực hiện và xác minh riêng.
- Nếu test của change thất bại, dừng trước change tiếp theo.
- Chỉ hoàn tác file thuộc change đang lỗi; không dùng destructive Git
  reset.
- Giữ kết quả Phase 0 làm baseline so sánh.
- Không xóa cấu hình local cũ cho đến khi database configuration mới đã
  kết nối và regression `PASS`.
- Test data và runtime logs được dọn sau mỗi test cycle.

## 13. Tiêu chí hoàn thành Phase 1

Phase 1 chỉ được coi là hoàn thành khi:

- Environment configuration hoạt động.
- Database connection dùng configuration.
- Production không hiển thị technical DB/error detail.
- `APP_ENV`, `APP_URL`, `APP_BASE_PATH`, `APP_TIMEZONE` hoạt động.
- Không còn hard-code `/aws-ecommerce-platform` trong application code, trừ dữ liệu
  compatibility được giải thích và test rõ.
- Application chạy `PASS` ở `/aws-ecommerce-platform`.
- Application chạy `PASS` ở document root.
- Customer regression `PASS`.
- Admin regression `PASS`.
- Không có secret trong repository.
- Runtime logs không được Git track.
- Không thay đổi business behavior ngoài configuration/portability.
- Báo cáo Phase 1 và tài liệu cấu hình bằng tiếng Việt đã hoàn thành.
- Human review hoàn tất trước Phase 2.

## 14. Definition of Done theo file-change rule

Khi implementation được duyệt, mỗi change report phải ghi cho từng file:

```text
FILE:
MỤC ĐÍCH:
THAY ĐỔI:
LÝ DO:
VAI TRÒ TRONG HỆ THỐNG:
RỦI RO:
CÁCH KIỂM THỬ:
KẾT QUẢ:
```

Không file nào được đánh dấu `PASS` nếu chưa có test evidence.

## 15. Approval gate

Kế hoạch đã được phê duyệt và implementation đã hoàn thành. Phase 1 đang
chờ human review trước khi có thể bắt đầu Phase 2.

Khi phê duyệt, implementation sẽ bắt đầu từ:

```text
Change 1 — Configuration contract và Git hygiene
```

Sau mỗi change:

```text
IMPLEMENT
   ↓
TEST
   ↓
VERIFY
   ↓
REPORT
   ↓
NEXT SMALL CHANGE
```

