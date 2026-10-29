<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$type = isset($_GET['type']) && is_string($_GET['type']) ? $_GET['type'] : '';
$filename = isset($_GET['file']) && is_string($_GET['file']) ? $_GET['file'] : '';

if ($type !== 'avatar' || $filename === '' || basename($filename) !== $filename || !preg_match('/^avatar_[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|gif|webp)$/i', $filename)) {
    http_response_code(404);
    exit;
}

$path = PROJECT_ROOT . '/uploads/avatars/' . $filename;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit;
}

$contents = file_get_contents($path);
$mimeType = $contents === false ? false : (new finfo(FILEINFO_MIME_TYPE))->buffer($contents);
if ($contents === false || !is_string($mimeType) || !str_starts_with($mimeType, 'image/')) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . strlen($contents));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
echo $contents;
