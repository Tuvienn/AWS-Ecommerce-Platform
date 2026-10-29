<?php

$storageDriver = getenv('STORAGE_DRIVER');
$storageDriver = $storageDriver === false || trim($storageDriver) === ''
    ? 'local'
    : strtolower(trim($storageDriver));

$localRoot = getenv('STORAGE_LOCAL_ROOT');
$localRoot = $localRoot === false || trim($localRoot) === ''
    ? dirname(__DIR__) . '/storage/uploads'
    : trim($localRoot);
if (!str_starts_with($localRoot, DIRECTORY_SEPARATOR)) {
    $localRoot = dirname(__DIR__) . '/' . ltrim($localRoot, '/');
}

$publicBaseUrl = getenv('STORAGE_PUBLIC_BASE_URL');
if ($publicBaseUrl === false || trim($publicBaseUrl) === '') {
    $appConfiguration = require __DIR__ . '/app.php';
    $publicBaseUrl = $appConfiguration['base_path'];
}
$publicBaseUrl = rtrim(trim((string) $publicBaseUrl), '/');

return [
    'driver' => $storageDriver,
    'local_root' => $localRoot,
    'public_base_url' => $publicBaseUrl,
    's3' => [
        'region' => getenv('AWS_REGION') ?: '',
        'bucket' => getenv('S3_BUCKET') ?: '',
        'prefix' => trim((string) (getenv('S3_PREFIX') ?: ''), '/'),
        'private' => filter_var(getenv('S3_PRIVATE') ?: '1', FILTER_VALIDATE_BOOL),
    ],
];
