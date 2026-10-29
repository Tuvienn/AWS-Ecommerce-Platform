# Phase 0 --- Runtime Baseline

## Trạng thái Phase

``` text
PHASE: Phase 0 — Runtime Baseline
STATUS: PASS WITH KNOWN LIMITATIONS
DATE: 2026-10-27
```

Application hiện tại đã được kiểm thử mà không thay đổi application
code, business logic hoặc database schema.

## Mục tiêu

-   Khôi phục repository về cấu trúc trước folder migration.
-   Thiết lập local PHP và MySQL runtime tương thích.
-   Import và xác minh schema và seed data hiện có.
-   Chạy PHP syntax checks.
-   Khởi chạy application hiện tại ở local.
-   Thực hiện các customer và admin workflow tối thiểu.
-   Tái hiện các security finding an toàn mà không sửa chúng.
-   Ghi lại evidence trước khi các thay đổi của Phase 1 bắt đầu.

## Khôi phục Repository

Folder migration được thực hiện quá sớm đã được rollback hoàn toàn trước
runtime testing.

Evidence:

-   Các entry point đã trở lại repository root.
-   `admin/`, `ajax/`, `assets/`, `includes/`, và `uploads/` đã được
    khôi phục.
-   Các include path ban đầu và `/aws-ecommerce-platform/ajax/cart_action.php` đã
    được khôi phục.
-   `git diff --stat` báo cáo không có thay đổi application code được
    tracked.
-   Các migration placeholder và deployment file tạm thời đã được xóa.
-   Các project document được giữ lại.

Kết quả: `PASS`

## Môi trường Runtime

  ------------------------------------------------------------------------------
  Thành phần              Kết quả                 Evidence
  ----------------------- ----------------------- ------------------------------
  Host architecture       PASS                    Apple Silicon `arm64`, macOS
                                                  27.0

  XAMPP hiện có           BLOCKED                 XAMPP PHP/MySQL binaries là
                                                  `x86_64` và PHP trả về
                                                  `bad CPU type in executable`

  PHP thay thế            PASS                    Homebrew PHP 8.5.12, native
                                                  ARM

  MySQL thay thế          PASS                    Homebrew MySQL 26.10.0, native
                                                  ARM

  MySQL availability      PASS                    Server sẵn sàng trên
                                                  `127.0.0.1:3306`

  Web runtime             PASS                    PHP built-in server trên
                                                  `127.0.0.1:8000`, application
                                                  prefix `/aws-ecommerce-platform`
  ------------------------------------------------------------------------------

Sự không tương thích của XAMPP là một environment blocker, không phải
application defect. Vấn đề này đã được giải quyết cho baseline testing
bằng cách cài đặt native ARM runtime. Không có application workaround
nào được đưa vào.

## PHP Extensions

Các extension bắt buộc đã có:

-   `PDO`
-   `pdo_mysql`
-   `session`
-   `fileinfo`
-   `mbstring`
-   `openssl`

PDO báo cáo có `mysql` driver.

Kết quả: `PASS`

## Kiểm tra PHP Syntax

Phạm vi command: mọi file `*.php` trong repository, không bao gồm
`.git`.

Kết quả: `PASS`

Evidence: tất cả 24 file PHP đều trả về `No syntax errors detected`.

## Import Database

File `database/schema.sql` không chỉnh sửa đã được import vào local
MySQL.

Xác minh dữ liệu ban đầu:

  Entity            Số lượng
  --------------- ----------
  Users                    2
  Categories               6
  Brands                  10
  Banners                  3
  Products                17
  Orders                   0
  Order details            0

Foreign-key behavior quan sát được từ schema đã import:

-   `products.category_id -> categories.id`: `ON DELETE CASCADE`
-   `orders.user_id -> users.id`: `ON DELETE SET NULL`
-   `order_details.order_id -> orders.id`: `ON DELETE CASCADE`
-   `order_details.product_id -> products.id`: `ON DELETE CASCADE`

Kết quả: `PASS`

## HTTP Smoke Tests

  Route                                                 Mong đợi Kết quả
  ---------------------------------------------- --------------- ---------
  `/aws-ecommerce-platform/`                                             200 PASS
  `/aws-ecommerce-platform/products.php`                                 200 PASS
  `/aws-ecommerce-platform/product_detail.php?id=1`                      200 PASS
  `/aws-ecommerce-platform/cart.php`                                     200 PASS
  `/aws-ecommerce-platform/login.php`                                    200 PASS
  `/aws-ecommerce-platform/register.php`                                 200 PASS
  `/aws-ecommerce-platform/admin/` khi chưa authentication     302 tới login PASS

Không tìm thấy PHP fatal error, warning, deprecation, uncaught exception
hoặc SQLSTATE message trong các HTTP response đã kiểm thử hoặc output
của development server.

## Customer Workflow

  -----------------------------------------------------------------------
  Test                    Kết quả                 Evidence
  ----------------------- ----------------------- -----------------------
  Register                PASS                    HTTP 302 tới login;
                                                  user đã được tạo

  Login                   PASS                    HTTP 302 tới
                                                  application root

  Browse products         PASS                    HTTP 200

  Search                  PASS                    HTTP 200 cho `iPhone`

  Category filter         PASS                    HTTP 200 cho category 1

  Brand filter            PASS                    HTTP 200 cho Apple

  Product detail          PASS                    HTTP 200 cho product 1

  Add to cart             PASS                    JSON success; cart
                                                  count 1

  Update cart             PASS                    JSON success; quantity
                                                  2 được render

  Checkout                PASS                    HTTP 302 tới order
                                                  success page

  Order creation          PASS                    Order và order detail
                                                  đã được lưu

  Price integrity trong   PASS                    2 × 32,990,000 =
  flow quan sát được                              65,980,000

  Stock update trong flow PASS                    Product stock thay đổi
  quan sát được                                   50 → 48

  Order history           PASS                    Order đã tạo xuất hiện
                                                  trong customer account

  Profile update          PASS                    Name, phone và address
                                                  đã được lưu

  Password change         PASS                    Re-login với password
                                                  mới thành công

  Avatar upload           PASS                    Database key và
                                                  uploaded file đã được
                                                  tạo

  Logout                  PASS                    Account access được
                                                  redirect tới login
  -----------------------------------------------------------------------

Customer, order, order detail và avatar tạm thời đã được xóa sau khi
testing. Product stock đã được khôi phục về 50.

## Admin Workflow

  -----------------------------------------------------------------------
  Test                    Kết quả                 Evidence
  ----------------------- ----------------------- -----------------------
  Admin login             PASS                    HTTP 302 tới `/admin/`

  Dashboard               PASS                    HTTP 200 và dashboard
                                                  content được render

  Category CRUD           PASS                    Create, read, update,
                                                  delete đã được xác minh

  Brand CRUD              PASS                    Create, read, update,
                                                  delete đã được xác minh

  Product CRUD            PASS                    Create, read, update,
                                                  delete đã được xác minh

  Banner CRUD             PASS                    Create, read, update,
                                                  toggle, delete đã được
                                                  xác minh

  Order detail            PASS                    Customer và product
                                                  detail được render

  Order status update     PASS                    `pending` được thay đổi
                                                  thành `confirmed`
  -----------------------------------------------------------------------

Tất cả temporary CRUD record và generated banner file đã được xóa sau
các test.

## Safe Security Baseline

Các finding đã được tái hiện nhưng không được sửa trong Phase 0.

### HIGH --- Order ownership / IDOR

Một unauthenticated request tới `success.php?order_id=1` trả về HTTP 200
và hiển thị order number, payment method, order time và total amount.

Target phase: Phase 2.

### HIGH --- Thiếu CSRF protection

Một authenticated profile-update POST không có CSRF token đã được chấp
nhận và trả về HTTP 302. Các Admin CRUD request cũng được chấp nhận mà
không có CSRF token.

Target phase: Phase 2.

### HIGH --- State-changing GET requests

Các thao tác Admin banner toggle và delete đối với products, banners,
brands và categories đã thực hiện thành công thông qua GET requests.

Target phase: Phase 2.

### MEDIUM --- Thiếu session fixation protection

PHP session ID trước và sau khi login thành công là giống nhau.

Target phase: Phase 2.

### PASS --- Admin server-side authorization

Một customer đã login khi request `/admin/` nhận HTTP 302 tới login
page.

### PASS --- SQL injection probe đã quan sát

Một search payload chứa `' OR 1=1 --` trả về HTTP 200, zero products, và
không có database error. Đây chỉ là evidence cho search path đã kiểm
thử, không phải khẳng định rằng mọi query đều an toàn.

### PASS --- Reflected XSS probe đã quan sát

Một script payload trong product search field đã được HTML-escaped trong
response. Đây chỉ là evidence cho search output đã kiểm thử.

### Chưa được runtime-test trong Phase 0

-   Malicious upload execution không được thử vì việc này sẽ tạo một
    executable-like file trong public tree hiện tại. Static audit
    finding vẫn để mở cho Phase 2.
-   Checkout concurrency/race behavior không được stress-test. Static
    finding vẫn để mở cho Phase 3.
-   Các destructive foreign-key scenario không được thực thi trên
    baseline data. Schema evidence vẫn để mở cho Phase 3.

## Xác minh Cleanup

After all runtime tests:

  Entity                         Số lượng/trạng thái
  ---------------------------- ---------------------
  Users                                            2
  Products                                        17
  Orders                                           0
  Order details                                    0
  Product 1 stock                                 50
  Generated baseline uploads                       0

Kết quả: `PASS`

## File đã tạo

### `docs/testing/PHASE_00_BASELINE.md`

-   **Purpose:** Evidence cho Phase 0 runtime và functional-test.
-   **What changed:** Đã thêm baseline report này.
-   **Why:** Deliverable bắt buộc của Phase 0.
-   **Role in system:** Chỉ là human-readable test documentation.
-   **Risk:** Không có đối với application runtime.
-   **How to test:** Review evidence dựa trên command, HTTP result,
    database count và Git state.
-   **Result:** PASS.

## File đã chỉnh sửa

Không có.

## File đã xóa

Không có file nào từ repository ban đầu. Chỉ các temporary file được tạo
bởi folder migration đã hủy và test data của Phase 0 được xóa.

## Các giới hạn đã biết

-   Visual browser QA đang `BLOCKED`: không có controllable browser
    surface trong tool session hiện tại. HTTP rendering và response
    content đã được kiểm thử.
-   Test được thực hiện với PHP 8.5.12 và MySQL 26.10.0. Việc lựa chọn
    deployment version vẫn là quyết định ở planning sau.
-   PHP built-in server chỉ phù hợp cho baseline verification và không
    phải production web-server design.
-   Automated regression test chưa tồn tại; các Phase 0 workflow được
    thực hiện thông qua repeatable HTTP request và direct database
    verification.

## Rủi ro còn lại

-   Order ownership/IDOR.
-   Thiếu CSRF protection.
-   State-changing GET requests.
-   Session fixation.
-   Upload validation and executable upload location.
-   Checkout stock race condition.
-   Mất order history thông qua foreign-key behavior hiện tại.
-   Database và `/aws-ecommerce-platform` configuration đang hard-code.

## Blockers

Không có application hoặc runtime blocker nào ngăn cản việc hoàn thành
Phase 0 baseline.

Trong quá trình final documentation verification,
`docs/plans/PLAN_OVERVIEW.md` không còn tồn tại trong filesystem mặc dù
nó đã được đọc trước đó trong phase. Phase 1 bị blocked cho đến khi plan
được khôi phục hoặc việc tạo lại nó được approve rõ ràng. File bị thiếu
không được tự động tạo lại.

## Phase được đề xuất tiếp theo

Khôi phục hoặc approve rõ ràng việc tạo lại
`docs/plans/PLAN_OVERVIEW.md`, sau đó thực hiện human review report này
trước khi Phase 1 --- Configuration & Portability bắt đầu.
