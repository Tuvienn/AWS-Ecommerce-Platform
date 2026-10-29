<?php

function csrfToken() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Session phải được khởi tạo trước khi tạo CSRF token.');
    }

    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="_csrf_token" value="'
        . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function isValidCsrfToken($token = null) {
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['_csrf_token'])) {
        return false;
    }

    if ($token === null) {
        $token = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    }

    return is_string($token) && hash_equals($_SESSION['_csrf_token'], $token);
}

function securityFailureResponse($statusCode, $message, $json = false) {
    http_response_code($statusCode);

    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    }

    exit;
}

function requirePostRequest($json = false) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        securityFailureResponse(405, 'Phương thức request không được hỗ trợ.', $json);
    }
}

function requireValidCsrfToken($json = false) {
    if (!isValidCsrfToken()) {
        securityFailureResponse(403, 'CSRF token không hợp lệ hoặc đã hết hạn.', $json);
    }
}

function validatedInteger($value, $minimum, $maximum) {
    $validated = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => [
            'min_range' => $minimum,
            'max_range' => $maximum,
        ],
    ]);

    return $validated === false ? null : $validated;
}

function isSafeContentUrl($url) {
    $url = trim((string) $url);
    if ($url === '' || $url === '#') {
        return true;
    }

    if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return false;
    }

    if (strpos($url, '//') === 0) {
        return true;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);
    if ($scheme !== null) {
        return in_array(strtolower($scheme), ['http', 'https'], true);
    }

    return true;
}

function destroyCurrentSession() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookieParameters = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookieParameters['path'],
            'domain' => $cookieParameters['domain'],
            'secure' => $cookieParameters['secure'],
            'httponly' => $cookieParameters['httponly'],
            'samesite' => $cookieParameters['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}

function storeUploadedImage($file, $destinationDirectory, $maxBytes, $prefix = 'image') {
    if (!is_array($file) || !isset($file['error'], $file['tmp_name'], $file['size'])) {
        throw new RuntimeException('File upload không hợp lệ.');
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload thất bại. Vui lòng chọn lại file.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Nguồn file upload không hợp lệ.');
    }

    $fileSize = (int) $file['size'];
    if ($fileSize <= 0 || $fileSize > $maxBytes) {
        throw new RuntimeException('Kích thước ảnh không hợp lệ hoặc vượt quá giới hạn.');
    }

    $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $imageInformation = @getimagesize($file['tmp_name']);

    if (!isset($mimeToExtension[$mimeType]) || $imageInformation === false) {
        throw new RuntimeException('Nội dung file không phải ảnh được hỗ trợ.');
    }

    if (($imageInformation['mime'] ?? '') !== $mimeType) {
        throw new RuntimeException('Định dạng ảnh không nhất quán.');
    }

    if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0755, true)) {
        throw new RuntimeException('Không thể chuẩn bị thư mục lưu ảnh.');
    }

    $destinationRealPath = realpath($destinationDirectory);
    if ($destinationRealPath === false || !is_writable($destinationRealPath)) {
        throw new RuntimeException('Thư mục lưu ảnh không khả dụng.');
    }

    $safePrefix = preg_replace('/[^a-z0-9_-]/i', '', $prefix) ?: 'image';
    $filename = $safePrefix . '_' . bin2hex(random_bytes(16)) . '.' . $mimeToExtension[$mimeType];
    $destinationPath = $destinationRealPath . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
        throw new RuntimeException('Không thể lưu file upload.');
    }

    return $filename;
}

function deleteUploadedFile($directory, $filename, $requiredPrefix = '', $protectedFilenames = []) {
    $filename = (string) $filename;
    if ($filename === '' || basename($filename) !== $filename) {
        return false;
    }

    if ($requiredPrefix !== '' && strpos($filename, $requiredPrefix . '_') !== 0) {
        return false;
    }

    if (in_array($filename, $protectedFilenames, true)) {
        return false;
    }

    $directoryRealPath = realpath($directory);
    if ($directoryRealPath === false) {
        return false;
    }

    $candidate = $directoryRealPath . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($candidate)) {
        return false;
    }

    return unlink($candidate);
}
