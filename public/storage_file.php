<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

$key = isset($_GET['key']) && is_string($_GET['key']) ? $_GET['key'] : '';
if ($key === '') {
    http_response_code(404);
    exit;
}

try {
    $contents = storage()->read($key);
    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'application/octet-stream';
    if (!str_starts_with($mimeType, 'image/')) {
        throw new RuntimeException('Object is not an image.');
    }
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . strlen($contents));
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    echo $contents;
} catch (Throwable $exception) {
    http_response_code(404);
}
