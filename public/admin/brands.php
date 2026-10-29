<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isAdmin()) {
    redirectTo('/login.php');
}

// Xử lý xóa bằng POST
if (($_POST['action'] ?? '') === 'delete') {
    requirePostRequest();
    requireValidCsrfToken();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) securityFailureResponse(400, 'ID nhãn hàng không hợp lệ.');
    $conn->prepare("UPDATE products SET brand_id = NULL, brand = '' WHERE brand_id = ?")->execute([$id]);
    $conn->prepare("DELETE FROM brands WHERE id = ?")->execute([$id]);
    setFlash('success', 'Đã xóa nhãn hàng');
    redirectTo('/admin/brands.php');
}

// Xử lý thêm/sửa
$editing = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $conn->prepare("SELECT * FROM brands WHERE id = ?");
        $stmt->execute([$editId]);
        $editing = $stmt->fetch();
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'delete') {
    requireValidCsrfToken();
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $editingId = (int)($_POST['id'] ?? 0);

    if (empty($name)) $errors[] = 'Tên nhãn hàng không được để trống';
    if (mb_strlen($name) > 100) $errors[] = 'Tên nhãn hàng quá dài';
    if (mb_strlen($icon) > 100) $errors[] = 'Tên icon quá dài';

    // Kiểm tra trùng tên
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM brands WHERE name = ? AND id != ?");
        $check->execute([$name, $editingId]);
        if ($check->fetch()) {
            $errors[] = 'Nhãn hàng này đã tồn tại';
        }
    }

    if (empty($errors)) {
        if ($editingId > 0) {
            $stmt = $conn->prepare("UPDATE brands SET name=?, icon=? WHERE id=?");
            $stmt->execute([$name, $icon, $editingId]);
            $conn->prepare("UPDATE products SET brand = ? WHERE brand_id = ?")->execute([$name, $editingId]);
            setFlash('success', 'Cập nhật nhãn hàng thành công');
        } else {
            $stmt = $conn->prepare("INSERT INTO brands (name, icon) VALUES (?, ?)");
            $stmt->execute([$name, $icon]);
            setFlash('success', 'Thêm nhãn hàng thành công');
        }
        redirectTo('/admin/brands.php');
    }
}

// Lấy danh sách nhãn hàng + số sản phẩm
$brands = $conn->query("SELECT b.*, COUNT(p.id) as product_count FROM brands b LEFT JOIN products p ON p.brand_id = b.id AND p.is_active = 1 GROUP BY b.id ORDER BY b.name")->fetchAll();

require_once __DIR__ . '/admin_header.php';
?>

<div class="admin-header">
    <h1>Quản lý nhãn hàng</h1>
    <a href="<?= sanitize(appUrl('/admin/brands.php?edit=0')) ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm nhãn hàng</a>
</div>

<?php if (isset($_GET['edit']) || !empty($errors)): ?>
<div class="admin-form" style="margin-bottom: 24px;">
    <h2><?= $editing ? 'Sửa nhãn hàng' : 'Thêm nhãn hàng mới' ?></h2>

    <?php if (!empty($errors)): ?>
    <div class="flash flash-error"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= $editing['id'] ?? 0 ?>">

        <div class="form-group">
            <label>Tên nhãn hàng *</label>
            <input type="text" name="name" required value="<?= sanitize($_POST['name'] ?? ($editing['name'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label>Icon (Font Awesome class)</label>
            <input type="text" name="icon" placeholder="vd: fa-brands fa-apple" value="<?= sanitize($_POST['icon'] ?? ($editing['icon'] ?? '')) ?>">
            <small style="color:var(--palette-muted);">Tìm icon tại <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com/icons</a></small>
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Cập nhật' : 'Thêm nhãn hàng' ?></button>
            <a href="<?= sanitize(appUrl('/admin/brands.php')) ?>" class="btn btn-outline">Hủy</a>
        </div>
    </form>
</div>
<?php endif; ?>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Icon</th>
            <th>Tên nhãn hàng</th>
            <th>Số sản phẩm</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($brands as $b): ?>
        <tr>
            <td><?= $b['id'] ?></td>
            <td><i class="<?= sanitize($b['icon']) ?>" style="font-size:20px; color:var(--palette-accent);"></i></td>
            <td><?= sanitize($b['name']) ?></td>
            <td><?= $b['product_count'] ?></td>
            <td>
                <div class="actions">
                    <a href="<?= sanitize(appUrl('/admin/brands.php?edit=' . $b['id'])) ?>" class="btn-edit"><i class="fa-solid fa-pen"></i> Sửa</a>
                    <form method="POST" style="display:inline" onsubmit="return confirmDelete('Xóa nhãn hàng này?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <button type="submit" class="btn-delete"><i class="fa-solid fa-trash"></i> Xóa</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
