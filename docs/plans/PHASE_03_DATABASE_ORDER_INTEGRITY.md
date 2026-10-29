# Kế hoạch Phase 3 — Toàn vẹn database và đơn hàng

## 1. Trạng thái

```text
PHASE: Phase 3 — Database & Order Integrity
TRẠNG THÁI KẾ HOẠCH: ĐÃ PHÊ DUYỆT
TRẠNG THÁI IMPLEMENTATION: PASS — ĐÃ ĐƯỢC HUMAN REVIEW
NGÀY LẬP: 2026-10-27
```

Change 1 đến Change 7 đã được triển khai và kiểm tra thành công. HTTP/browser
regression hai base path đã được người dùng xác nhận. Báo cáo tổng kết nằm tại
`docs/testing/PHASE_03_DATABASE_ORDER_INTEGRITY.md`.

## 2. Điều kiện đầu vào

- Phase 0: `PASS WITH KNOWN LIMITATIONS`.
- Phase 1: `PASS`.
- Phase 2: `PASS`, đã được người dùng duyệt.
- Database local sau Phase 2 đã trở về baseline: 2 user, 17 product, 0 order,
  0 order detail và stock sản phẩm kiểm tra là 50.
- Customer/admin regression chạy được ở `/aws-ecommerce-platform` và document root.

## 3. Finding hiện tại

### 3.1 Checkout có race condition

`checkout.php` đọc product/price/stock trước transaction. Trong transaction,
stock update có điều kiện `stock >= quantity` nhưng không kiểm tra `rowCount()`.
Vì vậy một request có thể vẫn commit order/order detail dù stock update thất
bại do request đồng thời khác đã lấy hết stock.

### 3.2 Giá và availability không được đọc lại trong transaction

Tổng tiền được tính trước transaction. Nếu giá, trạng thái hoặc product thay
đổi giữa lúc render và lúc submit, order có thể dùng dữ liệu không còn
authoritative.

### 3.3 Order history có thể mất

```text
categories → products: ON DELETE CASCADE
products → order_details: ON DELETE CASCADE
```

Xóa category có thể xóa product, rồi xóa luôn order detail lịch sử. Xóa product
trực tiếp cũng làm mất order detail.

### 3.4 Product chưa có cơ chế archive

Admin delete đang xóa vật lý product. Đối với dữ liệu thương mại điện tử, sản
phẩm đã xuất hiện trong order cần được giữ hoặc snapshot đầy đủ.

### 3.5 Order status chưa có transition rule

Admin có thể chuyển tự do giữa mọi status. Chưa có quy tắc terminal state và
chưa khôi phục stock khi order hợp lệ chuyển sang `cancelled`.

### 3.6 Brand đang lưu bằng chuỗi

`products.brand` lặp lại `brands.name`; rename/delete brand cần cập nhật chuỗi
hàng loạt và không có foreign key. Phase 3 sẽ chuyển sang `brand_id` theo mô
hình additive để có rollback an toàn, đồng thời tạm giữ cột legacy `brand`.

## 4. Mục tiêu

1. Mọi thay đổi schema đi qua versioned migration.
2. Order/order detail lịch sử không biến mất khi quản lý catalog.
3. Checkout dùng dữ liệu authoritative và row lock trong transaction.
4. Concurrent checkout không oversell và không tạo partial order.
5. Product delete trở thành archive; customer không thấy product archived.
6. Order status chỉ đi theo transition hợp lệ.
7. Cancel order chỉ khôi phục stock đúng một lần.
8. Brand có foreign key chuẩn hóa mà vẫn giữ đường rollback.
9. Fresh schema và upgraded schema có cấu trúc tương đương.

## 5. Ngoài phạm vi

- Không chuyển upload sang storage abstraction hoặc S3; thuộc Phase 4.
- Không tạo AWS/RDS resource hoặc chạy migration production.
- Không di chuyển entry point vào `public/`.
- Không thêm framework/ORM/Composer package.
- Không xây payment gateway, refund hoặc inventory reservation service.
- Không thay đổi UI ngoài trạng thái archive/restore và thông báo lỗi cần thiết.
- Không tự động xóa dữ liệu lịch sử để làm migration chạy được.

## 6. Thiết kế database mục tiêu

### 6.1 Migration registry

Tạo bảng:

```sql
schema_migrations (
    version VARCHAR(100) PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)
```

Migration nằm trong `database/migrations/`, có thứ tự và tài liệu rollback.
Không sửa database production thủ công.

### 6.2 Product archive

Thêm vào `products`:

```text
is_active TINYINT(1) NOT NULL DEFAULT 1
archived_at TIMESTAMP NULL
```

- Admin “xóa” product sẽ archive, không `DELETE` vật lý.
- Customer listing/detail/cart/checkout chỉ dùng product active.
- Admin có thể xem và restore product archived.
- Image không bị xóa khi archive; storage lifecycle thuộc Phase 4.

### 6.3 Order detail snapshot

Thêm `order_details.product_name VARCHAR(255)` và backfill từ `products.name`.
Sau khi application đã ghi snapshot cho order mới, migration finalize chuyển
cột thành `NOT NULL`.

`order_details.price` hiện có tiếp tục là price snapshot. `product_id` vẫn được
giữ để đối chiếu inventory và dùng `ON DELETE RESTRICT` thay vì cascade.

### 6.4 Foreign key behavior

| Quan hệ | Hiện tại | Mục tiêu | Lý do |
|---|---|---|---|
| `orders.user_id → users.id` | `SET NULL` | Giữ nguyên | Order đã snapshot customer data |
| `order_details.order_id → orders.id` | `CASCADE` | Giữ nguyên | Detail thuộc vòng đời order |
| `order_details.product_id → products.id` | `CASCADE` | `RESTRICT` | Không mất lịch sử/inventory reference |
| `products.category_id → categories.id` | `CASCADE` | `RESTRICT` | Không xóa dây chuyền product |
| `products.brand_id → brands.id` | Chưa có | `SET NULL` | Brand có thể xóa mà product vẫn tồn tại |

### 6.5 Brand normalization có đường rollback

- Thêm `products.brand_id` nullable.
- Backfill qua `brands.name = products.brand`.
- Application đọc/ghi `brand_id` và join `brands`.
- Tạm giữ cột `products.brand` trong Phase 3 làm compatibility/rollback column;
  chưa drop trong phase này.
- Mọi product mới/cập nhật đồng bộ legacy name với brand được chọn.
- Việc drop legacy column chỉ được đề xuất ở phase sau khi production evidence
  đủ mạnh.

### 6.6 Index dự kiến

- `products(is_active, category_id, brand_id)`.
- `orders(user_id, created_at)`.
- `orders(status, created_at)`.
- Chỉ thêm index sau khi kiểm tra `SHOW INDEX` để không trùng index do foreign
  key tự tạo.

## 7. Checkout target flow

Logic đặt hàng được tách tối thiểu vào `includes/order_service.php` để route và
concurrency test dùng cùng một implementation:

```text
BEGIN TRANSACTION
    ↓
Sắp xếp product IDs để có lock order ổn định
    ↓
SELECT authoritative products ... FOR UPDATE
    ↓
Xác minh đủ product, active, quantity và stock
    ↓
Tính lại unit price và total trong transaction
    ↓
INSERT order
    ↓
INSERT order details kèm product_name + price snapshot
    ↓
UPDATE stock ... WHERE stock >= quantity
    ↓
Kiểm tra rowCount() = 1 cho từng product
    ↓
COMMIT
```

Nếu bất kỳ bước nào thất bại:

```text
ROLLBACK
Không xóa cart
Không để lại partial order/order detail/stock update
```

Chỉ `unset($_SESSION['cart'])` sau commit thành công.

## 8. Order status và stock restoration

Transition đề xuất:

```text
pending   → confirmed | cancelled
confirmed → shipping  | cancelled
shipping  → completed
completed → terminal
cancelled → terminal
```

Khi chuyển `pending` hoặc `confirmed` sang `cancelled`:

1. Begin transaction.
2. Lock order bằng `SELECT ... FOR UPDATE`.
3. Xác minh transition hiện tại → target.
4. Lock các product liên quan theo ID tăng dần.
5. Cộng lại stock từ order details.
6. Update status thành `cancelled`.
7. Commit.

Hai request cancel đồng thời chỉ được restore stock một lần vì request thứ hai
đọc status `cancelled` sau khi lấy lock và bị từ chối.

## 9. Trình tự implementation

### Change 1 — Migration foundation và schema inventory

**WHAT**

- Tạo `database/migrations/` và convention version.
- Tạo migration registry/CLI runner tối thiểu hoặc quy trình apply có kiểm soát.
- Ghi inventory table, FK, index và row count trước migration.
- Clone database baseline để rehearsal; không thử destructive migration trực
  tiếp trên database đang dùng làm baseline.

**FILES DỰ KIẾN**

- `database/migrations/README.md` bằng tiếng Việt.
- `database/migrations/000_create_schema_migrations_up.sql`.
- Rollback tương ứng nếu an toàn.
- CLI runner nhỏ nếu review xác nhận cần thiết.

**TEST**

- Apply đúng thứ tự, không apply cùng version hai lần.
- Failure dừng ở migration lỗi và báo version rõ ràng.
- Schema/data inventory trước và sau không đổi ngoài registry.

### Change 2 — Additive integrity migration

**WHAT**

- Thêm `products.is_active`, `products.archived_at`, `products.brand_id`.
- Thêm nullable `order_details.product_name`.
- Backfill brand ID và product name.
- Thêm brand FK `SET NULL` và các index không trùng.
- Chưa đổi/drop constraint cũ trong change này.

**RỦI RO**

- Brand string không match brand master.
- Backfill snapshot thiếu product do dữ liệu mồ côi.

**TEST**

- Preflight query phải liệt kê mismatch trước khi ALTER.
- Mọi existing product/order detail được backfill hoặc migration dừng.
- Row count, price, stock và order total không đổi.

### Change 3 — Catalog integrity và soft delete

**WHAT**

- Customer queries chỉ hiển thị product active.
- Admin product delete thành archive; thêm restore.
- Product form dùng `brand_id`, đồng bộ legacy `brand` để rollback.
- Brand listing/count join bằng ID.
- Category delete có thông báo rõ khi còn product.

**FILES DỰ KIẾN**

- `index.php`, `products.php`, `product_detail.php`, `cart.php`.
- `ajax/cart_action.php`.
- `admin/products.php`, `admin/brands.php`, `admin/categories.php`.

**TEST**

- Archived product biến mất khỏi customer pages và không thêm/checkout được.
- Admin vẫn xem/restore được.
- Archive không xóa product image hoặc order history.
- Brand rename không cần mass-update theo chuỗi để giữ relation.

### Change 4 — Authoritative transactional checkout

**WHAT**

- Tạo `includes/order_service.php`.
- Lock product rows theo thứ tự ổn định.
- Tính lại price/total trong transaction.
- Ghi `product_name` và `price` snapshot.
- Kiểm tra mọi stock update; rollback toàn bộ khi một item thất bại.

**FILES DỰ KIẾN**

- `includes/order_service.php` — mới.
- `checkout.php` — gọi service và map lỗi chung cho user.

**TEST**

- Happy path nhiều sản phẩm.
- Product thiếu/archived, quantity/stock sai, giá đổi trước submit.
- Một item fail làm toàn order rollback.
- Cart chỉ xóa sau commit.
- Technical detail vào log, không lộ SQL cho user.

### Change 5 — Finalize historical constraints

**WHAT**

- Chuyển `order_details.product_name` thành `NOT NULL`.
- Đổi product/order-detail FK từ `CASCADE` sang `RESTRICT`.
- Đổi category/product FK từ `CASCADE` sang `RESTRICT`.
- Cập nhật `database/schema.sql` để fresh install phản ánh schema cuối.

**TEST**

- Không thể xóa vật lý product/category đang được tham chiếu.
- Order/detail count không đổi.
- Fresh schema và upgraded schema có table/column/FK/index tương đương.
- Rehearse rollback hoặc backup restore theo tài liệu.

### Change 6 — Order transition và cancel stock restoration

**WHAT**

- Thêm transition map server-side.
- Status update chạy trong transaction với order row lock.
- Cancel từ state được phép restore stock đúng một lần.
- Invalid/backward/terminal transition bị từ chối.

**FILES DỰ KIẾN**

- `includes/order_service.php`.
- `admin/orders.php`.

**TEST**

- Test từng cạnh hợp lệ và transition không hợp lệ.
- `completed`/`cancelled` là terminal.
- Cancel tăng đúng stock từ order details.
- Hai cancel đồng thời chỉ restore một lần.

### Change 7 — Concurrency, migration và full regression

**WHAT**

- Chạy hai checkout đồng thời trên fixture stock = 1.
- Chạy hai cancel đồng thời.
- Test fresh install và upgrade từ Phase 2 schema.
- Chạy full customer/admin regression ở hai base path.
- Dọn fixture và lập báo cáo Phase 3.

**EXPECTED CONCURRENCY RESULT**

```text
Checkout A: success
Checkout B: out-of-stock/rollback
Final stock: 0
Orders committed: 1
Order details committed: đúng số dòng của order thắng
Không có partial order
```

## 10. Migration safety và rollback

- Trước mọi rehearsal: dump database test và ghi checksum/count.
- MySQL DDL có thể implicit commit; không tuyên bố rollback transaction cho
  ALTER TABLE.
- Additive migrations có down script khi không làm mất dữ liệu mới.
- Constraint finalize chỉ chạy sau preflight query `0` violation.
- Nếu down migration có thể làm mất `brand_id`, snapshot hoặc archive state,
  rollback chính thức là restore backup và rollback application code.
- Không chạy migration production/AWS trong Phase 3.
- Không dùng `DROP` column legacy `products.brand` trong phase này.

## 11. File dự kiến tạo mới

- `database/migrations/README.md`.
- Các migration `_up.sql` và `_down.sql` có version.
- `includes/order_service.php`.
- Test/concurrency harness trong `tests/integration/` nếu implementation cần.
- `docs/testing/PHASE_03_CHANGE_XX_*.md`.
- `docs/testing/PHASE_03_DATABASE_ORDER_INTEGRITY.md`.

Mọi file Markdown mới phải viết bằng tiếng Việt.

## 12. Rủi ro và giảm thiểu

| Rủi ro | Mức độ | Giảm thiểu |
|---|---|---|
| Migration làm mất lịch sử | CRITICAL | Clone rehearsal, preflight, backup, row-count checks |
| Deadlock khi checkout | HIGH | Lock product theo ID tăng dần, retry policy có giới hạn nếu cần |
| Partial order | HIGH | Một transaction, throw khi rowCount sai, rollback |
| Double stock restoration | HIGH | Lock order row và terminal transition |
| Archived product còn bán được | HIGH | Filter ở listing/detail/cart/checkout, negative tests |
| Fresh/upgraded schema lệch nhau | HIGH | Schema equivalence test |
| Brand migration mismatch | MEDIUM | Preflight report, không drop legacy column |
| DDL rollback bị hiểu sai | MEDIUM | Nêu rõ implicit commit và backup restore |

## 13. Tiêu chí hoàn thành Phase 3

- Versioned migrations và migration evidence `PASS`.
- Fresh schema và upgraded schema tương đương.
- Order history không mất khi archive product hoặc thử xóa category/product.
- Checkout đọc price/stock/availability authoritative trong transaction.
- Concurrent last-item checkout chỉ có một order thành công.
- Không có partial order khi một product fail.
- Order detail có product-name và price snapshot.
- Status transition hợp lệ được enforce server-side.
- Cancel restore stock đúng một lần, kể cả request đồng thời.
- Customer/admin regression hai base path `PASS`.
- Không còn fixture/database phụ/file test sau cleanup.
- Báo cáo tiếng Việt hoàn chỉnh.
- Human review trước Phase 4.

## 14. Approval gate

Kế hoạch Phase 3 đã được người dùng phê duyệt và implementation đã `PASS`.
Phase 3 đã được human review và duyệt để chuyển sang Phase 4. Không triển khai
AWS hoặc production migration trong phase này.
