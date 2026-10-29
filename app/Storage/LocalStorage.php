<?php

final class LocalStorage implements StorageInterface
{
    public function __construct(
        private readonly string $rootDirectory,
        private readonly string $publicBaseUrl = '',
    ) {
    }

    public function putUploadedFile(array $uploadedFile, string $key, array $metadata = []): StoredObject
    {
        if (!isset($uploadedFile['error'], $uploadedFile['tmp_name']) || (int) $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload thất bại. Vui lòng chọn lại file.');
        }
        if (!is_uploaded_file((string) $uploadedFile['tmp_name'])) {
            throw new RuntimeException('Nguồn file upload không hợp lệ.');
        }

        $destination = $this->destinationPath($key);
        $this->ensureDestinationDirectory($destination);
        if (file_exists($destination)) {
            throw new RuntimeException('Object key đã tồn tại.');
        }
        if (!move_uploaded_file((string) $uploadedFile['tmp_name'], $destination)) {
            throw new RuntimeException('Không thể lưu file upload.');
        }

        return $this->storedObject($key, $destination, $metadata);
    }

    public function putFile(string $sourcePath, string $key, array $metadata = []): StoredObject
    {
        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            throw new RuntimeException('Không thể đọc file nguồn.');
        }

        $destination = $this->destinationPath($key);
        $this->ensureDestinationDirectory($destination);
        if (file_exists($destination)) {
            throw new RuntimeException('Object key đã tồn tại.');
        }
        if (!copy($sourcePath, $destination)) {
            throw new RuntimeException('Không thể sao chép file vào local storage.');
        }

        return $this->storedObject($key, $destination, $metadata);
    }

    public function read(string $key): string
    {
        $path = $this->destinationPath($key);
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Không tìm thấy object storage.');
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Không thể đọc object storage.');
        }
        return $contents;
    }

    public function url(string $key): string
    {
        $this->destinationPath($key);
        $base = rtrim($this->publicBaseUrl, '/');
        return ($base === '' ? '' : $base) . '/storage_file.php?key=' . rawurlencode($key);
    }

    public function exists(string $key): bool
    {
        return is_file($this->destinationPath($key));
    }

    public function delete(string $key): void
    {
        $path = $this->destinationPath($key);
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Không thể xóa object storage.');
        }
    }

    private function destinationPath(string $key): string
    {
        $normalizedKey = $this->validateKey($key);
        $root = $this->ensureRootDirectory();
        return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedKey);
    }

    private function ensureRootDirectory(): string
    {
        if (!is_dir($this->rootDirectory) && !mkdir($this->rootDirectory, 0750, true)) {
            throw new RuntimeException('Không thể chuẩn bị local storage.');
        }
        $root = realpath($this->rootDirectory);
        if ($root === false || !is_writable($root)) {
            throw new RuntimeException('Local storage không khả dụng.');
        }
        return $root;
    }

    private function ensureDestinationDirectory(string $destination): void
    {
        $directory = dirname($destination);
        if (!is_dir($directory) && !mkdir($directory, 0750, true)) {
            throw new RuntimeException('Không thể chuẩn bị thư mục object storage.');
        }
    }

    private function validateKey(string $key): string
    {
        $key = trim(str_replace('\\', '/', $key));
        if ($key === '' || str_starts_with($key, '/') || str_contains($key, '//')) {
            throw new RuntimeException('Object key không hợp lệ.');
        }
        $segments = explode('/', $key);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$/', $segment)) {
                throw new RuntimeException('Object key không hợp lệ.');
            }
        }
        return implode('/', $segments);
    }

    /** @param array<string,string|int> $metadata */
    private function storedObject(string $key, string $path, array $metadata): StoredObject
    {
        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException('Không thể đọc kích thước object storage.');
        }
        $mimeType = (string) ($metadata['mime_type'] ?? 'application/octet-stream');
        return new StoredObject($key, (int) $size, $mimeType);
    }
}
