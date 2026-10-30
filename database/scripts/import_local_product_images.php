<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Chi chay script nay bang CLI.\n");
}

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$apply = in_array('--apply', $argv, true);
$sourceRoot = dirname(__DIR__, 2) . '/images';
$mapping = [
    1 => '1777637811_iphone.jpg',
    2 => '1777637830_samsung.jpg',
    3 => '1777637844_xiaomi.jpg',
    4 => '1777637898_vivo.jpg',
    5 => '1777637866_macbook.jpg',
    6 => '1777637925_lap.jpg',
    7 => '1777637936_lap_rog.jpg',
    8 => '1777637925_lap.jpg',
    9 => '1777637975_tablet_apple.jpg',
    10 => '1777638009_tablet.jpg',
    11 => '1777638022_tai_nghe_ko_d__y.jpg',
    12 => '1777638032_d__y_s___c.jpg',
    13 => '1777638040_chu___t.jpg',
    14 => '1777638076______ng_h____1.jpg',
    15 => '1777638057______ng_h___.jpg',
    16 => '1777638105_tainghe.jpg',
    17 => '1777638085_loa.jpg',
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
$plan = [];

foreach ($mapping as $productId => $filename) {
    $path = $sourceRoot . '/' . $filename;
    if (!is_file($path) || !is_readable($path)) {
        throw new RuntimeException("Thiếu source image cho product #{$productId}: {$path}");
    }
    $contents = file_get_contents($path);
    $mime = $contents === false ? '' : ($finfo->buffer($contents) ?: '');
    if ($contents === false || !isset($extensions[$mime]) || @getimagesizefromstring($contents) === false) {
        throw new RuntimeException("Source image không hợp lệ cho product #{$productId}: {$filename}");
    }
    $plan[] = [
        'product_id' => $productId,
        'source' => $filename,
        'mime' => $mime,
        'size' => strlen($contents),
        'sha256' => hash('sha256', $contents),
    ];
}

if (!$apply) {
    echo json_encode(['mode' => 'dry-run', 'message' => 'Chưa thay đổi database/storage. Dùng --apply để import.', 'mapping' => $plan], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

$createdKeys = [];
$conn->beginTransaction();
try {
    $update = $conn->prepare('UPDATE products SET image = :image WHERE id = :id');
    foreach ($plan as $item) {
        $sourcePath = $sourceRoot . '/' . $item['source'];
        $key = runtimeImageKey('products', $extensions[$item['mime']]);
        storage()->putFile($sourcePath, $key, ['mime_type' => $item['mime'], 'size' => $item['size'], 'source' => 'local-images']);
        $createdKeys[] = $key;
        $update->execute(['image' => $key, 'id' => $item['product_id']]);
        $item['storage_key'] = $key;
        $item['image_url'] = runtimeImageUrl($key, 'assets/images');
        $item['source'] = $item['source'];
        $plan[array_search($item['product_id'], array_column($plan, 'product_id'), true)] = $item;
    }
    $conn->commit();
} catch (Throwable $exception) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    foreach ($createdKeys as $key) {
        try { storage()->delete($key); } catch (Throwable $ignored) { }
    }
    throw $exception;
}

echo json_encode(['mode' => 'apply', 'updated_count' => count($plan), 'mapping' => $plan], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
