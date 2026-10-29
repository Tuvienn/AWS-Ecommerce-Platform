<?php

if (PHP_SAPI !== 'cli') {
    exit("Chỉ chạy bằng PHP CLI.\n");
}

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/functions.php';

$root = sys_get_temp_dir() . '/aws-ecommerce-platform-storage-test-' . bin2hex(random_bytes(6));
$driver = new LocalStorage($root, '/aws-ecommerce-platform');
$source = tempnam(sys_get_temp_dir(), 'storage-source-');
file_put_contents($source, 'phase4-local-storage');
$key = 'products/2026/10/test-object.txt';

try {
    $stored = $driver->putFile($source, $key, ['mime_type' => 'text/plain']);
    if ($stored->size !== strlen('phase4-local-storage') || $driver->read($key) !== 'phase4-local-storage' || !$driver->exists($key)) {
        throw new RuntimeException('Read/write local storage không đúng.');
    }
    $blocked = false;
    try {
        $driver->read('../secret.txt');
    } catch (RuntimeException) {
        $blocked = true;
    }
    if (!$blocked) {
        throw new RuntimeException('Path traversal không bị chặn.');
    }
    $driver->delete($key);
    echo "PASS local storage read/write/delete và path traversal protection\n";
} finally {
    @unlink($source);
    @rmdir($root . '/products/2026/10');
    @rmdir($root . '/products/2026');
    @rmdir($root . '/products');
    @rmdir($root);
}
