<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Chỉ chạy script này bằng PHP CLI.\n");
    exit(1);
}

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$apply = in_array('--apply', $argv, true);
$mode = $apply ? 'APPLY' : 'DRY-RUN';
echo "Runtime upload migration: {$mode}\n";

$sources = [
    [
        'table' => 'products', 'id' => 'id', 'column' => 'image', 'namespace' => 'products',
        'directory' => PROJECT_ROOT . '/assets/images',
    ],
    [
        'table' => 'banners', 'id' => 'id', 'column' => 'image', 'namespace' => 'banners',
        'directory' => PROJECT_ROOT . '/assets/images',
    ],
    [
        'table' => 'users', 'id' => 'id', 'column' => 'avatar', 'namespace' => 'avatars',
        'directory' => PROJECT_ROOT . '/uploads/avatars',
    ],
];

$summary = ['legacy' => 0, 'migrated' => 0, 'missing' => 0, 'skipped' => 0, 'errors' => 0];
foreach ($sources as $source) {
    $statement = $conn->query(sprintf(
        'SELECT %s AS id, %s AS object_value FROM %s WHERE %s IS NOT NULL AND %s <> "" ORDER BY %s',
        $source['id'], $source['column'], $source['table'], $source['column'], $source['column'], $source['id']
    ));
    foreach ($statement->fetchAll() as $row) {
        $value = (string) $row['object_value'];
        if (isStorageObjectKey($value) || preg_match('#^https?://#i', $value)) {
            $summary['skipped']++;
            continue;
        }
        if (basename($value) !== $value) {
            fwrite(STDERR, "SKIP invalid legacy value {$source['table']}#{$row['id']}\n");
            $summary['errors']++;
            continue;
        }

        $summary['legacy']++;
        $legacyPath = $source['directory'] . DIRECTORY_SEPARATOR . $value;
        if (!is_file($legacyPath) || !is_readable($legacyPath)) {
            echo "MISSING {$source['table']}#{$row['id']} {$value}\n";
            $summary['missing']++;
            continue;
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($legacyPath);
        $extensionMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        if (!isset($extensionMap[$mimeType]) || @getimagesize($legacyPath) === false) {
            fwrite(STDERR, "SKIP unsupported image {$source['table']}#{$row['id']} {$value}\n");
            $summary['errors']++;
            continue;
        }

        $key = runtimeImageKey($source['namespace'], $extensionMap[$mimeType]);
        if (!$apply) {
            echo "WOULD-MIGRATE {$source['table']}#{$row['id']} {$value} -> {$key}\n";
            continue;
        }

        try {
            $stored = storage()->putFile($legacyPath, $key, ['mime_type' => $mimeType]);
            if (sha1_file($legacyPath) !== sha1(storage()->read($stored->key))) {
                storage()->delete($stored->key);
                throw new RuntimeException('Checksum không khớp sau khi copy.');
            }
            $update = $conn->prepare(sprintf('UPDATE %s SET %s = ? WHERE %s = ? AND %s = ?', $source['table'], $source['column'], $source['id'], $source['column']));
            $update->execute([$stored->key, $row['id'], $value]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Không thể cập nhật object key trong database.');
            }
            echo "MIGRATED {$source['table']}#{$row['id']} {$value} -> {$stored->key}\n";
            $summary['migrated']++;
        } catch (Throwable $exception) {
            fwrite(STDERR, "ERROR {$source['table']}#{$row['id']}: {$exception->getMessage()}\n");
            $summary['errors']++;
        }
    }
}

echo 'SUMMARY ' . json_encode($summary, JSON_UNESCAPED_UNICODE) . PHP_EOL;
if ($summary['errors'] > 0) {
    exit(1);
}
