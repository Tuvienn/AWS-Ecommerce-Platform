<?php

final class StorageFactory
{
    /** @param array<string,mixed> $configuration */
    public static function create(array $configuration): StorageInterface
    {
        $driver = $configuration['driver'] ?? 'local';
        if ($driver === 'local') {
            return new LocalStorage(
                (string) ($configuration['local_root'] ?? ''),
                (string) ($configuration['public_base_url'] ?? ''),
            );
        }
        if ($driver === 's3') {
            return new S3Storage((array) ($configuration['s3'] ?? []));
        }
        throw new RuntimeException('STORAGE_DRIVER không được hỗ trợ.');
    }
}
