<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isAdmin()) {
    redirectTo('/login.php');
}

// Xử lý AJAX thêm brand mới
if (isset($_POST['ajax_add_brand'])) {
    header('Content-Type: application/json');
    requirePostRequest(true);
    requireValidCsrfToken(true);
    $brandName = trim($_POST['brand_name'] ?? '');
    if ($brandName) {
        $check = $conn->prepare("SELECT id FROM brands WHERE name = ?");
        $check->execute([$brandName]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Nhãn hàng đã tồn tại']);
        } else {
            $stmt = $conn->prepare("INSERT INTO brands (name, icon) VALUES (?, 'fa-solid fa-tag')");
            $stmt->execute([$brandName]);
            echo json_encode(['success' => true, 'name' => $brandName, 'id' => $conn->lastInsertId()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Tên không được để trống']);
    }
    exit;
}

// Xử lý archive/restore bằng POST
if (($_POST['action'] ?? '') === 'delete') {
    requirePostRequest();
    requireValidCsrfToken();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) securityFailureResponse(400, 'ID sản phẩm không hợp lệ.');
    $stmt = $conn->prepare("UPDATE products SET is_active = 0, archived_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Đã lưu trữ sản phẩm');
    redirectTo('/admin/products.php');
}

if (($_POST['action'] ?? '') === 'restore') {
    requirePostRequest();
    requireValidCsrfToken();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) securityFailureResponse(400, 'ID sản phẩm không hợp lệ.');
    $stmt = $conn->prepare("UPDATE products SET is_active = 1, archived_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Đã khôi phục sản phẩm');
    redirectTo('/admin/products.php');
}

// Xử lý thêm/sửa
$editing = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_add_brand']) && !in_array($_POST['action'] ?? '', ['delete', 'restore'], true)) {
    requireValidCsrfToken();
    $name = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $brandId = validatedInteger($_POST['brand_id'] ?? 0, 0, PHP_INT_MAX) ?: null;
    $brand = '';
    $priceInput = $_POST['price'] ?? '';
    $salePriceInput = $_POST['sale_price'] ?? '0';
    $stock = validatedInteger($_POST['stock'] ?? 0, 0, 1000000);
    $price = is_numeric($priceInput) ? (float) $priceInput : -1;
    $salePrice = is_numeric($salePriceInput) ? (float) $salePriceInput : -1;
    $description = trim($_POST['description'] ?? '');
    $editingId = (int)($_POST['id'] ?? 0);
    $previousImage = $editingId > 0 && $editing ? ($editing['image'] ?? '') : '';

    $image = '';
    if ($editingId > 0 && $editing) {
        $image = $editing['image'] ?? '';
    }

    if (empty($name)) $errors[] = 'Tên sản phẩm không được để trống';
    if (mb_strlen($name) > 255) $errors[] = 'Tên sản phẩm quá dài';
    if ($categoryId <= 0) $errors[] = 'Chọn danh mục';
    if (!is_finite($price) || $price <= 0 || $price > 999999999999999) $errors[] = 'Giá không hợp lệ';
    if (!is_finite($salePrice) || $salePrice < 0 || $salePrice > $price) $errors[] = 'Giá khuyến mãi không hợp lệ';
    if ($stock === null) $errors[] = 'Tồn kho không hợp lệ';

    if (empty($errors)) {
        $categoryCheck = $conn->prepare("SELECT id FROM categories WHERE id = ?");
        $categoryCheck->execute([$categoryId]);
        if (!$categoryCheck->fetchColumn()) $errors[] = 'Danh mục không tồn tại';

        if ($brandId !== null) {
            $brandCheck = $conn->prepare("SELECT name FROM brands WHERE id = ?");
            $brandCheck->execute([$brandId]);
            $brand = (string) ($brandCheck->fetchColumn() ?: '');
            if ($brand === '') $errors[] = 'Nhãn hàng không tồn tại';
        }
    }

    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $image = storeRuntimeImage($_FILES['image'], 'products', 5 * 1024 * 1024);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        if ($editingId > 0) {
            $stmt = $conn->prepare("UPDATE products SET name=?, category_id=?, brand_id=?, brand=?, price=?, sale_price=?, stock=?, description=?, image=? WHERE id=?");
            $stmt->execute([$name, $categoryId, $brandId, $brand, $price, $salePrice, $stock, $description, $image, $editingId]);
            if ($previousImage && $image !== $previousImage) {
                deleteRuntimeImage($previousImage, 'assets/images', 'product');
            }
            setFlash('success', 'Cập nhật sản phẩm thành công');
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, category_id, brand_id, brand, price, sale_price, stock, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $categoryId, $brandId, $brand, $price, $salePrice, $stock, $description, $image]);
            setFlash('success', 'Thêm sản phẩm thành công');
        }
        redirectTo('/admin/products.php');
    }
}

// Lấy danh sách danh mục và nhãn hàng cho dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$brands = $conn->query("SELECT * FROM brands ORDER BY name")->fetchAll();

// Lấy danh sách sản phẩm
$products = $conn->query("SELECT p.*, c.name as category_name, b.name as brand_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON b.id = p.brand_id ORDER BY p.is_active DESC, p.created_at DESC")->fetchAll();

require_once __DIR__ . '/admin_header.php';
?>

<div class="admin-header">
    <h1><?= $editing ? 'Sửa sản phẩm' : 'Quản lý sản phẩm' ?></h1>
    <?php if (!$editing): ?>
    <a href="<?= sanitize(appUrl('/admin/products.php?edit=0')) ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm sản phẩm</a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['edit']) || !empty($errors)): ?>
<!-- Form thêm/sửa -->
<div class="admin-form" style="margin-bottom: 24px;">
    <h2><?= $editing ? 'Sửa sản phẩm: ' . sanitize($editing['name']) : 'Thêm sản phẩm mới' ?></h2>

    <?php if (!empty($errors)): ?>
    <div class="flash flash-error"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= $editing['id'] ?? 0 ?>">

        <div class="form-group">
            <label>Tên sản phẩm *</label>
            <input type="text" name="name" required value="<?= sanitize($_POST['name'] ?? ($editing['name'] ?? '')) ?>">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label>Danh mục *</label>
                <select name="category_id" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($editing['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= sanitize($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Nhãn hàng</label>
                <div style="display:flex; gap:8px;">
                    <select name="brand_id" id="brandSelect" style="flex:1;">
                        <option value="">-- Chọn nhãn hàng --</option>
                        <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= (int)($editing['brand_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>>
                            <?= sanitize($b['name']) ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="__new__">+ Thêm nhãn hàng mới</option>
                    </select>
                </div>
                <!-- Popup thêm brand mới -->
                <div id="newBrandBox" style="display:none; margin-top:8px; padding:12px; background:var(--palette-surface); border-radius:8px; border:1px solid var(--palette-border);">
                    <label style="font-size:13px; font-weight:600;">Tên nhãn hàng mới:</label>
                    <div style="display:flex; gap:8px; margin-top:6px;">
                        <input type="text" id="newBrandName" placeholder="Nhập tên nhãn hàng" style="flex:1;">
                        <button type="button" id="saveBrandBtn" class="btn btn-primary" style="padding:8px 16px; font-size:13px;">Lưu</button>
                        <button type="button" id="cancelBrandBtn" class="btn btn-outline" style="padding:8px 12px; font-size:13px;">Hủy</button>
                    </div>
                    <div id="brandMsg" style="margin-top:6px; font-size:12px;"></div>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label>Giá gốc (VNĐ) *</label>
                <input type="number" name="price" required min="0" value="<?= $_POST['price'] ?? ($editing['price'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Giá khuyến mãi (VNĐ)</label>
                <input type="number" name="sale_price" min="0" value="<?= $_POST['sale_price'] ?? ($editing['sale_price'] ?? 0) ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Tồn kho</label>
            <input type="number" name="stock" min="0" value="<?= $_POST['stock'] ?? ($editing['stock'] ?? 0) ?>">
        </div>

        <div class="form-group">
            <label>Hình ảnh</label>
            <input type="file" name="image" accept="image/*">
            <?php if ($editing && $editing['image']): ?>
                <div style="margin-top: 10px;">
                    <img src="<?= sanitize(runtimeImageUrl($editing['image'], 'assets/images')) ?>" alt="Ảnh hiện tại" style="max-height: 100px;">
                    <p><small>Ảnh hiện tại. Nếu không muốn đổi ảnh, vui lòng để trống.</small></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Mô tả</label>
            <textarea name="description" rows="4"><?= sanitize($_POST['description'] ?? ($editing['description'] ?? '')) ?></textarea>
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Cập nhật' : 'Thêm sản phẩm' ?></button>
            <a href="<?= sanitize(appUrl('/admin/products.php')) ?>" class="btn btn-outline">Hủy</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Danh sách sản phẩm -->
<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Sản phẩm</th>
            <th>Danh mục</th>
            <th>Nhãn hàng</th>
            <th>Giá</th>
            <th>Giá KM</th>
            <th>Kho</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= sanitize($p['name']) ?></td>
            <td><?= sanitize($p['category_name']) ?></td>
            <td><?= sanitize($p['brand_name'] ?? $p['brand'] ?? '') ?><?= (int)$p['is_active'] !== 1 ? ' (Đã lưu trữ)' : '' ?></td>
            <td><?= formatPrice($p['price']) ?></td>
            <td><?= $p['sale_price'] > 0 ? formatPrice($p['sale_price']) : '-' ?></td>
            <td><?= $p['stock'] ?></td>
            <td>
                <div class="actions">
                    <a href="<?= sanitize(appUrl('/admin/products.php?edit=' . $p['id'])) ?>" class="btn-edit"><i class="fa-solid fa-pen"></i> Sửa</a>
                    <form method="POST" style="display:inline" onsubmit="return confirmDelete('Lưu trữ sản phẩm này?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn-delete"><i class="fa-solid fa-box-archive"></i> Lưu trữ</button>
                    </form>
                    <?php if ((int)$p['is_active'] !== 1): ?>
                    <form method="POST" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="restore">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn-edit"><i class="fa-solid fa-box-open"></i> Khôi phục</button>
                    </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
(function() {
    var brandSelect = document.getElementById('brandSelect');
    var newBrandBox = document.getElementById('newBrandBox');
    var newBrandName = document.getElementById('newBrandName');
    var saveBrandBtn = document.getElementById('saveBrandBtn');
    var cancelBrandBtn = document.getElementById('cancelBrandBtn');
    var brandMsg = document.getElementById('brandMsg');

    if (!brandSelect) return;

    brandSelect.addEventListener('change', function() {
        if (this.value === '__new__') {
            newBrandBox.style.display = 'block';
            newBrandName.focus();
            this.value = '';
        } else {
            newBrandBox.style.display = 'none';
        }
    });

    cancelBrandBtn.addEventListener('click', function() {
        newBrandBox.style.display = 'none';
        newBrandName.value = '';
        brandMsg.textContent = '';
    });

    saveBrandBtn.addEventListener('click', function() {
        var name = newBrandName.value.trim();
        if (!name) {
            brandMsg.innerHTML = '<span style="color:red;">Vui lòng nhập tên nhãn hàng</span>';
            return;
        }
        saveBrandBtn.disabled = true;
        saveBrandBtn.textContent = 'Đang lưu...';

        var formData = new FormData();
        formData.append('ajax_add_brand', '1');
        formData.append('brand_name', name);
        formData.append('_csrf_token', (window.AWS_ECOMMERCE_PLATFORM_CONFIG || {}).csrfToken || '');

        fetch(<?= json_encode(appUrl('/admin/products.php'), JSON_UNESCAPED_SLASHES) ?>, { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                // Thêm option mới vào dropdown
                var opt = document.createElement('option');
                opt.value = data.name;
                opt.textContent = data.name;
                opt.selected = true;
                // Chèn trước option "+ Thêm nhãn hàng mới"
                var lastOpt = brandSelect.querySelector('option[value="__new__"]');
                brandSelect.insertBefore(opt, lastOpt);
                // Ẩn box
                newBrandBox.style.display = 'none';
                newBrandName.value = '';
                brandMsg.textContent = '';
            } else {
                brandMsg.innerHTML = '<span style="color:red;">' + data.message + '</span>';
            }
            saveBrandBtn.disabled = false;
            saveBrandBtn.textContent = 'Lưu';
        })
        .catch(function() {
            brandMsg.innerHTML = '<span style="color:red;">Lỗi kết nối</span>';
            saveBrandBtn.disabled = false;
            saveBrandBtn.textContent = 'Lưu';
        });
    });

    newBrandName.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); saveBrandBtn.click(); }
    });
})();
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
