<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Chi chay script nay bang CLI.\n");
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$projectRoot = dirname(__DIR__);
$imageRoot = $projectRoot . '/public/assets/images';
$placeholder = 'product-placeholder.jpg';
$placeholderPath = $imageRoot . '/' . $placeholder;

if (!is_file($placeholderPath)) {
    fwrite(STDERR, "Khong tim thay placeholder: {$placeholderPath}\n");
    exit(1);
}

$conn->beginTransaction();
$updated = [];
try {
    $statement = $conn->query('SELECT id, image FROM products ORDER BY id');
    while ($product = $statement->fetch()) {
        $image = trim((string) $product['image']);
        $exists = false;
        if ($image !== '') {
            if (preg_match('#^https?://#i', $image)) {
                $exists = true;
            } elseif (isStorageObjectKey($image)) {
                $exists = storage()->exists($image);
            } else {
                $exists = is_file($imageRoot . '/' . basename($image));
            }
        }
        if ($exists) {
            continue;
        }
        $update = $conn->prepare('UPDATE products SET image = :image WHERE id = :id');
        $update->execute(['image' => $placeholder, 'id' => (int) $product['id']]);
        $updated[] = ['id' => (int) $product['id'], 'from' => $image, 'to' => $placeholder];
    }
    $conn->commit();
} catch (Throwable $exception) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    fwrite(STDERR, 'Khong the sua du lieu catalog: ' . $exception->getMessage() . "\n");
    exit(1);
}

echo json_encode([
    'placeholder' => $placeholder,
    'updated_count' => count($updated),
    'updated' => $updated,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
