<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

requirePostRequest(true);
requireValidCsrfToken(true);

$action = $_POST['action'] ?? '';
$productId = validatedInteger($_POST['product_id'] ?? null, 1, PHP_INT_MAX);
$quantity = validatedInteger($_POST['quantity'] ?? 1, 1, 99);

if (!in_array($action, ['add', 'update', 'remove'], true) || $productId === null) {
    securityFailureResponse(400, 'Dữ liệu giỏ hàng không hợp lệ.', true);
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {
    case 'add':
        if ($quantity === null) {
            securityFailureResponse(400, 'Số lượng phải từ 1 đến 99.', true);
        }
        // Kiểm tra sản phẩm tồn tại
        $stmt = $conn->prepare("SELECT id, stock FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product || (int) $product['stock'] <= 0) {
            securityFailureResponse(400, 'Sản phẩm không tồn tại hoặc đã hết hàng.', true);
        }

        $currentQuantity = (int) ($_SESSION['cart'][$productId]['quantity'] ?? 0);
        if ($currentQuantity + $quantity > min(99, (int) $product['stock'])) {
            securityFailureResponse(400, 'Số lượng vượt quá tồn kho cho phép.', true);
        }

        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = ['quantity' => $quantity];
        }

        echo json_encode([
            'success' => true,
            'cart_count' => getCartCount(),
            'message' => 'Đã thêm vào giỏ hàng'
        ]);
        break;

    case 'update':
        if ($quantity === null) {
            securityFailureResponse(400, 'Số lượng phải từ 1 đến 99.', true);
        }
        $stmt = $conn->prepare("SELECT stock, is_active FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        $stock = $product['stock'] ?? false;
        if ($product === false || (int) $product['is_active'] !== 1 || $quantity > min(99, (int) $stock)) {
            securityFailureResponse(400, 'Số lượng vượt quá tồn kho cho phép.', true);
        }
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
        }
        echo json_encode(['success' => true, 'cart_count' => getCartCount()]);
        break;

    case 'remove':
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            setFlash('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
        }
        echo json_encode(['success' => true, 'cart_count' => getCartCount()]);
        break;

}
