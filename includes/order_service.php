<?php

class OrderServiceException extends RuntimeException
{
    private string $userMessage;

    public function __construct(string $userMessage, string $technicalMessage = '')
    {
        parent::__construct($technicalMessage !== '' ? $technicalMessage : $userMessage);
        $this->userMessage = $userMessage;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }
}

/**
 * Tạo đơn hàng bằng snapshot dữ liệu authoritative trong một transaction.
 * Cart có dạng [product_id => ['quantity' => số lượng]].
 */
function createOrder(PDO $connection, ?int $userId, array $customer, array $cart): int
{
    if ($cart === []) {
        throw new OrderServiceException('Giỏ hàng đang trống.');
    }

    $items = [];
    foreach ($cart as $productId => $cartItem) {
        $productId = filter_var($productId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $quantity = filter_var($cartItem['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        if ($productId === false || $quantity === false) {
            throw new OrderServiceException('Giỏ hàng có dữ liệu không hợp lệ.');
        }
        $items[(int) $productId] = (int) $quantity;
    }

    ksort($items, SORT_NUMERIC);

    try {
        $connection->beginTransaction();

        $placeholders = implode(',', array_fill(0, count($items), '?'));
        $productStatement = $connection->prepare(
            "SELECT id, name, price, sale_price, stock, is_active FROM products WHERE id IN ($placeholders) ORDER BY id FOR UPDATE"
        );
        $productStatement->execute(array_keys($items));
        $products = [];
        foreach ($productStatement->fetchAll() as $product) {
            $products[(int) $product['id']] = $product;
        }

        if (count($products) !== count($items)) {
            throw new OrderServiceException('Một sản phẩm trong giỏ hàng không còn tồn tại.');
        }

        $total = 0;
        foreach ($items as $productId => $quantity) {
            $product = $products[$productId];
            if ((int) $product['is_active'] !== 1) {
                throw new OrderServiceException('Một sản phẩm trong giỏ hàng đã được lưu trữ.');
            }
            if ((int) $product['stock'] < $quantity) {
                throw new OrderServiceException('Sản phẩm "' . $product['name'] . '" không đủ tồn kho.');
            }
            $unitPrice = (float) $product['sale_price'] > 0 ? (float) $product['sale_price'] : (float) $product['price'];
            $total += $unitPrice * $quantity;
        }

        $orderStatement = $connection->prepare(
            'INSERT INTO orders (user_id, fullname, email, phone, address, total_amount, note, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $orderStatement->execute([
            $userId,
            $customer['fullname'],
            $customer['email'],
            $customer['phone'],
            $customer['address'],
            $total,
            $customer['note'],
            $customer['payment_method'],
        ]);
        $orderId = (int) $connection->lastInsertId();

        $detailStatement = $connection->prepare(
            'INSERT INTO order_details (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)'
        );
        $stockStatement = $connection->prepare(
            'UPDATE products SET stock = stock - ? WHERE id = ? AND is_active = 1 AND stock >= ?'
        );

        foreach ($items as $productId => $quantity) {
            $product = $products[$productId];
            $unitPrice = (float) $product['sale_price'] > 0 ? (float) $product['sale_price'] : (float) $product['price'];
            $detailStatement->execute([$orderId, $productId, $product['name'], $quantity, $unitPrice]);
            $stockStatement->execute([$quantity, $productId, $quantity]);
            if ($stockStatement->rowCount() !== 1) {
                throw new OrderServiceException('Tồn kho đã thay đổi, vui lòng thử lại.');
            }
        }

        $connection->commit();
        return $orderId;
    } catch (OrderServiceException $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        error_log('Tạo đơn hàng thất bại: ' . $exception->getMessage());
        throw new OrderServiceException('Có lỗi xảy ra, vui lòng thử lại.', $exception->getMessage());
    }
}

function updateOrderStatus(PDO $connection, int $orderId, string $targetStatus): void
{
    $transitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['shipping', 'cancelled'],
        'shipping' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    if ($orderId <= 0 || !array_key_exists($targetStatus, $transitions)) {
        throw new OrderServiceException('Trạng thái đơn hàng không hợp lệ.');
    }

    try {
        $connection->beginTransaction();
        $orderStatement = $connection->prepare('SELECT id, status FROM orders WHERE id = ? FOR UPDATE');
        $orderStatement->execute([$orderId]);
        $order = $orderStatement->fetch();
        if (!$order) {
            throw new OrderServiceException('Không tìm thấy đơn hàng.');
        }
        if (!in_array($targetStatus, $transitions[$order['status']] ?? [], true)) {
            throw new OrderServiceException('Không thể chuyển đơn hàng từ trạng thái hiện tại sang trạng thái mới.');
        }

        if ($targetStatus === 'cancelled') {
            $detailStatement = $connection->prepare('SELECT product_id, quantity FROM order_details WHERE order_id = ? ORDER BY product_id FOR UPDATE');
            $detailStatement->execute([$orderId]);
            $details = $detailStatement->fetchAll();
            foreach ($details as $detail) {
                $stockStatement = $connection->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
                $stockStatement->execute([(int) $detail['quantity'], (int) $detail['product_id']]);
                if ($stockStatement->rowCount() !== 1) {
                    throw new OrderServiceException('Không thể hoàn tồn kho cho đơn hàng.');
                }
            }
        }

        $updateStatement = $connection->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $updateStatement->execute([$targetStatus, $orderId]);
        $connection->commit();
    } catch (OrderServiceException $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        error_log('Cập nhật trạng thái đơn hàng thất bại: ' . $exception->getMessage());
        throw new OrderServiceException('Không thể cập nhật trạng thái đơn hàng.', $exception->getMessage());
    }
}

