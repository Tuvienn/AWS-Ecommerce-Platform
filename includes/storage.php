<?php

function storage(): StorageInterface
{
    static $storage = null;
    if ($storage === null) {
        $storage = StorageFactory::create(projectConfig('storage'));
    }
    return $storage;
}

function isStorageObjectKey(string $value): bool
{
    return str_contains($value, '/');
}

function runtimeImageKey(string $namespace, string $extension): string
{
    $allowedNamespaces = ['products', 'banners', 'avatars'];
    if (!in_array($namespace, $allowedNamespaces, true) || !preg_match('/^[a-z0-9]+$/', $extension)) {
        throw new RuntimeException('Namespace hoặc extension storage không hợp lệ.');
    }
    return sprintf('%s/%s/%s/%s.%s', $namespace, date('Y'), date('m'), bin2hex(random_bytes(16)), $extension);
}

/** @return array{mime_type:string,extension:string,size:int} */
function validateRuntimeImage(array $file, int $maxBytes): array
{
    if (!isset($file['error'], $file['tmp_name'], $file['size']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload thất bại. Vui lòng chọn lại file.');
    }
    if (!is_uploaded_file((string) $file['tmp_name'])) {
        throw new RuntimeException('Nguồn file upload không hợp lệ.');
    }
    $size = (int) $file['size'];
    if ($size <= 0 || $size > $maxBytes) {
        throw new RuntimeException('Kích thước ảnh không hợp lệ hoặc vượt quá giới hạn.');
    }
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    $imageInformation = @getimagesize((string) $file['tmp_name']);
    if (!isset($extensions[$mimeType]) || $imageInformation === false || ($imageInformation['mime'] ?? '') !== $mimeType) {
        throw new RuntimeException('Nội dung file không phải ảnh được hỗ trợ.');
    }
    return ['mime_type' => $mimeType, 'extension' => $extensions[$mimeType], 'size' => $size];
}

function storeRuntimeImage(array $file, string $namespace, int $maxBytes): string
{
    $metadata = validateRuntimeImage($file, $maxBytes);
    $key = runtimeImageKey($namespace, $metadata['extension']);
    storage()->putUploadedFile($file, $key, $metadata);
    return $key;
}

function runtimeImageUrl(string $value, string $legacyDirectory): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }
    if (isStorageObjectKey($value)) {
        return storage()->url($value);
    }
    if (trim($legacyDirectory, '/') === 'uploads/avatars') {
        return appUrl('/legacy_file.php?type=avatar&file=' . rawurlencode($value));
    }
    return assetUrl('/' . trim($legacyDirectory, '/') . '/' . rawurlencode($value));
}

function deleteRuntimeImage(string $value, string $legacyDirectory, string $legacyPrefix = '', array $protectedLegacyFiles = []): void
{
    $value = trim($value);
    if ($value === '') {
        return;
    }
    if (preg_match('#^https?://#i', $value)) {
        return;
    }
    if (isStorageObjectKey($value)) {
        storage()->delete($value);
        return;
    }
    deleteUploadedFile(PROJECT_ROOT . '/' . trim($legacyDirectory, '/'), $value, $legacyPrefix, $protectedLegacyFiles);
}
