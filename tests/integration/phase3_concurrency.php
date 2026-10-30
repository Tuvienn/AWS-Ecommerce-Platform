<?php

if (PHP_SAPI !== 'cli') {
    exit("Chỉ chạy bằng PHP CLI.\n");
}

require_once __DIR__ . '/../../includes/db.php';

$productId = 1;
$original = $conn->prepare('SELECT stock, is_active FROM products WHERE id = ?');
$original->execute([$productId]);
$original = $original->fetch();
if (!$original) {
    throw new RuntimeException('Không tìm thấy product fixture.');
}

$conn->prepare('DELETE od FROM order_details od INNER JOIN orders o ON o.id = od.order_id WHERE o.note = ?')->execute(['PHASE3_CONCURRENCY']);
$conn->prepare('DELETE FROM orders WHERE note = ?')->execute(['PHASE3_CONCURRENCY']);
$conn->prepare('UPDATE products SET stock = 1, is_active = 1, archived_at = NULL WHERE id = ?')->execute([$productId]);

$resultDirectory = sys_get_temp_dir() . '/webbanhang-phase3-concurrency-' . getmypid();
mkdir($resultDirectory, 0700, true);
$children = [];
for ($worker = 1; $worker <= 2; $worker++) {
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Không thể tạo worker.');
    }
    if ($pid === 0) {
        $childConfiguration = require __DIR__ . '/../../config/database.php';
        $childDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $childConfiguration['host'], $childConfiguration['port'], $childConfiguration['name'], $childConfiguration['charset']);
        $conn = new PDO($childDsn, $childConfiguration['user'], $childConfiguration['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $output = ['worker' => $worker, 'success' => false];
        try {
            $id = \App\Services\OrderService::createOrder($conn, 1, [
                'fullname' => 'Phase3 Worker ' . $worker,
                'email' => 'phase3-worker-' . $worker . '@example.com',
                'phone' => '0900000000',
                'address' => 'Concurrency fixture',
                'note' => 'PHASE3_CONCURRENCY',
                'payment_method' => 'COD',
            ], [$productId => ['quantity' => 1]]);
            $output = ['worker' => $worker, 'success' => true, 'order_id' => $id];
        } catch (Throwable $exception) {
            $output['error'] = $exception->getMessage();
        }
        file_put_contents($resultDirectory . '/worker-' . $worker . '.json', json_encode($output, JSON_UNESCAPED_UNICODE));
        exit(0);
    }
    $children[] = $pid;
}

foreach ($children as $child) {
    pcntl_waitpid($child, $status);
}

$parentConfiguration = require __DIR__ . '/../../config/database.php';
$parentDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $parentConfiguration['host'], $parentConfiguration['port'], $parentConfiguration['name'], $parentConfiguration['charset']);
$conn = new PDO($parentDsn, $parentConfiguration['user'], $parentConfiguration['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$results = [];
foreach (glob($resultDirectory . '/worker-*.json') ?: [] as $file) {
    $results[] = json_decode(file_get_contents($file), true);
    unlink($file);
}
rmdir($resultDirectory);

$stock = (int) $conn->query('SELECT stock FROM products WHERE id = ' . $productId)->fetchColumn();
$orders = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE note = 'PHASE3_CONCURRENCY'")->fetchColumn();
$successful = count(array_filter($results, static fn (array $result): bool => !empty($result['success'])));

$conn->prepare('DELETE od FROM order_details od INNER JOIN orders o ON o.id = od.order_id WHERE o.note = ?')->execute(['PHASE3_CONCURRENCY']);
$conn->prepare('DELETE FROM orders WHERE note = ?')->execute(['PHASE3_CONCURRENCY']);
$conn->prepare('UPDATE products SET stock = ?, is_active = ?, archived_at = NULL WHERE id = ?')->execute([(int) $original['stock'], (int) $original['is_active'], $productId]);

echo json_encode([
    'workers' => $results,
    'successful_orders' => $successful,
    'committed_orders' => $orders,
    'final_stock_before_cleanup' => $stock,
    'expected' => ['successful_orders' => 1, 'committed_orders' => 1, 'final_stock_before_cleanup' => 0],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;

if ($successful !== 1 || $orders !== 1 || $stock !== 0) {
    exit(1);
}

