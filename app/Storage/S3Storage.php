<?php

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

final class S3Storage implements StorageInterface
{
    private S3Client $client;
    private string $bucket;
    private string $prefix;

    /** @param array<string,mixed> $configuration */
    public function __construct(private readonly array $configuration)
    {
        $region = trim((string) ($configuration['region'] ?? ''));
        $this->bucket = trim((string) ($configuration['bucket'] ?? ''));
        $this->prefix = trim((string) ($configuration['prefix'] ?? ''), '/');

        if ($region === '' || $this->bucket === '') {
            throw new RuntimeException('S3 requires AWS_REGION and S3_BUCKET.');
        }

        if (!class_exists(S3Client::class)) {
            throw new RuntimeException('AWS SDK for PHP is required for the S3 storage driver.');
        }

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $region,
        ]);
    }

    public function putUploadedFile(array $uploadedFile, string $key, array $metadata = []): StoredObject
    {
        if (
            !isset($uploadedFile['error'], $uploadedFile['tmp_name'])
            || (int) $uploadedFile['error'] !== UPLOAD_ERR_OK
        ) {
            throw new RuntimeException('Upload failed.');
        }

        if (!is_uploaded_file((string) $uploadedFile['tmp_name'])) {
            throw new RuntimeException('Invalid uploaded file.');
        }

        return $this->putFile((string) $uploadedFile['tmp_name'], $key, $metadata);
    }

    public function putFile(string $sourcePath, string $key, array $metadata = []): StoredObject
    {
        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            throw new RuntimeException('Source file is not readable.');
        }

        $normalizedKey = $this->validateKey($key);
        $objectKey = $this->objectKey($normalizedKey);

        if ($this->exists($normalizedKey)) {
            throw new RuntimeException('Object key already exists.');
        }

        $mimeType = (string) ($metadata['mime_type'] ?? 'application/octet-stream');

        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $objectKey,
                'SourceFile' => $sourcePath,
                'ContentType' => $mimeType,
            ]);
        } catch (AwsException $exception) {
            throw new RuntimeException('Unable to store object in S3.', 0, $exception);
        }

        $size = filesize($sourcePath);
        if ($size === false) {
            throw new RuntimeException('Unable to determine object size.');
        }

        return new StoredObject($normalizedKey, (int) $size, $mimeType);
    }

    public function read(string $key): string
    {
        $normalizedKey = $this->validateKey($key);

        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $this->objectKey($normalizedKey),
            ]);

            return (string) $result['Body'];
        } catch (AwsException $exception) {
            throw new RuntimeException('Unable to read object from S3.', 0, $exception);
        }
    }

    public function url(string $key): string
    {
        $normalizedKey = $this->validateKey($key);

        return '/storage_file.php?key=' . rawurlencode($normalizedKey);
    }

    public function exists(string $key): bool
    {
        $normalizedKey = $this->validateKey($key);

        try {
            return $this->client->doesObjectExist(
                $this->bucket,
                $this->objectKey($normalizedKey)
            );
        } catch (AwsException $exception) {
            throw new RuntimeException('Unable to check S3 object.', 0, $exception);
        }
    }

    public function delete(string $key): void
    {
        $normalizedKey = $this->validateKey($key);

        if (!$this->exists($normalizedKey)) {
            return;
        }

        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $this->objectKey($normalizedKey),
            ]);
        } catch (AwsException $exception) {
            throw new RuntimeException('Unable to delete object from S3.', 0, $exception);
        }
    }

    private function objectKey(string $key): string
    {
        return $this->prefix === ''
            ? $key
            : $this->prefix . '/' . $key;
    }

    private function validateKey(string $key): string
    {
        $key = trim(str_replace('\\', '/', $key));

        if ($key === '' || str_starts_with($key, '/') || str_contains($key, '//')) {
            throw new RuntimeException('Invalid object key.');
        }

        $segments = explode('/', $key);

        foreach ($segments as $segment) {
            if (
                $segment === ''
                || $segment === '.'
                || $segment === '..'
                || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$/', $segment)
            ) {
                throw new RuntimeException('Invalid object key.');
            }
        }

        return implode('/', $segments);
    }
}
