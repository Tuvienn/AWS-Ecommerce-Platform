<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$productRepository = new \App\Repositories\ProductRepository($conn);
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

if (!isLoggedIn()) {
    setFlash('error', 'Vui lòng đăng nhập để tiếp tục thanh toán.');
    redirectTo('/login.php');
}

if (empty($cart)) {
    redirectTo('/cart.php');
}

// Lấy thông tin sản phẩm trong giỏ
$ids = array_keys($cart);
$cartProducts = $productRepository->findByIds($ids);

$total = 0;
foreach ($cartProducts as $p) {
    $price = $p['sale_price'] > 0 ? $p['sale_price'] : $p['price'];
    $total += $price * $cart[$p['id']]['quantity'];
}

// Xử lý đặt hàng
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'COD');
    $allowedPaymentMethods = ['COD', 'Bank Card'];

    if (empty($fullname)) $errors[] = 'Vui lòng nhập họ tên';
    if (mb_strlen($fullname) > 100) $errors[] = 'Họ tên không được vượt quá 100 ký tự';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    if (mb_strlen($email) > 100) $errors[] = 'Email không được vượt quá 100 ký tự';
    if (empty($phone)) $errors[] = 'Vui lòng nhập số điện thoại';
    if (mb_strlen($phone) > 20) $errors[] = 'Số điện thoại không được vượt quá 20 ký tự';
    if (empty($address)) $errors[] = 'Vui lòng nhập địa chỉ';
    if (mb_strlen($address) > 1000) $errors[] = 'Địa chỉ quá dài';
    if (mb_strlen($note) > 2000) $errors[] = 'Ghi chú quá dài';
    if (!in_array($payment_method, $allowedPaymentMethods, true)) $errors[] = 'Phương thức thanh toán không hợp lệ';

    if (empty($errors)) {
        try {
            $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
            $orderId = \App\Services\OrderService::createOrder($conn, $userId, [
                'fullname' => $fullname,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'note' => $note,
                'payment_method' => $payment_method,
            ], $cart);

            // Xóa giỏ hàng
            unset($_SESSION['cart']);
            redirectTo('/success.php?order_id=' . $orderId);
        } catch (\App\Services\OrderServiceException $e) {
            $errors[] = $e->getUserMessage();
        }
    }
}

// Pre-fill nếu đã đăng nhập
$prefill = [
    'fullname' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
];
if (isLoggedIn()) {
    $userStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
    if ($user) {
        $prefill = [
            'fullname' => $user['fullname'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
            'address' => $user['address'] ?? ''
        ];
    }
}

require_once __DIR__ . '/../resources/views/layouts/header.php';
?>

<div class="breadcrumb">
    <a href="<?= sanitize(appUrl('/')) ?>">Trang chủ</a>
    <span>/</span>
    <a href="<?= sanitize(appUrl('/cart.php')) ?>">Giỏ hàng</a>
    <span>/</span>
    <strong>Đặt hàng</strong>
</div>

<?php if (!empty($errors)): ?>
<div class="flash flash-error">
    <?= implode('<br>', $errors) ?>
    <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
</div>
<?php endif; ?>

<div class="checkout-page">
    <form class="checkout-form" method="POST" onsubmit="return validateForm(this)">
        <?= csrfField() ?>
        <h2><i class="fa-solid fa-truck"></i> Thông tin giao hàng</h2>

        <div class="form-group">
            <label>Họ và tên *</label>
            <input type="text" name="fullname" required value="<?= sanitize($_POST['fullname'] ?? $prefill['fullname']) ?>">
        </div>

        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" required value="<?= sanitize($_POST['email'] ?? $prefill['email']) ?>">
        </div>

        <div class="form-group">
            <label>Số điện thoại *</label>
            <input type="tel" name="phone" required value="<?= sanitize($_POST['phone'] ?? $prefill['phone']) ?>">
        </div>

        <div class="form-group">
            <label>Địa chỉ giao hàng *</label>
            <div class="location-input js-location-input">
                <textarea name="address" class="js-address-textarea" required><?= sanitize($_POST['address'] ?? $prefill['address']) ?></textarea>
                <input type="hidden" class="js-location-lat" value="">
                <input type="hidden" class="js-location-lng" value="">
                <div class="location-tools">
                    <button type="button" class="btn btn-secondary btn-location js-get-current-location">
                        <i class="fa-solid fa-location-crosshairs"></i> Dùng vị trí hiện tại
                    </button>
                    <a href="#" class="location-map-link js-open-map" target="_blank" rel="noopener" hidden>
                        <i class="fa-solid fa-map-location-dot"></i> Xem vị trí đã chọn
                    </a>
                </div>
                <div class="location-coordinates js-location-coordinates">Bấm nút để mở bản đồ, ghim sẽ đặt sẵn ở vị trí hiện tại để bạn xác nhận hoặc kéo sang điểm giao hàng khác.</div>
                <div class="location-status js-location-status">Có thể dùng GPS hoặc kéo ghim trực tiếp trên bản đồ trước khi xác nhận địa chỉ giao hàng.</div>
            </div>
        </div>

        <div class="form-group">
            <label>Ghi chú</label>
            <textarea name="note"><?= sanitize($_POST['note'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Phương thức thanh toán *</label>
            <div class="payment-methods">
                <label class="payment-method-label">
                    <input type="radio" name="payment_method" value="COD" checked>
                    <span><i class="fa-solid fa-money-bill-wave"></i> Thanh toán khi nhận hàng (COD)</span>
                </label>
                <label class="payment-method-label">
                    <input type="radio" name="payment_method" value="Bank Card">
                    <span><i class="fa-solid fa-credit-card"></i> Thẻ ngân hàng (ATM/Visa/MasterCard)</span>
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">
            <i class="fa-solid fa-check"></i> Xác nhận đặt hàng
        </button>
    </form>

    <div class="checkout-summary">
        <h3><i class="fa-solid fa-receipt"></i> Đơn hàng của bạn</h3>
        <?php foreach ($cartProducts as $p):
            $price = $p['sale_price'] > 0 ? $p['sale_price'] : $p['price'];
            $qty = $cart[$p['id']]['quantity'];
        ?>
        <div class="checkout-item">
            <span class="item-name"><?= sanitize($p['name']) ?> x<?= $qty ?></span>
            <span><?= formatPrice($price * $qty) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="checkout-total">
            <span>Tổng cộng:</span>
            <span><?= formatPrice($total) ?></span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>
