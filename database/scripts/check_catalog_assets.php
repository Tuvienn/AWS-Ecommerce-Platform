<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Chi chay script nay bang CLI.\n");
}

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$projectRoot = dirname(__DIR__, 2);
$imageRoot = $projectRoot . '/public/assets/images';
$avatarRoot = $projectRoot . '/uploads/avatars';

$finfo = new finfo(FILEINFO_MIME_TYPE);

$inspect = static function (string $value, string $legacyRoot) use ($finfo): array {
    $value = trim($value);
    if ($value === '') {
        return ['status' => 'missing', 'kind' => 'empty', 'value' => $value];
    }
    if (preg_match('#^https?://#i', $value)) {
        return ['status' => 'external', 'kind' => 'url', 'value' => $value];
    }
    try {
        if (isStorageObjectKey($value)) {
            if (!storage()->exists($value)) {
                return ['status' => 'missing', 'kind' => 'storage', 'value' => $value];
            }
            $contents = storage()->read($value);
            $kind = 'storage';
        } else {
            $path = $legacyRoot . '/' . basename($value);
            if (!is_file($path) || !is_readable($path)) {
                return ['status' => 'missing', 'kind' => 'legacy', 'value' => $value];
            }
            $contents = file_get_contents($path);
            if ($contents === false) {
                return ['status' => 'invalid', 'kind' => 'legacy', 'value' => $value, 'reason' => 'read_failed'];
            }
            $kind = 'legacy';
        }
        $mime = $finfo->buffer($contents) ?: 'application/octet-stream';
        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $size = strlen($contents);
        if (!in_array($mime, $allowedMime, true) || @getimagesizefromstring($contents) === false || $size <= 0) {
            return ['status' => 'invalid', 'kind' => $kind, 'value' => $value, 'mime' => $mime, 'size' => $size, 'reason' => 'not_a_supported_image'];
        }
        return ['status' => 'valid', 'kind' => $kind, 'value' => $value, 'mime' => $mime, 'size' => $size, 'sha256' => hash('sha256', $contents)];
    } catch (Throwable $exception) {
        return ['status' => 'invalid', 'kind' => isStorageObjectKey($value) ? 'storage' : 'legacy', 'value' => $value, 'reason' => $exception->getMessage()];
    }
};

$result = [
    'health_check' => 'catalog_assets',
    'generated_at' => date(DATE_ATOM),
    'status' => 'PASS',
    'products' => ['total' => 0, 'valid' => 0, 'external' => 0, 'missing' => 0, 'invalid' => 0, 'issues' => [], 'assets' => []],
    'banners' => ['total' => 0, 'valid' => 0, 'external' => 0, 'missing' => 0, 'invalid' => 0, 'issues' => [], 'assets' => []],
    'avatars' => ['total' => 0, 'valid' => 0, 'external' => 0, 'missing' => 0, 'invalid' => 0, 'issues' => [], 'assets' => []],
];

$checks = [
    'products' => [$conn->query('SELECT id, image FROM products ORDER BY id'), $imageRoot, 'image'],
    'banners' => [$conn->query('SELECT id, image FROM banners ORDER BY id'), $imageRoot, 'image'],
    'avatars' => [$conn->query("SELECT id, avatar FROM users WHERE avatar IS NOT NULL AND TRIM(avatar) <> '' ORDER BY id"), $avatarRoot, 'avatar'],
];

foreach ($checks as $type => [$statement, $legacyRoot, $column]) {
    while ($row = $statement->fetch()) {
        $result[$type]['total']++;
        $inspection = $inspect((string) $row[$column], $legacyRoot);
        $result[$type]['assets'][] = ['id' => (int) $row['id']] + $inspection;
        $status = $inspection['status'];
        if ($status === 'valid') {
            $result[$type]['valid']++;
        } elseif ($status === 'external') {
            $result[$type]['external']++;
        } else {
            $result[$type][$status]++;
            $result[$type]['issues'][] = ['id' => (int) $row['id']] + $inspection;
            $result['status'] = 'FAIL';
        }
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($result['status'] === 'PASS' ? 0 : 1);
