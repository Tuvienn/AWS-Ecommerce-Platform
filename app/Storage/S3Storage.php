<?php

final class S3Storage implements StorageInterface
{
    /** @param array<string,mixed> $configuration */
    public function __construct(private readonly array $configuration)
    {
        if (($configuration['region'] ?? '') === '' || ($configuration['bucket'] ?? '') === '') {
            throw new RuntimeException('S3 cần AWS_REGION và S3_BUCKET.');
        }
        if (!class_exists('Aws\\S3\\S3Client')) {
            throw new RuntimeException('S3 driver cần AWS SDK for PHP được cài qua dependency được phê duyệt.');
        }
    }

    public function putUploadedFile(array $uploadedFile, string $key, array $metadata = []): StoredObject
    {
        if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file((string) $uploadedFile['tmp_name'])) {
            throw new RuntimeException('Nguồn file upload không hợp lệ.');
        }
        return $this->putFile((string) $uploadedFile['tmp_name'], $key, $metadata);
    }

    public function putFile(string $sourcePath, string $key, array $metadata = []): StoredObject
    {
        throw new RuntimeException('S3 adapter chưa được kích hoạt trong môi trường hiện tại.');
    }

    public function read(string $key): string
    {
        throw new RuntimeException('S3 adapter chưa được kích hoạt trong môi trường hiện tại.');
    }

    public function url(string $key): string
    {
        throw new RuntimeException('S3 adapter chưa được kích hoạt trong môi trường hiện tại.');
    }

    public function exists(string $key): bool
    {
        throw new RuntimeException('S3 adapter chưa được kích hoạt trong môi trường hiện tại.');
    }

    public function delete(string $key): void
    {
        throw new RuntimeException('S3 adapter chưa được kích hoạt trong môi trường hiện tại.');
    }
}
